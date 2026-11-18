# Panduan Praktikum & Implementasi FilamentPHP (BKPM Minggu 13)
**Mata Kuliah:** Workshop Pengembangan Website  
**Materi:** Acara 33 (Pengenalan & Instalasi Dasar FilamentPHP) & Acara 34 (Kustomisasi Lanjutan Filament: Relasi Data, Filter Tabel, dan Dashboard Widgets)  
**Target Proyek:** `sistem-kasir` (Laravel Project)

---

## 1. Analisis Kondisi Proyek Saat Ini (`sistem-kasir`)

Berdasarkan hasil inspeksi pada struktur arsip proyek Laravel `sistem-kasir` yang Anda kirimkan:
1. **Arsitektur & Konvensi:** Proyek sudah dilengkapi dengan *best practices* Laravel modern dan konvensi kode yang rapi (terlihat dari konfigurasi lingkungan kerja serta struktur direktori standar Laravel).
2. **Kesiapan Integrasi:** Untuk menerapkan **Acara 33** dan **Acara 34**, kita akan mengintegrasikan **FilamentPHP v3** berbasis **TALL Stack** (*Tailwind CSS, Alpine.js, Laravel, Livewire*) ke dalam proyek `sistem-kasir` untuk mengelola entitas master **Category** dan **Product**.
3. **Skema Basis Data yang Dibutuhkan:**
   - Tabel `categories`: `id`, `name`, `slug`, `timestamps`
   - Tabel `products`: `id`, `category_id` (*Foreign Key*), `name`, `price`, `stock`, `image`, `timestamps`

---

## 2. Implementasi Acara 33: Pengenalan & Instalasi Dasar FilamentPHP

### Langkah 1: Instalasi Package FilamentPHP v3
Buka terminal pada root direktori proyek `sistem-kasir`, lalu jalankan perintah Composer berikut:

```bash
composer require filament/filament:"^3.0" -W
```

Setelah proses unduh selesai, lakukan publikasi konfigurasi dan pembuatan *Panel Provider* default (`/admin`):

```bash
php artisan filament:install --panels
```
*(Tekan **Enter** jika diminta memasukkan ID panel, secara default akan menggunakan `admin`).*

**File yang otomatis terbentuk / terkonfigurasi:**
- `config/filament.php`
- `app/Providers/Filament/AdminPanelProvider.php`

---

### Langkah 2: Membuat Akun Administrator & Symlink Storage
Buat akun user untuk mengakses halaman panel admin:

```bash
php artisan make:filament-user
```
Masukkan **Name**, **Email address**, dan **Password** sesuai kebutuhan Anda.

*(Opsional namun wajib untuk upload gambar produk)* Jalankan perintah berikut agar gambar yang diunggah melalui Filament dapat tampil di browser:

```bash
php artisan storage:link
```

---

### Langkah 3: Persiapan Migration & Model Eloquent (`Category` & `Product`)
Pastikan tabel dan model untuk entitas master sudah tersedia di proyek `sistem-kasir`. Jika belum ada, buat melalui Artisan:

```bash
php artisan make:model Category -m
php artisan make:model Product -m
```

#### A. Konfigurasi File Migration
Sesuaikan skema kolom pada file migration di folder `database/migrations/`:

**1. Migration `create_categories_table.php`:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

**2. Migration `create_products_table.php`:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->integer('stock')->default(0);
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

Jalankan migrasi ke database:
```bash
php artisan migrate
```

#### B. Konfigurasi Model & Relasi Eloquent (Acara 33 & Acara 34 Langkah 1)
Sesuaikan properti `$fillable` dan definisikan relasi `hasMany` serta `belongsTo` pada masing-masing model:

**1. File: `app/Models/Category.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Relasi One-to-Many: Satu kategori memiliki banyak produk.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
```

**2. File: `app/Models/Product.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'price',
        'stock',
        'image',
    ];

    /**
     * Relasi Belongs-To: Setiap produk dimiliki oleh satu kategori.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
```

---

### Langkah 4: Generate Filament Resource Otomatis
Jalankan perintah Artisan berikut untuk men-generate modul CRUD otomatis bagi model `Category` dan `Product`:

```bash
php artisan make:filament-resource Category
php artisan make:filament-resource Product
```

Struktur folder yang terbentuk di `app/Filament/Resources/`:
```text
app/Filament/Resources/
├── CategoryResource.php
├── CategoryResource/Pages/
│   ├── CreateCategory.php
│   ├── EditCategory.php
│   └── ListCategories.php
├── ProductResource.php
└── ProductResource/Pages/
    ├── CreateProduct.php
    ├── EditProduct.php
    └── ListProducts.php
```

---

