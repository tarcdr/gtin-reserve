<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AppSettingsService
{
    public const TABLE = 'PROJ1_2_APP_SETTINGS';
    public const EXPORT_TAB = 'export_to_sap_tab_enabled';

    public function exportTabEnabled(): bool
    {
        // Existing deployments retain their current menu until the additive migration is installed.
        if (!Schema::hasTable(self::TABLE)) return true;
        $row = DB::table(self::TABLE)->where('SETTING_KEY', self::EXPORT_TAB)->selectRaw('SETTING_VALUE as value')->first();
        return $row === null || (string) $row->value === '1';
    }

    public function setExportTabEnabled(bool $enabled, string $user): void
    {
        if (!Schema::hasTable(self::TABLE)) throw ValidationException::withMessages(['enabled' => 'ยังไม่มีตารางตั้งค่าระบบ กรุณาติดตั้ง migration/SQL ก่อน']);
        DB::transaction(function () use ($enabled, $user) {
            DB::table(self::TABLE)->where('SETTING_KEY', self::EXPORT_TAB)->lockForUpdate()->first();
            DB::table(self::TABLE)->updateOrInsert(['SETTING_KEY' => self::EXPORT_TAB], [
                'SETTING_VALUE' => $enabled ? '1' : '0', 'UPDATED_BY' => $user, 'UPDATED_AT' => now(),
            ]);
        });
    }
}
