@extends('layouts.app')

@section('title', 'Kelola Produk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-boxes-stacked text-primary me-2"></i> Kelola Data Produk</h4>
        <p class="text-muted small mb-0">Manajemen stok, harga, kode barang, dan barcode minimarket.</p>
    </div>
    <div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary px-3 rounded-3 fw-semibold">
            <i class="fa-solid fa-plus me-1"></i> Tambah Produk Baru
        </a>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form action="{{ route('admin.products.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control bg-light" value="{{ $search }}" placeholder="Cari berdasarkan nama barang, kode produk, atau barcode...">
                </div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Cari</button>
                @if($search)
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary" title="Reset Pencarian"><i class="fa-solid fa-rotate-left"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Kode Produk</th>
                        <th>Barcode</th>
                        <th>Nama Barang</th>
                        <th class="text-end">Harga (Rp)</th>
                        <th class="text-center">Stok</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $product)
                        <tr>
                            <td class="ps-4 text-muted fw-semibold">{{ $products->firstItem() + $index }}</td>
                            <td><span class="badge bg-secondary font-monospace">{{ $product->code }}</span></td>
                            <td><span class="font-monospace text-muted">{{ $product->barcode ?: '-' }}</span></td>
                            <td class="fw-semibold text-dark">{{ $product->name }}</td>
                            <td class="text-end fw-bold text-primary">{{ $product->formatted_price }}</td>
                            <td class="text-center">
                                @if($product->stock <= 5)
                                    <span class="badge bg-danger px-2 py-1">{{ $product->stock }} (Stok Menipis)</span>
                                @else
                                    <span class="badge bg-light text-dark border px-2 py-1">{{ $product->stock }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($product->is_active)
                                    <span class="badge bg-success-subtle text-success px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i> Aktif</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger px-2 py-1"><i class="fa-solid fa-circle-minus me-1"></i> Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-outline-primary" title="Edit Produk">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    @if($product->is_active)
                                        <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Nonaktifkan produk ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Nonaktifkan Produk">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-box-open fs-1 mb-3 d-block text-secondary"></i>
                                <span>Tidak ada data produk yang ditemukan.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($products->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
