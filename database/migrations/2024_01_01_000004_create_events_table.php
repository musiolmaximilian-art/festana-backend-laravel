<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('website_name')->unique();
            $table->string('title');
            $table->date('wedding_date')->nullable();
            $table->string('timezone')->default('Europe/Berlin');
            $table->json('public_settings')->default(json_encode(['is_public' => false]));
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
