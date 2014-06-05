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
	
	private $api; 					//trks.it api
	
	function __construct(){
		$this->api = "http://api.trksit.dev";
	}
	//Parse the given URL & get...
	//title, meta desctipion, open graph fields, and all images.
	function parseURL($url) {
		
		//load WordPress HTTP to load url
		$url_paramaters = http_build_query(
			array(
					'destination_url'=>urlencode($url)
				)
			);
		$get_opengraph = wp_remote_get( $this->api."/parse/urls?".$url_paramaters, array( 
				'user-agent'=>'trks.it WordPress '.get_bloginfo('version'),
				'timeout'=>10,
				'blocking'=>true,
				'headers'=>array(
						'Authorization' => 'Bearer ' . get_option('trksit_token'),
						'Content-Type' => 'application/x-www-form-urlencoded'
					)
				) 
			);

		if( $get_opengraph['response']['code'] === 500 OR $get_opengraph['reponse']['code'] === 400 ){
			new WP_Error( 'broke', __( "Unable to get URL data", "trks.it" ) );
		}elseif( $get_opengraph['response']['code'] === 200 ){
			$opengraph = json_decode($get_opengraph['body'],true);
			//if the wp_http cannot get the json data without weird, encoded characters, this may be because wp_http added padding to the body
			if( !$opengraph ){
				$opengraph = json_decode($this->removePadding($get_opengraph['body']),true);
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
			$this->title = $opengraph['open_graph']['og:title'];
			$this->description = $opengraph['open_graph']['og:description'];
		}
	}	//END parseURL
	
	
	//Shorten the URL (STEP 2)
	function shortenURL($postArray){
		global $wpdb;	
	
		//Save the data to the database and get back the ID
		$shareURL_ID = $this->saveURL($wpdb, $postArray);
		
		//Build the longURL with query string params
		$longURL = plugins_url( 'trksit_go.php?utm_source='.$postArray['source'].'&utm_medium='.$postArray['medium'].'&utm_campaign='.$postArray['campaign'].'&url_id=' . $shareURL_ID, dirname(__FILE__) );
		
		//shorten the URL
		$shortURL = $this->generateURL($longURL,$postArray);
		$shortURL = "https://trks.it/" . $shortURL;
		
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
		
		
	}	//END shortenURL
	
	
		
	//Save URL to database
	function saveURL($wpdb, $postArray){
		
		//Setting up our 2 arrays, 1 from main data & other for Open Graph data
		$mainArray = $ogArray = array();
		
		foreach($postArray as $key => $value){
			
			//If the input name has "og:", then store it in the open graph array
			if(strstr($key, "og:")){
				$ogArray[$key] = $value;
				
			//otherwise, store it in the main array
			} else {
				$mainArray[$key] = $value;
			}
			
		}
		//print_r($mainArray);
		$mainArray["og_data"] = serialize($ogArray);
    
		//insert main data into DB
		$wpdb->insert( 
          	$wpdb->prefix . 'trksit_urls', 
          	array( 
          	  'destination_url' => $mainArray['destination_url'], 
          	  'meta_title' => $mainArray['meta_title'],
              'meta_description' => $mainArray['meta_description'],
              'meta_image' => $mainArray['meta_image'],
              'og_data' => $mainArray['og_data'],
              'campaign' => $mainArray['campaign'],
              'source' => $mainArray['source'],
              'medium' => $mainArray['medium'],
              'date_created' => $mainArray['date_created']    
          	),  
          	array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
          ); 
	
		//Setting the URL ID to return to ShortenURL function
		$shortenedURLID = $wpdb->insert_id;
			
	    if($wpdb->insert_id){
	    
	      $inserted_record = $wpdb->insert_id;
	      
	      $script_count = count($mainArray['trksit_scripts']);
	      $scripts_array = array();
	      
	      for( $i = 1; $i <= $script_count; $i++ ){
	      
	        $scripts_array[] = array(
	          'script_id' => $mainArray['trksit_scripts'][$i - 1],
	          'url_id' => $inserted_record
	        );
	        
	      }
	      
	      foreach ( $scripts_array as $script ){
	        $wpdb->insert( $wpdb->prefix . 'trksit_scripts_to_urls', $script, array('%d', '%d') );
	      }
	    
	    }
    
		//return the ID of the URL insert
		return 	$shortenedURLID;

	}//END saveURL
	
	
	//Generating the shortened URL from trks.it
	function generateURL($long_url,$data){
		
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
			$ogArray[$key] = $value;
		}
		
		$body["og_data"] = $ogArray;
		
		$headers = array(
			'Authorization' => 'Bearer ' . get_option('trksit_token'),
			'Content-Type' => 'application/x-www-form-urlencoded'
		);
		
		$request = new WP_Http;
		$result = $request->request( $url , array( 'method' => 'POST','body'=>$body, 'headers' => $headers) );
		
		//sometimes the API returns a 404 when the og_data is sent, so resend the data to shorten URL without the og data
		if( $result['reponse']['code'] != 201 ){
			unset($body['og_data']);
			
			$headers = array(
				'Authorization' => 'Bearer ' . get_option('trksit_token'),
				'Content-Type' => 'application/x-www-form-urlencoded'
			);
			
			$request2 = new WP_Http;
			$result2 = $request->request( $url , array( 'method' => 'POST','body'=>$body, 'headers' => $headers) );
			$result = $result2;
		}
		
		$output = json_decode($result['body']);
		//if the wp_http cannot get the json data without weird, encoded characters, this may be because wp_http added padding to the body
		if( !$output ){
			$output = json_decode($this->removePadding($result['body']));
		}

		if($output->error){
			return $output->error;
		} else {
			return $output->msg;
		}
		
	}
	
	
	//resetToken	
	function resetToken(){
		$url = $this->api.'/token?grant_type=creds';
		$body = array(
			'client_id' => get_option('trksit_public_api_key'),
			'client_secret' => get_option('trksit_private_api_key')
		);
		
		$request = new WP_Http;
		$result = $request->request( $url , array( 'method' => 'POST','body'=>$body,'headers'=>array('Content-Type'=>' application/x-www-form-urlencoded') ) );
		
		//check if wp_http has thrown an error message
		if( $result instanceof WP_Error ){
			echo $result->get_error_message();
			exit;
		}
		
		$output = json_decode($result['body']);
		//if the wp_http cannot get the json data without weird, encoded characters, this may be because wp_http added padding to the body
		if( !$output ){
			$output = json_decode($this->removePadding($result['body']));
		}
		
		update_option('trksit_token', $output->code->access_token);
		update_option('trksit_token_expires', $output->code->expires);
		
		return $output;
	}
	
	//checkToken	
	function checkToken(){
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
			$output = json_decode($this->removePadding($result['body']));
		}
		
		if($output->error){
			return $output->error;
		} else {
			return true;
		}

	}
	
	//get the hits for the links
	function getAnalytics($start_date = null,$end_date = null,$short_url_id = null){
		global $wpdb;

		//select the hit counts based on start and end dates
		if( is_null($short_url) AND is_null($start_date) AND is_null($end_date) ){
			$hits = $wpdb->get_results('SELECT *,(SELECT COALESCE(SUM(hit_count),0) as hit_count FROM '.$wpdb->prefix.'trksit_hits WHERE wp_trksit_hits.hit_date = v.hit_date) AS hit_count from (SELECT adddate("1970-01-01",t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) hit_date from (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t0,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t4) v WHERE v.hit_date BETWEEN "'.$start_date.'" AND "'.$end_date.'" ORDER BY v.hit_date',OBJECT);
		}elseif( is_null($short_url) AND !is_null($start_date) AND !is_null($end_date) ){
			$hits = $wpdb->get_results('SELECT *,(SELECT COALESCE(SUM(hit_count),0) as hit_count FROM '.$wpdb->prefix.'trksit_hits WHERE wp_trksit_hits.hit_date = v.hit_date) AS hit_count from (SELECT adddate("1970-01-01",t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) hit_date from (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t0,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t4) v WHERE v.hit_date BETWEEN "'.$start_date.'" AND "'.$end_date.'" ORDER BY v.hit_date',OBJECT);
		}elseif( !is_null($short_url) AND is_null($start_date) AND is_null($end_date) ){
			$hits = $wpdb->get_results('SELECT *,(SELECT COALESCE(SUM(hit_count),0) as hit_count FROM '.$wpdb->prefix.'trksit_hits WHERE wp_trksit_hits.hit_date = v.hit_date AND url_id = '.$short_url_id.') AS hit_count from (SELECT adddate("1970-01-01",t4.i*10000 + t3.i*1000 + t2.i*100 + t1.i*10 + t0.i) hit_date from (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t0,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,(SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t4) v WHERE v.hit_date BETWEEN "'.$start_date.'" AND "'.$end_date.'" ORDER BY v.hit_date',OBJECT);
		}elseif( !is_null($short_url) AND !is_null($start_date) AND !is_null($end_date) ){
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
	function removePadding($data){
		//if the wp_http cannot get the json data without weird, encoded characters, this may be because wp_http added padding to the body
		//remove lines
		$data = preg_replace("@[\\r|\\n|\\t]+@", "", $data);
		//remove padding @http://forrst.com/posts/PHP_Convert_JSONP_to_JSON-mcv
		$data = preg_replace('/.+?({.+}).+/','$1',$data);
		
		return $data;
	}

	//GET functions
	public function getTitle(){
		return $this->title;
	}
	public function getDescription(){
		return $this->description;
	}
	public function getURL(){
		return $this->URL;
	}
	public function getImage(){
		return $this->image;
	}
	public function getOGMetaArray(){
		return $this->ogMetaArray;
	}

}	//END Class trksit