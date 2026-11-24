@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1>Dashboard</h1>
    <p>Ringkasan statistik perpustakaan, diambil langsung dari <code>GET /api/stats</code>.</p>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="value">{{ $stats['total_buku'] }}</div>
            <div class="label">Total Buku</div>
        </div>
        <div class="stat-card">
            <div class="value">{{ $stats['total_anggota'] }}</div>
            <div class="label">Total Anggota</div>
        </div>
        <div class="stat-card">
            <div class="value">{{ $stats['peminjaman_aktif'] }}</div>
            <div class="label">Peminjaman Aktif</div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="value" style="color: #b91c1c;">{{ $stats['jumlah_terlambat'] }}</div>
            <div class="label">Peminjaman Terlambat</div>
        </div>
        <div class="stat-card">
            <div class="value" style="color: #b91c1c;">Rp {{ number_format($stats['total_denda'], 0, ',', '.') }}</div>
            <div class="label">Total Denda</div>
            <div class="label" style="font-size: 12px;">
                Tercatat Rp {{ number_format($stats['denda_tercatat'], 0, ',', '.') }}
                + berjalan Rp {{ number_format($stats['denda_berjalan'], 0, ',', '.') }}
            </div>
        </div>
    </div>

    <h2 style="margin-top: 30px;">Buku Terpopuler</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Judul</th>
                <th>Dipinjam (kali)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stats['buku_terpopuler'] as $i => $buku)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $buku['judul'] }}</td>
                <td>{{ $buku['dipinjam'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3">Belum ada data peminjaman.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 24px;"><a href="{{ route('loans.report') }}" class="btn">Lihat Laporan Peminjaman</a></p>
@endsection