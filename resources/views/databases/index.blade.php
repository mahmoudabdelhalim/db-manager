@extends('db-manager::layout')
@section('title', 'Databases — DB Manager')

@section('breadcrumb')
<a href="{{ route('db-manager.home') }}">Home</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span class="current">Databases</span>
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <div>
        <h1 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin:0">Databases</h1>
        <p style="font-size:.8rem;color:#64748b;margin:.2rem 0 0">{{ count($databases) }} databases on this server</p>
    </div>
    <div style="display:flex;gap:.5rem">
        <button onclick="document.getElementById('import-modal').style.display='flex'" class="btn btn-secondary">
            <i class="fa-solid fa-upload"></i> Import
        </button>
        <button onclick="document.getElementById('create-db-modal').style.display='flex'" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> New Database
        </button>
    </div>
</div>

<div class="card" style="overflow:hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Database</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($databases as $dbName)
            <tr>
                <td>
                    <a href="{{ route('db-manager.tables.index', $dbName) }}"
                       style="font-weight:600;color:#f97316;text-decoration:none;display:flex;align-items:center;gap:.5rem">
                        <i class="fa-solid fa-database" style="font-size:.7rem;color:#cbd5e1"></i>
                        {{ $dbName }}
                    </a>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:.375rem;flex-wrap:wrap">
                        <a href="{{ route('db-manager.tables.index', $dbName) }}" class="table-action ta-blue">
                            <i class="fa-solid fa-table-cells" style="margin-right:.25rem"></i>Tables
                        </a>
                        <a href="{{ route('db-manager.query.index', ['db' => $dbName]) }}" class="table-action ta-purple">
                            <i class="fa-solid fa-terminal" style="margin-right:.25rem"></i>Query
                        </a>
                        <a href="{{ route('db-manager.databases.export', $dbName) }}" class="table-action ta-green">
                            <i class="fa-solid fa-download" style="margin-right:.25rem"></i>Export
                        </a>
                        <form action="{{ route('db-manager.databases.import', $dbName) }}" method="POST"
                              enctype="multipart/form-data" style="margin:0">
                            @csrf
                            <label class="table-action ta-slate" style="cursor:pointer">
                                <i class="fa-solid fa-upload" style="margin-right:.25rem"></i>Import
                                <input type="file" name="sql_file" accept=".sql,.txt"
                                       style="display:none" onchange="this.form.submit()">
                            </label>
                        </form>
                        <form action="{{ route('db-manager.databases.destroy', $dbName) }}" method="POST" style="margin:0"
                              onsubmit="return confirm('Drop database ' + {{ json_encode($dbName) }} + '?\n\nAll tables and data will be permanently deleted.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="table-action ta-red">
                                <i class="fa-solid fa-trash" style="margin-right:.25rem"></i>Drop
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Create Database Modal --}}
<div id="create-db-modal" class="modal-backdrop" style="display:none">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h2>Create New Database</h2>
                <p>Add a new MySQL database to the server</p>
            </div>
            <button class="modal-close" onclick="document.getElementById('create-db-modal').style.display='none'">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form action="{{ route('db-manager.databases.store') }}" method="POST">
            @csrf
            <div class="modal-body" style="display:flex;flex-direction:column;gap:1rem">
                <div>
                    <label class="form-label">Database Name <span style="color:#f87171">*</span></label>
                    <input type="text" name="name" class="form-input" required
                           placeholder="e.g. my_database"
                           pattern="[a-zA-Z0-9_]+" title="Only letters, numbers and underscores">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div>
                        <label class="form-label">Character Set</label>
                        <select name="charset" class="form-input">
                            <option value="utf8mb4" selected>utf8mb4</option>
                            <option value="utf8">utf8</option>
                            <option value="latin1">latin1</option>
                            <option value="ascii">ascii</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Collation</label>
                        <select name="collation" class="form-input">
                            <option value="utf8mb4_unicode_ci" selected>utf8mb4_unicode_ci</option>
                            <option value="utf8mb4_general_ci">utf8mb4_general_ci</option>
                            <option value="utf8_general_ci">utf8_general_ci</option>
                            <option value="latin1_swedish_ci">latin1_swedish_ci</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('create-db-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Create Database
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Import SQL Modal --}}
<div id="import-modal" class="modal-backdrop" style="display:none">
    <div class="modal-box" style="max-width:520px">
        <div class="modal-header">
            <div>
                <h2>Import SQL File</h2>
                <p>Click "Choose file" next to the target database</p>
            </div>
            <button class="modal-close" onclick="document.getElementById('import-modal').style.display='none'">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body" style="padding:0;max-height:60vh;overflow-y:auto">
            @foreach($databases as $dbName)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;border-bottom:1px solid #f1f5f9">
                <div style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;font-weight:600;color:#1e293b">
                    <i class="fa-solid fa-database" style="color:#f97316;font-size:.7rem"></i>
                    {{ $dbName }}
                </div>
                <form action="{{ route('db-manager.databases.import', $dbName) }}" method="POST"
                      enctype="multipart/form-data" style="margin:0">
                    @csrf
                    <label class="btn btn-secondary btn-sm" style="cursor:pointer;margin:0">
                        <i class="fa-solid fa-upload"></i> Choose file…
                        <input type="file" name="sql_file" accept=".sql,.txt"
                               style="display:none" onchange="this.form.submit()">
                    </label>
                </form>
            </div>
            @endforeach
        </div>
        <div class="modal-footer" style="justify-content:flex-start">
            <p style="font-size:.72rem;color:#94a3b8;margin:0">
                <i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;margin-right:.25rem"></i>
                All statements in the file will be executed immediately.
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
['create-db-modal','import-modal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
@endpush
