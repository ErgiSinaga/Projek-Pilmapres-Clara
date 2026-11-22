<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('tanggal');
            $table->timestamps();

            $table->unique(['student_id', 'tanggal']);
        });

        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('butir_kode');
            $table->unsignedTinyInteger('nilai');
            $table->timestamps();

            $table->unique(['student_id', 'butir_kode']);
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamp('login_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('daily_claims');
    }
};
