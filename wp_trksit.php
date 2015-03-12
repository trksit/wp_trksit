<?php
/*
Plugin Name: trks.it for WordPress
Plugin URI: https://get.trks.it?utm_source=WordPress%20Admin%20Link
Description: Ever wonder how many people click links that lead to 3rd party sites from your social media platforms? trks.it is a WordPress plugin for tracking social media engagement.
Author: trks.it
Version: 1.5.1
Author URI: http://get.trks.it?utm_source=WordPress%20Admin%20Link
 */

// Installation Script
register_activation_hook( __FILE__, 'trksit_Install' );
function trksit_Install(){

	global $wpdb;

	$charset_collate = '';

	if ( ! empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	if ( ! empty($wpdb->collate) )
		$charset_collate .= " COLLATE $wpdb->collate";

	$trksit = new trksit();
	$active = $trksit->wp_trksit_user_is_active();
	if(is_wp_error($active)){
		echo "<code>trks.it API unavailable.  Plugin can not be activated.</code>";
		exit;
	}

	$table_1_name = $wpdb->prefix . "trksit_urls";
	$table_1_sql = "CREATE TABLE $table_1_name (
		url_id INT(10) unsigned NOT NULL AUTO_INCREMENT,
		user_id INT(4) UNSIGNED NOT NULL,
		date_created DATE DEFAULT '0000-00-00' NOT NULL,
		destination_url VARCHAR(255) DEFAULT '' NOT NULL,
		trksit_url VARCHAR(255) DEFAULT '' NOT NULL,
		source VARCHAR(255) DEFAULT '' NOT NULL,
		medium VARCHAR(255) DEFAULT '' NOT NULL,
		campaign VARCHAR(255) DEFAULT '' NOT NULL,
		meta_title VARCHAR(255) DEFAULT '' NOT NULL,
		meta_description VARCHAR(255) DEFAULT '' NOT NULL,
		meta_image VARCHAR(255) DEFAULT '' NOT NULL,
		og_data TEXT NOT NULL,
		PRIMARY KEY  url_id (url_id))
		ENGINE = InnoDB
		$charset_collate;";

$table_2_name = $wpdb->prefix . "trksit_hits";
$table_2_sql = "CREATE TABLE $table_2_name (
	hit_count INT(10) unsigned NOT NULL,
	url_id INT(10) unsigned NOT NULL,
	hit_date DATE DEFAULT '0000-00-00' NOT NULL,
	PRIMARY KEY  (url_id, hit_date))
	ENGINE = InnoDB
	$charset_collate;";

$table_3_name = $wpdb->prefix . "trksit_scripts";
$table_3_sql = "CREATE TABLE $table_3_name (
	script_id INT(10) unsigned NOT NULL AUTO_INCREMENT,
	date_created DATE DEFAULT '0000-00-00' NOT NULL,
	label VARCHAR(255) DEFAULT '' NOT NULL,
	script TEXT DEFAULT '' NOT NULL,
	platform VARCHAR(25) DEFAULT 'Google' NOT NULL,
	script_error tinyint(1) NOT NULL DEFAULT '0',
	PRIMARY KEY  script_id (script_id))
	ENGINE = InnoDB
	$charset_collate;";

$table_4_name = $wpdb->prefix . "trksit_scripts_to_urls";
$table_4_sql = "CREATE TABLE $table_4_name (
	assignment_id INT(10) unsigned NOT NULL AUTO_INCREMENT,
	script_id INT(10) unsigned NOT NULL,
	url_id INT NOT NULL,
	PRIMARY KEY  (assignment_id, script_id, url_id))
	ENGINE = InnoDB
	$charset_collate;";

update_option('trksit_jquery', 0);
update_option('trksit_redirect_delay', 500);
update_option('trksit_token', '');
update_option('trksit_token_expires', 1);

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

dbDelta( $table_1_sql ); // This is a WordPress function, cool huh?
dbDelta( $table_2_sql );
dbDelta( $table_3_sql );
dbDelta( $table_4_sql );

