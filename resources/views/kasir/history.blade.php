@extends('layouts.app')

@section('title', 'Riwayat Transaksi Penjualan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Riwayat Transaksi Penjualan</h4>
        <p class="text-muted small mb-0">Daftar riwayat seluruh transaksi pembayaran yang telah selesai.</p>
    </div>
    <div>
        <a href="{{ route('kasir.pos') }}" class="btn btn-primary px-3 rounded-3 fw-semibold">
            <i class="fa-solid fa-cash-register me-1"></i> Buka Kasir POS
        </a>
    </div>
</div>

<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form action="{{ route('kasir.history') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <label class="form-label fs-7 fw-semibold mb-1 text-muted">Cari No. Invoice / Transaksi</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="TRX-20261002...">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold mb-1 text-muted">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold mb-1 text-muted">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1 mt-md-4">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                @if($search || $startDate || $endDate)
                    <a href="{{ route('kasir.history') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filter"><i class="fa-solid fa-rotate-left"></i></a>
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
                        <th class="ps-4">No. Invoice</th>
                        <th>Waktu Transaksi</th>
                        <th>Kasir</th>
                        <th>Metode Bayar</th>
                        <th class="text-center">Jumlah Item</th>
                        <th class="text-end">Total Akhir</th>
                        <th class="text-center pe-4">Aksi / Struk</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-primary-subtle text-primary font-monospace fs-7">{{ $trx->invoice_number }}</span>
                            </td>
                            <td class="text-muted small">
                                <i class="fa-regular fa-calendar me-1"></i> {{ $trx->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="fw-semibold text-dark">{{ $trx->user->name }}</td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1"><i class="fa-solid fa-wallet me-1 text-secondary"></i> {{ $trx->payment_method }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-2 py-1">{{ $trx->details->sum('qty') }} pcs</span>
                            </td>
                            <td class="text-end fw-bold text-primary font-monospace">{{ $trx->formatted_grand_total }}</td>
                            <td class="text-center pe-4">
                                <a href="{{ route('kasir.receipt', $trx->id) }}" class="btn btn-outline-primary btn-sm px-3" target="_blank" title="Cetak Ulang Struk">
                                    <i class="fa-solid fa-print me-1"></i> Cetak Struk
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-receipt fs-1 text-secondary mb-3 d-block"></i>
                                <span>Belum ada riwayat transaksi penjualan.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($transactions->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection
