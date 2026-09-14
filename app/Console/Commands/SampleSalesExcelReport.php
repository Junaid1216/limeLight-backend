<?php

namespace App\Console\Commands;

use App\Helpers\CommissionHelper;
use App\Models\Commission;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Slab;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class SampleSalesExcelReport extends Command
{
    protected $signature = 'report:sample-sales-excel
        {--output= : Full path for the .xlsx file}';

    protected $description = 'Auto-pick 5 salesperson codes (with/without data) for a 2–3 day window and export Excel';

    public function handle(): int
    {
        $output = $this->option('output')
            ?: (getenv('USERPROFILE') ?: 'C:\\Users\\Ranglerz') . '\\Downloads\\sales_staff_sample_report.xlsx';

        $namedWith = [
            'Abdul Basit',
            'Abdul Haseeb',
            'Abdul Rafay',
            'Abdul Rehman',
            'Abdul Rehman Khan',
        ];
        $namedWithout = [
            '46623' => 'Laiba',
            '45840' => 'Laraib Arshad',
        ];

        $resolved = $this->resolveNamedCodes($namedWith);
        $withCodes = [];
        $unresolvedNames = [];
        foreach ($resolved as $name => $codes) {
            if (empty($codes)) {
                $unresolvedNames[] = $name;
            } else {
                foreach ($codes as $code) {
                    $withCodes[$code] = $name;
                }
            }
        }
        $withCodesList = array_keys($withCodes);

        $this->info('Finding 2–3 day window with data for named staff...');
        [$from, $to] = $this->pickDateWindowForCodes($withCodesList);
        $this->info("Window: {$from->toDateString()} → {$to->toDateString()}");

        $this->info('With sales codes: ' . implode(', ', array_map(function ($c) use ($withCodes) {
            return $withCodes[$c] . ' (' . $c . ')';
        }, $withCodesList)));
        if ($unresolvedNames) {
            $this->warn('Not found in sale_items: ' . implode(', ', $unresolvedNames));
        }
        $this->info('No sales: 46623-Laiba, 45840-Laraib Arshad');

        $slabs = Slab::orderBy('from_amount')->get(['from_amount', 'to_amount', 'incentive_amount']);
        $withRows = $this->buildRowsForCodes($withCodesList, $from, $to, $slabs, $withCodes);

        // Named staff with zero rows in the window also go on No Sales, plus Laiba / Laraib.
        $codesWithRows = [];
        foreach ($withRows as $row) {
            if (preg_match('/\((\d+)\)\s*$/', (string) $row[5], $m)) {
                $codesWithRows[$m[1]] = true;
            }
        }
        $emptyNamed = [];
        foreach ($withCodes as $code => $name) {
            if (!isset($codesWithRows[$code])) {
                $emptyNamed[$code] = $name;
            }
        }
        foreach ($unresolvedNames as $name) {
            $emptyNamed['N/A-' . md5($name)] = $name . ' (code not in sale_items)';
        }

        $withoutRows = $this->buildEmptyRows(
            $namedWithout + $emptyNamed,
            $from,
            $to
        );

        $meta = [
            ['Sales Staff Sample Report'],
            ['Date range', $from->toDateString() . ' to ' . $to->toDateString()],
            ['Named staff (with data)', implode(', ', $namedWith)],
            ['Codes resolved', implode(', ', array_map(function ($c) use ($withCodes) {
                return $withCodes[$c] . '=' . $c;
            }, $withCodesList))],
            ['Not in sale_items', implode(', ', $unresolvedNames) ?: '-'],
            ['No-data users', '46623-Laiba, 45840-Laraib Arshad'],
            ['Generated at', Carbon::now()->toDateTimeString()],
        ];

        $headers = [
            'Sr',
            'Sales ID',
            'Invoice ID',
            'Branch',
            'Date',
            'Salesperson',
            'Quantity',
            'Amount',
            'Slip Bound Incentive',
            'Commission',
        ];

        $this->writeXlsx($output, [
            'Meta' => $meta,
            'With Sales' => array_merge([$headers], $withRows),
            'No Sales' => array_merge([$headers], $withoutRows),
        ]);

        $this->info("Excel written to: {$output}");
        $this->info('With Sales rows: ' . count($withRows));
        $this->info('No Sales rows: ' . count($withoutRows));

        return 0;
    }

    /**
     * Map display names to salesperson_code values found in sale_items.
     *
     * @param  string[]  $names
     * @return array<string, string[]>
     */
    private function resolveNamedCodes(array $names): array
    {
        $out = [];
        foreach ($names as $name) {
            $out[$name] = $this->codesForName($name);
        }

        return $out;
    }

    /**
     * @return string[]
     */
    private function codesForName(string $name): array
    {
        $needle = strtolower(trim($name));

        $rows = DB::table('sale_items')
            ->select('salesperson_code', 'salesperson_name')
            ->whereNotNull('salesperson_code')
            ->where('salesperson_code', '!=', '')
            ->whereNotNull('salesperson_name')
            ->whereRaw('LOWER(salesperson_name) LIKE ?', ['%' . $needle . '%'])
            ->groupBy('salesperson_code', 'salesperson_name')
            ->get();

        // Prefer exact (case-insensitive) name match.
        $exact = $rows->filter(function ($r) use ($needle) {
            return strtolower(trim((string) $r->salesperson_name)) === $needle;
        });

        if ($exact->isNotEmpty()) {
            return $exact->pluck('salesperson_code')->map(function ($c) {
                return trim((string) $c);
            })->unique()->values()->all();
        }

        // "Abdul Rehman" must not pull "Abdul Rehman Kiyani" / "Khan".
        if ($needle === 'abdul rehman') {
            $exactRehman = $rows->filter(function ($r) {
                return strtolower(trim((string) $r->salesperson_name)) === 'abdul rehman';
            });

            return $exactRehman->pluck('salesperson_code')->map(function ($c) {
                return trim((string) $c);
            })->unique()->values()->all();
        }

        return $rows->pluck('salesperson_code')->map(function ($c) {
            return trim((string) $c);
        })->unique()->values()->all();
    }

    /**
     * Prefer a consecutive 2–3 day Sep 2026 window with the most invoices for given codes.
     *
     * @param  string[]  $codes
     * @return array{0: Carbon, 1: Carbon}
     */
    private function pickDateWindowForCodes(array $codes): array
    {
        if (empty($codes)) {
            return $this->pickDateWindow();
        }

        $days = DB::table('sales as s')
            ->join('sale_items as si', 'si.invoice_id', '=', 's.invoice_id')
            ->whereIn('si.salesperson_code', $codes)
            ->where('s.date', '>=', '2026-09-01')
            ->where('s.date', '<', '2026-10-01')
            ->selectRaw('LEFT(s.date, 10) as d, COUNT(DISTINCT s.invoice_id) as c')
            ->groupBy(DB::raw('LEFT(s.date, 10)'))
            ->orderBy('d')
            ->pluck('c', 'd')
            ->all();

        if (empty($days)) {
            return $this->pickDateWindow();
        }

        $dates = array_keys($days);
        $bestFrom = $dates[0];
        $bestTo = $dates[0];
        $bestSum = -1;
        $span = min(3, count($dates));

        for ($i = 0; $i <= count($dates) - $span; $i++) {
            $window = array_slice($dates, $i, $span);
            $ok = true;
            for ($j = 1; $j < count($window); $j++) {
                if (Carbon::parse($window[$j])->diffInDays(Carbon::parse($window[$j - 1])) !== 1) {
                    $ok = false;
                    break;
                }
            }
            if (!$ok) {
                continue;
            }
            $sum = 0;
            foreach ($window as $d) {
                $sum += (int) $days[$d];
            }
            if ($sum > $bestSum) {
                $bestSum = $sum;
                $bestFrom = $window[0];
                $bestTo = $window[count($window) - 1];
            }
        }

        return [
            Carbon::parse($bestFrom)->startOfDay(),
            Carbon::parse($bestTo)->endOfDay(),
        ];
    }

    /**
     * Prefer a consecutive 3-day window in Sep 2026 with the highest invoice volume.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function pickDateWindow(): array
    {
        $days = DB::table('sales')
            ->selectRaw('LEFT(`date`, 10) as d, COUNT(*) as c')
            ->where('date', '>=', '2026-09-01')
            ->where('date', '<', '2026-10-01')
            ->groupBy(DB::raw('LEFT(`date`, 10)'))
            ->orderBy('d')
            ->pluck('c', 'd')
            ->all();

        if (empty($days)) {
            throw new \RuntimeException('No sales found for September 2026.');
        }

        $dates = array_keys($days);
        $bestFrom = $dates[0];
        $bestTo = $dates[0];
        $bestSum = -1;
        $span = min(3, count($dates));

        for ($i = 0; $i <= count($dates) - $span; $i++) {
            $window = array_slice($dates, $i, $span);
            // Require consecutive calendar days
            $ok = true;
            for ($j = 1; $j < count($window); $j++) {
                if (Carbon::parse($window[$j])->diffInDays(Carbon::parse($window[$j - 1])) !== 1) {
                    $ok = false;
                    break;
                }
            }
            if (!$ok) {
                continue;
            }
            $sum = 0;
            foreach ($window as $d) {
                $sum += (int) $days[$d];
            }
            if ($sum > $bestSum) {
                $bestSum = $sum;
                $bestFrom = $window[0];
                $bestTo = $window[count($window) - 1];
            }
        }

        $from = Carbon::parse($bestFrom)->startOfDay();
        $to = Carbon::parse($bestTo)->endOfDay();

        return [$from, $to];
    }

    /**
     * @return array{0: string[], 1: string[]}
     */
    private function pickSalespersonCodes(Carbon $from, Carbon $to): array
    {
        $fromStr = $from->format('Y-m-d H:i:s');
        $toStr = $to->format('Y-m-d H:i:s');

        // Avoid heavy JOIN+GROUP temp files: pull invoice ids first, then tally codes in PHP.
        $invoiceIds = DB::table('sales')
            ->where('date', '>=', $fromStr)
            ->where('date', '<=', $toStr)
            ->pluck('invoice_id')
            ->all();

        if (empty($invoiceIds)) {
            throw new \RuntimeException('No invoices in the selected window.');
        }

        $counts = [];
        foreach (array_chunk($invoiceIds, 500) as $chunk) {
            $rows = DB::table('sale_items')
                ->select('salesperson_code', 'invoice_id')
                ->whereIn('invoice_id', $chunk)
                ->whereNotNull('salesperson_code')
                ->where('salesperson_code', '!=', '')
                ->get();

            foreach ($rows as $row) {
                $code = trim((string) $row->salesperson_code);
                if (!$this->isUsableCode($code)) {
                    continue;
                }
                if (!isset($counts[$code])) {
                    $counts[$code] = [];
                }
                $counts[$code][(string) $row->invoice_id] = true;
            }
        }

        $ranked = [];
        foreach ($counts as $code => $invoices) {
            $ranked[$code] = count($invoices);
        }
        arsort($ranked);
        $with = array_slice(array_keys($ranked), 0, 3);

        $activeSet = array_fill_keys($with, true);
        $without = [];
        DB::table('sale_items')
            ->select('salesperson_code')
            ->whereNotNull('salesperson_code')
            ->where('salesperson_code', '!=', '')
            ->groupBy('salesperson_code')
            ->orderBy('salesperson_code')
            ->chunk(200, function ($rows) use (&$without, $activeSet, $counts) {
                foreach ($rows as $row) {
                    if (count($without) >= 2) {
                        return false;
                    }
                    $code = trim((string) $row->salesperson_code);
                    if (!$this->isUsableCode($code) || isset($activeSet[$code])) {
                        continue;
                    }
                    if (!isset($counts[$code])) {
                        $without[] = $code;
                    }
                }
            });

        if (count($with) < 1) {
            throw new \RuntimeException('Could not find salesperson codes with sales in the window.');
        }

        if (count($without) < 2) {
            $extra = DB::table('sale_items')
                ->whereNotNull('salesperson_code')
                ->where('salesperson_code', '!=', '')
                ->whereNotIn('salesperson_code', array_merge($with, $without) ?: ['__none__'])
                ->groupBy('salesperson_code')
                ->limit(2 - count($without))
                ->pluck('salesperson_code')
                ->map(function ($c) {
                    return trim((string) $c);
                })
                ->all();
            $without = array_values(array_unique(array_merge($without, $extra)));
        }

        return [$with, $without];
    }

    private function isUsableCode(string $code): bool
    {
        // Prefer real employee-style codes; skip placeholders like "1", "01".
        return (bool) preg_match('/^\d{4,}$/', $code);
    }

    private function staffRateAt($at): float
    {
        if (Schema::hasTable('commission_histories')) {
            return CommissionHelper::rateFor('sales_staff', $at);
        }

        return (float) (Commission::where('role', 'sales_staff')->value('commission') ?? 0);
    }

    private function buildRowsForCodes(array $codes, Carbon $from, Carbon $to, $slabs, array $preferredNames = []): array
    {
        $rows = [];
        $sr = 0;
        $rateCache = [];

        foreach ($codes as $code) {
            $sales = Sale::with(['items' => function ($q) use ($code) {
                $q->where('salesperson_code', $code)
                    ->select(['id', 'invoice_id', 'quantity', 'price', 'salesperson_code', 'salesperson_name']);
            }])
                ->whereHas('items', function ($q) use ($code) {
                    $q->where('salesperson_code', $code);
                })
                ->where('date', '>=', $from->format('Y-m-d H:i:s'))
                ->where('date', '<=', $to->format('Y-m-d H:i:s'))
                ->orderBy('date')
                ->get(['id', 'sales_id', 'invoice_id', 'shop_name', 'date', 'net_total']);

            foreach ($sales as $sale) {
                $qty = $sale->items->sum(function ($item) {
                    return max(0, (int) $item->quantity);
                });
                $amount = $sale->items->sum(function ($item) {
                    return (float) $item->price * max(0, (int) $item->quantity);
                });

                $dateKey = (string) $sale->date;
                if (!array_key_exists($dateKey, $rateCache)) {
                    $rateCache[$dateKey] = $this->staffRateAt($sale->date);
                }
                $rate = $rateCache[$dateKey];
                $commission = round($sale->items->sum(function ($item) use ($rate) {
                    return (max(0, (float) $item->quantity) * max(0, (float) $item->price) * $rate) / 100;
                }), 2);

                $netTotal = (float) $sale->net_total;
                $slipBoundIncentive = 0.0;
                foreach ($slabs as $slab) {
                    if ($netTotal >= (float) $slab->from_amount && $netTotal <= (float) $slab->to_amount) {
                        $slipBoundIncentive = (float) ($slab->incentive_amount ?? 0);
                        break;
                    }
                }

                $name = $preferredNames[$code]
                    ?? ($sale->items->pluck('salesperson_name')->filter()->first() ?: '');
                $salesperson = trim($name) !== ''
                    ? trim($name) . ' (' . $code . ')'
                    : $code;

                $sr++;
                $rows[] = [
                    $sr,
                    $sale->sales_id,
                    $sale->invoice_id,
                    $sale->shop_name,
                    $sale->date,
                    $salesperson,
                    $qty,
                    round($amount, 2),
                    $slipBoundIncentive,
                    $commission,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, string>|string[]  $codes  code => display name, or list of codes
     */
    private function buildEmptyRows($codes, Carbon $from, Carbon $to): array
    {
        $rows = [];
        $sr = 0;
        $rangeNote = $from->toDateString() . ' to ' . $to->toDateString();

        $map = [];
        if (array_keys($codes) !== range(0, count($codes) - 1)) {
            $map = $codes;
        } else {
            foreach ($codes as $code) {
                $map[$code] = null;
            }
        }

        foreach ($map as $code => $name) {
            $displayCode = preg_match('/^N\/A-/', (string) $code) ? '' : $code;
            if ($name === null || $name === '') {
                $name = SaleItem::where('salesperson_code', $code)
                    ->whereNotNull('salesperson_name')
                    ->where('salesperson_name', '!=', '')
                    ->value('salesperson_name');
            }
            $salesperson = $displayCode !== ''
                ? (trim((string) $name) !== '' ? trim($name) . ' (' . $displayCode . ')' : $displayCode)
                : (string) $name;

            $sr++;
            $rows[] = [
                $sr,
                '',
                '',
                '',
                $rangeNote . ' (no sales)',
                $salesperson,
                0,
                0,
                0,
                0,
            ];
        }

        return $rows;
    }

    /**
     * XLSX writer styled like C12 export: MS Sans Serif 8pt, header row, alternating blue rows.
     *
     * @param  array<string, array<int, array<int, mixed>>>  $sheets
     */
    private function writeXlsx(string $path, array $sheets): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $tmpBase = is_dir('D:/mysql_tmp') ? 'D:/mysql_tmp' : sys_get_temp_dir();
        $tmp = rtrim($tmpBase, '\\/') . DIRECTORY_SEPARATOR . 'xlsx_' . uniqid('', true);
        mkdir($tmp);
        mkdir($tmp . '/_rels');
        mkdir($tmp . '/xl');
        mkdir($tmp . '/xl/_rels');
        mkdir($tmp . '/xl/worksheets');
        mkdir($tmp . '/xl/theme');

        file_put_contents($tmp . '/xl/styles.xml', $this->stylesXml());
        file_put_contents($tmp . '/xl/theme/theme1.xml', $this->themeXml());

        $sheetNames = array_keys($sheets);
        $sheetFiles = [];

        $i = 1;
        foreach ($sheetNames as $name) {
            $sheetFiles[$name] = 'worksheets/sheet' . $i . '.xml';
            $isMeta = (strcasecmp($name, 'Meta') === 0);
            file_put_contents(
                $tmp . '/xl/' . $sheetFiles[$name],
                $this->sheetXml($sheets[$name], $isMeta)
            );
            $i++;
        }

        $workbookSheets = '';
        $i = 1;
        foreach ($sheetNames as $name) {
            $safe = htmlspecialchars($name, ENT_XML1);
            $workbookSheets .= '<sheet name="' . $safe . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
            $i++;
        }

        file_put_contents($tmp . '/xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $workbookSheets . '</sheets></workbook>'
        );

        $rels = '';
        $i = 1;
        foreach ($sheetNames as $name) {
            $rels .= '<Relationship Id="rId' . $i . '"'
                . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                . ' Target="' . $sheetFiles[$name] . '"/>';
            $i++;
        }
        $rels .= '<Relationship Id="rId' . $i . '"'
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"'
            . ' Target="styles.xml"/>';
        $i++;
        $rels .= '<Relationship Id="rId' . $i . '"'
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme"'
            . ' Target="theme/theme1.xml"/>';

        file_put_contents($tmp . '/xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels . '</Relationships>'
        );

        file_put_contents($tmp . '/_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1"'
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"'
            . ' Target="xl/workbook.xml"/></Relationships>'
        );

        $overrides = '<Override PartName="/xl/workbook.xml"'
            . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml"'
            . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/theme/theme1.xml"'
            . ' ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>';
        foreach ($sheetNames as $name) {
            $overrides .= '<Override PartName="/xl/' . $sheetFiles[$name] . '"'
                . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        file_put_contents($tmp . '/[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . $overrides . '</Types>'
        );

        if (is_file($path)) {
            unlink($path);
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Could not create zip at ' . $path);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tmp, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $full = $file->getPathname();
            $local = str_replace('\\', '/', substr($full, strlen($tmp) + 1));
            $zip->addFile($full, $local);
        }
        $zip->close();

        $this->deleteDir($tmp);
    }

    private function stylesXml(): string
    {
        // Mirrors C12-01-07-Aug.xlsx look: MS Sans Serif 8pt, header, alternating #ECF4FF rows.
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3">'
            . '<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><sz val="8"/><color indexed="12"/><name val="Microsoft Sans Serif"/></font>'
            . '<font><sz val="8"/><color indexed="8"/><name val="Microsoft Sans Serif"/></font>'
            . '</fonts>'
            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFECF4FF"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFFFC0"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="5">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function themeXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Office Theme">'
            . '<a:themeElements><a:clrScheme name="Office">'
            . '<a:dk1><a:sysClr val="windowText" lastClr="000000"/></a:dk1>'
            . '<a:lt1><a:sysClr val="window" lastClr="FFFFFF"/></a:lt1>'
            . '<a:dk2><a:srgbClr val="0E2841"/></a:dk2>'
            . '<a:lt2><a:srgbClr val="E8E8E8"/></a:lt2>'
            . '<a:accent1><a:srgbClr val="156082"/></a:accent1>'
            . '<a:accent2><a:srgbClr val="E97132"/></a:accent2>'
            . '<a:accent3><a:srgbClr val="196B24"/></a:accent3>'
            . '<a:accent4><a:srgbClr val="0F9ED5"/></a:accent4>'
            . '<a:accent5><a:srgbClr val="A02B93"/></a:accent5>'
            . '<a:accent6><a:srgbClr val="4EA72E"/></a:accent6>'
            . '<a:hlink><a:srgbClr val="467886"/></a:hlink>'
            . '<a:folHlink><a:srgbClr val="96607D"/></a:folHlink>'
            . '</a:clrScheme>'
            . '<a:fontScheme name="Office">'
            . '<a:majorFont><a:latin typeface="Aptos Display"/><a:ea typeface=""/><a:cs typeface=""/></a:majorFont>'
            . '<a:minorFont><a:latin typeface="Aptos Narrow"/><a:ea typeface=""/><a:cs typeface=""/></a:minorFont>'
            . '</a:fontScheme>'
            . '<a:fmtScheme name="Office"><a:fillStyleLst>'
            . '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'
            . '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'
            . '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'
            . '</a:fillStyleLst><a:lnStyleLst>'
            . '<a:ln w="12700"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln>'
            . '<a:ln w="12700"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln>'
            . '<a:ln w="12700"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln>'
            . '</a:lnStyleLst><a:effectStyleLst>'
            . '<a:effectStyle><a:effectLst/></a:effectStyle>'
            . '<a:effectStyle><a:effectLst/></a:effectStyle>'
            . '<a:effectStyle><a:effectLst/></a:effectStyle>'
            . '</a:effectStyleLst><a:bgFillStyleLst>'
            . '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'
            . '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'
            . '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'
            . '</a:bgFillStyleLst></a:fmtScheme></a:themeElements></a:theme>';
    }

    private function sheetXml(array $rows, bool $isMeta = false): string
    {
        $colCount = 0;
        foreach ($rows as $row) {
            $colCount = max($colCount, count($row));
        }
        if ($colCount < 1) {
            $colCount = 1;
        }

        $lastCol = $this->colName($colCount - 1);
        $lastRow = max(1, count($rows));

        $colsXml = '<cols>';
        for ($c = 1; $c <= $colCount; $c++) {
            // Similar widths to C12 export (branch/name wider, numbers narrower).
            $width = 14.28515625;
            if ($c === 4 || $c === 6) {
                $width = 28.5703125;
            } elseif ($c === 1) {
                $width = 8;
            } elseif ($c === 5) {
                $width = 20;
            }
            $colsXml .= '<col min="' . $c . '" max="' . $c . '" width="' . $width . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:' . $lastCol . $lastRow . '"/>'
            . '<sheetViews><sheetView workbookViewId="0">'
            . ($isMeta ? '' : '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>')
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . $colsXml
            . '<sheetData>';

        $r = 1;
        foreach ($rows as $rowIndex => $row) {
            $xml .= '<row r="' . $r . '">';
            $c = 0;
            foreach ($row as $value) {
                $col = $this->colName($c) . $r;
                if ($isMeta) {
                    $style = ($c === 0) ? 1 : 2;
                } elseif ($rowIndex === 0) {
                    $style = 1; // header
                } else {
                    // Alternating rows like C12 (even white / odd light blue).
                    $style = ($rowIndex % 2 === 1) ? 2 : 3;
                }

                if (is_int($value) || is_float($value)) {
                    $xml .= '<c r="' . $col . '" s="' . $style . '"><v>' . $value . '</v></c>';
                } else {
                    $text = htmlspecialchars((string) $value, ENT_XML1);
                    $xml .= '<c r="' . $col . '" s="' . $style . '" t="inlineStr"><is><t>' . $text . '</t></is></c>';
                }
                $c++;
            }
            $xml .= '</row>';
            $r++;
        }

        $xml .= '</sheetData>'
            . '<pageMargins left="0.75" right="0.75" top="1" bottom="1" header="0.5" footer="0.5"/>'
            . '<autoFilter ref="A1:' . $lastCol . $lastRow . '"/>'
            . '</worksheet>';

        // Meta sheets shouldn't get autoFilter on short key/value lists.
        if ($isMeta) {
            $xml = str_replace(
                '<autoFilter ref="A1:' . $lastCol . $lastRow . '"/>',
                '',
                $xml
            );
        }

        return $xml;
    }

    private function colName(int $index): string
    {
        $name = '';
        $i = $index;
        while ($i >= 0) {
            $name = chr(($i % 26) + 65) . $name;
            $i = intdiv($i, 26) - 1;
        }

        return $name;
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
