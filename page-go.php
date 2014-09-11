<?php
   // Setting cache-control headers
   header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
   header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',false);
   header("Pragma: no-cache");

   //Forward to 404 page if id is not set.
   if(!isset($_GET['url_id'])){
	  $four04 = get_site_url() . '/error404';
	  echo '<script type="text/javascript">setTimeout(function(){window.location.href = "'.$four04.'"},0);</script>';
	  echo '<meta http-equiv="refresh" content="2; url='.$four04.'">';
   }

   //Getting options
   $analytics_id = get_option('trksit_analytics_id');
   $redirect_delay = get_option('trksit_redirect_delay');
   $redirect = '';
   setcookie("trks_new", "new_user", time()+900);


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

		 $js_redir = '<script type="text/javascript">setTimeout(function(){window.location.href = "'
			. $redirect_lookup[0]->destination_url . '"},' . $redirect_delay . ');</script>';
		 $meta_redir = '<meta http-equiv="refresh" content="2; url='.$redirect_lookup[0]->destination_url.'">';

		 // If destination URL exsists in wpdb result. Output redirect script.
		 if($redirect_lookup[0]->destination_url){

			$url_id = $redirect_lookup[0]->url_id;
			$today = date('Y-m-d');

			//Getting all the scripts for this URL
			$scripts_to_url = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "trksit_scripts_to_urls WHERE url_id=" . $url_id);
			$script_array = array();
			foreach($scripts_to_url as $single_script){
			   $script_results = array();
			   $single_script = $wpdb->get_results("SELECT script, script_id, script_error FROM "
			   . $wpdb->prefix . "trksit_scripts WHERE script_id="
			   . $single_script->script_id);
			   $script_results['script'] = $single_script[0]->script;
			   $script_results['id'] = $single_script[0]->script_id;
			   $script_results['error'] = $single_script[0]->script_error;
			   array_push($script_array, $script_results);
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
				  //$redirect = '<script type="text/javascript">setTimeout(function(){window.location.href = "' . $redirect_lookup[0]->destination_url . '"},' . $redirect_delay . ');</script>';
				  $redirect = $js_redir . $meta_redir;
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
				  //$redirect = '<script type="text/javascript">setTimeout(function(){window.location.href = "' . $redirect_lookup[0]->destination_url . '"},' . $redirect_delay . ');</script>';
				  $redirect = $js_redir . $meta_redir;
			   }

			}else{ die; }

		 }else{
			//$trksit = new trksit();
			//$surl = $_GET['su'];
			//$flags_set = $trksit->wp_trksit_setMissingFlags($surl);
			$four04 = get_site_url() . '/error404';
			echo '<script type="text/javascript">setTimeout(function(){window.location.href = "'.$four04.'"},0);</script>';
			echo '<meta http-equiv="refresh" content="2; url='.$four04.'">';
		 }

	  }else{ die; }

   }else{ die; }


?>
<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#" xmlns:og="http://opengraphprotocol.org/schema/" xmlns:fb="http://www.facebook.com/2008/fbml">
   <head>
	  <?php if($redirect == ''): ?>
	  <meta http-equiv="refresh" content="0; url=<?php echo $redirect_lookup[0]->destination_url; ?>">
	  <?php endif; ?>

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
		 function getCookie(c_name) {
			   if (document.cookie.length > 0) {
				  c_start = document.cookie.indexOf(c_name + "=");
				  if (c_start != -1) {
					 c_start = c_start + c_name.length + 1;
					 c_end = document.cookie.indexOf(";", c_start);
					 if (c_end == -1) c_end = document.cookie.length;
					 return unescape(document.cookie.substring(c_start, c_end));
				  }
			   }
			   return "";
			}
		 </script>



		 <script type="text/javascript">
			//		always set the GA account
			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', '<?php echo $analytics_id; ?>']);

			//REQUIRED FOR LOCAL DEVELOPMENT
			//		_gaq.push(['_setDomainName', 'none']);
			//		_gaq.push(['_setAllowLinker', true]);

			// 		if they haven't been here.. push an event to set their GA cookies
			var delay = 0;
			if(!getCookie("trks_new")){
			   // Fire an event to set it
			   _gaq.push(['_trackEvent', 'trks.it', 'New Visitor', '<?php echo $redirect_lookup[0]->destination_url; ?>', 0, true]);
			   delay = 100;
			}

			//		pushing a custom variable & event to Google Analytics to track this clicked link
			setTimeout(function(){

			   _gaq.push(['_setCustomVar', 1, 'trks.it', '<?php echo $redirect_lookup[0]->destination_url; ?>', 1]);
			   _gaq.push(['_trackEvent', 'trks.it', 'Clicked Link', '<?php echo $redirect_lookup[0]->destination_url; ?>'], 0, true);
			}, delay);

			(function() {
			   var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			   ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			   var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();

		 </script>

		 <style>
			#holdup {
				  color: #666;
				  font-family: "Helvetica Neue", "Helvetica", "Arial", sans-serif;
				  -webkit-animation: fadein 10s; /* Safari, Chrome and Opera > 12.1 */
				  -moz-animation: fadein 10s; /* Firefox < 16 */
				  -ms-animation: fadein 10s; /* Internet Explorer */
				  -o-animation: fadein 10s; /* Opera < 12.1 */
				  animation: fadein 10s;
			}
			/**
			* This business will not work in IE9 <
			**/

			@keyframes fadein {
				  0% { opacity: 0; }
				  7% { opacity: 0; }
				  100% { opacity: 1; }
			}

			/* Firefox < 16 */
			@-moz-keyframes fadein {
				  0% { opacity: 0; }
				  7% { opacity: 0; }
				  100% { opacity: 1; }

			}

			/* Safari, Chrome and Opera > 12.1 */
			@-webkit-keyframes fadein {
				  0% { opacity: 0; }
				  7% { opacity: 0; }
				  100% { opacity: 1; }

			}

			/* Internet Explorer */
			@-ms-keyframes fadein {
				  0% { opacity: 0; }
				  7% { opacity: 0; }
				  100% { opacity: 1; }

			}

			/* Opera < 12.1 */
			@-o-keyframes fadein {
				  0% { opacity: 0; }
				  7% { opacity: 0; }
				  100% { opacity: 1; }
			}
		 </style>

	  </head>
	  <body>
		 <h2 id='holdup'>Please wait, loading requested site</h2>

		 <script>

			<?php
			   foreach($script_array as $script){
				  if($script['error'] == 0) {
		 			 $script_out = stripslashes(htmlspecialchars_decode($script['script']));
					 $script_out = stripslashes($script_out);
					 echo 'try{ ';
					 echo $script_out;
					 echo ' } catch(err){ handle_error(err.message, ' . $script['id'] . '); }  ';
				  }
			   }
			?>

			<?php echo 'var ajaxurl = "wp-admin/admin-ajax.php"'; ?>


			function handle_error(error, id){
			   var dd = {
				  action: 'nopriv_handle_script',
				  error: error,
				  id: id
			   };

			   setTimeout(function(){ doAjax(ajaxurl, error, id); }, 0);
			}

			function doAjax(url, error, id) {
			   var xmlhttp = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
			   xmlhttp.onreadystatechange = function() {
				  if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
					 console.log(xmlhttp.responseText);
				  }
			   }
			   xmlhttp.open("POST", url, true);
			   xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			   xmlhttp.send("action=nopriv_handle_script&error=" + error + "&id=" + id);
			}



   </script>
   <?php echo $redirect; ?>
</body>
   </html>

