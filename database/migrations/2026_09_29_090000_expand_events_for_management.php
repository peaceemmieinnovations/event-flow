<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('slug')->nullable()->unique()->after('title');
            $table->time('start_time')->nullable()->after('event_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->string('address')->nullable()->after('location');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('country')->nullable()->after('state');
            $table->string('organizer_name')->nullable()->after('country');
            $table->string('organizer_email')->nullable()->after('organizer_name');
            $table->string('organizer_phone')->nullable()->after('organizer_email');
            $table->unsignedInteger('capacity')->nullable()->after('organizer_phone');
            $table->string('event_type')->default('free')->after('capacity');
            $table->string('status')->default('draft')->after('event_type');
            $table->dateTime('registration_deadline')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'start_time', 'end_time', 'address', 'city', 'state', 'country', 'organizer_name', 'organizer_email', 'organizer_phone', 'capacity', 'event_type', 'status', 'registration_deadline']);
        });
    }
};
