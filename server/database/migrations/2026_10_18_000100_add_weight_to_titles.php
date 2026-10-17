<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Titles get a weight: the share of articles a title gets over time (0 = paused).
 * Titles are now always re-used, so services.reuse_titles is not needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            $table->decimal('weight', 8, 2)->default(1)->after('title');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('reuse_titles');
        });
    }

    public function down(): void
    {
        Schema::table('titles', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('reuse_titles')->default(false)->after('title_list_id');
        });
    }
};
