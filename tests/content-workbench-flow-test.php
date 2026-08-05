<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'MINUTE_IN_SECONDS', 60 );

function absint( $v ) { return abs( (int) $v ); }
function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', str_replace( ' ', '_', (string) $v ) ) ); }
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_textarea_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_title( $v ) { return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $v ) ), '-' ); }
function esc_url_raw( $v ) { return filter_var( (string) $v, FILTER_VALIDATE_URL ) ? (string) $v : ''; }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function wp_kses_post( $v ) { return (string) $v; }
function wp_strip_all_tags( $v ) { return strip_tags( (string) $v ); }
function remove_accents( $v ) { return (string) $v; }
function __( $v ) { return $v; }
function current_time( $type, $gmt = false ) { return '2026-08-04 07:30:00'; }
function url_to_postid( $url ) { return 0; }
function get_permalink( $post ) { $id = is_object( $post ) ? $post->ID : absint( $post ); return 'https://example.com/?p=' . $id; }
function get_edit_post_link( $id, $context = '' ) { return 'https://example.com/wp-admin/post.php?post=' . absint( $id ); }
function get_preview_post_link( $id ) { return 'https://example.com/?p=' . absint( $id ) . '&preview=true'; }
function wp_slash( $v ) { return $v; }

class WP_Error {
	private $code; private $message; private $data;
	public function __construct( $code, $message, $data = null ) { $this->code=$code; $this->message=$message; $this->data=$data; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
	public function get_error_data() { return $this->data; }
}
function is_wp_error( $v ) { return $v instanceof WP_Error; }

$GLOBALS['transients'] = array();
function get_transient( $k ) { return $GLOBALS['transients'][$k] ?? false; }
function set_transient( $k, $v, $ttl ) { $GLOBALS['transients'][$k]=$v; return true; }
function delete_transient( $k ) { unset( $GLOBALS['transients'][$k] ); return true; }

$GLOBALS['posts'] = array();
$GLOBALS['post_meta'] = array();
function get_post( $id ) { return $GLOBALS['posts'][absint($id)] ?? null; }
function wp_insert_post( $data, $wp_error = false ) {
	$id = count( $GLOBALS['posts'] ) + 100;
	$post = (object) array_merge( array( 'ID'=>$id, 'post_status'=>'draft', 'post_type'=>'page', 'post_title'=>'', 'post_content'=>'' ), $data );
	$GLOBALS['posts'][$id] = $post;
	return $id;
}
function update_post_meta( $id, $key, $value ) { $GLOBALS['post_meta'][absint($id)][$key]=$value; return true; }
function get_post_meta( $id, $key, $single = false ) { return $GLOBALS['post_meta'][absint($id)][$key] ?? ''; }

class FakeWpdb {
	public $prefix='wp_'; public $insert_id=0; public $rows=array();
	public function esc_like( $v ) { return $v; }
	public function prepare( $query, ...$args ) {
		foreach ( $args as $arg ) { $query = preg_replace( '/%[ds]/', is_numeric($arg) ? (string)(int)$arg : "'".addslashes($arg)."'", $query, 1 ); }
		return $query;
	}
	public function get_var( $query ) { return 'wp_ikon_seo_content_briefs'; }
	public function get_col( $query, $col=0 ) { return array('opportunity_id','publisher_item_id','brief_version','evidence_hash','approved_by','draft_post_id'); }
	public function get_row( $query, $output = ARRAY_A ) {
		if ( preg_match('/opportunity_id=(\d+)/',$query,$m) ) {
			$matches=array_values(array_filter($this->rows,fn($r)=>absint($r['opportunity_id']??0)===absint($m[1])));
			return $matches ? end($matches) : null;
		}
		if ( preg_match('/id=(\d+)/',$query,$m) ) { return $this->rows[absint($m[1])] ?? null; }
		return null;
	}
	public function get_results( $query, $output = ARRAY_A ) {
		if ( false !== strpos($query,'GROUP BY status') ) {
			$counts=array(); foreach($this->rows as $r){ if(absint($r['opportunity_id']??0)>0){$st=$r['status'];$counts[$st]=($counts[$st]??0)+1;}}
			$out=array(); foreach($counts as $st=>$n){$out[]=array('status'=>$st,'total'=>$n);} return $out;
		}
		return array_values($this->rows);
	}
	public function insert( $table, $row ) { $id=count($this->rows)+1; $row['id']=$id; $this->rows[$id]=$row; $this->insert_id=$id; return 1; }
	public function update( $table, $data, $where ) { $id=absint($where['id']??0); if(!$id||!isset($this->rows[$id])) return false; $this->rows[$id]=array_merge($this->rows[$id],$data); return 1; }
}
$GLOBALS['wpdb'] = new FakeWpdb();

class Ikon_SEO_Opportunity_Engine {
	public $items=array();
	public function opportunity($id){ return $this->items[absint($id)]??array(); }
	public function report($args=array()){ return array('opportunities'=>array_values(array_filter($this->items,fn($i)=>($i['status']??'')==='planned'))); }
}
class Ikon_SEO_Publisher_Intelligence {
	const CACHE_KEY='publisher_cache'; const POST_META_REVIEW='_publisher_review'; public $saved=array();
	public function save_item($data,$user=0){ $data['id']=absint($data['id']??7)?:7; $this->saved[]=$data; return $data; }
	public function evaluate_post($post_id,$item_id,$strict,$user){ $review=array('gate_status'=>'passed'); update_post_meta($post_id,self::POST_META_REVIEW,$review); return $review; }
}
class Ikon_SEO_Competitor_Content_Intelligence { const CACHE_KEY='competitor_cache'; public function briefs_table(){return 'wp_ikon_seo_content_briefs';} }
class Ikon_SEO_Strategy { public function get(){return array('target_audience'=>'Property managers','value_proposition'=>'Confirmed cleaning support','primary_conversions'=>'Quote forms','main_offerings'=>'Office cleaning','monetization_model'=>'service_revenue');} }
class Ikon_SEO_Profile { public function get(){return array('target_audience'=>'Property managers');} }
class Ikon_SEO_Inventory { public function scan($refresh=false){return array('items'=>array());} }
class Ikon_SEO_Renderer { public function render($payload){return array('post_content'=>'<p>Rendered draft</p>','elementor_data'=>array());} }
class Ikon_SEO_Workspace_History { public $items=array(); public function add($data,$source,$user){$this->items[]=$data;} }
class Ikon_SEO_Logger { public $items=array(); public function log(...$args){$this->items[]=$args;} }
class Ikon_SEO_Plugin { public static function settings(){return array('builder_preference'=>'gutenberg','author_id'=>1);} }

require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-content-workbench.php';

$engine=new Ikon_SEO_Opportunity_Engine();
$engine->items[10]=array(
	'id'=>10,'status'=>'planned','type'=>'keyword_gap','category'=>'content_gap','primary_source'=>'search_console',
	'title'=>'Office cleaning Doha','summary'=>'Search evidence supports a focused service page.','target_url'=>'','post_id'=>0,
	'keyword'=>'office cleaning doha','intent'=>'commercial','priority'=>82,'confidence'=>'high','observed_at'=>'2026-08-04',
	'evidence'=>array('topics'=>array('office hygiene','commercial cleaning')),'actions'=>array('Create an evidence-led service brief.'),
);
$publisher=new Ikon_SEO_Publisher_Intelligence();
$workbench=new Ikon_SEO_Content_Workbench($engine,$publisher,new Ikon_SEO_Competitor_Content_Intelligence(),new Ikon_SEO_Strategy(),new Ikon_SEO_Profile(),new Ikon_SEO_Inventory(),new Ikon_SEO_Renderer(),new Ikon_SEO_Workspace_History(),new Ikon_SEO_Logger());
$failures=array();

$brief=$workbench->create_brief(10,5);
if(is_wp_error($brief)||'proposed'!==($brief['status']??'')){$failures[]='Planned opportunity did not create a proposed brief.';}
$wrong=$workbench->approve_brief($brief['id'],str_repeat('0',64),5);
if(!is_wp_error($wrong)||'ikon_seo_content_stale_request'!==$wrong->get_error_code()){$failures[]='Incorrect evidence token was not rejected.';}
$approved=$workbench->approve_brief($brief['id'],$brief['evidence_hash'],5);
if(is_wp_error($approved)||'approved'!==($approved['status']??'')){$failures[]='Current brief was not approved.';}
$draft=$workbench->create_scaffold($brief['id'],$brief['evidence_hash'],5);
if(is_wp_error($draft)||empty($draft['post_id'])){$failures[]='Approved brief did not create a separate draft.';}
else { $post=get_post($draft['post_id']); if('draft'!==$post->post_status){$failures[]='Controlled post was not saved as draft.';} }
$again=$workbench->create_scaffold($brief['id'],$brief['evidence_hash'],5);
if(!is_wp_error($again)){$failures[]='A second controlled draft was allowed from the same brief.';}
$engine->items[10]['evidence']['topics'][]='changed evidence';
$stale=$workbench->evaluate_draft($brief['id'],5);
if(!is_wp_error($stale)||'ikon_seo_content_evidence_changed'!==$stale->get_error_code()){$failures[]='Changed source evidence did not block draft evaluation.';}
if('outdated'!==($GLOBALS['wpdb']->rows[$brief['id']]['status']??'')){$failures[]='Stale brief was not marked outdated.';}

if($failures){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);} echo "Content Workbench approval and stale-draft flow tests passed.\n";