## 3. Implementasi Acara 34: Relasi Data, Filter Tabel, Badge, dan Dashboard Widgets

Pada tahap ini, kita menggabungkan konfigurasi dasar dari **Acara 33** dengan kustomisasi lanjutan pada **Acara 34** (relasi dropdown kategori, badge warna stok dinamis, filter kategori, pengelompokan navigasi sidebar, dan widget statistik dashboard).

### Langkah 1: Konfigurasi Lengkap `CategoryResource.php`
Buka file `app/Filament/Resources/CategoryResource.php` dan terapkan kode berikut (termasuk auto-generate `slug` dari `name` serta pengelompokan navigasi):

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    // Kustomisasi Navigasi Sidebar (Acara 34)
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Manajemen Master';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug URL')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Jumlah Produk')
                    ->counts('products')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
```

---

### Langkah 2: Konfigurasi Lengkap `ProductResource.php`
Buka file `app/Filament/Resources/ProductResource.php` dan terapkan skema Form (termasuk relasi `Select::relationship()`), Table (dot notation `category.name`, dynamic stock badge, `SelectFilter`), dan kustomisasi navigasi:

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    // Kustomisasi Navigasi Sidebar (Acara 34)
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Manajemen Master';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->label('Kategori Produk')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->label('Nama Produk')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('price')
                    ->label('Harga')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                Forms\Components\TextInput::make('stock')
                    ->label('Stok')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Forms\Components\FileUpload::make('image')
                    ->label('Gambar Produk')
                    ->image()
                    ->directory('products')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar')
                    ->circular(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->color(fn (int $state): string => $state <= 5 ? 'danger' : 'success')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Filter Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
```

---

### Langkah 3: Membuat Stats Overview Widget untuk Dashboard
Jalankan perintah Artisan berikut untuk membuat widget statistik pada panel `admin`:

```bash
php artisan make:filament-widget StatsOverview --stats-overview
```
*(Saat muncul pilihan di terminal, pilih `admin` agar widget otomatis terdaftar di dashboard utama).*

Buka file `app/Filament/Widgets/StatsOverview.php` dan perbarui isinya menjadi:

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Kategori', Category::count())
                ->description('Kategori produk terdaftar')
                ->descriptionIcon('heroicon-m-tag')
                ->color('info'),

            Stat::make('Total Produk', Product::count())
                ->description('Keseluruhan item aktif')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),

            Stat::make('Stok Menipis (<= 5)', Product::where('stock', '<=', 5)->count())
                ->description('Perlu segera dilakukan restock')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
```

---

## 4. Pengujian & Checklist Hasil Praktikum

Jalankan server pengembangan Laravel:
```bash
php artisan serve
```

Buka browser dan akses `http://127.0.0.1:8000/admin`, lalu lakukan verifikasi checklist berikut untuk bahan laporan praktikum (Acara 33 & 34):

- [ ] **Autentikasi Panel Admin:** Berhasil login menggunakan akun yang dibuat via `php artisan make:filament-user`.
- [ ] **Navigasi Sidebar:** Menu **Categories** dan **Products** terkelompok rapi di bawah grup **Manajemen Master** dengan ikon masing-masing.
- [ ] **CRUD Kategori:** Berhasil menambah kategori baru (slug terisi otomatis), mengubah, dan menghapus kategori.
- [ ] **CRUD Produk & Relasi:** Berhasil memilih kategori melalui dropdown relasi (`Select::relationship`), mengisi harga berawalan `Rp`, mengisi stok, dan mengunggah gambar produk.
- [ ] **Table Badge & Filter:**
  - Kolom **Stok** menampilkan badge berwarna **merah (`danger`)** jika stok $\le 5$ dan **hijau (`success`)** jika stok $> 5$.
  - Fitur **Filter Kategori** (`SelectFilter`) berfungsi menyaring daftar produk sesuai kategori yang dipilih.
- [ ] **Dashboard Widgets:** Halaman utama `/admin` menampilkan 3 kartu metrik statistik secara *real-time* (**Total Kategori**, **Total Produk**, dan **Stok Menipis**).

---

## 5. Catatan Format Pengumpulan Laporan (Sesuai BKPM)
- **Format File:** `*.pdf` (Kertas A4)
- **Struktur Laporan:** Cover, Pendahuluan, Hasil Praktik (sertakan *screenshot* hasil pengujian checklist di atas), Kesimpulan, dan Daftar Pustaka.
- **Identitas:** NIM, Nama, Golongan, Tugas Minggu Ke-X.
- **Format Penamaan File:** `ACARA-33_GOL_NIM_NAMA.pdf` & `ACARA-34_GOL_NIM_NAMA.pdf`
- **Platform Pengumpulan:** `https://elearning-jti.polije.ac.id/`