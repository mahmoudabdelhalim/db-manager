@extends('db-manager::layout')
@section('title', 'Home — DB Manager')

@section('content')
<div style="margin-bottom:1.5rem">
    <h1 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin:0">Server Overview</h1>
    <p style="font-size:.8rem;color:#64748b;margin:.2rem 0 0">
        @php
            $dbMode = config('db-manager.connection_mode', 'laravel');
            if ($dbMode === 'custom') {
                echo (session('db_manager_connection')['host'] ?? '') . ':' . (session('db_manager_connection')['port'] ?? '');
            } else {
                $conn = config('db-manager.connection') ?? config('database.default', 'default');
                $cfg  = config("database.connections.{$conn}", []);
                echo ($cfg['host'] ?? '127.0.0.1') . ':' . ($cfg['port'] ?? 3306);
            }
        @endphp
    </p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:2rem">
    @foreach([
        ['label'=>'MySQL Version','value'=>$version,'icon'=>'fa-database','color'=>'#3b82f6','bg'=>'#eff6ff'],
        ['label'=>'Databases','value'=>count($databases),'icon'=>'fa-layer-group','color'=>'#f97316','bg'=>'#fff7ed'],
        ['label'=>'Status','value'=>'Online','icon'=>'fa-circle-check','color'=>'#10b981','bg'=>'#ecfdf5'],
        ['label'=>'Mode','value'=>config('db-manager.connection_mode','laravel'),'icon'=>'fa-plug','color'=>'#8b5cf6','bg'=>'#f5f3ff'],
    ] as $stat)
    <div class="card" style="padding:1.25rem">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
            <span style="font-size:.65rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em">{{ $stat['label'] }}</span>
            <div style="width:32px;height:32px;border-radius:.5rem;background:{{ $stat['bg'] }};display:flex;align-items:center;justify-content:center">
                <i class="fa-solid {{ $stat['icon'] }}" style="color:{{ $stat['color'] }};font-size:.8rem"></i>
            </div>
        </div>
        <div style="font-size:1.1rem;font-weight:700;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            {{ $stat['value'] }}
        </div>
    </div>
    @endforeach
</div>

<div style="display:flex;gap:.75rem;margin-bottom:2rem;flex-wrap:wrap">
    <a href="{{ route('db-manager.databases.index') }}" class="btn btn-primary">
        <i class="fa-solid fa-layer-group"></i> Manage Databases
    </a>
    <a href="{{ route('db-manager.query.index') }}" class="btn btn-purple">
        <i class="fa-solid fa-terminal"></i> SQL Editor
    </a>
</div>

<div class="card" style="overflow:hidden">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.875rem 1.25rem;border-bottom:1px solid #e2e8f0;background:#f8fafc">
        <div style="display:flex;align-items:center;gap:.5rem;font-weight:700;color:#374151;font-size:.875rem">
            <i class="fa-solid fa-database" style="color:#f97316;font-size:.8rem"></i>
            Databases
        </div>
        <a href="{{ route('db-manager.databases.index') }}" style="font-size:.75rem;color:#f97316;text-decoration:none;font-weight:600">
            Manage all <i class="fa-solid fa-arrow-right" style="font-size:.65rem"></i>
        </a>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($databases as $dbName)
            <tr>
                <td>
                    <a href="{{ route('db-manager.tables.index', $dbName) }}"
                       style="font-weight:600;color:#f97316;text-decoration:none;display:flex;align-items:center;gap:.5rem">
                        <i class="fa-solid fa-database" style="font-size:.65rem;color:#cbd5e1"></i>
                        {{ $dbName }}
                    </a>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:.375rem">
                        <a href="{{ route('db-manager.tables.index', $dbName) }}" class="table-action ta-blue">
                            <i class="fa-solid fa-table-cells" style="margin-right:.25rem"></i>Tables
                        </a>
                        <a href="{{ route('db-manager.query.index', ['db' => $dbName]) }}" class="table-action ta-purple">
                            <i class="fa-solid fa-terminal" style="margin-right:.25rem"></i>Query
                        </a>
                        <a href="{{ route('db-manager.databases.export', $dbName) }}" class="table-action ta-green">
                            <i class="fa-solid fa-download" style="margin-right:.25rem"></i>Export
                        </a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
