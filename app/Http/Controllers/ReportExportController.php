<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): StreamedResponse
    {
        $data = $request->validate([
            'report' => ['required', Rule::in(['harvest', 'financial', 'care'])],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'group_by' => ['nullable', Rule::in(['block', 'tree_type', 'month'])],
        ]);

        $report = (string) $data['report'];
        $from = (string) $data['from'];
        $to = (string) $data['to'];
        $groupBy = (string) ($data['group_by'] ?? 'block');

        [$headers, $rows] = $reports->exportData($report, $from, $to, $groupBy);

        $filename = sprintf('%s-report-%s-to-%s.csv', $report, $from, $to);

        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers, ',', '"', '');

            foreach ($rows as $row) {
                fputcsv($handle, $row, ',', '"', '');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
