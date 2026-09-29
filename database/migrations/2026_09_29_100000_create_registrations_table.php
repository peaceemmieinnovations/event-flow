<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('first_name'); $table->string('last_name'); $table->string('email')->index(); $table->string('phone', 40);
            $table->string('organization')->nullable(); $table->text('notes')->nullable();
            $table->string('status')->default('confirmed')->index();
            $table->timestamp('registered_at'); $table->timestamps();
            $table->unique(['event_id', 'email']);
        });
    }
    public function down(): void { Schema::dropIfExists('registrations'); }
};
