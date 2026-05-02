@extends('db-manager::layout')
@section('title', "$table — Structure")

@section('breadcrumb')
<a href="{{ route('db-manager.home') }}">Home</a><span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('db-manager.tables.index', $database) }}">{{ $database }}</a><span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('db-manager.tables.browse', [$database, $table]) }}">{{ $table }}</a><span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span class="current">Structure</span>
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem">
    <div>
        <h1 style="font-size:1.125rem;font-weight:700;color:#0f172a;margin:0">
            <i class="fa-solid fa-sitemap" style="color:#f97316;margin-right:.375rem"></i>
            <span style="color:#64748b">{{ $database }}</span><span style="color:#cbd5e1">.</span><span style="color:#f97316">{{ $table }}</span>
        </h1>
        <p style="font-size:.75rem;color:#64748b;margin:.2rem 0 0">
            {{ count($columns) }} columns &bull; {{ count($indexes) }} indexes &bull; {{ count($foreignKeys) }} foreign keys
        </p>
    </div>
    <div style="display:flex;gap:.5rem">
        <a href="{{ route('db-manager.tables.browse', [$database, $table]) }}" class="btn btn-blue btn-sm">
            <i class="fa-solid fa-eye"></i> Browse
        </a>
        <a href="{{ route('db-manager.rows.create', [$database, $table]) }}" class="btn btn-success btn-sm">
            <i class="fa-solid fa-plus"></i> Insert Row
        </a>
    </div>
</div>

{{-- Columns --}}
<div class="card" style="overflow:hidden;margin-bottom:1rem">
    <div style="display:flex;align-items:center;gap:.5rem;padding:.75rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0">
        <i class="fa-solid fa-table-columns" style="color:#f97316;font-size:.8rem"></i>
        <span style="font-weight:700;color:#374151;font-size:.875rem">Columns</span>
        <span style="font-size:.7rem;background:#e2e8f0;color:#475569;padding:.15rem .5rem;border-radius:1rem;font-weight:600">{{ count($columns) }}</span>
        <div style="margin-left:auto">
            <button onclick="document.getElementById('add-col-modal').style.display='flex'" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Add Column
            </button>
        </div>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:2rem">#</th>
                    <th>Name</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th><th>Comment</th>
                    <th style="width:9rem">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($columns as $i => $col)
                <tr>
                    <td style="color:#cbd5e1;font-family:monospace;font-size:.72rem">{{ $i+1 }}</td>
                    <td style="font-weight:600;color:#0f172a">{{ $col['Field'] }}</td>
                    <td><code style="font-size:.72rem;background:#eff6ff;color:#2563eb;padding:.15rem .4rem;border-radius:.3rem;font-family:monospace">{{ $col['Type'] }}</code></td>
                    <td>
                        @if($col['Null']==='YES') <span class="badge badge-null">NULL</span>
                        @else <span class="badge badge-notnull">NOT NULL</span>
                        @endif
                    </td>
                    <td>
                        @if($col['Key']==='PRI') <span class="badge badge-pk">PRI</span>
                        @elseif($col['Key']==='UNI') <span class="badge badge-uni">UNI</span>
                        @elseif($col['Key']==='MUL') <span class="badge badge-mul">MUL</span>
                        @else <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>
                    <td style="font-family:monospace;font-size:.75rem;color:#64748b">{{ $col['Default'] ?? '—' }}</td>
                    <td>
                        @if($col['Extra']) <span class="badge badge-ai" style="font-size:.65rem">{{ $col['Extra'] }}</span>
                        @else <span style="color:#e2e8f0">—</span>
                        @endif
                    </td>
                    <td style="font-size:.75rem;color:#94a3b8">{{ $col['Comment'] ?: '—' }}</td>
                    <td>
                        <div style="display:flex;gap:.25rem">
                            <button class="table-action ta-blue edit-col-btn"
                                    data-col="{{ htmlspecialchars(json_encode($col), ENT_QUOTES, 'UTF-8') }}">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <form action="{{ route('db-manager.columns.destroy', [$database, $table]) }}" method="POST" style="margin:0"
                                  onsubmit="return confirm('Drop column? This cannot be undone.')">
                                @csrf
                                <input type="hidden" name="column_name" value="{{ $col['Field'] }}">
                                <button type="submit" class="table-action ta-red"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Indexes --}}
