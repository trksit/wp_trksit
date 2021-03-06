<?php
	ob_start();
	echo '<div id="trksit-loading-indicator">
			<img src="' .plugin_dir_url(__FILE__).'images/loading.gif' . '" alt="Loading" />
		  </div>';
	trksit_flush_buffers();
	if ( $_GET['page'] == 'trksit-generate' ):
?>
<div class="wrap" id="trksit-wrap">
	<h2 style="display: inline-block;"><?php echo __( 'Generate a new trks.it URL', 'trksit_menu' ); ?></h2> <?php echo WP_TKSIT_SUPPORT_BTN; ?>
	<?php
		/* ---- Step 3 ---- */
		if( isset( $_POST['meta_title'] ) && ( !empty( $_POST ) && check_admin_referer( 'trksit_generate_url', 'trksit_generate_step2' ) ) ){
			$trksit = new trksit();
			if ( time() > get_option( 'trksit_token_expires' ) ) {
				$trksit->wp_trksit_resetToken();
			}
			$shortURL = $trksit->wp_trksit_shortenURL( $_POST );
			$trks_error = $trksit->wp_trksit_getErrors();
	?>
		<h2 class="nav-tab-wrapper">
			<a href="./admin.php?page=trksit-generate" class="nav-tab">Step 1</a>
			<a href="#" class="nav-tab nav-tab-disabled">Step 2</a>
			<a href="#" class="nav-tab nav-tab-active">Step 3</a>
		</h2>
		<?php
			if ( is_wp_error( $trks_error ) ) {
				echo '<div class="trksit-alert danger">';
				$wp_errors = $trks_error->get_error_messages();
				foreach ( $wp_errors as $e ) {
				   echo '<p><strong>' . $e . '</strong></p>';
				}
				echo '</div>';
			} else {
		?>
			<div class="trksit_col left">
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Here\'s Your Tracking URL:' ); ?></span></h3>
					<div class="inside">
						<p><strong>Copy the link below and share it where your heart desires!</strong></p>
						<div class="input-row">
							<input id="shortened-link-input" type="text" onclick="this.setSelectionRange( 0, this.value.length );" value="<?php echo $shortURL; ?>" readonly />
						</div>
						<p>
							<a class="trksit-copy-btn" id="trks-copy-btn" data-trksit-link="<?php echo $shortURL; ?>"><?php _e( 'Copy to Clipboard' );?></a>
						</p>
						<p>
							<a class="trksit-copy-btn" id="trks-copy-btn-test" data-trksit-link="<?php echo $shortURL . '/test'; ?>"><?php _e( 'Copy Test URL *' );?></a> <small>* Test will not trigger analytics or count hit</small>
						</p>
					</div>
				</div>
				<p><a class="button button-primary button-large" href="./admin.php?page=trksit-generate">Create Another link</a></p>
			</div>
	<?php
		  	}
		/* ---- Step 2 ---- */
		} else if ( isset( $_POST['destination_url'] ) && ( !empty( $_POST ) && check_admin_referer( 'trksit_generate_url','trksit_generate_step1' ) ) ) {
			$trksit = new trksit();
			//if now is after when it expires
			if ( time() > get_option( 'trksit_token_expires' ) ) {
				$trksit->wp_trksit_resetToken();
			}
	?>
			<h2 class="nav-tab-wrapper">
				<a href="./admin.php?page=trksit-generate&url=<?php echo urlencode( $_POST['destination_url'] ); ?>" class="nav-tab">Step 1</a>
				<a href="#" class="nav-tab nav-tab-active">Step 2</a>
				<a href="#" class="nav-tab nav-tab-disabled">Step 3</a>
			</h2>
			<?php
				if ( !isset( $_SESSION['opengraph'] ) ) {
					$url_segments = parse_url( $_POST['destination_url'] );
					$dest_url = $_POST['destination_url'];
					if ( !isset( $url_segments['scheme'] ) ) {
						$dest_url = 'http://' . $url_segments['path'];
					}
					$trksit->wp_trksit_parseURL( $dest_url );
					$trksit_title = substr( $trksit->wp_trksit_getTitle(), 0, 100 );
					$trksit_description = substr( $trksit->wp_trksit_getDescription(), 0, 157 );
					$trksit_images = $trksit->imgArray;
					if ( strlen( $trksit->wp_trksit_getDescription() ) > 157 ) {
						$trksit_description .= '...';
					}
				} else {
					$trksit_title = '';
					if ( isset( $_SESSION['opengraph']['og:title'] ) ) {
						$trksit_title = $_SESSION['opengraph']['og:title'];
					}
					$trksit_description = '';
					if ( isset( $_SESSION['opengraph']['og:description'] ) ) {
						$trksit_description = $_SESSION['opengraph']['og:description'];
					}
					$trksit_imgarray = $_SESSION['opengraph']['og:image'];
					$trksit_images = array();
					foreach ( $trksit_imgarray as $tk_img ) {
						array_push( $trksit_images, $tk_img['og:image:url'] );
					}
				}
				$trks_error = $trksit->wp_trksit_getErrors();
				if ( is_wp_error( $trks_error ) ) {
					echo '<div class="trksit-alert danger">
						      <p><strong>' . $trks_error->get_error_message() . '</strong></p>
						      <p><a href="/wp-admin/admin.php?page=trksit-generate">Click here to try a new link</a></p>
						  </div>';
				} else {
					$clean_title = preg_replace( "/[^A-Za-z0-9 ]/", "", $trksit_title );
					$clean_title = preg_replace( "/\s+/", " ", $clean_title );
			?>
				<form class="trksit-form"  method="post">
					<input type="hidden" name="destination_url" value="<?php echo $_POST['destination_url'];?>">
					<input type="hidden" name="meta_title" id="title" maxlength="100" value="<?php echo $trksit_title; ?>">
					<input type="hidden" name="meta_description" id="description" rows="5" maxlength="255" value="<?php echo $trksit_description; ?>">
					<input type="hidden" name="date_created" value="<?php echo date( 'Y-m-d' );?>">
					<?php wp_nonce_field( 'trksit_generate_url', 'trksit_generate_step2' ); ?>
					<div class="trksit_col left">
						<div class="postbox">
							<h3 class="hndle"><span><?php _e( 'Analytics Tracking Data' ); ?></span></h3>
							<div class="inside">
								<!-- <button class="btn btn-small" id="advanced-toggle" data-toggle="button"><?php _e('Show Advanced Options'); ?></button> -->
								<!-- source -->
								<div class="input-row">
									<label for="source">
										<?php _e( 'Source:' ); ?>
										<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Use this field to define a unique source value to be sent into your Google Analytics dashboard.' ); ?>" data-original-title="<?php _e( 'Campaign Source' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
									</label>
									<select name="source" id="source_select">
									<?php
										$sources = maybe_unserialize( get_option( 'trksit_sources' ) );
										sort( $sources );
										for ( $i = 0; $i < count( $sources ); $i++ ) {
											$val = $sources[$i];
											echo '<option value="' . $val . '">' . $sources[$i] . '</option>';
										}
									?>
									</select>
								</div>
								<!-- medium -->
								<div class="input-row">
									<label for="medium">
										<?php _e( 'Medium:' ); ?>
										<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Use this field to define a unique medium value to be sent into your Google Analytics dashboard.' ); ?>" data-original-title="<?php _e( 'Campaign Medium' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
									</label>
									<!-- <input name="medium" id="medium" type="text" placeholder="ig: Social Media"> -->
									<select name="medium" id="source_select">
									<?php
										$medium = maybe_unserialize( get_option( 'trksit_medium' ) );
										sort($medium);
										for ( $i = 0; $i < count( $medium ); $i++ ) {
											$val = $medium[$i];
											echo '<option value="' . $val . '">' . $medium[$i] . '</option>';
										}
									?>
									</select>
								</div>
								<!-- campaign -->
								<div class="input-row">
									<label for="campaign">
										<?php _e( 'Campaign Name:' ); ?>
										<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Use this field to define a unique campaign value to be sent into your Google Analytics dashboard.' ); ?>" data-original-title="<?php _e( 'Campaign Name' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
									</label>
									<input name="campaign" id="campaign" type="text" placeholder="ig: Something Unique..." value="<?php echo $clean_title; ?>">
								</div>
							</div>
						</div>
						<div id="advanced-tracking-panel-lll" class="postbox">
							<h3 class="hndle"><span><?php _e( 'Advanced Tracking Options' ); ?></span></h3>
							<div class="inside">
								<div class="input-row">
									<!-- content -->
									<label for="content">
										<?php _e( 'Content:' ); ?>
										<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Use this field to define a unique conetnt value to be sent into your Google Analytics dashboard.' ); ?>" data-original-title="<?php _e( 'Content' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
									</label>
									<input name="content" id="content" type="text" placeholder="ig: Something Unique...">
								</div>
								<!-- term -->
								<div class="input-row">
									<label class="control-label" for="term">
										<?php _e( 'Term (keyword):' ); ?>
										<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'Use this field to define a unique term (keyword) value to be sent into your Google Analytics dashboard.' ); ?>" data-original-title="<?php _e( 'Term (keyword)' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
									</label>
									<input name="term" id="term" type="text" placeholder="ig: Something Unique...">
								</div>
							</div>
						</div>
						<div class="postbox">
							<h3 class="hndle"><span><?php _e( 'Attach Remarketing & Custom Scripts' ); ?></span></h3>
							<div class="inside">
								<?php
									$scripts = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "trksit_scripts WHERE script_error != 1 ORDER BY date_created DESC, label DESC" );
									if ( count( $scripts ) ) {
										$count = 1;
										echo '<ul class="list-unstyled">';
										foreach ( $scripts as $script ) {
											$even_odd = ($count&1 ? 'odd' : 'even');
											echo '<li><label class="checkbox ' . $even_odd . '"><input type="checkbox" name="trksit_scripts[]" value="' . $script->script_id . '">' . stripslashes( $script->label ) . '</label></li>';
											$count++;
										}
										echo '</ul>';
									} else {
										_e( '<div class="trksit-alert info"><p>You haven\'t setup any remarketing lists or custom scripts yet!</p> <a href="./admin.php?page=trksit-settings&tab=scripts" class="button button-primary">Create one now!</a></div>' );
									}
								?>
							</div>
						</div>
						<?php
							/*
								foreach( $trksit->wp_trksit_getOGMetaArray() as $property => $content ){
									echo sprintf( '<input type="hidden" name="%s" value="%s">', $property, $content );
								}
							*/
						?>
					</div>
					<div class="trksit_col right">
						<div class="postbox">
							<h3 class="hndle"><span><?php _e( 'Sharing Preview' ); ?></span></h3>
							<div class="inside">
								<p><?php _e( 'Below is an example of how your link will appear when shared on a social media channel such as Facebook or Linkedin.' ); ?></p>
								<div id="preview">
									<div class="image"></div>
									<div class="content">
										<div class="title"></div>
										<div class="url">
											<?php
												echo substr( $_POST['destination_url'], 0, 38 );
												if ( strlen( $_POST['destination_url'] ) > 40 ) {
													echo '...' ;
												}
											?>
										</div>
										<div class="description"></div>
									</div>
									<div class="clearfix"></div>
								</div><!-- #preview -->
								<select name="meta_image" id="preview-image-picker">
									<?php
										foreach( $trksit_images as $image ) {
											echo sprintf( '<option data-img-src="%s" value="%s">%s</option>', $image, $image, $image );
										}
									?>
								</select>
							</div>
						</div>
					</div>
					<div class="trksit_col full">
						<button type="submit" class="button button-primary button-large" data-loading-text="<?php _e( 'Please wait...' ); ?>">
							<?php _e( 'Generate Tracking URL...' ); ?>
						</button>
					</div>
				</form>
	<?php
			}
		/* ---- Step 1 ---- */
		} else {
		//if now is after when it expires
		if ( time() > get_option( 'trksit_token_expires' ) ) {
			$trksit = new trksit();
			$trksit->wp_trksit_resetToken();
		}
		unset( $_SESSION['opengraph'] );
	?>
		<h2 class="nav-tab-wrapper">
			<a href="#" class="nav-tab nav-tab-active">Step 1</a>
			<a href="#" class="nav-tab">Step 2</a>
			<a href="#" class="nav-tab nav-tab-disabled">Step 3</a>
		</h2>
		<?php
			if ( isset( $_SESSION['trksit_error'] ) ) {
				echo '<div class="trksit-alert danger">
					      <p><strong>' . $_SESSION['trksit_error'] . '</strong></p>
					  </div>';
				unset( $_SESSION['trksit_error'] );
			}
		?>
		<div class="trksit_col left">
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Step 1: What do you want to share?' ); ?></span></h3>
				<div class="inside">
					<p><?php _e( 'Enter the website URL you would like the link to resolve to. <strong>Don\'t add any extra tracking parameters on this link.</strong>' ); ?></p>
					<form method="post" class="trksit-form step-1" id="trksit-generate">
						<div class="input-row input-large input-appended">
							<label for="url">
								<?php _e( 'Destination URL:' ); ?>
								<a class="trksit-help" data-toggle="popover" data-content="<?php _e( 'The URL you want to share and track.' ); ?>" data-original-title="<?php _e( 'Destination URL' ); ?>"><i class="dashicons dashicons-editor-help"></i></a>
							</label>
							<?php wp_nonce_field( 'trksit_generate_url', 'trksit_generate_step1' ); ?>
							<input name="destination_url" id="url" type="text" class="url" value="<?php if ( isset( $_GET['url'] ) ) { echo $_GET['url']; } ?>" <?php if ( !isset( $_GET['url'] ) ) { echo 'placeholder="http://"'; } ?> focus />
							<button type="submit" class="button button-primary button-large margin-t" id="trksit-generate-submit-step-1">
								<?php _e( 'Proceed to step 2 &rsaquo;' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	  <?php
	  }
   ?>
<style>
	#trksit-loading-indicator {
		display: none;
	}
</style>
</div><!-- #trksit-wrap -->
<?php endif; ?>
