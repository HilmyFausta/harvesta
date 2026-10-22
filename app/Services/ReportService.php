<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Models\Block;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the aggregated datasets and CSV rows used by F-10 Reports.
 */
class ReportService
{
    /**
     * Summarize harvests by block, tree type, or month.
     *
     * @return Collection<int, array{key: string, label: string, harvests: int, kg: float, pcs: float}>
     */
    public function harvestSummary(string $from, string $to, string $groupBy): Collection
    {
        $groupBy = in_array($groupBy, ['block', 'tree_type', 'month'], true) ? $groupBy : 'block';

        $query = DB::table('harvests')
            ->join('trees', 'trees.id', '=', 'harvests.tree_id')
            ->join('tree_types', 'tree_types.id', '=', 'trees.tree_type_id')
            ->leftJoin('blocks', 'blocks.id', '=', 'trees.block_id')
            ->whereBetween('harvests.harvested_on', [$from, $to]);

        $aggregates = "count(*) as harvests,
            coalesce(sum(case when harvests.unit = 'kg' then harvests.quantity else 0 end), 0) as kg,
            coalesce(sum(case when harvests.unit = 'pcs' then harvests.quantity else 0 end), 0) as pcs";

        if ($groupBy === 'month') {
            $query->selectRaw("to_char(harvests.harvested_on, 'YYYY-MM') as bucket, {$aggregates}")
                ->groupBy('bucket')
                ->orderBy('bucket');
        } elseif ($groupBy === 'tree_type') {
            $query->selectRaw("tree_types.name as bucket, {$aggregates}")
                ->groupBy('tree_types.id', 'tree_types.name')
                ->orderBy('tree_types.name');
        } else {
            $query->selectRaw("blocks.name as bucket, {$aggregates}")
                ->groupBy('blocks.id', 'blocks.name')
                ->orderBy('blocks.name');
        }

        return $query->get()->map(function ($row) use ($groupBy): array {
            $data = (array) $row;
            $bucket = (string) ($data['bucket'] ?? '');

            $label = match ($groupBy) {
                'month' => Carbon::parse($bucket.'-01')->format('M Y'),
                'tree_type' => $bucket !== '' ? $bucket : (string) __('Unknown tree type'),
                default => $bucket !== '' ? $bucket : (string) __('General (no block)'),
            };

            return [
                'key' => $bucket,
                'label' => $label,
                'harvests' => (int) ($data['harvests'] ?? 0),
                'kg' => (float) ($data['kg'] ?? 0),
                'pcs' => (float) ($data['pcs'] ?? 0),
            ];
        })->values();
    }

    /**
     * Summarize sales, expenses, and net profit for every month in the period.
     *
     * @return Collection<int, array{key: string, label: string, sales: float, expenses: float, net: float}>
     */
    public function financialSummary(string $from, string $to): Collection
    {
        $sales = DB::table('sales')
            ->whereBetween('sold_on', [$from, $to])
            ->selectRaw("to_char(sold_on, 'YYYY-MM') as bucket, sum(total_amount) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $expenses = DB::table('expenses')
            ->whereBetween('spent_on', [$from, $to])
            ->selectRaw("to_char(spent_on, 'YYYY-MM') as bucket, sum(amount) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        return collect($this->months($from, $to))->map(function (array $month) use ($sales, $expenses): array {
            $salesTotal = (float) ($sales[$month['key']] ?? 0);
            $expenseTotal = (float) ($expenses[$month['key']] ?? 0);

            return [
                'key' => $month['key'],
                'label' => $month['label'],
                'sales' => $salesTotal,
                'expenses' => $expenseTotal,
                'net' => round($salesTotal - $expenseTotal, 2),
            ];
        })->values();
    }

