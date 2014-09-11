<?php
/*
Plugin Name: Trks.it for WordPress
Plugin URI: https://get.trks.it?utm_source=WordPress%20Admin%20Link
Description: Ever wonder how many people click links that lead to 3rd party sites from your social media platforms? Trks.it is a WordPress plugin for tracking social media engagement.
Author: Arsham Mirshah, De'Yonte Wilkinson, Derek Cavaliero
Version: 1.3.1
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

	$table_1_name = $wpdb->prefix . "trksit_urls";
	$table_1_sql = "CREATE TABLE $table_1_name (
	  url_id INT(10) unsigned NOT NULL AUTO_INCREMENT,
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

}

include( plugin_dir_path( __FILE__ ) . 'inc/trksit.class.php');
add_action( 'init', array( new trksit, '__construct' ) );
//load the needed scripts
add_action('admin_enqueue_scripts', 'trksit_load_scripts');
function trksit_load_scripts() {
	if(isset($_GET['page']) && ($_GET['page'] == 'trksit-dashboard' || $_GET['page'] == 'trksit-settings' || $_GET['page'] == 'trksit-generate')){
		wp_register_style('trksit-bootstrap', plugins_url( '/wp_trksit/css/bootstrap.min.css' , dirname(__FILE__)));
		wp_register_style('trksit-styles', plugins_url( '/wp_trksit/css/wp_trksit_style.css' , dirname(__FILE__)));
		wp_register_script('trksit-bootstrap-js', plugins_url( '/wp_trksit/js/bootstrap.min.js' , dirname(__FILE__)),array('jquery'));
		wp_register_script('trksit-zclip-js', plugins_url( '/wp_trksit/js/jquery.zclip.js' , dirname(__FILE__)),array('jquery'),'1.1.1',true);
		wp_register_script('trksit-validation-js', plugins_url( '/wp_trksit/js/jquery.validate.min.js' , dirname(__FILE__)),array('jquery'),'1.11.1');
		wp_register_script('trksit-main-js', plugins_url( '/wp_trksit/js/main.js', dirname(__FILE__)),array('jquery'),'1.2.1');
		wp_register_script('jquery-image-picker', plugins_url( '/wp_trksit/js/image-picker.min.js' , dirname(__FILE__) ),array('jquery'),'0.1.3',true);

		wp_enqueue_style('trksit-bootstrap');
		wp_enqueue_style('trksit-styles' );
		wp_enqueue_script('trksit-bootstrap-js');
		wp_enqueue_script('trksit-zclip-js');
		wp_enqueue_script('trksit-validation-js');
		wp_enqueue_script('jquery-image-picker');
		wp_enqueue_script('trksit-main-js');

	}
  if(isset($_GET['page']) && $_GET['page'] == 'trksit-dashboard'){
  	wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_style('morris-css', plugins_url( '/wp_trksit/js/morris.js/morris.css',dirname(__FILE__)),'','0.4.3');
		wp_register_script('raphael-js', plugins_url( '/wp_trksit/js/raphael-min.js',dirname(__FILE__)) , '','2.1.2');
		wp_enqueue_script('raphael-js');
		wp_enqueue_script('morris-js', plugins_url( '/wp_trksit/js/morris.js/morris.min.js',dirname(__FILE__)),array('raphael-js'),'0.4.3');
		wp_enqueue_script('datatables', plugins_url( '/wp_trksit/js/datatables/js/jquery.dataTables.min.js',dirname(__FILE__)),array('jquery'),'1.9.4',true);
		wp_enqueue_style('jquery-ui-bootstrap', plugins_url( '/wp_trksit/css/jquery-ui-1.10.0.custom.css',dirname(__FILE__)),'','0.4.3');
	}
  if(isset($_GET['page']) && $_GET['page'] == 'trksit-generate'){

		wp_register_script('trksit-generate-js', plugins_url( '/wp_trksit/js/generate.js' , dirname(__FILE__)),array('jquery'),'1.2.1',true);
		wp_enqueue_script('trksit-generate-js');
	}
}

//add pages to WordPress sidebar
add_action('admin_menu', 'trksit_add_pages');
function trksit_add_pages() {
  add_menu_page(__('Dashboard &lsaquo; Trks.it','trksit_menu'), __('Trks.it','trksit_menu'), 'manage_options', 'trksit-dashboard', 'trksit_dashboard', plugins_url( '/wp_trksit/img/trksit-icon-16x16.png' , dirname(__FILE__) ) );
  add_submenu_page('trksit-dashboard', __('Generate URL &lsaquo; Trks.it','trksit_menu'), __('Generate URL','trksit_menu'), 'manage_options', 'trksit-generate', 'trksit_generate');
  add_submenu_page('trksit-dashboard', __('Plugin Settings &lsaquo; Trks.it','trksit_menu'), __('Settings','trksit_menu'), 'manage_options', 'trksit-settings', 'trksit_settings');
}

// Dashboard Page Content
function trksit_dashboard() {

  if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}else{
    global $wpdb;
		include('wp_trksit_dashboard.php');
	}

}

// Generate URL Page Content
function trksit_generate() {

  if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}else{
    global $wpdb;
		include('wp_trksit_generate_url.php');
	}

}

// Settings Page Content
function trksit_settings() {

  if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}else{
    global $wpdb;
		include('wp_trksit_settings.php');
	}

}

function trksit_current_page() {
	$pageURL = 'http';
	if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
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
      'slug' => plugin_basename(__FILE__), // this is the slug of your plugin
      'proper_folder_name' => 'wp_trksit', // this is the name of the folder your plugin lives in
      'api_url' => 'https://api.github.com/repos/trksit/wp_trksit', // the github API url of your github repo
      'raw_url' => 'https://raw.github.com/trksit/wp_trksit/master', // the github raw url of your github repo
      'github_url' => 'https://github.com/trksit/wp_trksit', // the github url of your github repo
      'zip_url' => 'https://github.com/trksit/wp_trksit/archive/master.zip', // the zip url of the github repo
      'sslverify' => false,
      'requires' => '1.1', // which version of WordPress does your plugin require?
      'tested' => '3.7', // which version of WordPress is your plugin tested up to?
      'readme' => 'README.md'
    );
	  new WP_GitHub_Updater($config);
	}
 }
//Increase http request timeout
define('WP_TRKSIT_CURL_TIMEOUT', 15);
add_filter('http_request_args', 'trksit_http_request_args', 100, 1);
function trksit_http_request_args($r)
{
   $r['timeout'] = WP_TRKSIT_CURL_TIMEOUT;
	return $r;
}

add_action('http_api_curl', 'trksit_http_api_curl', 100, 1);
function trksit_http_api_curl($handle)
{
   curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, WP_TRKSIT_CURL_TIMEOUT );
   curl_setopt( $handle, CURLOPT_TIMEOUT, WP_TRKSIT_CURL_TIMEOUT );
}

function trksit_flush_buffers() {
   ob_end_flush();
   @ob_flush();
   flush();
   ob_start();
}


 // Sets the URL and sets an arbitrary query variable
 add_action( 'init', 'trksit_init_internal' );
 function trksit_init_internal(){
	add_rewrite_rule( 'trksitgo$', 'index.php?trksitgo=1', 'top' );
 }

 // Registers the query variable
 add_filter( 'query_vars', 'trksit_query_vars' );
 function trksit_query_vars( $query_vars ){
	$query_vars[] = 'trksitgo';
	return $query_vars;
 }

 // Include the template when loaded
 add_action( 'parse_request', 'trksit_parse_request' );
 function trksit_parse_request( &$wp ){
	if ( array_key_exists( 'trksitgo', $wp->query_vars ) ) {
	   include 'wp_trksit_redirector.php';
	   exit();
	}
	return;
 }

 // flush_rules() if our rules are not yet included
 add_action( 'wp_loaded','trksit_flush_rules' );
 function trksit_flush_rules(){
	$rules = get_option( 'rewrite_rules' );
	if ( ! isset( $rules['trksitgo$'] ) ) {
	   global $wp_rewrite;
	   $wp_rewrite->flush_rules();
	}
 }

 add_action( 'wp_loaded','trksit_set_header_encoding' );
 function trksit_set_header_encoding(){
	if(isset($_GET['page']) && ($_GET['page'] == 'trksit-generate' || $_GET['page'] == 'trksit-settings' || $_GET['page'] == 'trksit-dashboard') && !empty($_POST)){
	   header('Content-Encoding: none;'); // Use with ob_start() and flushing of buffers!!!
	}
 }

 //Debug log function
 if(!function_exists('_log')){
	function _log( $message ) {
	   if( WP_DEBUG === true && WP_DEBUG_LOG === true ){
		  if( is_array( $message ) || is_object( $message ) ){
			 error_log( print_r( $message, true ) );
		  } else {
			 error_log( $message );
		  }
	   }
	}
 }
