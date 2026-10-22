<?php

use App\Enums\HarvestUnit;
use App\Enums\TreeStatus;
use App\Models\Block;
use App\Models\CareLog;
use App\Models\Harvest;
use App\Models\Tree;
use App\Support\Format;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    public function render()
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $treeCounts = Tree::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $totalTrees = (int) $treeCounts->sum();

        $monthHarvest = DB::table('harvests')
            ->whereBetween('harvested_on', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->selectRaw('unit, sum(quantity) as total')
            ->groupBy('unit')
            ->pluck('total', 'unit');

        $monthKg = (float) ($monthHarvest[HarvestUnit::Kg->value] ?? 0);
        $monthPcs = (float) ($monthHarvest[HarvestUnit::Pcs->value] ?? 0);

        $upcomingQuery = CareLog::query()
            ->whereNotNull('next_due_on')
            ->whereDate('next_due_on', '<=', today()->addDays(7));

        $upcomingCareCount = (clone $upcomingQuery)->count();

        $upcomingCare = $upcomingQuery
            ->with('tree')
            ->orderBy('next_due_on')
            ->limit(10)
            ->get();

        $recentHarvests = Harvest::query()
            ->with(['tree.treeType', 'tree.block'])
            ->latest('harvested_on')
            ->latest('id')
            ->limit(5)
            ->get();

        $periods = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);

            $periods[$month->format('Y-m')] = $month->format('M Y');
        }

        $chartStart = now()->subMonths(11)->startOfMonth()->toDateString();

        $harvestRows = DB::table('harvests')
            ->where('harvested_on', '>=', $chartStart)
            ->selectRaw("to_char(harvested_on, 'YYYY-MM') as month, unit, sum(quantity) as total")
            ->groupBy('month', 'unit')
            ->get();

        $harvestChart = ['labels' => array_values($periods)];

        foreach (HarvestUnit::cases() as $unit) {
            $harvestChart[$unit->value] = [];

            foreach (array_keys($periods) as $month) {
                $row = $harvestRows->first(
                    fn ($row): bool => $row->month === $month && $row->unit === $unit->value,
                );

                $harvestChart[$unit->value][] = (float) ($row->total ?? 0);
            }
        }

        $finance = null;
        $financeChart = null;

        if (Gate::allows('admin')) {
            $expenseRows = DB::table('expenses')
                ->where('spent_on', '>=', $chartStart)
                ->selectRaw("to_char(spent_on, 'YYYY-MM') as month, sum(amount) as total")
                ->groupBy('month')
                ->pluck('total', 'month');

            $salesRows = DB::table('sales')
                ->where('sold_on', '>=', $chartStart)
                ->selectRaw("to_char(sold_on, 'YYYY-MM') as month, sum(total_amount) as total")
                ->groupBy('month')
                ->pluck('total', 'month');

            $finance = [
                'sales' => (float) DB::table('sales')
                    ->whereBetween('sold_on', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->sum('total_amount'),
                'expenses' => (float) DB::table('expenses')
                    ->whereBetween('spent_on', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->sum('amount'),
            ];

            $finance['net'] = $finance['sales'] - $finance['expenses'];

            $financeChart = ['labels' => array_values($periods), 'sales' => [], 'expenses' => []];

            foreach (array_keys($periods) as $month) {
                $financeChart['sales'][] = (float) ($salesRows[$month] ?? 0);
                $financeChart['expenses'][] = (float) ($expenseRows[$month] ?? 0);
            }
        }

        return $this->view([
            'treeCounts' => $treeCounts,
            'totalTrees' => $totalTrees,
            'blocksCount' => Block::query()->count(),
            'monthKg' => $monthKg,
            'monthPcs' => $monthPcs,
            'upcomingCare' => $upcomingCare,
            'upcomingCareCount' => $upcomingCareCount,
            'recentHarvests' => $recentHarvests,
            'harvestChart' => $harvestChart,
            'finance' => $finance,
            'financeChart' => $financeChart,
            'monthLabel' => now()->format('F Y'),
        ]);
    }
}; ?>

