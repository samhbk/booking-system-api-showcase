<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookable_resources', function (Blueprint $table) {
            $table->string('timezone', 64)->nullable()->after('description');
            $table->unsignedSmallInteger('slot_duration_minutes')->default(60)->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('bookable_resources', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'slot_duration_minutes']);
        });
    }
};
