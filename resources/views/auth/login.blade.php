@extends('layouts.auth')

@section('title', 'Login Pengguna')

@section('content')
<h5 class="fw-bold mb-4 text-dark text-center">Masuk ke Akun Anda</h5>

<form action="{{ route('login') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label text-secondary fw-semibold">Alamat Email</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="contoh@minimarket.com" required autofocus>
        </div>
        @error('email')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label text-secondary fw-semibold">Kata Sandi (Password)</label>
        <div class="input-group">
            <span class="input-group-text bg-light text-secondary"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" required>
        </div>
        @error('password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label text-secondary fs-6" for="remember">
                Ingat Saya
            </label>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-3">
        <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk Sistem
    </button>

    <div class="text-center">
        <span class="text-secondary small">Belum punya akun?</span>
        <a href="{{ route('register') }}" class="text-decoration-none fw-semibold text-primary small">Daftar Akun Kasir</a>
    </div>
</form>
@endsection
