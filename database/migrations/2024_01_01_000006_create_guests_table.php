<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('tags')->default('[]');
            $table->boolean('has_plus_one')->default(false);
            $table->string('dietary_restrictions')->nullable();
            $table->string('preferred_meal')->nullable();
            $table->enum('attendance_status', ['invited', 'attending', 'declined', 'unknown'])->default('unknown');
            $table->text('notes')->nullable();
            $table->string('invitation_token_hash')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
