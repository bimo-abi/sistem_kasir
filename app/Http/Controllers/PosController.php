<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PosController extends Controller
{
    /**
     * Tampilkan Halaman Utama Transaksi Kasir (POS).
     */
    public function index()
    {
        $kasir = Auth::user();
        
        // Generate Nomor Transaksi Otomatis: TRX-YYYYMMDD-XXXX
        $dateCode = date('Ymd');
        $transactionNumber = 'TRX-' . $dateCode . '-' . rand(1000, 9999);

        // Ambil produk aktif yang memiliki stok > 0 untuk quick selection
        $quickProducts = Product::active()
            ->where('stock', '>', 0)
            ->latest()
            ->get();

        return view('kasir.pos', compact('kasir', 'transactionNumber', 'quickProducts'));
    }
}
