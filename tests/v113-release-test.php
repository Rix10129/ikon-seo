<?php
$root=dirname(__DIR__); $failures=array();
$main=file_get_contents($root.'/ikon-seo.php');
$plugin=file_get_contents($root.'/includes/class-ikon-seo-plugin.php');
$rest=file_get_contents($root.'/includes/class-ikon-seo-rest.php');
$admin=file_get_contents($root.'/includes/class-ikon-seo-admin.php');
if(false===strpos($main,'Version: 2.0.1')||false===strpos($main,"IKON_SEO_VERSION', '2.0.1"))$failures[]='Plugin version is not 2.0.1.';
if(false===strpos($plugin,"const DB_VERSION = '40.0'"))$failures[]='Database version is not 35.0.';
foreach(array('ikon_seo_patterns','ikon_seo_pattern_evidence','ikon_seo_pattern_events','Ikon_SEO_Pattern_Library::CRON_HOOK') as $needle){if(false===strpos($plugin,$needle))$failures[]='Plugin wiring missing: '.$needle;}
foreach(array('Ikon_SEO_Pattern_Library $pattern_library','/pattern-library','syncIkonSEOPatternLibrary','PatternLibrarySync') as $needle){if(false===strpos($rest,$needle))$failures[]='REST wiring missing: '.$needle;}
foreach(array("'pattern-library'",'render_pattern_library','pattern_library_action') as $needle){if(false===strpos($admin,$needle))$failures[]='Admin wiring missing: '.$needle;}
$d=json_decode(file_get_contents($root.'/openapi/ikon-seo-openapi.json'),true); $ops=0;$ids=array(); foreach(($d['paths']??array()) as $path){foreach($path as $method=>$op){if(in_array($method,array('get','post','put','patch','delete'),true)){$ops++;$ids[]=$op['operationId']??'';}}}
if($ops!==30||count($ids)!==count(array_unique($ids)))$failures[]='Static OpenAPI must contain exactly 30 unique operations.';
if(empty($d['paths']['/pattern-library']['post'])||empty($d['components']['schemas']['PatternLibrarySync']))$failures[]='Static Pattern Library OpenAPI contract is missing.';
if($failures){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);} echo "Pattern Library wiring remains intact in Ikon SEO v2.0.1.\n";
