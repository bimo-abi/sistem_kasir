Bertindaklah sebagai Senior Laravel Developer, Software Architect, Database Designer, UI/UX Designer, dan mentor pemrograman yang berpengalaman membangun sistem Point of Sale (POS) untuk minimarket menggunakan Laravel 13 dan MySQL.

TUJUAN
Bangun project Laravel baru berupa sistem kasir minimarket yang berfungsi secara nyata, bukan sekadar desain frontend. Jadikan PDF "Prosedur Kerja Praktikum ke-3 - Membuat Halaman Transaksi Pembayaran Kasir" sebagai acuan utama fitur dan alur transaksi. Jangan menghilangkan fitur yang disebutkan dalam PDF.

KONDISI DAN TEKNOLOGI
- Project baru Laravel 13.
- Database MySQL melalui Laragon.
- Bahasa antarmuka Indonesia.
- Tampilan modern, responsif, mudah digunakan kasir.
- Gunakan Blade, Bootstrap, JavaScript seperlunya, dan arsitektur MVC Laravel.
- Autentikasi login, register, logout, dengan role admin dan kasir.
- Pembayaran non-tunai dicatat manual, tanpa payment gateway.
- Buat kode siap pakai dan jelaskan untuk pemula.
- Jangan menganggap ada tabel atau file lama karena ini project baru.

ATURAN KERJA
1. Kerjakan bertahap dan berurutan. Setiap tahap harus menghasilkan implementasi yang dapat dijalankan dan diuji sebelum lanjut.
2. Jangan langsung memberikan seluruh project dalam satu jawaban.
3. Awali dengan rencana tahap dan arsitektur, kemudian mulai Tahap 1.
4. Setiap kali memberikan kode, sebutkan path file lengkap, apakah membuat atau mengganti file, dan isi kode lengkap yang diperlukan.
5. Jangan menggunakan pseudocode, potongan yang tidak lengkap, atau komentar "lanjutkan sendiri".
6. Jangan mengarang hasil eksekusi. Berikan perintah pengujian dan minta saya mengirim error jika muncul.
7. Sebelum mengubah file yang sudah dibuat pada tahap sebelumnya, jelaskan perubahan dan dampaknya.
8. Gunakan praktik Laravel yang sesuai, validasi server-side, CSRF, autentikasi dan otorisasi.
9. Jangan mempercayai harga, diskon, pajak, subtotal, total, atau stok dari browser. Hitung dan validasi ulang di backend.
10. Gunakan transaksi database (DB::transaction) untuk pembayaran dan pengurangan stok agar konsisten.
11. Hindari duplikasi logika, gunakan Form Request/service bila memang bermanfaat, tetapi jangan membuat arsitektur terlalu rumit untuk tugas praktikum.
12. Gunakan rupiah (IDR), format Indonesia, dan waktu lokal Asia/Jakarta.
13. Setelah setiap tahap, berikan checklist keberhasilan dan langkah tes manual yang rinci.
14. Tunggu konfirmasi saya sebelum memulai tahap berikutnya.

FITUR DAN MODUL

A. AUTENTIKASI DAN ROLE
- Register, login, logout.
- Role admin dan kasir.
- Admin dapat mengelola pengguna dan menetapkan role.
- Kasir hanya mengakses fungsi yang diperlukan untuk transaksi.
- Lindungi route dan aksi berdasarkan role.
- Validasi form, pesan kesalahan, dan redirect yang jelas.
- Tentukan dengan aman apakah register publik boleh memilih role; secara default pengguna baru tidak boleh menetapkan dirinya sebagai admin.

B. DASHBOARD
- Dashboard sesuai role.
- Ringkasan transaksi dan penjualan yang relevan.
- Navigasi ke produk, kasir, transaksi, dan pengelolaan lain sesuai hak akses.
- Jangan menampilkan angka statistik palsu sebagai data nyata.

C. DATA PRODUK
- Daftar produk dari database MySQL.
- Produk memiliki nama, kode produk, barcode bila tersedia, harga, dan stok.
- Pencarian berdasarkan nama, kode, atau barcode.
- Validasi kode/barcode agar tidak duplikat sesuai aturan yang ditentukan.
- Produk yang sudah pernah digunakan dalam transaksi tidak dihapus secara sembarangan; pertimbangkan status aktif/nonaktif.
- CRUD produk untuk admin, dan hak akses kasir dibatasi sesuai kebutuhan.

