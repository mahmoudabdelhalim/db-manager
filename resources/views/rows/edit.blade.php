@extends('db-manager::layout')
@section('title', "Edit Row — $table")

@section('breadcrumb')
<a href="{{ route('db-manager.home') }}">Home</a><span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('db-manager.tables.index', $database) }}">{{ $database }}</a><span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('db-manager.tables.browse', [$database, $table]) }}">{{ $table }}</a><span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span class="current">Edit Row</span>
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <div>
        <h1 style="font-size:1.125rem;font-weight:700;color:#0f172a;margin:0;display:flex;align-items:center;gap:.5rem">
            <i class="fa-solid fa-pen-to-square" style="color:#3b82f6"></i> Edit Row
        </h1>
        <p style="font-size:.8rem;color:#64748b;margin:.2rem 0 0">{{ $database }}.{{ $table }}</p>
    </div>
    <a href="{{ route('db-manager.tables.browse', [$database, $table]) }}" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back to Browse
    </a>
</div>

<form action="{{ route('db-manager.rows.update', [$database, $table]) }}" method="POST" novalidate>
    @csrf @method('PUT')
    @foreach($where as $k => $v)
        <input type="hidden" name="_where[{{ $k }}]" value="{{ $v }}">
    @endforeach

    <div class="card" style="overflow:hidden">
        <div style="display:grid;grid-template-columns:220px 1fr;padding:.625rem 1.5rem;background:#f8fafc;border-bottom:1px solid #e2e8f0">
            <span style="font-size:.65rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.07em">Column</span>
            <span style="font-size:.65rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.07em">Value</span>
        </div>

        @foreach($columns as $col)
        @php
            $type       = strtolower($col['Type']);
            $isNull     = $col['Null'] === 'YES';
            $isPK       = $col['Key'] === 'PRI';
            $isAI       = str_contains(strtolower($col['Extra']), 'auto_increment');
            $isText     = preg_match('/^(text|mediumtext|longtext|tinytext)/', $type);
            $isInt      = preg_match('/^(int|bigint|smallint|tinyint|mediumint)/', $type);
            $isDecimal  = preg_match('/^(decimal|float|double|numeric)/', $type);
            $isDate     = preg_match('/^(datetime|timestamp)/', $type);
            $isDateOnly = $type === 'date';
            $hasError   = $errors->has($col['Field']);
            $curVal     = old($col['Field'], $row[$col['Field']] ?? '');
            $rowBg      = $isPK ? 'background:#fffbeb' : '';
        @endphp
        <div class="row-form-row {{ $hasError ? 'error' : '' }}" style="{{ $rowBg }}">
            <div class="field-meta">
                <div class="field-name">{{ $col['Field'] }}</div>
                <div class="field-badges">
                    <code style="font-size:.65rem;background:#eff6ff;color:#2563eb;padding:.1rem .375rem;border-radius:.3rem;font-family:monospace">{{ $col['Type'] }}</code>
                    @if($isPK) <span class="badge badge-pk">PK</span> @endif
                    @if($isAI) <span class="badge badge-ai">AUTO_INCREMENT</span> @endif
                    @if($isNull) <span class="badge badge-null">nullable</span> @endif
                </div>
            </div>
            <div>
                @if($isText)
                <textarea name="{{ $col['Field'] }}" rows="3"
                          class="form-input {{ $hasError ? 'error' : '' }}"
                          style="font-family:'JetBrains Mono','Fira Code',monospace;font-size:.8125rem{{ $isAI ? ';background:#f8fafc;color:#94a3b8' : '' }}"
                          {{ $isAI ? 'readonly' : '' }}>{{ $curVal }}</textarea>
                @elseif($isDate)
                <input type="text" name="{{ $col['Field'] }}" value="{{ $curVal }}"
                       class="form-input {{ $hasError ? 'error' : '' }}"
                       style="font-family:monospace{{ $isAI ? ';background:#f8fafc;color:#94a3b8' : '' }}"
                       placeholder="YYYY-MM-DD HH:MM:SS" {{ $isAI ? 'readonly' : '' }}>
                @elseif($isDateOnly)
                <input type="date" name="{{ $col['Field'] }}" value="{{ $curVal }}"
                       class="form-input {{ $hasError ? 'error' : '' }}"
                       {{ $isAI ? 'readonly' : '' }}>
                @elseif($isInt)
                <input type="number" name="{{ $col['Field'] }}" value="{{ $curVal }}"
                       class="form-input {{ $hasError ? 'error' : '' }}"
                       style="font-family:monospace{{ $isAI ? ';background:#f8fafc;color:#94a3b8;cursor:not-allowed' : '' }}"
                       step="1" {{ $isAI ? 'readonly' : '' }}>
                @elseif($isDecimal)
                <input type="number" name="{{ $col['Field'] }}" value="{{ $curVal }}"
                       class="form-input {{ $hasError ? 'error' : '' }}"
                       style="font-family:monospace" step="any">
                @else
                <input type="text" name="{{ $col['Field'] }}" value="{{ $curVal }}"
                       class="form-input {{ $hasError ? 'error' : '' }}"
                       style="{{ $isAI ? 'background:#f8fafc;color:#94a3b8;cursor:not-allowed' : '' }}"
                       {{ $isAI ? 'readonly' : '' }}>
                @endif

                @error($col['Field'])
                <div class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                @enderror
                @if($isAI)
                <div class="field-hint"><i class="fa-solid fa-lock" style="font-size:.65rem;margin-right:.2rem"></i>Auto-increment — cannot be edited</div>
                @endif
            </div>
        </div>
        @endforeach

        <div style="display:flex;align-items:center;gap:.75rem;padding:1rem 1.5rem;background:#f8fafc;border-top:1px solid #e2e8f0">
            <button type="submit" class="btn btn-blue">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
            <a href="{{ route('db-manager.tables.browse', [$database, $table]) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </div>
</form>
@endsection