trksit_enforce_defaults();
}
add_action('admin_init', 'trksit_enforce_defaults');
function trksit_enforce_defaults(){
	$sources = serialize(array('Social - Facebook','Social - Twitter','Social - Youtube','Social - LinkedIn','Social - Pinterest','Social - Online Community','Social - Blogger Outreach','Content Mktg - Blog','Content Mktg - Resources','Content Mktg - Article Library','Content Mktg - Landing Page','Content Mktg - Website Page','Content Mktg - Slideshare','Content Mktg - Prezi','Email - Promotion','Email - Newsletter','Paid - Facebook ','Paid - Twitter ','Paid - Youtube ','Paid - LinkedIn ','Paid - Other','Paid - Online to Offline ','Paid - Sponsorship ','Paid - Out of Home ','Paid - TV ','Paid - Radio'));
	$domains = serialize(array(get_option('siteurl')));
	$medium = serialize(array('Blog Post','Infographic','Video','Guide','Ebook','Webinar','White Paper','Presentation','Research Study','Paid Search','Display','Banner'));
	if(!get_option('trksit_sources')){
		update_option('trksit_sources', $sources);
	} else {
		$sources_blank = maybe_unserialize(get_option('trksit_sources'));
		if($sources_blank[0] == "" || !is_array($sources_blank)){
			update_option('trksit_sources', $domains);
		}
	}
	if(!get_option('trksit_domains')){
		update_option('trksit_domains', $domains);
	} else {
		$domains_blank = maybe_unserialize(get_option('trksit_domains'));
		if($domains_blank[0] == "" || !is_array($domains_blank)){
			update_option('trksit_domains', $domains);
		}
	}

	if(!get_option('trksit_medium')){
		update_option('trksit_medium', $medium);
	} else {
		$mediums_blank = maybe_unserialize(get_option('trksit_medium'));
		if($mediums_blank[0] == "" || !is_array($mediums_blank)){
			update_option('trksit_medium', $domains);
		}

	}
}

/*
 *register_uninstall_hook( __FILE__, 'trksit_uninstall');
 *function trksit_uninstall(){
 *    global $wpdb;
 *    $trksit = new trksit();
 *    $trksit->wp_trksit_resetToken();
 *    $success = $trksit->wp_trksit_api_uninstall(get_option('trksit_private_api_key'));
 *    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'trksit_hits');
 *    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'trksit_scripts');
 *    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'trksit_scripts_to_urls');
 *    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'trksit_urls');
 *}
 */

//Determine production or development
$wp_host = explode( '.', $_SERVER['HTTP_HOST'] );
$wp_host = array_pop( $wp_host );
define( 'WP_TKSIT_PRODUCTION', ( $wp_host == 'local' || $wp_host == 'dev' ) ? false : true );

$parsed = array_shift( ( explode( '.', $_SERVER['HTTP_HOST'] ) ) );
$beta = substr( $parsed, 0, 4 );

// Extra layer of URLs for beta testing
if ( WP_TKSIT_PRODUCTION ) {

	if ( $_SERVER['HTTP_HOST'] == 'beta.trks.it' || $beta == 'beta' ) {

		define( 'WP_TRKSIT_MANAGE_URL', 'http://manage-beta.trks.it' );
		define( 'WP_TRKSIT_API_URL', 'http://api-beta.trks.it' );
		define( 'WP_TRKSIT_SHORT_URL', 'http://shortener-beta.trks.it/' );

	} else {

		define( 'WP_TRKSIT_MANAGE_URL', 'http://manage.trks.it' );
		define( 'WP_TRKSIT_API_URL', 'https://api.trks.it' );
		define( 'WP_TRKSIT_SHORT_URL', 'http://trks.it/' );

	}

} else {

	define( 'WP_TRKSIT_MANAGE_URL', 'http://manage.trksit.local' );
	define( 'WP_TRKSIT_API_URL', 'http://api.trksit.local' );
	define( 'WP_TRKSIT_SHORT_URL', 'http://trksit.local/' );

}

include( plugin_dir_path( __FILE__ ) . 'inc/trksit.class.php' );
include( plugin_dir_path( __FILE__ ) . 'inc/trksit.ga_parse.php' );

add_action( 'init', array( new trksit, '__construct' ) );

