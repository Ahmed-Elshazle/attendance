<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academic_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId("course_id")
            ->nullable()
            ->constrained('courses')
            ->cascadeOnDelete();
            $table->enum('term', ['first', 'second', 'summer']);
            $table->foreignId("lecture_hall_id")
            ->nullable()
            ->constrained('halls')
            ->cascadeOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_schedule');
    }
};
