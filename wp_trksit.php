<?php 	
	/*
	Plugin Name: Trks.it for WordPress
	Plugin URI: http://get.trks.it?utm_source=WordPress%20Admin%20Link
	Description: Ever wonder how many people click links that lead to 3rd party sites from your social media platforms? Trks.it is a WordPress plugin for tracking social media engagement.
	Author: Arsham Mirshah, Deyonte Wilkinson, Derek Cavaliero
	Version: 1.0
	Author URI: http://get.trks.it?utm_source=WordPress%20Admin%20Link
	*/

  //showing all errors
  //error_reporting(E_ALL);
  //ini_set('display_errors', 1);	

	function trksit_load_scripts() {
		if($_GET['page'] == 'trksit-dashboard' || $_GET['page'] == 'trksit-settings' || $_GET['page'] == 'trksit-generate'){
			  include( plugin_dir_path( __FILE__ ) . 'inc/trksit.class.php');
				wp_register_style( 'trksit-bootstrap', plugins_url( '/wp_trksit/css/bootstrap.min.css' , dirname(__FILE__) ) );
				wp_register_style( 'trksit-styles', plugins_url( '/wp_trksit/css/wp_trksit_style.css' , dirname(__FILE__) ) );
				wp_register_script( 'trksit-bootstrap-js', plugins_url( '/wp_trksit/js/bootstrap.min.js' , dirname(__FILE__) ) );
				wp_register_script( 'trksit-zclip-js', plugins_url( '/wp_trksit/js/jquery.zclip.js' , dirname(__FILE__) ) );
				wp_register_script( 'trksit-validation-js', plugins_url( '/wp_trksit/js/jquery.validate.min.js' , dirname(__FILE__) ) );   
				wp_register_script( 'trksit-main-js', plugins_url( '/wp_trksit/js/main.js' , dirname(__FILE__) ) );
				
				wp_enqueue_style( 'trksit-bootstrap' );
				wp_enqueue_style( 'trksit-styles' );
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'trksit-bootstrap-js', false, array('jquery') );
				wp_enqueue_script( 'trksit-zclip-js', false, array('jquery') );
				wp_enqueue_script( 'trksit-validation-js', false, array('jquery') );
        wp_enqueue_script( 'jquery-image-picker', plugins_url( '/wp_trksit/js/image-picker.min.js' , dirname(__FILE__) ) );
				wp_enqueue_script( 'trksit-main-js', false, array('jquery') );
      
			}
	    if($_GET['page'] == 'trksit-dashboard'){
					
				wp_enqueue_script( 'jquery-flot', plugins_url( '/wp_trksit/js/jquery.flot.pkg.js' , dirname(__FILE__) ) );
				//wp_enqueue_script( 'trksit-dashboard', plugins_url( '/wp_trksit/js/dashboard.js' , dirname(__FILE__) ) );
			
			}
	    if($_GET['page'] == 'trksit-generate'){
			
				
				wp_register_script( 'trksit-generate-js', plugins_url( '/wp_trksit/js/generate.js' , dirname(__FILE__) ) );
				wp_enqueue_script( 'trksit-generate-js' );
			}	
	}
	add_action('admin_enqueue_scripts', 'trksit_load_scripts');

    // Installation Script
    function trksit_Install(){
      
        global $wpdb;
 
		$table_1_name = $wpdb->prefix . "trksit_urls";
		$table_1_sql = "CREATE TABLE $table_1_name (
		  url_id INT NOT NULL AUTO_INCREMENT,
		  date_created DATE DEFAULT '0000-00-00' NOT NULL,
		  destination_url VARCHAR(255) DEFAULT '' NOT NULL,
		  trksit_url VARCHAR(255) DEFAULT '' NOT NULL,
		  source VARCHAR(255) DEFAULT '' NOT NULL,
		  medium VARCHAR(255) DEFAULT '' NOT NULL,
		  campaign VARCHAR(255) DEFAULT '' NOT NULL,
		  meta_title VARCHAR(255) DEFAULT '' NOT NULL,
		  meta_description VARCHAR(255) DEFAULT '' NOT NULL,
		  meta_image VARCHAR(255) DEFAULT '' NOT NULL,
		  og_data TEXT DEFAULT '' NOT NULL,
		  PRIMARY KEY  url_id (url_id)
		);";
		
		$table_2_name = $wpdb->prefix . "trksit_hits";
		$table_2_sql = "CREATE TABLE $table_2_name (
		  hit_count INT NOT NULL,
		  url_id INT NOT NULL,
		  hit_date DATE DEFAULT '0000-00-00' NOT NULL,
		  PRIMARY KEY  (url_id, hit_date)
		);";
		
		$table_3_name = $wpdb->prefix . "trksit_scripts";
		$table_3_sql = "CREATE TABLE $table_3_name (
		  script_id INT NOT NULL AUTO_INCREMENT,
		  date_created DATE DEFAULT '0000-00-00' NOT NULL,
		  label VARCHAR(255) DEFAULT '' NOT NULL,
		  script TEXT DEFAULT '' NOT NULL,
		  PRIMARY KEY  script_id (script_id)
		);";
		
		$table_4_name = $wpdb->prefix . "trksit_scripts_to_urls";
		$table_4_sql = "CREATE TABLE $table_4_name (
		  assignment_id INT NOT NULL AUTO_INCREMENT,
		  script_id INT NOT NULL,
		  url_id INT NOT NULL,
		  PRIMARY KEY  (assignment_id, script_id, url_id)
		);";
		
		update_option('trksit_jquery', 0);
		update_option('trksit_redirect_delay', 500);
		update_option('trksit_token', '');
		update_option('trksit_token_expires', 1);
        
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    		
		dbDelta( $table_1_sql );
		dbDelta( $table_2_sql );
		dbDelta( $table_3_sql );
		dbDelta( $table_4_sql );		      
    }     
    register_activation_hook( __FILE__, 'trksit_Install' );

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
?>