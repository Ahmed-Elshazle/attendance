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
        Schema::create('academic_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->enum('term', ['first', 'second', 'summer']);
            $table->year('year');

            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('lecture_hall_id')->nullable()->constrained('halls')->onDelete('set null');
            $table->boolean('is_lecture_attendance_open')->default(0);
            $table->string('lecture_day');
            $table->time('lecture_start_hour');
            $table->time('lecture_end_hour');
            
            $table->foreignId('assistant_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('section_hall_id')->nullable()->constrained('halls')->onDelete('set null');
            $table->boolean('is_section_attendance_open')->default(0);
            $table->string('section_day');
            $table->time('section_start_hour');
            $table->time('section_end_hour');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_schedules');
    }
};
