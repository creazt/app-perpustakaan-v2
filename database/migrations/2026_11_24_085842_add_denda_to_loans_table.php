<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('loans', 'denda')) {
            Schema::table('loans', function (Blueprint $table) {
                // Total denda (rupiah), terisi saat buku dikembalikan
                $table->unsignedInteger('denda')->default(0)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('loans', 'denda')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropColumn('denda');
            });
        }
    }
};