<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->validate([
            'status' => 'nullable|in:dipinjam,terlambat,dikembalikan',
            'dari' => 'nullable|date',
            'sampai' => 'nullable|date|after_or_equal:dari',
        ], [
            'sampai.after_or_equal' => 'Tanggal "sampai" tidak boleh sebelum tanggal "dari".',
        ]);

        $loans = Loan::with(['member', 'user', 'loanItems.book'])
            ->filter($filter)
            ->latest('id')
            ->paginate(10)
            ->withQueryString(); // supaya filter tidak hilang saat pindah halaman

        return view('loans.index', compact('loans'));
    }

    public function create()
    {
        // Hanya anggota aktif yang boleh meminjam
        $members = Member::where('status', 'aktif')->get();
        $books = Book::all();

        return view('loans.create', compact('members', 'books'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|integer|exists:members,id',
            'tanggal_pinjam' => 'required|date',
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_pinjam',
            'book_ids' => 'required|array|min:1',
            'book_ids.*' => 'integer|distinct|exists:books,id',
        ]);

        // Aturan batas pinjam, stok, dan status anggota ada di Loan::buat()
        Loan::buat($validated + ['user_id' => Auth::id()]);

        return redirect()->route('loans.index')
            ->with('success', 'Data peminjaman berhasil ditambahkan.');
    }

    public function show(string $id)
    {
        $loan = Loan::with(['member', 'user', 'loanItems.book'])->findOrFail($id);
        return view('loans.show', compact('loan'));
    }

    public function edit(string $id)
    {
        $loan = Loan::with(['member', 'user', 'loanItems.book'])->findOrFail($id);
        return view('loans.edit', compact('loan'));
    }

    public function update(Request $request, string $id)
    {
        $loan = Loan::findOrFail($id);

        $validated = $request->validate([
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_pinjam',
            'status' => 'required|in:dipinjam,dikembalikan,terlambat',
        ], [
            'tanggal_kembali.required' => 'Tanggal kembali wajib diisi.',
            'tanggal_kembali.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal pinjam.',
        ]);

        $loan->update($validated);

        return redirect()->route('loans.index')
            ->with('success', 'Transaksi peminjaman berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $loan = Loan::findOrFail($id);
        $loan->loanItems()->delete();
        $loan->delete();

        return redirect()->route('loans.index')
            ->with('success', 'Transaksi peminjaman berhasil dihapus.');
    }

    public function kembalikan(string $id)
    {
        $loan = Loan::findOrFail($id);

        // Isi tanggal dikembalikan, simpan denda, dan tambah stok
        $loan->kembalikan();

        $pesan = $loan->denda > 0
            ? 'Buku berhasil dikembalikan. Denda keterlambatan: Rp ' . number_format($loan->denda, 0, ',', '.')
            : 'Buku berhasil dikembalikan tepat waktu. Tidak ada denda.';

        return redirect()->route('loans.index')->with('success', $pesan);
    }

    public function report(Request $request)
    {
        $body = ['data' => [], 'meta' => null];

        try {
            $response = Http::get(config('services.internal_api.base_url') . '/api/loans', [
                'page' => $request->query('page', 1),
            ]);

            if ($response->successful()) {
                $body = $response->json();
            }
        } catch (ConnectionException $e) {
        }

        return view('loans.report', [
            'loans' => $body['data'] ?? [],
            'meta' => $body['meta'] ?? null,
        ]);
    }
}