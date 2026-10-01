<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard utama berdasarkan role pengguna.
     */
    public function index()
    {
        $user = Auth::user();

        // Ringkasan data nyata
        $totalProducts = Product::where('is_active', true)->count();
        $lowStockProducts = Product::where('is_active', true)->where('stock', '<=', 5)->get();
        $lowStockCount = $lowStockProducts->count();

        return view('dashboard', compact('user', 'totalProducts', 'lowStockCount', 'lowStockProducts'));
    }
}
