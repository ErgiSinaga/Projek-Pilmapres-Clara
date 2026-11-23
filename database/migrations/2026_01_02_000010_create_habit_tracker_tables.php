<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habit_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('status', ['draft', 'selesai', 'dilewati'])->default('draft');
            $table->unsignedInteger('dilewati_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('durasi_detik')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'tanggal']);
            $table->index(['student_id', 'status']);
        });

        Schema::create('habit_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkin_id')->constrained('habit_checkins')->cascadeOnDelete();
            $table->string('item_kode', 50);
            $table->string('nilai', 50);
            $table->decimal('nilai_num', 4, 2)->nullable();
            $table->unsignedInteger('ms')->nullable();
            $table->timestamp('answered_at')->useCurrent();

            $table->unique(['checkin_id', 'item_kode']);
            $table->index(['item_kode', 'nilai']);
        });

        Schema::create('habit_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkin_id')->constrained('habit_checkins')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('tipe', 20); // video | game
            $table->string('target_kode', 50);
            $table->string('alasan_item', 50);
            $table->timestamp('shown_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'target_kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_recommendations');
        Schema::dropIfExists('habit_answers');
        Schema::dropIfExists('habit_checkins');
    }
};
