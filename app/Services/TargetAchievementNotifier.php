<?php

namespace App\Services;

use App\Helpers\NotificationHelper;
use App\Models\AreaSaleManager;
use App\Models\Branch;
use App\Models\BranchManager;
use App\Models\BranchTargetAchievementPush;
use App\Models\SaleItem;
use App\Models\Target;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TargetAchievementNotifier
{
    public function notifyCurrentMonthHits(): void
    {
        if (!Schema::hasTable('branch_target_achievement_pushes')) {
            return;
        }

        $now = Carbon::now();
        $month = $now->format('F');
        $year = (string) $now->year;
        $monthVariants = array_values(array_unique([
            $month,
            strtolower($month),
            ucfirst(strtolower($month)),
            $now->format('m'),
            (string) $now->month,
        ]));
        $yearVariants = array_values(array_unique([
            $year,
            (int) $year,
        ]));

        $from = $now->copy()->startOfMonth()->startOfDay();
        $to = $now->copy()->endOfMonth()->endOfDay();
        $mappings = $this->categoryMappings();

        $targets = Target::query()
            ->whereIn('month', $monthVariants)
            ->whereIn('year', $yearVariants)
            ->get()
            ->groupBy('branch_id');

        foreach ($targets as $branchId => $branchTargets) {
            $monthlyTarget = (float) $branchTargets->sum('monthly_target');
            if ($monthlyTarget <= 0) {
                continue;
            }

            $alreadySent = BranchTargetAchievementPush::query()
                ->where('branch_id', $branchId)
                ->where('month', $month)
                ->where('year', $year)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $branch = Branch::find($branchId);
            if (!$branch || trim((string) $branch->name) === '') {
                continue;
            }

            $qty = $this->achievedQty((string) $branch->name, $from, $to, $mappings);
            if ($qty < $monthlyTarget) {
                continue;
            }

            try {
                BranchTargetAchievementPush::create([
                    'branch_id' => $branch->id,
                    'month' => $month,
                    'year' => $year,
                ]);
            } catch (\Throwable $e) {
                continue;
            }

            $users = $this->asmRecipients($branch);
            if (empty($users)) {
                continue;
            }

            try {
                NotificationHelper::sendToUsers(
                    $users,
                    'Branch target achieved',
                    $branch->name . ' has achieved its monthly target for ' . $month . ' ' . $year . '.',
                    [
                        'type' => 'target_achieved',
                        'source' => 'target_achieved',
                        'branch_id' => (string) $branch->id,
                        'month' => $month,
                        'year' => $year,
                    ]
                );
            } catch (\Throwable $e) {
                Log::error('Target achievement push dispatch failed', [
                    'branch_id' => $branch->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function achievedQty(string $shopName, Carbon $from, Carbon $to, array $mappings): float
    {
        $items = SaleItem::query()
            ->select(['category', 'quantity'])
            ->whereHas('sale', function ($q) use ($shopName, $from, $to) {
                $q->where('shop_name', $shopName)
                    ->whereBetween('date', [$from, $to]);
            })
            ->get();

        $qty = 0.0;
        foreach ($items as $item) {
            $itemCategory = strtolower(trim((string) $item->category));
            foreach ($mappings as $labels) {
                if (in_array($itemCategory, $labels, true)) {
                    $qty += max(0, (float) $item->quantity);
                    break;
                }
            }
        }

        return $qty;
    }

    private function asmRecipients(Branch $branch): array
    {
        $asmIds = BranchManager::query()
            ->where('branch_id', $branch->id)
            ->whereNotNull('asm_id')
            ->where('asm_id', '!=', '')
            ->pluck('asm_id')
            ->unique()
            ->filter()
            ->values();

        if ($asmIds->isEmpty() && $branch->region_id) {
            $asmIds = AreaSaleManager::query()
                ->where('region_id', $branch->region_id)
                ->pluck('id');
        }

        return $asmIds->map(function ($id) {
            return ['id' => (int) $id, 'type' => 'asm'];
        })->all();
    }

    private function categoryMappings(): array
    {
        return [
            'garments' => [
                'signature',
                'flowy',
                'trouser',
                'regular prints',
                'fusion co-ords',
                'festive',
                'composed rotary',
                'premium',
                'casual',
                'glam',
                'dailywear',
                'regular running',
                'regular panel',
                'modish',
                'trendy',
                'premium wear',
                'tops',
            ],
            'unstitched' => [
                'dupatta - dyed',
                'unstitched trousers',
            ],
            'accessories' => [
                'hand bag',
                'scarves - printed',
                'sunglasses',
                'jewellery',
                'clutches',
                'perfumes',
                'body mist',
                'non-tradable',
            ],
        ];
    }
}
