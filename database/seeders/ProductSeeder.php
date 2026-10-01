<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'code' => 'PRD-001',
                'barcode' => '899238811001',
                'name' => 'Indomie Goreng Spesial 85g',
                'price' => 3100,
                'stock' => 120,
                'is_active' => true,
            ],
            [
                'code' => 'PRD-002',
                'barcode' => '899238811002',
                'name' => 'Air Mineral Aqua 600ml',
                'price' => 4000,
                'stock' => 85,
                'is_active' => true,
            ],
            [
                'code' => 'PRD-003',
                'barcode' => '899100110003',
                'name' => 'Teh Botol Sosro 450ml',
                'price' => 6500,
                'stock' => 50,
                'is_active' => true,
            ],
            [
                'code' => 'PRD-004',
                'barcode' => '899277100004',
                'name' => 'Minyak Goreng Bimoli 1L',
                'price' => 18500,
                'stock' => 30,
                'is_active' => true,
            ],
            [
                'code' => 'PRD-005',
                'barcode' => '899288100005',
                'name' => 'Gula Pasir Gulaku 1kg',
                'price' => 16000,
                'stock' => 45,
                'is_active' => true,
            ],
            [
                'code' => 'PRD-006',
                'barcode' => '899999100006',
                'name' => 'Susu Ultra Milk Cokelat 250ml',
                'price' => 7000,
                'stock' => 60,
                'is_active' => true,
            ],
            [
                'code' => 'PRD-007',
                'barcode' => '899888100007',
                'name' => 'Sabun Lifebuoy Red 110g',
                'price' => 4500,
                'stock' => 75,
                'is_active' => true,
            ],
            [
                'code' => 'PRD-008',
                'barcode' => '899777100008',
                'name' => 'Shampoo Pantene 160ml',
                'price' => 24000,
                'stock' => 25,
                'is_active' => true,
            ],
        ];

        foreach ($products as $item) {
            Product::updateOrCreate(['code' => $item['code']], $item);
        }
    }
}
