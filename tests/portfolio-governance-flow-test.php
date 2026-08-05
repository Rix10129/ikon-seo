<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function sanitize_title( $value ) { return trim( preg_replace( '/-+/', '-', preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $value ) ) ), '-' ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function esc_url_raw( $value ) { return (string) $value; }
function __( $value ) { return $value; }
function wp_json_encode( $value ) { return json_encode( $value ); }
function current_time( $type, $gmt = false ) { return '2026-08-04 10:30:00'; }
function home_url( $path = '/' ) { return 'https://agency.example' . $path; }
function rest_url( $path = '' ) { return 'https://agency.example/wp-json/' . ltrim( $path, '/' ); }
function untrailingslashit( $value ) { return rtrim( (string) $value, '/\\' ); }
function get_current_user_id() { return 7; }
function add_action() {}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
class WP_Error { public $code; public $message; public function __construct( $code='', $message='' ) { $this->code=$code; $this->message=$message; } public function get_error_code(){return $this->code;} }
$GLOBALS['options'] = array();
function get_option( $key, $default=false ) { return array_key_exists( $key, $GLOBALS['options'] ) ? $GLOBALS['options'][$key] : $default; }
function update_option( $key, $value, $autoload=false ) { $GLOBALS['options'][$key]=$value; return true; }

class Fake_WPDB {
	public $prefix='wp_'; public $insert_id=0; public $tables=array();
	public function __construct() {
		foreach ( array( 'wp_ikon_seo_governance_policies','wp_ikon_seo_governance_assignments','wp_ikon_seo_governance_inbox','wp_ikon_seo_governance_events','wp_ikon_seo_agency_sites' ) as $table ) { $this->tables[$table]=array(); }
	}
	public function esc_like( $value ) { return $value; }
	public function prepare( $query, ...$args ) {
		if ( count($args)===1 && is_array($args[0]) ) { $args=$args[0]; }
		foreach ( $args as &$arg ) { if ( is_string($arg) ) { $arg="'" . str_replace("'", "''", $arg) . "'"; } }
		return vsprintf( str_replace( array('%d','%s'), array('%u','%s'), $query ), $args );
	}
	public function get_var( $query ) {
		if ( preg_match( "/SHOW TABLES LIKE '([^']+)'/", $query, $m ) ) { return isset($this->tables[$m[1]]) ? $m[1] : null; }
		if ( preg_match( "/SELECT MAX\(version\) FROM ([^ ]+) WHERE policy_key='([^']+)'/", $query, $m ) ) {
			$max=0; foreach($this->tables[$m[1]]??array() as $row){ if($row['policy_key']===$m[2])$max=max($max,(int)$row['version']); } return $max;
		}
		return null;
	}
	public function insert( $table, $data, $formats=array() ) {
		$id=count($this->tables[$table]??array())+1; if(!isset($data['id']))$data['id']=$id; $this->insert_id=(int)$data['id']; $this->tables[$table][]=$data; return 1;
	}
	public function update( $table, $data, $where ) {
		$count=0; foreach($this->tables[$table] as &$row){ $match=true; foreach($where as $k=>$v){ if((string)($row[$k]??'')!==(string)$v){$match=false;break;} } if($match){$row=array_merge($row,$data);$count++;} } return $count;
	}
	public function query( $query ) {
		if ( strpos($query,'UPDATE wp_ikon_seo_governance_inbox SET status=')!==false ) {
			foreach($this->tables['wp_ikon_seo_governance_inbox'] as &$row){ if(($row['status']??'')==='accepted'){$row['status']='superseded';$row['updated_at']='2026-08-04 10:30:00';} } return 1;
		}
		return 0;
	}
	public function get_row( $query, $format=ARRAY_A ) {
		if ( preg_match( '/SELECT \* FROM ([^ ]+) WHERE id=([0-9]+)/', $query, $m ) ) { foreach($this->tables[$m[1]]??array() as $row){if((int)$row['id']===(int)$m[2])return $row;} return null; }
		if ( strpos($query,'FROM wp_ikon_seo_governance_inbox WHERE source_fingerprint=')!==false ) { return null; }
		return null;
	}
}
$GLOBALS['wpdb']=new Fake_WPDB();

class Ikon_SEO_Agency_Command_Centre { public function sites_table(){return 'wp_ikon_seo_agency_sites';} }
class Ikon_SEO_Crypto {}
class Ikon_SEO_Workspace_History { public $events=array(); public function add($event,$source,$user){$this->events[]=$event;} }
class Ikon_SEO_Logger { public $events=array(); public function log($category,$status,$message){$this->events[]=array($category,$status,$message);} }
class Ikon_SEO_Plugin { public static function settings(){ return array('draft_only'=>1,'allow_live_updates'=>0,'agency_command_brand_name'=>'Ikon SEO Agency'); } }
require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-portfolio-governance.php';

$history=new Ikon_SEO_Workspace_History(); $logger=new Ikon_SEO_Logger();
$engine=new Ikon_SEO_Portfolio_Governance(new Ikon_SEO_Agency_Command_Centre(),new Ikon_SEO_Crypto(),$history,$logger);
$failures=array();
$created=$engine->create_policy(array('name'=>'Agency Safety Standard','policy_key'=>'agency-safety','minimum_strategy_readiness'=>85,'max_safe_batch'=>2,'require_impact_study'=>true),7);
if(is_wp_error($created)||($created['status']??'')!=='draft')$failures[]='Draft policy creation failed.';
$approved=$engine->approve_policy($created['id']??0,'Approved for staging.',7);
if(is_wp_error($approved)||($approved['status']??'')!=='approved')$failures[]='Central policy approval failed.';
$ref=new ReflectionClass($engine); $method=$ref->getMethod('policy_envelope'); $method->setAccessible(true); $envelope=$method->invoke($engine,$approved);
$receipt=$engine->receive_proposal($envelope);
if(is_wp_error($receipt)||($receipt['status']??'')!=='pending_local_approval'||($receipt['activation']??'')!=='local_administrator_only')$failures[]='Proposal was not stored for local approval.';
if(Ikon_SEO_Portfolio_Governance::active_policy())$failures[]='Remote proposal activated itself before local approval.';
$accepted=$engine->accept_proposal($receipt['proposal_id']??0,'Accepted by the local staging administrator.',11);
if(is_wp_error($accepted)||($accepted['active_policy']['policy_version']??0)!==1)$failures[]='Local administrator acceptance failed.';
if(85!==Ikon_SEO_Portfolio_Governance::minimum_strategy_readiness()||2!==Ikon_SEO_Portfolio_Governance::max_safe_batch())$failures[]='Accepted policy limits were not enforced.';
if(($accepted['active_policy']['publishes_automatically']??true)!==false)$failures[]='Accepted policy does not explicitly preserve manual publishing.';
$second=$engine->accept_proposal($receipt['proposal_id']??0,'Duplicate.',11);
if(!is_wp_error($second)||$second->get_error_code()!=='ikon_seo_governance_proposal_state')$failures[]='An already-decided proposal could be accepted twice.';
if(count($history->events)<4||count($logger->events)<4)$failures[]='Governance decisions were not recorded in history and logs.';
if($failures){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);} echo "Portfolio Governance central proposal, local acceptance and policy-enforcement flow tests passed.\n";
