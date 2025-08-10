<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBreakTimesToAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->time('break_in_time_1')->nullable()->after('check_out_time');
            $table->time('break_out_time_1')->nullable()->after('break_in_time_1');
            $table->time('break_in_time_2')->nullable()->after('break_out_time_1');
            $table->time('break_out_time_2')->nullable()->after('break_in_time_2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('break_out_time_2');
            $table->dropColumn('break_in_time_2');
            $table->dropColumn('break_out_time_1');
            $table->dropColumn('break_in_time_1');
        });
    }
}
