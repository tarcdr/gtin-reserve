<?php
// Only for the isolated container started with --network none. Never uses the application's Oracle credentials.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use App\Services\ExistingComponentService;
use App\Services\ExistingLevelService;
$connection = array_replace(config('database.connections.oracle'), [
    'tns' => '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=127.0.0.1)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=XEPDB1)))',
    'host' => '127.0.0.1', 'port' => '1521', 'service_name' => 'XEPDB1', 'database' => 'XEPDB1',
    'username' => 'GTIN_QA', 'password' => 'gtin_test_only_password', 'prefix_schema' => '', 'url' => null,
]);
config(['database.connections.oracle' => $connection, 'database.default' => 'oracle']); DB::purge('oracle'); $db=DB::connection('oracle');
if (strtoupper($db->selectOne('SELECT USER AS NAME FROM DUAL')->name) !== 'GTIN_QA') throw new RuntimeException('QA schema required');
foreach ($db->select('SELECT TABLE_NAME FROM USER_TABLES') as $table) $db->unprepared('TRUNCATE TABLE '. $table->table_name);
$checks = 0;
$check = function ($condition, $message) use (&$checks) { if (!$condition) throw new RuntimeException($message); $checks++; echo 'PASS '.$message.PHP_EOL; };
$put = fn ($table, $row) => $db->table($table)->insert($row);
$put('PROJ1_BRAND', ['BRAND_ABB'=>'BR','BRAND'=>'Test Brand']);
foreach (['SRC','TARGET'] as $fg) $put('PROJ1_2_DML_FG_MATTYPE_1', ['MATERIAL_ID_FG_1'=>$fg,'FG_BOM_ID'=>'BFG'.$fg,'DESC_FG_BOM_ID'=>'Test '.$fg,'BRAND'=>'Test Brand','SITE'=>'01','STATUS_ROW'=>'INS']);
foreach (['SRC','TARGET'] as $suffix) {
 $fg=$suffix; $b2='B2'.$suffix; $b1='B3'.$suffix;
 $put('PROJ1_2_DML_SEMI_L2_BOM',['NO'=>'1','SEMI_FG_LV2_BOM_ID'=>$b2,'DESC_SEMI_FG_LV2_BOM_ID'=>'Level2 '.$suffix,'FG_BOM_ID'=>'BFG'.$fg,'MATERIAL_ID_FG_1'=>$fg,'SITE'=>'01','STATUS_ROW'=>'INS']);
 $put('PROJ1_2_DML_SEMI_L2_ID',['NO'=>'1','SEMI_FG_LV2_ID'=>'20'.$suffix,'DESC_SEMI_FG_LV2_ID'=>'Level2 material','SEMI_FG_LV2_BOM_ID'=>$b2,'FG_BOM_ID'=>'BFG'.$fg,'MATERIAL_ID_FG_1'=>$fg,'MATTYPE_SEMI_FG_L2ID'=>'2','SUB_MATTYPE_SEMI_FG_L2ID'=>'0','UOM_SEMI_FG_L2ID'=>'EA','STATUS_ROW'=>'INS']);
 $put('PROJ1_2_DML_SEMI_L1_BOM',['NO'=>'1','SEMI_FG_LV1_BOM_ID'=>$b1,'DESC_SEMI_FG_LV1_BOM_ID'=>'Level1 '.$suffix,'FG_BOM_ID'=>'BFG'.$fg,'MATERIAL_ID_FG_1'=>$fg,'SEMI_FG_LV2_BOM_ID'=>$b2,'SEMI_FG_LV2_ID'=>'20'.$suffix,'STATUS_ROW'=>'INS']);
 $put('PROJ1_2_DML_SEMI_L1_ID',['NO'=>'1','SEMI_FG_LV1_ID'=>'30'.$suffix,'DESC_SEMI_FG_LV1_ID'=>'Level1 material','SEMI_FG_LV1_BOM_ID'=>$b1,'FG_BOM_ID'=>'BFG'.$fg,'MATERIAL_ID_FG_1'=>$fg,'SEMI_FG_LV2_BOM_ID'=>$b2,'SEMI_FG_LV2_ID'=>'20'.$suffix,'MATTYPE_SEMI_FG_L1ID'=>'3','SUB_MATTYPE_SEMI_FG_L1ID'=>'0','UOM_SEMI_FG_L1ID'=>'EA','STATUS_ROW'=>'INS']);
}
foreach ([['5001','50'],['4001','40']] as [$id,$type]) $put('PROJ1_2_MASTER_MATERIAL',['MATERIAL_ID'=>$id,'MATERIAL_DESC'=>'Master '.$id,'MAT_TYPE'=>$type,'SUB_TYPE'=>'01']);
$put('PROJ1_2_DML_SEMI_L1_COMP_M4',['NO'=>'1','MATERIAL_ID_M4'=>'MASTER4','SEMI_FG_LV1_BOM_ID'=>'B3TARGET','SEARCH_DESCRIPTION'=>'Parent raw material','STATUS_ROW'=>'INS']);
$components=app(ExistingComponentService::class);$levels=app(ExistingLevelService::class);
$base=['fgMaterialId'=>'TARGET','fgBomId'=>'BFGTARGET'];
$check($levels->availability(2)['ready'] && $levels->availability(1)['ready'], 'level views and procedures valid');
foreach ([2,1] as $level) {
 $input=$base+['brand'=>'BR','mattype'=>$level===2?'2':'3','subMattype'=>'0','componentId'=>$level===2?'B2SRC':'B3SRC','semiFgLv2BomId'=>'B2SRC'];
 $options=$levels->options($level,$input);$check(count($options)>=2,'LV'.$level.' source BOM list');
 $before=$db->table('PROJ1_2_DML_SEMI_L'.$level.'_BOM')->count();
 $levels->save($level,$input,'qa','user');
 $check($db->table('PROJ1_2_DML_SEMI_L'.$level.'_BOM')->count()===$before,'LV'.$level.' Existing leaves New source table unchanged');
 $check(count($levels->rows($level,$levels->context($level,$input)))===1,'LV'.$level.' Existing BOM and ID linked');
}
foreach (['semi2','semi1'] as $scope) {
 $input=$base+['semiFgLv2BomId'=>'B2TARGET','semiFgLv1BomId'=>'B3TARGET','mattype'=>$scope==='semi2'?'5':'4','subMattype'=>'0','productSubCat'=>'5001','componentId'=>$scope==='semi2'?'5001':'4001'];
 $check($components->availability($scope)['ready'],$scope.' objects ready');
 $components->save($scope,$input,'qa','user');$rows=$components->rows($scope,$components->context($scope,$input));
 $check(count($rows)===1,$scope.' Existing component saved with correct parent');
 $components->delete($scope,$input+['no'=>$rows[0]['no']],'qa','user');
 $check(count($components->rows($scope,$components->context($scope,$input)))===0,$scope.' Existing component delete');
}
$raw=$base+['semiFgLv1BomId'=>'B3TARGET','masterMaterialId'=>'MASTER4','componentId'=>'4001'];
$components->save('raw4',$raw,'qa','user');$rawRows=$components->rows('raw4',$components->context('raw4',$raw));
$check(count($rawRows)===1,'raw Existing component actual procedure');
$components->delete('raw4',$raw+['no'=>$rawRows[0]['no']],'qa','user');
$put('PROJ1_2_DML_SEMI_L1_COMP_M4_TP',['MATERIAL_ID_M4_TMP'=>'40010001','USER_CREATE_TMP'=>'qa','STATUS_USED'=>'NEW']);
$user=new App\Models\User(['user_login'=>'qa','role'=>'user']);
$controller=new class extends App\Http\Controllers\RawChildComponentController { public function __construct() {} };
$payload=$raw+['mattype'=>'4','subMattype'=>'0','productCat'=>'40','productSubCat'=>'4001','searchDesc'=>'Child description','fullDescEn'=>'Child EN','fullDescTh'=>'Child TH','uom'=>'EA'];$payload['componentId']='40010001';
$request=Illuminate\Http\Request::create('/raw-child-components','PATCH',$payload);$request->setUserResolver(fn()=>$user);
$controller->save($request);
$check(count($components->rawNewRows($components->context('raw4',$raw)))===1,'nested New M4 controller and procedure insert');
$request->merge(['searchDesc'=>'Edited child']);$controller->save($request);
$check($components->rawNewRows($components->context('raw4',$raw))[0]['label']==='Edited child','nested New M4 scoped edit');
$request->merge(['childId'=>'40010001']);$controller->remove($request);
$check(count($components->rawNewRows($components->context('raw4',$raw)))===0,'nested New M4 scoped delete');
$pack = new class extends App\Http\Controllers\PackMaterialController {
 public function __construct() {}
 public function saveNew1(array $data): void { $this->saveSemiFgLv1ComponentBom($data,'qa','user'); }
 public function saveNew2(array $data): void { $this->saveSemiFgLv2ComponentBom($data,'qa','user'); }
};
$newData=$base+['semiFgLvBomId'=>'B2TARGET','mattype'=>'5','subMattype'=>'0','productCat'=>'50','productSubCat'=>'5001','componentId'=>'500100010','searchDesc'=>'Target New','fullDescEn'=>'EN','fullDescTh'=>'TH','uom'=>'EA','site'=>'01'];
$put('PROJ1_2_DML_SEMI_L2_COMP_M5',['NO'=>'99','MATERIAL_ID_M5'=>'500100010','SEMI_FG_LV2_BOM_ID'=>'B2SRC','FG_BOM_ID'=>'BFGSRC','SEARCH_DESCRIPTION'=>'Foreign untouched','STATUS_ROW'=>'INS','USER_CREATE'=>'qa']);
$pack->saveNew2($newData);
$check($db->table('PROJ1_2_DML_SEMI_L2_COMP_M5')->where('FG_BOM_ID','BFGTARGET')->where('MATERIAL_ID_M5','500100010')->exists(),'New LV2 component application call matches 17-argument signature');
$check($db->table('PROJ1_2_DML_SEMI_L2_COMP_M5')->where('FG_BOM_ID','BFGSRC')->first()->search_description==='Foreign untouched','New LV2 leaves other parent component unchanged');
$newData['semiFgLvBomId']='B3TARGET';$newData['mattype']='4';$newData['productCat']='40';$newData['productSubCat']='4001';$newData['componentId']='400100010';
$put('PROJ1_2_DML_SEMI_L1_COMP_M4',['NO'=>'99','MATERIAL_ID_M4'=>'400100010','SEMI_FG_LV1_BOM_ID'=>'B3SRC','SEARCH_DESCRIPTION'=>'Foreign untouched','STATUS_ROW'=>'INS','USER_CREATE'=>'qa']);
$pack->saveNew1($newData);
$check($db->table('PROJ1_2_DML_SEMI_L1_COMP_M4')->where('SEMI_FG_LV1_BOM_ID','B3TARGET')->where('MATERIAL_ID_M4','400100010')->exists(),'New LV1 component actual application call');
$check($db->table('PROJ1_2_DML_SEMI_L1_COMP_M4')->where('SEMI_FG_LV1_BOM_ID','B3SRC')->first()->search_description==='Foreign untouched','New LV1 leaves other parent component unchanged');
$genRequest=Illuminate\Http\Request::create('/generate','GET',['ownerLevel'=>'semiFgLv1','productSubCat'=>'4001']);$genRequest->setUserResolver(fn()=>$user);
$generated=$pack->generateComponentId($genRequest)->getData(true);
$check(empty($generated['error']) && strlen($generated['componentId'])===9 && $generated['componentId']>'400100010','M4 generator considers New and nested saved IDs');
$reused=$pack->generateComponentId($genRequest)->getData(true);
$check($reused['componentId']===$generated['componentId'],'M4 reservation reused for same user');
$payload['componentId']=$generated['componentId'];$newRequest=Illuminate\Http\Request::create('/raw-child-components','PATCH',$payload);$newRequest->setUserResolver(fn()=>$user);$controller->save($newRequest);
$generatedAgain=$pack->generateComponentId($genRequest)->getData(true);
$check($generatedAgain['componentId']!==$generated['componentId'],'Saved nested M4 ID is never generated again');
$newRequest->merge(['childId'=>$generated['componentId']]);$controller->remove($newRequest);
foreach ([1,2] as $lv) {
 $rows=$levels->rows($lv,$levels->context($lv,$base+['semiFgLv2BomId'=>'B2SRC']));$r=$rows[0];
 $levels->delete($lv,$base+['semiFgLv2BomId'=>'B2SRC','componentId'=>$r['bomId'],'no'=>$r['no']],'qa','user');
 $check(count($levels->rows($lv,$levels->context($lv,$base+['semiFgLv2BomId'=>'B2TARGET'])))===0,'LV'.$lv.' Existing delete preserves source BOM');
}
echo 'Oracle integration checks: '.$checks.PHP_EOL;
