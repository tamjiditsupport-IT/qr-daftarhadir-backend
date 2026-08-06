<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Default values
        \DB::table('settings')->insert([
            ['key' => 'organization_name', 'value' => 'Pondok Pesantren', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'late_minutes', 'value' => '10', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'timezone', 'value' => 'Asia/Jakarta', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
