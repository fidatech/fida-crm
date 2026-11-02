@extends('layouts.app')

@section('title',($doc?'Edit':'New').' '.ucfirst($kind))

@section('content')
<div class="crm-page-intro"><div><div class="eyebrow"><a href="{{ route('sales.index',$kind) }}">REVENUE / {{ strtoupper($kind) }}</a></div><h1>{{ $doc?'Edit':'New' }} {{ $kind }}</h1><p>Line totals are calculated on the server, including tax and discounts.</p></div><a href="{{ route('sales.index',$kind) }}" class="btn btn-outline-secondary">Back to documents</a></div>
<form method="POST" action="{{ $doc?route('sales.update',[$kind,$doc->id]):route('sales.store',$kind) }}" data-sales-form data-smart-form>

@csrf

@if($doc)

@method('PUT')

@endif
<div class="crm-card mb-4"><div class="crm-card-header"><h3>Document details</h3></div><div class="row g-3 p-4"><div class="col-md-8"><label class="form-label">Title *</label><input name="title" class="form-control crm-input" required value="{{ old('title',$doc?->title) }}" placeholder="e.g. Annual services agreement"></div><div class="col-md-4"><label class="form-label">Currency *</label><select name="currency" class="form-select crm-input hub-enhanced-select">

@foreach(\App\Services\CurrencyService::options(\App\Services\WorkspaceContext::id()) as $c)<option value="{{ $c }}" 

@selected(old('currency',$doc?->currency??\App\Services\WorkspaceContext::tenant()->currency)===$c)>{{ $c }}</option>

@endforeach</select></div>

@foreach(['company_id'=>'companies','contact_id'=>'contacts','deal_id'=>'deals'] as $field=>$entity)<div class="col-md-4"><label class="form-label">{{ Str::headline($entity) }}</label><select class="form-select crm-input hub-enhanced-select" name="{{ $field }}"><option value="">None</option>

@foreach(app(\App\Services\CrmService::class)->query($entity,\App\Services\WorkspaceContext::id())->limit(500)->get() as $x)<option value="{{ $x->id }}" 

@selected((string)old($field,$doc?->{$field})===(string)$x->id)>{{ $x->{config('crm.entities.'.$entity.'.title')} }}</option>

@endforeach</select></div>

@endforeach
<div class="col-md-4"><label class="form-label">Issue date *</label><input type="date" name="issued_on" class="form-control crm-input" required value="{{ old('issued_on',$doc?->issued_on??date('Y-m-d')) }}"></div><div class="col-md-4"><label class="form-label">Due / expiry date</label><input type="date" name="due_on" class="form-control crm-input" value="{{ old('due_on',$doc?->due_on) }}"></div><div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="3" class="form-control crm-input">{{ old('notes',$doc?->notes) }}</textarea></div></div></div>
<div class="crm-card mb-4"><div class="crm-card-header"><div><h3>Products & line items</h3><p>Quantities, unit prices, discounts and taxes</p></div><button class="btn btn-outline-secondary btn-sm" type="button" data-add-line><i class="bi bi-plus-lg"></i> Add item</button></div><div class="table-responsive"><table class="table crm-table sales-lines"><thead><tr><th>Catalog product</th><th>Description</th><th>Qty</th><th>Unit price</th><th>Discount %</th><th>Tax %</th><th>Line total</th><th></th></tr></thead><tbody id="salesLineItems">

@php
$items=old('lines',$lines->count()?$lines->map(fn($x)=>(array)$x)->toArray():[['description'=>'','quantity'=>1,'unit_price'=>0,'discount_percent'=>0,'tax_percent'=>0]]);
@endphp

@foreach($items as $i=>$line)
<tr data-line><td><select name="lines[{{ $i }}][product_id]" class="form-select crm-input" data-product-picker><option value="">Custom line</option>

@foreach(app(\App\Services\CrmService::class)->query('products',\App\Services\WorkspaceContext::id())->where('active',1)->limit(200)->get() as $product)<option value="{{ $product->id }}" data-price="{{ $product->unit_price }}" data-tax="{{ $product->tax_rate }}" data-label="{{ $product->name }}" data-currency="{{ $product->currency }}" 

@selected((string)($line['product_id']??'')===(string)$product->id)>{{ $product->name }}</option>

@endforeach</select></td><td><input name="lines[{{ $i }}][description]" class="form-control crm-input" required value="{{ $line['description']??'' }}" placeholder="Service or product"></td><td><input type="number" step="0.001" min="0.001" name="lines[{{ $i }}][quantity]" class="form-control crm-input" value="{{ $line['quantity']??1 }}" required></td><td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][unit_price]" class="form-control crm-input" value="{{ $line['unit_price']??0 }}" required></td><td><input type="number" step="0.01" min="0" max="100" name="lines[{{ $i }}][discount_percent]" class="form-control crm-input" value="{{ $line['discount_percent']??0 }}"></td><td><input type="number" step="0.01" min="0" max="100" name="lines[{{ $i }}][tax_percent]" class="form-control crm-input" value="{{ $line['tax_percent']??0 }}"></td><td data-total class="fw-semibold">—</td><td><button type="button" class="btn btn-sm btn-outline-danger" data-remove-line><i class="bi bi-x"></i></button></td></tr>

@endforeach</tbody></table></div><div class="crm-sales-total">Estimated total <strong data-grand-total>—</strong></div></div>
<div class="text-end mb-5"><button type="submit" class="btn btn-fida-amber px-5 py-3"><i class="bi bi-check-lg me-2"></i>Save {{ $kind }}</button></div></form>
<template id="salesLineTemplate"><tr data-line><td><select data-name="product_id" data-product-picker class="form-select crm-input"><option value="">Custom line</option>

@foreach(app(\App\Services\CrmService::class)->query('products',\App\Services\WorkspaceContext::id())->where('active',1)->limit(200)->get() as $product)<option value="{{ $product->id }}" data-price="{{ $product->unit_price }}" data-tax="{{ $product->tax_rate }}" data-label="{{ $product->name }}" data-currency="{{ $product->currency }}">{{ $product->name }}</option>

@endforeach</select></td><td><input data-name="description" class="form-control crm-input" required placeholder="Product / service"></td><td><input type="number" step="0.001" min="0.001" data-name="quantity" value="1" class="form-control crm-input" required></td><td><input type="number" step="0.01" min="0" data-name="unit_price" value="0" class="form-control crm-input" required></td><td><input type="number" step="0.01" min="0" max="100" data-name="discount_percent" value="0" class="form-control crm-input"></td><td><input type="number" step="0.01" min="0" max="100" data-name="tax_percent" value="0" class="form-control crm-input"></td><td data-total>—</td><td><button class="btn btn-sm btn-outline-danger" type="button" data-remove-line>×</button></td></tr></template>

@endsection