D. HALAMAN TRANSAKSI KASIR (POS)
Ikuti susunan konsep PDF:
- Header toko, informasi toko, nomor transaksi, tanggal/waktu, dan identitas kasir.
- Area kiri untuk pencarian barang dan keranjang belanja.
- Area kanan untuk ringkasan pembayaran dan pembayaran.
- Layout responsif desktop/tablet/mobile.

Pencarian dan keranjang:
- Cari produk menurut nama/kode/barcode.
- Input dapat menerima hasil scan barcode.
- Produk yang ditemukan dapat ditambahkan ke keranjang.
- Jika produk sudah ada di keranjang, tambah kuantitasnya.
- Tampilkan nomor urut, nama/kode barang, harga, qty, subtotal, dan tombol hapus.
- Tombol tambah/kurangi qty dan input qty manual.
- Validasi qty minimal 1 dan stok tidak boleh terlampaui.
- Tampilkan keadaan keranjang kosong.

Ringkasan pembayaran:
- Total item.
- Subtotal.
- Diskon persentase.
- Diskon nominal.
- Pajak/PPN.
- Biaya lain.
- Total akhir.
- Ikuti rumus PDF: diskon persentase dihitung dari subtotal, lalu ditambah diskon nominal; total akhir = maksimal nol dari subtotal dikurangi total diskon ditambah pajak dan biaya lain.
- Nilai awal diskon, pajak, dan biaya lain adalah 0, sesuai contoh PDF.
- Jangan menambahkan aturan pajak atau diskon baru tanpa instruksi. Validasi nilai agar tidak negatif dan diskon persentase berada pada 0–100.
- Jelaskan apakah pajak/biaya dimasukkan sebagai nominal rupiah sesuai contoh input PDF.

Pembayaran:
- Input uang dibayar.
- Metode: Tunai, QRIS, Debit, Kredit, E-Wallet, Transfer.
- Hitung kembalian otomatis.
- Jika uang kurang, tampilkan jumlah kekurangan dan cegah pembayaran.
- Untuk pembayaran non-tunai, catat metode secara manual; jangan mengklaim ada verifikasi gateway.
- Sediakan tombol nominal cepat: uang pas dan nominal praktis seperti yang dicontohkan PDF, dengan nilai disesuaikan total.
- Tombol Bayar & Cetak.
- Setelah sukses, simpan transaksi dan detail transaksi, kurangi stok, dan arahkan ke struk.
- Cegah double submit dan transaksi ganda.

E. TRANSAKSI DITAHAN
- Tombol tahan transaksi.
- Simpan transaksi sementara agar tidak hilang ketika kasir melayani pelanggan lain.
- Halaman daftar transaksi ditahan dengan nomor transaksi, jumlah item, total sementara, dan tombol lanjutkan.
- Kasir dapat membuka kembali transaksi dan melanjutkan belanja.
- Sediakan pembatalan transaksi ditahan dengan konfirmasi.
- Transaksi ditahan tidak mengurangi stok permanen; ketika dilanjutkan dan dibayar, periksa stok lagi.
- Pastikan data keranjang dan ringkasan tersimpan konsisten.

F. DATABASE DAN RIWAYAT TRANSAKSI
Gunakan pemisahan tabel transaksi dan detail transaksi seperti rekomendasi PDF:
- transactions: nomor transaksi, user/kasir, pelanggan bila digunakan, subtotal, diskon, pajak, biaya lain, grand total, jumlah dibayar, kembalian, metode pembayaran, status, timestamp.
- transaction_details: transaction_id, product_id, snapshot kode produk, snapshot nama produk, harga saat transaksi, qty, diskon bila diterapkan per item, subtotal.
- products: data barang dan stok.
- users: akun dan role.
- customers hanya dibuat jika memang dibutuhkan untuk mendukung pilihan pelanggan dalam PDF.
- Tentukan relasi, foreign key, indeks, tipe data uang yang aman (DECIMAL atau integer rupiah), dan aturan penghapusan.
- Simpan snapshot nama, kode, dan harga produk agar struk lama tetap benar meskipun produk diubah.
- Buat migration, model, relasi, seeder data contoh, dan factory bila relevan.
- Nomor transaksi dibuat unik dan aman terhadap transaksi bersamaan.
- Riwayat transaksi dengan pencarian nomor, filter tanggal, detail, dan akses sesuai role.
- Status transaksi ditentukan secara eksplisit, misalnya held, completed, cancelled, sesuai kebutuhan alur.

