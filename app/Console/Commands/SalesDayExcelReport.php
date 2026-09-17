<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class SalesDayExcelReport extends Command
{
    protected $signature = 'report:sales-day-excel
        {--date=2026-09-16 : Date Y-m-d}
        {--rate=0.25 : Sales staff commission rate percent}
        {--output= : Full path for the .xlsx file}';

    protected $description = 'Export one day of sales (invoice × salesperson) with Sales History amount/commission math in C12 format';

    public function handle(): int
    {
        $day = $this->option('date');
        $rate = (float) $this->option('rate');
        $from = $day . ' 00:00:00';
        $to = $day . ' 23:59:59';
        $output = $this->option('output')
            ?: ('D:\\sales_' . str_replace('-', '', $day) . '.xlsx');

        $this->info("Exporting {$day} (staff rate {$rate}%) ...");
        $this->info("Output: {$output}");

        $tmpBase = is_dir('D:/mysql_tmp') ? 'D:/mysql_tmp' : sys_get_temp_dir();
        $work = rtrim($tmpBase, '\\/') . DIRECTORY_SEPARATOR . 'xlsx_day_' . uniqid('', true);
        mkdir($work);
        mkdir($work . '/_rels');
        mkdir($work . '/xl');
        mkdir($work . '/xl/_rels');
        mkdir($work . '/xl/worksheets');
        mkdir($work . '/xl/theme');

        file_put_contents($work . '/xl/styles.xml', $this->stylesXml());
        file_put_contents($work . '/xl/theme/theme1.xml', $this->themeXml());

        $sheetPath = $work . '/xl/worksheets/sheet1.xml';
        $fh = fopen($sheetPath, 'wb');
        if (!$fh) {
            throw new \RuntimeException('Cannot open sheet file for writing');
        }

        $headers = [
            'Sale ID',
            'Invoice ID',
            'Branch',
            'Date',
            'Salesperson Name',
            'Product',
            'Quantity',
            'Amount',
            'Commission',
        ];
        $colCount = count($headers);
        $lastCol = $this->colName($colCount - 1);

        $bodyPath = $work . '/sheet_body.xml';
        $body = fopen($bodyPath, 'wb');

        fwrite($body, '<row r="1">');
        foreach ($headers as $i => $h) {
            $ref = $this->colName($i) . '1';
            fwrite($body, '<c r="' . $ref . '" s="1" t="inlineStr"><is><t>'
                . htmlspecialchars($h, ENT_XML1) . '</t></is></c>');
        }
        fwrite($body, '</row>');

        // Same math as Sales History: amount = (price - discount) * qty
        // commission = amount * rate / 100
        // Exclude Online Store* branches; Product = distinct product names on the invoice×salesperson group
        $sql = "
            SELECT
                s.sales_id,
                s.invoice_id,
                s.shop_name,
                s.date,
                si.salesperson_code,
                MAX(si.salesperson_name) AS salesperson_name,
                GROUP_CONCAT(DISTINCT NULLIF(TRIM(si.product_name), '') ORDER BY si.product_name SEPARATOR ', ') AS products,
                SUM(CASE WHEN si.quantity > 0 THEN si.quantity ELSE 0 END) AS qty,
                SUM(
                    (GREATEST(COALESCE(si.price, 0), 0) - GREATEST(COALESCE(si.discount, 0), 0))
                    * (CASE WHEN si.quantity > 0 THEN si.quantity ELSE 0 END)
                ) AS amount
            FROM sales s
            INNER JOIN sale_items si ON si.invoice_id = s.invoice_id
            WHERE s.date >= ?
              AND s.date <= ?
              AND si.salesperson_code IS NOT NULL
              AND si.salesperson_code != ''
              AND s.shop_name NOT LIKE 'Online Store%'
              AND LOWER(TRIM(si.salesperson_name)) != 'return'
            GROUP BY s.invoice_id, s.sales_id, s.shop_name, s.date, si.salesperson_code
            ORDER BY s.date, si.salesperson_code, s.invoice_id
        ";

        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$from, $to]);

        $sr = 0;
        $this->info('Querying and writing rows...');

        while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
            $sr++;
            $r = $sr + 1;
            $style = ($sr % 2 === 1) ? 2 : 3;
            $name = trim((string) ($row->salesperson_name ?? ''));
            $code = trim((string) $row->salesperson_code);
            $salesperson = $name !== '' ? $name : $code;

            $amount = round((float) $row->amount, 2);
            $commission = round(($amount * $rate) / 100, 2);

            $values = [
                (string) ($row->sales_id ?? ''),
                $row->invoice_id,
                (string) ($row->shop_name ?? ''),
                (string) ($row->date ?? ''),
                $salesperson,
                (string) ($row->products ?? ''),
                (int) $row->qty,
                $amount,
                $commission,
            ];

            fwrite($body, '<row r="' . $r . '">');
            foreach ($values as $i => $value) {
                $ref = $this->colName($i) . $r;
                if (is_int($value) || is_float($value)) {
                    fwrite($body, '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>');
                } else {
                    fwrite($body, '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t>'
                        . htmlspecialchars((string) $value, ENT_XML1) . '</t></is></c>');
                }
            }
            fwrite($body, '</row>');

            if ($sr % 2000 === 0) {
                $this->info("  ... {$sr} rows");
            }
        }
        fclose($body);

        $lastRow = $sr + 1;
        $widths = [14.28515625, 14.28515625, 28.5703125, 20, 28.5703125, 40, 12, 14.28515625, 14.28515625];
        $colsXml = '<cols>';
        for ($c = 1; $c <= $colCount; $c++) {
            $w = $widths[$c - 1] ?? 14.28515625;
            $colsXml .= '<col min="' . $c . '" max="' . $c . '" width="' . $w . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        fwrite($fh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($fh, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">');
        fwrite($fh, '<dimension ref="A1:' . $lastCol . $lastRow . '"/>');
        fwrite($fh, '<sheetViews><sheetView tabSelected="1" workbookViewId="0">');
        fwrite($fh, '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>');
        fwrite($fh, '</sheetView></sheetViews>');
        fwrite($fh, '<sheetFormatPr defaultRowHeight="15"/>');
        fwrite($fh, $colsXml);
        fwrite($fh, '<sheetData>');

        $bodyIn = fopen($bodyPath, 'rb');
        while (!feof($bodyIn)) {
            $chunk = fread($bodyIn, 1024 * 1024);
            if ($chunk !== false && $chunk !== '') {
                fwrite($fh, $chunk);
            }
        }
        fclose($bodyIn);

        fwrite($fh, '</sheetData>');
        fwrite($fh, '<autoFilter ref="A1:' . $lastCol . $lastRow . '"/>');
        fwrite($fh, '<pageMargins left="0.75" right="0.75" top="1" bottom="1" header="0.5" footer="0.5"/>');
        fwrite($fh, '</worksheet>');
        fclose($fh);
        unlink($bodyPath);

        file_put_contents($work . '/xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Sales" sheetId="1" r:id="rId1"/></sheets></workbook>'
        );

        file_put_contents($work . '/xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>'
            . '</Relationships>'
        );

        file_put_contents($work . '/_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>'
        );

        file_put_contents($work . '/[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>'
        );

        if (is_file($output)) {
            unlink($output);
        }

        $zip = new ZipArchive();
        if ($zip->open($output, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Could not create ' . $output);
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($work, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $full = $file->getPathname();
            $local = str_replace('\\', '/', substr($full, strlen($work) + 1));
            $zip->addFile($full, $local);
        }
        $zip->close();
        $this->deleteDir($work);

        $this->info("Rows written: {$sr}");
        $this->info("Excel: {$output}");

        return 0;
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3">'
            . '<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><sz val="8"/><color indexed="12"/><name val="Microsoft Sans Serif"/></font>'
            . '<font><sz val="8"/><color indexed="8"/><name val="Microsoft Sans Serif"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFECF4FF"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
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
        foreach (scandir($dir) as $item) {
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
