@extends('layouts.guest')

@section('title','New password')

@section('content')<h2>Choose a new password</h2><form method="POST" action="{{ route('password.update') }}" data-smart-form>

@csrf<input name="token" type="hidden" value="{{ $token }}"><label class="form-label">Email address</label><input name="email" type="email" class="form-control mb-3" value="{{ $email }}" required><label class="form-label">New password</label><input name="password" type="password" class="form-control mb-3" required><label class="form-label">Confirm password</label><input name="password_confirmation" type="password" class="form-control mb-4" required><button class="btn btn-fida-amber w-100">Change password</button></form>

@endsection