G. STOK
- Stok otomatis berkurang hanya ketika pembayaran berhasil.
- Lakukan pengecekan stok ulang di backend ketika membayar.
- Gunakan transaksi database dan penguncian baris yang sesuai untuk menghindari overselling pada transaksi bersamaan.
- Jangan izinkan stok negatif.
- Catat perubahan stok jika diperlukan agar dapat diaudit.
- Jelaskan perlakuan pembatalan dan retur jika fitur tersebut belum termasuk tahap utama.

H. STRUK DAN CETAK
- Halaman struk yang rapi dan ramah printer.
- Tampilkan identitas toko, nomor transaksi, tanggal, kasir, item, harga, qty, subtotal, diskon, pajak, biaya lain, total, pembayaran, metode, dan kembalian.
- Tombol cetak menggunakan window.print().
- CSS khusus print, ukuran thermal 58 mm dan 80 mm atau pilihan ukuran yang mudah diubah.
- Sediakan halaman struk yang tetap bisa dibuka dari riwayat.
- Jangan menganggap printer sudah terhubung; jelaskan bahwa cetak browser bergantung pada perangkat dan pengaturan printer.

I. SHORTCUT KEYBOARD
Ikuti PDF:
- F2 fokus ke pencarian produk.
- F4 fokus ke input pembayaran.
- Escape memicu pembatalan dengan konfirmasi.
- Hindari shortcut saat pengguna sedang mengetik di input yang tidak sesuai, kecuali memang diperlukan.
- Cegah konflik dengan fungsi browser yang relevan.

J. KEAMANAN, VALIDASI, DAN UX
- Validasi semua input di backend.
- Proteksi CSRF dan route middleware.
- Otorisasi berdasarkan role.
- Escape output Blade dan hindari menyisipkan input pengguna ke HTML mentah.
- Tangani stok berubah, produk nonaktif, keranjang kosong, pembayaran kurang, dan kegagalan database.
- Gunakan pesan sukses/gagal yang mudah dimengerti.
- Form memiliki label, state loading bila relevan, dan feedback validasi.
- Gunakan pagination untuk daftar panjang.
- Pastikan tampilan responsif dan aksesibel.

TAHAP PENGERJAAN
Tahap 1: Perencanaan, kebutuhan, struktur folder, alur sistem, dan persiapan Laravel 13 + Laragon + MySQL.
Tahap 2: Instalasi project baru, konfigurasi .env, koneksi database, dan pengujian.
Tahap 3: Autentikasi, register/login/logout, role admin/kasir, middleware, dan pengujian akses.
Tahap 4: Database produk, migration, model, seeder, CRUD produk, dan pencarian.
Tahap 5: Layout utama, navigasi, dashboard, dan UI responsif.
Tahap 6: Halaman POS, pencarian produk, keranjang, kuantitas, serta perhitungan ringkasan.
Tahap 7: Backend checkout, validasi, penyimpanan transaksi/detail, pengurangan stok atomik, dan metode pembayaran.
Tahap 8: Transaksi ditahan dan melanjutkan transaksi.
Tahap 9: Struk, print stylesheet, dan shortcut keyboard.
Tahap 10: Riwayat transaksi, detail transaksi, dan penyempurnaan UX.
Tahap 11: Pengujian menyeluruh, skenario edge case, keamanan, dan dokumentasi praktikum.

FORMAT JAWABAN SETIAP TAHAP
1. Tujuan tahap.
2. Penjelasan sederhana tentang konsep yang digunakan.
3. Persiapan dan perintah terminal secara berurutan.
4. Daftar file yang dibuat/diubah.
5. Kode lengkap setiap file yang diperlukan, dengan path.
6. Penjelasan kode dan hubungan MVC.
7. Cara menjalankan.
8. Cara menguji langkah demi langkah.
9. Hasil yang seharusnya terlihat.
10. Masalah umum dan cara memperbaikinya.
11. Checklist tahap.
12. Minta konfirmasi sebelum lanjut.

KETENTUAN AWAL
Jangan langsung menulis semua kode. Mulai dengan:
- Menyajikan arsitektur dan rencana tahap.
- Menjelaskan asumsi yang dipakai dan pertanyaan yang benar-benar menghalangi implementasi.
- Jika tidak ada hal yang menghalangi, mulai Tahap 1 dengan instruksi konkret.
- Jangan meminta saya mengulang informasi yang sudah saya berikan.
- Gunakan bahasa Indonesia, penjelasan ramah pemula, terstruktur, dan rinci.
- Jadikan PDF praktikum sebagai acuan fitur dan tampilan, sementara detail implementasi backend disusun agar sesuai Laravel 13 dan aman.
