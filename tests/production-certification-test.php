<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'IKON_SEO_VERSION', '2.0.0' );
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( is_scalar( $value ) ? (string) $value : '' ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( is_scalar( $value ) ? (string) $value : '' ) ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function current_time() { return '2026-08-05 00:00:00'; }
function __( $value ) { return $value; }
class WP_Error { private $code; private $message; public function __construct($c,$m){$this->code=$c;$this->message=$m;} public function get_error_code(){return $this->code;} public function get_error_message(){return $this->message;} }
class Ikon_SEO_Plugin { const DB_VERSION='39.0'; public static function settings(){ return array('certification_max_rollout_sites'=>100,'certification_monitor_batch'=>3); } }
class Ikon_SEO_Platform_Hardening {}
class Ikon_SEO_Deployment_Control {}
class Ikon_SEO_Production_Health {}
class Ikon_SEO_Workspace_History {}
class Ikon_SEO_Logger {}
require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-production-certification.php';

$reflection = new ReflectionClass( 'Ikon_SEO_Production_Certification' );
$engine = $reflection->newInstanceWithoutConstructor();
$failures = array();

$contract = $engine->normalize_support_contract( array( 'contract_key'=>'prod','label'=>'Production','channels'=>array('stable','unsafe'),'support_window_days'=>30,'recovery_drill_days'=>2 ) );
if ( 90 !== $contract['support_window_days'] || 7 !== $contract['recovery_drill_days'] ) { $failures[]='Support and recovery windows were not bounded safely.'; }
if ( array('stable') !== $contract['channels'] ) { $failures[]='Release-channel allowlist failed.'; }
foreach ( array('manual_distribution_only','remote_publish_disabled','client_data_isolated') as $flag ) { if ( empty( $contract[$flag] ) ) { $failures[]='Mandatory safety flag missing: '.$flag; } }
foreach ( array('automatic_installation','automatic_rollback') as $flag ) { if ( ! array_key_exists($flag,$contract) || false !== $contract[$flag] ) { $failures[]='Unsafe automation flag enabled: '.$flag; } }
if ( $engine->contract_fingerprint($contract) !== $engine->contract_fingerprint(array_reverse($contract,true)) ) { $failures[]='Support-contract fingerprint is not deterministic.'; }

$waiver = $engine->normalize_check('package_integrity',array('status'=>'waived','evidence'=>'none'));
if ( ! ( $waiver instanceof WP_Error ) ) { $failures[]='Critical certification check was allowed to be waived.'; }
$noncritical = $engine->normalize_check('cache_compatibility',array('status'=>'waived','evidence'=>'Not used'));
if ( $noncritical instanceof WP_Error || 'waived' !== $noncritical['status'] ) { $failures[]='Non-critical documented waiver was not accepted.'; }

$approved_contract = array_merge($contract,array('status'=>'approved','product_version'=>'2.0.0','database_version'=>'39.0','recovery_drill_days'=>90,'channels'=>array('stable')));
$checks=array(); foreach($engine->allowed_checks() as $key=>$def){$checks[$key]=array('check_key'=>$key,'status'=>'passed','critical'=>$def['critical'],'evidence_hash'=>str_repeat('a',64),'observed_at'=>'2026-08-04 00:00:00');}
$platform=array('status'=>'ready','fingerprint'=>str_repeat('b',64),'checked_at'=>'2026-08-04 00:00:00');
$deployment=array('status'=>'verified','release_fingerprint'=>str_repeat('c',64),'verified_at'=>'2026-08-04 00:00:00');
$recovery=array('id'=>7,'payload_hash'=>str_repeat('d',64),'tested_at'=>'2026-08-01 00:00:00');
$gate=$engine->certification_gate($approved_contract,$checks,$platform,$deployment,$recovery,'production',strtotime('2026-08-05 UTC'));
if('ready'!==$gate['status']||100!==$gate['score']||false!==$gate['automatic_installation']||false!==$gate['publishes_automatically']){$failures[]='Clean production-certification gate failed or safety flags changed.';}
$checks['tenant_isolation']['status']='failed';
$blocked=$engine->certification_gate($approved_contract,$checks,$platform,$deployment,$recovery,'production',strtotime('2026-08-05 UTC'));
if('blocked'!==$blocked['status']){$failures[]='Failed critical tenant-isolation check did not block certification.';}
$checks['tenant_isolation']['status']='passed';
$old_recovery=$recovery;$old_recovery['tested_at']='2025-01-01 00:00:00';
if('blocked'!==$engine->certification_gate($approved_contract,$checks,$platform,$deployment,$old_recovery,'production',strtotime('2026-08-05 UTC'))['status']){$failures[]='Outdated restore drill did not block production certification.';}
$bad_channel=$approved_contract;$bad_channel['channels']=array('candidate');
if('blocked'!==$engine->certification_gate($bad_channel,$checks,$platform,$deployment,$recovery,'production',strtotime('2026-08-05 UTC'))['status']){$failures[]='Production certification allowed a non-stable channel.';}

$source=file_get_contents(dirname(__DIR__).'/includes/class-ikon-seo-production-certification.php');
foreach(array('Plugin_Upgrader','WP_Upgrader','download_url(','activate_plugin(','deactivate_plugins(','wp_publish_post','wp_update_post','wp_delete_post','wp_mail') as $needle){if(false!==strpos($source,$needle)){$failures[]='Production Certification contains prohibited automatic or live-change primitive: '.$needle;}}
foreach(array('manual_distribution_only','automatic_installation','automatic_rollback','remote_publish_disabled','changes_live_site_content') as $needle){if(false===strpos($source,$needle)){$failures[]='Production safety declaration missing: '.$needle;}}

if($failures){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);} echo "Production Certification support-contract, evidence-gate and no-automatic-distribution tests passed.\n";
