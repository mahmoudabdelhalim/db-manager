@extends('db-manager::layout')
@section('title', 'SQL Editor — DB Manager')

@section('breadcrumb')
<a href="{{ route('db-manager.home') }}">Home</a>
<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span class="current">SQL Editor</span>
@endsection

@push('head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.css">
<style>
    .CodeMirror          { height:230px; font-size:13px; font-family:'JetBrains Mono','Fira Code','Cascadia Code',monospace; border-radius:0 0 .625rem .625rem; }
    .CodeMirror-scroll   { overflow-y:auto!important; }
    .cm-bar              { background:#282a36; border-radius:.625rem .625rem 0 0; border-bottom:1px solid #44475a; display:flex; align-items:center; gap:.5rem; padding:.5rem .875rem; }
    .cm-bar-dot          { width:10px;height:10px;border-radius:50%; }
    .cm-action-bar       { background:rgba(40,42,54,.7); border-top:1px solid #44475a; display:flex; align-items:center; gap:.75rem; padding:.5rem .875rem; }
    .cm-btn              { display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .625rem;border-radius:.375rem;font-size:.72rem;font-weight:500;border:none;cursor:pointer;transition:all 100ms; }
    .cm-btn-ghost        { background:transparent;color:#6272a4; }
    .cm-btn-ghost:hover  { background:rgba(255,255,255,.06);color:#f8f8f2; }
    .cm-validation       { padding:.5rem .875rem;font-size:.72rem;display:flex;align-items:center;gap:.5rem;border-top:1px solid #44475a; }
    .cm-validation.warn  { background:rgba(255,184,108,.08);color:#ffb86c; }
    .cm-validation.err   { background:rgba(255,85,85,.08);color:#ff5555; }
    .sql-error-box       { background:#fef2f2;border:1px solid #fecaca;border-radius:.625rem;padding:1rem 1.25rem;margin-bottom:1rem; }
    .sql-error-box pre   { background:#fff5f5;border-radius:.375rem;padding:.625rem;font-size:.75rem;color:#991b1b;white-space:pre-wrap;word-break:break-all;margin:.5rem 0 0;line-height:1.5; }
</style>
@endpush

@section('content')
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.25rem;gap:1rem;flex-wrap:wrap">
    <div>
        <h1 style="font-size:1.25rem;font-weight:700;color:#0f172a;margin:0;display:flex;align-items:center;gap:.5rem">
            <i class="fa-solid fa-terminal" style="color:#f97316"></i> SQL Editor
        </h1>
        <p style="font-size:.75rem;color:#64748b;margin:.2rem 0 0">
            Press <kbd style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:.3rem;padding:.1rem .4rem;font-size:.7rem;font-family:monospace">Ctrl+Enter</kbd> to run &bull;
            <kbd style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:.3rem;padding:.1rem .4rem;font-size:.7rem;font-family:monospace">Ctrl+Space</kbd> for autocomplete
        </p>
    </div>
    @if(!empty($database))
    <div style="display:flex;align-items:center;gap:.625rem;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:.5rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem;padding:.375rem .75rem;font-size:.8rem;color:#475569">
            <i class="fa-solid fa-database" style="color:#f97316;font-size:.7rem"></i>
            <span style="font-weight:600;color:#1e293b">{{ $database }}</span>
        </div>
        <a href="{{ route('db-manager.tables.index', $database) }}"
           style="font-size:.78rem;color:#64748b;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;padding:.375rem .625rem;border:1px solid #e2e8f0;border-radius:.5rem;background:#fff;transition:border-color .15s"
           onmouseover="this.style.borderColor='#f97316';this.style.color='#f97316'"
           onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b'">
            <i class="fa-solid fa-arrow-left" style="font-size:.65rem"></i> Tables
        </a>
    </div>
    @endif
</div>

@isset($queryError)
<div class="sql-error-box">
    <div style="display:flex;align-items:center;gap:.5rem;font-weight:700;color:#991b1b;font-size:.875rem">
        <i class="fa-solid fa-circle-exclamation"></i> Query Error
    </div>
    <pre>{{ $queryError }}</pre>
</div>
@endisset

<form action="{{ route('db-manager.query.run') }}" method="POST" id="sql-form">
    @csrf
    <textarea name="sql" id="sql-hidden" style="display:none">{{ $sql ?? '' }}</textarea>
    <input type="hidden" name="database" id="db-hidden" value="{{ $database ?? '' }}">
    <input type="hidden" name="page" id="page-input" value="1">

    <div style="border-radius:.625rem;overflow:hidden;border:1px solid #44475a;margin-bottom:1rem;box-shadow:0 4px 20px rgba(0,0,0,.15)">
        <div class="cm-bar">
            <div style="display:flex;gap:.3rem">
                <div class="cm-bar-dot" style="background:#ff5f57"></div>
                <div class="cm-bar-dot" style="background:#ffbd2e"></div>
                <div class="cm-bar-dot" style="background:#28ca41"></div>
            </div>
            <span style="font-size:.7rem;color:#6272a4;font-family:monospace;margin-left:.5rem">MySQL Query</span>
            <div style="margin-left:auto;display:flex;align-items:center;gap:.375rem">
                <select id="db-select" class="cm-btn cm-btn-ghost"
                        style="background:#44475a;color:#f8f8f2;border:1px solid #6272a4;font-family:inherit;cursor:pointer"
                        onchange="document.getElementById('db-hidden').value=this.value">
                    <option value="">— No database —</option>
                    @foreach($databases as $dbName)
                    <option value="{{ $dbName }}" {{ (isset($database) && $database===$dbName)?'selected':'' }}>{{ $dbName }}</option>
                    @endforeach
                </select>
                <button type="button" class="cm-btn cm-btn-ghost" onclick="editor.setValue('');editor.focus()">
                    <i class="fa-solid fa-eraser"></i> Clear
                </button>
                <button type="button" class="cm-btn cm-btn-ghost" id="format-btn">
                    <i class="fa-solid fa-align-left"></i> Format
                </button>
            </div>
        </div>

        <div id="cm-container"></div>

        <div id="cm-validation" class="cm-validation" style="display:none">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span id="cm-validation-msg"></span>
        </div>

        <div class="cm-action-bar">
            <button type="submit" id="run-btn"
                    style="display:inline-flex;align-items:center;gap:.4rem;padding:.35rem .875rem;border-radius:.5rem;background:#f97316;color:#fff;font-size:.8125rem;font-weight:600;border:none;cursor:pointer;transition:all 120ms;box-shadow:0 1px 4px rgba(249,115,22,.4)">
                <i class="fa-solid fa-play"></i> Run Query
            </button>
            <span style="font-size:.7rem;color:#6272a4">Ctrl+Enter to execute</span>
            <div id="danger-badge" style="display:none;margin-left:auto">
                <span style="font-size:.7rem;font-weight:600;background:rgba(255,85,85,.15);color:#ff5555;border:1px solid rgba(255,85,85,.3);padding:.2rem .625rem;border-radius:.375rem">
                    <i class="fa-solid fa-skull-crossbones"></i> Destructive operation
                </span>
            </div>
        </div>
    </div>
</form>

<div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem">
    <span style="font-size:.72rem;color:#94a3b8;font-weight:600">Quick:</span>
    @php $sqlTemplates = ['SELECT * FROM ', 'SHOW TABLES', 'SHOW COLUMNS FROM ', 'DESCRIBE ', 'SHOW CREATE TABLE ', 'SHOW STATUS', "SHOW VARIABLES LIKE '%version%'"]; @endphp
    @foreach($sqlTemplates as $tpl)
    <button type="button" onclick="insertTemplate({{ json_encode($tpl) }})"
            style="font-size:.72rem;background:#fff;border:1px solid #e2e8f0;color:#475569;padding:.25rem .625rem;border-radius:.4rem;font-family:monospace;cursor:pointer;transition:all 100ms"
            onmouseover="this.style.borderColor='#f97316';this.style.color='#f97316'"
            onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#475569'">{{ $tpl }}</button>
    @endforeach
</div>

@if(isset($rows))
@php
    $page        = $page ?? 1;
    $perPage     = $perPage ?? 50;
    $paginated   = $paginated ?? false;
    $primaryKeys = $primaryKeys ?? [];
    $tableName   = $tableName ?? null;
    $pkInResults = $tableName && !empty($primaryKeys) && !empty($columns)
                   && count(array_intersect($primaryKeys, $columns)) === count($primaryKeys);
    $hasMore     = $paginated && (isset($totalPages) ? $page < $totalPages : count($rows) >= $perPage);
@endphp
<div class="card" style="overflow:hidden">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0">
        <div style="display:flex;align-items:center;gap:.625rem;font-size:.875rem;font-weight:600;color:#0f172a">
            @if(count($rows) > 0)
            <span style="width:8px;height:8px;background:#10b981;border-radius:50%;display:inline-block"></span>
            @if($paginated && isset($total))
                {{ number_format($total) }} row(s) &middot; Page {{ $page }} of {{ $totalPages }}
            @elseif($paginated)
                Page {{ $page }} &middot; {{ count($rows) }} row(s)
            @else
                {{ number_format(count($rows)) }} row(s) returned
            @endif
            @else
            <span style="width:8px;height:8px;background:#3b82f6;border-radius:50%;display:inline-block"></span>
            Query OK — {{ $affected }} row(s) affected
            @endif
        </div>
        @if(count($rows) > 0)
        <button onclick="exportCsv()" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-download"></i> Export CSV
        </button>
        @endif
    </div>

    @if(count($rows) > 0)
    <div style="overflow-x:auto">
        <table class="data-table" id="results-table">
            <thead>
                <tr>
                    <th style="width:2rem">#</th>
                    @foreach($columns as $col)<th>{{ $col }}</th>@endforeach
                    @if($pkInResults)<th style="width:6rem">Actions</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                <tr>
                    <td style="color:#cbd5e1;font-family:monospace;font-size:.72rem">{{ ($page - 1) * $perPage + $i + 1 }}</td>
                    @foreach($row as $value)
                    <td style="font-family:monospace;font-size:.78rem;max-width:280px">
                        @if($value === null)
                            <span style="color:#94a3b8;font-style:italic;font-family:inherit">NULL</span>
                        @else
                            <span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                  title="{{ $value }}">{{ Str::limit((string)$value, 200) }}</span>
                        @endif
                    </td>
                    @endforeach
                    @if($pkInResults)
                    @php $pkWhere = array_intersect_key($row, array_flip($primaryKeys)); @endphp
                    <td>
                        <div style="display:flex;gap:.3rem">
                            <a href="{{ route('db-manager.rows.edit', [$database, $tableName]) . '?' . http_build_query($pkWhere) }}"
                               class="table-action ta-blue" style="text-decoration:none" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('db-manager.rows.destroy', [$database, $tableName]) }}" method="POST" style="margin:0"
                                  onsubmit="return confirm('Delete this row permanently?')">
                                @csrf @method('DELETE')
                                @foreach($pkWhere as $col => $val)
                                <input type="hidden" name="_where[{{ $col }}]" value="{{ $val }}">
                                @endforeach
                                <button type="submit" class="table-action ta-red" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($paginated && ($page > 1 || $hasMore))
    <div style="display:flex;align-items:center;justify-content:center;gap:.75rem;padding:.75rem 1.25rem;border-top:1px solid #e2e8f0;background:#f8fafc">
        <button onclick="goPage({{ $page - 1 }})" @disabled($page <= 1) class="btn btn-secondary btn-sm"
                style="{{ $page <= 1 ? 'opacity:.4;cursor:default' : '' }}">
            <i class="fa-solid fa-chevron-left"></i> Prev
        </button>
        <span style="font-size:.8rem;color:#475569">
            Page {{ $page }}{{ isset($totalPages) ? ' of ' . $totalPages : '' }}
        </span>
        <button onclick="goPage({{ $page + 1 }})" @disabled(!$hasMore) class="btn btn-secondary btn-sm"
                style="{{ !$hasMore ? 'opacity:.4;cursor:default' : '' }}">
            Next <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>
    @endif

    @else
    <div style="padding:2.5rem;text-align:center">
        <i class="fa-solid fa-circle-check" style="font-size:2rem;color:#10b981;display:block;margin-bottom:.5rem"></i>
        <p style="font-weight:600;color:#1e293b;margin:0">Query executed successfully</p>
        <p style="font-size:.8rem;color:#64748b;margin:.25rem 0 0">{{ $affected }} row(s) affected</p>
    </div>
    @endif
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/sql/sql.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/closebrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/sql-hint.min.js"></script>
<script>
const VALID_STARTS = ['SELECT','INSERT','UPDATE','DELETE','CREATE','DROP','ALTER','SHOW','DESCRIBE','DESC','TRUNCATE','EXPLAIN','SET','USE','GRANT','REVOKE','COMMIT','ROLLBACK','START','BEGIN','REPLACE','CALL','FLUSH','OPTIMIZE','ANALYZE','REPAIR','CHECK'];
const DANGEROUS    = ['DROP','TRUNCATE','DELETE'];

const editor = CodeMirror(document.getElementById('cm-container'), {
    value:             document.getElementById('sql-hidden').value || '',
    mode:              'text/x-mysql',
    theme:             'dracula',
    lineNumbers:       true,
    matchBrackets:     true,
    autoCloseBrackets: true,
    extraKeys: {
        'Ctrl-Enter': submitForm,
        'Cmd-Enter':  submitForm,
        'Ctrl-Space': 'autocomplete',
    },
    hintOptions: { completeSingle: false },
    lineWrapping: true,
    tabSize: 2,
});

editor.on('change', validate);

function getSql() { return editor.getValue().trim(); }

function validate() {
    const sql   = getSql();
    const valEl = document.getElementById('cm-validation');
    const msgEl = document.getElementById('cm-validation-msg');
    const dngEl = document.getElementById('danger-badge');
    const runEl = document.getElementById('run-btn');

    valEl.className   = 'cm-validation';
    valEl.style.display = 'none';
    dngEl.style.display = 'none';
    runEl.disabled = false;

    if (!sql) return;

    const upper = sql.toUpperCase().replace(/\s+/g, ' ').trim();
    const first = upper.split(/[\s(]/)[0];

    if (!VALID_STARTS.includes(first)) {
        valEl.className   = 'cm-validation err';
        valEl.style.display = 'flex';
        msgEl.textContent = `Unknown statement "${first}" — SQL must begin with a valid keyword.`;
        runEl.disabled = true;
        return;
    }

    if (DANGEROUS.includes(first)) dngEl.style.display = 'block';

    if (first === 'DELETE' && !upper.includes('WHERE')) {
        valEl.className   = 'cm-validation warn';
        valEl.style.display = 'flex';
        msgEl.textContent = 'DELETE without WHERE will remove ALL rows — are you sure?';
    }
}

function submitForm() {
    const sql = getSql();
    if (!sql) { showEmptyError(); return; }
    document.getElementById('sql-hidden').value = sql;
    document.getElementById('page-input').value = 1;
    document.getElementById('sql-form').submit();
}

function goPage(p) {
    document.getElementById('sql-hidden').value = getSql();
    document.getElementById('page-input').value = p;
    document.getElementById('sql-form').submit();
}

function showEmptyError() {
    const v = document.getElementById('cm-validation');
    v.className   = 'cm-validation err';
    v.style.display = 'flex';
    document.getElementById('cm-validation-msg').textContent = 'SQL query cannot be empty.';
}

document.getElementById('sql-form').addEventListener('submit', function(e) {
    const sql = getSql();
    if (!sql) { e.preventDefault(); showEmptyError(); return; }
    document.getElementById('sql-hidden').value = sql;
});

document.getElementById('format-btn').addEventListener('click', () => {
    const keywords = ['SELECT','FROM','WHERE','JOIN','LEFT JOIN','RIGHT JOIN','INNER JOIN','ON','GROUP BY','ORDER BY','HAVING','LIMIT','INSERT INTO','VALUES','UPDATE','SET','DELETE FROM','CREATE TABLE','CREATE DATABASE','DROP TABLE','ALTER TABLE','ADD COLUMN','MODIFY COLUMN'];
    let sql = getSql();
    keywords.forEach(kw => {
        const re = new RegExp('\\b' + kw + '\\b', 'gi');
        sql = sql.replace(re, '\n' + kw.toUpperCase());
    });
    sql = sql.replace(/,\s*/g, ',\n  ').replace(/^\n/, '').replace(/\n{3,}/g, '\n\n').trim();
    editor.setValue(sql);
});

function insertTemplate(tpl) {
    editor.replaceRange(tpl, editor.getCursor());
    editor.focus();
}

function exportCsv() {
    const t = document.getElementById('results-table');
    if (!t) return;
    const rows = Array.from(t.querySelectorAll('tr')).map(tr =>
        Array.from(tr.querySelectorAll('th,td')).slice(1).map(td => {
            let v = td.textContent.trim();
            if (/^[=+\-@\t\r]/.test(v)) v = '‌' + v;
            return /[,"\n\r]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v;
        }).join(',')
    );
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([rows.join('\n')], {type:'text/csv'}));
    a.download = 'query_result.csv';
    a.click();
}

validate();
editor.focus();
</script>
@endpush
