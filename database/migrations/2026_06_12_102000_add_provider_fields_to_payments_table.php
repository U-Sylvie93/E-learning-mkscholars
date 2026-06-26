<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'provider' => fn (Blueprint $table) => $table->string('provider')->nullable()->default(Payment::PROVIDER_MANUAL),
            'provider_reference' => fn (Blueprint $table) => $table->string('provider_reference')->nullable(),
            'provider_status' => fn (Blueprint $table) => $table->string('provider_status')->nullable(),
            'provider_payload' => fn (Blueprint $table) => $table->json('provider_payload')->nullable(),
            'provider_callback_received_at' => fn (Blueprint $table) => $table->timestamp('provider_callback_received_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('payments', $column)) {
                Schema::table('payments', fn (Blueprint $table) => $definition($table));
            }
        }

        DB::table('payments')
            ->whereNull('provider')
            ->update(['provider' => Payment::PROVIDER_MANUAL]);
    }

    public function down(): void
    {
        foreach ([
            'provider_callback_received_at',
            'provider_payload',
            'provider_status',
            'provider_reference',
            'provider',
        ] as $column) {
            if (Schema::hasColumn('payments', $column)) {
                Schema::table('payments', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
