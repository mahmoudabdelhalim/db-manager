@extends('db-manager::layout')
@section('title', "$database — DB Manager")

@section('breadcrumb')
<a href="{{ route('db-manager.home') }}">Home</a><span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('db-manager.databases.index') }}">Databases</a><span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span class="current">{{ $database }}</span>
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem">
    <div>
        <h1 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin:0;display:flex;align-items:center;gap:.5rem">
            <i class="fa-solid fa-database" style="color:#f97316;font-size:1rem"></i> {{ $database }}
        </h1>
        <p style="font-size:.8rem;color:#64748b;margin:.2rem 0 0">{{ count($tables) }} tables</p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="{{ route('db-manager.query.index', ['db' => $database]) }}" class="btn btn-purple btn-sm">
            <i class="fa-solid fa-terminal"></i> SQL
        </a>
        <a href="{{ route('db-manager.databases.export', $database) }}" class="btn btn-success btn-sm">
            <i class="fa-solid fa-download"></i> Export
        </a>
        <a href="{{ route('db-manager.tables.create', $database) }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> New Table
        </a>
    </div>
</div>

<div class="card" style="overflow:hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Table</th>
                <th style="text-align:right">Rows</th>
                <th>Engine</th>
                <th>Collation</th>
                <th style="text-align:right">Size</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tables as $table)
            <tr>
                <td>
                    <a href="{{ route('db-manager.tables.browse', [$database, $table['Name']]) }}"
                       style="font-weight:600;color:#f97316;text-decoration:none;display:flex;align-items:center;gap:.5rem">
                        <i class="fa-solid fa-table-cells" style="font-size:.65rem;color:#cbd5e1"></i>
                        {{ $table['Name'] }}
                    </a>
                </td>
                <td style="text-align:right;font-family:monospace;font-size:.78rem;color:#475569">
                    {{ number_format($table['Rows'] ?? 0) }}
                </td>
                <td>
                    @if($table['Engine'])
                    <span style="font-size:.7rem;background:#f1f5f9;color:#475569;padding:.2rem .5rem;border-radius:.375rem;font-weight:600">{{ $table['Engine'] }}</span>
                    @else —
                    @endif
                </td>
                <td style="font-size:.72rem;color:#64748b;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    {{ $table['Collation'] ?? '—' }}
                </td>
                <td style="text-align:right;font-family:monospace;font-size:.72rem;color:#64748b">
                    @php
                        $sz = ($table['Data_length'] ?? 0) + ($table['Index_length'] ?? 0);
                        echo $sz > 1048576 ? round($sz / 1048576, 1) . ' MB' : round($sz / 1024, 1) . ' KB';
                    @endphp
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:.3rem;flex-wrap:wrap">
                        <a href="{{ route('db-manager.tables.browse', [$database, $table['Name']]) }}" class="table-action ta-blue">
                            <i class="fa-solid fa-eye" style="margin-right:.25rem"></i>Browse
                        </a>
                        <a href="{{ route('db-manager.tables.structure', [$database, $table['Name']]) }}" class="table-action ta-slate">
                            <i class="fa-solid fa-sitemap" style="margin-right:.25rem"></i>Structure
                        </a>
                        <a href="{{ route('db-manager.rows.create', [$database, $table['Name']]) }}" class="table-action ta-green">
                            <i class="fa-solid fa-plus" style="margin-right:.25rem"></i>Insert
                        </a>
                        <form action="{{ route('db-manager.tables.truncate', [$database, $table['Name']]) }}" method="POST" style="margin:0"
                              onsubmit="return confirm('Truncate \'{{ $table['Name'] }}\'?\n\nAll rows will be permanently deleted.')">
                            @csrf
                            <button type="submit" class="table-action ta-amber">
                                <i class="fa-solid fa-eraser" style="margin-right:.25rem"></i>Truncate
                            </button>
                        </form>
                        <form action="{{ route('db-manager.tables.destroy', [$database, $table['Name']]) }}" method="POST" style="margin:0"
                              onsubmit="return confirm('Drop table \'{{ $table['Name'] }}\'?\n\nThis cannot be undone!')">
                            @csrf @method('DELETE')
                            <button type="submit" class="table-action ta-red">
                                <i class="fa-solid fa-trash" style="margin-right:.25rem"></i>Drop
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-table"></i></div>
                        <p style="font-weight:600;color:#374151;margin:0">No tables yet</p>
                        <div style="display:flex;align-items:center;justify-content:center;gap:1rem;margin-top:.5rem;flex-wrap:wrap">
                            <a href="{{ route('db-manager.tables.create', $database) }}" style="font-size:.8rem;color:#f97316;text-decoration:none">
                                <i class="fa-solid fa-plus" style="margin-right:.25rem"></i>Create your first table
                            </a>
                            <span style="color:#e2e8f0">|</span>
                            <form id="empty-import-form" action="{{ route('db-manager.databases.import', $database) }}" method="POST" enctype="multipart/form-data" style="display:inline">
                                @csrf
                                <label style="font-size:.8rem;color:#64748b;cursor:pointer;display:inline-flex;align-items:center;gap:.3rem">
                                    <i class="fa-solid fa-upload"></i>Import SQL file
                                    <input type="file" name="sql_file" accept=".sql" style="display:none"
                                           onchange="document.getElementById('empty-import-form').submit()">
                                </label>
                            </form>
                        </div>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
