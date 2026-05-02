@extends('db-manager::layout')
@section('title', "Insert Row — $table")

@section('breadcrumb')
<a href="{{ route('db-manager.home') }}">Home</a><span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('db-manager.tables.index', $database) }}">{{ $database }}</a><span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('db-manager.tables.browse', [$database, $table]) }}">{{ $table }}</a><span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span class="current">Insert</span>
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <div>
        <h1 style="font-size:1.125rem;font-weight:700;color:#0f172a;margin:0;display:flex;align-items:center;gap:.5rem">
            <i class="fa-solid fa-plus-circle" style="color:#10b981"></i> Insert Row
        </h1>
        <p style="font-size:.8rem;color:#64748b;margin:.2rem 0 0">{{ $database }}.{{ $table }}</p>
    </div>
    <a href="{{ route('db-manager.tables.browse', [$database, $table]) }}" class="btn btn-secondary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back to Browse
    </a>
</div>

<form action="{{ route('db-manager.rows.store', [$database, $table]) }}" method="POST" novalidate>
    @csrf
    <div class="card" style="overflow:hidden">
        <div style="display:grid;grid-template-columns:220px 1fr;padding:.625rem 1.5rem;background:#f8fafc;border-bottom:1px solid #e2e8f0">
            <span style="font-size:.65rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.07em">Column</span>
            <span style="font-size:.65rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.07em">Value</span>
        </div>

        @foreach($columns as $col)
        @php
            $isAI      = str_contains(strtolower($col['Extra']), 'auto_increment');
            $isNull    = $col['Null'] === 'YES';
            $type      = strtolower($col['Type']);
            $isText    = preg_match('/^(text|mediumtext|longtext|tinytext)/', $type);
            $isInt     = preg_match('/^(int|bigint|smallint|tinyint|mediumint)/', $type);
            $isDecimal = preg_match('/^(decimal|float|double|numeric)/', $type);
            $isDate    = preg_match('/^(datetime|timestamp)/', $type);
            $isDateOnly = $type === 'date';
            $hasError  = $errors->has($col['Field']);
            $oldVal    = old($col['Field'], $isNull ? '' : ($col['Default'] ?? ''));
        @endphp
        @if(!$isAI)
        <div class="row-form-row {{ $hasError ? 'error' : '' }}">
            <div class="field-meta">
                <div class="field-name">
                    {{ $col['Field'] }}
                    @if(!$isNull) <span style="color:#f87171;font-weight:700;margin-left:1px">*</span> @endif
                </div>
                <div class="field-badges">
                    <code style="font-size:.65rem;background:#eff6ff;color:#2563eb;padding:.1rem .375rem;border-radius:.3rem;font-family:monospace">{{ $col['Type'] }}</code>
                    @if($col['Key']==='PRI') <span class="badge badge-pk">PK</span> @endif
                    @if($isNull) <span class="badge badge-null">nullable</span> @else <span class="badge badge-notnull">required</span> @endif
                </div>
            </div>
            <div>
                @if($isText)
                <textarea name="{{ $col['Field'] }}" rows="3"
                          class="form-input {{ $hasError ? 'error' : '' }}"
                          style="font-family:'JetBrains Mono','Fira Code',monospace;font-size:.8125rem"
                          placeholder="{{ $isNull ? 'Leave empty for NULL' : 'Required' }}"
                          {{ !$isNull ? 'required' : '' }}>{{ $oldVal }}</textarea>
                @elseif($isDate)
                <input type="text" name="{{ $col['Field'] }}" value="{{ $oldVal }}"
                       class="form-input {{ $hasError ? 'error' : '' }}"
                       style="font-family:monospace" placeholder="YYYY-MM-DD HH:MM:SS"
                       {{ !$isNull ? 'required' : '' }}>
                @elseif($isDateOnly)
                <input type="date" name="{{ $col['Field'] }}" value="{{ $oldVal }}"
                       class="form-input {{ $hasError ? 'error' : '' }}"
                       {{ !$isNull ? 'required' : '' }}>
                @elseif($isInt)
                <input type="number" name="{{ $col['Field'] }}" value="{{ $oldVal }}"
                       class="form-input {{ $hasError ? 'error' : '' }}"
                       style="font-family:monospace" step="1"
                       placeholder="{{ $isNull ? 'NULL' : '0' }}"
                       {{ !$isNull ? 'required' : '' }}>
                @elseif($isDecimal)
                <input type="number" name="{{ $col['Field'] }}" value="{{ $oldVal }}"
                       class="form-input {{ $hasError ? 'error' : '' }}"
                       style="font-family:monospace" step="any"
                       placeholder="{{ $isNull ? 'NULL' : '0.00' }}"
                       {{ !$isNull ? 'required' : '' }}>
                @else
                <input type="text" name="{{ $col['Field'] }}" value="{{ $oldVal }}"
                       class="form-input {{ $hasError ? 'error' : '' }}"
                       placeholder="{{ $isNull ? 'Leave empty for NULL' : 'Required' }}"
                       {{ !$isNull ? 'required' : '' }}>
                @endif

                @error($col['Field'])
                <div class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                @enderror

                @if($col['Default'] !== null && !$hasError)
                <div class="field-hint">Default: <code style="background:#f1f5f9;padding:.1rem .3rem;border-radius:.25rem;font-size:.7rem">{{ $col['Default'] ?: 'empty string' }}</code></div>
                @endif
            </div>
        </div>
        @endif
        @endforeach

        <div style="display:flex;align-items:center;gap:.75rem;padding:1rem 1.5rem;background:#f8fafc;border-top:1px solid #e2e8f0">
            <button type="submit" class="btn btn-success">
                <i class="fa-solid fa-floppy-disk"></i> Insert Row
            </button>
            <a href="{{ route('db-manager.tables.browse', [$database, $table]) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </div>
</form>
@endsection
