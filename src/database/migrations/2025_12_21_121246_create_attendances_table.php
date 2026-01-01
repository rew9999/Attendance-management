<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');

            // 打刻時刻
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();

            // 備考
            $table->text('note')->nullable();

            // ステータス: working(出勤中), on_break(休憩中), finished(退勤済)
            $table->enum('status', ['working', 'on_break', 'finished'])->nullable();

            // 修正フラグ
            $table->boolean('is_corrected')->default(false);
            $table->dateTime('corrected_at')->nullable();

            $table->timestamps();

            // ユニーク制約
            $table->unique(['user_id', 'date']);

            // インデックス
            $table->index('date');
            $table->index(['user_id', 'date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
