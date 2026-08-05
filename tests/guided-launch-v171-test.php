<?php
$GLOBALS['ikon_options'] = array();
function get_option( $key, $default = array() ) { return $GLOBALS['ikon_options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = false ) { $GLOBALS['ikon_options'][ $key ] = $value; return true; }
function sanitize_text_field( $v ) { return trim( (string) $v ); }
function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $v ) ); }
function absint( $v ) { return abs( (int) $v ); }
function current_time( $type, $gmt = false ) { return '2026-08-04 07:00:00'; }
function __( $v, $domain = null ) { return $v; }
function admin_url( $v = '' ) { return 'https://example.test/wp-admin/' . ltrim( $v, '/' ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
class WP_Error { private $code; private $message; public function __construct( $c, $m ) { $this->code=$c; $this->message=$m; } public function get_error_code(){return $this->code;} public function get_error_message(){return $this->message;} }
class Ikon_SEO_Auto_Discovery { public $report_data; public function report(){return $this->report_data;} }
class Ikon_SEO_Discovery_Review { public $report_data; public function report(){return $this->report_data;} }
class Ikon_SEO_Strategy { public $data; public function get(){return $this->data;} }
class Ikon_SEO_Automation {
	public $created=false;
	public function summary($limit=25){return array('workflows'=>$this->created?array(array('id'=>1)):array(),'counts'=>array('completed'=>0,'ready'=>2,'pending_approval'=>0,'failed'=>0),'recommended_template'=>'local_growth','tasks'=>array());}
	public function recommended_template(){return 'local_growth';}
	public function create_workflow($template,$args=array()){$this->created=true; return array('id'=>1);}
	public function run_safe_tasks($limit,$force){return array('processed'=>$limit);}
}
class Ikon_SEO_Closed_Loop {
	public $created=false;
	public function report($limit=25){return array('status'=>array('last_plan_refresh'=>$this->created?'2026-08-04 07:00:00':''),'summary'=>array('recommendations'=>$this->created?4:0),'recommendations'=>array());}
	public function refresh_plan($a,$b,$c,$user){$this->created=true; return array('stored'=>4);}
}
class Ikon_SEO_Workspace_History { public function add($event,$source='',$user=0){} }
class Ikon_SEO_Logger {}
define('ABSPATH',__DIR__);
require_once dirname(__DIR__).'/includes/class-ikon-seo-guided-launch.php';
function ok($condition,$message){if(!$condition){fwrite(STDERR,"FAIL: $message\n");exit(1);}echo "PASS: $message\n";}
$auto=new Ikon_SEO_Auto_Discovery();
$auto->report_data=array('generated_at'=>'scan-1','summary'=>array('pages_reviewed'=>25),'conflicts'=>array());
$review=new Ikon_SEO_Discovery_Review();
$review->report_data=array('ready'=>false,'counts'=>array('needs_confirmation'=>1,'outdated'=>0,'confirmed'=>0,'edited'=>0),'conflicts'=>array(),'unresolved_conflicts'=>0);
$strategy=new Ikon_SEO_Strategy();
$strategy->data=array('configured'=>true,'mode'=>'local_business','mode_label'=>'Local Business','readiness'=>array('score'=>80,'level'=>'ready','gaps'=>array()));
$automation=new Ikon_SEO_Automation();$closed=new Ikon_SEO_Closed_Loop();
$launch=new Ikon_SEO_Guided_Launch($auto,$review,$strategy,$automation,$closed,new Ikon_SEO_Workspace_History(),new Ikon_SEO_Logger());
$result=$launch->activate(array(),9);
ok(is_wp_error($result)&&'ikon_seo_guided_launch_fact_review'===$result->get_error_code(),'Guided Launch blocks unresolved fact review');
$review->report_data=array('ready'=>true,'counts'=>array('needs_confirmation'=>0,'outdated'=>0,'confirmed'=>4,'edited'=>1),'conflicts'=>array(),'unresolved_conflicts'=>0);
$result=$launch->activate(array('task_batch'=>3),9);
ok(!is_wp_error($result)&&true===$result['workflow']['created'],'Guided Launch creates workflow after review is ready');
ok(3===$result['last_run']['safe_tasks_processed'],'Guided Launch runs bounded safe task batch');
ok(4===$result['last_run']['plan_items_generated'],'Guided Launch generates Operating Plan');
$stage=array_values(array_filter($result['stages'],function($s){return 'strategy'===$s['key'];}))[0];
ok(true===$stage['complete'],'Business confirmation stage includes ready fact review');
echo "All Guided Launch v1.7.1 tests passed.\n";
