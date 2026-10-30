<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the attendee account link and a flexible communication de-duplication key.
 *
 * The previous unique index on (registration_id, type) made it impossible to
 * send an admin broadcast to the same person twice, because every bulk message
 * shares one type. A nullable de-duplication key keeps the once-only guarantee
 * for lifecycle mail while letting broadcasts through: MySQL permits any number
 * of NULL values in a unique index.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('registrations', 'user_id')) {
            Schema::table('registrations', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->after('event_id')->constrained('users')->nullOnDelete();
            });
        }

        Schema::table('registrations', function (Blueprint $table): void {
            if (! Schema::hasIndex('registrations', 'registrations_user_id_index')) {
                $table->index('user_id', 'registrations_user_id_index');
            }
        });

        // MySQL backs the registration foreign key with the composite unique
        // index, so a single-column index has to exist before it can be dropped.
        Schema::table('communication_logs', function (Blueprint $table): void {
            if (! Schema::hasIndex('communication_logs', 'communication_logs_registration_id_index')) {
                $table->index('registration_id', 'communication_logs_registration_id_index');
            }
        });

        if (Schema::hasIndex('communication_logs', 'communication_logs_registration_id_type_unique')) {
            Schema::table('communication_logs', function (Blueprint $table): void {
                $table->dropUnique('communication_logs_registration_id_type_unique');
            });
        }

        if (! Schema::hasColumn('communication_logs', 'dedupe_key')) {
            Schema::table('communication_logs', function (Blueprint $table): void {
                $table->string('dedupe_key', 120)->nullable()->after('type');
            });
        }

        Schema::table('communication_logs', function (Blueprint $table): void {
            if (! Schema::hasIndex('communication_logs', 'communication_logs_dedupe_key_unique')) {
                $table->unique('dedupe_key', 'communication_logs_dedupe_key_unique');
            }

            if (! Schema::hasIndex('communication_logs', 'communication_logs_event_type_index')) {
                $table->index(['event_id', 'type'], 'communication_logs_event_type_index');
            }

            if (! Schema::hasIndex('communication_logs', 'communication_logs_status_created_index')) {
                $table->index(['status', 'created_at'], 'communication_logs_status_created_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('communication_logs', function (Blueprint $table): void {
            foreach (['communication_logs_event_type_index', 'communication_logs_status_created_index'] as $index) {
                if (Schema::hasIndex('communication_logs', $index)) {
                    $table->dropIndex($index);
                }
            }

            if (Schema::hasIndex('communication_logs', 'communication_logs_dedupe_key_unique')) {
                $table->dropUnique('communication_logs_dedupe_key_unique');
            }

            if (Schema::hasColumn('communication_logs', 'dedupe_key')) {
                $table->dropColumn('dedupe_key');
            }
        });

        // The registration foreign key relies on the composite unique index, so
        // it has to be restored before the single-column index is removed.
        if (Schema::hasIndex('communication_logs', 'communication_logs_registration_id_type_unique')) {
            Schema::table('communication_logs', function (Blueprint $table): void {
                $table->unique(['registration_id', 'type'], 'communication_logs_registration_id_type_unique');
            });
        } else {
            Schema::table('communication_logs', function (Blueprint $table): void {
                $table->unique(['registration_id', 'type']);
            });
        }

        Schema::table('communication_logs', function (Blueprint $table): void {
            if (Schema::hasIndex('communication_logs', 'communication_logs_registration_id_index')) {
                $table->dropIndex('communication_logs_registration_id_index');
            }
        });

        Schema::table('registrations', function (Blueprint $table): void {
            if (Schema::hasIndex('registrations', 'registrations_user_id_index')) {
                $table->dropIndex('registrations_user_id_index');
            }

            if (Schema::hasColumn('registrations', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
