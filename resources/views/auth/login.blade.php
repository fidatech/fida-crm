@extends('layouts.guest')

@section('title','Sign in')

@section('content')

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>

@endif<p class="eyebrow">WELCOME BACK</p><h2>Sign in to your workspace</h2><p class="text-muted mb-4">Enter your credentials to continue.</p>
<form method="POST" action="{{ route('login') }}" data-smart-form>

@csrf
 <div class="mb-3"><label class="form-label">Work email</label><input class="form-control" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></div>
 <div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" autocomplete="current-password" required></div>
 <div class="d-flex justify-content-between align-items-center mb-4"><label class="d-flex gap-2 align-items-center"><input type="checkbox" name="remember" value="1"> Remember this device</label><a class="small" href="{{ route('password.request') }}">Forgot password?</a></div>
 <button class="btn btn-fida-amber w-100 py-3">Sign in <i class="bi bi-arrow-right ms-2"></i></button>
</form><p class="mt-4 text-center small">New to Fida CRM? <a href="{{ route('register') }}">Create a workspace</a></p>

@endsection
