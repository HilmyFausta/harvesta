<?php

use App\Services\ReportService;
use App\Support\Format;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reports')] class extends Component {
    public string $from = '';

    public string $to = '';

    public string $groupBy = 'block';

    public function mount(): void
    {
        Gate::authorize('admin');

        $from = request()->input('from');
        $to = request()->input('to');
        $groupBy = request()->input('group_by');

        $this->from = $this->dateOr($from, now()->startOfYear()->toDateString());
        $this->to = $this->dateOr($to, today()->toDateString());

        if ($this->from > $this->to) {
            [$this->from, $this->to] = [$this->to, $this->from];
        }

        $this->groupBy = is_string($groupBy) && in_array($groupBy, ['block', 'tree_type', 'month'], true)
            ? $groupBy
            : 'block';
    }

    public function render()
    {
        $reports = app(ReportService::class);

        $harvestRows = $reports->harvestSummary($this->from, $this->to, $this->groupBy);
        $harvestMonthRows = $this->groupBy === 'month'
            ? $harvestRows
            : $reports->harvestSummary($this->from, $this->to, 'month');

        $financialRows = $reports->financialSummary($this->from, $this->to);
        $financialBlockRows = $reports->financialByBlock($this->from, $this->to);
        $careRows = $reports->careByActivity($this->from, $this->to);

        $harvestTotals = [
            'harvests' => (int) $harvestRows->sum('harvests'),
            'kg' => (float) $harvestRows->sum('kg'),
            'pcs' => (float) $harvestRows->sum('pcs'),
        ];

        $financialTotals = [
            'sales' => (float) $financialRows->sum('sales'),
            'expenses' => (float) $financialRows->sum('expenses'),
            'net' => (float) $financialRows->sum('net'),
        ];

        $groupLabel = match ($this->groupBy) {
            'tree_type' => __('Tree type'),
            'month' => __('Month'),
            default => __('Block'),
        };

        return $this->view([
            'harvestRows' => $harvestRows,
            'financialRows' => $financialRows,
            'financialBlockRows' => $financialBlockRows,
            'careRows' => $careRows,
            'harvestTotals' => $harvestTotals,
            'financialTotals' => $financialTotals,
            'careTotal' => (int) $careRows->sum('total'),
            'groupLabel' => $groupLabel,
            'harvestChart' => [
                'labels' => $harvestMonthRows->pluck('label')->all(),
                'kg' => $harvestMonthRows->pluck('kg')->all(),
                'pcs' => $harvestMonthRows->pluck('pcs')->all(),
            ],
            'financialChart' => [
                'labels' => $financialRows->pluck('label')->all(),
                'sales' => $financialRows->pluck('sales')->all(),
                'expenses' => $financialRows->pluck('expenses')->all(),
            ],
            'careChart' => [
                'labels' => $careRows->pluck('label')->all(),
                'totals' => $careRows->pluck('total')->all(),
            ],
            'harvestExportUrl' => route('reports.export', [
                'report' => 'harvest',
                'from' => $this->from,
                'to' => $this->to,
                'group_by' => $this->groupBy,
            ]),
            'financialExportUrl' => route('reports.export', [
                'report' => 'financial',
                'from' => $this->from,
                'to' => $this->to,
            ]),
            'careExportUrl' => route('reports.export', [
                'report' => 'care',
                'from' => $this->from,
                'to' => $this->to,
            ]),
            'periodLabel' => __(':from to :to', [
                'from' => Format::date(Carbon::parse($this->from)),
                'to' => Format::date(Carbon::parse($this->to)),
            ]),
        ]);
    }

    private function dateOr(mixed $value, string $fallback): string
    {
        if (! is_string($value) || $value === '') {
            return $fallback;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return $fallback;
        }
    }
}; ?>

