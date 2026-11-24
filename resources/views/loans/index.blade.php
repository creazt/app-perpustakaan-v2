@extends('layouts.app')

@section('title', 'Daftar Peminjaman')

@section('content')
<h1>Daftar Peminjaman</h1>
<p><a href="{{ route('loans.create') }}" class="btn">+ Tambah Peminjaman</a></p>

@if(session('success'))
<div style="background: #dcfce7; color: #166534; padding: 10px; margin-bottom: 10px; border-radius: 4px;">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div style="background: #fee2e2; color: #991b1b; padding: 10px; margin-bottom: 10px; border-radius: 4px;">
    @foreach($errors->all() as $error)
    <div>{{ $error }}</div>
    @endforeach
</div>
@endif

<form method="GET" action="{{ route('loans.index') }}" style="margin-bottom: 16px;">
    <select name="status" style="padding: 6px;">
        <option value="">Semua status</option>
        <option value="dipinjam" @selected(request('status')==='dipinjam' )>Dipinjam</option>
        <option value="terlambat" @selected(request('status')==='terlambat' )>Terlambat</option>
        <option value="dikembalikan" @selected(request('status')==='dikembalikan' )>Dikembalikan</option>
    </select>
    <label>Tgl pinjam dari
        <input type="date" name="dari" value="{{ request('dari') }}" style="padding: 5px;">
    </label>
    <label>sampai
        <input type="date" name="sampai" value="{{ request('sampai') }}" style="padding: 5px;">
    </label>
    <button type="submit" class="btn">Filter</button>
    @if (request()->filled('status') || request()->filled('dari') || request()->filled('sampai'))
    <a href="{{ route('loans.index') }}">Reset</a>
    @endif
</form>

<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #f1f5f9;">
            <th>ID</th>
            <th>Anggota</th>
            <th>Petugas</th>
            <th>Buku</th>
            <th>Tgl Pinjam</th>
            <th>Tgl Kembali</th>
            <th>Status</th>
            <th>Denda</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($loans as $loan)
        @php $statusTampil = $loan->statusTampil(); @endphp
        <tr>
            <td>{{ $loan->id }}</td>
            <td>{{ $loan->member->nama }}</td>
            <td>{{ $loan->user->name }}</td>
            <td>
                @foreach ($loan->loanItems as $item)
                {{ $item->book->judul }}@if (!$loop->last), @endif
                @endforeach
            </td>
            <td style="white-space: nowrap;">{{ $loan->tanggal('tanggal_pinjam') }}</td>
            <td style="white-space: nowrap;">{{ $loan->tanggal('tanggal_kembali') }}</td>
            <td>
                @if($statusTampil === 'dikembalikan')
                <span style="color: green; font-weight: bold;">Dikembalikan</span>
                @elseif($statusTampil === 'terlambat')
                <span style="color: red; font-weight: bold;">Terlambat</span>
                <br><small>{{ $loan->hariTerlambat() }} hari</small>
                @else
                <span style="color: orange; font-weight: bold;">Dipinjam</span>
                @endif
            </td>
            <td>
                @if($statusTampil === 'dikembalikan')
                {{ $loan->denda > 0 ? 'Rp ' . number_format($loan->denda, 0, ',', '.') : '-' }}
                @elseif($statusTampil === 'terlambat')
                <span style="color: red;">Rp {{ number_format($loan->dendaSaatIni(), 0, ',', '.') }}</span>
                <br><small>berjalan</small>
                @else
                -
                @endif
            </td>
            <td>
                <a href="{{ route('loans.show', $loan->id) }}">Detail</a> |
                <a href="{{ route('loans.edit', $loan->id) }}">Edit</a> |

                <form action="{{ route('loans.destroy', $loan->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Yakin hapus?')">Hapus</button>
                </form>

                @if ($loan->status !== 'dikembalikan')
                |
                <form action="{{ route('loans.kembalikan', $loan->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('PUT')
                    <button type="submit" style="background:none; border:none; color:#059669; cursor:pointer; text-decoration:underline; padding:0;">Kembalikan</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align: center;">
                {{ request()->filled('status') || request()->filled('dari') || request()->filled('sampai')
                    ? 'Tidak ada peminjaman yang cocok dengan filter.'
                    : 'Belum ada data peminjaman.' }}
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top: 20px;">
    {{ $loans->links() }}
</div>
@endsection