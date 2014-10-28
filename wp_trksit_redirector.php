<?php
if(isset($_GET['ping']) && $_GET['ping'] == 'true'){
	echo json_encode(array('alive' => true));
} else {
	if(!isset($_COOKIE['trksit_new'])){
		setcookie("trks_new", "new_user", time()+400000);
	}


	// Setting cache-control headers
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',false);
	header("Pragma: no-cache");

	//Forward to 404 page if id is not set.
	if(!isset($_GET['url_id'])){
		$four04 = '/index.php?error404=true';
		echo '<script type="text/javascript">setTimeout(function(){window.location.href = "'.$four04.'"},0);</script>';
		echo '<meta http-equiv="refresh" content="2; url='.$four04.'">';
	}


	//Getting options
	$analytics_id = get_option('trksit_analytics_id');
	$redirect_delay = get_option('trksit_redirect_delay');
	$redirect = '';


	//THIS SHOULD BE GOTTEN FROM THE DB
	$api_signature = 'testing12345678';
	//KILL NEXT LINE TO SECURE
	$_GET['api_signature'] = 'testing12345678';
	$testing = false;
	$scripterror = false;
	$script_id = null;

	if(isset($_GET['testing'])){
		if($_GET['testing'] == 'test'){
			$testing = true;
		}
		if($_GET['testing'] == 'scripterror'){
			if(isset($_GET['scriptid'])){
				$script_id = intval($_GET['scriptid']);
			}
			if(!isset($_GET['script_error_nonce']) || !wp_verify_nonce($_GET['script_error_nonce'], 'script_error_' . $script_id)){
				die("Access denied");
			}
			$scripterror = true;
		}
	}

	// Check request method and ensure all parameters are present in return from API.
	if( $_SERVER['REQUEST_METHOD'] == 'GET' && ( isset( $_GET['url_id'] ) && isset( $_GET['api_signature'] ) ) ){

		// Check API Signature (Needs work!)
		if( $_GET['api_signature'] == $api_signature ){

			global $wpdb;
			if(!$scripterror){

				$incoming_url_id = $_GET['url_id'];

				$redirect_lookup = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'trksit_urls WHERE url_id=' . $incoming_url_id );


				// If destination URL exsists in wpdb result. Output redirect script.
				if($redirect_lookup && $redirect_lookup[0]->destination_url){
					//get the short URL code
					$surl = $_GET['su'];
					$domain_party = "third";
					$dest = $redirect_lookup[0]->destination_url;
					$domains = maybe_unserialize(get_option('trksit_domains'));
					if(in_array($dest, $domains)){
						$domain_party = "first";
					}

					if(isset($_COOKIE['trks_party'])){
						$party = $_COOKIE['trks_party'];
						if($party == 'first' && $domain_party == 'third'){
							setcookie('trks_party', 'both', time()+400000);
						}
					} else {
						setcookie('trks_party', $domain_party, time()+400000);
						$party = $domain_party;
					}

					if( !isset($_COOKIE['original_source']) ){
						original_cookies(false, true);
					} else {
						converting_cookies(false, true);
					}

					//Check for transient that is set when a link is not in the database
					//If found, API call to revert the flag set at trks.it
					if(get_transient('trksit_404_' . $surl)){
						$trksit = new trksit();
						$flags_set = $trksit->wp_trksit_setMissingFlags($surl, true);
						delete_transient( 'trksit_404_' . $surl );
					}

					//Set redirect URLs
					$js_redir = '<script type="text/javascript">setTimeout(function(){window.location.href = "'
						. $redirect_lookup[0]->destination_url . '"},' . $redirect_delay . ');</script>';
					$meta_redir = '<meta http-equiv="refresh" content="2; url='.$redirect_lookup[0]->destination_url.'">';

					$url_id = $redirect_lookup[0]->url_id;
					$today = date('Y-m-d');

					$redirect = $js_redir . $meta_redir;

					//Getting all the scripts for this URL
					if(!$scripterror){
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
					}

					//Do not increment hit counter if testing link or debugging javascript
					if(!$testing && !$scripterror){
						$hit_result = $wpdb->get_results(
							"SELECT * FROM " . $wpdb->prefix . "trksit_hits WHERE url_id="
							. $url_id . " AND hit_date='" . $today . "'"
						);
						$hit_result_count = count($hit_result);

						if($hit_result_count === 1){

							$update_results = $wpdb->query(
								$wpdb->prepare(
									"UPDATE " . $wpdb->prefix ."trksit_hits
									SET hit_count = hit_count + 1
									WHERE url_id = %d
									AND hit_date = %s",
$url_id, $today
								)
							);

if($update_results){
	$redirect = $js_redir . $meta_redir;
}

						} else if($hit_result_count === 0){
							$wpdb->insert(
								$wpdb->prefix . 'trksit_hits',
								array(
									'hit_count' => 1,
									'url_id' => $url_id,
									'hit_date' => $today
								),
								array( '%d', '%d','%s' )
							);


						} else { die; }
					} else {
						//testing redirects, script_error should not
						if(!$testing) {
							$redirect = 'no';
						}
					}

				} else {
					$trksit = new trksit();
					$surl = $_GET['su'];

					//set transient to let us know that a 404 has ocurred with this short URL
					set_transient('trksit_404_'.$surl, '404', 60*60*24*30);

					//flag the URL in the API
					$flags_set = $trksit->wp_trksit_setMissingFlags($surl);

					//If we have a redirect URL returned use it, otherwise 404
					if($redir_url = json_decode($flags_set['body'])->url){
						$four04 = $redir_url;
					} else {
						$four04 = '/index.php?error404=true';
					}
					echo '<script type="text/javascript">setTimeout(function(){window.location.href = "'.$four04.'"},0);</script>';
					echo '<meta http-equiv="refresh" content="2; url='.$four04.'">';
				}
			} else {
				$redirect = 'no';
			}
		}else{ die; }

	}else{ die; }

	if((isset($redirect_lookup) && $redirect_lookup) || $scripterror){
?>
<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#" xmlns:og="http://opengraphprotocol.org/schema/" xmlns:fb="http://www.facebook.com/2008/fbml">
   <head>
	  <?php if($redirect == ''): ?>
	  <meta http-equiv="refresh" content="0; url=<?php echo $redirect_lookup[0]->destination_url; ?>">
	  <?php endif; ?>

	  <title><?php if(!$scripterror) { echo $redirect_lookup[0]->meta_title; } else { echo "Script Error"; }?></title>
	  <?php if(!$scripterror): ?>
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
			echo '<meta property="' . $key . '" content="' . $value . '" />';
		}

		//if the open graph image is NOT set, we need to set it
		if(!isset($ogArray['og:image'])){
			echo '<meta property="og:image" content="' . $redirect_lookup[0]->meta_image . '" />';
		}

		//if the open graph URL is NOT set, we need to set it
		if(!isset($ogArray['og:url'])){
			echo '<meta property="og:url" content="' . $redirect_lookup[0]->destination_url . '" />';
		}

		//skip analytics if testing
		if(!$testing):
			if(!is_null($analytics_id) && $analytics_id != ''):
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
	  //always set the GA account
	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', '<?php echo $analytics_id; ?>']);
