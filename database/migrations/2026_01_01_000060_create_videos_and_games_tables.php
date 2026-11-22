<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('video_id');
            $table->unsignedInteger('detik_ditonton')->default(0);
            $table->timestamp('selesai_at')->nullable();
            $table->unsignedInteger('skor_tantangan')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'video_id']);
        });

        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('game_kode');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('skor')->default(0);
            $table->json('detail')->nullable();
            $table->timestamps();
        });

        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('tipe');
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_events');
        Schema::dropIfExists('game_sessions');
        Schema::dropIfExists('video_progress');
    }
};
