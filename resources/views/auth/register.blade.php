@extends('layouts.auth')

@section('title', 'Daftar Akun Kasir')

@section('content')
<h5 class="fw-bold mb-4 text-dark text-center">Registrasi Akun Kasir Baru</h5>

<form action="{{ route('register') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label text-secondary fw-semibold">Nama Lengkap</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="fa-solid fa-user"></i></span>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Nama Lengkap Kasir" required autofocus>
        </div>
        @error('name')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label text-secondary fw-semibold">Alamat Email</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="kasir@minimarket.com" required>
        </div>
        @error('email')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label text-secondary fw-semibold">Kata Sandi (Password)</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" required>
        </div>
        @error('password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label text-secondary fw-semibold">Konfirmasi Kata Sandi</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="fa-solid fa-shield-halved"></i></span>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Ulangi kata sandi" required>
        </div>
    </div>

    <div class="alert alert-info py-2 px-3 mb-4 fs-7" role="alert">
        <i class="fa-solid fa-info-circle me-1"></i> Pendaftaran publik secara otomatis menetapkan role sebagai <strong>Kasir</strong>. Akun Administrator dikelola secara tertutup oleh Admin.
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-3">
        <i class="fa-solid fa-user-plus me-2"></i> Buat Akun Kasir
    </button>

    <div class="text-center">
        <span class="text-secondary small">Sudah memiliki akun?</span>
        <a href="{{ route('login') }}" class="text-decoration-none fw-semibold text-primary small">Masuk di sini</a>
    </div>
</form>
@endsection
