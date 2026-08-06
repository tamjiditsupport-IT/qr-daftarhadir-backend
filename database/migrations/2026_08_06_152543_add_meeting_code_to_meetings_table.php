<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('meeting_code')->unique()->after('id')->nullable();
        });

        // Set missing codes for existing records
        $meetings = \Illuminate\Support\Facades\DB::table('meetings')->get();
        foreach($meetings as $meeting) {
            \Illuminate\Support\Facades\DB::table('meetings')
                ->where('id', $meeting->id)
                ->update(['meeting_code' => 'RPT-' . date('Y', strtotime($meeting->created_at)) . '-' . str_pad($meeting->id, 6, '0', STR_PAD_LEFT)]);
        }

        // Now make it not nullable
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('meeting_code')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('meeting_code');
        });
    }
};
