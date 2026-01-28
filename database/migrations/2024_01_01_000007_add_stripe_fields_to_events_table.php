<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('stripe_account_id')->nullable()->index();
            $table->string('stripe_account_status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['stripe_account_id']);
            $table->dropColumn(['stripe_account_id', 'stripe_account_status']);
        });
    }
};
