<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('registration_id')->nullable()->constrained()->nullOnDelete();
            $t->string('recipient_email')->index();
            $t->string('type')->index();
            $t->string('status')->default('queued');
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
            $t->unique(['registration_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
