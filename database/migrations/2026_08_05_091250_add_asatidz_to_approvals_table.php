<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->foreignId('asatidz_id')->nullable()->constrained('asatidz')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->nullable(); // Sick, Excused
            $table->timestamp('approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->dropForeign(['asatidz_id']);
            $table->dropForeign(['requested_by']);
            $table->dropColumn(['asatidz_id', 'requested_by', 'type', 'approved_at']);
        });
    }
};
