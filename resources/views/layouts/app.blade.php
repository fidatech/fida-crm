<!doctype html>
<html lang="en" dir="ltr" data-theme="amber" data-mode="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Workspace') · Fida CRM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="crm-body hub-body">
@php
    $currentEntity = request()->route('entity');
    $currentGroup = ($currentEntity && config('crm.entities.'.$currentEntity)) ? config('crm.entities.'.$currentEntity.'.group') : null;
    if (request()->routeIs('sales.*')) $currentGroup = 'Commerce';
    if (request()->routeIs('discovery.*')) $currentGroup = 'Sales';
    if (request()->routeIs('roles.*') || request()->routeIs('currencies.*')) $currentGroup = 'Settings';
    if (request()->routeIs('board*')) $currentGroup = 'Sales';
    if (request()->routeIs('assistant.*')) $currentGroup = 'Automation';
    if (request()->routeIs('reports')) $currentGroup = 'Reporting';
    if (request()->routeIs('workspace.*') || request()->routeIs('billing.*') || request()->routeIs('platform.*')) $currentGroup = 'Settings';
    $sections = [
        'Relationships'=>['icon'=>'bi-person-lines-fill','label'=>'CRM','entity'=>'contacts'],
        'Sales'=>['icon'=>'bi-graph-up-arrow','label'=>'Sales','entity'=>'deals'],
        'Marketing'=>['icon'=>'bi-megaphone','label'=>'Marketing','entity'=>'campaigns'],
        'Service'=>['icon'=>'bi-headset','label'=>'Service','entity'=>'tickets'],
        'Commerce'=>['icon'=>'bi-receipt','label'=>'Commerce','entity'=>'products'],
        'Productivity'=>['icon'=>'bi-calendar-check','label'=>'Activities','entity'=>'tasks'],
        'Automation'=>['icon'=>'bi-lightning-charge','label'=>'Automate','entity'=>'workflows'],
        'Reporting'=>['icon'=>'bi-bar-chart-line','label'=>'Reports','entity'=>null],
        'Settings'=>['icon'=>'bi-gear','label'=>'Settings','entity'=>null],
    ];
    $activeTitle = $currentGroup === 'Relationships' ? 'CRM' : ($sections[$currentGroup]['label'] ?? 'Home');
    $access = \App\Services\WorkspaceContext::class;