//load the needed scripts
add_action( 'admin_enqueue_scripts', 'trksit_load_scripts' );
function trksit_load_scripts() {

	// All trksit admin pages
	if ( isset( $_GET['page'] )
		&& ( $_GET['page'] == 'trksit-dashboard' || $_GET['page'] == 'trksit-settings' || $_GET['page'] == 'trksit-generate' ) ) {

		wp_register_style( 'trksit-bootstrap', plugin_dir_url(__FILE__) . 'css/bootstrap.min.css');
		wp_register_style( 'trksit-styles', plugin_dir_url(__FILE__) . 'css/main.css');

		wp_register_script( 'trksit-bootstrap-js', plugin_dir_url(__FILE__).'js/lib/bootstrap.min.js', array('jquery') );
		wp_register_script( 'trksit-zclip-js', plugin_dir_url(__FILE__) . 'js/lib/jquery.zclip.js', array( 'jquery' ), '1.1.1', true );
		wp_register_script( 'trksit-validation-js', plugin_dir_url(__FILE__) . 'js/lib/jquery.validate.min.js', array( 'jquery' ), '1.11.1' );
		wp_register_script( 'trksit-main-js', plugin_dir_url(__FILE__) . '/js/main.js', array( 'jquery' ), '1.2.1' );
		wp_register_script( 'trksit-jquery-image-picker', plugin_dir_url(__FILE__) . 'js/lib/image-picker.min.js', array( 'jquery' ), '0.1.3', true );

		wp_enqueue_style( 'trksit-bootstrap' );
		wp_enqueue_style( 'trksit-styles' );
		wp_enqueue_script( 'trksit-bootstrap-js' );
		wp_enqueue_script( 'trksit-zclip-js' );
		wp_enqueue_script( 'trksit-validation-js' );
		wp_enqueue_script( 'trksit-jquery-image-picker' );
		wp_enqueue_script( 'trksit-main-js' );

	}

	// Dashboard
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'trksit-dashboard' ) {

		wp_enqueue_script( 'jquery-ui-datepicker' );

		wp_register_script( 'raphael-js', plugin_dir_url(__FILE__).'js/lib/raphael-min.js', '', '2.1.2' );
		wp_enqueue_script( 'raphael-js' );

		wp_enqueue_script( 'morris-js', plugin_dir_url(__FILE__).'js/lib/morris.min.js', array( 'raphael-js' ), '0.5.1' );
		wp_enqueue_script( 'datatables', plugin_dir_url(__FILE__).'js/lib/jquery.dataTables.1.10.5.min.js', array( 'jquery' ), '1.10.5', true );

		wp_enqueue_style( 'jquery-ui-datepicker-css', plugin_dir_url(__FILE__).'css/jquery-ui-1.10.4.custom.min.css', null, null );

	}

	// Generate Page
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'trksit-generate' ) {

		wp_register_script( 'trksit-generate-js', plugin_dir_url(__FILE__).'js/generate.js', array( 'jquery' ), '1.2.1', true );
		wp_enqueue_script( 'trksit-generate-js' );

	}

}

/*
 * Saves options from settings then redirects to refresh the admin menu
 * If the plugin is active, all pages show in admin menu
 * If not, only settings.  See trksit_add_pages() function.
 */
add_action( 'plugins_loaded', 'trksit_update_settings_redirect' );
function trksit_update_settings_redirect(){

	if ( ( isset( $_POST['trksit_page'] ) && $_POST['trksit_page'] == 'settings' )
		&& ( !empty( $_POST ) && check_admin_referer( 'trksit_save_settings', 'trksit_general_settings' ) ) ) {

		$trksit_analytics_id = $_POST['trksit_analytics_id'];
		$trksit_public_api_key = $_POST['trksit_public_api_key'];
		$trksit_private_api_key = $_POST['trksit_private_api_key'];
		$trksit_jquery = $_POST['trksit_jquery'];
		$trksit_redirect_delay = $_POST['trksit_redirect_delay'];

		if ( $trksit_public_api_key != '' && $trksit_private_api_key != '' ) {

			update_option( 'trksit_analytics_id', $trksit_analytics_id );
			update_option( 'trksit_public_api_key', $trksit_public_api_key );
			update_option( 'trksit_private_api_key', $trksit_private_api_key );
			update_option( 'trksit_jquery', $trksit_jquery );
			update_option( 'trksit_redirect_delay', $trksit_redirect_delay );

		}

		$trksit = new trksit();
		$reset_token = $trksit->wp_trksit_resetToken();

		//Refresh so the admin menu has the correct pages
		wp_redirect( '/wp-admin/admin.php?page=trksit-settings' );

	} else {

		//This is to refresh the settings page so the menu is correct
		//Also redirects if a user is on generate or dashboard and becomes inactive
		//A refresh will force user to the trksit-settings page
		$trksit = new trksit();
		$page = array();

		if ( isset( $_GET['page'] ) ) {
			$page = explode( '-', $_GET['page'] );
		}

		if ( count( $page ) > 0 && $page[0] == 'trksit' && !$trksit->wp_trksit_user_is_active() && !isset( $_GET['trksit_active'] ) ) {
			wp_redirect( '/wp-admin/admin.php?page=trksit-settings&trksit_active=false' );
			exit;
		}

	}

}

/*
 * Admin notices - show wordpress info box if plugin can not activate
 * Or if the api is offline
 */
