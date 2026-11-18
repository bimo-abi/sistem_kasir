<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Alignment;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

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
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_crop' => ['nullable', 'boolean'],
            'image_rotate' => ['nullable', 'integer', 'in:0,90,180,270'],
            'image_watermark' => ['nullable', 'boolean'],
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
        unset($validated['image'], $validated['image_crop'], $validated['image_rotate'], $validated['image_watermark']);
        if ($request->hasFile('image')) {
            $validated['image_path'] = $this->processProductImage($request);
        }

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
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_crop' => ['nullable', 'boolean'],
            'image_rotate' => ['nullable', 'integer', 'in:0,90,180,270'],
            'image_watermark' => ['nullable', 'boolean'],
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
        unset($validated['image'], $validated['image_crop'], $validated['image_rotate'], $validated['image_watermark']);
        if ($request->hasFile('image')) {
            $oldImage = $product->image_path;
            $validated['image_path'] = $this->processProductImage($request);
            if ($oldImage) {
                Storage::disk('public')->delete($oldImage);
            }
        }

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

    /**
     * Simpan gambar produk sebagai WebP dengan Intervention Image v4.
     * Mendukung resize, crop opsional, rotasi, dan watermark opsional.
     */
    private function processProductImage(Request $request): string
    {
        $upload = $request->file('image');
        $manager = ImageManager::usingDriver(Driver::class);
        $image = $manager->decodePath($upload->getRealPath());

        // Resize agar gambar tidak melebihi 1200 x 1200 px.
        $image->scaleDown(width: 1200, height: 1200);

        // Crop opsional menjadi thumbnail persegi.
        if ($request->boolean('image_crop')) {
            $image->cover(800, 800);
        }

        // Rotasi opsional.
        $rotation = (int) $request->input('image_rotate', 0);
        if ($rotation !== 0) {
            $image->rotate($rotation);
        }

        // Watermark PNG transparan yang disertakan di public/images.
        $watermarkPath = public_path('images/watermark.png');
        if ($request->boolean('image_watermark') && is_file($watermarkPath)) {
            $watermark = $manager->decodePath($watermarkPath);
            $watermark->scaleDown(width: 260);
            $image->insert($watermark, 12, 12, Alignment::BOTTOM_RIGHT, 0.72);
        }

        Storage::disk('public')->makeDirectory('products');
        $relativePath = 'products/'.Str::uuid().'.webp';
        $absolutePath = Storage::disk('public')->path($relativePath);
        $image->encodeUsingFormat(Format::WEBP, quality: 85)->save($absolutePath);

        return $relativePath;
    }
}
