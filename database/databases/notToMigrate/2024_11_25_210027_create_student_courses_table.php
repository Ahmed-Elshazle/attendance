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
        Schema::create('student_courses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')
        ->unique()
        ->constrained('users')
        ->onDelete('cascade');

        for ($i = 1; $i <= 8; $i++) {
            $table->foreignId("course{$i}_id")
                ->nullable()
                ->constrained('courses');
        }
        $table->integer('grade');
        $table->enum('term', ['first', 'second', 'summer']);
        $table->dateTime('year', precision: 0);
        
        // $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_courses');
    }
};
