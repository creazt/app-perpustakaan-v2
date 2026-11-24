<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Loan extends Model
{
    /** Maksimal buku yang boleh dipinjam sekaligus oleh satu anggota */
    public const MAKS_BUKU_AKTIF = 3;

    /** Denda per buku per hari keterlambatan (rupiah) */
    public const DENDA_PER_BUKU_PER_HARI = 1000;

    protected $fillable = [
        'member_id', 'user_id', 'tanggal_pinjam', 'tanggal_kembali',
        'tanggal_dikembalikan', 'status', 'denda',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loanItems(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }

    /* ---------------------------------------------------------------
     |  Scope
     |---------------------------------------------------------------*/

    /** Peminjaman yang bukunya belum dikembalikan */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', '!=', 'dikembalikan');
    }

    /** Belum dikembalikan DAN sudah lewat tanggal_kembali */
    public function scopeTerlambat(Builder $query): Builder
    {
        return $query->aktif()->where('tanggal_kembali', '<', now()->toDateString());
    }

    /**
     * Filter daftar peminjaman.
     * $filter: status (dipinjam|terlambat|dikembalikan), dari, sampai (tanggal pinjam)
     */
    public function scopeFilter(Builder $query, array $filter): Builder
    {
        $hariIni = now()->toDateString();
        $status = $filter['status'] ?? null;

        if ($status === 'dipinjam') {
            $query->aktif()->where('tanggal_kembali', '>=', $hariIni);
        } elseif ($status === 'terlambat') {
            $query->aktif()->where('tanggal_kembali', '<', $hariIni);
        } elseif ($status === 'dikembalikan') {
            $query->where('status', 'dikembalikan');
        }

        if (! empty($filter['dari'])) {
            $query->where('tanggal_pinjam', '>=', $filter['dari']);
        }

        if (! empty($filter['sampai'])) {
            $query->where('tanggal_pinjam', '<=', $filter['sampai']);
        }

        return $query;
    }

    /* ---------------------------------------------------------------
     |  Status & denda
     |---------------------------------------------------------------*/

    /** Status untuk ditampilkan: terlambat dihitung otomatis dari tanggal */
    public function statusTampil(): string
    {
        if ($this->status === 'dikembalikan') {
            return 'dikembalikan';
        }

        $batas = Carbon::parse($this->tanggal_kembali)->startOfDay();

        return $batas->lt(now()->startOfDay()) ? 'terlambat' : 'dipinjam';
    }

    public function jumlahBuku(): int
    {
        return $this->loanItems->count();
    }

    /**
     * Jumlah hari terlambat.
     * Sudah dikembalikan: dihitung sampai tanggal_dikembalikan.
     * Belum dikembalikan: dihitung sampai hari ini.
     */
    public function hariTerlambat(?string $sampai = null): int
    {
        $batas = Carbon::parse($this->tanggal_kembali)->startOfDay();
        $akhir = Carbon::parse(
            $sampai ?? $this->tanggal_dikembalikan ?? now()->toDateString()
        )->startOfDay();

        return $akhir->gt($batas) ? (int) $batas->diffInDays($akhir) : 0;
    }

    /** Denda = jumlah buku x hari terlambat x tarif per buku per hari */
    public function hitungDenda(?string $sampai = null): int
    {
        return $this->hariTerlambat($sampai)
            * $this->jumlahBuku()
            * self::DENDA_PER_BUKU_PER_HARI;
    }

    /** Denda yang tercatat (sudah dikembalikan) atau denda berjalan (belum dikembalikan) */
    public function dendaSaatIni(): int
    {
        return $this->status === 'dikembalikan'
            ? (int) $this->denda
            : $this->hitungDenda();
    }

    /** Rincian denda per buku (butuh relasi loanItems.book sudah di-load) */
    public function rincianDenda(): array
    {
        $hari = $this->hariTerlambat();

        return $this->loanItems->map(fn ($item) => [
            'judul' => $item->book?->judul ?? '(buku sudah dihapus)',
            'hari' => $hari,
            'tarif' => self::DENDA_PER_BUKU_PER_HARI,
            'denda' => $hari * self::DENDA_PER_BUKU_PER_HARI,
        ])->all();
    }

    /* ---------------------------------------------------------------
     |  Transaksi
     |---------------------------------------------------------------*/

    /**
     * Buat peminjaman + item bukunya, dengan aturan: anggota harus aktif,
     * batas pinjam, dan stok tersedia (stok dikurangi).
     * Dipakai bersama oleh controller web dan API.
     *
     * @param  array  $data  member_id, user_id, tanggal_pinjam, tanggal_kembali, book_ids
     */
    public static function buat(array $data): self
    {
        return DB::transaction(function () use ($data) {
            $member = Member::findOrFail($data['member_id']);

            if ($member->status !== 'aktif') {
                throw ValidationException::withMessages([
                    'member_id' => 'Anggota berstatus nonaktif tidak dapat meminjam buku.',
                ]);
            }

            $bookIds = $data['book_ids'];

            $sedangDipinjam = LoanItem::whereHas('loan', function ($q) use ($member) {
                $q->where('member_id', $member->id)->aktif();
            })->count();

            if ($sedangDipinjam + count($bookIds) > self::MAKS_BUKU_AKTIF) {
                $sisa = max(self::MAKS_BUKU_AKTIF - $sedangDipinjam, 0);

                throw ValidationException::withMessages([
                    'book_ids' => 'Batas pinjam maksimal ' . self::MAKS_BUKU_AKTIF
                        . " buku. Anggota ini masih bisa meminjam {$sisa} buku lagi.",
                ]);
            }

            $books = Book::whereIn('id', $bookIds)->get();

            foreach ($books as $book) {
                if ($book->stok < 1) {
                    throw ValidationException::withMessages([
                        'book_ids' => "Stok buku \"{$book->judul}\" sedang habis.",
                    ]);
                }
            }

            $loan = self::create([
                'member_id' => $member->id,
                'user_id' => $data['user_id'],
                'tanggal_pinjam' => $data['tanggal_pinjam'],
                'tanggal_kembali' => $data['tanggal_kembali'],
                'status' => 'dipinjam',
            ]);

            foreach ($books as $book) {
                $loan->loanItems()->create(['book_id' => $book->id]);
                $book->decrement('stok');
            }

            return $loan;
        });
    }

    /** Pengembalian: isi tanggal, simpan total denda, dan tambah kembali stok */
    public function kembalikan(): self
    {
        if ($this->status === 'dikembalikan') {
            throw ValidationException::withMessages([
                'status' => 'Peminjaman ini sudah dikembalikan sebelumnya.',
            ]);
        }

        DB::transaction(function () {
            $hariIni = now()->toDateString();

            $this->update([
                'status' => 'dikembalikan',
                'tanggal_dikembalikan' => $hariIni,
                'denda' => $this->hitungDenda($hariIni),
            ]);

            foreach ($this->loanItems()->with('book')->get() as $item) {
                $item->book?->increment('stok');
            }
        });

        return $this;
    }
}