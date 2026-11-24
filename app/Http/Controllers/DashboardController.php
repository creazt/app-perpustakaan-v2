<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_buku' => 0,
            'total_anggota' => 0,
            'peminjaman_aktif' => 0,
            'jumlah_terlambat' => 0,
            'total_denda' => 0,
            'denda_tercatat' => 0,
            'denda_berjalan' => 0,
            'buku_terpopuler' => [],
        ];

        try {
            $response = Http::get(config('services.internal_api.base_url').'/api/stats');

            if ($response->successful()) {
                $stats = array_merge($stats, $response->json());
            }
        } catch (ConnectionException $e) {
            // Server internal API (port 8011) tidak bisa dihubungi - statistik tetap tampil nol.
        }

        return view('dashboard', compact('stats'));
    }
}