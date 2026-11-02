@extends('layouts.guest')

@section('title','Reset password')

@section('content')<h2>Reset your password</h2><p class="text-muted mb-4">Enter your work email and we will send a recovery link.</p>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>

@endif<form method="POST" action="{{ route('password.email') }}" data-smart-form>

@csrf<label class="form-label">Email address</label><input name="email" type="email" class="form-control mb-4" required><button class="btn btn-fida-amber w-100">Send recovery link</button></form><a href="{{ route('login') }}" class="d-block mt-4 text-center">Back to sign in</a>

@endsection
