<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function __( $value ) { return $value; }
function wp_json_encode( $value ) { return json_encode( $value ); }
class WP_Error { public function __construct( $code = '', $message = '' ) { $this->code=$code; $this->message=$message; } }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
class Ikon_SEO_Search_Impact {}
class Ikon_SEO_Publishing_Readiness {}
class Ikon_SEO_Profile {}
class Ikon_SEO_Workspace_History {}
class Ikon_SEO_Logger {}
require_once dirname( __DIR__ ) . '/includes/class-ikon-seo-pattern-library.php';
$reflection = new ReflectionClass( 'Ikon_SEO_Pattern_Library' );
$engine = $reflection->newInstanceWithoutConstructor();
$invoke = static function( $name, array $args = array() ) use ( $reflection, $engine ) { $m=$reflection->getMethod($name); $m->setAccessible(true); return $m->invokeArgs($engine,$args); };
$failures=array();
if ( 25.0 !== $invoke( 'median', array( array( 10, 20, 30, 40 ) ) ) ) { $failures[]='Median calculation failed.'; }
$eligible=array('site_count'=>3,'study_count'=>5,'usable_study_count'=>5,'usable_site_count'=>3,'consistency_percent'=>70,'directional_signal'=>'positive_signal','confidence'=>'medium');
if ( true !== $invoke( 'eligible_for_validation', array( $eligible ) ) ) { $failures[]='Eligible cross-site pattern was not accepted.'; }
$single=$eligible; $single['usable_site_count']=1;
if ( false !== $invoke( 'eligible_for_validation', array( $single ) ) ) { $failures[]='Single-site evidence was allowed to validate a pattern.'; }
$record=array(
 'source_site_fingerprint'=>str_repeat('a',64),'source_study_key'=>'study-1','website_mode'=>'local_business','industry'=>'cleaning','market'=>'qatar','language'=>'en','page_type'=>'service','change_family'=>'content_refresh','primary_metric'=>'clicks','outcome'=>'positive_signal','confidence'=>'high','adjusted_change_percent'=>18.2,'human_decision'=>'retain','observed_at'=>'2026-08-01 00:00:00'
);
$clean=$invoke('normalize_import_record',array($record));
if ( is_wp_error($clean) || ($clean['context']['industry']??'')!=='cleaning' ) { $failures[]='Valid anonymised import was rejected.'; }
$record['target_url']='https://example.com/private-page/';
if ( ! is_wp_error( $invoke('normalize_import_record',array($record)) ) ) { $failures[]='URL-bearing portfolio evidence was accepted.'; }
$source=file_get_contents(dirname(__DIR__).'/includes/class-ikon-seo-pattern-library.php');
foreach(array('revalidation_required','No command edits, publishes','not universal SEO rules','source_site_fingerprint','forbidden_broadening') as $needle){ if(false===strpos($source,$needle))$failures[]='Pattern safety guard missing: '.$needle; }
foreach(array('wp_publish_post','wp_update_post','wp_delete_post') as $needle){ if(false!==strpos($source,$needle))$failures[]='Pattern Library contains prohibited content mutation: '.$needle; }
if($failures){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);} echo "Pattern Library cross-site threshold, privacy and no-live-change tests passed.\n";
