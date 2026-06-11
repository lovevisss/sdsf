<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->tableIndexNames() as $tableName => $indexName) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                $table->string('severity_level', 1)->nullable()->after('violation_type');
                $table->string('data_source')->default('manual')->after('deduction_points');
                $table->string('handler_department')->nullable()->after('data_source');
                $table->foreignId('handler_user_id')->nullable()->after('handler_department')->constrained('users')->nullOnDelete();
                $table->text('deduction_basis')->nullable()->after('handler_user_id');
                $table->json('evidence_attachments')->nullable()->after('deduction_basis');
                $table->string('verification_status')->default('verified')->after('evidence_attachments');
                $table->foreignId('verified_by')->nullable()->after('verification_status')->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable()->after('verified_by');

                $table->index(['severity_level', 'verification_status'], $indexName);
            });
        }

        if (Schema::hasTable('ethics_warnings')) {
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE ethics_warnings MODIFY warning_level ENUM('blue','yellow','orange','red') NOT NULL");
                DB::statement("ALTER TABLE ethics_warnings MODIFY source_type ENUM('teaching','research','behavior','training','manual','scoring','discipline') NOT NULL");
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tableIndexNames() as $tableName => $indexName) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                $table->dropIndex($indexName);
                $table->dropConstrainedForeignId('handler_user_id');
                $table->dropConstrainedForeignId('verified_by');
                $table->dropColumn([
                    'severity_level',
                    'data_source',
                    'handler_department',
                    'deduction_basis',
                    'evidence_attachments',
                    'verification_status',
                    'verified_at',
                ]);
            });
        }
    }

    /**
     * @return array<string, string>
     */
    private function tableIndexNames(): array
    {
        return [
            'ethics_political_violations' => 'epol_severity_status_idx',
            'ethics_education_violations' => 'eedu_severity_status_idx',
            'ethics_academic_violations' => 'eaca_severity_status_idx',
            'ethics_professional_violations' => 'epro_severity_status_idx',
        ];
    }
};
