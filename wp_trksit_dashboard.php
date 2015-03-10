<div class="wrap" id="trksit-wrap">
<?php

if ( isset( $_GET['view'] ) ) {

	//Added to action hook
	//header('Content-Encoding: none;'); // Use with ob_start() and flushing of buffers!!!

	ob_start();

	echo '<div id="trksit-loading-indicator">
		      <img src="' . plugins_url( '/wp_trksit/images/loading.gif' , dirname(__FILE__) ) . '" alt="Loading" />
		  </div>';

	trksit_flush_buffers();
}

?>

<?php

$trksit = new trksit();

if ( ( isset( $_GET['view'] ) && $_GET['view'] == 'link-detail' ) && is_numeric( $_GET['linkid'] ) ) {

	$details_nonce = $_REQUEST['_wpnonce'];

	if ( !wp_verify_nonce( $details_nonce, 'trksit-view-details' ) ) {

		die();

	} else {

		if ( isset( $_POST['meta_title'] ) && !empty( $_POST ) ) {
			$trksit->wp_trksit_saveURL( $wpdb, $_POST, true, $_GET['linkid'] );
		}

		$link_id = $_GET['linkid'];
		$url_details = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "trksit_urls WHERE url_id = " . $link_id . "" );

		if ( count( $url_details === 1 ) ) {

			$trksit->wp_trksit_parseURL( $url_details[0]->destination_url );
			$og_data = unserialize ( $url_details[0]->og_data );
			$date = $url_details[0]->date_created;
			$trksit_url = $url_details[0]->trksit_url;

			if ( isset( $_GET['trksit_start_date'] ) AND !empty( $_GET['trksit_start_date'] ) AND isset( $_GET['trksit_end_date'] ) AND !empty( $_GET['trksit_end_date'] ) ){

				$start_date = date( 'Y-m-d', strtotime( $_GET['trksit_start_date'] ) );
				$end_date = date( 'Y-m-d', strtotime( $_GET['trksit_end_date'] ) );

			} else {

				$start_date = date( 'Y-m-d',strtotime( 'last week' ) );
				$end_date = date( 'Y-m-d', time() );

			}

			$user_info = get_userdata($url_details[0]->user_id);
			$user_display_details = '';

			if( is_object( $user_info ) ) {

				$user_display_details = '<h3>Created by: ' . $user_info->display_name . ' [' . $user_info->user_login . ']</h3>';

			}
?>
			<h2><?php echo __( 'trks.it - Details for Link ID #' . $url_details[0]->url_id, 'trksit_menu' ); ?></h2>

			<?php if( $user_display_details != '' ){ echo __( $user_display_details, 'trksit_menu' ); } ?>

			<div class="postbox">

				<div class="inside">

					<div id="trks_hits"></div>

				</div>

			</div>

			<?php
				$trksit->wp_trksit_getAnalytics( $start_date, $end_date, $link_id );
			?>

			<form class="trksit-form"  method="post">

			    <div class="trksit_col third">

					<div class="postbox">

						<h3 class="hndle"><span><?php _e( 'Links' ); ?></span></h3>

						<div class="inside">

							<div class="input-row">

								<label>
									<?php _e( 'trks.it URL:' ); ?>
									<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'This is the shortened trks.it URL you will use on various social and marketing platforms.' ); ?>" data-original-title="<?php _e( 'trks.it URL' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
								</label>

								<a href="<?php echo $url_details[0]->trksit_url; ?>" target="_blank" style="text-decoration: none;">
									<span class="uneditable-input" id="final-url"><?php echo $url_details[0]->trksit_url; ?></span>
								</a>

								<input type="hidden" name="trksit_url" value="<?php echo $url_details[0]->trksit_url;?>" />

							</div>

							<div class="input-row">
								<label>
									<?php _e( 'Destination URL:' ); ?>
									<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'The user will end up at this url.' ); ?>" data-original-title="<?php _e( 'Destination URL' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
								</label>

							    <a href="<?php echo $url_details[0]->destination_url; ?>" target="_blank" style="text-decoration: none;">
							    	<span class="uneditable-input" id="final-url"><?php echo $url_details[0]->destination_url; ?></span>
							    </a>

							    <input type="hidden" name="destination_url" value="<?php echo $url_details[0]->destination_url;?>" />

							</div>

							<!-- <div class="control-group">
								<label class="control-label" for="title"><?php _e('Title:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('The title to be used when sharing the content. This is pulled from the pages Open Graph data if it already exists. If not, it defaults to the title of the page.'); ?>" data-original-title="<?php _e('Title'); ?>"><i class="dashicons dashicons-editor-help"></i></a></label>
								<div class="controls">
								   <input name="meta_title" id="title" <?php echo ($og_data['og:title']) ? '' : 'class="listen"'; ?> type="text" maxlength="100" value="<?php echo $url_details[0]->meta_title; ?>">
								</div>
							 </div>
							 <div class="control-group">
								<label class="control-label" for="description"><?php _e('Description:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('The description to be used when sharing the content. This is pulled from the pages Open Graph data if it already exists. If not, it defaults to the meta description of the page.'); ?>" data-original-title="<?php _e('Description'); ?>"><i class="dashicons dashicons-editor-help"></i></a></label>
								<div class="controls">
								   <textarea name="meta_description" id="description" <?php if(isset($og_data['og:description'])) echo ($og_data['og:description']) ? '' : 'class="listen"'; ?> rows="5" maxlength="255"><?php echo $url_details[0]->meta_description; ?></textarea>
								</div>
							 </div> -->

						</div>

					</div>
			    </div>

			    <div class="trksit_col third middle">

					<div class="postbox">

						<h3 class="hndle"><span><?php _e( 'Analytics Tracking Data' ); ?></span></h3>

						<div class="inside">

							<div class="input-row">

								<label for="campaign">
									<?php _e( 'Campaign Name:' ); ?>
									<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Use this field to define a unique campaign value to be sent into your Google Analytics dashboard.' ); ?>" data-original-title="<?php _e( 'Campaign Name' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
								</label>

								<span class="uneditable-input"><?php echo $url_details[0]->campaign; ?></span>
								<input type="hidden" name="campaign" value="<?php echo $url_details[0]->campaign;?>">

							</div>

							<div class="input-row">

								<label for="source">
									<?php _e( 'Source:' ); ?>
									<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Use this field to define a unique source value to be sent into your Google Analytics dashboard.' ); ?>" data-original-title="<?php _e( 'Campaign Source' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
								</label>

								<span class="uneditable-input">
									<?php
										if ( $url_details[0]->source === '' ) {

											echo 'Auto Detect';

										} else {

											echo $url_details[0]->source;

										}
									?>
								</span>

								<input type="hidden" name="source" value="<?php echo $url_details[0]->source;?>">

							</div>

							<div class="input-row">

								<label for="medium">
									<?php _e( 'Medium:' ); ?>
									<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Use this field to define a unique medium value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e( 'Campaign Medium' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
								</label>

								<span class="uneditable-input"><?php echo $url_details[0]->medium; ?></span>
								<input type="hidden" name="medium" value="<?php echo $url_details[0]->medium;?>">

							</div>

						</div>

				    </div>

					<?php
						foreach ( $trksit->wp_trksit_getOGMetaArray() as $property => $content ) {
							echo sprintf( '<input type="hidden" name="%s" value="%s">', $property, $content );
						}
					?>

			    </div><!-- .trksit_col.third.middle -->

			    <div class="trksit_col third">

					<?php

					/*
					<div class="postbox">

						<h3 class="hndle"><span><?php _e('Sharing Preview'); ?></span></h3>

						<div class="inside">

							<div id="preview">

								<?php //if(isset($og_data['og:image'])): ?>

								<div class="image"><!-- <img src="<?php echo (isset($og_data['og:image'])) ? $og_data['og:image'] : $url_details[0]->meta_image; ?>"> --></div>

								<?php //endif; ?>

								<div class="content">

									<div class="title">
										<?php echo ( isset( $og_data['og:title'] ) ) ? $og_data['og:title'] : $url_details[0]->meta_title; ?>
									</div>

									<div class="url">
										<?php
											echo substr( $url_details[0]->destination_url, 0, 38 );

											if ( strlen( $url_details[0]->destination_url ) > 40 ) {
												echo '...';
											}
										?>
									</div>

									<?php if ( isset( $og_data['og:description'] ) ) { ?>

									<div class="description">
										<?php echo ( isset( $og_data['og:description'] ) ) ? $og_data['og:description'] : $url_details[0]->meta_description; ?>
									</div>

									<?php } ?>

								</div>

								<div class="clearfix"></div>

							</div><!-- #preview -->

							<?php
								if ( !isset( $og_data['og:image'] ) ) {
							?>

							<select name="meta_image" id="preview-image-picker">
								<?php
									foreach ( $trksit->imgArray as $image ) {

										if ( strpos( $image, 'www.googleadservices.com' ) === false ) {
											echo sprintf( '<option data-img-src="%s" value="%s">%s</option>', $image, $image, $image );
										}

									}
								?>
							</select>

							<?php
								}
							?>

						</div>

					</div>
					*/

					?>

					<div class="postbox">

						<h3 class="hndle"><span><?php _e( 'Attached Scripts' ); ?></span></h3>

						<div class="inside">

						<?php

							$active_scripts = $wpdb->get_results( "SELECT script_id FROM " . $wpdb->prefix . "trksit_scripts_to_urls WHERE url_id=" . $url_details[0]->url_id );

							$active_scripts_array = array();

							foreach ( $active_scripts as $active_script ) {
								$active_scripts_array[] = $active_script->script_id;
							}

							$scripts = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "trksit_scripts order by label" );

							if ( count( $scripts ) ) {

								$count = 1;

								echo '<ul class="list-unstyled">';

								foreach ( $scripts as $script ) {

									$even_odd = ( $count&1 ? 'odd' : 'even' );

									$checked = ( in_array( $script->script_id, $active_scripts_array ) ) ? ' checked' :  '';

									echo '<li><label class="checkbox ' . $even_odd . '"><input type="checkbox" name="trksit_scripts[]" value="' . $script->script_id . '"' . $checked . ' disabled />' . stripslashes( $script->label ) . '</label></li>';

									$count++;

								}

								echo '</ul>';

							} else {

								_e( '<div class="trksit-alert info"><p>You haven\'t setup any remarketing lists or custom scripts yet!</p> <a href="./admin.php?page=trksit-settings&tab=scripts" class="button button-primary">Create one now!</a></div>' );

							}

						?>

						</div>

					</div>

			    </div><!-- .trksit_col.third -->

			    <div class="trksit_col full">

			    	<?php
						foreach ( $trksit->wp_trksit_getOGMetaArray() as $property => $content ) {
							echo sprintf( '<input type="hidden" name="%s" value="%s">', $property, $content );
						}
					?>

					<?php wp_nonce_field( 'trksit_update_url','trksit_update_url' ); ?>

					<?php
						/*
						<button type="submit" class="button button-primary button-large" data-loading-text="<?php _e( 'Please wait...' ); ?>">
							<?php _e( 'Update URL' ); ?>
						</button>
						*/
					?>

			    </div><!-- .trksit_col.full -->

			</form>

<?php
		}
	}
}
else if ( $_GET['page'] == 'trksit-dashboard' ) {

?>
	<h2><?php echo __( 'trks.it Dashboard', 'trksit_menu' ); ?></h2>
<?php

	if ( isset( $_GET['trksit_start_date'] ) AND !empty( $_GET['trksit_start_date'] ) AND isset( $_GET['trksit_end_date'] ) AND !empty( $_GET['trksit_end_date'] ) ) {

		$start_date = date( 'Y-m-d', strtotime( $_GET['trksit_start_date'] ) );
		$end_date = date( 'Y-m-d', strtotime( $_GET['trksit_end_date'] ) );

	} else {

		$start_date = date( 'Y-m-d', strtotime( 'last week' ) );
		$end_date = date( 'Y-m-d', time() );

	}
	//get the date of the last week so we can select all links created within the past week
	$last_week = date( 'Y-m-d', strtotime( 'last week' ) );
	$today = date( 'Y-m-d', time() );

	$timeline_points = $wpdb->get_results( "SELECT date_created FROM " . $wpdb->prefix . "trksit_urls LIMIT 1" );
	if ( count( $timeline_points ) === 1 ) {
		$date = $timeline_points[0]->date_created;
?>



		<div class="postbox">

			<div class="hndle">
				<form action="<?php echo trksit_current_page();?>" class="wp-core-ui" method="GET" id="trksit_date_selector">
					<input type="hidden" name="page" value="trksit-dashboard">
					<div class="trksit_date">
						<label for="trksit_start_date"><?php _e( 'Start Date' ); ?></label>
						<input type="text" id="trksit_start_date" name="trksit_start_date">
					</div>
					<div class="trksit_date">
						<label for="trksit_end_date"><?php _e( 'End Date' ); ?></label>
						<input type="text" id="trksit_end_date" name="trksit_end_date">
					</div>
					<button type="submit" class="button button-primary">Update</button>
				</form>
			</div>

			<div class="inside">

				<div id="trks_hits">
				</div>

				<div id="graph-lines">
					<div class="line line-1"></div>
					<div class="line line-2"></div>
					<div class="line line-3"></div>
				</div>

				<div id="graph-lines-end"></div>

			</div>

		</div>

<?php
		$trksit->wp_trksit_getAnalytics( $start_date, $end_date );
	}
?>
		 <table class="wp-list-table widefat fixed" id="trks_dashboard" style="width: 100% !important;">
			<thead>
				<tr>
					<th width="100">
						<?php _e( 'Created' ); ?>
					</th>
					<th width="100">
						<?php _e( 'Hits' ); ?>
					</th>
					<th width="125">
						<?php _e( 'trks.it URL' ); ?>
					</th>
					<th width="40"></th>
					<th id="trks_it_destination"><?php _e( 'Destination URL' ); ?></th>
					<th><?php _e( 'Campaign' ); ?></th>
					<th><?php _e( 'Source' ); ?></th>
					<th><?php _e( 'Medium' ); ?></th>
					<th width="80"></th>
				</tr>
			</thead>
			<tbody></tbody>
		 </table>
<?php

}

?>

<style>
	#trksit-loading-indicator {
		display: none;
	}
</style>

</div><!-- #trksit-wrap -->