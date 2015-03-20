<?php
if( !empty( $_POST ) || ( isset( $_GET['purge-data'] ) && $_GET['purge-data'] == true ) ){
	ob_start();
	echo '<div id="trksit-loading-indicator">
			<img src="' .plugin_dir_url(__FILE__).'images/loading.gif' . '" alt="Loading" />
		  </div>';
	trksit_flush_buffers();
}
if( isset( $_GET['purge-data'] ) && $_GET['purge-data'] == 'true' ){
	if( isset( $_GET['trksit_purge_nonce'] )
		&& wp_verify_nonce( $_GET['trksit_purge_nonce'], 'purge_my_data' ) ){
		$trksit = new trksit();
		$purged = $trksit->wp_trksit_api_uninstall( get_option( 'trksit_private_api_key' ) );
		$response = json_decode( $purged['body'] );
		if( $response->error ){
			echo '<div class="trksit-alert danger">API Error, data not purged. ' . $response->msg . '</div>';
		} else {
			$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'trksit_hits' );
			$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'trksit_scripts' );
			$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'trksit_scripts_to_urls' );
			$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'trksit_urls' );
			echo '<div class="trksit-alert success">All data removed from WordPress and trks.it databases.</div>';
		}
	} else {
		die( '<h1>Unauthorized Operation</h1>' );
	}
}

$trksit_analytics_id = '';
$trksit_public_api_key = '';
$trksit_private_api_key = '';
$trksit_jquery = '';
$trksit_redirect_delay = '';

