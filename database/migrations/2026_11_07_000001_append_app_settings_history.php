<?php

use App\Services\AppSettingsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable(AppSettingsService::TABLE)) {
            throw new RuntimeException('Install the original app-settings migration first.');
        }
        if (DB::connection()->getDriverName() === 'oracle') {
            foreach (preg_split('/^\/\s*$/m', file_get_contents(database_path('sql/upgrade_app_settings_history.sql'))) as $statement) {
                if (trim(preg_replace('/^--.*$/m', '', $statement)) !== '') {
                    DB::unprepared(trim($statement));
                }
            }

            return;
        }
        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException('Unsupported settings history database.');
        }
        if (Schema::hasColumn(AppSettingsService::TABLE, 'ID') && Schema::hasColumn(AppSettingsService::TABLE, 'PREVIOUS_VALUE')) {
            return;
        }
        // SQLite fixture equivalent: replace the key primary key without losing a legacy row.
        DB::transaction(function () {
            $rows = DB::table(AppSettingsService::TABLE)->get()->map(fn ($row) => array_change_key_case((array) $row, CASE_UPPER));
            Schema::drop(AppSettingsService::TABLE);
            Schema::create(AppSettingsService::TABLE, function (Blueprint $table) {
                $table->unsignedBigInteger('ID')->primary();
                $table->string('SETTING_KEY', 100);
                $table->string('PREVIOUS_VALUE', 1)->nullable();
                $table->string('SETTING_VALUE', 1);
                $table->string('UPDATED_BY', 255)->nullable();
                $table->timestamp('UPDATED_AT')->nullable();
                $table->index(['SETTING_KEY', 'ID']);
            });
            foreach ($rows as $index => $row) {
                DB::table(AppSettingsService::TABLE)->insert($row + ['ID' => $index + 1, 'PREVIOUS_VALUE' => null]);
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Settings history cannot be collapsed automatically. Restore a reviewed backup or deploy a forward fix.');
    }
};
