<?php

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
		
		$longURL = plugins_url( 'trksit_go.php?url_id=' . $shareURL_ID, dirname(__FILE__) );
		
		//shorten the URL
		$shortURL = $this->generateURL($longURL);

		$shortURL = "trks.it/" . $shortURL->msg;

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
	function generateURL($destination_url){
		
		//$encoded_url = base64_encode( $destination_url );
		//$hashed_url = hash_hmac('sha256', $encoded_url, get_option('trksit_private_api_key') );
		
		$url = 'https://api.trks.it/urls/' . get_option('trksit_public_api_key');
		$body = array(
			'url' => $destination_url
		);
		$headers = array(
			'Authorization' => 'Bearer ' . get_option('trksit_token')
		);
		
		$request = new WP_Http;
		$result = $request->request( $url , array( 'method' => 'POST','body'=>$body, 'headers' => $headers) );
		
		$output = json_decode($result['body']);
		
		return $output; 
		
	}
	
	
	//resetToken	
	function resetToken(){
		$url = 'https://api.trks.it/token?grant_type=client_credentials';
		$body = array(
			'client_id' => get_option('trksit_public_api_key'),
			'client_secret' => get_option('trksit_private_api_key')
		);
		
		$request = new WP_Http;
		$result = $request->request( $url , array( 'method' => 'POST','body'=>$body ) );

		$output = json_decode($result["body"]);
		update_option('trksit_token', $output->code->access_token);
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
