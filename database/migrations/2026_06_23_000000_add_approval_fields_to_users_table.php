<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'approval_status')) {
                $table->string('approval_status', 32)->default(User::APPROVAL_APPROVED)->index()->after('role');
            }

            if (! Schema::hasColumn('users', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_status');
            }

            if (! Schema::hasColumn('users', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
        });

        DB::table('users')
            ->whereNull('approval_status')
            ->orWhere('approval_status', '')
            ->update(['approval_status' => User::APPROVAL_APPROVED]);

        DB::table('users')
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STUDENT])
            ->where('approval_status', '!=', User::APPROVAL_APPROVED)
            ->update(['approval_status' => User::APPROVAL_APPROVED]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }

            if (Schema::hasColumn('users', 'approved_at')) {
                $table->dropColumn('approved_at');
            }

            if (Schema::hasColumn('users', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
        });
    }
};
