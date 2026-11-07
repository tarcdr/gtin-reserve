<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AppSettingsService
{
    public const TABLE = 'PROJ1_2_APP_SETTINGS';

    public const SEQUENCE = 'PROJ1_2_APP_SETTINGS_SEQ';

    public const EXPORT_TAB = 'export_to_sap_tab_enabled';

    public function exportTabEnabled(): bool
    {
        if (! Schema::hasTable(static::TABLE)) {
            return true;
        }
        $query = DB::table(static::TABLE)->where('SETTING_KEY', self::EXPORT_TAB);
        // Read-only compatibility while the old schema is being upgraded; never write without history.
        if (Schema::hasColumn(static::TABLE, 'ID')) {
            $query->orderByDesc('ID');
        }
        $row = $query->selectRaw('SETTING_VALUE as value')->first();

        return $row === null || (string) $row->value === '1';
    }

    private function ensureHistorySchema(): void
    {
        if (! Schema::hasTable(static::TABLE)) {
            throw ValidationException::withMessages(['enabled' => 'ยังไม่มีตารางตั้งค่าระบบ กรุณาติดตั้ง migration/SQL ก่อน']);
        }
        if (! Schema::hasColumn(static::TABLE, 'ID') || ! Schema::hasColumn(static::TABLE, 'PREVIOUS_VALUE')) {
            throw ValidationException::withMessages(['enabled' => 'กรุณาอัปเกรดตารางตั้งค่าให้รองรับประวัติก่อนเปลี่ยนค่า']);
        }
    }

    public function setExportTabEnabled(bool $enabled, string $user): void
    {
        $this->ensureHistorySchema();
        DB::transaction(function () use ($enabled, $user) {
            $connection = DB::connection();
            if ($connection->getDriverName() === 'oracle') {
                // Serialize readers-then-writers before allocating a globally ordered sequence ID.
                $connection->statement('LOCK TABLE '.static::TABLE.' IN EXCLUSIVE MODE');
            } elseif ($connection->getDriverName() === 'sqlite') {
                // Acquire SQLite's write lock before reading MAX(ID); used by local tests.
                $connection->update('UPDATE '.static::TABLE.' SET ID = ID WHERE 1 = 0');
            } else {
                throw new \RuntimeException('Unsupported settings history database.');
            }
            $row = $connection->table(static::TABLE)->where('SETTING_KEY', self::EXPORT_TAB)->orderByDesc('ID')->selectRaw('SETTING_VALUE as value')->first();
            $old = $row === null ? '1' : (string) $row->value;
            $value = $enabled ? '1' : '0';
            if ($old === $value) {
                return;
            }
            $id = $connection->getDriverName() === 'oracle'
                ? (array_values((array) $connection->selectOne('SELECT '.static::SEQUENCE.'.NEXTVAL AS id FROM DUAL'))[0])
                : ((int) $connection->table(static::TABLE)->max('ID') + 1);
            $connection->table(static::TABLE)->insert([
                'ID' => $id, 'SETTING_KEY' => self::EXPORT_TAB, 'PREVIOUS_VALUE' => $old,
                'SETTING_VALUE' => $value, 'UPDATED_BY' => $user, 'UPDATED_AT' => now('UTC'),
            ]);
        }, 3);
    }

    public function history(int $page): array
    {
        $this->ensureHistorySchema();
        $paginator = DB::table(static::TABLE)->where('SETTING_KEY', self::EXPORT_TAB)->orderByDesc('ID')->paginate(25, ['*'], 'page', $page);
        $paginator->setCollection($paginator->getCollection()->map(function ($row) {
            $row = array_change_key_case((array) $row, CASE_UPPER);

            return [
                'id' => (string) $row['ID'], 'previous_value' => $row['PREVIOUS_VALUE'],
                'value' => $row['SETTING_VALUE'], 'changed_by' => $row['UPDATED_BY'],
                'changed_at' => $row['UPDATED_AT'] === null ? null : CarbonImmutable::parse($row['UPDATED_AT'], 'UTC')->utc()->toIso8601String(),
            ];
        }));

        return $paginator->toArray();
    }
}
