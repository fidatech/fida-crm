@extends('layouts.guest')

@section('title','Create workspace')

@section('content')
<p class="eyebrow">START YOUR WORKSPACE</p><h2>Welcome to Fida CRM</h2><p class="text-muted mb-3">Create your account and your first workspace.</p>
<form method="POST" action="{{ route('register') }}" data-smart-form>

@csrf
 

@foreach(['name'=>'Full name','organization'=>'Company / workspace','email'=>'Work email'] as $field=>$label)<div class="mb-3"><label class="form-label">{{ $label }}</label><input class="form-control" name="{{ $field }}" type="{{ $field==='email'?'email':'text' }}" value="{{ old($field) }}" required></div>

@endforeach
 <div class="mb-3"><label class="form-label">Password (minimum 10 characters)</label><input class="form-control" type="password" name="password" required></div>
 <div class="mb-3"><label class="form-label">Confirm password</label><input class="form-control" type="password" name="password_confirmation" required></div>
 <button class="btn btn-fida-amber w-100 py-3">Create workspace <i class="bi bi-arrow-right ms-2"></i></button>
</form><p class="mt-4 text-center small">Already registered? <a href="{{ route('login') }}">Sign in</a></p>

@endsection
