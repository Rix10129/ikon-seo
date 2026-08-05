<?php
$root = dirname( __DIR__ );
$failures = array();
$main = file_get_contents( $root . '/ikon-seo.php' );
$plugin = file_get_contents( $root . '/includes/class-ikon-seo-plugin.php' );
$rest = file_get_contents( $root . '/includes/class-ikon-seo-rest.php' );
$auth = file_get_contents( $root . '/includes/class-ikon-seo-auth.php' );
$admin = file_get_contents( $root . '/includes/class-ikon-seo-admin.php' );
$health = file_get_contents( $root . '/includes/class-ikon-seo-production-health.php' );
$engine = file_get_contents( $root . '/includes/class-ikon-seo-deployment-control.php' );
if ( false === strpos( $main, 'Version: 2.0.1' ) || false === strpos( $main, "define( 'IKON_SEO_VERSION', '2.0.1' )" ) ) { $failures[]='Plugin version is not 2.0.1.'; }
if ( false === strpos( $main, 'class-ikon-seo-deployment-control.php' ) ) { $failures[]='Deployment Control class is not loaded.'; }
if ( false === strpos( $plugin, "const DB_VERSION = '40.0'" ) ) { $failures[]='Database component version is not 38.0.'; }
foreach ( array('ikon_seo_license_entitlements','ikon_seo_release_catalog','ikon_seo_deployment_plans','ikon_seo_deployment_events','Ikon_SEO_Deployment_Control::CRON_HOOK','ikon_seo_installation_id') as $needle ) {
    if ( false === strpos( $plugin, $needle ) ) { $failures[]='Deployment database, cron or identity integration missing: '.$needle; }
}
foreach ( array('/deployment-control','deployment_control_report','deployment_control_sync','DeploymentControlSync','can_deployment_control') as $needle ) {
    if ( false === strpos( $rest, $needle ) && false === strpos( $auth, $needle ) ) { $failures[]='Deployment REST integration missing: '.$needle; }
}
foreach ( array("'deployment-control'",'render_deployment_control','ikon_seo_deployment_control_action') as $needle ) {
    if ( false === strpos( $admin, $needle ) ) { $failures[]='Deployment admin integration missing: '.$needle; }
}
foreach ( array('ikon_seo_license_entitlements','ikon_seo_release_catalog','ikon_seo_deployment_plans','ikon_seo_deployment_events','Ikon_SEO_Deployment_Control::CRON_HOOK') as $needle ) {
    if ( false === strpos( $health, $needle ) ) { $failures[]='Production Health does not cover deployment resource: '.$needle; }
}
foreach ( array('manual_wordpress_update_required','automatic_plugin_updates','license_expiry_disables_public_site','separate administrator') as $needle ) {
    if ( false === stripos( $engine, $needle ) ) { $failures[]='Deployment safeguard missing: '.$needle; }
}
$openapi=json_decode(file_get_contents($root.'/openapi/ikon-seo-openapi.json'),true);
if(!is_array($openapi)){$failures[]='Static OpenAPI is invalid.';}else{
    $ops=0;$ids=array();foreach(($openapi['paths']??array()) as $path){foreach($path as $method=>$op){if(in_array($method,array('get','post','put','patch','delete'),true)){$ops++;$ids[]=$op['operationId']??'';}}}
    if(30!==$ops||count($ids)!==count(array_unique($ids)))$failures[]='Focused OpenAPI must contain exactly 30 unique operations.';
    if(empty($openapi['paths']['/deployment-control']['post'])||empty($openapi['components']['schemas']['DeploymentControlSync']))$failures[]='Deployment Control focused OpenAPI contract is missing.';
    if(!empty($openapi['paths']['/inventory']))$failures[]='Inventory remains in the focused schema after replacement.';
    if('2.0.1'!==($openapi['info']['version']??''))$failures[]='Static OpenAPI version is not 2.0.1.';
}
foreach(array('docs/DEPLOYMENT-CONTROL-MANAGED-UPDATES.md','docs/UPGRADE-v1.19.md','release/license-public-key.pem') as $relative){if(!file_exists($root.'/'.$relative))$failures[]='Release file missing: '.$relative;}
if($failures){fwrite(STDERR,implode("\n",$failures)."\n");exit(1);}echo "Ikon SEO v2.0.1 Deployment Control integration and focused-schema tests passed.\n";