@endphp
<div class="hub-shell" id="hubShell">
    <aside class="hub-rail" aria-label="Primary navigation">
        <a href="{{ route('dashboard') }}" class="hub-rail-logo" title="Fida CRM home" aria-label="Fida CRM home">F<span>.</span></a>
        <a class="hub-rail-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}" title="Home" aria-label="Home"><i class="bi bi-house-door"></i><small>Home</small></a>
        @foreach($sections as $sectionName => $section)
            @php
                $sectionPermission = match($sectionName){'Relationships'=>'records.contacts.view','Sales'=>'pipeline.view','Marketing'=>'records.campaigns.view','Service'=>'records.tickets.view','Commerce'=>'sales.view','Productivity'=>'records.tasks.view','Automation'=>'records.workflows.view','Reporting'=>'reports.view',default=>'settings.view'};
            @endphp
            @if($access::allows($sectionPermission))
            @php
                $sectionUrl = $section['entity'] ? route('entities.index', $section['entity']) : ($sectionName === 'Reporting' ? route('reports') : route('workspace.settings'));
                if ($sectionName === 'Sales') $sectionUrl = route('board');
                if ($sectionName === 'Automation') $sectionUrl = route('entities.index','workflows');
            @endphp
            <a class="hub-rail-link {{ $currentGroup === $sectionName ? 'is-active' : '' }}" href="{{ $sectionUrl }}" title="{{ $section['label'] }}" aria-label="{{ $section['label'] }}"><i class="bi {{ $section['icon'] }}"></i><small>{{ $section['label'] }}</small></a>
            @endif
        @endforeach
        <div class="hub-rail-bottom"><button class="hub-rail-link hub-rail-theme" data-theme-toggle type="button" title="Toggle light / dark" aria-label="Toggle light / dark"><i class="bi bi-moon-stars"></i></button></div>
    </aside>
    <aside class="hub-secondary" id="crmSidebar" aria-label="Section navigation">
        <div class="hub-side-head"><div><span class="hub-product-kicker">FIDA CUSTOMER PLATFORM</span><strong>{{ $activeTitle }}</strong></div><button class="hub-mobile-close" type="button" id="sidebarClose" aria-label="Close navigation"><i class="bi bi-x-lg"></i></button></div>
        <nav class="hub-side-scroll" data-nav-scroll id="crmNav">
            <span class="hub-side-label">{{ $activeTitle }} workspace</span>
            @if(!$currentGroup)
                <a class="hub-side-link {{ request()->routeIs('dashboard') ? 'is-current' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i> Overview</a>
                @if($access::allows('pipeline.view'))<a class="hub-side-link" href="{{ route('board') }}"><i class="bi bi-kanban"></i> Deal board</a>@endif
                @if($access::allows('reports.view'))<a class="hub-side-link" href="{{ route('reports') }}"><i class="bi bi-bar-chart"></i> Reports</a>@endif
            @else
                @if($currentGroup === 'Sales')
                    @if($access::allows('pipeline.view'))<a class="hub-side-link {{ request()->routeIs('board*') ? 'is-current' : '' }}" href="{{ route('board') }}"><i class="bi bi-kanban"></i> Pipeline board</a>@endif
                    @if($access::allows('discovery.view'))<a class="hub-side-link {{ request()->routeIs('discovery.*') ? 'is-current' : '' }}" href="{{ route('discovery.index') }}"><i class="bi bi-bullseye"></i> Prospect discovery</a>@endif
                @endif
                @foreach(config('crm.entities') as $slug => $entry)
                    @if($entry['group'] === $currentGroup && $access::allows('records.'.$slug.'.view'))
                        <a class="hub-side-link {{ request()->routeIs('entities.*') && $currentEntity === $slug ? 'is-current' : '' }}" href="{{ route('entities.index',$slug) }}"><i class="bi {{ $entry['icon'] }}"></i><span>{{ $entry['label'] }}</span></a>
                    @endif
                @endforeach
                @if($currentGroup === 'Sales')
                    @if($access::allows('records.pipelines.view'))<a class="hub-side-link {{ $currentEntity === 'pipelines' ? 'is-current' : '' }}" href="{{ route('entities.index','pipelines') }}"><i class="bi bi-diagram-3"></i> Pipeline settings</a>@endif
                    @if($access::allows('sales.view'))<a class="hub-side-link" href="{{ route('sales.index','quote') }}"><i class="bi bi-file-earmark-text"></i> Quotes</a>@endif
                @endif
                @if($currentGroup === 'Commerce')
                    @if($access::allows('sales.view'))
                        <a class="hub-side-link {{ request()->routeIs('sales.*') && request()->route('kind') === 'quote' ? 'is-current' : '' }}" href="{{ route('sales.index','quote') }}"><i class="bi bi-file-earmark-text"></i> Quotations</a>
                        <a class="hub-side-link {{ request()->routeIs('sales.*') && request()->route('kind') === 'invoice' ? 'is-current' : '' }}" href="{{ route('sales.index','invoice') }}"><i class="bi bi-receipt"></i> Invoices & payments</a>
                    @endif
                    @if($access::allows('currencies.view'))<a class="hub-side-link {{ request()->routeIs('currencies.*') ? 'is-current' : '' }}" href="{{ route('currencies.index') }}"><i class="bi bi-currency-exchange"></i> Exchange rates</a>@endif
                @endif
                @if($currentGroup === 'Automation' && $access::allows('assistant.use'))<a class="hub-side-link {{ request()->routeIs('assistant.*') ? 'is-current' : '' }}" href="{{ route('assistant.index') }}"><i class="bi bi-stars"></i> AI assistant</a>@endif
                @if($currentGroup === 'Reporting' && $access::allows('reports.view'))<a class="hub-side-link is-current" href="{{ route('reports') }}"><i class="bi bi-bar-chart-line"></i> Reporting overview</a>@endif
                @if($currentGroup === 'Settings')
                    @if($access::allows('settings.view'))<a class="hub-side-link {{ request()->routeIs('workspace.settings') ? 'is-current' : '' }}" href="{{ route('workspace.settings') }}"><i class="bi bi-building-gear"></i> Organization & team</a>@endif
                    @if($access::allows('roles.view'))<a class="hub-side-link {{ request()->routeIs('roles.*') ? 'is-current' : '' }}" href="{{ route('roles.index') }}"><i class="bi bi-person-lock"></i> Roles & permissions</a>@endif
                    @if($access::allows('currencies.view'))<a class="hub-side-link {{ request()->routeIs('currencies.*') ? 'is-current' : '' }}" href="{{ route('currencies.index') }}"><i class="bi bi-currency-exchange"></i> Currencies & rates</a>@endif
                    @if($access::allows('billing.view'))<a class="hub-side-link {{ request()->routeIs('billing.*') ? 'is-current' : '' }}" href="{{ route('billing.plans') }}"><i class="bi bi-credit-card"></i> Billing & subscriptions</a>@endif
                    @if(auth()->user()->platform_admin)<a class="hub-side-link {{ request()->routeIs('platform.*') ? 'is-current' : '' }}" href="{{ route('platform.index') }}"><i class="bi bi-shield-check"></i> Platform console</a>@endif
                @endif
            @endif
            <span class="hub-side-label">Quick access</span>
            @if($access::allows('search.view'))<a class="hub-side-link" href="{{ route('dashboard') }}"><i class="bi bi-house"></i> Home</a>@endif
            @if($access::allows('records.tasks.view'))<a class="hub-side-link" href="{{ route('entities.index','tasks') }}"><i class="bi bi-check2-square"></i> My tasks</a>@endif
            @if($access::allows('discovery.view'))<a class="hub-side-link" href="{{ route('discovery.index') }}"><i class="bi bi-stars"></i> Find prospects</a>@endif
        </nav>
        <div class="hub-side-foot"><span class="hub-side-status"><i class="bi bi-circle-fill"></i> Workspace active</span><small>{{ \App\Services\WorkspaceContext::tenant()->name }}</small></div>
    </aside>
    <div class="hub-backdrop" id="hubBackdrop" aria-hidden="true"></div>
    <div class="hub-main">
        <header class="hub-topbar">
            <div class="hub-top-left">
                <button class="hub-icon hub-mobile-menu" id="sidebarToggle" type="button" aria-label="Open navigation"><i class="bi bi-list"></i></button>
                <div class="dropdown hub-tenant-picker">
                    <button class="hub-workspace-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><span class="hub-workspace-symbol"><i class="bi bi-building"></i></span><span>{{ \App\Services\WorkspaceContext::tenant()->name }}</span></button>
                    <div class="dropdown-menu hub-workspaces-dropdown">
                        <div class="dropdown-header">Your workspaces</div>
                        @foreach(auth()->user()->tenants as $tenantOption)
                            <form action="{{ route('workspace.choose') }}" method="POST" data-smart-form>
                                @csrf
                                <input type="hidden" name="tenant_id" value="{{ $tenantOption->id }}">
                                <button class="dropdown-item" type="submit">{{ $tenantOption->name }} @if($tenantOption->id === \App\Services\WorkspaceContext::id())<i class="bi bi-check-lg ms-auto"></i>@endif</button>
                            </form>
                        @endforeach
                        <div class="dropdown-divider"></div><a class="dropdown-item" href="{{ route('workspace.new') }}"><i class="bi bi-plus-lg me-2"></i>New workspace</a>
                    </div>
                </div>
                <span class="hub-top-divider"></span><span class="hub-section-crumb">{{ $activeTitle }}</span>
            </div>
            <div class="hub-top-right">
                <form method="GET" action="{{ route('search') }}" class="hub-global-search" role="search"><i class="bi bi-search"></i><input name="q" type="search" placeholder="Search the CRM" aria-label="Search all CRM records" autocomplete="off"><kbd>⌘ K</kbd></form>
                <div class="dropdown"><button class="hub-create-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-plus-lg"></i><span>Create</span></button>
                    <div class="dropdown-menu dropdown-menu-end hub-create-menu">
                        <div class="dropdown-header">Create a new record</div>
                        @foreach(['contacts','companies','leads','deals','tasks','tickets','products','meetings'] as $quick)
                            @if($access::allows('records.'.$quick.'.create'))
                            <a class="dropdown-item" href="{{ route('entities.create',$quick) }}"><i class="bi {{ config('crm.entities.'.$quick.'.icon') }}"></i>{{ Str::singular(config('crm.entities.'.$quick.'.label')) }}</a>
                            @endif
                        @endforeach
                        <div class="dropdown-divider"></div>
                        @if($access::allows('sales.create'))<a class="dropdown-item" href="{{ route('sales.create','quote') }}"><i class="bi bi-file-earmark-plus"></i>Quotation</a>
                        <a class="dropdown-item" href="{{ route('sales.create','invoice') }}"><i class="bi bi-receipt"></i>Invoice</a>@endif
                    </div>
                </div>
                <div class="dropdown hub-user-dropdown"><button type="button" class="hub-user-button dropdown-toggle" data-bs-toggle="dropdown" aria-label="Account options"><span class="hub-user-avatar">{{ strtoupper(mb_substr(auth()->user()->name,0,2)) }}</span></button>
                    <div class="dropdown-menu dropdown-menu-end p-2 hub-account-menu"><span class="dropdown-header">{{ auth()->user()->name }}<small class="d-block fw-normal">{{ auth()->user()->email }}</small></span><a class="dropdown-item" href="{{ route('workspace.settings') }}">Workspace settings</a><form method="POST" action="{{ route('logout') }}" data-smart-form>@csrf<button type="submit" class="dropdown-item text-danger">Sign out</button></form></div>
                </div>
            </div>
        </header>
        <main class="hub-content" id="mainContent">
            @if(session('success'))
                <div class="hub-server-message" role="status"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="hub-server-error" role="alert"><strong><i class="bi bi-exclamation-triangle"></i> Please correct the following errors</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<div class="hub-progress" data-navigation-progress aria-hidden="true"></div>
@stack('scripts')
</body>
</html>