<?php
				if($domain_party == 'third'){
					echo "_gaq.push(['_setSessionCookieTimeout', 0])";
				}
?>

	  //REQUIRED FOR LOCAL DEVELOPMENT
	  _gaq.push(['_setDomainName', 'none']);
	  _gaq.push(['_setAllowLinker', true]);

	  //if they haven't been here.. push an event to set their GA cookies
	  var delay = 0;
	  if(!getCookie("trks_new")){
		  // Fire an event to set it
		  _gaq.push(['_trackEvent', 'trks.it', 'New Visitor', '<?php echo $redirect_lookup[0]->destination_url; ?>', 0, true]);
		  delay = 100;
	  }

	  //		pushing a custom variable & event to Google Analytics to track this clicked link
	  setTimeout(function(){

		  _gaq.push(['_setCustomVar', 1, 'trks.it', '<?php echo $redirect_lookup[0]->destination_url; ?>', 2]);
		  _gaq.push(['_setCustomVar', 2, 'trks.it', '<?php echo $party; ?>', 2]);
		  _gaq.push(['_trackEvent', 'trks.it', 'Clicked <?php echo $domain_party; ?> Party Link', '<?php echo $_GET['su'] . " - " . $redirect_lookup[0]->destination_url; ?>'], 0, true);

		  _gaq.push(['_trackPageview', '<?php echo $_GET['su']; ?> : <?php echo $redirect_lookup[0]->destination_url; ?>']);
	  }, delay);

	  (function() {
		  var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		  ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		  var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();

	  </script>
		 <?php endif; endif; endif; ?>

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
			code {
				  color: #FF0000;
			}
		 </style>

	  </head>
	  <body>
		 <?php if(!$scripterror): ?>
		 <h2 id='holdup'>Please wait, loading requested site</h2>
		 <?php else: ?>
		 <h2>Script Error</h2>
		 <code id='script_error_message'></code>
		 <p>Information on using the console <a href='http://codex.wordpress.org/Using_Your_Browser_to_Diagnose_JavaScript_Errors' target='_blank'>here</a>
		 <?php endif; ?>

				<script>

<?php
				//testing outputs user defined scripts
				if(!$scripterror){
					foreach($script_array as $script){
						if($script['error'] == 0) {
							$script_out = stripslashes(htmlspecialchars_decode($script['script']));
							$script_out = stripslashes($script_out);
							echo 'try{ ';
							echo $script_out;
							echo ' } catch(err){ ';
							echo 'handle_error(err.message, ' . $script['id'] . ');';
							echo '}  ';
						}
					}
				} else {
					//scrit execute/debug only outputs the script being debugged
					$error_script = $wpdb->get_results("SELECT script FROM "
						. $wpdb->prefix . "trksit_scripts WHERE script_id=" . $script_id . " LIMIT 1");
					if($error_script){
						$script_out = stripslashes(htmlspecialchars_decode($error_script[0]->script));
						$script_out = stripslashes($script_out);
						echo 'try { ';
						echo $script_out;
						echo ' } catch(err) {';
						echo 'console.log("ERROR: " + err.message);';
						echo 'console.log(err);';
						echo 'document.getElementById("script_error_message").innerHTML=err + " - Open console for more information";';
						echo '}';
					}
				}
?>

		<?php echo 'var ajaxurl = "wp-admin/admin-ajax.php"'; ?>

		//In catch block, ajax call to set error flags
		//if a script produces an error
		//emails site admin
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
				//console.log(xmlhttp.responseText);
	}
	}
	xmlhttp.open("POST", url, true);
	xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp.send("action=nopriv_handle_script&error=" + error + "&id=" + id);
	}
	</script>
			<?php if(!$scripterror){ echo $redirect; } ?>
		 </body>
	  </html>
   <?php } }?>

