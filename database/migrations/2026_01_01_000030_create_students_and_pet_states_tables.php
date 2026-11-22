<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('kode')->unique();
            $table->string('pin_hash');
            $table->string('pet_name')->nullable();
            $table->unsignedInteger('failed_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('consent_confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pet_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->unsignedSmallInteger('kebersihan')->default(100);
            $table->unsignedSmallInteger('kebahagiaan')->default(100);
            $table->unsignedInteger('koin')->default(0);
            $table->unsignedInteger('xp')->default(0);
            $table->unsignedTinyInteger('level')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_decay_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_states');
        Schema::dropIfExists('students');
    }
};
