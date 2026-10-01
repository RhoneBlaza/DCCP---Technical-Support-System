@forelse ($logs as $log)
    <tr class="hover:bg-surface-muted transition-colors">
        <td class="px-4 py-2.5 whitespace-nowrap text-xs text-ink-subtle" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
            {{ $log->created_at->diffForHumans() }}
        </td>
        <td class="px-4 py-2.5 whitespace-nowrap">
            @if ($log->user)
                <span class="text-sm font-medium text-ink">{{ $log->user->full_name }}</span>
            @else
                <span class="text-sm text-ink-faint">System</span>
            @endif
        </td>
        <td class="px-4 py-2.5">
            <code class="text-xs font-mono text-navy-700 dark:text-navy-200 bg-navy-50 dark:bg-navy-500/10 rounded px-1.5 py-0.5">{{ $log->action }}</code>
        </td>
        <td class="px-4 py-2.5 text-sm text-ink-muted">{{ $log->description }}</td>
        <td class="px-4 py-2.5 whitespace-nowrap text-xs font-mono text-ink-subtle">{{ $log->ip_address }}</td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="px-4 py-8 text-center text-sm text-ink-faint">
            No activity matches the current filters.
        </td>
    </tr>
@endforelse