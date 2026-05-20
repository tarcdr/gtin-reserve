<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private function displayValue(mixed $value): string
    {
      return $value === null || $value === '' ? '-' : (string) $value;
    }

    private function displayMonth(mixed $value): string
    {
      if ($value === null || $value === '') {
        return '-';
      }

      $month = strtoupper(trim((string) $value));
      $map = [
        'JAN' => 'Jan.',
        'FEB' => 'Feb.',
        'MAR' => 'Mar.',
        'APR' => 'Apr.',
        'MAY' => 'May.',
        'JUN' => 'Jun.',
        'JUL' => 'Jul.',
        'AUG' => 'Aug.',
        'SEP' => 'Sep.',
        'OCT' => 'Oct.',
        'NOV' => 'Nov.',
        'DEC' => 'Dec.',
      ];

      return $map[$month] ?? ucfirst(strtolower($month));
    }

    private function fetchSummaryHead(): array
    {
      try {
        return DB::connection('oracle')
          ->table('proj1_2_summary_head')
          ->select(['NO', 'HEAD_LABEL'])
          ->orderBy('NO')
          ->get()
          ->map(function ($row) {
            $data = array_change_key_case((array) $row, CASE_LOWER);

            return [
              'no' => (int) ($data['no'] ?? 0),
              'head_label' => (string) ($data['head_label'] ?? ''),
            ];
          })
          ->filter(fn ($row) => $row['no'] > 0)
          ->values()
          ->all();
      } catch (\Throwable $exception) {
        return [];
      }
    }

    private function fetchSummaryDetail(): array
    {
      $emptyRow = [
        'DATA_YEAR' => '-',
        'DATA_MONTH' => '-',
        'NPD_AMT' => '-',
        'GTIN_PCS_AMT' => '-',
        'GTIN_BOX_AMT' => '-',
        'GTIN_CARTON_AMT' => '-',
        'GTIN_PACKAGE_AMT' => '-',
        'GTIN_PAIR_AMT' => '-',
        'GTIN_SET_AMT' => '-',
      ];

      try {
        $rows = DB::connection('oracle')
          ->table('proj1_2_summary_detail')
          ->select([
            'DATA_YEAR',
            'DATA_MONTH',
            'DATA_MONTH_ORDER',
            'NPD_AMT',
            'GTIN_PCS_AMT',
            'GTIN_BOX_AMT',
            'GTIN_CARTON_AMT',
            'GTIN_PACKAGE_AMT',
            'GTIN_PAIR_AMT',
            'GTIN_SET_AMT',
          ])
          ->orderByDesc('DATA_YEAR')
          ->orderByDesc('DATA_MONTH_ORDER')
          ->limit(12)
          ->get()
          ->map(function ($row) {
            $data = array_change_key_case((array) $row, CASE_LOWER);

            return [
              'DATA_YEAR' => $this->displayValue($data['data_year'] ?? null),
              'DATA_MONTH' => $this->displayMonth($data['data_month'] ?? null),
              'NPD_AMT' => $this->displayValue($data['npd_amt'] ?? null),
              'GTIN_PCS_AMT' => $this->displayValue($data['gtin_pcs_amt'] ?? null),
              'GTIN_BOX_AMT' => $this->displayValue($data['gtin_box_amt'] ?? null),
              'GTIN_CARTON_AMT' => $this->displayValue($data['gtin_carton_amt'] ?? null),
              'GTIN_PACKAGE_AMT' => $this->displayValue($data['gtin_package_amt'] ?? null),
              'GTIN_PAIR_AMT' => $this->displayValue($data['gtin_pair_amt'] ?? null),
              'GTIN_SET_AMT' => $this->displayValue($data['gtin_set_amt'] ?? null),
            ];
          })
          ->values()
          ->all();

        return array_pad($rows, 12, $emptyRow);
      } catch (\Throwable $exception) {
        return array_fill(0, 12, $emptyRow);
      }
    }

    public function view(Request $request): Response
    {
      return Inertia::render('Dashboard', [
        'message' => DB::connection('oracle')->table('proj1_dash')->value('message') ?: '',
        'summaryHead' => $this->fetchSummaryHead(),
        'summaryRows' => $this->fetchSummaryDetail(),
      ]);
    }
}
