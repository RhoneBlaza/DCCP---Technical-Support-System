@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-page-header title="Admin Dashboard" description="Institution-wide ticket overview">
    </x-page-header>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        <x-stat-card label="Total tickets" :value="$stats['total']" icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-6 3h6m-6 4h6m-6 4h2" />
        <x-stat-card label="Open" :value="$stats['open']" color="blue" icon="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        <x-stat-card label="In progress" :value="$stats['in_progress']" color="amber" icon="M13 10V3L4 14h7v7l9-11h-7z" />
        <x-stat-card label="Overdue SLA" :value="$stats['overdue']" color="red" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        <x-stat-card label="Pending (awaiting)" :value="$stats['pending']" color="slate" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        <x-stat-card label="Urgent open" :value="$stats['urgent']" color="red" icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        <x-stat-card label="Resolved" :value="$stats['resolved']" color="green" icon="M5 13l4 4L19 7" />
        <x-stat-card label="Created this month" :value="$stats['month']" color="blue" icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </div>

    @php
        $trendLabels30 = array_map(fn ($day) => \Carbon\Carbon::parse($day)->format('M j'), array_keys($time_data));
        $trendCounts30 = array_values($time_data);
        $trendLabels7 = array_slice($trendLabels30, -7);
        $trendCounts7 = array_slice($trendCounts30, -7);

        $statusLabels = [];
        $statusCounts = [];
        $statusColors = [];
        foreach ($status_data as $name => $count) {
            // Skip placeholder text
            if (stripos($name, 'quis') !== false || stripos($name, 'illum') !== false) {
                continue;
            }
            $statusLabels[] = $name.' ('.$count.')';
            $statusCounts[] = $count;
            $statusColors[] = $status_colors[$name] ?? '#64748b';
        }

        $categoryLabels = array_keys($category_data);
        $categoryCounts = array_values($category_data);
        $workloadLabels = array_keys($workload_data);
        $workloadCounts = array_values($workload_data);
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <x-card title="Tickets created" description="Volume over the last 7 or 30 days" x-data="trendToggle()">
            <x-slot:actions>
                <div class="flex items-center rounded-lg bg-slate-200 p-0.5 text-xs font-medium">
                    <button type="button" @click="setRange(7)" :class="range === 7 ? 'bg-navy-700 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'" :aria-pressed="range === 7" class="px-2.5 py-1 rounded-md transition-colors">7 days</button>
                    <button type="button" @click="setRange(30)" :class="range === 30 ? 'bg-navy-700 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'" :aria-pressed="range === 30" class="px-2.5 py-1 rounded-md transition-colors">30 days</button>
                </div>
            </x-slot:actions>
            <div class="h-64">
                @if (array_sum($trendCounts30) > 0)
                    <canvas id="trendChart"></canvas>
                @else
                    <x-empty-state title="No tickets yet" description="The trend chart will appear once tickets are created." />
                @endif
            </div>
        </x-card>

        <x-card title="Tickets by status">
            <div class="h-64">
                @if (array_sum($statusCounts) > 0)
                    <canvas id="statusChart"></canvas>
                @else
                    <x-empty-state title="No tickets yet" description="Status distribution will appear once tickets are created." />
                @endif
            </div>
        </x-card>

        <x-card title="Tickets by category">
            <div class="h-64">
                @if (array_sum($categoryCounts) > 0)
                    <canvas id="categoryChart"></canvas>
                @else
                    <x-empty-state title="No tickets yet" description="Category distribution will appear once tickets are created." />
                @endif
            </div>
        </x-card>

        <x-card title="Workload by support staff (in progress)">
            <div class="h-64">
                @if (array_sum($workloadCounts) > 0)
                    <canvas id="workloadChart"></canvas>
                @else
                    <x-empty-state title="No open assignments" description="Staff workload will appear once tickets are assigned." />
                @endif
            </div>
        </x-card>
    </div>

    @push('scripts')
        <script>
            function trendToggle() {
                return {
                    range: 30,
                    setRange(n) {
                        this.range = n;
                        const chart = window.adminCharts && window.adminCharts.trend;
                        if (! chart || ! window.adminChartData) {
                            return;
                        }
                        const data = window.adminChartData[n === 7 ? 'trend7' : 'trend30'];
                        chart.data.labels = data.labels;
                        chart.data.datasets[0].data = data.data;
                        chart.update();
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', () => {
                const barValues = {
                    id: 'barValues',
                    afterDatasetsDraw(chart) {
                        const horizontal = chart.options.indexAxis === 'y';
                        const { ctx, chartArea } = chart;
                        ctx.save();
                        ctx.font = '600 11px "Instrument Sans", system-ui, sans-serif';
                        chart.data.datasets.forEach((dataset, i) => {
                            const meta = chart.getDatasetMeta(i);
                            meta.data.forEach((bar, j) => {
                                const value = dataset.data[j];
                                if (! value) {
                                    return;
                                }
                                const text = String(value);
                                if (horizontal) {
                                    if (bar.x + 6 + ctx.measureText(text).width <= chartArea.right) {
                                        ctx.textAlign = 'left';
                                        ctx.fillStyle = '#475569';
                                        ctx.fillText(text, bar.x + 6, bar.y + 4);
                                    } else {
                                        ctx.textAlign = 'right';
                                        ctx.fillStyle = '#ffffff';
                                        ctx.fillText(text, bar.x - 6, bar.y + 4);
                                    }
                                } else if (bar.y - 8 >= chartArea.top) {
                                    ctx.textAlign = 'center';
                                    ctx.fillStyle = '#475569';
                                    ctx.fillText(text, bar.x, bar.y - 6);
                                } else {
                                    ctx.textAlign = 'center';
                                    ctx.fillStyle = '#ffffff';
                                    ctx.fillText(text, bar.x, bar.y + 14);
                                }
                            });
                        });
                        ctx.restore();
                    },
                };

                const charts = {};
                window.adminCharts = charts;
                window.adminChartData = {
                    trend7: { labels: @json($trendLabels7), data: @json($trendCounts7) },
                    trend30: { labels: @json($trendLabels30), data: @json($trendCounts30) },
                };

                const trendCanvas = document.getElementById('trendChart');
                if (trendCanvas) {
                    charts.trend = new Chart(trendCanvas, {
                    type: 'line',
                    data: {
                        labels: @json($trendLabels30),
                        datasets: [{
                            label: 'Tickets',
                            data: @json($trendCounts30),
                            borderColor: '#254875',
                            backgroundColor: 'rgba(37, 72, 117, 0.08)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 2,
                            pointBackgroundColor: '#254875',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
                            y: { beginAtZero: true, ticks: { precision: 0, color: '#94a3b8', font: { size: 11 } }, grid: { color: '#f1f5f9' } },
                        },
                    },
                });
                }

                const statusCanvas = document.getElementById('statusChart');
                if (statusCanvas) {
                    charts.status = new Chart(statusCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: @json($statusLabels),
                        datasets: [{
                            data: @json($statusCounts),
                            backgroundColor: @json($statusColors),
                            borderWidth: 1,
                            borderColor: '#ffffff',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: { boxWidth: 12, boxHeight: 12, padding: 12, color: '#334155', font: { size: 11 } },
                            },
                        },
                    },
                });
                }

                const categoryCanvas = document.getElementById('categoryChart');
                if (categoryCanvas) {
                    charts.category = new Chart(categoryCanvas, {
                        type: 'bar',
                        data: {
                            labels: @json($categoryLabels),
                            datasets: [{
                                label: 'Tickets',
                                data: @json($categoryCounts),
                                backgroundColor: '#254875',
                                borderRadius: 4,
                                maxBarThickness: 20,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y',
                            layout: { padding: { right: 20 } },
                            plugins: {
                                legend: { display: false },
                                barValues,
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    suggestedMax: @json($category_max ?? null),
                                    ticks: { precision: 0, color: '#94a3b8', font: { size: 11 } },
                                    grid: { color: '#f1f5f9' }
                                },
                                y: { grid: { display: false }, ticks: { color: '#475569', font: { size: 11 } } },
                            },
                        },
                    });
                }

                const workloadCanvas = document.getElementById('workloadChart');
                if (workloadCanvas) {
                    charts.workload = new Chart(workloadCanvas, {
                        type: 'bar',
                        data: {
                            labels: @json($workloadLabels),
                            datasets: [{
                                label: 'Open tickets',
                                data: @json($workloadCounts),
                                backgroundColor: '#3a70ab',
                                borderRadius: 4,
                                maxBarThickness: 32,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: { padding: { top: 16 } },
                            plugins: {
                                legend: { display: false },
                                barValues,
                            },
                            scales: {
                                x: { grid: { display: false }, ticks: { color: '#475569', font: { size: 11 } } },
                                y: { beginAtZero: true, ticks: { precision: 0, color: '#94a3b8', font: { size: 11 } }, grid: { color: '#f1f5f9' } },
                            },
                        },
                    });
                }
            });
        </script>
    @endpush
@endsection