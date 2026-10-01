@extends('layouts.app')

@section('title', 'Kasir (POS) - Halaman Transaksi Pembayaran')

@push('styles')
<style>
    .pos-header-card {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: #ffffff;
        border-radius: 12px;
    }
    .pos-card-left, .pos-card-right {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .cart-table th {
        background-color: #f8fafc;
        font-size: 0.8rem;
        text-transform: uppercase;
    }
    .grand-total-box {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        border-radius: 10px;
        padding: 16px;
    }
    .quick-cash-btn {
        font-weight: 600;
        font-size: 0.85rem;
    }
    .search-results-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1050;
        max-height: 280px;
        overflow-y: auto;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

@section('content')
<!-- Header Toko & Identitas Transaksi -->
<div class="pos-header-card p-3 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h4 class="fw-bold mb-0 text-white"><i class="fa-solid fa-store text-primary me-2"></i> MINIMARKET SEJAHTERA</h4>
            <small class="text-white-50"><i class="fa-solid fa-location-dot me-1"></i> Jl. Pemuda No. 45, Jakarta Pusat | Telp: (021) 555-0192</small>
        </div>
        <div class="col-md-6 text-md-end mt-2 mt-md-0">
            <div class="d-inline-block text-start me-4">
                <span class="d-block fs-7 text-white-50">No. Transaksi</span>
                <strong class="font-monospace fs-6 text-warning" id="trans-number-disp">{{ $transactionNumber }}</strong>
            </div>
            <div class="d-inline-block text-start me-4">
                <span class="d-block fs-7 text-white-50">Kasir Aktif</span>
                <strong class="fs-6 text-white">{{ $kasir->name }}</strong>
            </div>
            <div class="d-inline-block text-start">
                <span class="d-block fs-7 text-white-50">Tanggal & Waktu</span>
                <strong class="fs-6 text-info" id="realtime-clock">{{ date('d/m/Y H:i:s') }}</strong>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- AREA KIRI: Pencarian & Keranjang Belanja -->
    <div class="col-lg-7">
        <div class="pos-card-left p-3 h-100 d-flex flex-column">
            <!-- Form Pencarian / Scan Barcode -->
            <div class="position-relative mb-3">
                <label for="product-search-input" class="form-label fw-bold text-dark fs-7 mb-1">
                    <i class="fa-solid fa-barcode text-primary me-1"></i> Cari Barang / Scan Barcode (Tekan F2)
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-primary text-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="product-search-input" class="form-control form-control-lg fs-6" placeholder="Ketik nama barang, kode (PRD-001), atau scan barcode..." autocomplete="off" autofocus>
                </div>

                <!-- Dropdown Result Search -->
                <div id="search-results" class="list-group search-results-dropdown d-none"></div>
            </div>

            <!-- Quick Selection Carousel/Grid Produk -->
            <div class="mb-3">
                <span class="text-muted fs-7 fw-semibold d-block mb-1">Pilihan Cepat Produk (Klik untuk tambah):</span>
                <div class="d-flex gap-2 overflow-x-auto pb-2">
                    @foreach($quickProducts as $qp)
                        <button type="button" class="btn btn-outline-secondary btn-sm text-nowrap text-start bg-light" onclick="addToCart({{ json_encode($qp) }})">
                            <strong class="d-block text-dark fs-7">{{ $qp->name }}</strong>
                            <span class="text-primary font-monospace fs-7">{{ $qp->formatted_price }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Tabel Keranjang Belanja -->
            <div class="table-responsive flex-grow-1 border rounded-3 mb-2" style="min-height: 250px; max-height: 380px;">
                <table class="table table-hover align-middle mb-0 cart-table">
                    <thead class="sticky-top">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 35%;">Nama & Kode Barang</th>
                            <th style="width: 20%;" class="text-end">Harga</th>
                            <th style="width: 20%;" class="text-center">Qty</th>
                            <th style="width: 15%;" class="text-end">Subtotal</th>
                            <th style="width: 5%;" class="text-center">Hapus</th>
                        </tr>
                    </thead>
                    <tbody id="cart-table-body">
                        <!-- Render Javascript -->
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-2">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                    <i class="fa-solid fa-trash me-1"></i> Kosongkan Keranjang
                </button>
                <div class="fs-6 fw-bold text-dark">
                    Total Item: <span id="disp-total-items-qty" class="text-primary">0</span> pcs (<span id="disp-total-items-type" class="text-secondary">0</span> jenis)
                </div>
            </div>
        </div>
    </div>

    <!-- AREA KANAN: Ringkasan Pembayaran & Checkout -->
    <div class="col-lg-5">
        <div class="pos-card-right p-3 h-100 d-flex flex-column justify-content-between">
            <div>
                <!-- Box Display Total Akhir -->
                <div class="grand-total-box mb-3 text-center">
                    <span class="text-white-50 fs-7 text-uppercase fw-semibold d-block">TOTAL AKHIR (GRAND TOTAL)</span>
                    <h1 class="fw-bold mb-0 display-5 font-monospace" id="disp-grand-total">Rp 0</h1>
                </div>

                <!-- Form Ringkasan Perhitungan -->
                <div class="card bg-light border-0 p-3 mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal Barang:</span>
                        <strong class="text-dark" id="disp-subtotal">Rp 0</strong>
                    </div>

                    <!-- Diskon Persentase & Nominal -->
                    <div class="row g-2 mb-2 align-items-center">
                        <div class="col-6">
                            <label class="form-label text-muted fs-7 mb-0">Diskon (%)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="input-discount-percent" class="form-control" value="0" min="0" max="100" oninput="calculateTotals()">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted fs-7 mb-0">Diskon Nominal (Rp)</label>
                            <input type="number" id="input-discount-nominal" class="form-control form-control-sm" value="0" min="0" oninput="calculateTotals()">
                        </div>
                    </div>

                    <!-- Pajak PPN & Biaya Lain -->
                    <div class="row g-2 mb-2 align-items-center">
                        <div class="col-6">
                            <label class="form-label text-muted fs-7 mb-0">Pajak / PPN (%)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="input-tax-percent" class="form-control" value="0" min="0" max="100" oninput="calculateTotals()">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted fs-7 mb-0">Biaya Lain (Rp)</label>
                            <input type="number" id="input-other-cost" class="form-control form-control-sm" value="0" min="0" oninput="calculateTotals()">
                        </div>
                    </div>
                </div>

                <!-- Input Pembayaran & Metode -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark fs-7">Metode Pembayaran</label>
                    <select id="payment-method-select" class="form-select fw-semibold text-primary">
                        <option value="Tunai" selected>Tunai (Cash)</option>
                        <option value="QRIS">QRIS / Non-Tunai</option>
                        <option value="Debit">Kartu Debit</option>
                        <option value="Kredit">Kartu Kredit</option>
                        <option value="E-Wallet">E-Wallet (Gopay/OVO/Dana)</option>
                        <option value="Transfer">Transfer Bank</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="input-paid-amount" class="form-label fw-bold text-dark fs-7">
                        Uang Dibayar (Rp) - <span class="text-primary">Tekan F4</span>
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-success text-white fw-bold">Rp</span>
                        <input type="number" id="input-paid-amount" class="form-control fw-bold font-monospace fs-4 text-success" placeholder="0" min="0" oninput="calculateTotals()">
                    </div>
                    <!-- Quick Cash Buttons -->
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <button type="button" class="btn btn-outline-primary btn-sm quick-cash-btn flex-fill" onclick="setExactCash()">Uang Pas</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm quick-cash-btn flex-fill" onclick="setCash(10000)">10.000</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm quick-cash-btn flex-fill" onclick="setCash(20000)">20.000</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm quick-cash-btn flex-fill" onclick="setCash(50000)">50.000</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm quick-cash-btn flex-fill" onclick="setCash(100000)">100.000</button>
                    </div>
                </div>

                <!-- Kembalian / Peringatan Kurang -->
                <div class="alert p-3 mb-3 rounded-3" id="change-alert-box" style="background-color: #f8fafc; border: 1px solid #cbd5e1;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-secondary" id="change-label">Kembalian:</span>
                        <strong class="fs-4 font-monospace text-dark" id="disp-change-amount">Rp 0</strong>
                    </div>
                    <small id="payment-warning-msg" class="text-danger d-none fw-semibold"><i class="fa-solid fa-circle-exclamation me-1"></i> Uang dibayar masih kurang!</small>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row g-2 mt-2">
                <div class="col-6">
                    <button type="button" class="btn btn-warning w-100 py-3 fw-bold rounded-3" onclick="holdTransaction()">
                        <i class="fa-solid fa-pause me-1"></i> Tahan Transaksi
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" id="btn-submit-pay" class="btn btn-success w-100 py-3 fw-bold rounded-3 fs-6" onclick="submitCheckout()">
                        <i class="fa-solid fa-print me-1"></i> Bayar & Cetak
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // State Keranjang Belanja POS
    let cart = [];
    let grandTotal = 0;
    let subtotal = 0;

    // Realtime Clock
    setInterval(() => {
        const now = new Date();
        document.getElementById('realtime-clock').innerText = now.toLocaleDateString('id-ID') + ' ' + now.toLocaleTimeString('id-ID');
    }, 1000);

    // Live Search Produk via Fetch API
    const searchInput = document.getElementById('product-search-input');
    const searchResults = document.getElementById('search-results');

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 1) {
            searchResults.classList.add('d-none');
            return;
        }

        fetch(`{{ route('kasir.products.search') }}?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(products => {
                searchResults.innerHTML = '';
                if (products.length === 0) {
                    searchResults.innerHTML = '<div class="list-group-item text-muted text-center py-3">Produk tidak ditemukan atau stok habis.</div>';
                } else {
                    products.forEach(p => {
                        const itemHtml = `
                            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" onclick="selectProductFromSearch(event, ${JSON.stringify(p).replace(/"/g, '&quot;')})">
                                <div>
                                    <strong class="d-block text-dark">${p.name}</strong>
                                    <small class="text-muted font-monospace">${p.code} ${p.barcode ? '| ' + p.barcode : ''}</small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold text-primary">Rp ${parseInt(p.price).toLocaleString('id-ID')}</span>
                                    <span class="badge bg-secondary ms-2">Stok: ${p.stock}</span>
                                </div>
                            </a>
                        `;
                        searchResults.insertAdjacentHTML('beforeend', itemHtml);
                    });
                }
                searchResults.classList.remove('d-none');
            })
            .catch(err => console.error(err));
    });

    function selectProductFromSearch(event, product) {
        event.preventDefault();
        addToCart(product);
        searchInput.value = '';
        searchResults.classList.add('d-none');
        searchInput.focus();
    }

    // Fungsi Tambah Barang ke Keranjang
    function addToCart(product) {
        const existingItem = cart.find(item => item.id === product.id);

        if (existingItem) {
            if (existingItem.qty + 1 > product.stock) {
                alert(`Stok produk "${product.name}" tidak mencukupi (Tersisa: ${product.stock}).`);
                return;
            }
            existingItem.qty += 1;
            existingItem.subtotal = existingItem.qty * existingItem.price;
        } else {
            if (product.stock < 1) {
                alert(`Stok produk "${product.name}" telah habis.`);
                return;
            }
            cart.push({
                id: product.id,
                code: product.code,
                name: product.name,
                price: parseFloat(product.price),
                stock: product.stock,
                qty: 1,
                subtotal: parseFloat(product.price)
            });
        }

        renderCartTable();
    }

    // Render Ulang Tabel Keranjang
    function renderCartTable() {
        const tbody = document.getElementById('cart-table-body');
        tbody.innerHTML = '';

        if (cart.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-cart-arrow-down fs-1 text-secondary mb-2 d-block"></i>
                        <span>Keranjang belanja masih kosong.<br>Ketik nama/scan barcode barang di atas untuk menambahkan.</span>
                    </td>
                </tr>
            `;
        } else {
            cart.forEach((item, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-muted fw-semibold">${index + 1}</td>
                    <td>
                        <strong class="d-block text-dark">${item.name}</strong>
                        <small class="text-muted font-monospace">${item.code}</small>
                    </td>
                    <td class="text-end font-monospace">Rp ${item.price.toLocaleString('id-ID')}</td>
                    <td class="text-center">
                        <div class="input-group input-group-sm justify-content-center" style="max-width: 120px; margin: 0 auto;">
                            <button type="button" class="btn btn-outline-secondary" onclick="updateQty(${item.id}, ${item.qty - 1})">-</button>
                            <input type="number" class="form-control text-center px-1 font-monospace fw-bold" value="${item.qty}" min="1" max="${item.stock}" onchange="updateQty(${item.id}, parseInt(this.value))">
                            <button type="button" class="btn btn-outline-secondary" onclick="updateQty(${item.id}, ${item.qty + 1})">+</button>
                        </div>
                    </td>
                    <td class="text-end fw-bold text-primary font-monospace">Rp ${item.subtotal.toLocaleString('id-ID')}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-link text-danger p-0" onclick="removeFromCart(${item.id})" title="Hapus Item">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        calculateTotals();
    }

    function updateQty(id, newQty) {
        const item = cart.find(i => i.id === id);
        if (!item) return;

        if (isNaN(newQty) || newQty < 1) {
            newQty = 1;
        }
        if (newQty > item.stock) {
            alert(`Jumlah melebihi stok barang yang tersedia (Maksimal: ${item.stock}).`);
            newQty = item.stock;
        }

        item.qty = newQty;
        item.subtotal = item.qty * item.price;
        renderCartTable();
    }

    function removeFromCart(id) {
        cart = cart.filter(i => i.id !== id);
        renderCartTable();
    }

    function clearCart() {
        if (cart.length > 0 && confirm('Kosongkan semua barang dari keranjang belanja?')) {
            cart = [];
            renderCartTable();
        }
    }

    // Kalkulasi Total Sesuai Rumus PDF
    function calculateTotals() {
        const totalItemsQty = cart.reduce((sum, item) => sum + item.qty, 0);
        const totalItemsType = cart.length;
        document.getElementById('disp-total-items-qty').innerText = totalItemsQty;
        document.getElementById('disp-total-items-type').innerText = totalItemsType;

        subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
        document.getElementById('disp-subtotal').innerText = 'Rp ' + subtotal.toLocaleString('id-ID');

        // Input Diskon & Biaya
        const discPercent = parseFloat(document.getElementById('input-discount-percent').value) || 0;
        const discNominal = parseFloat(document.getElementById('input-discount-nominal').value) || 0;
        const taxPercent = parseFloat(document.getElementById('input-tax-percent').value) || 0;
        const otherCost = parseFloat(document.getElementById('input-other-cost').value) || 0;

        // Rumus PDF:
        // total_diskon = (subtotal * discPercent / 100) + discNominal
        const totalDiscount = (subtotal * (discPercent / 100)) + discNominal;
        const totalTax = (subtotal - totalDiscount) * (taxPercent / 100);
        
        // Total Akhir = Max(0, Subtotal - Total Diskon + Pajak + Biaya Lain)
        grandTotal = Math.max(0, subtotal - totalDiscount + totalTax + otherCost);
        document.getElementById('disp-grand-total').innerText = 'Rp ' + Math.round(grandTotal).toLocaleString('id-ID');

        // Kembalian
        const paidAmount = parseFloat(document.getElementById('input-paid-amount').value) || 0;
        const change = paidAmount - grandTotal;
        const changeDisp = document.getElementById('disp-change-amount');
        const warningMsg = document.getElementById('payment-warning-msg');
        const submitBtn = document.getElementById('btn-submit-pay');

        if (paidAmount < grandTotal && cart.length > 0) {
            changeDisp.innerText = 'Rp 0';
            warningMsg.classList.remove('d-none');
            warningMsg.innerText = `Uang dibayar kurang Rp ${Math.abs(change).toLocaleString('id-ID')}`;
            submitBtn.disabled = true;
        } else {
            changeDisp.innerText = 'Rp ' + Math.max(0, change).toLocaleString('id-ID');
            warningMsg.classList.add('d-none');
            submitBtn.disabled = cart.length === 0;
        }
    }

    // Tombol Nominal Cepat
    function setExactCash() {
        document.getElementById('input-paid-amount').value = Math.round(grandTotal);
        calculateTotals();
    }

    function setCash(amount) {
        document.getElementById('input-paid-amount').value = amount;
        calculateTotals();
    }

    // Shortcut Keyboard F2, F4, Esc
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F2') {
            e.preventDefault();
            searchInput.focus();
        } else if (e.key === 'F4') {
            e.preventDefault();
            document.getElementById('input-paid-amount').focus();
        } else if (e.key === 'Escape') {
            if (cart.length > 0 && confirm('Batalkan transaksi saat ini dan kosongkan keranjang?')) {
                cart = [];
                renderCartTable();
            }
        }
    });

    // Cek apakah ada data Transaksi Ditahan yang dilanjutkan dari Halaman Held
    window.addEventListener('DOMContentLoaded', () => {
        const heldDataRaw = sessionStorage.getItem('resume_held_data');
        if (heldDataRaw) {
            try {
                const heldData = JSON.parse(heldDataRaw);
                sessionStorage.removeItem('resume_held_data');

                cart = heldData.details.map(d => ({
                    id: d.product_id || d.id,
                    code: d.product_code,
                    name: d.product_name,
                    price: parseFloat(d.price),
                    stock: d.product ? d.product.stock : 999,
                    qty: parseInt(d.qty),
                    subtotal: parseFloat(d.subtotal)
                }));

                document.getElementById('input-discount-percent').value = heldData.discount_percent || 0;
                document.getElementById('input-discount-nominal').value = heldData.discount_nominal || 0;
                document.getElementById('input-tax-percent').value = heldData.tax_percent || 0;
                document.getElementById('input-other-cost').value = heldData.other_cost || 0;
                document.getElementById('payment-method-select').value = heldData.payment_method || 'Tunai';

                renderCartTable();
                alert(`Melanjutkan transaksi ditahan (${heldData.invoice_number}).`);
            } catch (err) {
                console.error(err);
            }
        }
    });

    function holdTransaction() {
        if (cart.length === 0) {
            alert('Keranjang belanja masih kosong!');
            return;
        }

        if (!confirm('Tahan transaksi ini sementara waktu?')) return;

        const payload = {
            _token: '{{ csrf_token() }}',
            items: cart,
            discount_percent: document.getElementById('input-discount-percent').value,
            discount_nominal: document.getElementById('input-discount-nominal').value,
            tax_percent: document.getElementById('input-tax-percent').value,
            other_cost: document.getElementById('input-other-cost').value,
            payment_method: document.getElementById('payment-method-select').value
        };

        fetch('{{ route("kasir.checkout.hold") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(`Transaksi berhasil ditahan (${data.invoice_number}).`);
                cart = [];
                renderCartTable();
            } else {
                alert('Gagal menahan transaksi: ' + data.message);
            }
        })
        .catch(err => alert('Terjadi kesalahan server.'));
    }

    function submitCheckout() {
        if (cart.length === 0) {
            alert('Keranjang belanja masih kosong!');
            return;
        }

        const paidAmount = parseFloat(document.getElementById('input-paid-amount').value) || 0;
        if (paidAmount < grandTotal) {
            alert('Uang dibayar belum mencukupi Total Akhir!');
            return;
        }

        const submitBtn = document.getElementById('btn-submit-pay');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses...';

        const payload = {
            _token: '{{ csrf_token() }}',
            items: cart,
            discount_percent: document.getElementById('input-discount-percent').value,
            discount_nominal: document.getElementById('input-discount-nominal').value,
            tax_percent: document.getElementById('input-tax-percent').value,
            other_cost: document.getElementById('input-other-cost').value,
            paid_amount: paidAmount,
            payment_method: document.getElementById('payment-method-select').value
        };

        fetch('{{ route("kasir.checkout.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Transaksi Berhasil Dikeluarkan!');
                window.location.href = data.redirect_url;
            } else {
                alert('Gagal Memproses Pembayaran: ' + data.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-print me-1"></i> Bayar & Cetak';
            }
        })
        .catch(err => {
            alert('Terjadi kesalahan koneksi server.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-print me-1"></i> Bayar & Cetak';
        });
    }
</script>
@endpush
