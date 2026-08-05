<?php
$root=dirname(__DIR__);$failures=array();
$main=file_get_contents($root.'/ikon-seo.php');
if(false===strpos($main,'Version: 2.0.1')||false===strpos($main,"define( 'IKON_SEO_VERSION', '2.0.1' )"))$failures[]='Plugin version is not 2.0.1.';
if(false===strpos($main,'class-ikon-seo-production-certification.php'))$failures[]='Production Certification class is not loaded.';
$plugin=file_get_contents($root.'/includes/class-ikon-seo-plugin.php');
if(false===strpos($plugin,"const DB_VERSION = '40.0'"))$failures[]='Database component version is not 39.0.';
foreach(array('ikon_seo_support_contracts','ikon_seo_production_certifications','ikon_seo_certification_checks','ikon_seo_rollout_waves','ikon_seo_certification_events','Ikon_SEO_Production_Certification::CRON_HOOK') as $needle){if(false===strpos($plugin,$needle))$failures[]='Production platform migration/cron integration missing: '.$needle;}
$admin=file_get_contents($root.'/includes/class-ikon-seo-admin.php');
foreach(array("'production-certification'",'render_production_certification','ikon_seo_production_certification_action') as $needle){if(false===strpos($admin,$needle))$failures[]='Production Certification admin integration missing: '.$needle;}
$auth=file_get_contents($root.'/includes/class-ikon-seo-auth.php');
foreach(array('can_production_certification',"'approve_certification'","'approve_rollout'","'close_rollout'") as $needle){if(false===strpos($auth,$needle))$failures[]='Production Certification approval-scope routing missing: '.$needle;}
$rest=file_get_contents($root.'/includes/class-ikon-seo-rest.php');
foreach(array("'/production-certification'",'production_certification_report','production_certification_sync','ProductionCertificationSync','can_production_certification') as $needle){if(false===strpos($rest,$needle))$failures[]='Production Certification REST integration missing: '.$needle;}
$openapi=json_decode(file_get_contents($root.'/openapi/ikon-seo-openapi.json'),true);if(!is_array($openapi))$failures[]='Static OpenAPI JSON is invalid.';else{$ops=0;$ids=array();foreach(($openapi['paths']??array()) as $path){foreach($path as $method=>$op){if(in_array($method,array('get','post','put','patch','delete'),true)){$ops++;$ids[]=$op['operationId']??'';}}}if(30!==$ops||count($ids)!==count(array_unique($ids)))$failures[]='Focused OpenAPI must contain exactly 30 unique operations.';if(empty($openapi['paths']['/production-certification']['post'])||empty($openapi['components']['schemas']['ProductionCertificationSync']))$failures[]='Production Certification focused OpenAPI contract is missing.';if(!empty($openapi['paths']['/reviews/{id}/merge']))$failures[]='Focused schema still exposes the replaced remote merge action.';if('2.0.1'!==($openapi['info']['version']??''))$failures[]='Static OpenAPI version is not 2.0.1.';}
$engine=file_get_contents($root.'/includes/class-ikon-seo-production-certification.php');
foreach(array('manual_distribution_only','Critical production checks cannot be waived','A different administrator must approve','evidence_fingerprint','Manual installation remains required') as $needle){if(false===stripos($engine,$needle))$failures[]='Production certification governance behavior missing: '.$needle;}
foreach(array('wp_publish_post','wp_update_post','wp_delete_post','Plugin_Upgrader','download_url(','wp_mail') as $needle){if(false!==strpos($engine,$needle))$failures[]='Production Certification contains prohibited live action: '.$needle;}
foreach(array('docs/PRODUCTION-AGENCY-PLATFORM.md','docs/UPGRADE-v2.0.md') as $relative){if(!file_exists($root.'/'.$relative))$failures[]='Release documentation missing: '.$relative;}
if($failures){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);}echo "Ikon SEO v2.0.1 production platform, safety and focused-schema tests passed.\n";
