@forelse ($logs as $log)
    <tr class="hover:bg-slate-50 transition-colors">
        <td class="px-4 py-2.5 whitespace-nowrap text-xs text-slate-500" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
            {{ $log->created_at->diffForHumans() }}
        </td>
        <td class="px-4 py-2.5 whitespace-nowrap">
            @if ($log->user)
                <span class="text-sm font-medium text-slate-800">{{ $log->user->full_name }}</span>
            @else
                <span class="text-sm text-slate-400">System</span>
            @endif
        </td>
        <td class="px-4 py-2.5">
            <code class="text-xs font-mono text-navy-700 bg-navy-50 rounded px-1.5 py-0.5">{{ $log->action }}</code>
        </td>
        <td class="px-4 py-2.5 text-sm text-slate-600">{{ $log->description }}</td>
        <td class="px-4 py-2.5 whitespace-nowrap text-xs font-mono text-slate-500">{{ $log->ip_address }}</td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">
            No activity matches the current filters.
        </td>
    </tr>
@endforelse