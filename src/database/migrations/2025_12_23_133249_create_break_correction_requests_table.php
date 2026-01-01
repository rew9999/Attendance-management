<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakCorrectionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correction_request_id')->constrained('attendance_correction_requests')->onDelete('cascade');
            $table->foreignId('break_id')->nullable()->constrained()->onDelete('cascade');

            // 修正後の休憩時刻
            $table->dateTime('requested_break_start');
            $table->dateTime('requested_break_end')->nullable();

            $table->timestamps();

            $table->index('correction_request_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('break_correction_requests');
    }
}