<div class="card" style="overflow:hidden;margin-bottom:1rem">
    <div style="display:flex;align-items:center;gap:.5rem;padding:.75rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0">
        <i class="fa-solid fa-key" style="color:#f97316;font-size:.8rem"></i>
        <span style="font-weight:700;color:#374151;font-size:.875rem">Indexes</span>
        <span style="font-size:.7rem;background:#e2e8f0;color:#475569;padding:.15rem .5rem;border-radius:1rem;font-weight:600">{{ count($indexes) }}</span>
        <div style="margin-left:auto">
            <button onclick="document.getElementById('add-idx-modal').style.display='flex'" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Add Index
            </button>
        </div>
    </div>
    @if($indexes)
    <table class="data-table">
        <thead>
            <tr><th>Index Name</th><th>Column</th><th>Type</th><th>Unique</th><th style="width:6rem">Actions</th></tr>
        </thead>
        <tbody>
            @foreach($indexes as $idx)
            <tr>
                <td style="font-weight:600">
                    @if($idx['Key_name']==='PRIMARY') <span class="badge badge-pk">PRIMARY</span>
                    @else {{ $idx['Key_name'] }}
                    @endif
                </td>
                <td style="font-family:monospace;font-size:.78rem;color:#475569">{{ $idx['Column_name'] }}</td>
                <td><code style="font-size:.7rem;background:#f1f5f9;color:#475569;padding:.1rem .375rem;border-radius:.3rem">{{ $idx['Index_type'] }}</code></td>
                <td>
                    @if($idx['Non_unique']==0)
                        <span style="color:#059669;font-size:.78rem;font-weight:600"><i class="fa-solid fa-check" style="margin-right:.2rem"></i>Yes</span>
                    @else
                        <span style="color:#94a3b8;font-size:.78rem">No</span>
                    @endif
                </td>
                <td>
                    <form action="{{ route('db-manager.indexes.destroy', [$database, $table]) }}" method="POST" style="margin:0"
                          onsubmit="return confirm('Drop this index?')">
                        @csrf
                        <input type="hidden" name="index_name" value="{{ $idx['Key_name'] }}">
                        <button type="submit" class="table-action ta-red"><i class="fa-solid fa-trash"></i> Drop</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="empty-state" style="padding:1.5rem">
        <i class="fa-solid fa-key" style="font-size:1.5rem;color:#cbd5e1;display:block;margin-bottom:.5rem"></i>
        <p style="margin:0;color:#94a3b8;font-size:.85rem">No indexes defined</p>
    </div>
    @endif
</div>

