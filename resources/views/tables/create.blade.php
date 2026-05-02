@extends('db-manager::layout')
@section('title', "Create Table — $database")

@section('breadcrumb')
<a href="{{ route('db-manager.home') }}">Home</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('db-manager.tables.index', $database) }}">{{ $database }}</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span class="current">New Table</span>
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
    <div>
        <h1 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin:0;display:flex;align-items:center;gap:.5rem">
            <i class="fa-solid fa-table" style="color:#f97316"></i> Create Table
        </h1>
        <p style="font-size:.8rem;color:#64748b;margin:.2rem 0 0">in database <strong>{{ $database }}</strong></p>
    </div>
    <a href="{{ route('db-manager.tables.index', $database) }}" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>

<form action="{{ route('db-manager.tables.store', $database) }}" method="POST">
    @csrf

    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <h2 style="font-size:.875rem;font-weight:600;color:#374151;margin:0 0 1rem;display:flex;align-items:center;gap:.5rem">
            <i class="fa-solid fa-gear" style="color:#f97316"></i> Table Settings
        </h2>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:1rem">
            <div style="grid-column:span 2">
                <label class="form-label">Table Name <span style="color:#f87171">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="form-input" placeholder="my_table"
                       pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, underscores">
            </div>
            <div>
                <label class="form-label">Engine</label>
                <select name="engine" class="form-input">
                    <option value="InnoDB" selected>InnoDB</option>
                    <option value="MyISAM">MyISAM</option>
                    <option value="MEMORY">MEMORY</option>
                </select>
            </div>
            <div>
                <label class="form-label">Charset</label>
                <select name="charset" class="form-input">
                    <option value="utf8mb4" selected>utf8mb4</option>
                    <option value="utf8">utf8</option>
                    <option value="latin1">latin1</option>
                </select>
            </div>
            <div>
                <label class="form-label">Collation</label>
                <select name="collation" class="form-input">
                    <option value="utf8mb4_unicode_ci" selected>utf8mb4_unicode_ci</option>
                    <option value="utf8mb4_general_ci">utf8mb4_general_ci</option>
                    <option value="utf8_general_ci">utf8_general_ci</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card" style="overflow:hidden;margin-bottom:1.25rem">
        <div style="padding:.75rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;background:#f8fafc">
            <h2 style="font-size:.875rem;font-weight:600;color:#374151;margin:0;display:flex;align-items:center;gap:.5rem">
                <i class="fa-solid fa-columns" style="color:#f97316"></i> Columns
            </h2>
            <button type="button" id="add-col" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Add Column
            </button>
        </div>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.75rem" id="columns-table">
                <thead style="background:#f8fafc;border-bottom:1px solid #e2e8f0">
                    <tr>
                        <th style="padding:.625rem .75rem;text-align:left;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">Name *</th>
                        <th style="padding:.625rem .75rem;text-align:left;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">Type *</th>
                        <th style="padding:.625rem .75rem;text-align:left;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.07em">Length</th>
                        <th style="padding:.625rem .75rem;text-align:center;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.07em">Unsigned</th>
                        <th style="padding:.625rem .75rem;text-align:center;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.07em">Not Null</th>
                        <th style="padding:.625rem .75rem;text-align:center;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.07em">AI</th>
                        <th style="padding:.625rem .75rem;text-align:center;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.07em">PK</th>
                        <th style="padding:.625rem .75rem;text-align:left;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.07em">Default</th>
                        <th style="padding:.625rem .75rem;text-align:left;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.07em">Comment</th>
                        <th style="padding:.625rem .75rem"></th>
                    </tr>
                </thead>
                <tbody id="columns-body"></tbody>
            </table>
        </div>
        <div id="no-cols" style="display:none;padding:1.5rem;text-align:center;color:#94a3b8;font-size:.875rem">
            No columns added yet — click "Add Column"
        </div>
    </div>

    <div style="display:flex;gap:.75rem">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-check"></i> Create Table
        </button>
        <a href="{{ route('db-manager.tables.index', $database) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
const TYPES = ['INT','BIGINT','SMALLINT','TINYINT','MEDIUMINT','VARCHAR','CHAR','TEXT','MEDIUMTEXT','LONGTEXT','TINYTEXT','DECIMAL','FLOAT','DOUBLE','DATE','DATETIME','TIMESTAMP','TIME','YEAR','BOOLEAN','TINYINT(1)','ENUM','SET','JSON','BLOB','MEDIUMBLOB','LONGBLOB'];
const NO_LENGTH = ['TEXT','MEDIUMTEXT','LONGTEXT','TINYTEXT','DATE','DATETIME','TIMESTAMP','TIME','YEAR','BOOLEAN','JSON','BLOB','MEDIUMBLOB','LONGBLOB'];

let colIndex = 0;

