@extends('layouts.app')

@section('title', 'Daftar Transaksi Ditahan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-pause text-warning me-2"></i> Transaksi Ditahan (Pending Hold)</h4>
        <p class="text-muted small mb-0">Daftar keranjang pelanggan yang ditahan sementara oleh kasir.</p>
    </div>
    <div>
        <a href="{{ route('kasir.pos') }}" class="btn btn-primary px-3 rounded-3 fw-semibold">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Kasir POS
        </a>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">No. Hold</th>
                        <th>Waktu Ditahan</th>
                        <th>Kasir</th>
                        <th class="text-center">Jumlah Item</th>
                        <th class="text-end">Total Sementara</th>
                        <th class="text-center pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($heldTransactions as $trx)
                        <tr>
                            <td class="ps-4"><span class="badge bg-warning text-dark font-monospace fs-7">{{ $trx->invoice_number }}</span></td>
                            <td class="text-muted small"><i class="fa-regular fa-clock me-1"></i> {{ $trx->created_at->format('d/m/Y H:i') }}</td>
                            <td class="fw-semibold text-dark">{{ $trx->user->name }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-2 py-1">{{ $trx->details->sum('qty') }} pcs ({{ $trx->details->count() }} barang)</span>
                            </td>
                            <td class="text-end fw-bold text-primary">{{ $trx->formatted_grand_total }}</td>
                            <td class="text-center pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-success px-3" onclick="resumeHeldTransaction({{ $trx->id }})" title="Lanjutkan Pembayaran di POS">
                                        <i class="fa-solid fa-play me-1"></i> Lanjutkan
                                    </button>
                                    <form action="{{ route('kasir.held.cancel', $trx->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Batalkan transaksi ditahan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Batalkan Transaksi">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-circle-check fs-1 text-success mb-3 d-block"></i>
                                <span>Tidak ada transaksi yang sedang ditahan.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function resumeHeldTransaction(id) {
        fetch(`{{ url('/kasir/held/resume') }}/${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    sessionStorage.setItem('resume_held_data', JSON.stringify(data.transaction));
                    window.location.href = "{{ route('kasir.pos') }}";
                }
            })
            .catch(err => alert('Gagal memuat transaksi ditahan.'));
    }
</script>
@endpush
