<?php
$root=dirname(__DIR__);$failures=array();
$main=file_get_contents($root.'/ikon-seo.php');
if(false===strpos($main,'Version: 2.0.1')||false===strpos($main,"define( 'IKON_SEO_VERSION', '2.0.1' )"))$failures[]='Plugin version is not 2.0.1.';
if(false===strpos($main,'class-ikon-seo-staging-validation.php'))$failures[]='Staging Validation class is not loaded.';
$plugin=file_get_contents($root.'/includes/class-ikon-seo-plugin.php');
if(false===strpos($plugin,"const DB_VERSION = '40.0'"))$failures[]='Database component version is not 40.0.';
foreach(array('ikon_seo_staging_runs','ikon_seo_staging_checks','ikon_seo_staging_events','Ikon_SEO_Staging_Validation::CRON_HOOK') as $needle){if(false===strpos($plugin,$needle))$failures[]='Staging migration or cron wiring missing: '.$needle;}
$admin=file_get_contents($root.'/includes/class-ikon-seo-admin.php');
foreach(array("'staging-validation'",'render_staging_validation','ikon_seo_staging_validation_action') as $needle){if(false===strpos($admin,$needle))$failures[]='Staging admin integration missing: '.$needle;}
$auth=file_get_contents($root.'/includes/class-ikon-seo-auth.php');
foreach(array('can_staging_validation',"'approve_run'") as $needle){if(false===strpos($auth,$needle))$failures[]='Staging scope routing missing: '.$needle;}
$rest=file_get_contents($root.'/includes/class-ikon-seo-rest.php');
foreach(array("'/staging-validation'",'staging_validation_report','staging_validation_sync','StagingValidationSync','can_staging_validation') as $needle){if(false===strpos($rest,$needle))$failures[]='Staging REST integration missing: '.$needle;}
$openapi=json_decode(file_get_contents($root.'/openapi/ikon-seo-openapi.json'),true);
if(!is_array($openapi))$failures[]='Static OpenAPI JSON is invalid.';else{$ops=0;$ids=array();foreach(($openapi['paths']??array()) as $path){foreach($path as $method=>$op){if(in_array($method,array('get','post','put','patch','delete'),true)){$ops++;$ids[]=$op['operationId']??'';}}}if(30!==$ops||count($ids)!==count(array_unique($ids)))$failures[]='Focused OpenAPI must contain exactly 30 unique operations.';if(empty($openapi['paths']['/staging-validation']['post'])||empty($openapi['components']['schemas']['StagingValidationSync']))$failures[]='Staging Validation focused OpenAPI contract is missing.';if(!empty($openapi['paths']['/reviews']))$failures[]='Focused schema still contains the replaced review-list operation.';if('2.0.1'!==($openapi['info']['version']??''))$failures[]='Static OpenAPI version is not 2.0.1.';}
foreach(array('docs/STAGING-VALIDATION-EVIDENCE-RUNNER.md','docs/UPGRADE-v2.0.1.md') as $relative){if(!file_exists($root.'/'.$relative))$failures[]='Release documentation missing: '.$relative;}
if($failures){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);} echo "Ikon SEO v2.0.1 staging validation integration and focused-schema tests passed.\n";
