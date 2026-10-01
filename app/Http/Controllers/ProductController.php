<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Tampilkan daftar produk dengan filter pencarian.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $products = Product::query()
            ->search($search)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('products.index', compact('products', 'search'));
    }

    /**
     * Tampilkan form tambah produk.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Simpan produk baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:products,code'],
            'barcode' => ['nullable', 'string', 'max:50', 'unique:products,barcode'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.required' => 'Kode produk wajib diisi.',
            'code.unique' => 'Kode produk ini sudah digunakan.',
            'barcode.unique' => 'Barcode ini sudah digunakan.',
            'name.required' => 'Nama produk wajib diisi.',
            'price.required' => 'Harga produk wajib diisi.',
            'price.numeric' => 'Harga harus berupa angka.',
            'price.min' => 'Harga tidak boleh kurang dari 0.',
            'stock.required' => 'Stok barang wajib diisi.',
            'stock.min' => 'Stok tidak boleh kurang dari 0.',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Product::create($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Tampilkan form edit produk.
     */
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    /**
     * Update data produk di database.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('products', 'code')->ignore($product->id)],
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('products', 'barcode')->ignore($product->id)],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.required' => 'Kode produk wajib diisi.',
            'code.unique' => 'Kode produk ini sudah digunakan.',
            'barcode.unique' => 'Barcode ini sudah digunakan.',
            'name.required' => 'Nama produk wajib diisi.',
            'price.required' => 'Harga produk wajib diisi.',
            'stock.required' => 'Stok barang wajib diisi.',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Data produk berhasil diperbarui.');
    }

    /**
     * Nonaktifkan produk (Soft Status) agar riwayat transaksi aman.
     */
    public function destroy(Product $product)
    {
        $product->update(['is_active' => false]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Status produk berhasil dinonaktifkan.');
    }

    /**
     * API JSON untuk pencarian produk di Halaman Kasir (POS).
     */
    public function apiSearch(Request $request)
    {
        $query = $request->query('q');

        if (empty($query)) {
            return response()->json([]);
        }

        $products = Product::active()
            ->search($query)
            ->where('stock', '>', 0)
            ->limit(10)
            ->get();

        return response()->json($products);
    }
}
