<?php

use App\Services\AppSettingsService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Isolated Oracle check: creates only randomly named GTIN_SET_QA_* objects and
// removes those objects afterward. Never migrates or writes the real settings table.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.default' => 'oracle']);
$db = DB::connection('oracle');
$worker = ($argv[1] ?? '') === '--worker';
$table = $worker ? ($argv[2] ?? '') : 'GTIN_SET_QA_'.strtoupper(bin2hex(random_bytes(4)));
if (! preg_match('/^GTIN_SET_QA_[A-F0-9]{8}$/', $table)) {
    throw new RuntimeException('Invalid QA table name');
}
define('GTIN_SETTINGS_QA_TABLE', $table);
define('GTIN_SETTINGS_QA_SEQUENCE', $table.'_SEQ');
$settings = new class extends AppSettingsService
{
    public const TABLE = GTIN_SETTINGS_QA_TABLE;

    public const SEQUENCE = GTIN_SETTINGS_QA_SEQUENCE;
};
if ($worker) {
    $settings->setExportTabEnabled(true, 'qa-worker-'.getmypid());
    exit(0);
}
$check = function ($condition, $message) {
    if (! $condition) {
        throw new RuntimeException($message);
    }
};
$created = false;
$processes = [];
try {
    $db->statement('CREATE TABLE '.$table.' (SETTING_KEY VARCHAR2(100) PRIMARY KEY, SETTING_VALUE VARCHAR2(1) NOT NULL, UPDATED_BY VARCHAR2(255), UPDATED_AT TIMESTAMP)');
    $created = true;
    $db->table($table)->insert(['SETTING_KEY' => AppSettingsService::EXPORT_TAB, 'SETTING_VALUE' => '0', 'UPDATED_BY' => 'legacy-admin', 'UPDATED_AT' => '2026-11-02 15:44:10']);
    $sql = str_replace('PROJ1_2_APP_SETTINGS', $table, file_get_contents(dirname(__DIR__).'/database/sql/upgrade_app_settings_history.sql'));
    $upgrade = function () use ($db, $sql) {
        foreach (preg_split('/^\/\s*$/m', $sql) as $statement) {
            if (trim(preg_replace('/^--.*$/m', '', $statement)) !== '') {
                $db->unprepared(trim($statement));
            }
        }
    };
    $upgrade();
    $upgrade();
    $check(! $settings->exportTabEnabled(), 'Upgrade reset the current value');
    $row = array_change_key_case((array) $db->table($table)->first(), CASE_UPPER);
    $check($row['UPDATED_BY'] === 'legacy-admin' && $row['UPDATED_AT'] === '2026-11-02 15:44:10' && $row['PREVIOUS_VALUE'] === null, 'Legacy metadata changed');
    $check($db->table($table)->count() === 1, 'Repeated upgrade created rows');
    $sequence = array_change_key_case((array) $db->selectOne('SELECT CACHE_SIZE, ORDER_FLAG FROM USER_SEQUENCES WHERE SEQUENCE_NAME = ?', [$table.'_SEQ']), CASE_UPPER);
    $check((int) $sequence['CACHE_SIZE'] === 0 && $sequence['ORDER_FLAG'] === 'Y', 'Sequence is not NOCACHE ORDER');
    echo "PASS: Oracle upgrade preserves the legacy row, repeats safely, and configures NOCACHE ORDER.\n";

    $db->beginTransaction();
    $db->statement('LOCK TABLE '.$table.' IN EXCLUSIVE MODE');
    for ($i = 0; $i < 2; $i++) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, '--worker', $table], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            throw new RuntimeException('Cannot start Oracle QA worker');
        }
        fclose($pipes[0]);
        $processes[] = [$process, $pipes];
    }
    usleep(500000);
    $db->commit();
    foreach ($processes as [$process, $pipes]) {
        $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $check(proc_close($process) === 0, 'Worker failed: '.$output);
    }
    $processes = [];
    $check($settings->exportTabEnabled(), 'Concurrent enable did not become current');
    $check($db->table($table)->count() === 2, 'Concurrent identical changes wrote duplicate history');
    $settings->setExportTabEnabled(false, 'qa-admin');
    $history = $settings->history(1);
    $check($history['total'] === 3 && $history['data'][0]['value'] === '0' && $history['data'][0]['previous_value'] === '1', 'History or latest ID ordering failed');
    $check(str_ends_with($history['data'][0]['changed_at'], '+00:00'), 'History timestamp is not UTC');
    echo "PASS: Concurrent Oracle writers serialize; duplicate clicks do not append; current state and UTC history are correct.\n";
    $db->statement('DROP TABLE '.$table.' PURGE');
    $db->statement('DROP SEQUENCE '.$table.'_SEQ');
    $created = false;
    $fresh = str_replace('PROJ1_2_APP_SETTINGS', $table, file_get_contents(dirname(__DIR__).'/database/sql/create_app_settings.sql'));
    $fresh = preg_replace('/^--.*$/m', '', $fresh);
    foreach (array_filter(array_map('trim', explode(';', $fresh))) as $statement) {
        if (str_starts_with(strtoupper($statement), 'SELECT')) {
            continue;
        }
        $db->unprepared($statement);
        if (str_starts_with(strtoupper($statement), 'CREATE TABLE')) {
            $created = true;
        }
    }
    $check($settings->exportTabEnabled() && $db->table($table)->count() === 1, 'Fresh-install seed failed');
    $settings->setExportTabEnabled(false, 'qa-admin');
    $check(! $settings->exportTabEnabled() && $db->table($table)->count() === 2, 'Fresh-install append failed');
    echo "PASS: Plain Oracle fresh-install SQL seeds once and supports append-only changes.\n";

} finally {
    if ($db->transactionLevel() > 0) {
        $db->rollBack();
    }
    foreach ($processes as [$process, $pipes]) {
        @proc_terminate($process);
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        } @proc_close($process);
    }
    if ($created) {
        $db->statement('DROP TABLE '.$table.' PURGE');
        if ($db->select('SELECT 1 FROM USER_SEQUENCES WHERE SEQUENCE_NAME = ?', [$table.'_SEQ'])) {
            $db->statement('DROP SEQUENCE '.$table.'_SEQ');
        }
        echo "Cleaned isolated QA objects. Real settings table was not changed.\n";
    }
}
