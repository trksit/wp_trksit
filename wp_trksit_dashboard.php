<div class="wrap" id="trksit-wrap">      
<?php

if($_GET['view'] == 'link-detail' && is_numeric($_GET['linkid'])){
  $details_nonce = $_REQUEST['_wpnonce'];
  if(!wp_verify_nonce($details_nonce, 'trksit-view-details')){
      die();                                           
  }else { 
      $link_id = $_GET['linkid'];
      $url_details = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "trksit_urls WHERE url_id = " . $link_id . "" );
      if(count($url_details === 1)){
      
		$trksit = new trksit();
	    $trksit->parseURL($url_details[0]->destination_url);	
		$og_data = unserialize ( $url_details[0]->og_data );
		$date = $url_details[0]->date_created;
		$trksit_url = $url_details[0]->trksit_url;
?>       
<h2 class="trksit-header top"><img src="<?php echo plugins_url( '/wp_trksit/img/trksit-icon-36x36.png' , dirname(__FILE__) ); ?>" class="trksit-header-icon" /><?php echo __( 'Trks.it - Details for Link ID #' . $url_details[0]->url_id, 'trksit_menu' ); ?></h2>

	<div id="trks_hits"></div>
	<?php 
	$trksit_slug = explode('/',$trksit_url);
	$trksit->getAnalytics($date,'',$trksit_slug[2]);
	?>
  <div class="trksit_tab_nav"></div>
  
	<form class="trksit-form"  method="post">	
	<div class="trksit_col left">
		
		<div class="trksit-section">

		    <h2 class="trksit-header"><?php _e('Sharing Settings'); ?></h2>

			<div class="control-group">
			  <label class="control-label"><?php _e('Trks.it URL:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('This is the shortened Trks.it URL you will use on various social and marketing platforms.'); ?>" data-original-title="<?php _e('Trks.it URL'); ?>"><i class="icon-question-sign"></i></a></label>
			  <div class="controls">
				  <a href="http://<?php echo $url_details[0]->trksit_url; ?>" target="_blank"><span class="uneditable-input input-span6" id="final-url"><?php echo $url_details[0]->trksit_url; ?></span></a>
				  <input type="hidden" name="trksit_url" value="<?php echo $url_details[0]->trksit_url;?>">
			  </div>
			</div>
		    
			<div class="control-group">
			  <label class="control-label"><?php _e('Destination URL:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('The user will end up at this url.'); ?>" data-original-title="<?php _e('Destination URL'); ?>"><i class="icon-question-sign"></i></a></label>
			  <div class="controls">
				  <a href="<?php echo $url_details[0]->destination_url; ?>" target="_blank"><span class="uneditable-input input-span6" id="final-url"><?php echo $url_details[0]->destination_url; ?></span></a>
				  <input type="hidden" name="destination_url" value="<?php echo $url_details[0]->destination_url;?>">
			  </div>
			</div>
			<div class="control-group">
			  <label class="control-label" for="title"><?php _e('Title:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('The title to be used when sharing the content. This is pulled from the pages Open Graph data if it already exists. If not, it defaults to the title of the page.'); ?>" data-original-title="<?php _e('Title'); ?>"><i class="icon-question-sign"></i></a></label>
			  <div class="controls">
			      <input name="meta_title" id="title" <?php echo ($og_data['og:title']) ? '' : 'class="listen"'; ?> type="text" maxlength="100" value="<?php echo $url_details[0]->meta_title; ?>">
			  </div>
			</div>
			<div class="control-group">
			  <label class="control-label" for="description"><?php _e('Description:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('The description to be used when sharing the content. This is pulled from the pages Open Graph data if it already exists. If not, it defaults to the meta description of the page.'); ?>" data-original-title="<?php _e('Description'); ?>"><i class="icon-question-sign"></i></a></label>
			  <div class="controls">
				  <textarea name="meta_description" id="description" <?php echo ($og_data['og:description']) ? '' : 'class="listen"'; ?> rows="5" maxlength="255"><?php echo $url_details[0]->meta_description; ?></textarea>
			  </div>
			</div>
		</div>
		
		<div class="trksit-section">

		  <h2 class="trksit-header"><?php _e('Analytics Tracking Data'); ?></h2>
	
			<div class="control-group">
			  <label class="control-label" for="campaign"><?php _e('Campaign Name:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique campaign value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Campaign Name'); ?>"><i class="icon-question-sign"></i></a></label>
			  <div class="controls">
			      <span class="uneditable-input"><?php echo $url_details[0]->campaign; ?></span>
			  </div>
			</div>
			
			<div class="control-group">
			  <label class="control-label" for="source"><?php _e('Source:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique source value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Campaign Source'); ?>"><i class="icon-question-sign"></i></a></label>
			  <div class="controls">
	            <span class="uneditable-input"><?php if($url_details[0]->source === ''){ echo 'Auto Detect'; }else{ echo $url_details[0]->source; } ?></span>
			  </div>
			</div>
			<div class="control-group">
			  <label class="control-label" for="medium"><?php _e('Medium:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique medium value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Campaign Medium'); ?>"><i class="icon-question-sign"></i></a></label>
			  <div class="controls">
			      <span class="uneditable-input"><?php echo $url_details[0]->medium; ?></span>
			  </div>
			</div>
		
		</div>
    
    <div class="trksit-section">
    <h2 class="trskit-header"><?php _e('Attached Scripts'); ?></h2><br />
    <?php                                          
      
      $active_scripts = $wpdb->get_results( "SELECT script_id FROM " . $wpdb->prefix . "trksit_scripts_to_urls WHERE url_id=" . $url_details[0]->url_id );
	    $active_scripts_array = array();
      foreach($active_scripts as $active_script){
        $active_scripts_array[] = $active_script->script_id;    
      }

      $scripts = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "trksit_scripts order by label" );
                     
	    if(count($scripts)){
        $count = 1;
        foreach($scripts as $script):
	
        $even_odd = ($count&1 ? 'odd' : 'even');
        
        $checked = (in_array($script->script_id, $active_scripts_array)) ? ' checked' :  '';
  
        echo '<label class="checkbox ' . $even_odd . '"><input type="checkbox" name="trksit_scripts[]" value="' . $script->script_id . '"' . $checked . ' />' . stripslashes($script->label) . '</label>'; 
        
        $count++;
        
        endforeach;
        
       }else{
        _e('You haven\'t setup any scripts yet! <a href="#">Click here to create one now!</a>');
       }

    ?>
        <div class="clearfix"></div>
    </div>
		
		<div class="clearfix"></div>

		<?php
		foreach($trksit->getOGMetaArray() as $property => $content){
			echo sprintf('<input type="hidden" name="%s" value="%s">', $property, $content);
		}
		?>
		
		<?php wp_nonce_field('trksit_update_url','trksit_update_url'); ?>
		<input type="submit" class="btn btn-success" data-loading-text="<?php _e('Please wait...'); ?>" value="<?php _e('Update URL'); ?>" />
		
	</div>
	
	<div class="trksit_col right">
	
	<h2><?php _e('Sharing Preview'); ?></h2>
		
    <div id="preview">
			<div class="image"><img src="<?php echo ($og_data['og:image']) ? $og_data['og:image'] : $url_details[0]->meta_image; ?>"></div>
			<div class="content">
				<label for="title"><div class="title"><?php echo ($og_data['og:title']) ? $og_data['og:title'] : $url_details[0]->meta_title; ?></div></label>
				<div class="url"><?php echo substr($url_details[0]->destination_url, 0, 38); if(strlen($url_details[0]->destination_url) > 40){echo "...";}?></div>
				<label for="description"><div class="description"><?php echo ($og_data['og:description']) ? $og_data['og:description'] : $url_details[0]->meta_description; ?></div></label>
			</div><div class="clear"></div>
		</div><!-- #preview -->
	
    <?php 
      if($og_data['og:image'] || $og_data['og:title'] || $og_data['og:description']){
        echo '<div class="alert alert-warning">
              We have detected the following open graph tags on this URL:<br /><br />'; 
        echo ($og_data['og:image']) ? '<strong>og:image</strong><br />' : ''; 
        echo ($og_data['og:title']) ? '<strong>og:title</strong><br />' : ''; 
        echo ($og_data['og:description']) ? '<strong>og:description</strong><br />' : '';       
        echo '<br />Trks.it will use the specified open graph tags above instead of the custom values defined on the left.</div>';  
      }
      if(!$og_data['og:image']){
    ?>
 
		<div class="control-group controls">	
			<select name="meta_image" id="preview-image-picker">
				<?php
				foreach($trksit->imgArray as $image){
					if (strpos($image,'www.googleadservices.com') === false) {
					echo sprintf('<option data-img-src="%s" value="%s">%s</option>', $image, $image, $image);
					}
				}
				?>
			</select>
		</div>
    <?php 
      }
    ?>
	
	<div class="clearfix"></div>
	
	</div>
	<div class="clearfix"></div>
	
	</form>


      
<?php
	  }
  } 
}
else if($_GET['page'] == 'trksit-dashboard'){ 
?>
<h2 class="trksit-header top"><img src="<?php echo plugins_url( '/wp_trksit/img/trksit-icon-36x36.png' , dirname(__FILE__) ); ?>" class="trksit-header-icon" /><?php echo __( 'Trks.it Dashboard', 'trksit_menu' ); ?></h2>
<?php  
  $timeline_points = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "trksit_hits WHERE url_id = 1" );

  //print_r($timeline_points);

  $points = '';
  $count = 1;
  $point_count = count($timeline_points);
  
  foreach($timeline_points as $timeline_point){
    
    $timestamp = (int)(strtotime($timeline_point->hit_date)*1000);
    $timeline_point->hit_date = (int)$timestamp;
    
    $timeline_converted[] = array(
      $timestamp => $timeline_point->hit_count
    );
    
    $points .= "[" . $timeline_point->hit_date . ", " . $timeline_point->hit_count . "]";
    
    if($count != $point_count){
      $points .= ',';
    }
    
    $count++;    
    
  }
  
  $graph_json = '[' . $points . ']';

?>
<script type="text/javascript">

jQuery(document).ready(function($) {
	
	var graphData = [{
	        data: <?php echo $graph_json; ?>,
	        color: '#21759b',
	        points: { radius: 3, fillColor: '#21759b' }
	    }];
	
	$.plot($('#graph-lines'), graphData, {
	    series: {
	        points: {
	            show: true,
	            radius: 5
	        },
	        lines: {
	            show: true
	        },
	        shadowSize: 0
	    },
	    grid: {
	        color: '#646464',
	        borderColor: 'transparent',
	        borderWidth: 20,
	        hoverable: true
	    },
	    xaxis: {
	        tickColor: 'transparent',
	        tickDecimals: 0,
	        mode: "time",  
	        timeformat: "%m/%d/%y",  
	        minTickSize: [10, "day"]
	    },
	    yaxis: {
	        tickSize: 10
	    }
	});

	function showTooltip(x, y, contents) {
		$('<div id="tooltip">' + contents + '<span class="nip"></span></div>').css({
			top: y - 50,
			left: x - 80
		}).appendTo('body').fadeIn();
	}

	var previousPoint = null;

	$('#graph-lines').bind('plothover', function (event, pos, item) {
		if (item) {
			if (previousPoint != item.dataIndex) {
				previousPoint = item.dataIndex;
				$('#tooltip').remove();
				var x = item.datapoint[0],
					y = item.datapoint[1];
				var hitdate = new Date(x);
					showTooltip(item.pageX, item.pageY, y + ' hits on ' + (hitdate.getUTCMonth() + 1) + "/" + hitdate.getUTCDate() + "/" + hitdate.getUTCFullYear());
			}
		} else {
			$('#tooltip').remove();
			previousPoint = null;
		}
	});
    
});
</script>

<div id="graph-wrapper"><div class="graph-container"><div id="graph-lines"></div></div></div>

<table class="wp-list-table widefat fixed">
  <thead>
  	<tr>
  		<th class="sortable desc" width="120"><a href="#"><span><?php _e('Created'); ?></span><span class="sorting-indicator"></span></a></th>
      <th class="sortable desc" width="60"><a href="#"><span><?php _e('Hits'); ?></span><span class="sorting-indicator"></span></a></th>
  		<th width="80"><?php _e('Trks.it URL'); ?></th>
      <th width="50"></th>
  		<th><?php _e('Destination URL'); ?></th>      
  		<th><?php _e('Campaign'); ?></th>
  		<th><?php _e('Source'); ?></th>
  		<th><?php _e('Medium'); ?></th>
  		<th width="80"></th>
  	</tr>
  </thead>
  <tbody>
  <?php 
  	
  	$table_data = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "trksit_urls ORDER BY date_created DESC, url_id DESC" );
  	
  	if(count($table_data)){
  	
    	foreach($table_data as $table_row){
    	
      	$datetime = strtotime($table_row->date_created);
      	$date_created = date('M j, Y', $datetime);
        $details_nonce = wp_create_nonce('trksit-view-details');
        $details_link = 'admin.php?page=trksit-dashboard&view=link-detail&linkid=' . $table_row->url_id . '&_wpnonce=' . $details_nonce; 
  ?>
      	<tr>
      	  <td><?php echo $date_created; ?></td>
          <td><?php echo mt_rand ( 0, 100 ); ?></td>
      	  <td>
            <a href="//<?php echo $table_row->trksit_url; ?>" target="_blank" class="trksit-link" id="trksit-link-<?php echo $table_row->url_id; ?>"><?php echo $table_row->trksit_url; ?></a> 
          </td>
          <td><span class="copy-btn-wrap"><a class="trksit-copy-btn" id="trks-copy-btn-<?php echo $table_row->url_id; ?>" data-trksit-link="<?php echo $table_row->trksit_url; ?>">Copy</a></span></td>
      	  <td class="truncate"><a href="<?php echo $table_row->destination_url; ?>" target="_blank"><?php echo $table_row->destination_url; ?></a></td>

          <td><?php echo stripslashes($table_row->campaign); ?></td>
      		<td><?php echo stripslashes($table_row->source); ?></td>
      		<td><?php echo stripslashes($table_row->medium); ?></td>
      		<td class="details"><a href="<?php echo $details_link; ?>">View Details</a></td>
      	</tr>
  <?php 
  	 }
  	}else{
  ?>
        <tr>
        	<td colspan="7"><?php _e('You haven\'t created a Trks.it URL yet...'); ?> <a href="/wp-admin/admin.php?page=trksit-generate"><?php _e('Create one now!'); ?></a></td>
        </tr>		
  <?php 
  	}
  ?>
  </tbody>
</table>
<?php 
}
?>
</div><!-- #trksit-wrap -->
