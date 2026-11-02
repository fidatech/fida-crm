<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>

@yield('title','Welcome') · Fida CRM</title>@vite(['resources/css/app.css','resources/js/app.js'])</head><body class="crm-auth"><div class="auth-main"><div class="auth-left"><div class="auth-logo"><span class="brand-mark">F</span> FIDA CRM</div><h1>Every relationship.<br><em>One clear view.</em></h1><p>Sales, marketing, support and revenue workflows in a focused customer platform.</p><div class="auth-stripes"><span></span><span></span><span></span></div><small>Built for teams that value clarity and speed.</small></div><div class="auth-right"><div class="auth-panel">

@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>

@endif 

@yield('content')</div></div></div></body></html>
