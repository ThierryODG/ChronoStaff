<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::select("
            SELECT setval(
                pg_get_serial_sequence('companies', 'id'),
                COALESCE((SELECT MAX(id) FROM companies), 1),
                EXISTS (SELECT 1 FROM companies)
            )
        ");
    }

    public function down(): void
    {
    }
};
