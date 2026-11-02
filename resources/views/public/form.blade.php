<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $form->name }} · Contact us</title>@vite(['resources/css/app.css','resources/js/app.js'])</head><body class="crm-auth"><div class="auth-panel mx-auto mt-5" style="max-width:540px"><div class="eyebrow">CONTACT OUR TEAM</div><h1>{{ $form->name }}</h1><form method="POST" action="{{ route('form.submit',$form->slug) }}" data-smart-form>

@csrf<div style="display:none"><label>Do not fill this field<input name="website_confirm" tabindex="-1" autocomplete="off"></label></div>

@foreach(['name'=>'Your name','email'=>'Email address','phone'=>'Phone number','company_name'=>'Company'] as $key=>$label)<label class="form-label mt-3">{{ $label }}</label><input class="form-control crm-input" name="{{ $key }}" 

@if($key==='email') type="email" 

@endif 

@if(in_array($key,['name','email'])) required @endif>

@endforeach<button class="btn btn-fida-amber mt-4 w-100">Submit request</button></form></div></body></html>
