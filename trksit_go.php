<?php 

  // Setting cache-control headers 
  header("Cache-Control: no-cache, must-revalidate");
  header("Expires: Tue, 01 Jan 1980 1:00:00 GMT");


  //Loading WordPress
  define( 'SHORTINIT', true );
  require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

   
  //Getting options 
  $analytics_id = get_option('trksit_analytics_id');  
  $redirect_delay = get_option('trksit_redirect_delay');
  $redirect = '';


  //THIS SHOULD BE GOTTEN FROM THE DB
  $api_signature = 'testing12345678';
  //KILL NEXT LINE TO SECURE
  $_GET['api_signature'] = 'testing12345678';
  




  // Check request method and ensure all parameters are present in return from API.
  if( $_SERVER['REQUEST_METHOD'] == 'GET' && ( isset( $_GET['url_id'] ) && isset( $_GET['api_signature'] ) ) ){
  
    // Check API Signature (Needs work!)
    if( $_GET['api_signature'] == $api_signature ){
      
     $incoming_url_id = $_GET['url_id'];
      
     global $wpdb;
     $redirect_lookup = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'trksit_urls WHERE url_id=' . $incoming_url_id ); 
     
     // If destination URL exsists in wpdb result. Output redirect script.
     if($redirect_lookup[0]->destination_url){

      $url_id = $redirect_lookup[0]->url_id;
      $today = date('Y-m-d');
      
      //Getting all the scripts for this URL
      $scripts_to_url = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "trksit_scripts_to_urls WHERE url_id=" . $url_id);
      $script_array = array();
      foreach($scripts_to_url as $single_script){
	      $single_script = $wpdb->get_results("SELECT script FROM " . $wpdb->prefix . "trksit_scripts WHERE script_id=" . $single_script->script_id);
	      array_push($script_array, $single_script[0]->script);
      }
      
      $hit_result = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "trksit_hits WHERE url_id=" . $url_id . " AND hit_date='" . $today . "'"); 
      $hit_result_count = count($hit_result);      
     
      if($hit_result_count === 1){
        //echo 'Update the current hit record and redirect to (' . $redirect_lookup[0]->destination_url . ').';
        
        $update_results = $wpdb->query(
                            $wpdb->prepare(
                              "
                              UPDATE " . $wpdb->prefix ."trksit_hits 
                              SET hit_count = hit_count + 1 
                              WHERE url_id = %d 
                              AND hit_date = %s
                              ",
                              $url_id, $today
                            )
                          );
                      
        if($update_results){
          $redirect = '<script type="text/javascript">setTimeout(function(){window.location.href = "' . $redirect_lookup[0]->destination_url . '"},' . $redirect_delay . ');</script>';    
        }
        
      }
      else if($hit_result_count === 0){
        //echo 'Insert a new hit record and redirect to (' . $redirect_lookup[0]->destination_url . ').';
        
          $wpdb->insert( 
          	$wpdb->prefix . 'trksit_hits', 
          	array( 
          		'hit_count' => 1, 
          		'url_id' => $url_id,
		  		'hit_date' => $today 
          	), 
          	array( '%d', '%d','%s' ) 
          ); 
          
          if($wpdb->insert_id){
            $redirect = '<script type="text/javascript">setTimeout(function(){window.location.href = "' . $redirect_lookup[0]->destination_url . '"},' . $redirect_delay . ');</script>';  
          }
          
      }else{ die; }
     
     }else{ die; }
     
    }else{ die; }
    
  }else{ die; }

if (strpos($_SERVER['HTTP_USER_AGENT'], "facebook")){
	header ('HTTP/1.1 301 Moved Permanently');
	header ('Location: '. $redirect_lookup[0]->destination_url);
}

?>
<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#" xmlns:og="http://opengraphprotocol.org/schema/" xmlns:fb="http://www.facebook.com/2008/fbml">
  	<head>
		<title><?php echo $redirect_lookup[0]->meta_title; ?></title>
		<meta name="description" content="<?php echo $redirect_lookup[0]->meta_description; ?>" />

		<link rel="canonical" href="<?php echo $redirect_lookup[0]->destination_url; ?>">

		<!-- Making Sure Page doesn't get indexed or cached -->
		<!--meta name="robots" content="noindex, nofollow" />
		<meta http-equiv="cache-control" content="max-age=0" />
		<meta http-equiv="cache-control" content="no-cache" />
		<meta http-equiv="expires" content="0" />
		<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
		<meta http-equiv="pragma" content="no-cache" /-->
		
		    
		<?php
		
		//Get the Open Graph data & unseralize it
		$ogArray = unserialize($redirect_lookup[0]->og_data);
		
		foreach($ogArray as $key => $value){
			echo '<meta property="' . $key . '" content="' . $value . '" />
			';
		}
		
		//if the open graph image is NOT set, we need to set it
		if(!isset($ogArray['og:image'])){
			echo '<meta property="og:image" content="' . $redirect_lookup[0]->meta_image . '" />
			';
		}
		
		//if the open graph URL is NOT set, we need to set it
		if(!isset($ogArray['og:url'])){
			echo '<meta property="og:url" content="' . $redirect_lookup[0]->destination_url . '" />
			';
		}
		
		?>
    
    
		<script type="text/javascript">
		
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', '<?php echo $analytics_id; ?>']);
		_gaq.push(['_setAllowLinker', true]);
//		_gaq.push(['_trackPageview']);
		_gaq.push(['_setCustomVar', 1, 'Trks.it', '<?php echo $redirect_lookup[0]->destination_url; ?>', 1]);
        _gaq.push(['_trackEvent', 'Trks.it', 'Clicked Link', '<?php echo $redirect_lookup[0]->destination_url; ?>'], 0, true);
        
        (function() {
		  var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		  ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		  var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
		
		</script>    
    
  	</head>
  	<body>
  		<?php 
	  		foreach($script_array as $script){
		  		$script_out = stripslashes($script);
		  		$script_out = stripslashes($script_out);
		  		
		  		echo $script_out;
	  		}
	  		
  		?>
  	
	  	<?php echo $redirect; ?>
	</body>
</html>