{{-- Foreign Keys --}}
<div class="card" style="overflow:hidden;margin-bottom:1rem">
    <div style="display:flex;align-items:center;gap:.5rem;padding:.75rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0">
        <i class="fa-solid fa-link" style="color:#f97316;font-size:.8rem"></i>
        <span style="font-weight:700;color:#374151;font-size:.875rem">Foreign Keys / Relations</span>
        <span style="font-size:.7rem;background:#e2e8f0;color:#475569;padding:.15rem .5rem;border-radius:1rem;font-weight:600">{{ count($foreignKeys) }}</span>
        <div style="margin-left:auto">
            <button onclick="document.getElementById('add-fk-modal').style.display='flex'" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Add Relation
            </button>
        </div>
    </div>
    @if($foreignKeys)
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr><th>Constraint</th><th>Column</th><th>References</th><th>On Delete</th><th>On Update</th><th style="width:6rem">Actions</th></tr>
            </thead>
            <tbody>
                @foreach($foreignKeys as $fk)
                <tr>
                    <td style="font-weight:600;font-size:.8rem;font-family:monospace;color:#475569">{{ $fk['CONSTRAINT_NAME'] }}</td>
                    <td style="font-family:monospace;font-size:.78rem;color:#0f172a;font-weight:600">{{ $fk['COLUMN_NAME'] }}</td>
                    <td style="font-size:.78rem">
                        <span style="color:#64748b">{{ $fk['REFERENCED_TABLE_SCHEMA'] }}</span>.<span style="font-weight:700;color:#f97316">{{ $fk['REFERENCED_TABLE_NAME'] }}</span>(<span style="font-family:monospace">{{ $fk['REFERENCED_COLUMN_NAME'] }}</span>)
                    </td>
                    <td><code style="font-size:.7rem;background:#fef3c7;color:#92400e;padding:.1rem .375rem;border-radius:.3rem">{{ $fk['DELETE_RULE'] }}</code></td>
                    <td><code style="font-size:.7rem;background:#ede9fe;color:#5b21b6;padding:.1rem .375rem;border-radius:.3rem">{{ $fk['UPDATE_RULE'] }}</code></td>
                    <td>
                        <form action="{{ route('db-manager.fk.destroy', [$database, $table]) }}" method="POST" style="margin:0"
                              onsubmit="return confirm('Drop this foreign key?')">
                            @csrf
                            <input type="hidden" name="fk_name" value="{{ $fk['CONSTRAINT_NAME'] }}">
                            <button type="submit" class="table-action ta-red"><i class="fa-solid fa-trash"></i> Drop</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="empty-state" style="padding:1.5rem">
        <i class="fa-solid fa-link" style="font-size:1.5rem;color:#cbd5e1;display:block;margin-bottom:.5rem"></i>
        <p style="margin:0;color:#94a3b8;font-size:.85rem">No foreign keys defined</p>
    </div>
    @endif
</div>

{{-- CREATE TABLE --}}
<div class="card" style="overflow:hidden">
    <div style="display:flex;align-items:center;gap:.5rem;padding:.75rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0">
        <i class="fa-solid fa-code" style="color:#f97316;font-size:.8rem"></i>
        <span style="font-weight:700;color:#374151;font-size:.875rem">CREATE TABLE Statement</span>
    </div>
    <div style="padding:1rem 1.25rem">
        <pre class="code-block">{{ $create }}</pre>
    </div>
</div>

