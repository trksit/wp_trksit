<?php
   if($_GET['page'] == 'trksit-settings'):
   if($_POST['trksit_page'] == 'settings' && ( !empty($_POST) && check_admin_referer('trksit_save_settings','trksit_general_settings') )) {

	  $trksit_analytics_id = $_POST['trksit_analytics_id'];
	  $trksit_public_api_key = $_POST['trksit_public_api_key'];
	  $trksit_private_api_key = $_POST['trksit_private_api_key'];
	  $trksit_jquery = $_POST['trksit_jquery'];
	  $trksit_redirect_delay = $_POST['trksit_redirect_delay'];

	  update_option('trksit_analytics_id', $trksit_analytics_id);
	  update_option('trksit_public_api_key', $trksit_public_api_key);
	  update_option('trksit_private_api_key', $trksit_private_api_key);
	  update_option('trksit_jquery', $trksit_jquery);
	  update_option('trksit_redirect_delay', $trksit_redirect_delay);

	  $trksit_confirmation = '<div class="alert alert-success" style="margin:30px 0px 0px 0px;">' . __('Trks.it Settings Updated') . '</div>';

   }else{

	  $trksit_analytics_id = get_option('trksit_analytics_id');
	  $trksit_public_api_key = get_option('trksit_public_api_key');
	  $trksit_private_api_key = get_option('trksit_private_api_key');
	  $trksit_jquery = get_option('trksit_jquery');
	  $trksit_redirect_delay = get_option('trksit_redirect_delay');

   }

   if($_POST['trksit_page'] == 'add_script' && ( !empty($_POST) && check_admin_referer('trksit_save_settings','trksit_add_script') )) {
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

   <?php echo $trksit_confirmation; ?>

   <div class="trksit_tab_nav">
	  <ul>
		 <li <?php if($_GET['tab'] == 'general' || empty($_GET['tab'])): ?>class="active"<?php endif; ?>><a href="/wp-admin/admin.php?page=trksit-settings&tab=general"><?php _e('General Settings'); ?></a></li>
		 <li <?php if($_GET['tab'] == 'scripts'): ?>class="active"<?php endif; ?>><a href="/wp-admin/admin.php?page=trksit-settings&tab=scripts"><?php _e('Custom Scripts'); ?></a></li>
	  </ul>
   </div>

   <?php if($_GET['tab'] == 'general' || empty($_GET['tab'])): ?>

   <form name="trksit_settings_form" id="trksit_settings_form" class="trksit-form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" style="float:left; display:block;">

	  <div class="trksit_col left">

		 <div class="trksit-section">

			<h2 class="trksit-header"><?php _e("API Settings"); ?></h2>

			<input type="hidden" name="trksit_page" value="settings" />
			<?php wp_nonce_field('trksit_save_settings','trksit_general_settings'); ?>

			<div class="control-group">
			   <label for="trksit_public_api_key" class="control-label"><?php _e("Trks.it Public API Key:"); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e("Enter your public API key, this was emailed to you."); ?>" data-original-title="<?php _e("Public API Key"); ?>"><i class="icon-question-sign"></i></a></label>
			   <div class="controls">
				  <input name="trksit_public_api_key" type="text" id="trksit_public_api_key" value="<?php echo $trksit_public_api_key; ?>" />
			   </div>
			</div>
			<div class="control-group">
			   <label for="trksit_private_api_key" class="control-label"><?php _e("Trks.it Private API Key:"); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e("Enter your private API key, this was emailed to you."); ?>" data-original-title="<?php _e("Private API Key"); ?>"><i class="icon-question-sign"></i></a></label>
			   <div class="controls">
				  <input name="trksit_private_api_key" type="text" id="trksit_private_api_key" value="<?php echo $trksit_private_api_key; ?>" />
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

		 <input type="submit" name="Submit" class="btn btn-success" value="<?php _e('Update Options', 'trksit_menu' ) ?>" />

	  </div>
	  <div class="trksit_col right">
	  </div>
   </form>

   <?php endif; ?>

   <?php if($_GET['tab'] == 'scripts'): ?>

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
		 $s_script = stripslashes(html_entity_decode($script_details[0]->script));
		 $s_sid = $script_details[0]->script_id;

		 //TODO htmspecialchar javascript for databassery

		 echo "<script>jQuery(window).load(function(){ jQuery('#add-script-window').modal('show'); });</script>";
	  }

	  if(isset($_GET['delete_nonce']) && $_GET['act'] == 'delete' && wp_verify_nonce($_GET['delete_nonce'], 'delete_script')){
		 $trksit->wp_trksit_deleteScript($wpdb, $_GET['id']);
	  }
   ?>

   <div class="trksit_col_full">

	  <h2 class="trksit-header"><?php _e("Custom Scripts"); ?></h2>
	  <p><?php _e("Here you can define custom scripts to be run when a Trks.it link is clicked. You can then assign your links with one or more scripts defined below."); ?></p>

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
			   $table_data = $wpdb->get_results(
				  "SELECT * FROM " . $wpdb->prefix . "trksit_scripts ORDER BY date_created DESC, script_id DESC" );

				  if(count($table_data)){

					 foreach($table_data as $table_row){
						$datetime = strtotime($table_row->date_created);
						$date_created = date('F j, Y', $datetime);
						$edit_url = wp_nonce_url(admin_url('admin.php?page=trksit-settings&tab=scripts&act=edit&id=' . $table_row->script_id), 'edit_script', 'edit_nonce');
						$delete_url = wp_nonce_url(admin_url('admin.php?page=trksit-settings&tab=scripts&act=delete&id=' . $table_row->script_id), 'delete_script', 'delete_nonce');
					 ?>
					 <tr>
						<td><?php echo $date_created; ?></td>
						<td><?php echo stripslashes($table_row->label); ?></td>
						<td><?php echo $table_row->platform; ?></td>
						<td>TODO</td>
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
		 <button data-target="#add-script-window" role="button" id="add-script" class="btn btn-success" data-toggle="modal"><?php _e("+ Add New Script"); ?></button>

	  </div>

	  <div id="add-script-window" class="modal fade hide" tabindex="-1" role="dialog" aria-labelledby="add-script-window" aria-hidden="true">
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
					 <select name="trksit_script_platform" id="trksit_script_platform">
						<option value="google" <?php if ($s_platform == 'google') { echo 'selected="selected"'; } ?>>Google Remarketing</option>
						<option value="bing" <?php if ($s_platform == 'bing') { echo 'selected="selected"'; } ?>>Bing</option>
						<option value="facebook" <?php if ($s_platform == 'facebook') { echo 'selected="selected"'; } ?>>Facebook</option>
						<option value="custom" <?php if ($s_platform == 'custom') { echo 'selected="selected"'; } ?>>Custom</option>
					 </select>
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

	  <?php endif; ?>

   </div>
   <?php endif; ?>