function addColumn(cfg = {}) {
    const i    = colIndex++;
    const type = cfg.type || 'VARCHAR';
    const len  = cfg.len || (type === 'VARCHAR' ? '255' : '');
    const noLen = NO_LENGTH.includes(type);

    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid #f1f5f9';
    tr.innerHTML = `
        <td style="padding:.375rem .5rem">
            <input type="text" name="columns[${i}][name]" value="${cfg.name||''}" required
                   style="border:1px solid #e2e8f0;border-radius:.375rem;padding:.375rem .5rem;width:120px;font-size:.75rem;outline:none"
                   placeholder="col_name"
                   onfocus="this.style.borderColor='#f97316'" onblur="this.style.borderColor='#e2e8f0'">
        </td>
        <td style="padding:.375rem .5rem">
            <select name="columns[${i}][type]" onchange="onTypeChange(this,${i})"
                    style="border:1px solid #e2e8f0;border-radius:.375rem;padding:.375rem .5rem;font-size:.75rem;outline:none;cursor:pointer">
                ${TYPES.map(t=>`<option value="${t}"${t===type?' selected':''}>${t}</option>`).join('')}
            </select>
        </td>
        <td style="padding:.375rem .5rem">
            <input type="text" name="columns[${i}][length]" value="${len}" id="len-${i}"
                   style="border:1px solid #e2e8f0;border-radius:.375rem;padding:.375rem .5rem;width:72px;font-size:.75rem;outline:none"
                   ${noLen?'disabled placeholder="—"':'placeholder="255"'}
                   onfocus="this.style.borderColor='#f97316'" onblur="this.style.borderColor='#e2e8f0'">
        </td>
        <td style="padding:.375rem .5rem;text-align:center"><input type="checkbox" name="columns[${i}][unsigned]" value="1" style="accent-color:#f97316" ${cfg.unsigned?'checked':''}></td>
        <td style="padding:.375rem .5rem;text-align:center"><input type="checkbox" name="columns[${i}][notnull]" value="1" style="accent-color:#f97316" ${cfg.notnull!==false?'checked':''}></td>
        <td style="padding:.375rem .5rem;text-align:center"><input type="checkbox" name="columns[${i}][auto_increment]" value="1" style="accent-color:#f97316" ${cfg.ai?'checked':''}></td>
        <td style="padding:.375rem .5rem;text-align:center"><input type="checkbox" name="columns[${i}][primary]" value="1" style="accent-color:#f97316" ${cfg.pk?'checked':''}></td>
        <td style="padding:.375rem .5rem">
            <input type="text" name="columns[${i}][default]" value="${cfg.def||''}"
                   style="border:1px solid #e2e8f0;border-radius:.375rem;padding:.375rem .5rem;width:88px;font-size:.75rem;outline:none"
                   placeholder="NULL"
                   onfocus="this.style.borderColor='#f97316'" onblur="this.style.borderColor='#e2e8f0'">
        </td>
        <td style="padding:.375rem .5rem">
            <input type="text" name="columns[${i}][comment]" value="${cfg.comment||''}"
                   style="border:1px solid #e2e8f0;border-radius:.375rem;padding:.375rem .5rem;width:104px;font-size:.75rem;outline:none"
                   placeholder="Optional"
                   onfocus="this.style.borderColor='#f97316'" onblur="this.style.borderColor='#e2e8f0'">
        </td>
        <td style="padding:.375rem .5rem">
            <button type="button" onclick="removeColumn(this)"
                    style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:.375rem;background:#fef2f2;color:#dc2626;border:none;cursor:pointer;font-size:.75rem"
                    onmouseover="this.style.background='#dc2626';this.style.color='#fff'"
                    onmouseout="this.style.background='#fef2f2';this.style.color='#dc2626'">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </td>
    `;
    document.getElementById('columns-body').appendChild(tr);
    checkEmpty();
}

function removeColumn(btn) { btn.closest('tr').remove(); checkEmpty(); }

function checkEmpty() {
    const hasRows = document.getElementById('columns-body').children.length > 0;
    document.getElementById('no-cols').style.display  = hasRows ? 'none' : 'block';
}

function onTypeChange(sel, i) {
    const noLen = NO_LENGTH.includes(sel.value);
    const inp   = document.getElementById('len-' + i);
    inp.disabled = noLen;
    inp.placeholder = noLen ? '—' : '255';
    if (noLen) inp.value = '';
    else if (!inp.value) inp.value = sel.value === 'VARCHAR' ? '255' : '';
}

document.getElementById('add-col').addEventListener('click', () => addColumn());

addColumn({ name:'id',         type:'INT',       len:'11',  unsigned:true, notnull:true, ai:true, pk:true });
addColumn({ name:'created_at', type:'TIMESTAMP',  notnull:false, def:'CURRENT_TIMESTAMP' });
addColumn({ name:'updated_at', type:'TIMESTAMP',  notnull:false, def:'CURRENT_TIMESTAMP' });
</script>
@endpush
