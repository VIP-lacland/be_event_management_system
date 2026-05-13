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
        Schema::create('events', function (Blueprint $table) {
        $table->id();
        $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
        $table->string('title');
        $table->text('description')->nullable();
        $table->enum('category', ['Music','Sports','Food & Drink','Arts','Education','Community']);
        $table->string('location');
        $table->dateTime('event_date');
        $table->integer('capacity');
        $table->enum('status', ['draft','published','cancelled'])->default('draft');
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
