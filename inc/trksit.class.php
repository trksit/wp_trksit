<?php
/*attempt to disable gzip compression. WordPress' HTTP API causes known issues when getting back content with varied content-length.*/
//@http://wordpress.stackexchange.com/questions/10088/how-do-i-troubleshoot-responses-with-wp-http-api
@ini_set('zlib.output_compression', 'Off');
@ini_set('output_buffering', 'Off');
@ini_set('output_handler', '');

class trksit {
	public $imgArray = array();		//Images array
	public $metaArray = array();	//Meta Tags Array
	public $ogMetaArray = array();	//Open Graph meta array

	public $title;					//title tag
	public $description;			//meta description
	public $image;					//og:image ?
	public $URL;					//og:url ?
	public $trksit_errors;

	private $api; 					//trks.it api
	private $short_url_base;		//trks.it redirector url, always end with /

	function __construct(){
		$this->api = WP_TRKSIT_API_URL;
		$this->short_url_base = WP_TRKSIT_SHORT_URL;

		//exposing public functions to wp_ajax
		add_action( 'wp_ajax_nopriv_handle_script', array( $this, 'wp_trksit_handle_script' ) );
	}

	/*
	 * Private function to handle wp_errors
	 *
	 * @param error - error message to set in wp_error
	 *
	 */
	private function wp_trksit_handleError($error){
		if(!is_wp_error($this->trksit_errors)){
			$this->trksit_errors = new WP_Error( 'broke', __($error, 'trks.it'));
		} else {
			$this->trksit_errors->add( 'broke', __($error, 'trks.it'));
		}
	}

	/**
	 * wp_trksit_handle_script - Called via ajax when a script errors to set db flags
	 * This function runs on the try/catch of the output scripts on the go page
	 */
	function wp_trksit_handle_script(){
		$error = $_POST['error'];
		$id = $_POST['id'];
		global $wpdb;

		$script = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "trksit_scripts WHERE script_id = " . $id);
		$error = $wpdb->update($wpdb->prefix . "trksit_scripts", array('script_error' => 1), array('script_id' => $id), array('%d'));

		$email = get_option('admin_email');
		$site = str_replace("http://", "", get_site_url());
		$msg = "The script \"" . $script->label . "\" has thrown an execution error.  The script has been disabled in the database.  Please fix the script syntax and update in the trksit plugin settings.";
		$subject = "Script error on the " . $site . " website";
		$headers = 'From: <no-reply@'.$site.'>' . "\r\n";
		wp_mail($email, $subject, $msg, $headers);

