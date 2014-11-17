<?php
if(!empty($_POST) || (isset($_GET['purge-data']) && $_GET['purge-data'] == true)){
	ob_start();
	echo '<div id="loading-indicator" style="margin: 0px auto; width: 200px; text-align: center; padding-top: 200px;">';
	echo '<h2>Saving Settings...</h2><br />';
	echo '<img src="' . plugins_url( '/wp_trksit/img/loading.gif' , dirname(__FILE__) ) . '" alt="Loading" /></div>';
	trksit_flush_buffers();
}
if(isset($_GET['purge-data']) && $_GET['purge-data'] == 'true'){
	if(isset($_GET['trksit_purge_nonce']) && wp_verify_nonce($_GET['trksit_purge_nonce'], 'purge_my_data')){
		$trksit = new trksit();
		$purged = $trksit->wp_trksit_api_uninstall(get_option('trksit_private_api_key'));
		$response = json_decode($purged['body']);
		if($response->error){
			echo '<div class="alert alert-danger">API Error, data not purged. '.$response->msg.'</div>';
		} else {
			$wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'trksit_hits');
			$wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'trksit_scripts');
			$wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'trksit_scripts_to_urls');
			$wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'trksit_urls');
			echo '<div class="alert alert-success">All data removed from WordPress and trks.it databases.</div>';
		}
	} else {
		die('<h1>Unauthorized Operation</h1>');
	}
}
if($_GET['page'] == 'trksit-settings'){

	//see trksit_update_settings_redirect() in main plugin file
	//Options are saved in action hook, then page is refreshed to update menu
	if(!isset($_POST['trksit_page']) && empty($_POST)) {
		$trksit_analytics_id = get_option('trksit_analytics_id');
		$trksit_public_api_key = get_option('trksit_public_api_key');
		$trksit_private_api_key = get_option('trksit_private_api_key');
		$trksit_jquery = get_option('trksit_jquery');
		$trksit_redirect_delay = get_option('trksit_redirect_delay');
	} else {
		if(isset($_GET['tab']) && ($_GET['tab'] != 'scripts' && $_GET['tab'] != 'sources' && $_GET['tab'] != 'domains') && $_GET['tab'] != 'medium'){
			$trksit_analytics_id = $_POST['trksit_analytics_id'];
			$trksit_public_api_key = $_POST['trksit_public_api_key'];
			$trksit_private_api_key = $_POST['trksit_private_api_key'];
			$trksit_jquery = $_POST['trksit_jquery'];
			$trksit_redirect_delay = $_POST['trksit_redirect_delay'];
		}
	}

	if((isset($_POST['trksit_page']) && $_POST['trksit_page'] == 'add_script') && ( !empty($_POST) && check_admin_referer('trksit_save_settings','trksit_add_script') )) {
		$trksit = new trksit();
		if($_POST['script-id'] == ''){
			$trksit_confirmation = $trksit->wp_trksit_saveCustomScript($wpdb, $_POST, false);
		} else {
			$trksit_confirmation = $trksit->wp_trksit_saveCustomScript($wpdb, $_POST, true);
		}

	}
?>

<div class="wrap" id="trksit-wrap">

   <h2 class="trksit-header top"><img src="<?php echo plugins_url( '/wp_trksit/img/trksit-icon-36x36.png' , dirname(__FILE__) ); ?>" class="trksit-header-icon" /><?php echo __( 'Trks.it Settings', 'trksit_menu' ); ?></h2>
   <div class="trksit_tab_nav">
	  <ul>
		 <li <?php if((isset($_GET['tab']) && $_GET['tab'] == 'general') || empty($_GET['tab'])): ?>class="active"<?php endif; ?>><a href="/wp-admin/admin.php?page=trksit-settings&tab=general"><?php _e('General Settings'); ?></a></li>
		 <li <?php if(isset($_GET['tab']) && $_GET['tab'] == 'scripts'): ?>class="active"<?php endif; ?>><a href="/wp-admin/admin.php?page=trksit-settings&tab=scripts"><?php _e('Remarketing & Custom Scripts'); ?></a></li>
		 <li <?php if(isset($_GET['tab']) && $_GET['tab'] == 'sources'): ?>class="active"<?php endif; ?>><a href="/wp-admin/admin.php?page=trksit-settings&tab=sources"><?php _e('Sources'); ?></a></li>
		 <li <?php if(isset($_GET['tab']) && $_GET['tab'] == 'medium'): ?>class="active"<?php endif; ?>><a href="/wp-admin/admin.php?page=trksit-settings&tab=medium"><?php _e('Medium'); ?></a></li>
		 <li <?php if(isset($_GET['tab']) && $_GET['tab'] == 'domains'): ?>class="active"<?php endif; ?>><a href="/wp-admin/admin.php?page=trksit-settings&tab=domains"><?php _e('Domains'); ?></a></li>
		 <li style="float:right;"><a href="<?php echo WP_TRKSIT_MANAGE_URL; ?>" target="_blank">Manage Account</a></li>
	  </ul>
   </div>

   <?php if((isset($_GET['tab']) && $_GET['tab'] == 'general') || empty($_GET['tab'])){ ?>

   <form name="trksit_settings_form" id="trksit_settings_form" class="trksit-form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" style="float:left; display:block;">

	  <div class="trksit_col left">

		 <div class="trksit-section">

			<h2 class="trksit-header"><?php _e("API Settings"); ?></h2>
			<?php if(get_transient('trksit_active_user') && get_transient('trksit_active_user') == 'inactive'): ?>
			<div class="alert alert-danger">
			<p>Plugin not activated.  Please <a href="<?php echo WP_TRKSIT_MANAGE_URL; ?>" target="_blank">register here</a> then enter valid API keys</p>
<?php
	if($status = get_transient('trksit_status_messages')){
		echo '<h3>Recent Status Messages</h3>';
		foreach(maybe_unserialize($status) as $s){
			echo '<p>'.$s->status_msg . ' on '. date('M d, Y - g:ia', strtotime($s->date_created)) . '</p>';
		}
		if(get_transient('trksit_url_status_msg')){
			echo '<p>'.get_transient('trksit_url_status_msg') . '</p>';
		}
	}
?>
			</div>
			<?php endif; ?>

			<input type="hidden" name="trksit_page" value="settings" />
			<?php wp_nonce_field('trksit_save_settings','trksit_general_settings'); ?>

			<div class="control-group">
			   <label for="trksit_public_api_key" class="control-label"><?php _e("Trks.it Public API Key:"); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e("Enter your public API key, this was emailed to you."); ?>" data-original-title="<?php _e("Public API Key"); ?>"><i class="icon-question-sign"></i></a></label>
			   <div class="controls">
				  <input name="trksit_public_api_key" type="text" id="trksit_public_api_key" value="<?php echo $trksit_public_api_key; ?>" required />
			   </div>
			</div>
			<div class="control-group">
			   <label for="trksit_private_api_key" class="control-label"><?php _e("Trks.it Private API Key:"); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e("Enter your private API key, this was emailed to you."); ?>" data-original-title="<?php _e("Private API Key"); ?>"><i class="icon-question-sign"></i></a></label>
			   <div class="controls">
				  <input name="trksit_private_api_key" type="text" id="trksit_private_api_key" value="<?php echo $trksit_private_api_key; ?>" required />
			   </div>
			</div>

		 </div>

		 <div class="trksit-section">

			<h2 class="trksit-header"><?php _e("Google Analytics"); ?></h2>


			<div class="control-group">

			   <label for="trksit_analytics_id" class="control-label"><?php _e("Google Analytics Profile ID:"); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e("Enter your Google Analytics UA-XXXXXXX-X ID. This will be where the data is recorded by Trks.it."); ?>" data-original-title="<?php _e("Google Analytics Profile ID"); ?>"><i class="icon-question-sign"></i></a></label>
			   <div class="controls">
				  <input name="trksit_analytics_id" type="text" class="medium-text" placeholder="UA-XXXXXXX-X" id="trksit_analytics_id" value="<?php echo $trksit_analytics_id; ?>" />
			   </div>
			</div>

		 </div>

		 <div class="trksit-section">
			<h2 class="trksit-header"><?php _e('Other Settings'); ?></h2>

			<div class="control-group">
			   <label for="trksit_jquery" class="control-label"><?php _e("Include jQuery?:"); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e("Turn on to allow jQuery script to be run on the redirect script."); ?>" data-original-title="<?php _e("jQuery"); ?>"><i class="icon-question-sign"></i></a></label>
			   <div class="btn-group" data-toggle="buttons-radio" id="trksit_jquery_radio">
				  <button type="button" class="btn <?php if(!$trksit_jquery) echo 'active'; ?>" value="0"><?php _e('No'); ?></button>
				  <button type="button" class="btn <?php if($trksit_jquery) echo 'active'; ?>" value="1"><?php _e('Yes'); ?></button>
			   </div>
			   <input type="hidden" name="trksit_jquery" id="trksit_jquery" value="<?php echo $trksit_jquery; ?>" />
			</div>

			<div class="control-group">
			   <label for="trksit_redirect_delay" class="control-label"><?php _e("Redirect Delay:"); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e("Number of milliseconds to delay the redirect script. Default is 500."); ?>" data-original-title="<?php _e("Redirect Delay"); ?>"><i class="icon-question-sign"></i></a></label>
			   <input type="text" name="trksit_redirect_delay" class="trksit-input-small" value="<?php echo $trksit_redirect_delay; ?>" placeholder="ig: 500" />
			</div>

			<div class="control-group">
			   <label for="trksit_redirect_delay" class="control-label"><?php _e("Default Dashboard View:"); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e("The way the dashboard displays hits"); ?>" data-original-title="<?php _e("Default Dashboard View"); ?>"><i class="icon-question-sign"></i></a></label>
			   <select name="trksit_default_view" id="">
				  <option value="">Number of hits</option>
				  <option value="">Date created</option>
			   </select>
			</div>

		 </div>

		<input type="submit" name="Submit" class="btn btn-success" value="<?php _e('Update Options', 'trksit_menu' ) ?>" id="trksit_settings_update" />
		<div style='margin-top: 40px;'>
			<div class='alert alert-danger' role='alert'>This can not be undone, proceed with caution</div>
			<a
				href='<?php echo wp_nonce_url(admin_url('admin.php?page=trksit-settings&purge-data=true'), 'purge_my_data', 'trksit_purge_nonce'); ?>'
				onclick="return confirm('This will delete all URLs from WordPress and the trks.it API. Continue?');"
				class='btn btn-danger'
				style='text-decoration:none;'
			>Purge all data</a>
		</div>

	  </div>
	  <div class="trksit_col right">
	  </div>
   </form>

   <?php } ?>

   <?php if(isset($_GET['tab']) && $_GET['tab'] == 'scripts'){ ?>

<?php
	$s_label = '';
	$s_platform = '';
	$s_script = '';
	$s_sid = '';
	$form_url = remove_query_arg( array('act','id','edit_nonce','delete_nonce'), str_replace( '%7E', '~', $_SERVER['REQUEST_URI']));
	$trksit = new trksit();
	if(isset($_GET['edit_nonce']) && $_GET['act'] == 'edit' && wp_verify_nonce($_GET['edit_nonce'], 'edit_script')){
		$script_details = $trksit->wp_trksit_scriptDetails($wpdb, $_GET['id']);
		$s_label = $script_details[0]->label;
		$s_platform = $script_details[0]->platform;
		$s_script = stripslashes(htmlspecialchars_decode($script_details[0]->script));
		$s_sid = $script_details[0]->script_id;

		echo "<script>jQuery(window).load(function(){ jQuery('#add-script-window').modal('show'); });</script>";
	}

	if(isset($_GET['delete_nonce']) && $_GET['act'] == 'delete' && wp_verify_nonce($_GET['delete_nonce'], 'delete_script')){
		$trksit->wp_trksit_deleteScript($wpdb, $_GET['id']);
	}
?>

   <div class="trksit_col_full">

	  <h2 class="trksit-header"><?php _e("Remarketing & Custom Scripts"); ?></h2>
	  <p><?php _e("Here you can define remarketing lists & custom scripts to be run when a trks.it link is clicked. You can then assign your links with one or more scripts defined below."); ?></p>

	  <table class="wp-list-table widefat fixed">
		 <thead>
			<tr>
			   <th width="120"><?php _e("Date Created"); ?></th>
			   <th><?php _e("Label"); ?></th>
			   <th><?php _e("Platform"); ?></th>
			   <th><?php _e("URL's Attached"); ?></th>
			   <th width="100"><?php _e("Edit / Delete"); ?></th>
			</tr>
		 </thead>

		 <tbody>

<?php
	$footnote = '';
	$table_data = $wpdb->get_results(
		"SELECT * FROM " . $wpdb->prefix . "trksit_scripts ORDER BY date_created DESC, script_id DESC" );


	if(count($table_data)){

		foreach($table_data as $table_row){
			$q = "SELECT url_id FROM " . $wpdb->prefix . "trksit_scripts_to_urls WHERE script_id = " . $table_row->script_id;
			$times_used = $wpdb->get_results($q);
			$used = count($times_used);
			$datetime = strtotime($table_row->date_created);
			$date_created = date('F j, Y', $datetime);
			$edit_url = wp_nonce_url(admin_url('admin.php?page=trksit-settings&tab=scripts&act=edit&id=' . $table_row->script_id), 'edit_script', 'edit_nonce');
			$delete_url = wp_nonce_url(admin_url('admin.php?page=trksit-settings&tab=scripts&act=delete&id=' . $table_row->script_id), 'delete_script', 'delete_nonce');
?>
					 <tr <?php if($table_row->script_error) { echo "class='error-script'"; } ?>>
						<td><?php echo $date_created; ?></td>
						<td>
<?php
			echo stripslashes($table_row->label);
			if($table_row->script_error) {
				$url = '/index.php?trksitgo=1&url_id=scripterror&testing=scripterror&scriptid=' . $table_row->script_id;
				$url = wp_nonce_url($url, 'script_error_' . $table_row->script_id, 'script_error_nonce');
				echo " * &nbsp; <a href='".$url."' class='script_debug' target='_blank'>[execute]</a>";
				$footnote = '<p style="color: #a94442; float: left; padding-top: 20px;">'
					. '<span style="float: left;">*</span>'
					. '<span style="float: left; padding-left: 10px;">'
					. 'Scripts in red indicate an error has occured in its execution<br />'
					. 'Click [execute] with console open to see error.</span></p>';
			}
?>
						</td>
						<td><?php echo $table_row->platform; ?></td>
						<td><?php echo $used; ?></td>
						<td>
						   <a href="<?php echo $edit_url; ?>">
							  Edit
						   </a> |
						   <a href="<?php echo $delete_url; ?>" onclick="return confirm('Are you sure? This can not be undone.');">Delete</a>
						</td>
					 </tr>
<?php
		}
	}else{
?>
				  <tr>
					 <td colspan="5">
						<?php _e('You haven\'t created a custom script yet, '); ?><a href="#" data-target="#add-script-window" role="button" data-toggle="modal"><?php _e('Add a script now!'); ?></a>
					 </td>
				  </tr>
<?php
	}
?>
			</tbody>

			<tfoot>
			</tfoot>
		 </table>
		 <?php echo $footnote; ?>
		 <button data-target="#add-script-window" role="button" id="add-script" class="btn btn-success" data-toggle="modal"><?php _e("+ Add New Script"); ?></button>

	  </div>

	  <div id="add-script-window" class="modal fade hide" tabindex="-1" role="dialog" aria-labelledby="add-script-window" aria-hidden="true" style="display:none;">
		 <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="myModalLabel"><?php _e("Add a New Custom Script"); ?></h3>
		 </div>
		 <form name="trksit_add_script_form" id="trksit_add_script_form" class="trksit-form" method="post" action="<?php echo $form_url; ?>" style="margin-bottom:0px;">
			<div class="modal-body">

			   <div class="control-group">
				  <label for="trksit_script_label" class="control-label"><?php _e("Script Label:"); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e("Enter a label that will allow you to easily identify this script."); ?>" data-original-title="<?php _e("Script Label"); ?>"><i class="icon-question-sign"></i></a></label>
				  <div class="controls">
					 <input name="trksit_script_label" type="text" id="trksit_script_label" value="<?php echo $s_label; ?>" />
				  </div>
			   </div>

			   <div class="control-group">
				  <label for="trksit_script_platform" class="control-label"><?php _e("Platform"); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e("What is this script for? Google? Bing? Facebook? etc..."); ?>" data-original-title="<?php _e("Platform"); ?>"><i class="icon-question-sign"></i></a></label>
				  <div class="controls">
					 <select name="trksit_script_platform" id="trksit_script_platform" required="required">
						<option value="">--Please choose a platform--</option>
						<option value="google" <?php if ($s_platform == 'google') { echo 'selected="selected"'; } ?>>Google Remarketing</option>
						<option value="bing" <?php if ($s_platform == 'bing') { echo 'selected="selected"'; } ?>>Bing</option>
						<option value="facebook" <?php if ($s_platform == 'facebook') { echo 'selected="selected"'; } ?>>Facebook</option>
						<option value="custom" <?php if ($s_platform == 'custom') { echo 'selected="selected"'; } ?>>Custom</option>
<?php
	$custom_opts = get_option('trksit_script_platforms');
	$co = maybe_unserialize($custom_opts);
	foreach($co as $c){
		echo '<option value="'.$c.'"';
		if($s_platform == $c){ echo ' selected="selected" ';
		}
		echo '>'.$c.'</option>';
	}
?>
						<option value="other" id="other">Other</option>
					 </select>
					<input type="text" name="trksit_script_platform_other" id="trksit_script_platform_other" value="" placeholder="Add platform..." style="display: none;" />
				  </div>
			   </div>

			   <div class="control-group">
				  <label for="trksit_script" class="control-label"><?php _e("Custom Script"); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e("Use this field to write your custom script. Make sure everything works!"); ?>" data-original-title="<?php _e("Custom Script"); ?>"><i class="icon-question-sign"></i></a></label>
				  <div class="controls">
					 <textarea name="trksit_script" id="trksit_script"><?php echo $s_script; ?></textarea>
				  </div>
			   </div>
			   <input type="hidden" name="trksit_page" value="add_script" />
			   <?php wp_nonce_field('trksit_save_settings','trksit_add_script'); ?>
			   <input type='hidden' name='script-id' id='script-id' value='<?php echo $s_sid; ?>' />
			</div>
			<div class="modal-footer">
			   <button class="btn btn-danger" id="script_cancel" data-dismiss="modal" data-url="<?php echo $form_url; ?>" aria-hidden="true"><?php _e("Close"); ?></button>
			   <input type="submit" name="Submit" class="btn btn-success" value="<?php _e("Save Script"); ?>" />
			</div>
		 </form>
	  </div>

	  <?php } ?>

   </div>
   <?php } ?>
   <?php if(isset($_GET['tab']) && $_GET['tab'] == 'sources'){ ?>
<?php
	if(isset($_POST['source_submit'])){
		$t_sources = maybe_unserialize(get_option('trksit_sources'));
		array_push($t_sources, $_POST['source']);
		update_option('trksit_sources', serialize($t_sources));
	}
?>
   <div class="trksit_col_full">
	   <h2 class="trksit-header">Sources</h2>
	   <p>Here you can add sources to the drop down available when creating a new link</p>
	   <table class="wp-list-table widefat fixed">
		   <thead>
			   <tr>
				   <th><?php _e("Source"); ?></th>
				   <th width="100"><?php _e("Delete"); ?></th>
			   </tr>
		   </thead>

		   <tbody>
<?php
	$sources = maybe_unserialize(get_option('trksit_sources'));
	$count_of_sources = count($sources);
	for($i = 0; $i < $count_of_sources; $i++){
		$source_url = wp_nonce_url(str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) . '&deletesource=' . $i, 'delete_source', 'ds_nonce');
?>
			<tr>
			<td><?php echo $sources[$i]; ?></td>
			<td>
				<?php if($count_of_sources !== 1): ?>
				<a href="<?php echo $source_url; ?>">Delete</a>
				<?php else: ?>
				<p>You must have at least one (1) source active.</p>
				<p>Please add another source before attempting to delete this one.</p>
				<?php endif; ?>
			</td>
			</tr>
<?php } ?>
		   </tbody>
	   </table>
		<div style="padding-top: 20px;">
		<form action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" class="trksit-form" method="post">
			<input type="text" name="source" id="source" class="medium-text" value="" autofocus="autofocus" />
			<input type="submit" name="source_submit" id="source_submit" class="btn btn-success" value="Add Source" />
		</form>
