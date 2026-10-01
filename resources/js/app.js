import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

document.addEventListener('alpine:init', () => {
    Alpine.store('modals', {
        openModals: [],
        isOpen(id) {
            return this.openModals.includes(id);
        },
        open(id) {
            if (!this.isOpen(id)) {
                this.openModals.push(id);
            }
        },
        close(id) {
            this.openModals = this.openModals.filter((m) => m !== id);
        },
    });

    // One source of truth for the auto-hide navigation drawer. The brand
    // button, the left-edge hover zone, the outside-click catcher and the
    // Escape key all drive this state so they can never disagree.
    Alpine.store('sidebar', {
        // The drawer always starts closed so it never covers content on load.
        open: false,
        closeTimer: null,

        openDrawer() {
            this.cancelClose();
            this.open = true;
        },

        closeDrawer() {
            this.cancelClose();
            this.open = false;
        },

        toggleDrawer() {
            if (this.open) {
                this.closeDrawer();
            } else {
                this.openDrawer();
            }
        },

        // Grace period so crossing the gap between the panel and the pointer
        // does not slam the drawer shut mid-move.
        scheduleClose(delay = 250) {
            this.cancelClose();
            this.closeTimer = window.setTimeout(() => {
                this.closeTimer = null;
                this.open = false;
            }, delay);
        },

        cancelClose() {
            if (this.closeTimer !== null) {
                window.clearTimeout(this.closeTimer);
                this.closeTimer = null;
            }
        },
    });

    // Light / dark / system theme. The inline script in the layout has already
    // applied the choice before first paint; this store keeps it in sync, follows
    // the OS while "system" is selected, and saves an explicit pick to the
    // account so it travels with the user.
    Alpine.store('theme', {
        storageKey: 'tsts.theme',
        choices: ['light', 'dark', 'system'],
        preference: 'system',
        systemPrefersDark: false,

        init() {
            this.systemPrefersDark = this.media().matches;
            this.preference = this.validOrDefault(window.themePreference);

            this.onSystemChange(() => {
                this.systemPrefersDark = this.media().matches;
                if (this.preference === 'system') {
                    this.apply();
                }
            });
        },

        is(preference) {
            return this.preference === preference;
        },

        /** The theme that is actually on screen right now. */
        isDark() {
            if (this.preference === 'light') {
                return false;
            }

            if (this.preference === 'dark') {
                return true;
            }

            return this.systemPrefersDark;
        },

        /** The label shown on the collapsed toggle button. */
        label() {
            return this.preference.charAt(0).toUpperCase() + this.preference.slice(1);
        },

        set(preference) {
            if (!this.choices.includes(preference)) {
                return;
            }

            this.preference = preference;
            window.themePreference = preference;

            try {
                window.localStorage.setItem(this.storageKey, preference);
            } catch (error) {
                // Storage unavailable (private browsing): the theme still applies.
            }

            this.apply();
            this.persist(preference);
        },

        apply() {
            const dark = this.isDark();
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            this.$dispatch('theme-changed', { theme: this.preference, dark });
        },

        persist(preference) {
            // Guests have no endpoint and no account; localStorage is the whole
            // story for them, so there is nothing to send.
            if (!window.themeEndpoint) {
                return;
            }

            window
                .fetch(window.themeEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ theme: preference }),
                })
                .catch(() => {
                    // The theme is applied locally regardless; a failed save only
                    // means it will not follow the user to another device.
                });
        },

        validOrDefault(preference) {
            return this.choices.includes(preference) ? preference : 'system';
        },

        media() {
            return window.matchMedia('(prefers-color-scheme: dark)');
        },

        onSystemChange(handler) {
            const media = this.media();

            if (typeof media.addEventListener === 'function') {
                media.addEventListener('change', handler);
            } else if (typeof media.addListener === 'function') {
                media.addListener(handler);
            }
        },
    });
});

Alpine.start();

// Auto-dismiss alert toasts.
window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-dismiss]').forEach((element) => {
        const seconds = Number(element.dataset.autoDismiss) || 4;
        setTimeout(() => {
            element.classList.add('opacity-0', 'transition-opacity', 'duration-500');
            setTimeout(() => element.remove(), 500);
        }, seconds * 1000);
    });
});