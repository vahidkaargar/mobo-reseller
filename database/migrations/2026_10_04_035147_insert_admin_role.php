<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Create the role that guards the /admin routes.
     */
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'Full access to the admin area',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->where('name', 'admin')->delete();
    }
};
