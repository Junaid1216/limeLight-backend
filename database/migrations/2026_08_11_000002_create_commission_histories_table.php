<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCommissionHistoriesTable extends Migration
{
    public function up()
    {
        Schema::create('commission_histories', function (Blueprint $table) {
            $table->id();
            $table->enum('role', ['branch_manager', 'sales_staff']);
            $table->decimal('commission', 10, 2);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->timestamps();

            $table->index(['role', 'effective_from']);
        });

        // Seed open history from current commissions so past sales keep today's rate until admin changes it
        $now = now();
        $rows = DB::table('commissions')->get();

        foreach ($rows as $row) {
            DB::table('commission_histories')->insert([
                'role' => $row->role,
                'commission' => $row->commission,
                'effective_from' => $row->created_at ?? '2000-01-01 00:00:00',
                'effective_to' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('commission_histories');
    }
}