add_action( 'admin_notices', 'trksit_admin_notices' );
function trksit_admin_notices(){

	global $pagenow;

	if ( $pagenow == 'plugins.php' || $pagenow == 'admin.php' ) {

		$pubapi = get_option( 'trksit_public_api_key' );
		$privapi = get_option( 'trksit_private_api_key' );

		if ( !$pubapi || !$privapi || $privapi == '' || $pubapi == '' ){

			if ( $pagenow == 'plugins.php' || ( $pagenow == 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] != 'trksit-settings' ) ) {

				echo '<div id="message" class="updated">
					  <p>Please visit the <a href="/wp-admin/admin.php?page=trksit-settings">trks.it settings page</a> to enter your API keys.</p>
					  </div>';

			}

		}

		$trksit = new trksit();
		$active = $trksit->wp_trksit_user_is_active();

		if ( is_wp_error( $active ) ) {
			echo '<div id="message" class="error"><p>trks.it API offline.</p></div>';
		}

	}

}

/*
 * Add pages to wordpress sidebar
 * Only settings shows if no API keys match
 */
add_action( 'admin_menu', 'trksit_add_pages' );
function trksit_add_pages() {

	$svg_menu_icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMjU2IDI1NiIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgMjU2IDI1NiIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PGc+PHBhdGggZmlsbD0iIzk5OTk5OSIgZD0iTTE5NC43LDI3LjNjMTMsMCwyMy42LDEwLjYsMjMuNiwyMy42cy0xMC42LDIzLjYtMjMuNiwyMy42Yy0xMywwLTIzLjctMTAuNi0yMy43LTIzLjZTMTgxLjcsMjcuMywxOTQuNywyNy4zIE0xOTQuNywxNy44Yy0xOC40LDAtMzMuMiwxNC44LTMzLjIsMzMuMWMwLDE4LjQsMTQuOCwzMy4xLDMzLjIsMzMuMWMxOC4zLDAsMzMuMS0xNC44LDMzLjEtMzMuMUMyMjcuOSwzMi42LDIxMy4xLDE3LjgsMTk0LjcsMTcuOEwxOTQuNywxNy44eiIvPjxnPjxwYXRoIGZpbGw9IiM5OTk5OTkiIGQ9Ik0xMDAuMSwyNDIuMWMtMSwyLjMtMy4xLDMuOC01LjYsMy45Yy0wLjUsMC0xLDAtMS41LTAuMWMtMy0wLjUtNS4yLTIuOS01LjQtNmwtMy41LTU4LjVsLTU4LjUsMy41Yy0zLjEsMC4yLTUuNy0xLjctNi42LTQuNmMtMC45LTIuOSwwLjMtNiwyLjktNy41TDE0OCw5OS45YzAuOS0wLjYsMS45LTAuOCwyLjktMC45YzEuNy0wLjEsMy40LDAuNCw0LjcsMS43YzIuMiwxLjgsMi44LDQuOCwxLjcsNy40TDEwMC4xLDI0Mi4xeiIvPjwvZz48Zz48cGF0aCBmaWxsPSIjOTk5OTk5IiBkPSJNMTk0LjcsOTUuMmMtMi42LDAtNC43LTIuMS00LjctNC43VjcwLjdjMC0yLjYsMi4xLTQuNyw0LjctNC43YzIuNiwwLDQuNywyLjEsNC43LDQuN3YxOS44QzE5OS41LDkzLjEsMTk3LjMsOTUuMiwxOTQuNyw5NS4yeiIvPjwvZz48Zz48cGF0aCBmaWxsPSIjOTk5OTk5IiBkPSJNMTk0LjcsMzkuM2MtMi42LDAtNC43LTIuMS00LjctNC43VjE0LjdjMC0yLjYsMi4xLTQuNyw0LjctNC43YzIuNiwwLDQuNywyLjEsNC43LDQuN3YxOS44QzE5OS41LDM3LjIsMTk3LjMsMzkuMywxOTQuNywzOS4zeiIvPjwvZz48Zz48cGF0aCBmaWxsPSIjOTk5OTk5IiBkPSJNMjMyLjYsNTcuNGgtMTkuOGMtMi42LDAtNC43LTIuMS00LjctNC43YzAtMi42LDIuMS00LjcsNC43LTQuN2gxOS44YzIuNiwwLDQuNywyLjEsNC43LDQuN0MyMzcuMyw1NS4yLDIzNS4yLDU3LjQsMjMyLjYsNTcuNHoiLz48L2c+PGc+PHBhdGggZmlsbD0iIzk5OTk5OSIgZD0iTTE3Ni42LDU3LjRoLTE5LjhjLTIuNiwwLTQuNy0yLjEtNC43LTQuN2MwLTIuNiwyLjEtNC43LDQuNy00LjdoMTkuOGMyLjYsMCw0LjcsMi4xLDQuNyw0LjdDMTgxLjQsNTUuMiwxNzkuMyw1Ny40LDE3Ni42LDU3LjR6Ii8+PC9nPjwvZz48L3N2Zz4=';

	$active = false;

	if( !get_option( 'trksit_token' ) || time() > get_option( 'trksit_token_expires' ) ){

		$trksit = new trksit();
		$trksit->wp_trksit_resetToken();

	}

	if( !get_transient( 'trksit_active_user' ) ){

		$trksit = new trksit();
		$active = $trksit->wp_trksit_user_is_active();

		if( is_wp_error( $active ) ){
			//echo $active->get_error_message();
		}

		if( $trksit->wp_trksit_user_is_active() ){
			$active = true;
		}

	} else {

		if( 'active' == get_transient( 'trksit_active_user' ) ){
			$active = true;
		}

	}

	if( !$active ){

		add_menu_page(
			__( 'Plugin Settings &lsaquo; trks.it', 'trksit_menu' ),
			__( 'trks.it Settings','trksit_menu' ),
			'edit_private_pages',
			'trksit-settings',
			'trksit_settings',
			$svg_menu_icon
		);

	} else {

		add_menu_page(
			__( 'Dashboard &lsaquo; trks.it', 'trksit_menu' ),
			__( 'trks.it', 'trksit_menu' ),
			'edit_private_pages',
			'trksit-dashboard',
			'trksit_dashboard',
			$svg_menu_icon
		);

		add_submenu_page(
			'trksit-dashboard',
			__( 'Dashboard', 'trksit_menu' ),
			__( 'Dashboard', 'trksit_menu' ),
			'edit_private_pages',
			'trksit-dashboard',
			'trksit_dashboard'
		);

		add_submenu_page(
			'trksit-dashboard',
			__( 'Generate URL &lsaquo; trks.it', 'trksit_menu' ),
			__( 'Generate URL', 'trksit_menu' ),
			'edit_private_pages',
			'trksit-generate',
			'trksit_generate'
		);

		add_submenu_page(
			'trksit-dashboard',
			__( 'Plugin Settings &lsaquo; trks.it', 'trksit_menu'),
			__( 'Settings', 'trksit_menu'),
			'edit_private_pages',
			'trksit-settings',
			'trksit_settings'
		);

	}

}

