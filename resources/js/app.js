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