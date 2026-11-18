<?php

return [
    'printer' => env('RECEIPT_PRINTER', 'pdf'),
    'printer_name' => env('RECEIPT_PRINTER_NAME', 'POS-58'),
    'store_name' => env('RECEIPT_STORE_NAME', 'MINIMARKET SEJAHTERA'),
    'store_address' => env('RECEIPT_STORE_ADDRESS', 'Jl. Pemuda No. 45, Jakarta Pusat'),
    'store_phone' => env('RECEIPT_STORE_PHONE', '(021) 555-0192'),
];
