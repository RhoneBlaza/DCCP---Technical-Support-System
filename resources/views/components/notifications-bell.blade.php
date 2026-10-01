<div class="relative" x-data="notificationBell()" @click.outside="open = false">
    <button
        type="button"
        class="relative p-2 rounded-lg text-ink-muted hover:bg-surface-sunken"
        @click="toggle"
        aria-label="Notifications"
        aria-haspopup="true">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span
            x-cloak
            x-show="unread > 0"
            class="absolute top-1 right-1 flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold"
            x-text="unread"></span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition
        class="absolute right-0 mt-2 w-80 sm:w-96 rounded-xl bg-surface ring-1 ring-line shadow-lg z-40 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-2.5 border-b border-line-soft">
            <p class="text-sm font-semibold text-ink">Notifications</p>
            <template x-if="items.length > 0">
                <button type="button" class="text-xs text-navy-700 dark:text-navy-200 hover:underline" @click="markAllRead">Mark all read</button>
            </template>
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-line-soft" @click.outside="open = false">
            <template x-if="items.length === 0">
                <div class="px-4 py-8 text-center text-sm text-ink-subtle">You're all caught up.</div>
            </template>
            <template x-for="item in items" :key="item.id">
                <a :href="item.url || '#'" class="block px-4 py-3 hover:bg-surface-muted" :class="item.read_at ? '' : 'bg-navy-50/40'">
                    <p class="text-sm text-ink" x-text="item.text"></p>
                    <p class="text-xs text-ink-subtle mt-0.5" x-text="item.relative"></p>
                </a>
            </template>
        </div>

        <a href="{{ route('notifications.index') }}" class="block border-t border-line-soft px-4 py-2.5 text-center text-sm text-navy-700 dark:text-navy-200 font-medium hover:bg-surface-muted">
            View all notifications
        </a>
    </div>
</div>

<script>
    function notificationBell() {
        return {
            open: false,
            unread: 0,
            items: [],
            async init() {
                await this.refresh();
            },
            async refresh() {
                try {
                    const response = await fetch('{{ route("notifications.recent") }}', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const data = await response.json();
                    this.unread = data.unread || 0;
                    this.items = data.items || [];
                } catch (e) {
                    // The notification center remains functional without polling.
                }
            },
            toggle() {
                this.open = !this.open;
                if (this.open) this.refresh();
            },
            async markAllRead() {
                try {
                    await fetch('{{ route("notifications.read-all") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    this.unread = 0;
                    this.items = this.items.map(i => ({ ...i, read_at: new Date().toISOString() }));
                } catch (e) {}
            }
        };
    }
</script>