		echo $email;
		exit;
	}

	/*
	 * wp_trksit_setMissingFlags - API call to set 404 found or purge_request
	 *
	 * @param shortURL - the short URL code
	 *
	 * @param revert = false - Set to true if a page 404d due to deletion then was found later
	 *
	 */
	function wp_trksit_setMissingFlags($shorturl, $revert = false){
		$flags = wp_remote_post(
			$this->api."/urls/missing", array(
				'user-agent'=>'trks.it WordPress '.get_bloginfo('version'),
				'timeout'=>10,
				'blocking'=>true,
				'headers'=>array(
					'Authorization' => 'Bearer ' . get_option('trksit_token'),
					'Content-Type' => 'application/x-www-form-urlencoded'
				),
				//POST parameters
				'body' => array('short_url' => $shorturl, 'revert' => $revert)
			)
		);

		return $flags;
	}
	/*
	 * API call to purge links, sets go_page to false and timestamps purge_request field.
	 *
	 * @param secret - API secret key
	 *
	 * @return success message or false
	 *
	 */
	function wp_trksit_api_uninstall($secret){
		$purge = wp_remote_post(
			$this->api."/urls/uninstall", array(
				'user-agent'=>'trks.it WordPress '.get_bloginfo('version'),
				'timeout'=>10,
				'blocking'=>true,
				'headers'=>array(
					'Authorization' => 'Bearer ' . get_option('trksit_token'),
					'Content-Type' => 'application/x-www-form-urlencoded'
				),
				//POST parameters
				'body' => array('secret' => $secret)
			)
		);

		if($purge){
			return $purge;
		} else {
			return false;
		}
	}


	/**
	 * wp_trksit_parseUrl($url) - Parse the URL and get information
	 * about the page
	 *
	 * variables received: title, meta desctipion, open graph fields, and all images.
	 *
	 * @param $url - The URL to be parsed
	 *
	 */
	function wp_trksit_parseURL($url) {

		//load WordPress HTTP to load url
		$url_paramaters = http_build_query(
			array(
				'destination_url'=>urlencode($url)
			)
		);

		//Add fb_noscript parameter to URL if URL is facebook.com
		$is_facebook = parse_url($url, PHP_URL_HOST);
		if( strpos($is_facebook,'facebook.com') !== false ){
			$facebook_url = parse_url($destination_url);
			$original_url = $destination_url;

			if( !empty($facebook_url['query']) )
				$url .= "&_fb_noscript=1";
			else
				$url .= "?_fb_noscript=1";
		}

		$og_html = $this->wp_trksit_scrapeURL($url);

		//Handle cURL errors
		if($og_html['error']['error_code']) {
			$this->wp_trksit_handleError($og_html['error']['error_message']);
		}

		//Send HTML to API for opengraph data
		$get_opengraph = wp_remote_post(
			$this->api."/parse/opengraph", array(
				'user-agent'=>'trks.it WordPress '.get_bloginfo('version'),
				'timeout'=>10,
				'blocking'=>true,
				'headers'=>array(
					'Authorization' => 'Bearer ' . get_option('trksit_token'),
					'Content-Type' => 'application/x-www-form-urlencoded'
				),
				//POST parameters
				'body' => array('html_encoded' => $og_html['body'], 'destination_url' => urlencode($url))
			)
		);

		if($get_opengraph['response']['code'] === 400){
			$this->wp_trksit_handleError(json_decode($get_opengraph['body'])->msg);
		}
		// Need error handling here when API times out - 140820
		if( $get_opengraph['response']['code'] === 500 || $get_opengraph['response']['code'] === 400 ){
			new WP_Error( 'broke', __( "Unable to get URL data", "trks.it" ) );
		}elseif( $get_opengraph['response']['code'] === 200 ){
			$opengraph = json_decode($get_opengraph['body'],true);
			//if the wp_http cannot get the json data without weird, encoded characters, this may be because wp_http added padding to the body
			if( !$opengraph ){
				$opengraph = json_decode($this->wp_trksit_removePadding($get_opengraph['body']),true);
			}
			$imageArray = $opengraph['open_graph']['og:image'];
			$opengraph_images = array();
			foreach( $imageArray as $image ){
				$opengraph_images[] = $image['og:image:url'];
			}
			$opengraph_data = array();
			// set the opengraph data
			foreach( $opengraph['open_graph'] as $key=>$new_opengraph){
				if( $key !=  'og:image'){
					$opengraph_data[$key] = $opengraph['open_graph'][$key];
				}
			}
			$this->ogMetaArray = $opengraph_data;
			$this->imgArray = $opengraph_images;
			if(isset($opengraph['open_graph']['og:title'])){
				$this->title = $opengraph['open_graph']['og:title'];
			} else {
				$this->title = '';
			}
			if(isset($opengraph['open_graph']['og:description'])){
				$this->description = $opengraph['open_graph']['og:description'];
			} else {
				$this->description = '';
			}
			$_SESSION['opengraph'] = $opengraph['open_graph'];
		}
	}	//END parseURL

	/**
	 * wp_trksit_scrapeURL($url)
	 * Scrapes the HTML of a page using wp_remote_get rather than the API
	 * to safeguard against exploits
	 *
	 * @param url - url to be scraped of HTML content
	 *
	 * @return array - ['body'] contains the HTML
	 *
	 */
	function wp_trksit_scrapeURL($url){

		$html = wp_remote_get(urldecode($url));

		$urldata = array();

		if(is_wp_error($html) || 200 != wp_remote_retrieve_response_code($html)){
			$urldata['error'] = array('error_code' => true, 'error_message' => $html->get_error_message());
		} else {
			$urldata['error'] = array('error_code' => false, 'error_message' => null);
			//$urldata['body'] = gzdeflate(wp_remote_retrieve_body($html), 9);
			$urldata['body'] = wp_remote_retrieve_body($html);
		}

		return $urldata;

	}


	/*
	 * Shorten the URL
	 *
	 * @param postarray - $_POST array
	 *
	 * @return short_url or false
	 *
	 */
	function wp_trksit_shortenURL($postArray){
		global $wpdb;

		//Save the data to the database and get back the ID
		$shareURL_ID = $this->wp_trksit_saveURL($wpdb, $postArray);

		//Set 1st or 3rd party link, preppend to source parameter.
		$domain_party = "3rd";
		$domains = maybe_unserialize(get_option('trksit_domains'));
		if(in_array($postArray['destination_url'], $domains)){
			$domain_party = "1st";
		}

		//Build the longURL with query string params

		$longURL = get_site_url() . '/index.php?trksitgo=1&url_id=' . $shareURL_ID . '&su=&utm_source='.$domain_party.' '.$postArray['source'].'&utm_medium='.$postArray['medium'].'&utm_campaign='.$postArray['campaign'].'&utm_content='.$postArray['content'].'&utm_term='.$postArray['term'];
		//shorten the URL
		$shortURL = $this->wp_trksit_generateURL($longURL,$postArray);
		if($shortURL){
			$shortURL = $this->short_url_base . $shortURL;

			//set the updateArray & whereArray for the shortened URL
			$updateArray = array('trksit_url' => $shortURL);
			$whereArray = array('url_id' => $shareURL_ID);

			//Including database class
			$wpdb->update(
				$wpdb->prefix . 'trksit_urls',
				$updateArray,
				$whereArray,
				array('%s'),
				array('%d')
			);

			return $shortURL;
		} else {
			$wpdb->delete( $wpdb->prefix . 'trksit_urls', array('url_id' => $shareURL_ID));
			$this->wp_trksit_handleError('Short URL Unavailable.');
			return false;
		}



	}	//END shortenURL

	/*
	 * Save custom javascript to database
	 *
	 * @param wpdb - wordpress database object
	 * @param mainArray - list of script IDs to add to this URL
	 * @param id - URL ID
	 *
	 */
	private function wp_trksit_saveScripts($wpdb, $mainArray, $id){
		$wpdb->delete($wpdb->prefix . 'trksit_scripts_to_urls', array('url_id' => $id));
		$script_count = 0;
		if(isset($mainArray['trksit_scripts'])){
			$script_count = count($mainArray['trksit_scripts']);
		}
		$scripts_array = array();

		for( $i = 1; $i <= $script_count; $i++ ){
			$scripts_array[] = array(
				'script_id' => $mainArray['trksit_scripts'][$i - 1],
				'url_id' => $id
			);
		}
		foreach ( $scripts_array as $script ){
			$wpdb->insert( $wpdb->prefix . 'trksit_scripts_to_urls', $script, array('%d', '%d') );
		}
	}

	/*
	 * Save URL to database
	 *
	 * @param wpdb - wordpress database object
	 * @param postarray - $_POST array
	 * @param update - boolean to send update or not
	 * @param updateid - id of URL if update
	 *
	 * @return ShortenedURL ID
	 *
	 */
	function wp_trksit_saveURL($wpdb, $postArray, $update = false, $updateid = null){

		//Setting up our 2 arrays, 1 from main data & other for Open Graph data
		$mainArray = $ogArray = array();

		foreach($postArray as $key => $value){

			//If the input name has "og:", then store it in the open graph array
			if(strstr($key, ":")){
				$ogArray[$key] = $value;

				//otherwise, store it in the main array
			} else {
				$mainArray[$key] = $value;
			}

		}
		$mainArray["og_data"] = serialize($ogArray);

		$fields = array(
			'destination_url' => $mainArray['destination_url'],
			'meta_title' => $mainArray['meta_title'],
			'meta_description' => $mainArray['meta_description'],
			'meta_image' => $mainArray['meta_image'],
			'og_data' => $mainArray['og_data'],
			'campaign' => $mainArray['campaign'],
			'source' => $mainArray['source'],
			'medium' => $mainArray['medium']
			//'date_created' => $mainArray['date_created']
		);
		$values = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );


		if($update){
			$wpdb->update( $wpdb->prefix . 'trksit_urls', $fields, array('url_id' => intval($updateid)), $values);
			$this->wp_trksit_saveScripts($wpdb, $mainArray, $updateid);
		} else {
			//insert main data into DB
			$fields['date_created'] = $mainArray['date_created'];
			array_push($values, '%s');
			$wpdb->insert( $wpdb->prefix . 'trksit_urls', $fields, $values );

			//Setting the URL ID to return to ShortenURL function
			$shortenedURLID = $wpdb->insert_id;

			if($wpdb->insert_id){
				$inserted_record = $wpdb->insert_id;
				$this->wp_trksit_saveScripts($wpdb, $mainArray, $inserted_record);
			}
			//return the ID of the URL insert
			return $shortenedURLID;
		}

	}//END saveURL

	/*
	 * Delete script
	 *
	 * @param wpdb - wordpress database object
	 * @param id - id of script to delete
	 *
	 * @return success or error messages
	 *
	 */
	function wp_trksit_deleteScript($wpdb, $id) {
		$wpdb->delete($wpdb->prefix . 'trksit_scripts', array('script_id' => intval($id)));
		$wpdb->delete($wpdb->prefix . 'trksit_scripts_to_urls', array('script_id' => intval($id)));
	}

	/*
	 * Get script details
	 *
	 * @param wpdb - wordpress database object
	 * @param id - script id
	 *
	 * @return label, script, platform, script_id
	 *
	 */
	function wp_trksit_scriptDetails($wpdb, $id){
		$query = 'SELECT label, script, platform, script_id FROM ' . $wpdb->prefix . 'trksit_scripts WHERE script_id = ' . intval($id);
		return $wpdb->get_results($query);
	}

	function wp_trksit_saveCustomScript($wpdb, $post, $update = false){
		$platform = $post['trksit_script_platform'];
		if($post['trksit_script_platform_other'] != ""){
			$platform = $post['trksit_script_platform_other'];
			if($opt = get_option('trksit_script_platforms')){
				$o = maybe_unserialize($opt);
				if(!in_array($post['trksit_script_platform_other'], $o)){
					$o[] = $post['trksit_script_platform_other'];
					$ser_o = serialize($o);
					update_option('trksit_script_platforms', $ser_o);
				}
			} else {
				$p = serialize(array($post['trksit_script_platform_other']));
				update_option('trksit_script_platforms', $p);
			}
		}
		$trksit_replace = array('http://', 'https://', '<script>', '</script>');
		$trksit_replacements = array('//', '//', '', '');
		$trksit_script_label = $post['trksit_script_label'];
		$trksit_script = htmlspecialchars(str_replace($trksit_replace, $trksit_replacements, $post['trksit_script']));
		$trksit_platform = $platform;
		$trksit_id = $post['script-id'];
		$trksit_confirmation = '<div class="alert alert-success" style="margin:30px 0px 0px 0px;">' . __('Script successfully added') . '</div>';
		$trksit_update = '<div class="alert alert-success" style="margin:30px 0px 0px 0px;">' . __('Script successfully updated') . '</div>';
		$trksit_conf_fail = '<div class="alert alert-danger" style="margin: 30px 0 0 0;">' . __('Script did not save please try again') . '</div>';

		$fields = array(
			'date_created' => date('Y-m-d'),
			'label' => $trksit_script_label,
			'script' => $trksit_script,
			'platform' => $trksit_platform
		);
		$values = array('%s','%s','%s','%s');

		if($update){
			$fields['script_error'] = 0;
			array_push($values, '%s');
			$upd = $wpdb->update($wpdb->prefix . 'trksit_scripts', $fields, array('script_id' => $trksit_id), $values);
			if(!$upd){
				$trksit_confirmation = $trksit_conf_fail;
			} else {
				$trksit_confirmation = $trksit_update;
			}
		} else {
			$wpdb->insert($wpdb->prefix . 'trksit_scripts', $fields, $values);
			if(!$wpdb->insert_id){
				$trksit_confirmation = $trks_conf_fail;
			}
		}

		return $trksit_confirmation;
	}

	/*
	 * API call to see if current user is active
	 *
	 * @return boolean
	 */
	function wp_trksit_user_is_active(){
		$url = $this->api.'/active';
		$headers = array(
			'Authorization' => 'Bearer ' . get_option('trksit_token'),
			'Content-Type' => 'aplication/x-www-form-urlencoded'
		);
		$request = new WP_Http;
		$result = $request->request( $url, array('method' => 'POST', 'body' => array(), 'headers' => $headers));
		if(is_wp_error($result)){
			set_transient('trksit_active_user', 'inactive', 60*60*24);
			return $result;
		} else {
			if(json_decode($result['body'])->error){
				$status_msg = json_decode($result['body'])->status_messages;
				set_transient('trksit_active_user', 'inactive', 60*60*24);
				set_transient('trksit_status_messages', serialize($status_msg), 60*60*24);
				return false;
			} else {
				set_transient('trksit_active_user', 'active', 60*60*24);
				return true;
			}
		}
	}

	/*
	 * API call to generate short URL
	 *
	 * @param long_url - the long URL to the go page
	 * @param data - OG and meta information
	 *
	 * @return error or URL
	 *
	 */
	function wp_trksit_generateURL($long_url,$data){

		$url = $this->api.'/urls';

		$body = array(
			'client_id' => get_option('trksit_public_api_key'),
			'url' => $long_url,
			'destination_url' => $data['destination_url'],
			'image'=> $data['meta_image'],
			'title' => $data['meta_title'],
			'description' => $data['meta_description']
		);
		//pass the og data to trks.it
		foreach($data as $key => $value){
			if( strpos($key,':') )
				$ogArray[$key] = $value;
		}
		$body["og_data"] = $ogArray;

		$headers = array(
			'Authorization' => 'Bearer ' . get_option('trksit_token'),
			'Content-Type' => 'application/x-www-form-urlencoded'
		);

		$request = new WP_Http;
		$result = $request->request(
			$url,
			array(
				'method' => 'POST',
				'body'=>$body,
				'headers' => $headers)
			);

		if(is_wp_error($result)){
			$this->wp_trksit_handleError($result->get_error_message());
			return false;
		}

		if($result['response']['code'] == 500){
			$this->wp_trksit_handleError(json_decode($result['body'])->msg);
			return false;
		}

		//sometimes the API returns a 404 when the og_data is sent, so resend the data to shorten URL with og data removed
		if( $result['response']['code'] != 201 ){
			unset($body["og_data"]);
			$headers = array(
				'Authorization' => 'Bearer ' . get_option('trksit_token'),
				'Content-Type' => 'application/x-www-form-urlencoded'
			);

			$request2 = new WP_Http;
			$result2 = $request->request( $url , array( 'method' => 'POST','body'=>$body, 'headers' => $headers) );
			$result = $result2;
		}
		if($result['response']['code'] === 400){
			$this->wp_trksit_handleError(json_decode($result['body'])->msg);
		}

		$output = json_decode($result['body']);
		//if the wp_http cannot get the json data without weird, encoded characters, this may be because wp_http added padding to the body
		if( !$output ){
			$output = json_decode($this->wp_trksit_removePadding($result['body']));
		}

		if($output->error){
			return $output->error;
		} else {
			return $output->msg;
		}

	}

	/*
	 * API call to reset OAuth2 token
	 * Also checks associated URL with siteurl to make sure they match
	 *
	 * @return success or error
	 *
	 */
	function wp_trksit_resetToken(){
		$url = $this->api.'/token?grant_type=creds';
		$body = array(
			'client_id' => get_option('trksit_public_api_key'),
			'client_secret' => get_option('trksit_private_api_key')
		);

		$request = new WP_Http;
		$result = $request->request( $url , array( 'method' => 'POST','body'=>$body,'headers'=>array('Content-Type'=>' application/x-www-form-urlencoded') ) );

		//check if wp_http has thrown an error message
		if( $result instanceof WP_Error ){
			//echo $result->get_error_message();
			//exit;
			return $result;
		}

		$urlsmatch = false;

		//Compare the siteurl to the associated URL saved in oauth_clients
		$site_url = get_option('siteurl');
		$associated_url = wp_remote_get(
			$this->api."/active/url/".get_option('trksit_public_api_key'), array(
				'user-agent'=>'trks.it WordPress '.get_bloginfo('version'),
				'timeout'=>10,
				'blocking'=>true,
				'headers'=>array(
					'Authorization' => 'Bearer ' . get_option('trksit_token'),
					'Content-Type' => 'application/x-www-form-urlencoded'
				)
			)
		);

		$au = json_decode($associated_url['body']);
		if($site_url == $au->msg){
			$urlsmatch = true;
		}


		$output = json_decode($result['body']);
		//if the wp_http cannot get the json data without weird, encoded characters, this may be because wp_http added padding to the body
		if( !$output ){
			$output = json_decode($this->wp_trksit_removePadding($result['body']));
		}

		if(isset($output->code->access_token) && $urlsmatch){
			update_option('trksit_token', $output->code->access_token);
			update_option('trksit_token_expires', $output->code->expires);
			set_transient('trksit_active_user', 'active', 60*60*24);
			delete_transient('trksit_url_status_msg');
		} else {
			delete_option('trksit_token');
			delete_option('trksit_token_expires');
			set_transient('trksit_active_user', 'inactive', 60*60*24);
			set_transient('trksit_url_status_msg', 'URL associated with this public key ('.$au->msg.') does not match this site\'s URL', 60*60*24*365);
		}

		return $output;
	}

	/*
	 * Check the validity of issued token
	 *
	 * @return error or true
	 *
	 */
	function wp_trksit_checkToken(){
		$url = $this->api.'/token?grant_type=authorization';
		$body = array(
			'client_id' => get_option('trksit_public_api_key'),
			'client_secret' => get_option('trksit_private_api_key'),
			'Authorization' => get_option('trksit_token')
		);

		$request = new WP_Http;
		$result = $request->request( $url , array( 'method' => 'POST','body'=>$body ) );

		//check if wp_http has thrown an error message
		if( $result instanceof WP_Error ){
			echo $result->get_error_message();
			exit;
		}
		$output = json_decode($result["body"]);

		//if the wp_http cannot get the json data without weird, encoded characters, this may be because wp_http added padding to the body
		if( !$output ){
			$output = json_decode($this->wp_trksit_removePadding($result['body']));
		}

		if($output->error){
			return $output->error;
		} else {
			return true;
		}

	}

	/*
	 * Get URL hits from database.  Outputs javascript to chart data.
	 *
	 * @param start_date
	 * @param end_date
	 * @param short_url_id
	 *
	 */
	function wp_trksit_getAnalytics($start_date = null,$end_date = null,$short_url_id = null){
		global $wpdb;

		//select the hit counts based on start and end dates
		if( is_null($short_url_id) AND is_null($start_date) AND is_null($end_date) ){
			$hits = $wpdb->get_results('SELECT *,(SELECT COALESCE(SUM(hit_count),0) as hit_count FROM '.$wpdb->prefix.'trksit_hits WHERE wp_trksit_hits.hit_date = v.hit_date) AS hit_count from (SELECT adddate("1970-01-01",t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) hit_date from (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t0,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t4) v WHERE v.hit_date BETWEEN "'.$start_date.'" AND "'.$end_date.'" ORDER BY v.hit_date',OBJECT);
		}elseif( is_null($short_url_id) AND !is_null($start_date) AND !is_null($end_date) ){
			$hits = $wpdb->get_results('SELECT *,(SELECT COALESCE(SUM(hit_count),0) as hit_count FROM '.$wpdb->prefix.'trksit_hits WHERE wp_trksit_hits.hit_date = v.hit_date) AS hit_count from (SELECT adddate("1970-01-01",t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) hit_date from (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t0,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t4) v WHERE v.hit_date BETWEEN "'.$start_date.'" AND "'.$end_date.'" ORDER BY v.hit_date',OBJECT);
		}elseif( !is_null($short_url_id) AND is_null($start_date) AND is_null($end_date) ){
			$hits = $wpdb->get_results('SELECT *,(SELECT COALESCE(SUM(hit_count),0) as hit_count FROM '.$wpdb->prefix.'trksit_hits WHERE wp_trksit_hits.hit_date = v.hit_date AND url_id = '.$short_url_id.') AS hit_count from (SELECT adddate("1970-01-01",t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) hit_date from (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t0,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t4) v WHERE v.hit_date BETWEEN "'.$start_date.'" AND "'.$end_date.'" ORDER BY v.hit_date',OBJECT);
		}elseif( !is_null($short_url_id) AND !is_null($start_date) AND !is_null($end_date) ){
			$hits = $wpdb->get_results('SELECT *,(SELECT COALESCE(SUM(hit_count),0) as hit_count FROM '.$wpdb->prefix.'trksit_hits WHERE wp_trksit_hits.hit_date = v.hit_date AND url_id = '.$short_url_id.') AS hit_count from (SELECT adddate("1970-01-01",t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) hit_date from (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t0,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t4) v WHERE v.hit_date BETWEEN "'.$start_date.'" AND "'.$end_date.'" ORDER BY v.hit_date',OBJECT);
		}

		$data = null;
		foreach( $hits as $hit ){
			if( is_null($data) ){
				$data = "{ day: '".$hit->hit_date."', hits: ".$hit->hit_count."},";
			}else{
				$data .= "{ day: '".$hit->hit_date."', hits: ".$hit->hit_count."},";
			}
		}
		echo "<script>
			//JS for line graph
			var graph = new Morris.Line({
			element: 'trks_hits',
				data: [
					$data
				],
				xkey: 'day',
				ykeys: ['hits'],
				labels: ['Hits'],
				xLabels:'day',
				resize: true,
				pointSize: 5,
				smooth: false,
				lineWidth: 2,
				gridTextSize: 11,
				gridTextFamily: 'Open Sans',
				pointFillColors: ['#76bd1d'],
				hideHover: true,
				lineColors: ['#555555'],
				pointStrokeColors: '#000000',
				dateFormat: function(x){
					var date = new Date(x)
						return ('0' + (date.getMonth() + 1).toString()).substr(-2) + '/' + ('0' + date.getDate().toString()).substr(-2)  + '/' + (date.getFullYear().toString()).substr(2);
	},
		xLabelFormat:function(x){
			var date = new Date(x)
				return ('0' + (date.getMonth() + 1).toString()).substr(-2) + '/' + ('0' + date.getDate().toString()).substr(-2)  + '/' + (date.getFullYear().toString()).substr(2);
	}
	});
	</script>";
	}

	//remove the padding by wp_http that causes padding to be added to a JSON request
	function wp_trksit_removePadding($data){
		//if the wp_http cannot get the json data without weird, encoded characters, this may be because wp_http added padding to the body
		//remove lines
		$data = preg_replace("@[\\r|\\n|\\t]+@", "", $data);
		//remove padding @http://forrst.com/posts/PHP_Convert_JSONP_to_JSON-mcv
		$data = preg_replace('/.+?({.+}).+/','$1',$data);

		return $data;
	}

	//GET functions
	public function wp_trksit_getTitle(){
		return $this->title;
	}
	public function wp_trksit_getDescription(){
		return $this->description;
	}
	public function wp_trksit_getURL(){
		return $this->URL;
	}
	public function wp_trksit_getImage(){
		return $this->image;
	}
	public function wp_trksit_getOGMetaArray(){
		return $this->ogMetaArray;
	}
	public function wp_trksit_getErrors(){
		return $this->trksit_errors;
	}

}	//END Class trksit
