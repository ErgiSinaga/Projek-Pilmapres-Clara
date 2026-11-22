<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_items', function (Blueprint $table) {
            $table->id();
            $table->string('set')->default('A');
            $table->unsignedInteger('urutan');
            $table->string('tp'); // TP1, TP2, TP3, TP4, TP5
            $table->text('teks');
            $table->json('opsi');
            $table->unsignedTinyInteger('benar'); // index opsi benar (0, 1, 2)
            $table->string('gambar')->nullable();
            $table->string('audio')->nullable();
            $table->text('penjelasan_internal')->nullable();
            $table->string('status')->default('DRAFT');
            $table->timestamps();
        });

        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('jenis'); // pre, post
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('skor')->default(0);
            $table->unsignedInteger('total')->default(10);
            $table->unsignedInteger('durasi_detik')->default(0);
            $table->timestamps();

            $table->unique(['student_id', 'jenis']);
        });

        Schema::create('test_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('test_attempts')->cascadeOnDelete();
            $table->foreignId('test_item_id')->constrained('test_items')->cascadeOnDelete();
            $table->unsignedTinyInteger('pilihan');
            $table->boolean('benar');
            $table->unsignedInteger('ms')->default(0);
            $table->json('opsi_urutan'); // Urutan opsi yang ditampilkan ke murid
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_answers');
        Schema::dropIfExists('test_attempts');
        Schema::dropIfExists('test_items');
    }
};