if( $_GET['page'] == 'trksit-settings' ){
	//see trksit_update_settings_redirect() in main plugin file
	//Options are saved in action hook, then page is refreshed to update menu
	

	
	if( !isset( $_POST['trksit_page'] ) && empty( $_POST ) ) {
		$trksit_analytics_id = get_option('trksit_analytics_id');
		$trksit_public_api_key = get_option('trksit_public_api_key');
		$trksit_private_api_key = get_option('trksit_private_api_key');
		$trksit_jquery = get_option('trksit_jquery');
		$trksit_redirect_delay = get_option('trksit_redirect_delay');
	} else {
		if( isset( $_GET['tab'] )
			&& ( $_GET['tab'] != 'scripts' && $_GET['tab'] != 'sources' && $_GET['tab'] != 'domains' )
			&& $_GET['tab'] != 'medium' ){
			$trksit_analytics_id = $_POST['trksit_analytics_id'];
			$trksit_public_api_key = $_POST['trksit_public_api_key'];
			$trksit_private_api_key = $_POST['trksit_private_api_key'];
			$trksit_jquery = $_POST['trksit_jquery'];
			$trksit_redirect_delay = $_POST['trksit_redirect_delay'];
		}
	}
	if( ( isset( $_POST['trksit_page'] ) && $_POST['trksit_page'] == 'add_script' )
		&& ( !empty( $_POST ) && check_admin_referer( 'trksit_save_settings', 'trksit_add_script' ) ) ) {
		$trksit = new trksit();
		if( $_POST['script-id'] == '' ){
			$trksit_confirmation = $trksit->wp_trksit_saveCustomScript( $wpdb, $_POST, false );
		} else {
			$trksit_confirmation = $trksit->wp_trksit_saveCustomScript( $wpdb, $_POST, true );
		}
	}
?>
<div class="wrap" id="trksit-wrap">
	<h2><?php echo __( 'trks.it Settings', 'trksit_menu' ); ?></h2>
	<h2 class="nav-tab-wrapper">
		<a href="/wp-admin/admin.php?page=trksit-settings&tab=general" class="nav-tab <?php if( ( isset( $_GET['tab'] ) && $_GET['tab'] == 'general' ) || empty( $_GET['tab'] ) ): ?>nav-tab-active<?php endif; ?>">
			<?php _e( 'General' ); ?>
		</a>
		<a href="/wp-admin/admin.php?page=trksit-settings&tab=scripts" class="nav-tab <?php if( isset( $_GET['tab'] ) && $_GET['tab'] == 'scripts' ): ?>nav-tab-active<?php endif; ?>">
			<?php _e( 'Scripts' ); ?>
		</a>
		<a href="/wp-admin/admin.php?page=trksit-settings&tab=sources" class="nav-tab <?php if( isset( $_GET['tab'] ) && $_GET['tab'] == 'sources' ): ?>nav-tab-active<?php endif; ?>">
			<?php _e( 'Sources' ); ?>
		</a>
		<a href="/wp-admin/admin.php?page=trksit-settings&tab=medium" class="nav-tab <?php if( isset( $_GET['tab'] ) && $_GET['tab'] == 'medium' ): ?>nav-tab-active<?php endif; ?>">
			<?php _e( 'Medium' ); ?>
		</a>
		<a href="/wp-admin/admin.php?page=trksit-settings&tab=domains" class="nav-tab <?php if( isset( $_GET['tab'] ) && $_GET['tab'] == 'domains' ): ?>nav-tab-active<?php endif; ?>">
			<?php _e( 'Domains' ); ?>
		</a>
		<div class="pull-right">
			<?php
			if ( strpos( $_SERVER['HTTP_USER_AGENT'], 'Chrome' ) !== false ) :
			?>
				<a href="https://get.trks.it/chrome-extension/" class="nav-tab simple" target="_blank">
					<img src="<?php echo plugin_dir_url( __FILE__ ).'images/chrome-icon-120x120.png'; ?>" style="height: 15px; width: 15px; display: inline; margin-right: 5px; position: relative; top: 1px;" />
					<?php _e( 'Chrome Extension' ); ?>
				</a>
			<?php endif; ?>
			<a href="<?php echo WP_TRKSIT_MANAGE_URL; ?>" target="_blank" class="nav-tab simple"><i class="dashicons dashicons-external"></i> Account</a>
		</div>
	</h2>
	<?php
		// START General Settings Panel Output
	if( ( isset( $_GET['tab'] ) && $_GET['tab'] == 'general' ) || empty( $_GET['tab'] ) ):
		$api_online = true;
	?>
	<form name="trksit_settings_form" id="trksit_settings_form" class="trksit-form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>">

		<?php if( get_transient( 'trksit_error_message' ) && get_transient( 'trksit_error_message' ) != '' ): ?>
			<div class="trksit-alert warning">
				<h4>API temporarily offline</h4>
				<?php
				echo '<p><strong>' . get_transient('trksit_error_message') . "</strong></p>";
				delete_transient('trksit_error_message');
				$api_online = false;
				?>
			</div>
		<?php elseif( get_transient( 'trksit_active_user' ) && get_transient( 'trksit_active_user' ) == 'inactive' ): ?>
			<div class="trksit-alert warning">
				<h4>Plugin not Active</h4>
				<p>Please <a href="<?php echo WP_TRKSIT_MANAGE_URL; ?>" target="_blank">register here</a> then enter valid API keys</p>
				<?php
					if( $status = get_transient( 'trksit_status_messages' ) ){
						echo '<p><strong>Recent Status Messages</strong></p>';
						$stats = maybe_unserialize( $status );
						if( count( $stats ) > 0 ){
							foreach( $stats as $s ){
								echo '<p>' . $s->status_msg . ' on '. date( 'M d, Y - g:ia', strtotime( $s->date_created ) ) . '</p>';
							}
						}
						if( get_transient( 'trksit_url_status_msg' ) && get_option( 'trksit_public_api_key' ) ){
							echo '<p>' . get_transient( 'trksit_url_status_msg' ) . '</p>';
						}
					}
				?>
			</div>
		<?php endif; ?>
		<?php if ( $api_online ) : ?>
		<div class="trksit_col left">
		<div class="postbox" id="trksit-api-settings">
			<h3 class="hndle"><span><?php _e( 'API Settings' ); ?></span></h3>
			<div class="inside">
				<input type="hidden" name="trksit_page" value="settings" />
				<?php wp_nonce_field( 'trksit_save_settings', 'trksit_general_settings' ); ?>
				<div class="input-row">
					<label for="trksit_public_api_key"><?php _e( 'Public API Key:' ); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Enter your public API key, this was emailed to you.' ); ?>" data-original-title="<?php _e("Public API Key"); ?>"><i class="dashicons dashicons-editor-help"></i></a>
					</label>
					<input name="trksit_public_api_key" type="text" id="trksit_public_api_key" value="<?php echo $trksit_public_api_key; ?>" required />
				</div>
				<div class="input-row">
					<label for="trksit_private_api_key"><?php _e( 'Private API Key:' ); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Enter your private API key, this was emailed to you.' ); ?>" data-original-title="<?php _e( 'Private API Key' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
					</label>
					<input name="trksit_private_api_key" type="text" id="trksit_private_api_key" value="<?php echo $trksit_private_api_key; ?>" required />
				</div>
			</div>
		</div><!-- #trksit-api-settings.postbox -->
		<div id="trksit-google-analytics" class="postbox">
			<h3 class="hndle"><span><?php _e( 'Google Analytics Settings' ); ?></span></h3>
			<div class="inside">
				<div class="input-row">
					<label for="trksit_analytics_id"><?php _e("Google Analytics Profile ID:"); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Enter your Google Analytics UA-XXXXXXX-X ID. This will be where the data is recorded by trks.it.' ); ?>" data-original-title="<?php _e( 'Google Analytics Profile ID' ); ?>"><i class="dashicons dashicons-editor-help"></i></a></label>
					<input name="trksit_analytics_id" type="text" class="medium-text" placeholder="UA-XXXXXXX-X" id="trksit_analytics_id" value="<?php echo $trksit_analytics_id; ?>" />
				</div>
			</div>
		</div><!-- #trksit-google-analytics.postbox -->
		<div id="trksit-other-settings" class="postbox">
			<h3 class="hndle"><span><?php _e( 'Other Settings' ); ?></span></h3>
			<div class="inside">
				<div class="input-row inline-label">
					<label for="trksit_jquery"><?php _e( 'Include jQuery?:' ); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Turn on to allow jQuery script to be run on the redirect script.' ); ?>" data-original-title="<?php _e( 'jQuery' ); ?>"><i class="dashicons dashicons-editor-help"></i></a></label>
					<div class="btn-group" data-toggle="buttons-radio" id="trksit_jquery_radio">
						<button type="button" class="btn btn-default <?php if( !$trksit_jquery ) echo 'active'; ?>" value="0"><?php _e( 'No' ); ?></button>
						<button type="button" class="btn btn-default <?php if( $trksit_jquery ) echo 'active'; ?>" value="1"><?php _e( 'Yes' ); ?></button>
					</div>
					<input type="hidden" name="trksit_jquery" id="trksit_jquery" value="<?php echo $trksit_jquery; ?>" />
				</div>
				<div class="input-row inline-label">
					<label for="trksit_redirect_delay"><?php _e( 'Redirect Delay:' ); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Number of milliseconds to delay the redirect script. Default is 500.' ); ?>" data-original-title="<?php _e( 'Redirect Delay' ); ?>"><i class="dashicons dashicons-editor-help"></i></a></label>
					<input type="text" name="trksit_redirect_delay" id="trksit_redirect_delay" class="trksit-input-small" value="<?php echo $trksit_redirect_delay; ?>" placeholder="ig: 500" />
				</div>
				<div class="input-row">
					<label for="trksit_default_view"><?php _e( 'Default Dashboard View:' ); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'The way the dashboard displays hits' ); ?>" data-original-title="<?php _e( 'Default Dashboard View' ); ?>"><i class="dashicons dashicons-editor-help"></i></a></label>
					<select name="trksit_default_view" id="trksit_default_view">
						<option value="">Number of hits</option>
						<option value="">Date created</option>
					</select>
				</div>
			</div>
		</div><!-- #trksit-other-settings.postbox -->
		<input type="submit" name="Submit" class="button button-primary button-large" value="<?php _e( 'Update Options', 'trksit_menu' ) ?>" id="trksit_settings_update" />
		</div>
		<div class="trksit_col right">
			<div class="trksit-alert danger">
				<h4><i class="dashicons dashicons-trash"></i> Delete trks.it Data</h4>
				<p><strong>This action cannot be undone!</strong> This process will purge all shortened links from your local database and the trks.it short link library.</p>
				<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=trksit-settings&purge-data=true' ), 'purge_my_data', 'trksit_purge_nonce' ); ?>" onclick="return confirm( 'This will delete all URLs from WordPress and the trks.it API. Continue?' );" class="btn btn-danger">Purge all data</a>
			</div>
		</div>
		<?php endif; // api_online? ?>
	</form>
	<?php
		endif; // END General Settings Panel Output
		// START Scripts Panel Output
		if( isset( $_GET['tab'] ) && $_GET['tab'] == 'scripts' ):
			$s_label = '';
			$s_platform = '';
			$s_script = '';
			$s_sid = '';
			$form_url = remove_query_arg( array( 'act', 'id', 'edit_nonce','delete_nonce' ), str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ) );
			$trksit = new trksit();
			if( isset( $_GET['edit_nonce'] ) && $_GET['act'] == 'edit'
				&& wp_verify_nonce( $_GET['edit_nonce'], 'edit_script' ) ){
				$script_details = $trksit->wp_trksit_scriptDetails( $wpdb, $_GET['id'] );
				$s_label = $script_details[0]->label;
				$s_platform = $script_details[0]->platform;
				$s_script = stripslashes( htmlspecialchars_decode( $script_details[0]->script ) );
				$s_sid = $script_details[0]->script_id;
				echo "<script>jQuery(window).load(function(){ jQuery('#add-script-window').modal('show'); });</script>";
			}
			if( isset( $_GET['delete_nonce'] ) && $_GET['act'] == 'delete'
				&& wp_verify_nonce( $_GET['delete_nonce'], 'delete_script' ) ){
				$trksit->wp_trksit_deleteScript( $wpdb, $_GET['id'] );
			}
	?>
	<div class="trksit_col full">
		<h2><?php _e( 'Remarketing &amp; Custom Scripts' ); ?></h2>
		<p><?php _e( 'Here you can define remarketing lists & custom scripts to be run when a trks.it link is clicked. You can then assign your links with one or more scripts defined below.' ); ?></p>
		<table class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th width="120"><?php _e( 'Date Created' ); ?></th>
					<th><?php _e( 'Label' ); ?></th>
					<th><?php _e( 'Platform' ); ?></th>
					<th><?php _e( 'URL\'s Attached' ); ?></th>
					<th width="100"><?php _e( 'Edit / Delete' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
				$footnote = '';
				$table_data = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "trksit_scripts ORDER BY date_created DESC, script_id DESC" );
				if( count( $table_data ) ):
					foreach( $table_data as $table_row ):
						$q = "SELECT url_id FROM " . $wpdb->prefix . "trksit_scripts_to_urls WHERE script_id = " . $table_row->script_id;
						$times_used = $wpdb->get_results( $q );
						$used = count( $times_used );
						$datetime = strtotime( $table_row->date_created );
						$date_created = date( 'F j, Y', $datetime );
						$edit_url = wp_nonce_url( admin_url( 'admin.php?page=trksit-settings&tab=scripts&act=edit&id=' . $table_row->script_id ), 'edit_script', 'edit_nonce' );
						$delete_url = wp_nonce_url( admin_url( 'admin.php?page=trksit-settings&tab=scripts&act=delete&id=' . $table_row->script_id ), 'delete_script', 'delete_nonce' );
			?>
				<tr <?php if( $table_row->script_error ) { echo 'class="error-script"'; } ?>>
					<td><?php echo $date_created; ?></td>
					<td>
						<?php
							echo stripslashes( $table_row->label );
							if( $table_row->script_error ) {
								$url = '/index.php?trksitgo=1&url_id=scripterror&testing=scripterror&scriptid=' . $table_row->script_id;
								$url = wp_nonce_url( $url, 'script_error_' . $table_row->script_id, 'script_error_nonce' );
								echo ' * &nbsp; <a href="' . $url . '" class="script_debug" target="_blank">[execute]</a>';
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
					   <a href="<?php echo $edit_url; ?>">Edit</a> |
					   <a href="<?php echo $delete_url; ?>" class="danger-text" onclick="return confirm( 'Are you sure? This can not be undone.' );">Delete</a>
					</td>
				</tr>
			<?php
					endforeach; // End table row loop
				else: // If there aren't any custom scripts created - prompt the user to create one
			?>
				<tr>
					<td colspan="5" style="padding:20px;">
						<?php _e( 'You haven\'t created a custom script yet, ' ); ?><a href="#" data-target="#add-script-window" role="button" data-toggle="modal"><?php _e( 'Add a script now!' ); ?></a>
					</td>
				</tr>
			<?php
				endif; // End table row output
			?>
			</tbody>
			<tfoot>
				<tr>
					<th><?php _e( 'Date Created' ); ?></th>
					<th><?php _e( 'Label' ); ?></th>
					<th><?php _e( 'Platform' ); ?></th>
					<th><?php _e( 'URL\'s Attached' ); ?></th>
					<th><?php _e( 'Edit / Delete' ); ?></th>
				</tr>
			</tfoot>
		</table>
		<?php echo $footnote; ?>
		<button data-target="#add-script-window" role="button" id="add-script" class="button button-primary button-large" data-toggle="modal"><?php _e( '+ Add New Script' ); ?></button>
	</div>
	<!-- Add Script Modal -->
	<div class="modal fade" id="add-script-window" tabindex="-1" role="dialog" aria-labelledby="add-script-window-label" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="add-script-window-label"><?php _e( 'Add a New Custom Script' ); ?></h4>
				</div>
				<form name="trksit_add_script_form" id="trksit_add_script_form" class="trksit-form" method="post" action="<?php echo $form_url; ?>">
					<div class="modal-body">
						<div class="input-row">
							<label for="trksit_script_label" class="control-label">
								<?php _e( 'Script Label:' ); ?>
								<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Enter a label that will allow you to easily identify this script.' ); ?>" data-original-title="<?php _e( 'Script Label' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
							</label>
							<input name="trksit_script_label" type="text" id="trksit_script_label" value="<?php echo $s_label; ?>" required="required" />
						</div>
						<div class="input-row">
							<label for="trksit_script_platform" class="control-label">
								<?php _e( 'Platform' ); ?>
								<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'What is this script for? Google? Bing? Facebook? etc...' ); ?>" data-original-title="<?php _e( 'Platform' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
							</label>
							<select name="trksit_script_platform" id="trksit_script_platform" required="required">
								<option value="">--Please choose a platform--</option>
								<option value="google" <?php if ( $s_platform == 'google' ) { echo 'selected="selected"'; } ?>>Google Remarketing</option>
								<option value="bing" <?php if ( $s_platform == 'bing' ) { echo 'selected="selected"'; } ?>>Bing</option>
								<option value="facebook" <?php if ( $s_platform == 'facebook' ) { echo 'selected="selected"'; } ?>>Facebook</option>
								<option value="custom" <?php if ( $s_platform == 'custom' ) { echo 'selected="selected"'; } ?>>Custom</option>
								<?php
									$custom_opts = get_option( 'trksit_script_platforms' );
									$co = maybe_unserialize( $custom_opts );
									foreach( $co as $c ){
										echo '<option value="' . $c . '"';
											if( $s_platform == $c ){
												echo ' selected="selected" ';
											}
										echo '>' . $c . '</option>';
									}
								?>
								<option value="other" id="other">Other</option>
							</select>
							<input type="text" name="trksit_script_platform_other" id="trksit_script_platform_other" value="" placeholder="Add platform..." class="margin-t" style="display: none;" />
						</div>
						<div class="input-row no-margin-b">
							<label for="trksit_script" class="control-label">
								<?php _e( 'Custom Script' ); ?>
								<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Use this field to write your custom script. Make sure everything works!' ); ?>" data-original-title="<?php _e( 'Custom Script' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
							</label>
							<textarea name="trksit_script" id="trksit_script" class="code-input" required="required"><?php echo $s_script; ?></textarea>
						</div>
						<input type="hidden" name="trksit_page" value="add_script" />
						<?php wp_nonce_field( 'trksit_save_settings', 'trksit_add_script' ); ?>
						<input type="hidden" name="script-id" id="script-id" value="<?php echo $s_sid; ?>" />
					</div>
					<div class="modal-footer">
						<button class="button button-large margin-r" id="script_cancel" data-dismiss="modal" data-url="<?php echo $form_url; ?>" aria-hidden="true"><?php _e( 'Close' ); ?></button>
					    <button type="submit" name="Submit" class="button button-primary button-large"><?php _e( 'Save Script' ); ?></button>
					</div>
				</form>
			</div>
		</div>
	</div><!-- #add-script-modal -->
	<?php
		endif; // END Scripts Panel Output
		// START Sources Panel Output
		if( isset( $_GET['tab'] ) && $_GET['tab'] == 'sources' ):
	?>
	<div class="trksit_col full">
		<h2>Sources</h2>
		<p>Here you can add source values to the drop down available when creating a new link.</p>
		<form action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>" class="trksit-form input-row inline-label" method="post">
			<label for="source" style="width: auto;" class="margin-r">Add New: </label>
			<input type="text" name="source" id="source" class="margin-r" value="" autofocus="autofocus" style="display: inline-block; width: auto;" />
			<input type="submit" name="source_submit" id="source_submit" class="button button-primary button-large" value="Add Source" />
			<?php
				if( isset( $_SESSION['trksit_error'] ) ){
					echo '<div class="trksit-alert danger"><p>' . $_SESSION['trksit_error'] . '</p></div>';
					unset( $_SESSION['trksit_error'] );
				}
			?>
		</form>
		<table class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th><?php _e( 'Source' ); ?></th>
					<th width="100"><?php _e( 'Delete' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
				$sources = maybe_unserialize( get_option( 'trksit_sources' ) );
				sort( $sources );
				$count_of_sources = count( $sources );
				for( $i = 0; $i < $count_of_sources; $i++ ):
					$source_url = wp_nonce_url( str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ) . '&deletesource=' . $i, 'delete_source', 'ds_nonce' );
			?>
			<tr>
				<td><?php echo $sources[$i]; ?></td>
				<td>
				<?php
					if( $count_of_sources !== 1 ){
						echo '<a href="' . $source_url . '" title="Delete this Source" class="danger-text">Delete</a>';
					} else {
						echo '<p>You must have at least one (1) source active.</p>
							  <p>Please add another source before attempting to delete this one.</p>';
					}
				?>
				</td>
			</tr>
			<?php
				endfor; // End table row output
			?>
			</tbody>
		</table>
	</div>
	<?php
		endif; // END Sources Panel Output
		// START Medium Panel Output
		if( isset( $_GET['tab'] ) && $_GET['tab'] == 'medium' ):
	?>
	<div class="trksit_col full">
		<h2>Mediums</h2>
		<p>Here you can add medium values to the drop down available when creating a new link.</p>
		<form action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>" class="trksit-form input-row inline-label" method="post">
			<label for="source" style="width: auto;" class="margin-r">Add New: </label>
			<input type="text" name="medium" id="medium" class="margin-r" value="" autofocus="autofocus" style="display: inline-block; width: auto;" />
			<input type="submit" name="medium_submit" id="medium_submit" class="button button-primary button-large" value="Add Medium" />
			<?php
				if( isset( $_SESSION['trksit_error'] ) ){
					echo '<div class="trksit-alert danger"><p>' . $_SESSION['trksit_error'] . '</p></div>';
					unset( $_SESSION['trksit_error'] );
				}
			?>
		</form>
		<table class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th><?php _e( 'Medium' ); ?></th>
					<th width="100"><?php _e( 'Delete' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					$medium = maybe_unserialize( get_option( 'trksit_medium' ) );
					$count_of_medium = count( $medium );
					sort( $medium );
					for( $i = 0; $i < $count_of_medium; $i++ ):
						$medium_url = wp_nonce_url( str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ) . '&deletemedium=' . $i, 'delete_medium', 'dm_nonce' );
				?>
				<tr>
					<td><?php echo $medium[$i]; ?></td>
					<td>
						<?php
							if( $count_of_medium !== 1 ){
								echo '<a href="' . $medium_url . '" title="Delete This Medium" class="danger-text">Delete</a>';
							} else {
								echo '<p>You must have at least one (1) medium active.</p>
									  <p>Please add another medium before attempting to delete this one.</p>';
							}
						?>
					</td>
				</tr>
				<?php
					endfor;
				?>
			</tbody>
		</table>
	</div>
<?php
	endif; // END Medium Panel Output
	// START Domains Panel Output
	if( isset( $_GET['tab'] ) && $_GET['tab'] == 'domains' ):
?>
	<div class="trksit_col full">
		<h2>Domains</h2>
		<p>Here you can add first-party domain names.</p>
		<form action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>" class="trksit-form input-row inline-label" method="post">
			<label for="source" style="width: auto;" class="margin-r">Add New: </label>
			<input type="text" name="domain" id="domain" class="margin-r" value="" autofocus="autofocus" placeholder="http://example.com" style="display: inline-block; width: auto;" />
			<input type="submit" name="domain_submit" id="domain_submit" class="button button-primary button-large" value="Add Domain" />
			<?php
				if( isset( $_SESSION['trksit_error'] ) ){
					echo '<div class="trksit-alert danger"><p>' . $_SESSION['trksit_error'] . '</p></div>';
					unset( $_SESSION['trksit_error'] );
				}
			?>
		</form>
		<table class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th><?php _e( 'Domain' ); ?></th>
					<th width="100"><?php _e( 'Delete' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					$domains = maybe_unserialize( get_option( 'trksit_domains' ) );
					for( $i = 0; $i < count( $domains ); $i++ ):
						$domain_url = wp_nonce_url( str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ) . '&deletedomain=' . $i, 'delete_domain', 'dd_nonce' );
				?>
				<tr>
					<td><?php echo $domains[$i]; ?></td>
					<td>
						<?php
							if( $i > 0 ){
								echo '<a href="' . $domain_url . '" class="danger-text" title="Delete this Domain">Delete</a>';
							}
						?>
					</td>
				</tr>
				<?php
					endfor;
				?>
			</tbody>
		</table>
	</div>
	<?php
		endif; // END Domains Panel Output
	?>
<style>
	#trksit-loading-indicator {
		display: none;
	}
</style>
<?php } ?>