    /**
     * Summarize sales and expenses for every block touched by the period.
     *
     * @return Collection<int, array{label: string, sales: float, expenses: float, net: float}>
     */
    public function financialByBlock(string $from, string $to): Collection
    {
        $sales = [];

        foreach (DB::table('sales')
            ->join('harvests', 'harvests.id', '=', 'sales.harvest_id')
            ->join('trees', 'trees.id', '=', 'harvests.tree_id')
            ->whereBetween('sales.sold_on', [$from, $to])
            ->selectRaw('trees.block_id as bucket, sum(sales.total_amount) as total')
            ->groupBy('trees.block_id')
            ->get() as $row) {
            $data = (array) $row;
            $sales[(int) ($data['bucket'] ?? 0)] = (float) ($data['total'] ?? 0);
        }

        $expenses = [];

        foreach (DB::table('expenses')
            ->whereBetween('spent_on', [$from, $to])
            ->selectRaw('block_id as bucket, sum(amount) as total')
            ->groupBy('block_id')
            ->get() as $row) {
            $data = (array) $row;
            $expenses[(int) ($data['bucket'] ?? 0)] = (float) ($data['total'] ?? 0);
        }

        $ids = collect(array_keys($sales))
            ->merge(array_keys($expenses))
            ->unique()
            ->sort()
            ->values();

        $blockNames = Block::query()
            ->whereIn('id', $ids->filter(fn (int $id): bool => $id > 0)->all())
            ->pluck('name', 'id');

        return $ids->map(function (int $id) use ($sales, $expenses, $blockNames): array {
            $salesTotal = $sales[$id] ?? 0.0;
            $expenseTotal = $expenses[$id] ?? 0.0;

            return [
                'label' => $id === 0
                    ? (string) __('General (no block)')
                    : (string) ($blockNames[$id] ?? __('Unknown block')),
                'sales' => $salesTotal,
                'expenses' => $expenseTotal,
                'net' => round($salesTotal - $expenseTotal, 2),
            ];
        })->values();
    }

    /**
     * Summarize care activity by type.
     *
     * @return Collection<int, array{activity: string, label: string, total: int, last_performed: string|null}>
     */
    public function careByActivity(string $from, string $to): Collection
    {
        return DB::table('care_logs')
            ->whereBetween('performed_on', [$from, $to])
            ->selectRaw('activity_type, count(*) as total, max(performed_on) as last_performed')
            ->groupBy('activity_type')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row): array {
                $data = (array) $row;
                $activity = (string) ($data['activity_type'] ?? '');

                return [
                    'activity' => $activity,
                    'label' => ActivityType::tryFrom($activity)?->label() ?? $activity,
                    'total' => (int) ($data['total'] ?? 0),
                    'last_performed' => ($data['last_performed'] ?? null) !== null
                        ? (string) $data['last_performed']
                        : null,
                ];
            })
            ->values();
    }

    /**
     * Build the CSV headers and rows for a report.
     *
     * @return array{0: array<int, string>, 1: array<int, array<int, string|int|float>>}
     */
    public function exportData(string $report, string $from, string $to, string $groupBy): array
    {
        if ($report === 'financial') {
            $rows = [];

            foreach ($this->financialSummary($from, $to) as $row) {
                $rows[] = [
                    __('Month'),
                    $row['label'],
                    $this->decimal($row['sales']),
                    $this->decimal($row['expenses']),
                    $this->decimal($row['net']),
                ];
            }

            foreach ($this->financialByBlock($from, $to) as $row) {
                $rows[] = [
                    __('Block'),
                    $row['label'],
                    $this->decimal($row['sales']),
                    $this->decimal($row['expenses']),
                    $this->decimal($row['net']),
                ];
            }

            return [
                [__('Group'), __('Name'), __('Sales (IDR)'), __('Expenses (IDR)'), __('Net (IDR)')],
                $rows,
            ];
        }

        if ($report === 'care') {
            $rows = [];

            foreach ($this->careByActivity($from, $to) as $row) {
                $rows[] = [
                    $row['label'],
                    $row['total'],
                    $row['last_performed'] ?? '',
                ];
            }

            return [
                [__('Activity'), __('Care logs'), __('Last performed')],
                $rows,
            ];
        }

        $groupBy = in_array($groupBy, ['block', 'tree_type', 'month'], true) ? $groupBy : 'block';

        $groupHeader = match ($groupBy) {
            'tree_type' => __('Tree type'),
            'month' => __('Month'),
            default => __('Block'),
        };

        $rows = [];

        foreach ($this->harvestSummary($from, $to, $groupBy) as $row) {
            $rows[] = [
                $row['label'],
                $row['harvests'],
                $this->decimal($row['kg']),
                $this->decimal($row['pcs']),
            ];
        }

        return [
            [$groupHeader, __('Harvests'), __('Quantity (kg)'), __('Quantity (pcs)')],
            $rows,
        ];
    }

    /**
     * Build the month buckets between two dates.
     *
     * @return array<int, array{key: string, label: string}>
     */
    private function months(string $from, string $to): array
    {
        $start = Carbon::parse($from)->startOfMonth();
        $end = Carbon::parse($to)->startOfMonth();
        $months = [];

        while ($start->lte($end)) {
            $months[] = [
                'key' => $start->format('Y-m'),
                'label' => $start->format('M Y'),
            ];

            $start->addMonth();
        }

        return $months;
    }

    /**
     * Format a numeric value for CSV without thousands separators.
     */
    private function decimal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