<section class="w-full space-y-6">
    <x-page-header :title="__('Dashboard')" :description="__('An overview of your farm, harvests, and care schedule.')">
        @can('admin')
            <flux:button :href="route('reports.index')" icon="document-chart-bar" wire:navigate>
                {{ __('View reports') }}
            </flux:button>
        @endcan
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <flux:text>{{ __('Total trees') }}</flux:text>
                    <p class="mt-2 text-3xl font-semibold text-zinc-800 dark:text-white">{{ Format::number($totalTrees, 0) }}</p>
                    <p class="mt-1 text-xs text-zinc-500">
                        {{ __(':productive productive · :attention need attention', [
                            'productive' => (int) ($treeCounts[TreeStatus::Productive->value] ?? 0),
                            'attention' => (int) ($treeCounts[TreeStatus::NeedsAttention->value] ?? 0),
                        ]) }}
                    </p>
                </div>

                <div class="rounded-lg bg-lime-100 p-2 text-lime-700 dark:bg-lime-400/10 dark:text-lime-400">
                    <flux:icon name="squares-2x2" class="size-5" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <flux:text>{{ __('Blocks') }}</flux:text>
                    <p class="mt-2 text-3xl font-semibold text-zinc-800 dark:text-white">{{ Format::number($blocksCount, 0) }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('Planting areas') }}</p>
                </div>

                <div class="rounded-lg bg-sky-100 p-2 text-sky-700 dark:bg-sky-400/10 dark:text-sky-400">
                    <flux:icon name="map" class="size-5" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <flux:text>{{ __('Harvest this month') }}</flux:text>
                    <p class="mt-2 text-3xl font-semibold text-zinc-800 dark:text-white">{{ Format::number($monthKg, 0) }} kg</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ __(':count pcs · :month', ['count' => Format::number($monthPcs, 0), 'month' => $monthLabel]) }}</p>
                </div>

                <div class="rounded-lg bg-amber-100 p-2 text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
                    <flux:icon name="archive-box" class="size-5" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <flux:text>{{ __('Upcoming care') }}</flux:text>
                    <p class="mt-2 text-3xl font-semibold text-zinc-800 dark:text-white">{{ Format::number($upcomingCareCount, 0) }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('Overdue or due within 7 days') }}</p>
                </div>

                <div class="rounded-lg bg-orange-100 p-2 text-orange-700 dark:bg-orange-400/10 dark:text-orange-400">
                    <flux:icon name="wrench-screwdriver" class="size-5" />
                </div>
            </div>
        </div>
    </div>

    @if ($finance)
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>{{ __('Sales this month') }}</flux:text>
                <p class="mt-2 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">{{ Format::money($finance['sales']) }}</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>{{ __('Expenses this month') }}</flux:text>
                <p class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">{{ Format::money($finance['expenses']) }}</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>{{ __('Net this month') }}</flux:text>
                <p class="mt-2 text-2xl font-semibold {{ $finance['net'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ Format::money($finance['net']) }}
                </p>
            </div>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 xl:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Harvest trend') }}</h2>
                <flux:text>{{ __('Last 12 months') }}</flux:text>
            </div>

            <div class="h-72">
                <canvas id="harvest-chart" data-chart="{{ json_encode($harvestChart) }}" role="img" aria-label="{{ __('Harvest trend for the last 12 months') }}"></canvas>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Tree status') }}</h2>

            <div class="space-y-3">
                @foreach (TreeStatus::cases() as $status)
                    <div class="flex items-center justify-between gap-3">
                        <x-tree-status-badge :status="$status" />
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ Format::number((int) ($treeCounts[$status->value] ?? 0), 0) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if ($financeChart)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Sales and expenses') }}</h2>
                <flux:text>{{ __('Last 12 months') }}</flux:text>
            </div>

            <div class="h-72">
                <canvas id="finance-chart" data-chart="{{ json_encode($financeChart) }}" role="img" aria-label="{{ __('Sales and expenses for the last 12 months') }}"></canvas>
            </div>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        @include('pages.dashboard.partials.upcoming-care')

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-zinc-800 dark:text-white">{{ __('Recent harvests') }}</h2>

                <flux:button :href="route('harvests.index')" variant="ghost" size="sm" wire:navigate>
                    {{ __('View all') }}
                </flux:button>
            </div>

            @if ($recentHarvests->isEmpty())
                <div class="py-8 text-center text-zinc-500">
                    <p>{{ __('No harvests recorded yet.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-zinc-200 dark:border-zinc-700">
                            <tr>
                                <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ __('Date') }}</th>
                                <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ __('Tree') }}</th>
                                <th class="pb-2 text-right font-medium text-zinc-600 dark:text-zinc-400">{{ __('Quantity') }}</th>
                                <th class="pb-2 text-left font-medium text-zinc-600 dark:text-zinc-400">{{ __('Grade') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($recentHarvests as $harvest)
                                <tr>
                                    <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ Format::date($harvest->harvested_on) }}</td>
                                    <td class="py-3 font-medium text-zinc-800 dark:text-white">{{ $harvest->tree->code }}</td>
                                    <td class="py-3 text-right text-zinc-600 dark:text-zinc-400">{{ Format::number($harvest->quantity) }} {{ $harvest->unit->label() }}</td>
                                    <td class="py-3">
                                        <flux:badge size="sm" color="zinc">{{ $harvest->grade->label() }}</flux:badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</section>

@script
<script>
    (() => {
        const renderDashboardCharts = () => {
            if (!window.Chart) {
                return;
            }

            const harvestCanvas = document.getElementById('harvest-chart');

            if (harvestCanvas) {
                const chart = JSON.parse(harvestCanvas.dataset.chart);

                new window.Chart(harvestCanvas, {
                    type: 'bar',
                    data: {
                        labels: chart.labels,
                        datasets: [
                            {
                                label: @js(__('kg')),
                                data: chart.kg,
                                backgroundColor: '#84cc16',
                                borderRadius: 4,
                                maxBarThickness: 24,
                            },
                            {
                                label: @js(__('pcs')),
                                data: chart.pcs,
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

            const financeCanvas = document.getElementById('finance-chart');

            if (financeCanvas) {
                const chart = JSON.parse(financeCanvas.dataset.chart);

                new window.Chart(financeCanvas, {
                    type: 'line',
                    data: {
                        labels: chart.labels,
                        datasets: [
                            {
                                label: @js(__('Sales')),
                                data: chart.sales,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.12)',
                                fill: true,
                                tension: 0.3,
                                pointRadius: 2,
                            },
                            {
                                label: @js(__('Expenses')),
                                data: chart.expenses,
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
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderDashboardCharts, { once: true });
        } else {
            renderDashboardCharts();
        }
    })();
</script>
@endscript
