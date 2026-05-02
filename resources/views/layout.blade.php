<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DB Manager')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', system-ui, sans-serif; box-sizing: border-box; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

        .layout-shell { display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        .layout-body  { display: flex; flex: 1; overflow: hidden; }
        .layout-main  { flex: 1; overflow-y: auto; background: #f8fafc; }

        .topbar { height: 48px; background: #0f172a; border-bottom: 1px solid #1e293b; display: flex; align-items: center; padding: 0 1rem; gap: 1rem; flex-shrink: 0; z-index: 30; }
        .topbar-logo { font-weight: 700; font-size: 1rem; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; letter-spacing: -0.02em; }
        .topbar-logo span.accent { color: #f97316; }
        .topbar-divider { width: 1px; height: 16px; background: #334155; }
        .topbar-conn { font-size: 0.72rem; color: #94a3b8; display: flex; align-items: center; gap: 0.5rem; }
        .topbar-conn .host { color: #cbd5e1; font-weight: 500; }
        .topbar-pill { display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.72rem; font-weight: 500; padding: 0.25rem 0.625rem; border-radius: 0.5rem; text-decoration: none; transition: all 120ms; cursor: pointer; border: none; background: none; }
        .topbar-pill.sql { color: #cbd5e1; background: #1e293b; border: 1px solid #334155; }
        .topbar-pill.sql:hover { background: #334155; color: #fff; }
        .topbar-pill.disconnect { color: #f87171; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); }
        .topbar-pill.disconnect:hover { background: rgba(239,68,68,0.15); color: #fca5a5; }

        .sidebar { width: 280px; min-width: 280px; background: #0f172a; border-right: 1px solid #1e293b; display: flex; flex-direction: column; overflow: hidden; }
        .sidebar-scroll { flex: 1; overflow-y: auto; padding: 0.625rem 0.5rem; }
        .sidebar-section { font-size: 0.65rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.1em; padding: 0.75rem 0.75rem 0.375rem; }
        .sidebar-link { display: flex; align-items: center; gap: 0.625rem; padding: 0.4rem 0.75rem; border-radius: 0.5rem; font-size: 0.8125rem; color: #94a3b8; transition: background 120ms, color 120ms; text-decoration: none; white-space: nowrap; }
        .sidebar-link:hover { background: #1e293b; color: #e2e8f0; }
        .sidebar-link.active { background: rgba(249,115,22,0.12); color: #fb923c; font-weight: 600; }
        .sidebar-link .icon { width: 16px; text-align: center; flex-shrink: 0; font-size: 0.75rem; color: #475569; }
        .sidebar-link:hover .icon, .sidebar-link.active .icon { color: inherit; }
        .sidebar-db { display: flex; align-items: center; padding: 0.35rem 0.75rem; border-radius: 0.5rem; font-size: 0.8rem; color: #94a3b8; text-decoration: none; transition: background 120ms, color 120ms; }
        .sidebar-db:hover { background: #1e293b; color: #e2e8f0; }
        .sidebar-db.active { background: rgba(249,115,22,0.1); color: #fb923c; }
        .sidebar-db .db-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; margin-left: 0.5rem; }
        .sidebar-db .db-chevron { flex-shrink: 0; width: 12px; font-size: 0.6rem; color: #475569; margin-left: 0.25rem; }
        .sidebar-table-link { display: flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0.625rem; border-radius: 0.375rem; font-size: 0.75rem; color: #64748b; text-decoration: none; transition: background 100ms, color 100ms; overflow: hidden; }
        .sidebar-table-link:hover { background: #1e293b; color: #cbd5e1; }
        .sidebar-table-link.active { color: #fdba74; background: rgba(249,115,22,0.08); }
        .sidebar-table-link span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sidebar-footer { padding: 0.5rem 0.75rem; border-top: 1px solid #1e293b; font-size: 0.7rem; color: #334155; }

        .card { background: #fff; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }

        .btn { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.4rem 0.875rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 500; transition: all 120ms; cursor: pointer; border: none; text-decoration: none; line-height: 1.5; }
        .btn:disabled { opacity: 0.45; cursor: not-allowed; }
        .btn-primary  { background: #f97316; color: #fff; box-shadow: 0 1px 3px rgba(249,115,22,0.3); }
        .btn-primary:hover  { background: #ea6c0a; }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #e2e8f0; color: #334155; }
        .btn-danger  { background: #ef4444; color: #fff; }
        .btn-danger:hover  { background: #dc2626; }
        .btn-success { background: #10b981; color: #fff; }
        .btn-success:hover { background: #059669; }
        .btn-blue    { background: #3b82f6; color: #fff; }
        .btn-blue:hover    { background: #2563eb; }
        .btn-purple  { background: #8b5cf6; color: #fff; }
        .btn-purple:hover  { background: #7c3aed; }
        .btn-sm { padding: 0.25rem 0.625rem; font-size: 0.75rem; }

        .form-label { display: block; font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 0.375rem; }
        .form-input { display: block; width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; line-height: 1.4; color: #1e293b; background: #fff; transition: border-color 120ms, box-shadow 120ms; }
        .form-input:focus { outline: none; border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,0.15); }
        .form-input:disabled, .form-input[readonly] { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }
        .form-input.error { border-color: #f87171; box-shadow: 0 0 0 3px rgba(248,113,113,0.15); }
        select.form-input { cursor: pointer; }
        textarea.form-input { resize: vertical; min-height: 80px; }

        .table-action { display: inline-flex; align-items: center; font-size: 0.7rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 0.375rem; transition: all 100ms; text-decoration: none; cursor: pointer; border: none; line-height: 1.4; }
        .ta-blue   { background: #eff6ff; color: #2563eb; }
        .ta-blue:hover   { background: #dbeafe; }
        .ta-slate  { background: #f1f5f9; color: #475569; }
        .ta-slate:hover  { background: #e2e8f0; }
        .ta-green  { background: #ecfdf5; color: #059669; }
        .ta-green:hover  { background: #d1fae5; }
        .ta-amber  { background: #fffbeb; color: #d97706; }
        .ta-amber:hover  { background: #fef3c7; }
        .ta-red    { background: #fef2f2; color: #dc2626; }
        .ta-red:hover    { background: #fee2e2; }
        .ta-purple { background: #f5f3ff; color: #7c3aed; }
        .ta-purple:hover { background: #ede9fe; }

        .badge { display: inline-flex; align-items: center; font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.4rem; border-radius: 0.3rem; }
        .badge-pk  { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .badge-uni { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .badge-mul { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .badge-ai  { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .badge-null { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
        .badge-notnull { background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; }

        .data-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
        .data-table th { padding: 0.625rem 1rem; text-align: left; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        .data-table td { padding: 0.625rem 1rem; border-bottom: 1px solid #f1f5f9; color: #374151; vertical-align: middle; }
        .data-table tbody tr:hover { background: #fafbff; }
        .data-table tbody tr:last-child td { border-bottom: none; }

        .alert { display: flex; align-items: flex-start; gap: 0.625rem; padding: 0.75rem 1rem; border-radius: 0.625rem; font-size: 0.8125rem; margin-bottom: 0.75rem; }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-warning { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }

        .breadcrumb { display: flex; align-items: center; gap: 0.375rem; font-size: 0.8rem; color: #94a3b8; flex-wrap: wrap; }
        .breadcrumb a { color: #64748b; text-decoration: none; transition: color 100ms; }
        .breadcrumb a:hover { color: #f97316; }
        .breadcrumb .sep { color: #cbd5e1; font-size: 0.6rem; }
        .breadcrumb .current { color: #475569; font-weight: 600; }

        .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.55); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 1rem; }
        .modal-box { background: #fff; border-radius: 1rem; box-shadow: 0 20px 60px rgba(0,0,0,0.2); width: 100%; max-width: 480px; overflow: hidden; }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
        .modal-header h2 { font-size: 0.9375rem; font-weight: 700; color: #0f172a; margin: 0; }
        .modal-header p { font-size: 0.75rem; color: #94a3b8; margin: 0.125rem 0 0; }
        .modal-close { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 0.5rem; background: none; border: none; color: #94a3b8; cursor: pointer; transition: all 120ms; }
        .modal-close:hover { background: #f1f5f9; color: #374151; }
        .modal-body { padding: 1.25rem 1.5rem; }
        .modal-footer { display: flex; align-items: center; justify-content: flex-end; gap: 0.625rem; padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; background: #fafbff; }

        .page-btn { display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 0.4rem; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 0.75rem; text-decoration: none; transition: all 100ms; }
        .page-btn:hover { border-color: #f97316; color: #f97316; background: #fff7ed; }
        .page-btn.active { background: #f97316; border-color: #f97316; color: #fff; font-weight: 700; }

        .row-form-row { display: grid; grid-template-columns: 220px 1fr; gap: 1.5rem; padding: 1rem 1.5rem; align-items: start; border-bottom: 1px solid #f8fafc; transition: background 80ms; }
        .row-form-row:hover { background: #fafbff; }
        .row-form-row.error { background: #fff5f5; }
        .row-form-row:last-child { border-bottom: none; }
        .field-meta { padding-top: 0.375rem; }
        .field-name { font-size: 0.875rem; font-weight: 600; color: #1e293b; }
        .field-badges { display: flex; gap: 0.25rem; flex-wrap: wrap; margin-top: 0.3rem; }
        .field-error { font-size: 0.72rem; color: #dc2626; margin-top: 0.375rem; display: flex; align-items: center; gap: 0.25rem; }
        .field-hint  { font-size: 0.72rem; color: #94a3b8; margin-top: 0.25rem; }

        .code-block { background: #0f172a; color: #4ade80; border-radius: 0.625rem; padding: 1rem; font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 0.78rem; line-height: 1.6; overflow-x: auto; white-space: pre-wrap; word-break: break-all; }

        .dot-online { width: 7px; height: 7px; background: #10b981; border-radius: 50%; display: inline-block; box-shadow: 0 0 0 2px rgba(16,185,129,0.2); }
        .empty-state { padding: 3rem; text-align: center; }
        .empty-icon  { width: 48px; height: 48px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem; font-size: 1.125rem; color: #94a3b8; }
        a { transition: color 100ms; }
    </style>

    @stack('head')
</head>
<body class="h-full">
<div class="layout-shell">

    {{-- Topbar --}}
    <header class="topbar">
        <a href="{{ route('db-manager.home') }}" class="topbar-logo">
            <span style="font-size:1.1rem">⚡</span>
            DB<span class="accent">Manager</span>
        </a>

        @php
            $dbMode   = config('db-manager.connection_mode', 'laravel');
            $connInfo = $dbMode === 'custom' ? session('db_manager_connection') : null;
            if ($dbMode === 'custom' && $connInfo) {
                $connLabel = ($connInfo['username'] ?? '?') . '@' . ($connInfo['host'] ?? 'localhost') . ':' . ($connInfo['port'] ?? 3306);
            } else {
                $laravelConn = config('db-manager.connection') ?? config('database.default', 'default');
                $connLabel   = 'Laravel · ' . $laravelConn;
            }
        @endphp

        <div class="topbar-divider"></div>
        <div class="topbar-conn">
            <i class="fa-solid fa-server" style="color:#475569;font-size:.7rem"></i>
            <span class="host">{{ $connLabel }}</span>
            <span class="dot-online" style="margin-left:.25rem"></span>
        </div>

        <div style="margin-left:auto;display:flex;align-items:center;gap:.5rem">
            <a href="{{ route('db-manager.query.index') }}" class="topbar-pill sql">
                <i class="fa-solid fa-terminal" style="color:#f97316;font-size:.7rem"></i> SQL Editor
            </a>
            @if($dbMode === 'custom' && $connInfo)
            <form action="{{ route('db-manager.disconnect') }}" method="POST" style="margin:0">
                @csrf
                <button type="submit" class="topbar-pill disconnect">
                    <i class="fa-solid fa-plug-circle-xmark" style="font-size:.7rem"></i> Disconnect
                </button>
            </form>
            @endif
        </div>
    </header>

    <div class="layout-body">

        {{-- Sidebar --}}
        <aside class="sidebar">
            <div class="sidebar-scroll">
                <a href="{{ route('db-manager.home') }}" class="sidebar-link {{ request()->routeIs('db-manager.home') ? 'active' : '' }}">
                    <i class="fa-solid fa-house icon"></i> Home
                </a>
                <a href="{{ route('db-manager.databases.index') }}" class="sidebar-link {{ request()->routeIs('db-manager.databases.index') ? 'active' : '' }}">
                    <i class="fa-solid fa-layer-group icon"></i> All Databases
                </a>

                <div class="sidebar-section">Databases</div>

                @isset($sidebarDatabases)
                @foreach($sidebarDatabases as $dbName)
                @php $isActive = isset($currentDatabase) && $currentDatabase === $dbName; @endphp
                <div style="margin-bottom:1px">
                    <a href="{{ route('db-manager.tables.index', $dbName) }}"
                       class="sidebar-db {{ $isActive ? 'active' : '' }}" title="{{ $dbName }}">
                        <i class="fa-solid fa-database" style="font-size:.7rem;flex-shrink:0;color:{{ $isActive ? '#f97316' : '#475569' }}"></i>
                        <span class="db-name">{{ $dbName }}</span>
                        <i class="fa-solid {{ $isActive ? 'fa-chevron-down' : 'fa-chevron-right' }} db-chevron"></i>
                    </a>

                    @if($isActive && isset($sidebarTables))
                    <div style="margin-left:.875rem;padding-left:.625rem;border-left:1px solid #1e293b;margin-top:2px;margin-bottom:2px">
                        @forelse($sidebarTables as $tbl)
                        <a href="{{ route('db-manager.tables.browse', [$dbName, $tbl['Name']]) }}"
                           class="sidebar-table-link {{ request()->route('table') === $tbl['Name'] ? 'active' : '' }}"
                           title="{{ $tbl['Name'] }}">
                            <i class="fa-solid fa-table-cells" style="font-size:.6rem;flex-shrink:0;color:#475569"></i>
                            <span>{{ $tbl['Name'] }}</span>
                        </a>
                        @empty
                        <span style="font-size:.72rem;color:#334155;padding:.25rem .625rem;display:block;font-style:italic">No tables</span>
                        @endforelse
                    </div>
                    @endif
                </div>
                @endforeach
                @endisset
            </div>
            <div class="sidebar-footer">
                <i class="fa-solid fa-database" style="margin-right:.25rem;color:#334155"></i> GC DB-Manager
            </div>
        </aside>

        {{-- Main --}}
        <main class="layout-main">

            @if(session('success') || session('error') || $errors->any())
            <div style="padding:1rem 1.5rem 0">
                @if(session('success'))
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check" style="margin-top:.1rem;flex-shrink:0"></i>
                    <span>{{ session('success') }}</span>
                    <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;opacity:.6;font-size:.9rem">✕</button>
                </div>
                @endif
                @if(session('error'))
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation" style="margin-top:.1rem;flex-shrink:0"></i>
                    <span>{{ session('error') }}</span>
                    <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:inherit;opacity:.6;font-size:.9rem">✕</button>
                </div>
                @endif
                @if($errors->any())
                <div class="alert alert-error">
                    <i class="fa-solid fa-triangle-exclamation" style="margin-top:.1rem;flex-shrink:0"></i>
                    <div>
                        <div style="font-weight:600;margin-bottom:.25rem">Please fix the following errors:</div>
                        <ul style="margin:0;padding-left:1rem;font-size:.75rem">
                            @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>
            @endif

            @hasSection('breadcrumb')
            <div style="padding:.875rem 1.5rem 0">
                <nav class="breadcrumb">
                    <i class="fa-solid fa-location-dot" style="font-size:.6rem;color:#94a3b8"></i>
                    @yield('breadcrumb')
                </nav>
            </div>
            @endif

            <div style="padding:1.25rem 1.5rem">
                @yield('content')
            </div>
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
