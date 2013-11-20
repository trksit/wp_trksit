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
	
	
	//Parse the given URL & get...
	//title, meta desctipion, open graph fields, and all images.
	function parseURL($url) {
		
		//dom loading
		$dom = new domDocument;
		@$dom->loadHTML(file_get_contents($url));
		$dom->preserveWhiteSpace = false;
		
		//Getting OG meta data
		$xpath = new DOMXPath($dom);
		$query = '//*/meta[starts-with(@property, \'og:\')]';
		$metas = $xpath->query($query);
		$rmetas = array();
		foreach ($metas as $meta) {
		    $property = $meta->getAttribute('property');
		    $content = $meta->getAttribute('content');
		    $rmetas[$property] = $content;
		}
		$this->ogMetaArray = $rmetas;
				
		//if og:title exists, don't get meta title, instead set $this->title to og:title
		if(isset($rmetas['og:title'])){
			$this->title = $rmetas['og:title'];
		} else {
			//Getting title tag
			$title = $dom->getElementsByTagName('title');
			$this->title = $title->item(0)->nodeValue;						
		}

		//if og:description exists, don't get meta description, instead set $this->description to og:description
		if(isset($rmetas['og:description'])) {
			$this->description = $rmetas['og:description'];
		} else {
			
			//Getting description
			$metas = $dom->getElementsByTagName('meta');
			
			for ($i = 0; $i < $metas->length; $i++) {
			    $meta = $metas->item($i);
			    if($meta->getAttribute('name') == 'description')
			        $this->description = $meta->getAttribute('content');
			}
									
		} 

		
		//Getting Images array     
		$images = $dom->getElementsByTagName('img');
		$imageArray = array();
		
		foreach($images as $img) {
		    	//if the img src does NOT include an http
		    	//it's a relative path and we need to build the path
		    	if(!strstr($img->getAttribute('src'), '//')){
			    	$imgHost = parse_url($url);
			    	$imgSrc = $imgHost[scheme] . '://' . $imgHost[host] . '/' . $img->getAttribute('src');
		    	} else {
			    	$imgSrc = $img->getAttribute('src');
		    	}
		    	
		    	array_push($imageArray, $imgSrc);
		}
		$this->imgArray = array_unique($imageArray);
		//print_r($this->imgArray);

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

	} 	//END saveURL
	
	
	//Generating the shortened URL from trks.it
	function generateURL($long_url,$data){
		
		$url = 'https://api.trks.it/urls';
		
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
			//If the input name has "og:", then store it in the open graph array
			if( strstr($key, "og:") )
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
			//remove lines
			$result['body'] = preg_replace("@[\\r|\\n|\\t]+@", "", $result['body']);
			//remove padding @http://forrst.com/posts/PHP_Convert_JSONP_to_JSON-mcv
			$result['body'] = preg_replace('/.+?({.+}).+/','$1',$result['body']);
			$output = json_decode($result['body']);
		}

		if($output->error){
			return $output->error;
		} else {
			return $output->msg;
		}
		
	}
	
	
	//resetToken	
	function resetToken(){
		$url = 'https://api.trks.it/token?grant_type=creds';
		$body = array(
			'client_id' => get_option('trksit_public_api_key'),
			'client_secret' => get_option('trksit_private_api_key')
		);
		
		$request = new WP_Http;
		$result = $request->request( $url , array( 'method' => 'POST','body'=>$body,'headers'=>array('Content-Type'=>' application/x-www-form-urlencoded') ) );
		
		//rebuild the response body to remove any extra characters before the json string
		/*$response = explode('{',$result['body'],2);
		$response = "{".$response[1];
		$response = explode('}',$response,2);
		$response = $response[0]."}}";*/
		$output = json_decode($result['body']);
		
		//if the wp_http cannot get the json data without weird, encoded characters, this may be because wp_http added padding to the body
		if( !$output ){
			//remove lines
			$result['body'] = preg_replace("@[\\r|\\n|\\t]+@", "", $result['body']);
			//remove padding @http://forrst.com/posts/PHP_Convert_JSONP_to_JSON-mcv
			$result['body'] = preg_replace('/.+?({.+}).+/','$1',$result['body']);
			$output = json_decode($result['body']);
		}
		
		update_option('trksit_token', $output->code->access_token);
		update_option('trksit_token_expires', $output->code->expires);
		return $output;
	}
	
	//checkToken	
	function checkToken(){
		$url = 'https://api.trks.it/token?grant_type=authorization';
		$body = array(
			'client_id' => get_option('trksit_public_api_key'),
			'client_secret' => get_option('trksit_private_api_key'),
			'Authorization' => get_option('trksit_token')
		);
		
		$request = new WP_Http;
		$result = $request->request( $url , array( 'method' => 'POST','body'=>$body ) );

		$output = json_decode($result["body"]);
		
		if($output->error){
			return $output->error;
		} else {
			return true;
		}

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