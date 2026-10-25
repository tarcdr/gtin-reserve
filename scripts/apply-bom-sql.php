<?php
// Run from the application container: php scripts/apply-bom-sql.php [--apply]
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (in_array('--qa', $argv, true)) {
    if (getenv('GTIN_ORACLE_QA') !== '1') throw new RuntimeException('Isolated QA environment required');
    config(['database.connections.oracle' => array_replace(config('database.connections.oracle'), [
        'tns' => '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=127.0.0.1)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=XEPDB1)))',
        'host' => '127.0.0.1', 'database' => 'XEPDB1', 'service_name' => 'XEPDB1', 'username' => 'GTIN_QA', 'password' => 'gtin_test_only_password', 'prefix_schema' => '', 'url' => null,
    ])]);
    Illuminate\Support\Facades\DB::purge('oracle');
}
$db = Illuminate\Support\Facades\DB::connection('oracle');
$schema = $db->selectOne('SELECT USER AS SCHEMA_NAME FROM DUAL')->schema_name;
$sql = file_get_contents(dirname(__DIR__).'/database/sql/complete_existing_bom_flows.sql');
$statements = preg_split('/^\/\s*$/m', $sql);
$apply = in_array('--apply', $argv, true);
$backup = [];
$previouslyValid = $db->table('USER_OBJECTS')->where('STATUS', 'VALID')->whereIn('OBJECT_TYPE', ['VIEW','PROCEDURE','FUNCTION'])->get(['OBJECT_NAME','OBJECT_TYPE'])->map(fn ($r) => ['name' => $r->object_name ?? $r->OBJECT_NAME, 'type' => $r->object_type ?? $r->OBJECT_TYPE])->all();
preg_match_all('/CREATE OR REPLACE\s+(VIEW|PROCEDURE)\s+(PROJ1_[A-Z0-9_]+)/i', $sql, $objects, PREG_SET_ORDER);
foreach ($objects as $object) {
    $name = strtoupper($object[2]); $type = strtoupper($object[1]);
    $rows = $type === 'VIEW' ? $db->select('SELECT TEXT FROM USER_VIEWS WHERE VIEW_NAME = ?', [$name]) : $db->select('SELECT TEXT FROM USER_SOURCE WHERE NAME = ? ORDER BY LINE', [$name]);
    $text = implode('', array_map(fn ($r) => is_resource($r->text) ? stream_get_contents($r->text) : $r->text, $rows));
    $backup[$name] = ['type' => $type, 'source' => $text];
    echo $name.' '.($text === '' ? 'CREATE' : 'REPLACE').PHP_EOL;
}
$directory = storage_path('app/bom-object-backups');
if (!is_dir($directory)) mkdir($directory, 0700, true);
$file = $directory.'/'.$schema.'_'.gmdate('Ymd_His').'_'.bin2hex(random_bytes(3)).'.json';
file_put_contents($file, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
chmod($file, 0600);
echo 'Backup: '.$file.PHP_EOL;
if (!$apply) { echo 'Dry run. No Oracle objects changed.'.PHP_EOL; exit(0); }
// Existing LV2 links without an exact BOM association require explicit data mapping, never a guessed join.
$columns = $db->select("SELECT COLUMN_NAME FROM USER_TAB_COLUMNS WHERE TABLE_NAME='PROJ1_2_DML_SEMI_L2_ID_EXIST' AND COLUMN_NAME='SEMI_FG_LV2_BOM_ID_EXIST'");
if (!$columns && $db->table('PROJ1_2_DML_SEMI_L2_ID_EXIST')->count() > 0) throw new RuntimeException('Unmapped legacy LV2 Existing rows require an explicit BOM mapping before applying.');
foreach ($statements as $statement) {
    if (trim(preg_replace('/^--.*$/m', '', $statement)) === '') continue;
    $db->unprepared(trim($statement));
}
// Adding columns may invalidate dependent functions/views. Recompile only objects that were valid before this change.
for ($pass=0; $pass<3; $pass++) {
    $invalidNow = $db->table('USER_OBJECTS')->where('STATUS', '<>', 'VALID')->get(['OBJECT_NAME'])->map(fn ($r) => $r->object_name ?? $r->OBJECT_NAME)->all();
    foreach ($previouslyValid as $object) {
        if (in_array($object['name'], $invalidNow, true) && preg_match('/^PROJ1_[A-Z0-9_]+$/', $object['name'])) $db->unprepared('ALTER '.$object['type'].' '.$object['name'].' COMPILE');
    }
}
$names = array_map(fn ($o) => strtoupper($o[2]), $objects);
$errors = $db->table('USER_ERRORS')->whereIn('NAME', $names)->where('ATTRIBUTE', 'ERROR')->get(['NAME', 'LINE', 'POSITION', 'TEXT']);
if ($errors->isNotEmpty()) throw new RuntimeException('Compile errors: '.json_encode($errors));
$invalid = $db->table('USER_OBJECTS')->whereIn('OBJECT_NAME', $names)->where('STATUS', '<>', 'VALID')->get(['OBJECT_NAME'])->map(fn ($r) => $r->object_name ?? $r->OBJECT_NAME)->all();
if ($invalid) throw new RuntimeException('Invalid objects: '.implode(', ', $invalid));
echo 'Applied. All changed views/procedures are VALID.'.PHP_EOL;
