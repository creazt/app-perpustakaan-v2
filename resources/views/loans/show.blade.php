<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Peminjaman</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 40px;
            max-width: 700px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 16px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px 12px;
            text-align: left;
        }

        .info th {
            width: 180px;
            background: #f3f4f6;
        }

        .text-right {
            text-align: right;
        }

        tfoot td {
            font-weight: bold;
            background: #f9fafb;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            color: #fff;
            font-size: 0.85em;
            font-weight: bold;
        }

        .badge-success { background: #10b981; }
        .badge-warning { background: #f59e0b; }
        .badge-danger { background: #ef4444; }
    </style>
</head>

<body>
    @php
        $statusTampil = $loan->statusTampil();
        $rincian = $loan->rincianDenda();
        $tarif = \App\Models\Loan::DENDA_PER_BUKU_PER_HARI;
    @endphp

    <h1>Detail Peminjaman</h1>
    <p><a href="{{ route('loans.index') }}">&larr; Kembali ke daftar peminjaman</a></p>

    <table class="info">
        <tr>
            <th>Anggota</th>
            <td>{{ $loan->member->nama }} ({{ $loan->member->nim }})</td>
        </tr>
        <tr>
            <th>Petugas</th>
            <td>{{ $loan->user->name }}</td>
        </tr>
        <tr>
            <th>Tanggal Pinjam</th>
            <td>{{ $loan->tanggal_pinjam }}</td>
        </tr>
        <tr>
            <th>Tanggal Kembali (batas)</th>
            <td>{{ $loan->tanggal_kembali }}</td>
        </tr>
        <tr>
            <th>Tanggal Dikembalikan</th>
            <td>{{ $loan->tanggal_dikembalikan ?? '-' }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>
                @if($statusTampil === 'dikembalikan')
                <span class="badge badge-success">Dikembalikan</span>
                @elseif($statusTampil === 'terlambat')
                <span class="badge badge-danger">Terlambat</span>
                @else
                <span class="badge badge-warning">Dipinjam</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>Hari Terlambat</th>
            <td>{{ $loan->hariTerlambat() }} hari</td>
        </tr>
        <tr>
            <th>Total Denda</th>
            <td>
                Rp {{ number_format($loan->dendaSaatIni(), 0, ',', '.') }}
                @if($statusTampil === 'terlambat')
                <small>(denda berjalan, belum dikembalikan)</small>
                @endif
            </td>
        </tr>
    </table>

    <h2>Buku yang Dipinjam &amp; Rincian Denda</h2>
    <table>
        <thead>
            <tr>
                <th>Judul</th>
                <th>Penulis</th>
                <th class="text-right">Terlambat</th>
                <th class="text-right">Denda</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($loan->loanItems as $item)
            <tr>
                <td>{{ $item->book?->judul ?? '-' }}</td>
                <td>{{ $item->book?->penulis ?? '-' }}</td>
                <td class="text-right">{{ $rincian[$loop->index]['hari'] }} hari</td>
                <td class="text-right">Rp {{ number_format($rincian[$loop->index]['denda'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">
                    Total ({{ $loan->jumlahBuku() }} buku &times; {{ $loan->hariTerlambat() }} hari
                    &times; Rp {{ number_format($tarif, 0, ',', '.') }})
                </td>
                <td class="text-right">Rp {{ number_format($loan->dendaSaatIni(), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>