</div>
   <!-- </div> -->
	<?php } ?>
<?php
		if(isset($_GET['tab']) && $_GET['tab'] == 'medium'){

			if(isset($_POST['medium_submit'])){
				$t_medium = maybe_unserialize(get_option('trksit_medium'));
				array_push($t_medium, $_POST['medium']);
				update_option('trksit_medium', serialize($t_medium));
			}
?>

 <div class="trksit_col_full">
	   <h2 class="trksit-header">Sources</h2>
	   <p>Here you can add sources to the drop down available when creating a new link</p>
	   <table class="wp-list-table widefat fixed">
		   <thead>
			   <tr>
				   <th><?php _e("Source"); ?></th>
				   <th width="100"><?php _e("Delete"); ?></th>
			   </tr>
		   </thead>

		   <tbody>

<?php
			$medium = maybe_unserialize(get_option('trksit_medium'));
			$count_of_medium = count($medium);
			for($i = 0; $i < $count_of_medium; $i++){
				$medium_url = wp_nonce_url(str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) . '&deletemedium=' . $i, 'delete_medium', 'dm_nonce');
?>
			<tr>
			<td><?php echo $medium[$i]; ?></td>
			<td>
				<?php if($count_of_medium !== 1): ?>
				<a href="<?php echo $medium_url; ?>">Delete</a>
				<?php else: ?>
				<p>You must have at least one (1) source active.</p>
				<p>Please add another source before attempting to delete this one.</p>
				<?php endif; ?>
			</td>
			</tr>

		<?php } ?>
			</tbody>
				</table>
					<div style="padding-top: 20px;">
					<form action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" class="trksit-form" method="post">
						<input type="text" name="medium" id="medium" class="medium-text" value="" autofocus="autofocus" />
						<input type="submit" name="medium_submit" id="medium_submit" class="btn btn-success" value="Add Source" />
					</form>
			</div>

   <?php } ?>

   <?php if(isset($_GET['tab']) && $_GET['tab'] == 'domains'){ ?>

<?php
				if(isset($_POST['domain_submit'])){
					$t_domains = maybe_unserialize(get_option('trksit_domains'));
					array_push($t_domains, $_POST['domain']);
					update_option('trksit_domains', serialize($t_domains));
				}
?>

   <div class="trksit_col_full">
	   <h2 class="trksit-header">Domains</h2>
	   <p>Here you can add first-party domain names</p>
	   <table class="wp-list-table widefat fixed">
		   <thead>
			   <tr>
				   <th><?php _e("Domain"); ?></th>
				   <th width="100"><?php _e("Delete"); ?></th>
			   </tr>
		   </thead>
		   <tbody>
<?php
				$domains = maybe_unserialize(get_option('trksit_domains'));
				for($i = 0; $i < count($domains); $i++){
					$domain_url = wp_nonce_url(str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) . '&deletedomain=' . $i, 'delete_domain', 'dd_nonce');
?>
		<tr>
		<td><?php echo $domains[$i]; ?></td>
			<td>
				<?php if($i > 0): ?>
				<a href="<?php echo $domain_url; ?>">Delete</a>
				<?php endif; ?>
			</td>
		</tr>
<?php
				}
?>
		   </tbody>
	   </table>
		<div style="padding-top: 20px;">
	   <form action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" class="trksit-form" method="post">
		   <input type="text" name="domain" id="domain" class="medium-text" value="" autofocus="autofocus" placeholder="http://example.com" />
		   <input type="submit" name="domain_submit" id="domain_submit" class="btn btn-success" value="Add Domain" />
	   </form>
	   </div>
   </div>


	<?php } ?>

<style>
	  #loading-indicator {
		 display: none;
	  }
</style>
