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
            Schema::create(AppSettingsService::TABLE, function (Blueprint $table) {
                $table->string('SETTING_KEY', 100)->primary();
                $table->string('SETTING_VALUE', 1);
                $table->string('UPDATED_BY', 255)->nullable();
                $table->timestamp('UPDATED_AT')->nullable();
            });
        }
        if (! DB::table(AppSettingsService::TABLE)->where('SETTING_KEY', AppSettingsService::EXPORT_TAB)->exists()) {
            $seed = ['SETTING_KEY' => AppSettingsService::EXPORT_TAB, 'SETTING_VALUE' => '1', 'UPDATED_BY' => 'migration', 'UPDATED_AT' => now('UTC')];
            if (Schema::hasColumn(AppSettingsService::TABLE, 'ID')) {
                $seed['ID'] = DB::connection()->getDriverName() === 'oracle'
                    ? DB::raw(AppSettingsService::SEQUENCE.'.NEXTVAL')
                    : (int) DB::table(AppSettingsService::TABLE)->max('ID') + 1;
            }
            DB::table(AppSettingsService::TABLE)->insert($seed);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn(AppSettingsService::TABLE, 'ID')) {
            throw new RuntimeException('Do not drop settings history automatically.');
        }
        Schema::dropIfExists(AppSettingsService::TABLE);
    }
};
