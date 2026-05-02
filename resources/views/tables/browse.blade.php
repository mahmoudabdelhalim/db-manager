@extends('db-manager::layout')
@section('title', "$table — Browse")

@section('breadcrumb')
<a href="{{ route('db-manager.home') }}">Home</a><span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<a href="{{ route('db-manager.tables.index', $database) }}">{{ $database }}</a><span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
<span class="current">{{ $table }}</span>
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.75rem">
    <div>
        <h1 style="font-size:1.125rem;font-weight:700;color:#0f172a;margin:0">
            <span style="color:#64748b">{{ $database }}</span><span style="color:#cbd5e1">.</span><span style="color:#f97316">{{ $table }}</span>
        </h1>
        <p style="font-size:.75rem;color:#64748b;margin:.2rem 0 0">
            @if($total > 0)
                Rows {{ number_format(($page-1)*$perPage+1) }}–{{ number_format(min($page*$perPage,$total)) }} of {{ number_format($total) }}
            @else No rows @endif
        </p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="{{ route('db-manager.tables.structure', [$database, $table]) }}" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-sitemap"></i> Structure
        </a>
        <a href="{{ route('db-manager.rows.create', [$database, $table]) }}" class="btn btn-success btn-sm">
            <i class="fa-solid fa-plus"></i> Insert Row
        </a>
        <a href="{{ route('db-manager.query.index', ['db' => $database]) }}" class="btn btn-purple btn-sm">
            <i class="fa-solid fa-terminal"></i> SQL
        </a>
    </div>
</div>

<div class="card" style="overflow:hidden">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:64px">Actions</th>
                    @foreach($columns as $col)
                    @php $active = $orderBy === $col['Field']; $next = ($active && $orderDir==='ASC') ? 'DESC' : 'ASC'; @endphp
                    <th>
                        <a href="{{ route('db-manager.tables.browse', ['database'=>$database,'table'=>$table,'order_by'=>$col['Field'],'order_dir'=>$next]) }}"
                           style="display:flex;align-items:center;gap:.25rem;text-decoration:none;color:{{ $active?'#f97316':'#64748b' }};font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;white-space:nowrap">
                            {{ $col['Field'] }}
                            <i class="fa-solid fa-sort{{ $active ? ($orderDir==='ASC'?'-up':'-down') : '' }}"
                               style="font-size:.55rem;opacity:{{ $active?'1':'.35' }}"></i>
                        </a>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td style="padding:.5rem .75rem">
                        <div style="display:flex;gap:.25rem">
                            <a href="{{ route('db-manager.rows.edit', array_merge(['database'=>$database,'table'=>$table],$row)) }}"
                               style="width:26px;height:26px;display:flex;align-items:center;justify-content:center;border-radius:.375rem;background:#eff6ff;color:#2563eb;text-decoration:none;transition:all 100ms;font-size:.7rem"
                               onmouseover="this.style.background='#2563eb';this.style.color='#fff'"
                               onmouseout="this.style.background='#eff6ff';this.style.color='#2563eb'"
                               title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <form action="{{ route('db-manager.rows.destroy', [$database,$table]) }}" method="POST"
                                  style="margin:0" onsubmit="return confirm('Delete this row? This cannot be undone.')">
                                @csrf @method('DELETE')
                                @foreach($row as $k => $v)
                                    <input type="hidden" name="_where[{{ $k }}]" value="{{ $v }}">
                                @endforeach
                                <button type="submit"
                                        style="width:26px;height:26px;display:flex;align-items:center;justify-content:center;border-radius:.375rem;background:#fef2f2;color:#dc2626;border:none;cursor:pointer;transition:all 100ms;font-size:.7rem"
                                        onmouseover="this.style.background='#dc2626';this.style.color='#fff'"
                                        onmouseout="this.style.background='#fef2f2';this.style.color='#dc2626'"
                                        title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                    @foreach($row as $value)
                    <td style="font-family:'JetBrains Mono','Fira Code',monospace;font-size:.78rem;max-width:260px;padding:.5rem 1rem">
                        @if($value === null)
                            <span style="color:#cbd5e1;font-style:italic;font-family:inherit">NULL</span>
                        @else
                            <span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                  title="{{ $value }}">{{ Str::limit((string)$value, 100) }}</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($columns)+1 }}">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
                            <p style="font-weight:600;color:#374151;margin:0">No rows found</p>
                            <a href="{{ route('db-manager.rows.create',[$database,$table]) }}" style="font-size:.8rem;color:#f97316;text-decoration:none;margin-top:.25rem;display:inline-block">Insert the first row →</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($total > $perPage)
    @php $totalPages = (int)ceil($total/$perPage); $start=max(1,$page-2); $end=min($totalPages,$page+2); @endphp
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;background:#f8fafc;border-top:1px solid #e2e8f0">
        <span style="font-size:.75rem;color:#64748b">
            Page {{ $page }} of {{ number_format($totalPages) }} &bull; {{ number_format($total) }} total rows
        </span>
        <div style="display:flex;align-items:center;gap:.25rem">
            @if($page>1)
            <a href="{{ route('db-manager.tables.browse',['database'=>$database,'table'=>$table,'page'=>1,'order_by'=>$orderBy,'order_dir'=>$orderDir]) }}" class="page-btn" title="First"><i class="fa-solid fa-angles-left"></i></a>
            <a href="{{ route('db-manager.tables.browse',['database'=>$database,'table'=>$table,'page'=>$page-1,'order_by'=>$orderBy,'order_dir'=>$orderDir]) }}" class="page-btn" title="Previous"><i class="fa-solid fa-angle-left"></i></a>
            @endif
            @for($p=$start;$p<=$end;$p++)
            <a href="{{ route('db-manager.tables.browse',['database'=>$database,'table'=>$table,'page'=>$p,'order_by'=>$orderBy,'order_dir'=>$orderDir]) }}"
               class="page-btn {{ $p==$page?'active':'' }}">{{ $p }}</a>
            @endfor
            @if($page<$totalPages)
            <a href="{{ route('db-manager.tables.browse',['database'=>$database,'table'=>$table,'page'=>$page+1,'order_by'=>$orderBy,'order_dir'=>$orderDir]) }}" class="page-btn" title="Next"><i class="fa-solid fa-angle-right"></i></a>
            <a href="{{ route('db-manager.tables.browse',['database'=>$database,'table'=>$table,'page'=>$totalPages,'order_by'=>$orderBy,'order_dir'=>$orderDir]) }}" class="page-btn" title="Last"><i class="fa-solid fa-angles-right"></i></a>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
