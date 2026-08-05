<?php
function absint($v){return abs((int)$v);} function sanitize_key($v){return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$v));} function sanitize_text_field($v){return trim(strip_tags((string)$v));}
define('ABSPATH', __DIR__ . '/');
require_once dirname(__DIR__).'/includes/class-ikon-seo-staging-validation.php';
$failures=array();
$r=new ReflectionClass('Ikon_SEO_Staging_Validation');
$engine=$r->newInstanceWithoutConstructor();
$defs=$engine->allowed_checks();
$passed=array(); foreach($defs as $key=>$def){$passed[]=array('check_key'=>$key,'status'=>'passed','message'=>'Passed');}
$gate=$engine->gate($passed);
if('review_ready'!==($gate['status']??'' )||100!==($gate['score']??0))$failures[]='All-passed evidence did not become review ready.';
$critical_warning=$passed; foreach($critical_warning as &$check){if('package_integrity'===$check['check_key']){$check['status']='warning';$check['message']='Signature could not be verified.';break;}} unset($check);
$gate=$engine->gate($critical_warning); if('blocked'!==($gate['status']??''))$failures[]='A warning critical check did not block certification.';
$critical_skipped=$passed; foreach($critical_skipped as &$check){if('cron_loopback'===$check['check_key']){$check['status']='skipped';break;}} unset($check);
$gate=$engine->gate($critical_skipped); if('blocked'!==($gate['status']??''))$failures[]='A skipped critical check did not block certification.';
$advisory_warning=$passed; foreach($advisory_warning as &$check){if('elementor_compatibility'===$check['check_key']){$check['status']='warning';break;}} unset($check);
$gate=$engine->gate($advisory_warning); if('review_ready'!==($gate['status']??'' )||empty($gate['warnings']))$failures[]='An advisory warning incorrectly blocked certification.';
foreach(array('publishes_automatically','installs_plugins','changes_live_site_content') as $flag){if(!array_key_exists($flag,$gate)||false!==$gate[$flag])$failures[]='Safety flag is missing or unsafe: '.$flag;}
$tokenMethod=$r->getMethod('find_prohibited_executables');$tokenMethod->setAccessible(true);
if($tokenMethod->invoke($engine,"<?php \$label='wp_publish_post'; // Plugin_Upgrader ?>"))$failures[]='Quoted or commented primitive names caused a false positive.';
$functionMatches=$tokenMethod->invoke($engine,"<?php wp_publish_post(123); ?>");if(!in_array('wp_publish_post()',$functionMatches,true))$failures[]='Executable publish function was not detected.';
$classMatches=$tokenMethod->invoke($engine,"<?php \$u = new Plugin_Upgrader(); ?>");if(!in_array('new plugin_upgrader',$classMatches,true))$failures[]='Executable upgrader construction was not detected.';
$source=file_get_contents(dirname(__DIR__).'/includes/class-ikon-seo-staging-validation.php');
foreach(array('wp_publish_post','download_url','wp_mail','wp_delete_post') as $function){if(preg_match('/^[\t ]*'.$function.'[\t ]*\(/m',$source))$failures[]='Staging runner contains prohibited executable call: '.$function;} if(preg_match('/new[\t ]+Plugin_Upgrader\b/',$source))$failures[]='Staging runner instantiates Plugin_Upgrader.';
if($failures){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);} echo "Staging Validation gate and no-live-change tests passed.\n";