/** Dashboard Page Content */
function trksit_dashboard() {

	if ( !current_user_can( 'edit_private_pages' ) || !current_user_can( 'edit_private_posts' ) ) {

		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	} else {

		global $wpdb;
		include( 'wp_trksit_dashboard.php' );

	}

}

/** Generate URL Page Content */
function trksit_generate() {

	if ( !current_user_can( 'manage_categories' ) || !current_user_can( 'edit_private_posts' ) ) {

		wp_die( __('You do not have sufficient permissions to access this page.') );

	} else {

		global $wpdb;
		include( 'wp_trksit_generate_url.php' );

	}

}

/** Settings Page Content */
function trksit_settings() {

	if ( !current_user_can( 'manage_categories' ) || !current_user_can( 'edit_private_posts' ) ) {

		wp_die( __('You do not have sufficient permissions to access this page.') );

	} else {

		global $wpdb;
		include( 'wp_trksit_settings.php' );

	}

}

/** force https when appropriate */
function trksit_current_page() {

	$pageURL = 'http';

	if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) { $pageURL .= 's'; }

	$pageURL .= '://';

	if ($_SERVER['SERVER_PORT'] != '80') {

		$pageURL .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];

	} else {

		$pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

	}

	return $pageURL;
}

/*
 * UPDATING the plugin automatically
 */
add_action( 'init', 'trksit_github_plugin_updater_init' );
function trksit_github_plugin_updater_init() {

	include_once 'updater.php';

	define( 'WP_GITHUB_FORCE_UPDATE', false );

	if ( is_admin() ) { // note the use of is_admin() to double check that this is happening in the admin

		$config = array(
			'slug'               => plugin_basename(__FILE__), // this is the slug of your plugin
			'proper_folder_name' => 'wp_trksit', // this is the name of the folder your plugin lives in
			'api_url'            => 'https://api.github.com/repos/trksit/wp_trksit', // the github API url of your github repo
			'raw_url'            => 'https://raw.github.com/trksit/wp_trksit/master', // the github raw url of your github repo
			'github_url'         => 'https://github.com/trksit/wp_trksit', // the github url of your github repo
			'zip_url'            => 'https://github.com/trksit/wp_trksit/archive/master.zip', // the zip url of the github repo
			'sslverify'          => false,
			'requires'           => '1.1', // which version of WordPress does your plugin require?
			'tested'             => '4.0', // which version of WordPress is your plugin tested up to?
			'readme'             => 'README.md'
		);

		new WP_GitHub_Updater( $config );

	}

}

