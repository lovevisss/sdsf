<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ethics_political_violations', function (Blueprint $table): void {
            if (! Schema::hasColumn('ethics_political_violations', 'staff_no')) {
                $table->string('staff_no')->after('recorder_user_id');
            }

            if (! Schema::hasColumn('ethics_political_violations', 'staff_name')) {
                $table->string('staff_name')->after('staff_no');
            }

            if (! Schema::hasColumn('ethics_political_violations', 'staff_unit_name')) {
                $table->string('staff_unit_name')->nullable()->after('staff_name');
            }
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE ethics_political_violations MODIFY ethics_profile_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE ethics_political_violations MODIFY violator_user_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        Schema::table('ethics_political_violations', function (Blueprint $table): void {
            if (Schema::hasColumn('ethics_political_violations', 'staff_unit_name')) {
                $table->dropColumn('staff_unit_name');
            }

            if (Schema::hasColumn('ethics_political_violations', 'staff_name')) {
                $table->dropColumn('staff_name');
            }

            if (Schema::hasColumn('ethics_political_violations', 'staff_no')) {
                $table->dropColumn('staff_no');
            }
        });
    }
};