<section class="w-full space-y-6">
    <x-page-header :title="__('Reports')" :description="__('Harvest, financial, and care summaries for the selected period.')" />

    <form method="GET" action="{{ route('reports.index') }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid items-end gap-4 md:grid-cols-2 xl:grid-cols-4">
            <flux:input name="from" type="date" :label="__('From')" :value="$from" />
            <flux:input name="to" type="date" :label="__('To')" :value="$to" />

            <flux:select name="group_by" :label="__('Group harvest by')">
                <flux:select.option value="block" :selected="$groupBy === 'block'">{{ __('Block') }}</flux:select.option>
                <flux:select.option value="tree_type" :selected="$groupBy === 'tree_type'">{{ __('Tree type') }}</flux:select.option>
                <flux:select.option value="month" :selected="$groupBy === 'month'">{{ __('Month') }}</flux:select.option>
            </flux:select>

            <flux:button type="submit" variant="primary" icon="funnel" class="w-full">
                {{ __('Apply filters') }}
            </flux:button>
        </div>

        <flux:text class="mt-3">{{ __('Showing data for :period.', ['period' => $periodLabel]) }}</flux:text>
    </form>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Harvest summary') }}</h2>
                <flux:text>{{ __('Grouped by :group', ['group' => strtolower($groupLabel)]) }}</flux:text>
            </div>

            <flux:button :href="$harvestExportUrl" icon="arrow-down-tray" size="sm" variant="ghost">
                {{ __('Export CSV') }}
            </flux:button>
        </div>

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                <flux:text>{{ __('Harvests') }}</flux:text>
                <p class="mt-1 text-xl font-semibold text-zinc-800 dark:text-white">{{ Format::number($harvestTotals['harvests'], 0) }}</p>
            </div>

            <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                <flux:text>{{ __('Quantity (kg)') }}</flux:text>
                <p class="mt-1 text-xl font-semibold text-zinc-800 dark:text-white">{{ Format::number($harvestTotals['kg']) }}</p>
            </div>

            <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                <flux:text>{{ __('Quantity (pcs)') }}</flux:text>
                <p class="mt-1 text-xl font-semibold text-zinc-800 dark:text-white">{{ Format::number($harvestTotals['pcs']) }}</p>
            </div>
        </div>

        @if ($harvestRows->isEmpty())
            <x-empty-state icon="archive-box" :title="__('No harvests found.')" :description="__('No harvests were recorded in the selected period.')" />
        @else
            <div class="mb-6 h-64">
                <canvas id="report-harvest-chart" data-chart="{{ json_encode($harvestChart) }}" role="img" aria-label="{{ __('Harvest by month') }}"></canvas>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ $groupLabel }}</th>
                            <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Harvests') }}</th>
                            <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Quantity (kg)') }}</th>
                            <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Quantity (pcs)') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($harvestRows as $row)
                            <tr>
                                <td class="py-3 text-zinc-800 dark:text-white">{{ $row['label'] }}</td>
                                <td class="py-3 text-right text-zinc-600 dark:text-zinc-400">{{ Format::number($row['harvests'], 0) }}</td>
                                <td class="py-3 text-right text-zinc-600 dark:text-zinc-400">{{ Format::number($row['kg']) }}</td>
                                <td class="py-3 text-right text-zinc-600 dark:text-zinc-400">{{ Format::number($row['pcs']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Financial summary') }}</h2>
                <flux:text>{{ __('Sales minus expenses per month and block.') }}</flux:text>
            </div>

            <flux:button :href="$financialExportUrl" icon="arrow-down-tray" size="sm" variant="ghost">
                {{ __('Export CSV') }}
            </flux:button>
        </div>

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                <flux:text>{{ __('Sales') }}</flux:text>
                <p class="mt-1 text-xl font-semibold text-emerald-600 dark:text-emerald-400">{{ Format::money($financialTotals['sales']) }}</p>
            </div>

            <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                <flux:text>{{ __('Expenses') }}</flux:text>
                <p class="mt-1 text-xl font-semibold text-red-600 dark:text-red-400">{{ Format::money($financialTotals['expenses']) }}</p>
            </div>

            <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                <flux:text>{{ __('Net profit') }}</flux:text>
                <p class="mt-1 text-xl font-semibold {{ $financialTotals['net'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ Format::money($financialTotals['net']) }}
                </p>
            </div>
        </div>

        <div class="mb-6 h-64">
            <canvas id="report-financial-chart" data-chart="{{ json_encode($financialChart) }}" role="img" aria-label="{{ __('Sales and expenses by month') }}"></canvas>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div>
                <h3 class="mb-3 font-semibold text-zinc-800 dark:text-white">{{ __('By month') }}</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-zinc-200 dark:border-zinc-700">
                            <tr>
                                <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ __('Month') }}</th>
                                <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Sales') }}</th>
                                <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Expenses') }}</th>
                                <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Net') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($financialRows as $row)
                                <tr>
                                    <td class="py-3 text-zinc-800 dark:text-white">{{ $row['label'] }}</td>
                                    <td class="py-3 text-right text-zinc-600 dark:text-zinc-400">{{ Format::money($row['sales']) }}</td>
                                    <td class="py-3 text-right text-zinc-600 dark:text-zinc-400">{{ Format::money($row['expenses']) }}</td>
                                    <td class="py-3 text-right font-medium {{ $row['net'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ Format::money($row['net']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h3 class="mb-3 font-semibold text-zinc-800 dark:text-white">{{ __('By block') }}</h3>

                @if ($financialBlockRows->isEmpty())
                    <flux:text>{{ __('No sales or expenses were recorded in the selected period.') }}</flux:text>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ __('Block') }}</th>
                                    <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Sales') }}</th>
                                    <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Expenses') }}</th>
                                    <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Net') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($financialBlockRows as $row)
                                    <tr>
                                        <td class="py-3 text-zinc-800 dark:text-white">{{ $row['label'] }}</td>
                                        <td class="py-3 text-right text-zinc-600 dark:text-zinc-400">{{ Format::money($row['sales']) }}</td>
                                        <td class="py-3 text-right text-zinc-600 dark:text-zinc-400">{{ Format::money($row['expenses']) }}</td>
                                        <td class="py-3 text-right font-medium {{ $row['net'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ Format::money($row['net']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Care activity summary') }}</h2>
                <flux:text>{{ __('Care logs grouped by activity type.') }}</flux:text>
            </div>

            <flux:button :href="$careExportUrl" icon="arrow-down-tray" size="sm" variant="ghost">
                {{ __('Export CSV') }}
            </flux:button>
        </div>

        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                <flux:text>{{ __('Care logs') }}</flux:text>
                <p class="mt-1 text-xl font-semibold text-zinc-800 dark:text-white">{{ Format::number($careTotal, 0) }}</p>
            </div>

            <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                <flux:text>{{ __('Activity types') }}</flux:text>
                <p class="mt-1 text-xl font-semibold text-zinc-800 dark:text-white">{{ Format::number($careRows->count(), 0) }}</p>
            </div>
        </div>

        @if ($careRows->isEmpty())
            <x-empty-state icon="wrench-screwdriver" :title="__('No care activities found.')" :description="__('No care logs were recorded in the selected period.')" />
        @else
            <div class="mb-6 h-64">
                <canvas id="report-care-chart" data-chart="{{ json_encode($careChart) }}" role="img" aria-label="{{ __('Care logs by activity type') }}"></canvas>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ __('Activity') }}</th>
                            <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Care logs') }}</th>
                            <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Last performed') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($careRows as $row)
                            <tr>
                                <td class="py-3 text-zinc-800 dark:text-white">{{ $row['label'] }}</td>
                                <td class="py-3 text-right text-zinc-600 dark:text-zinc-400">{{ Format::number($row['total'], 0) }}</td>
                                <td class="py-3 text-right text-zinc-600 dark:text-zinc-400">{{ $row['last_performed'] ? Format::date(Carbon::parse($row['last_performed'])) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

@script
<script>
    (() => {
        const renderReportCharts = () => {
            if (!window.Chart) {
                return;
            }

            const renderChart = (id, config) => {
                const canvas = document.getElementById(id);

                if (!canvas) {
                    return;
                }

                if (canvas.reportChart) {
                    canvas.reportChart.destroy();
                }

                canvas.reportChart = new window.Chart(canvas, config);
            };

            const harvestCanvas = document.getElementById('report-harvest-chart');

            if (harvestCanvas) {
                const data = JSON.parse(harvestCanvas.dataset.chart);

                renderChart('report-harvest-chart', {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: @js(__('kg')),
                                data: data.kg,
                                backgroundColor: '#84cc16',
                                borderRadius: 4,
                                maxBarThickness: 24,
                            },
                            {
                                label: @js(__('pcs')),
                                data: data.pcs,
                                backgroundColor: '#0ea5e9',
                                borderRadius: 4,
                                maxBarThickness: 24,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 12 },
                            },
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                        },
                    },
                });
            }

            const financialCanvas = document.getElementById('report-financial-chart');

            if (financialCanvas) {
                const data = JSON.parse(financialCanvas.dataset.chart);

                renderChart('report-financial-chart', {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: @js(__('Sales')),
                                data: data.sales,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.12)',
                                fill: true,
                                tension: 0.3,
                                pointRadius: 2,
                            },
                            {
                                label: @js(__('Expenses')),
                                data: data.expenses,
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.08)',
                                fill: true,
                                tension: 0.3,
                                pointRadius: 2,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 12 },
                            },
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: { beginAtZero: true },
                        },
                    },
                });
            }

            const careCanvas = document.getElementById('report-care-chart');

            if (careCanvas) {
                const data = JSON.parse(careCanvas.dataset.chart);

                renderChart('report-care-chart', {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: @js(__('Care logs')),
                                data: data.totals,
                                backgroundColor: '#f59e0b',
                                borderRadius: 4,
                                maxBarThickness: 32,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: { beginAtZero: true, ticks: { precision: 0 } },
                        },
                    },
                });
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderReportCharts, { once: true });
        } else {
            renderReportCharts();
        }
    })();
</script>
@endscript
