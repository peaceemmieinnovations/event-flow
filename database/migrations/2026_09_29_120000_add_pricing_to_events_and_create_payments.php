<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $t) {
            $t->unsignedBigInteger('price_amount')->default(0)->after('event_type');
            $t->string('currency', 3)->default('NGN')->after('price_amount');
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $t->foreignId('event_id')->index()->constrained()->cascadeOnDelete();
            $t->string('reference')->unique();
            $t->string('gateway')->index();
            $t->string('gateway_transaction_id')->nullable();
            $t->unsignedBigInteger('amount');
            $t->string('currency', 3);
            $t->string('status')->default('pending')->index();
            $t->timestamp('paid_at')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::table('events', fn (Blueprint $t) => $t->dropColumn(['price_amount', 'currency']));
    }
};