/** Increase http request timeout */
define( 'WP_TRKSIT_CURL_TIMEOUT', 15 );
add_filter( 'http_request_args', 'trksit_http_request_args', 100, 1 );
function trksit_http_request_args( $r ) {

	$r['timeout'] = WP_TRKSIT_CURL_TIMEOUT;
	return $r;

}

/** Set some cURL parameters */
add_action( 'http_api_curl', 'trksit_http_api_curl', 100, 1 );
function trksit_http_api_curl( $handle ) {

	curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, WP_TRKSIT_CURL_TIMEOUT );
	curl_setopt( $handle, CURLOPT_TIMEOUT, WP_TRKSIT_CURL_TIMEOUT );

}

/** Output buffer flush */
function trksit_flush_buffers() {

	ob_end_flush();
	@ob_flush();
	flush();
	ob_start();

}

/*****************************************************
 * Commented out these add_actions but left functions
 * in case they must be added back
 *****************************************************/

/** Sets the URL and sets an arbitrary query variable */
//add_action( 'init', 'trksit_init_internal' );
/*
 *function trksit_init_internal(){
 *    add_rewrite_rule( 'trksitgo$', 'index.php?trksitgo=1', 'top' );
 *}
 */

/** Registers the query variable */
//add_filter( 'query_vars', 'trksit_query_vars' );
/*
 *function trksit_query_vars( $query_vars ){
 *    $query_vars[] = 'trksitgo';
 *    return $query_vars;
 *}
 */

/** Include the template when loaded */
//add_action( 'parse_request', 'trksit_parse_request' );
/*
 *function trksit_parse_request( &$wp ){
 *    if ( array_key_exists( 'trksitgo', $wp->query_vars ) ) {
 *        include 'wp_trksit_redirector.php';
 *        exit();
 *    }
 *    return;
 *}
 */

/** flush_rules() if our rules are not yet included */
//add_action( 'wp_loaded','trksit_flush_rules' );
/*
 *function trksit_flush_rules(){
 *    $rules = get_option( 'rewrite_rules' );
 *    if ( ! isset( $rules['trksitgo$'] ) ) {
 *        global $wp_rewrite;
 *        $wp_rewrite->flush_rules();
 *    }
 *}
 */
/*****************************************************
 *****************************************************/


/** Add header encoding for output buffering */
add_action( 'wp_loaded', 'trksit_set_header_encoding' );
function trksit_set_header_encoding(){

	if( isset( $_GET['page'] ) && ( $_GET['page'] == 'trksit-generate' || $_GET['page'] == 'trksit-settings' || $_GET['page'] == 'trksit-dashboard' ) && !empty( $_POST ) ){

		header( 'Content-Encoding: none;' ); // Use with ob_start() and flushing of buffers!!!

	}

}

/** Start session for generate URL section */
add_action( 'init', 'trksit_session_start' );
function trksit_session_start(){

	if( isset( $_GET['page'] ) && ( $_GET['page'] == 'trksit-generate' || $_GET['page'] == 'trksit-settings' ) && session_id() == '' ){
		session_start();
	}

}

/*
 * Delete source or domain through CRUD interface
 * After deletion strip query variables and redirect.
 */
add_action( 'init', 'trksit_delete_source_redirect' );
function trksit_delete_source_redirect(){

	if ( isset( $_GET['deletesource'] ) && wp_verify_nonce( $_GET['ds_nonce'], 'delete_source' ) ) {

		$d_sources = maybe_unserialize( get_option( 'trksit_sources' ) );

		array_splice( $d_sources, (int) $_GET['deletesource'], 1 );
		update_option( 'trksit_sources', serialize( $d_sources ) );

		$url = remove_query_arg( array( 'ds_nonce', 'deletesource' ), str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ) );
		wp_redirect( $url );

	}

	if ( isset( $_GET['deletedomain'] ) && wp_verify_nonce( $_GET['dd_nonce'], 'delete_domain' ) ) {

		$d_domains = maybe_unserialize( get_option( 'trksit_domains' ) );

		array_splice( $d_domains, (int) $_GET['deletedomain'], 1 );
		update_option( 'trksit_domains', serialize( $d_domains ) );

		$url = remove_query_arg( array( 'dd_nonce', 'deletedomain' ), str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ) );
		wp_redirect( $url );

	}

	if ( isset( $_GET['deletemedium'] ) && wp_verify_nonce( $_GET['dm_nonce'], 'delete_medium' ) ) {

		$d_medium = maybe_unserialize( get_option( 'trksit_medium' ) );

		array_splice( $d_medium, (int) $_GET['deletemedium'], 1 );
		update_option( 'trksit_medium', serialize( $d_medium ) );

		$url = remove_query_arg( array( 'dm_nonce', 'deletemedium' ), str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ) );
		wp_redirect( $url );

	}

}

