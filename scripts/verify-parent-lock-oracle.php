<?php
// Requires a fresh, disposable GTIN_LOCK_QA schema in XEPDB1. Copies definitions only;
// all DDL and fixture DML are performed through the explicitly guarded QA connection.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $e) { fwrite(STDERR, get_class($e).': '.$e->getMessage().PHP_EOL); exit(1); });
use Illuminate\Support\Facades\DB;
use App\Services\ExistingComponentService;
use App\Http\Controllers\ExistingComponentController;

if (getenv('GTIN_LOCK_QA') !== '1' || !getenv('GTIN_LOCK_QA_PASSWORD')) throw new RuntimeException('Explicit disposable QA configuration required');
$source = DB::connection('oracle');
$qaConfig = array_replace(config('database.connections.oracle'), [
    'tns' => '', 'database' => 'XEPDB1', 'service_name' => 'XEPDB1',
    'options' => ['cached' => false, PDO::ATTR_PERSISTENT => false],
    'username' => 'GTIN_LOCK_QA', 'password' => getenv('GTIN_LOCK_QA_PASSWORD'), 'prefix_schema' => '', 'url' => null,
]);
config(['database.connections.lock_qa' => $qaConfig]);
$qa = DB::connection('lock_qa');
$identity = $qa->selectOne("SELECT USER AS name, SYS_CONTEXT('USERENV','CON_NAME') AS pdb FROM DUAL");
if ($identity->name !== 'GTIN_LOCK_QA' || $identity->pdb !== 'XEPDB1' || $qa->table('USER_TABLES')->exists()) throw new RuntimeException('Fresh GTIN_LOCK_QA in XEPDB1 required');
$procedures = ['PROJ1_2_SAVE_COMP_BOM_SEML1_ID', 'PROJ1_2_SAVE_COMP_BOM_SEML2_ID', 'PROJ1_2_SAVE_COMP_BOM_SL1_M4ID', 'PROJ1_2_SAVE_INSUPD_ETS'];
$tables = ['PROJ1_2_DML_FG_MATTYPE_1', 'PROJ1_2_DML_SEMI_L1_BOM', 'PROJ1_2_DML_SEMI_L1_ID', 'PROJ1_2_DML_SEMI_L2_BOM', 'PROJ1_2_DML_SEMI_L2_ID', 'PROJ1_2_DML_SEMI_L1_COMP_M4', 'PROJ1_2_DML_SEMI_L1_M4_COMP_M4', 'PROJ1_2_MASTER_MATERIAL', 'PROJ1_2_TRN_LOG'];
$sequences = [];
$definitions = [];
$owner = $source->selectOne('SELECT USER AS name FROM DUAL')->name;
for ($i = 0; $i < count($procedures); $i++) {
    $name = $procedures[$i];
    foreach ($source->select('SELECT REFERENCED_NAME AS name, REFERENCED_TYPE AS type FROM USER_DEPENDENCIES WHERE NAME = ? AND REFERENCED_OWNER = ?', [$name, $owner]) as $dep) {
        if ($dep->type === 'TABLE') $tables[] = $dep->name;
        if ($dep->type === 'SEQUENCE') $sequences[] = $dep->name;
        if ($dep->type === 'PROCEDURE' && !in_array($dep->name, $procedures, true)) $procedures[] = $dep->name;
    }
    $text = implode('', array_map(fn ($r) => is_resource($r->text) ? stream_get_contents($r->text) : $r->text, $source->select('SELECT TEXT FROM USER_SOURCE WHERE NAME = ? AND TYPE = ? ORDER BY LINE', [$name, 'PROCEDURE'])));
    if ($text === '') throw new RuntimeException('Missing procedure definition '.$name);
    $definitions[] = 'CREATE OR REPLACE '.$text;
}
foreach (array_unique($tables) as $table) {
    if (!preg_match('/^PROJ1_[A-Z0-9_]+$/', $table)) throw new RuntimeException('Unexpected dependency');
    $columns = $source->select('SELECT COLUMN_NAME, DATA_TYPE, DATA_LENGTH, DATA_PRECISION, DATA_SCALE FROM USER_TAB_COLUMNS WHERE TABLE_NAME = ? ORDER BY COLUMN_ID', [$table]);
    if (!$columns) throw new RuntimeException('Missing column metadata '.$table);
    $defs = array_map(function ($c) {
        $type = $c->data_type;
        if (in_array($type, ['VARCHAR2', 'CHAR', 'RAW'])) $type .= '('.$c->data_length.')';
        elseif (in_array($type, ['NVARCHAR2', 'NCHAR'])) $type .= '('.(int) ($c->data_length / 2).')';
        elseif ($type === 'NUMBER' && $c->data_precision !== null) $type .= '('.$c->data_precision.','.($c->data_scale ?? 0).')';
        return '"'.$c->column_name.'" '.$type;
    }, $columns);
    $qa->unprepared('CREATE TABLE '.$table.' ('.implode(',', $defs).')');
}
foreach (array_unique($sequences) as $seq) $qa->unprepared('CREATE SEQUENCE '.$seq);
foreach ($definitions as $sql) {
    try { $qa->unprepared($sql); }
    catch (Illuminate\Database\QueryException $e) { if (!str_contains($e->getMessage(), 'ORA-24344')) throw $e; }
}
// Definitions may reference procedures copied later in the dependency graph.
for ($pass = 0; $pass < count($procedures); $pass++) {
    foreach ($procedures as $proc) {
        try { $qa->unprepared('ALTER PROCEDURE '.$proc.' COMPILE'); }
        catch (Illuminate\Database\QueryException $e) { if (!str_contains($e->getMessage(), 'ORA-24344')) throw $e; }
    }
}
if ($qa->table('USER_ERRORS')->where('ATTRIBUTE', 'ERROR')->exists()) throw new RuntimeException('QA procedure compile errors: '.json_encode($qa->table('USER_ERRORS')->get()));
// Minimal LOV fixtures; service filtering and procedure calls remain real.
foreach ([
 'PROJ1_2_SEMI_L1_COM_EXS_ID_LS' => "SELECT '40070039' M4_ID, 'Fixture raw material' M4_DESCRIPTION FROM DUAL",
 'PROJ1_2_SEMI_L1_COM_EXS_MAT_LS' => "SELECT '4' MATTYPE FROM DUAL",
 'PROJ1_2_SEMI_L1_COM_EXS_SUB_LS' => "SELECT '0' SUB_MATTYPE FROM DUAL",
 'PROJ1_2_SEMI_L2_COM_EXS_ID_LS' => "SELECT '50010017' M5_ID, 'Fixture packaging' M5_DESCRIPTION FROM DUAL",
 'PROJ1_2_SEMI_L2_COM_EXS_MAT_LS' => "SELECT '5' MATTYPE FROM DUAL",
 'PROJ1_2_SEMI_L2_COM_EXS_SUB_LS' => "SELECT '0' SUB_MATTYPE, 'Fixture category' DESC_PROD_CAT FROM DUAL",
 'PROJ1_2_SEMI_L2_COM_EXS_CAT_LS' => "SELECT '5001' CODE_SUB_CAT, 'Fixture subcategory' DESC_PROD_SUB_CAT FROM DUAL",
 'PROJ1_2_COMP_M4_MAT_ID_M4_LS' => "SELECT '40070039' M4_COMP_M4_ID, 'Fixture raw material' M4_COMP_M4_DESC FROM DUAL",
] as $view => $sql) $qa->unprepared('CREATE VIEW '.$view.' AS '.$sql);
// Switch the application services to QA only after definition copying is complete.
config(['database.connections.oracle' => $qaConfig, 'database.default' => 'oracle', 'session.driver' => 'array']);
DB::purge('oracle');
$db = DB::connection('oracle');
if ($db->selectOne('SELECT USER AS name FROM DUAL')->name !== 'GTIN_LOCK_QA') throw new RuntimeException('QA schema required');
$checks = 0;
$check = function ($condition, $message) use (&$checks) { if (!$condition) throw new RuntimeException($message); $checks++; echo 'PASS '.$message.PHP_EOL; };
$put = fn ($table, $row) => $db->table($table)->insert($row);
$base = ['fgMaterialId' => '11BR00012', 'fgBomId' => 'B11BR00012_RK_01', 'semiFgLv1BomId' => 'B30BR0001_RK_01', 'semiFgLv2BomId' => 'B20BR00007_RK_01'];
$put('PROJ1_2_DML_FG_MATTYPE_1', ['MATERIAL_ID_FG_1' => $base['fgMaterialId'], 'FG_BOM_ID' => $base['fgBomId'], 'STATUS_ROW' => 'INS']);
foreach ([1,2] as $lv) {
    $put("PROJ1_2_DML_SEMI_L{$lv}_BOM", ['FG_BOM_ID' => $base['fgBomId'], "SEMI_FG_LV{$lv}_BOM_ID" => $base["semiFgLv{$lv}BomId"], 'SEMI_FG_LV2_BOM_ID' => $base['semiFgLv2BomId'], 'STATUS_ROW' => 'INS']);
    $put("PROJ1_2_DML_SEMI_L{$lv}_ID", ['FG_BOM_ID' => $base['fgBomId'], "SEMI_FG_LV{$lv}_BOM_ID" => $base["semiFgLv{$lv}BomId"], "SEMI_FG_LV{$lv}_ID" => $lv === 1 ? '30BR0001' : '20BR00007', 'STATUS_ROW' => 'INS']);
}
$put('PROJ1_2_DML_SEMI_L1_COMP_M4', ['SEMI_FG_LV1_BOM_ID' => $base['semiFgLv1BomId'], 'MATERIAL_ID_M4' => '400200004', 'STATUS_ROW' => 'INS']);
foreach ([['40070039', '40'], ['50010017', '50']] as [$id, $type]) $put('PROJ1_2_MASTER_MATERIAL', ['MATERIAL_ID' => $id, 'MAT_TYPE' => $type, 'SUB_TYPE' => '01']);
$components = app(ExistingComponentService::class);
// Reproduce the reported query first, then prove that actual service queries lock successfully.
$db->beginTransaction();
try {
    $db->table('PROJ1_2_DML_SEMI_L1_BOM')->where('FG_BOM_ID', $base['fgBomId'])->limit(2)->lockForUpdate()->get();
    throw new RuntimeException('Old query unexpectedly succeeded');
} catch (Illuminate\Database\QueryException $e) { $check(str_contains($e->getMessage(), 'ORA-02014'), 'old pagination lock reproduces ORA-02014'); }
finally { $db->rollBack(); }
$db->beginTransaction();
$components->context('raw4', $base + ['masterMaterialId' => '400200004'], true);
try {
    $qa->beginTransaction();
    $qa->select('SELECT * FROM PROJ1_2_DML_FG_MATTYPE_1 WHERE MATERIAL_ID_FG_1 = ? FOR UPDATE NOWAIT', [$base['fgMaterialId']]);
    throw new RuntimeException('Parent lock was not held');
} catch (Illuminate\Database\QueryException $e) { $check(str_contains($e->getMessage(), 'ORA-00054'), 'parent remains locked against a concurrent transaction'); }
finally { if ($qa->transactionLevel()) $qa->rollBack(); $db->rollBack(); }
$app['redirect']->setSession($app['session']->driver());
$controller = app(ExistingComponentController::class);
foreach (['semi1', 'semi2', 'raw4'] as $scope) {
    $input = $base + ['masterMaterialId' => '400200004', 'mattype' => $scope === 'semi2' ? '5' : '4', 'subMattype' => '0', 'productSubCat' => '5001', 'componentId' => $scope === 'semi2' ? '50010017' : '40070039'];
    $request = Illuminate\Http\Request::create('/existing-components/'.$scope, 'POST', $input);
    $request->setUserResolver(fn () => new App\Models\User(['user_login' => 'qa', 'role' => 'user']));
    $request->setLaravelSession($app['session']->driver());
    $response = $controller->save($request, $scope);
    $rows = $components->rows($scope, $components->context($scope, $input));
    $check(count($rows) === 1, $scope.' Save uses actual procedure under the exact parent');
    $expectedRoute = $scope === 'raw4' ? 'packmaterial.semi-fg-lv1-bom.new' : 'material-levels.semi-fg-lv'.($scope === 'semi1' ? '1' : '2').'.new';
    $target = parse_url($response->getTargetUrl()); parse_str($target['query'], $params);
    $check(str_contains($response->getTargetUrl(), parse_url(route($expectedRoute), PHP_URL_PATH)) && ($params['mode'] ?? $params['actionMode'] ?? '') === 'view', $scope.' redirect returns to Detail in View mode');
    for ($refresh = 0; $refresh < 2; $refresh++) {
        $get = Illuminate\Http\Request::create('/existing-components/'.$scope, 'GET', $input + ['mode' => 'view', 'no' => $rows[0]['no']]);
        $get->headers->set('X-Inertia', 'true');
        $props = $controller->index($get, $scope)->toResponse($get)->getData(true)['props'];
        $check($props['selected']['code'] === $input['componentId'], $scope.' saved Existing detail reload '.$refresh);
    }
}
echo 'Oracle parent lock integration: '.$checks.' checks passed.'.PHP_EOL;
