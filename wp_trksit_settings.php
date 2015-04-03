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

if(isset($_POST['script_submit']) && wp_verify_nonce( $_POST['trksit_scripts'], 'trksit_save_scripts' )){
	$trksit_scripts = array(
		'google' => esc_html($_POST['trksit_google_id']),
		'bing' => esc_html($_POST['trksit_bing_id']),
		'facebook' => esc_html($_POST['trksit_facebook_id'])
	);
	update_option('trksit_script_ids', serialize($trksit_scripts));
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
	//if( ( isset( $_POST['trksit_page'] ) && $_POST['trksit_page'] == 'add_script' )
		//&& ( !empty( $_POST ) && check_admin_referer( 'trksit_save_settings', 'trksit_add_script' ) ) ) {
		//$trksit = new trksit();
		//if( $_POST['script-id'] == '' ){
			//$trksit_confirmation = $trksit->wp_trksit_saveCustomScript( $wpdb, $_POST, false );
		//} else {
			//$trksit_confirmation = $trksit->wp_trksit_saveCustomScript( $wpdb, $_POST, true );
		//}
	//}
?>
<div class="wrap" id="trksit-wrap">
	<h2 style="display: inline-block;"><?php echo __( 'trks.it Settings', 'trksit_menu' ); ?></h2> <?php echo WP_TKSIT_SUPPORT_BTN; ?>
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
		$google_id = '';
		$bing_id = '';
		$bing_secondvalue = '';
		$facebook_id = '';
		$facebook_secondvalue = '';
		if($trksit_scripts = maybe_unserialize(get_option('trksit_script_ids'))){
			$google_id = $trksit_scripts['google'];
			$bing_id = $trksit_scripts['bing'];
			$facebook_id = $trksit_scripts['facebook'];
		}
?>
	<div class="trksit_col full">
		<h2><?php _e( 'Remarketing &amp; Custom Scripts' ); ?></h2>
		<!-- <p><?php _e( 'Here you can define remarketing lists & custom scripts to be run when a trks.it link is clicked. You can then assign your links with one or more scripts defined below.' ); ?></p> -->
		<p>
<?php _e( 'Here you can enter you IDs for various popular remarketing and custom scripts. You can then assign your links with one or more of these defined scripts below.'); ?>
		</p>
	</div>
	<form action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>" class="trksit-form input-row inline-label" method="post" id="trksit_remarketing_scripts">
		<?php wp_nonce_field( 'trksit_save_scripts', 'trksit_scripts' ); ?>
		<div class="postbox" id="trksit-google">
			<h3 class="hndle"><span><?php _e( 'Platform Remarketing IDs' ); ?></span></h3>
			<div class="inside">
				<div class="input-row">
					<label for="trksit_google_id">
						<?php _e( 'Google Remarketing ID:' ); ?>
							<a class="trksit-help" data-toggle="popover"
								data-content="<?php _e( 'Provided by Google (UA-XXXXXXX-X)' ); ?>"
								data-original-title="<?php _e("Google Remarketing ID"); ?>">
								<i class="dashicons dashicons-editor-help"></i>
							</a>
					</label><br />
					<input name="trksit_google_id" type="text" id="trksit_google_id" value="<?php echo $google_id; ?>" />
				</div>
				<div class="input-row">
					<label for="trksit_bing_id">
						<?php _e( 'Bing Remarketing ID:' ); ?>
							<a class="trksit-help" data-toggle="popover"
								data-content="<?php _e( 'Provided by Bing (XXXXXX-XXXXX)' ); ?>"
								data-original-title="<?php _e("Bing Remarketing ID"); ?>">
								<i class="dashicons dashicons-editor-help"></i>
							</a>
					</label><br />
					<input name="trksit_bing_id" type="text" id="trksit_bing_id" value="<?php echo $bing_id; ?>" />
				</div>
				<div class="input-row">
					<label for="trksit_facebook_id">
						<?php _e( 'Facebook Remarketing ID:' ); ?>
							<a class="trksit-help" data-toggle="popover"
								data-content="<?php _e( 'Provided by Facebook: (XXXX-XXXX)' ); ?>"
								data-original-title="<?php _e("Facebook Remarketing ID"); ?>">
								<i class="dashicons dashicons-editor-help"></i>
							</a>
					</label><br />
					<input name="trksit_facebook_id" type="text" id="trksit_facebook_id" value="<?php echo $facebook_id; ?>" />
				</div>
			</div>
		</div><!-- #trksit-api-settings.postbox -->
		<input type="submit" name="script_submit" class="button button-primary button-large" value="<?php _e( 'Update Options', 'trksit_menu' ) ?>" id="trksit_scripts_update" />
	</form>
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
