@extends('layouts.app')

@section('title', 'Dashboard - Sistem Kasir Minimarket')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h3 class="fw-bold mb-1">Selamat Datang, {{ $user->name }}!</h3>
        <p class="text-muted mb-0">Anda terautentikasi sebagai <span class="badge {{ $user->isAdmin() ? 'bg-danger' : 'bg-success' }} fs-6">{{ strtoupper($user->role) }}</span> dalam sistem kasir POS Minimarket Sejahtera.</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="{{ route('kasir.pos') }}" class="btn btn-primary btn-lg rounded-3 px-4 shadow-sm fw-semibold">
            <i class="fa-solid fa-cash-register me-2"></i> Buka Halaman Kasir (POS)
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-custom p-3 border-start border-primary border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Total Produk Aktif</span>
                    <h2 class="fw-bold mb-0 text-dark mt-1">{{ $totalProducts }}</h2>
                    <span class="text-secondary fs-7">Barang siap dijual di POS</span>
                </div>
                <div class="bg-primary-subtle text-primary p-3 rounded-circle fs-3">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-custom p-3 border-start border-warning border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Produk Stok Menipis</span>
                    <h2 class="fw-bold mb-0 text-warning mt-1">{{ $lowStockCount }}</h2>
                    <span class="text-secondary fs-7">Stok barang &le; 5 unit</span>
                </div>
                <div class="bg-warning-subtle text-warning p-3 rounded-circle fs-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-custom p-3 border-start border-success border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Metode Pembayaran</span>
                    <h4 class="fw-bold mb-0 text-success mt-1">Non-Tunai & Cash</h4>
                    <span class="text-secondary fs-7">Tunai, QRIS, Debit, E-Wallet</span>
                </div>
                <div class="bg-success-subtle text-success p-3 rounded-circle fs-3">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-bolt text-warning me-2"></i> Akses Cepat Fitur Sistem</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="{{ route('kasir.pos') }}" class="text-decoration-none">
                            <div class="p-3 border rounded-3 bg-light hover-shadow transition d-flex align-items-center">
                                <div class="bg-primary text-white p-3 rounded-3 me-3 fs-4">
                                    <i class="fa-solid fa-calculator"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Transaksi Pembayaran Kasir</h6>
                                    <p class="text-muted fs-7 mb-0">Halaman utama pencarian barang & hitung total kasir.</p>
                                </div>
                            </div>
                        </a>
                    </div>

                    @if($user->isAdmin())
                    <div class="col-md-6">
                        <a href="{{ route('admin.products.index') }}" class="text-decoration-none">
                            <div class="p-3 border rounded-3 bg-light hover-shadow transition d-flex align-items-center">
                                <div class="bg-secondary text-white p-3 rounded-3 me-3 fs-4">
                                    <i class="fa-solid fa-list-check"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Kelola Data Produk</h6>
                                    <p class="text-muted fs-7 mb-0">Tambah, edit, dan atur stok produk minimarket.</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-danger fs-6"><i class="fa-solid fa-box text-danger me-2"></i> Peringatan Stok Menipis</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($lowStockProducts as $prod)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                            <div>
                                <span class="fw-semibold text-dark fs-7 d-block">{{ $prod->name }}</span>
                                <small class="text-muted font-monospace">{{ $prod->code }}</small>
                            </div>
                            <span class="badge bg-danger rounded-pill">{{ $prod->stock }} pcs</span>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4 fs-7">
                            <i class="fa-solid fa-circle-check text-success fs-3 d-block mb-2"></i>
                            Semua stok barang dalam kondisi aman.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
