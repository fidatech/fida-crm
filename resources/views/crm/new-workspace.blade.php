@extends('layouts.guest')

@section('title','New workspace')

@section('content')<h2>New workspace</h2><p class="text-muted">Create a separate company workspace.</p><form method="POST" action="{{ route('workspace.create') }}" data-smart-form>

@csrf<label class="form-label">Organization name</label><input class="form-control crm-input mb-4" name="name" required><button class="btn btn-fida-amber">Create workspace</button></form>

@endsection