/** cleaner way to force 404 */
add_action( 'wp', 'trksit_parse_query_404' );
function trksit_parse_query_404() {

	global $wp_query;

	if ( isset( $_GET['error404'] ) && $_GET['error404'] == 'true' ) {

		$wp_query->set_404();
		status_header( 404 );

	}

}

/** set original source, medium, campaign cookie */
function original_cookies( $party = false, $notgo = false ){

	if ( isset( $_POST['utmz'] ) ) {

		list( $source, $campaign, $medium ) = explode( '|', $_POST['utmz'] );

		//source
		$source = explode( '=', $source );
		$source = $source[1];

		//campaign
		$campaign = explode( '=', $campaign );
		$campaign = $campaign[1];

		//medium
		$medium = explode( '=', $medium );
		$medium = $medium[1];

	} else {

		$ga_parse = new GA_Parse( $_COOKIE );
		$source = ( isset( $_GET['utm_source'] ) ? $_GET['utm_source'] : $ga_parse->campaign_source );
		$medium = ( isset( $_GET['utm_medium'] ) ? $_GET['utm_medium'] : $ga_parse->campaign_medium );
		$campaign = ( isset( $_GET['utm_campaign'] ) ? $_GET['utm_campaign'] : $ga_parse->campaign_name );

	}

	//set original source, medium and campaign
	setcookie( 'trksit_original_source', $source, time() + 400000 );
	setcookie( 'trksit_original_medium', $medium, time() + 400000 );
	setcookie( 'trksit_original_campaign', $campaign, time() + 400000 );

	//set converting source, medium and campaign
	converting_cookies( $party, $notgo );

}

/** set converting source, medium, campaign cookie */
function converting_cookies( $party = false, $notgo = false ){

	if ( isset( $_COOKIE['__utmz'] ) ){

		list( $source, $campaign, $medium ) = explode( '|', $_COOKIE['__utmz'] );

		//source
		$source = explode( '=',$source );
		$source = $source[1];

		//campaign
		$campaign = explode( '=', $campaign );
		$campaign = $campaign[1];

		//medium
		$medium = explode( '=' , $medium );
		$medium = $medium[1];

	} else {

		$ga_parse = new GA_Parse( $_COOKIE );
		$source = ( isset( $_GET['utm_source'] ) ? $_GET['utm_source'] : $ga_parse->campaign_source );
		$medium = ( isset( $_GET['utm_medium'] ) ? $_GET['utm_medium'] : $ga_parse->campaign_medium );
		$campaign = ( isset( $_GET['utm_campaign'] ) ? $_GET['utm_campaign'] : $ga_parse->campaign_name );

	}

	//set converting source, medium and campaign
	setcookie( 'trksit_converting_source', $source, time() + 400000 );
	setcookie( 'trksit_converting_medium', $medium, time() + 400000 );
	setcookie( 'trksit_converting_campaign', $campaign, time() + 400000 );

}

/*
 * Pulse webhook, returns {"alive":true} when GET parameter pulse is set to check
 * Used by trks.it redirector to make sure plugin is alive
 * Added to fire very early to bypass any force login type plugins
 *
 * http://yoursite.com/?trksitgo=1&pulse=check
 */
add_action( 'plugins_loaded', 'wp_trksit_pulse', -9999 );
function wp_trksit_pulse(){

	if ( isset( $_GET['trksitpulse'] ) && $_GET['trksitpulse'] == 'check' ){
		if(isset($_GET['urlid'])){
			global $wpdb;
			$results = $wpdb->get_results("SELECT destination_url FROM " . $wpdb->prefix. "trksit_urls WHERE url_id = " . intval($_GET['urlid']));
			if(!$results){
				die(json_encode(array('alive' => false)));
			}
		}
		die( json_encode( array( 'alive' => true ) ) );
	}

}

/*
 * Parameter to load the redirection page
 * Fires very early to bypass any force login type plugins
 * Used in the long_url
 */
add_action( 'plugins_loaded', 'wp_trksit_redirect_page', -9999 );
function wp_trksit_redirect_page(){

	if ( isset( $_GET['trksitgo'] ) && $_GET['trksitgo'] == 1 ){

		include 'wp_trksit_redirector.php';
		exit();

	}

}