{{-- Add Column Modal --}}
<div id="add-col-modal" class="modal-backdrop" style="display:none">
    <div class="modal-box" style="max-width:560px">
        <div class="modal-header">
            <div><h2>Add Column</h2><p>New column for <strong>{{ $table }}</strong></p></div>
            <button class="modal-close" onclick="document.getElementById('add-col-modal').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('db-manager.columns.store', [$database, $table]) }}" method="POST">
            @csrf
            <div class="modal-body" style="display:flex;flex-direction:column;gap:.875rem">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div><label class="form-label">Column Name <span style="color:#f87171">*</span></label>
                        <input type="text" name="name" class="form-input" required placeholder="e.g. user_id"></div>
                    <div><label class="form-label">Type <span style="color:#f87171">*</span></label>
                        <input type="text" name="type" class="form-input" required placeholder="e.g. varchar(255)" list="sql-types"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div><label class="form-label">Default Value</label>
                        <input type="text" name="default" class="form-input" placeholder="Leave blank for none"></div>
                    <div><label class="form-label">After Column</label>
                        <select name="after" class="form-input">
                            <option value="">— Append at end —</option>
                            @foreach($columns as $c)<option value="{{ $c['Field'] }}">{{ $c['Field'] }}</option>@endforeach
                        </select></div>
                </div>
                <div><label class="form-label">Comment</label>
                    <input type="text" name="comment" class="form-input" placeholder="Optional description"></div>
                <div style="display:flex;gap:1.5rem;flex-wrap:wrap">
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.8125rem;color:#374151;cursor:pointer">
                        <input type="checkbox" name="notnull" value="1" style="accent-color:#f97316"> NOT NULL
                    </label>
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.8125rem;color:#374151;cursor:pointer">
                        <input type="checkbox" name="unsigned" value="1" style="accent-color:#f97316"> UNSIGNED
                    </label>
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.8125rem;color:#374151;cursor:pointer">
                        <input type="checkbox" name="auto_increment" value="1" style="accent-color:#f97316"> AUTO_INCREMENT
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-col-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Column</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Column Modal --}}
<div id="edit-col-modal" class="modal-backdrop" style="display:none">
    <div class="modal-box" style="max-width:560px">
        <div class="modal-header">
            <div><h2>Modify Column</h2><p>Edit column in <strong>{{ $table }}</strong></p></div>
            <button class="modal-close" onclick="document.getElementById('edit-col-modal').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('db-manager.columns.update', [$database, $table]) }}" method="POST">
            @csrf
            <input type="hidden" name="old_name" id="edit-old-name">
            <div class="modal-body" style="display:flex;flex-direction:column;gap:.875rem">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div><label class="form-label">Column Name <span style="color:#f87171">*</span></label>
                        <input type="text" name="name" id="edit-name" class="form-input" required></div>
                    <div><label class="form-label">Type <span style="color:#f87171">*</span></label>
                        <input type="text" name="type" id="edit-type" class="form-input" required list="sql-types"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div><label class="form-label">Default Value</label>
                        <input type="text" name="default" id="edit-default" class="form-input" placeholder="Leave blank for none"></div>
                    <div><label class="form-label">Comment</label>
                        <input type="text" name="comment" id="edit-comment" class="form-input"></div>
                </div>
                <div style="display:flex;gap:1.5rem;flex-wrap:wrap">
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.8125rem;color:#374151;cursor:pointer">
                        <input type="checkbox" name="notnull" id="edit-notnull" value="1" style="accent-color:#f97316"> NOT NULL
                    </label>
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.8125rem;color:#374151;cursor:pointer">
                        <input type="checkbox" name="unsigned" id="edit-unsigned" value="1" style="accent-color:#f97316"> UNSIGNED
                    </label>
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.8125rem;color:#374151;cursor:pointer">
                        <input type="checkbox" name="auto_increment" id="edit-ai" value="1" style="accent-color:#f97316"> AUTO_INCREMENT
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-col-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Index Modal --}}
<div id="add-idx-modal" class="modal-backdrop" style="display:none">
    <div class="modal-box" style="max-width:460px">
        <div class="modal-header">
            <div><h2>Add Index</h2><p>New index on <strong>{{ $table }}</strong></p></div>
            <button class="modal-close" onclick="document.getElementById('add-idx-modal').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('db-manager.indexes.store', [$database, $table]) }}" method="POST">
            @csrf
            <div class="modal-body" style="display:flex;flex-direction:column;gap:.875rem">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div><label class="form-label">Index Name <span style="color:#f87171">*</span></label>
                        <input type="text" name="index_name" class="form-input" required placeholder="e.g. idx_email"></div>
                    <div><label class="form-label">Type</label>
                        <select name="index_type" class="form-input">
                            <option value="">Regular (INDEX)</option>
                            <option value="UNIQUE">UNIQUE</option>
                            <option value="FULLTEXT">FULLTEXT</option>
                        </select></div>
                </div>
                <div>
                    <label class="form-label">Columns <span style="color:#f87171">*</span></label>
                    <div style="border:1px solid #e2e8f0;border-radius:.5rem;padding:.625rem;display:flex;flex-direction:column;gap:.375rem;max-height:180px;overflow-y:auto">
                        @foreach($columns as $c)
                        <label style="display:flex;align-items:center;gap:.5rem;font-size:.8125rem;color:#374151;cursor:pointer;padding:.2rem 0">
                            <input type="checkbox" name="index_columns[]" value="{{ $c['Field'] }}" style="accent-color:#f97316">
                            <span style="font-weight:500">{{ $c['Field'] }}</span>
                            <code style="font-size:.68rem;color:#94a3b8;margin-left:auto">{{ $c['Type'] }}</code>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-idx-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Index</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Foreign Key Modal --}}
