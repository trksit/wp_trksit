<?php
if(isset($_GET['view'])){
	//Added to action hook
	//header('Content-Encoding: none;'); // Use with ob_start() and flushing of buffers!!!
	ob_start();
	echo '<div id="loading-indicator" style="margin: 0px auto; width: 200px; text-align: center; padding-top: 200px;">';
	echo '<h2>Loading...</h2><br />';
	echo '<img src="' . plugins_url( '/wp_trksit/img/loading.gif' , dirname(__FILE__) ) . '" alt="Loading" /></div>';
	trksit_flush_buffers();
}
?>
<div class="wrap" id="trksit-wrap">
<?php
$trksit = new trksit();

if((isset($_GET['view']) && $_GET['view'] == 'link-detail') && is_numeric($_GET['linkid'])){
	$details_nonce = $_REQUEST['_wpnonce'];
	if(!wp_verify_nonce($details_nonce, 'trksit-view-details')){
		die();
	}else {
		if(isset($_POST["meta_title"]) && !empty($_POST) ){
			$trksit->wp_trksit_saveURL($wpdb, $_POST, true, $_GET['linkid']);
		}
		$link_id = $_GET['linkid'];
		$url_details = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "trksit_urls WHERE url_id = " . $link_id . "" );
		if(count($url_details === 1)){

			$trksit->wp_trksit_parseURL($url_details[0]->destination_url);
			$og_data = unserialize ( $url_details[0]->og_data );
			$date = $url_details[0]->date_created;
			$trksit_url = $url_details[0]->trksit_url;
			if( isset($_GET['trksit_start_date']) AND !empty($_GET['trksit_start_date']) AND isset($_GET['trksit_end_date']) AND !empty($_GET['trksit_end_date']) ){
				$start_date = date('Y-m-d',strtotime($_GET['trksit_start_date']));
				$end_date = date('Y-m-d',strtotime($_GET['trksit_end_date']));
			}else{
				$start_date = date('Y-m-d',strtotime("last week"));
				$end_date = date('Y-m-d', time());
			}
?>
			<h2 class="trksit-header top"><img src="<?php echo plugins_url( '/wp_trksit/img/trksit-icon-36x36.png' , dirname(__FILE__) ); ?>" class="trksit-header-icon" /><?php echo __( 'trks.it - Details for Link ID #' . $url_details[0]->url_id, 'trksit_menu' ); ?></h2>

			<div id="trks_hits"></div>
<?php
			$trksit->wp_trksit_getAnalytics($start_date,$end_date,$link_id);
?>
			<div class="trksit_tab_nav"></div>

			<form class="trksit-form"  method="post">
			   <div class="trksit_col left">

				  <div class="trksit-section">

					 <h2 class="trksit-header"><?php _e('Sharing Settings'); ?></h2>

					 <div class="control-group">
						<label class="control-label"><?php _e('trks.it URL:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('This is the shortened trks.it URL you will use on various social and marketing platforms.'); ?>" data-original-title="<?php _e('trks.it URL'); ?>"><i class="icon-question-sign"></i></a></label>
						<div class="controls">
						   <a href="<?php echo $url_details[0]->trksit_url; ?>" target="_blank"><span class="uneditable-input input-span6" id="final-url"><?php echo $url_details[0]->trksit_url; ?></span></a>
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
						   <textarea name="meta_description" id="description" <?php if(isset($og_data['og:description'])) echo ($og_data['og:description']) ? '' : 'class="listen"'; ?> rows="5" maxlength="255"><?php echo $url_details[0]->meta_description; ?></textarea>
						</div>
					 </div>
				  </div>

				  <div class="trksit-section">

					 <h2 class="trksit-header"><?php _e('Analytics Tracking Data'); ?></h2>

					 <div class="control-group">
						<label class="control-label" for="campaign"><?php _e('Campaign Name:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique campaign value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Campaign Name'); ?>"><i class="icon-question-sign"></i></a></label>
						<div class="controls">
						   <span class="uneditable-input"><?php echo $url_details[0]->campaign; ?></span>
						   <input type="hidden" name="campaign" value="<?php echo $url_details[0]->campaign;?>">
						</div>
					 </div>

					 <div class="control-group">
						<label class="control-label" for="source"><?php _e('Source:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique source value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Campaign Source'); ?>"><i class="icon-question-sign"></i></a></label>
						<div class="controls">
						   <span class="uneditable-input"><?php if($url_details[0]->source === ''){ echo 'Auto Detect'; }else{ echo $url_details[0]->source; } ?></span>
						   <input type="hidden" name="source" value="<?php echo $url_details[0]->source;?>">
						</div>
					 </div>
					 <div class="control-group">
						<label class="control-label" for="medium"><?php _e('Medium:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique medium value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Campaign Medium'); ?>"><i class="icon-question-sign"></i></a></label>
						<div class="controls">
						   <span class="uneditable-input"><?php echo $url_details[0]->medium; ?></span>
						   <input type="hidden" name="medium" value="<?php echo $url_details[0]->medium;?>">
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
				_e('<p>You haven\'t setup any scripts yet! <a href="#">Click here to create one now!</a></p>');
			}

?>
					 <div class="clearfix"></div>
				  </div>

				  <div class="clearfix"></div>

<?php
			foreach($trksit->wp_trksit_getOGMetaArray() as $property => $content){
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
			if(isset($og_data['og:image']) || isset($og_data['og:title']) || isset($og_data['og:description'])){
				echo '<div class="alert alert-warning">
					We have detected the following open graph tags on this URL:<br /><br />';
echo (isset($og_data['og:image'])) ? '<strong>og:image</strong><br />' : '';
echo (isset($og_data['og:title'])) ? '<strong>og:title</strong><br />' : '';
echo (isset($og_data['og:description'])) ? '<strong>og:description</strong><br />' : '';
echo '<br />trks.it will use the specified open graph tags above instead of the custom values defined on the left.</div>';
			}
			if(!isset($og_data['og:image'])){
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
	  <h2 class="trksit-header top"><img src="<?php echo plugins_url( '/wp_trksit/img/trksit-icon-36x36.png' , dirname(__FILE__) ); ?>" class="trksit-header-icon" /><?php echo __( 'trks.it Dashboard', 'trksit_menu' ); ?></h2>
<?php
	if( isset($_GET['trksit_start_date']) AND !empty($_GET['trksit_start_date']) AND isset($_GET['trksit_end_date']) AND !empty($_GET['trksit_end_date']) ){
		$start_date = date('Y-m-d',strtotime($_GET['trksit_start_date']));
		$end_date = date('Y-m-d',strtotime($_GET['trksit_end_date']));
	}else{
		$start_date = date('Y-m-d',strtotime("last week"));
		$end_date = date('Y-m-d', time());
	}
	//get the date of the last week so we can select all links created within the past week
	$last_week = date('Y-m-d',strtotime("last week"));
	$today = date('Y-m-d', time());

	$timeline_points = $wpdb->get_results( "SELECT date_created FROM " . $wpdb->prefix . "trksit_urls LIMIT 1" );
	if( count($timeline_points) === 1){
		$date = $timeline_points[0]->date_created;
?>
		 <form action="<?php echo trksit_current_page();?>" class="wp-core-ui" method="GET" id="trksit_date_selector">
			<input type="hidden" name="page" value="trksit-dashboard">
			<div class="trksit_date">
			   <label for="trksit_start_date"><?php _e('Start Date'); ?></label>
			   <input type="text" id="trksit_start_date" name="trksit_start_date">
			</div>
			<div class="trksit_date">
			   <label for="trksit_end_date"><?php _e('End Date'); ?></label>
			   <input type="text" id="trksit_end_date" name="trksit_end_date">
			</div>
			<input type="submit" value="Update" class="button button-primary button-large">
		 </form>
		 <br class="clear">
		 <div id="trks_hits"></div>

<?php
		$trksit->wp_trksit_getAnalytics($start_date,$end_date);
	}
	$trks_query = "SELECT *, "
		."(SELECT COALESCE(SUM(tkhits.hit_count),0) as hit_total "
		."FROM ".$wpdb->prefix."trksit_hits tkhits "
		."WHERE tku.url_id = tkhits.url_id "
		."AND tkhits.hit_date "
		."BETWEEN '$start_date' AND '$end_date') "
		."AS hit_total "
		."FROM ".$wpdb->prefix."trksit_urls tku "
		//."WHERE tku.url_id IN("
		//."SELECT DISTINCT tkhits.url_id "
		//."FROM ".$wpdb->prefix."trksit_hits tkhits "
		//."WHERE tku.url_id = tkhits.url_id "
		//."AND tkhits.hit_date "
		//."BETWEEN '$start_date' AND '$end_date') "
		."ORDER BY date_created DESC";

	$table_data = $wpdb->get_results($trks_query);

	if( count($table_data) >= 1 ):
?>

	  <div id="trks_dashboard_par" style="display: none;">
		 <table class="wp-list-table widefat fixed" id="trks_dashboard" style="width: 100%;display:table;white-space:nowrap;overflow: hidden;">
			<thead>
			   <tr>
				  <th class="sortable desc" width="100"><a href="#"><span><?php _e('Created'); ?></span><span class="sorting-indicator"></span></a></th>
				  <th class="sortable desc" width="75"><a href="#"><span><?php _e('Hits'); ?></span><span class="sorting-indicator"></span></a></th>
				  <th width="140"><?php _e('trks.it URL'); ?></th>
				  <th width="50"></th>
				  <th id="trks_it_destination" width="180"><?php _e('Destination URL'); ?></th>
				  <th><?php _e('Campaign'); ?></th>
				  <th><?php _e('Source'); ?></th>
				  <th><?php _e('Medium'); ?></th>
				  <th width="80"></th>
			   </tr>
			</thead>
			<tbody>
<?php
		foreach($table_data as $table_row):
			$datetime = strtotime($table_row->date_created);
	$date_created = date('M j, Y', $datetime);
	$details_nonce = wp_create_nonce('trksit-view-details');
	$details_link = 'admin.php?page=trksit-dashboard&view=link-detail&linkid=' . $table_row->url_id . '&_wpnonce=' . $details_nonce;
?>
			   <tr>
				  <td class="trks_it_date"><?php _e($date_created); ?></td>
				  <td class="trks_it_hits"><?php _e($table_row->hit_total); ?></td>
				  <td class="trks_it_url">
					 <!-- <a href="<?php echo $table_row->trksit_url; ?>?preview=true" target="_blank" class="trksit-link" id="trksit-link-<?php echo $table_row->url_id; ?>"><?php echo str_replace("https://", "", $table_row->trksit_url); ?></a> -->
					<?php echo str_replace("https://", "", $table_row->trksit_url); ?>
				  </td>
				  <td class="trks_it_copy">
					 <span class="copy-btn-wrap"><a class="trksit-copy-btn" id="trks-copy-btn-<?php echo $table_row->url_id; ?>" data-trksit-link="<?php echo $table_row->trksit_url; ?>"><?php _e('Copy');?></a></span>
				  </td>
				  <td class="truncate trks_it_destination">
					 <a href="<?php echo $table_row->destination_url; ?>"
						target="_blank"
						title="<?php echo $table_row->destination_url; ?>"
					 >
						<?php echo $table_row->destination_url; ?>
					 </a>
				  </td>
				  <td class="trks_it_campaign"><?php _e(stripslashes($table_row->campaign)); ?></td>
				  <td class="trks_it_source"><?php _e(stripslashes($table_row->source)); ?></td>
				  <td class="trks_it_medium"><?php _e(stripslashes($table_row->medium)); ?></td>
				  <td class="trks_it_details"><a href="<?php echo $details_link; ?>"><?php _e('View Details');?></a></td>
			   </tr>
			   <?php endforeach;?>
			</tbody>
		 </table>
<?php
	else:
	_e('<p>You haven\'t created a trks.it URL yet...</p>'); ?>
			<p><a href="/wp-admin/admin.php?page=trksit-generate" class="btn btn-success" style="text-decoration: none;"><?php _e('Create one now!'); ?></a></p>
			<?php endif;?>
		 </div>
<?php
}
?>
	  <style>
		 #loading-indicator {
			display: none;
		 }
	  </style>
   </div><!-- #trksit-wrap -->
