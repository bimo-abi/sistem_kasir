<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Kategori Produk')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('code')
                    ->label('Kode Produk')
                    ->required(),
                TextInput::make('barcode')
                    ->label('Barcode'),
                TextInput::make('name')
                    ->label('Nama Produk')
                    ->required(),
                FileUpload::make('image_path')
                    ->label('Gambar Produk')
                    ->image()
                    ->directory('products'),
                TextInput::make('price')
                    ->label('Harga')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('stock')
                    ->label('Stok')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
