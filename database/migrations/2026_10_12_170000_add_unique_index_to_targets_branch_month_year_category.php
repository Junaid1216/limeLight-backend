<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUniqueIndexToTargetsBranchMonthYearCategory extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('targets')) {
            return;
        }

        $duplicates = DB::table('targets')
            ->select('branch_id', 'month', 'year', 'category', DB::raw('MAX(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('branch_id', 'month', 'year', 'category')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            DB::table('targets')
                ->where('branch_id', $dup->branch_id)
                ->where('month', $dup->month)
                ->where('year', $dup->year)
                ->where('category', $dup->category)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        Schema::table('targets', function (Blueprint $table) {
            $table->unique(
                ['branch_id', 'month', 'year', 'category'],
                'targets_branch_month_year_category_unique'
            );
        });
    }

    public function down()
    {
        if (!Schema::hasTable('targets')) {
            return;
        }

        Schema::table('targets', function (Blueprint $table) {
            $table->dropUnique('targets_branch_month_year_category_unique');
        });
    }
}
