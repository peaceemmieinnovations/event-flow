<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('events', 'category')) {
            Schema::table('events', function (Blueprint $table): void {
                $table->string('category', 60)->nullable()->after('country')->index();
            });
        }

        if (! Schema::hasColumn('events', 'published_at')) {
            Schema::table('events', function (Blueprint $table): void {
                $table->timestamp('published_at')->nullable()->after('registration_deadline');
            });
        }

        if (! Schema::hasColumn('communication_logs', 'subject')) {
            Schema::table('communication_logs', function (Blueprint $table): void {
                $table->string('subject')->nullable()->after('type');
                $table->text('message')->nullable()->after('subject');
                $table->string('channel', 20)->default('mail')->after('message')->index();
                $table->unsignedInteger('recipient_count')->default(1)->after('channel');
                $table->text('error_message')->nullable()->after('sent_at');
            });
        }

        Schema::table('payments', function (Blueprint $table): void {
            if (! $this->hasIndex('payments', 'payments_gateway_transaction_id_index')) {
                $table->index('gateway_transaction_id', 'payments_gateway_transaction_id_index');
            }

            if (! $this->hasIndex('payments', 'payments_status_paid_at_index')) {
                $table->index(['status', 'paid_at'], 'payments_status_paid_at_index');
            }
        });

        Schema::table('registrations', function (Blueprint $table): void {
            if (! $this->hasIndex('registrations', 'registrations_event_id_status_index')) {
                $table->index(['event_id', 'status'], 'registrations_event_id_status_index');
            }

            if (! $this->hasIndex('registrations', 'registrations_status_registered_at_index')) {
                $table->index(['status', 'registered_at'], 'registrations_status_registered_at_index');
            }
        });

        Schema::table('tickets', function (Blueprint $table): void {
            if (! $this->hasIndex('tickets', 'tickets_event_id_status_index')) {
                $table->index(['event_id', 'status'], 'tickets_event_id_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex('tickets_event_id_status_index');
        });

        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropIndex('registrations_event_id_status_index');
            $table->dropIndex('registrations_status_registered_at_index');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_gateway_transaction_id_index');
            $table->dropIndex('payments_status_paid_at_index');
        });

        Schema::table('communication_logs', function (Blueprint $table): void {
            $table->dropColumn(['subject', 'message', 'channel', 'recipient_count', 'error_message']);
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'published_at']);
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        foreach (Schema::getIndexes($table) as $existing) {
            if ($existing['name'] === $index) {
                return true;
            }
        }

        return false;
    }
};
