@extends('layouts.app')
@section('title', ($record ? 'Edit ' : 'New ').Str::singular($def['label']))
@section('content')
<div class="hub-page-head hub-form-header"><div><div class="hub-breadcrumb"><a href="{{ route('entities.index',$entity) }}">{{ $def['label'] }}</a> <i class="bi bi-chevron-right"></i> {{ $record ? 'Edit' : 'Create' }}</div><h1>{{ $record ? 'Edit' : 'Create' }} {{ Str::singular($def['label']) }}</h1><p>Keep customer information connected, consistent and easy to find.</p></div><a class="hub-button hub-button-quiet" href="{{ $record ? route('entities.show',[$entity,$record->id]) : route('entities.index',$entity) }}"><i class="bi bi-x-lg"></i> Cancel</a></div>
<form method="POST" action="{{ $record ? route('entities.update',[$entity,$record->id]) : route('entities.store',$entity) }}" data-smart-form class="hub-edit-layout">
    @csrf
    @if($record)
        @method('PUT')
    @endif
    <div class="hub-form-main">
        <section class="hub-form-panel"><div class="hub-panel-heading"><span class="hub-panel-symbol"><i class="bi {{ $def['icon'] }}"></i></span><div><h2>{{ Str::singular($def['label']) }} information</h2><p>Fields marked <strong>*</strong> are required.</p></div></div>
            <div class="hub-field-grid">
            @foreach($def['fields'] as $field)
                @php
                    [$name,$type,$required] = $field;
                    $value = old($name, $record ? $record->{$name} : null);
                    $wide = in_array($type, ['textarea','json'],true);
                @endphp
                <div class="hub-field {{ $wide ? 'hub-field-wide' : '' }}"><label class="form-label" for="crm-{{ $name }}">{{ Str::headline($name) }}@if($required) <span class="hub-required" aria-hidden="true">*</span>@endif</label>
                    @if(str_starts_with($type,'select:'))
                        <select id="crm-{{ $name }}" class="form-select crm-input hub-enhanced-select {{ $errors->has($name) ? 'is-invalid' : '' }}" name="{{ $name }}" {{ $required ? 'required' : '' }} data-placeholder="Choose {{ Str::headline($name) }}"><option value="">Select an option</option>
                        @foreach(explode('|',substr($type,7)) as $opt)<option value="{{ $opt }}" {{ (string)$value === $opt ? 'selected' : '' }}>{{ Str::headline($opt) }}</option>@endforeach
                        </select>
                    @elseif(str_starts_with($type,'fk:'))
                        @php
                            $relatedEntity = substr($type,3);
                            $options = app(\App\Services\CrmService::class)->query($relatedEntity,\App\Services\WorkspaceContext::id())->orderByDesc('id')->limit(500)->get();
                        @endphp
                        <select id="crm-{{ $name }}" class="form-select crm-input hub-enhanced-select {{ $errors->has($name) ? 'is-invalid' : '' }}" name="{{ $name }}" data-placeholder="Search related records" {{ $required ? 'required' : '' }}><option value="">None selected</option>
                        @foreach($options as $option)<option value="{{ $option->id }}" {{ (string)$value === (string)$option->id ? 'selected' : '' }}>{{ $option->{config('crm.entities.'.$relatedEntity.'.title')} }} #{{ $option->id }}</option>@endforeach
                        </select>
                    @elseif($type === 'user')
                        <select id="crm-{{ $name }}" class="form-select crm-input hub-enhanced-select" name="{{ $name }}" data-placeholder="Assign a team member"><option value="">Unassigned</option>
                        @foreach(\App\Services\WorkspaceContext::tenant()->users as $member)<option value="{{ $member->id }}" {{ (string)$value === (string)$member->id ? 'selected' : '' }}>{{ $member->name }}</option>@endforeach
                        </select>
                    @elseif($type === 'textarea' || $type === 'json')
                        <textarea id="crm-{{ $name }}" name="{{ $name }}" class="form-control crm-input {{ $errors->has($name) ? 'is-invalid' : '' }}" rows="{{ $type === 'json' ? 4 : 5 }}" placeholder="Add {{ strtolower(Str::headline($name)) }}" {{ $required ? 'required' : '' }}>{{ is_scalar($value) ? $value : json_encode($value) }}</textarea>
                    @elseif($type === 'bool')
                        <div class="hub-switch-wrap"><input type="hidden" name="{{ $name }}" value="0"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="crm-{{ $name }}" name="{{ $name }}" value="1" {{ $value ? 'checked' : '' }}><label class="form-check-label" for="crm-{{ $name }}">Enabled</label></div></div>
                    @elseif($type === 'currency')
                        <select id="crm-{{ $name }}" class="form-select crm-input hub-enhanced-select" name="{{ $name }}">
                        @foreach(\App\Services\CurrencyService::options(\App\Services\WorkspaceContext::id()) as $currency)<option value="{{ $currency }}" {{ ($value ?: \App\Services\WorkspaceContext::tenant()->currency) === $currency ? 'selected' : '' }}>{{ $currency }}</option>@endforeach
                        </select>
                    @else
                        <input id="crm-{{ $name }}" name="{{ $name }}" class="form-control crm-input {{ $errors->has($name) ? 'is-invalid' : '' }}" type="{{ match($type){'money','number'=>'number','email'=>'email','url'=>'url','date'=>'date','datetime'=>'datetime-local',default=>'text'} }}" value="{{ $type === 'datetime' && $value ? date('Y-m-d\TH:i',strtotime((string)$value)) : $value }}" placeholder="{{ Str::headline($name) }}" {{ $required ? 'required' : '' }} @if($type === 'money') step="0.01" min="0" @elseif($type === 'number') step="1" @endif>
                    @endif
                    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            @endforeach
            @php
                $customDefs = app(\App\Services\CrmService::class)->query('custom_fields',\App\Services\WorkspaceContext::id())->where('entity',$entity)->get();
            @endphp
            @foreach($customDefs as $custom)
                @php
                    $savedValue = $record ? \Illuminate\Support\Facades\DB::table('custom_field_values')->where('tenant_id',\App\Services\WorkspaceContext::id())->where('field_id',$custom->id)->where('entity',$entity)->where('entity_id',$record->id)->value('value') : null;
                @endphp
                <div class="hub-field"><label class="form-label" for="custom-{{ $custom->field_key }}">{{ $custom->name }} @if($custom->required)<span class="hub-required">*</span>@endif</label><input id="custom-{{ $custom->field_key }}" class="form-control crm-input" name="custom_{{ $custom->field_key }}" type="{{ $custom->field_type === 'number' ? 'number' : ($custom->field_type === 'date' ? 'date' : 'text') }}" value="{{ old('custom_'.$custom->field_key,$savedValue) }}" {{ $custom->required ? 'required' : '' }}></div>
            @endforeach
            </div>
        </section>
        <div class="hub-form-bottom"><a class="hub-button hub-button-quiet" href="{{ route('entities.index',$entity) }}">Discard</a><button type="submit" class="hub-button hub-button-primary hub-submit"><span class="hub-button-spinner" aria-hidden="true"></span><i class="bi bi-check2"></i><span data-submit-label>{{ $record ? 'Save changes' : 'Create '.Str::singular($def['label']) }}</span></button></div>
    </div>
    <aside class="hub-form-aside"><div class="hub-help-card"><i class="bi bi-stars"></i><h3>Better records, better conversations</h3><p>Connect this {{ Str::lower(Str::singular($def['label'])) }} to owners and related customers so your sales and service teams always have the same context.</p><div class="hub-help-rule"></div><small><i class="bi bi-shield-check me-1"></i> Information is kept inside this workspace.</small></div><div class="hub-tip"><i class="bi bi-lightbulb"></i><span>Tip: Searchable dropdowns help you quickly find and associate related records.</span></div></aside>
</form>
@endsection