add_action( 'init', 'wp_trksit_validate_generate_url' );
function wp_trksit_validate_generate_url(){

	if ( isset( $_GET['page'] ) && $_GET['page'] == 'trksit-generate' && isset( $_POST['trksit_generate_step1'] ) ) {

		if ( !isset( $_POST['destination_url'] ) || ( isset( $_POST['destination_url'] ) && $_POST['destination_url'] == '' ) ) {

			$_SESSION['trksit_error'] = 'We can\'t shorten a link if you don\'t give us one!';
			wp_redirect( '/wp-admin/admin.php?page=trksit-generate' );

		} else {

			$url_segments = parse_url( $_POST['destination_url'] );
			$url = $_POST['destination_url'];
			$valid_schemes = array( 'https', 'http' );

			if ( filter_var( $_POST['destination_url'], FILTER_VALIDATE_URL ) === FALSE ) {

				if ( !isset( $url['scheme'] ) ) {

					if ( isset( $url_segments['path'] ) && strpos( $url_segments['path'], '.' ) === false ) {

						$_SESSION['trksit_error'] = 'Invalid URL. Example: http://example.com';
						wp_redirect( '/wp-admin/admin.php?page=trksit-generate' );

					}

					$_POST['destination_url'] = 'http://' . $url;

				}

			} else {

				if ( !in_array( $url_segments['scheme'], $valid_schemes ) ) {

					$_SESSION['trksit_error'] = 'Invalid URL. Example: http://example.com';
					wp_redirect( '/wp-admin/admin.php?page=trksit-generate' );

				}

			}

		}

	}

}

add_action( 'init', 'wp_trksit_validate_domains' );
function wp_trksit_validate_domains(){

	if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'domains' && isset( $_POST['domain_submit'] ) ) {

		if ( $_POST['domain'] != '') {

			$t_domains = maybe_unserialize( get_option( 'trksit_domains' ) );

			array_push( $t_domains, $_POST['domain'] );
			update_option( 'trksit_domains', serialize( $t_domains ) );

		} else {

			$_SESSION['trksit_error'] = 'Invalid URL. Example: http://example.com';

		}

	}

}

add_action( 'init', 'wp_trksit_validate_medium' );
function wp_trksit_validate_medium(){

	if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'medium' && isset( $_POST['medium_submit'] ) ) {

		if ( $_POST['medium'] != '' ) {

			$t_medium = maybe_unserialize( get_option( 'trksit_medium' ) );
			array_push( $t_medium, $_POST['medium'] );
			update_option( 'trksit_medium', serialize( $t_medium ) );

		} else {

			$_SESSION['trksit_error'] = 'Medium field required';

		}

	}

}

add_action( 'init', 'wp_trksit_validate_source' );
function wp_trksit_validate_source(){

	if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'sources' && isset( $_POST['source_submit'] ) ) {

		if ( $_POST['source'] != '' ) {

			$t_source = maybe_unserialize( get_option( 'trksit_sources' ) );

			array_push( $t_source, $_POST['source'] );
			update_option( 'trksit_sources', serialize( $t_source ) );

		} else {

			$_SESSION['trksit_error'] = 'Source field required';

		}

	}

}

add_action( 'admin_init', 'wp_trksit_daterange_transient' );
function wp_trksit_daterange_transient(){

	if ( isset( $_GET['page'] ) && $_GET['page'] == 'trksit-dashboard' ) {

		$uid = wp_get_current_user();
		$sd = date( 'Y-m-d', strtotime( 'last week' ) );
		$ed = date( 'Y-m-d', time() );

		if ( !get_transient( 'wp_trksit_daterange_user' . $uid->ID ) ) {

			if ( isset( $_GET['trksit_start_date'] ) && $_GET['trksit_start_date'] != '' ) {
				$sd = date( 'Y-m-d', strtotime( urldecode( $_GET['trksit_start_date'] ) ) );
			}

			if ( isset( $_GET['trksit_end_date'] ) && $_GET['trksit_end_date'] != '' ) {
				$ed = date( 'Y-m-d', strtotime( urldecode( $_GET['trksit_end_date'] ) ) );
			}

			$dr = array(
					'start' => $sd,
					'end'   => $ed
			);

			set_transient( 'wp_trksit_daterange_user' . $uid->ID, serialize( $dr ), 60 );

		}

	}

}

//Debug log function
if ( !function_exists( '_log' ) ) {

	function _log( $message ){

		if ( WP_DEBUG === true && WP_DEBUG_LOG === true ) {

			if( is_array( $message ) || is_object( $message ) ){

				error_log( print_r( $message, true ) );

			} else {

				error_log( $message );
			}

		}

	}

}
