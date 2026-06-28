<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\MaintenanceReport;
use App\Models\SparePart;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAssets = Asset::count();
        $breakdown = Asset::where('status', 'danger')->count();
        $laporanHariIni = MaintenanceReport::whereDate('created_at', today())->count();
        $stokKritis = SparePart::whereColumn('stok_tersedia', '<', 'stok_minimum')->count();
        $assets = Asset::with('company')->latest()->take(8)->get();

        // DSS sederhana — nanti dikembangkan
        $rekomendasi = [];

        if ($breakdown > 0) {
            $rekomendasi[] = [
                'level' => 'high',
                'title' => '🔴 Ada ' . $breakdown . ' equipment breakdown',
                'desc' => 'Segera lakukan tindakan corrective.'
            ];
        }

        if ($stokKritis > 0) {
            $rekomendasi[] = [
                'level' => 'med',
                'title' => '🟡 ' . $stokKritis . ' spare part stok kritis',
                'desc' => 'Stok di bawah minimum, perlu reorder.'
            ];
        }

        return view('dashboard', compact(
            'totalAssets', 'breakdown', 'laporanHariIni', 'stokKritis', 'assets', 'rekomendasi'
        ));
    }
}