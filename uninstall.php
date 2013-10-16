<?php

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();
 
 
global $wpdb;
$table_1_name = $wpdb->prefix . "trksit_urls";
$table_2_name = $wpdb->prefix . "trksit_hits";
$table_3_name = $wpdb->prefix . "trksit_scripts";
$table_4_name = $wpdb->prefix . "trksit_scripts_to_urls";

$table_1_sql = "DROP TABLE IF EXISTS $table_1_name";
$table_2_sql = "DROP TABLE IF EXISTS $table_2_name";
$table_3_sql = "DROP TABLE IF EXISTS $table_3_name";
$table_4_sql = "DROP TABLE IF EXISTS $table_4_name";

$wpdb->query($table_1_sql);    
$wpdb->query($table_2_sql);    
$wpdb->query($table_3_sql);    
$wpdb->query($table_4_sql);    


?>