@extends('layouts.admin')

@section('title', 'Masuk Panel Guru & Peneliti - Dento')

@section('content')
<div style="max-width: 420px; margin: 40px auto; background: #ffffff; padding: 32px; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
    <h2 style="color: var(--color-title); margin-top: 0; font-size: 24px; text-align: center; margin-bottom: 24px;">Masuk Panel Admin</h2>

    <form method="POST" action="{{ route('admin.login.post') }}">
        @csrf

        <div style="margin-bottom: 16px;">
            <label for="email" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">Alamat Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box; font-size: 15px;">
            @error('email')
                <span style="color: #ef4444; font-size: 13px; display: block; margin-top: 4px;">{{ $message }}</span>
            @enderror
        </div>

        <div style="margin-bottom: 20px;">
            <label for="password" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px;">Kata Sandi</label>
            <input type="password" id="password" name="password" required
                style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box; font-size: 15px;">
        </div>

        <div style="margin-bottom: 20px; display: flex; align-items: center;">
            <input type="checkbox" id="remember" name="remember" style="margin-right: 8px;">
            <label for="remember" style="font-size: 14px; color: #475569;">Ingat saya di perangkat ini</label>
        </div>

        <button type="submit"
            style="width: 100%; padding: 12px; background-color: var(--color-sky-dark); color: #ffffff; border: none; border-radius: 8px; font-weight: 600; font-size: 16px; cursor: pointer;">
            Masuk
        </button>
    </form>
</div>
@endsection