<div id="add-fk-modal" class="modal-backdrop" style="display:none">
    <div class="modal-box" style="max-width:540px">
        <div class="modal-header">
            <div><h2>Add Foreign Key</h2><p>Define a relation from <strong>{{ $table }}</strong></p></div>
            <button class="modal-close" onclick="document.getElementById('add-fk-modal').style.display='none'"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('db-manager.fk.store', [$database, $table]) }}" method="POST">
            @csrf
            <div class="modal-body" style="display:flex;flex-direction:column;gap:.875rem">
                <div><label class="form-label">Constraint Name <span style="color:#f87171">*</span></label>
                    <input type="text" name="name" class="form-input" required placeholder="e.g. fk_orders_user_id"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div><label class="form-label">Local Column <span style="color:#f87171">*</span></label>
                        <select name="column" class="form-input" required>
                            @foreach($columns as $c)<option value="{{ $c['Field'] }}">{{ $c['Field'] }}</option>@endforeach
                        </select></div>
                    <div><label class="form-label">Ref Database</label>
                        <input type="text" name="ref_database" class="form-input" value="{{ $database }}"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div><label class="form-label">Ref Table <span style="color:#f87171">*</span></label>
                        <input type="text" name="ref_table" class="form-input" required placeholder="e.g. users"></div>
                    <div><label class="form-label">Ref Column <span style="color:#f87171">*</span></label>
                        <input type="text" name="ref_column" class="form-input" required placeholder="e.g. id"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                    <div><label class="form-label">On Delete</label>
                        <select name="on_delete" class="form-input"><option>RESTRICT</option><option>CASCADE</option><option>SET NULL</option><option>NO ACTION</option></select></div>
                    <div><label class="form-label">On Update</label>
                        <select name="on_update" class="form-input"><option>RESTRICT</option><option>CASCADE</option><option>SET NULL</option><option>NO ACTION</option></select></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-fk-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-link"></i> Add Foreign Key</button>
            </div>
        </form>
    </div>
</div>

<datalist id="sql-types">
    <option value="INT"><option value="INT(11)"><option value="BIGINT"><option value="BIGINT UNSIGNED">
    <option value="SMALLINT"><option value="MEDIUMINT"><option value="TINYINT(1)">
    <option value="VARCHAR(255)"><option value="VARCHAR(100)"><option value="CHAR(36)">
    <option value="TEXT"><option value="MEDIUMTEXT"><option value="LONGTEXT">
    <option value="DECIMAL(10,2)"><option value="FLOAT"><option value="DOUBLE">
    <option value="DATE"><option value="DATETIME"><option value="TIMESTAMP"><option value="TIME"><option value="YEAR">
    <option value="JSON"><option value="BLOB"><option value="BOOLEAN">
</datalist>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.edit-col-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var col = JSON.parse(btn.dataset.col);
        document.getElementById('edit-old-name').value  = col.Field;
        document.getElementById('edit-name').value      = col.Field;
        document.getElementById('edit-type').value      = col.Type;
        document.getElementById('edit-default').value   = col.Default || '';
        document.getElementById('edit-comment').value   = col.Comment || '';
        document.getElementById('edit-notnull').checked  = col.Null === 'NO';
        document.getElementById('edit-unsigned').checked = col.Type.toLowerCase().indexOf('unsigned') !== -1;
        document.getElementById('edit-ai').checked       = (col.Extra || '').indexOf('auto_increment') !== -1;
        document.getElementById('edit-col-modal').style.display = 'flex';
    });
});

['add-col-modal','edit-col-modal','add-idx-modal','add-fk-modal'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
@endpush
