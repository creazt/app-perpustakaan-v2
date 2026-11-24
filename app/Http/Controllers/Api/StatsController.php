<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;

class StatsController extends Controller
{
    /**
     * Menampilkan ringkasan statistik perpustakaan
     */
    public function index()
    {
        // Peminjaman yang belum dikembalikan dan sudah lewat tanggal_kembali
        $terlambat = Loan::terlambat()->with('loanItems')->get();

        $dendaBerjalan = $terlambat->sum(fn ($loan) => $loan->hitungDenda());
        $dendaTercatat = (int) Loan::where('status', 'dikembalikan')->sum('denda');

        $populer = Book::select('id', 'judul')
            ->withCount('loanItems')
            ->whereHas('loanItems')
            ->orderByDesc('loan_items_count')
            ->orderBy('judul')
            ->limit(5)
            ->get()
            ->map(fn ($book) => [
                'id' => $book->id,
                'judul' => $book->judul,
                'dipinjam' => $book->loan_items_count,
            ]);

        return response()->json([
            'total_buku' => Book::count(),
            'total_anggota' => Member::count(),
            'peminjaman_aktif' => Loan::aktif()->count(),
            'jumlah_terlambat' => $terlambat->count(),
            'total_denda' => $dendaTercatat + $dendaBerjalan,
            'denda_tercatat' => $dendaTercatat,
            'denda_berjalan' => $dendaBerjalan,
            'buku_terpopuler' => $populer,
        ]);
    }
}