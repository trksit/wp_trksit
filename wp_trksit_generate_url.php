<?php
	  ob_start();
	  echo '<div id="loading-indicator" style="margin: 0px auto; width: 200px; text-align: center; padding-top: 200px;">';
	  echo '<h2>Loading...</h2><br />';
	  echo '<img src="' . plugins_url( '/wp_trksit/img/loading.gif' , dirname(__FILE__) ) . '" alt="Loading" /></div>';
	  trksit_flush_buffers();
?>

<?php if($_GET['page'] == 'trksit-generate'): ?>


<div class="wrap" id="trksit-wrap"><!--wrap-->
   <h2 class="trksit-header top"><img src="<?php echo plugins_url( '/wp_trksit/img/trksit-icon-36x36.png' , dirname(__FILE__) ); ?>" class="trksit-header-icon" /><?php echo __( 'Generate a New trks.it URL', 'trksit_menu' ); ?></h2>
   <?php
	  /* ---- Step 3 ---- */
	  if(isset($_POST['meta_title']) && ( !empty($_POST) && check_admin_referer('trksit_generate_url','trksit_generate_step2') )){

		 $trksit = new trksit();
		 if(time() > get_option('trksit_token_expires')){
			$trksit->wp_trksit_resetToken();
		 }
		 $shortURL = $trksit->wp_trksit_shortenURL($_POST);
		 $trks_error = $trksit->wp_trksit_getErrors();
	  ?>
	  <div class="trksit_tab_nav">
		 <ul>
			<li><a href="./admin.php?page=trksit-generate">Step 1</a></li>
			<li><a href="#">Step 2</a></li>
			<li class="active"><a href="#">Step 3</a></li>
		 </ul>
	  </div>
	  <?php
		 if(is_wp_error($trks_error)){
			$wp_errors = $trks_error->get_error_messages();
			foreach($wp_errors as $e){
			   echo '<h2 class="trksit-header">'.$e.'</h2>';
			}
		 } else {
	  ?>
	  <!-- <div class="trksit_col left"> -->
		 <h2>
			<!-- trksit-admin-button btn btn-success -->
			<?php _e('Here\'s Your Tracking URL:'); ?> <?php echo $shortURL; ?>
		 </h2>
		 <a class="trksit-copy-btn" id="trks-copy-btn" data-trksit-link="<?php echo $shortURL; ?>"><?php _e('Copy to Clipboard');?></a>
		 <p>
			<a class="trksit-copy-btn" id="trks-copy-btn-test" data-trksit-link="<?php echo $shortURL . '/test'; ?>"><?php _e('Copy Test URL *');?></a>
			<a class='trksit-copy-btn' href='./admin.php?page=trksit-generate'>Create another link</a>
		 </p>
		 <p><small>* Test will not trigger analytics or count hit</small></p>
	  <!-- </div>
	  <div class="trksit_col right"></div> -->
	  <?php } ?>

	  <?php
		 /* ---- Step 2 ---- */
	  } else if(isset($_POST['destination_url']) && ( !empty($_POST) && check_admin_referer('trksit_generate_url','trksit_generate_step1') )){

		 $trksit = new trksit();
		 //if now is after when it expires
		 if(time() > get_option('trksit_token_expires')){
			$trksit->wp_trksit_resetToken();
		 }

	  ?>
	  <div class="trksit_tab_nav">
		 <ul>
			<li><a href="./admin.php?page=trksit-generate&url=<?php echo urlencode($_POST['destination_url']);?>">Step 1</a></li>
			<li class="active"><a href="#">Step 2</a></li>
			<li><a href="#">Step 3</a></li>
		 </ul>
	  </div>


	  <?php
		 if(!isset($_SESSION['opengraph'])){
			$trksit->wp_trksit_parseURL($_POST['destination_url']);
			$trksit_title = substr($trksit->wp_trksit_getTitle(), 0, 100);
			$trksit_description = substr($trksit->wp_trksit_getDescription(), 0, 157);
			$trksit_images = $trksit->imgArray;
			if(strlen($trksit->wp_trksit_getDescription()) > 157) $trksit_description .= "...";
		 } else {
			$trksit_title = '';
			if(isset($_SESSION['opengraph']['og:title'])){
			   $trksit_title = $_SESSION['opengraph']['og:title'];
			}
			$trksit_description = '';
			if(isset($_SESSION['opengraph']['og:description'])){
			   $trksit_description = $_SESSION['opengraph']['og:description'];
			}
			$trksit_imgarray = $_SESSION['opengraph']['og:image'];
			$trksit_images = array();
			foreach($trksit_imgarray as $tk_img){
			   array_push($trksit_images, $tk_img['og:image:url']);
			}

		 }
		 $trks_error = $trksit->wp_trksit_getErrors();
		 if(is_wp_error($trks_error)){
			echo '<h2 class="trksit-header">' . $trks_error->get_error_message() . '</h2>';
			echo '<p><a href="/wp-admin/admin.php?page=trksit-settings">Please visit settings to correct</a></p>';
		 } else {
	  ?>

	  <form class="trksit-form"  method="post">
	  <input type="hidden" name="destination_url" value="<?php echo $_POST['destination_url'];?>">
	  <input type="hidden" name="meta_title" id="title" maxlength="100" value="<?php echo $trksit_title; ?>">
	  <input type="hidden" name="meta_description" id="description" rows="5" maxlength="255" value="<?php echo $trksit_description; ?>">
		 <div class="trksit_col left">
			<!--div class="trksit-section">
			   <h2 class="trksit-header"><?php _e('Sharing Options'); ?></h2>
			   <div class="control-group">
				  <label class="control-label"><?php _e('Destination URL:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('The user will end up at this url.'); ?>" data-original-title="<?php _e('Destination URL'); ?>"><i class="icon-question-sign"></i></a></label>
				  <div class="controls">
					 <a href="<?php echo $_POST['destination_url'];?>" target="_blank"><span class="uneditable-input input-span6" id="final-url"><?php echo $_POST['destination_url'];?></span></a>
					 <input type="hidden" name="destination_url" value="<?php echo $_POST['destination_url'];?>">
				  </div>
			   </div>
			   <div class="control-group">
				  <label class="control-label" for="title"><?php _e('Title:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('The title to be used when sharing the content. This is pulled from the pages Open Graph data if it already exists. If not, it defaults to the title of the page.'); ?>" data-original-title="<?php _e('Title'); ?>"><i class="icon-question-sign"></i></a></label>
				  <div class="controls">
					 <input name="meta_title" id="title" type="text" maxlength="100" value="<?php echo $trksit_title; ?>">
				  </div>
			   </div>
			   <div class="control-group">
				  <label class="control-label" for="description"><?php _e('Description:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('The description to be used when sharing the content. This is pulled from the pages Open Graph data if it already exists. If not, it defaults to the meta description of the page.'); ?>" data-original-title="<?php _e('Description'); ?>"><i class="icon-question-sign"></i></a></label>
				  <div class="controls">
					 <textarea name="meta_description" id="description" rows="5" maxlength="255"><?php echo $trksit_description; ?></textarea>
				  </div>
			   </div>
			</div-->
			<div class="trksit-section">
			   <h2 class="trksit-header"><?php _e('Analytics Tracking Data'); ?> <!-- <button class="btn btn-small" id="advanced-toggle" data-toggle="button"><?php _e('Show Advanced Options'); ?></button> --></h2>

			   	<! -- source -->
				<div class="control-group">
					<label class="control-label" for="source"><?php _e('Source:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique source value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Campaign Source'); ?>"><i class="icon-question-sign"></i></a></label>
					<div class="controls">
						<select name="source" id="source_select">
							<?php
							$sources = maybe_unserialize(get_option("trksit_sources"));
							sort($sources);
							for($i = 0; $i < count($sources); $i++){
								$val = strtolower($sources[$i]);
								echo '<option value="'.$val.'">'.$sources[$i].'</option>';
							}
							?>
						</select>
					</div>
				</div>

			   	<! -- medium -->
				<div class="control-group">
					<label class="control-label" for="medium"><?php _e('Medium:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique medium value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Campaign Medium'); ?>"><i class="icon-question-sign"></i></a></label>
					<div class="controls">
						<!-- <input name="medium" id="medium" type="text" placeholder="ig: Social Media"> -->
						<select name="medium" id="source_select">
							<?php
							$medium = maybe_unserialize(get_option("trksit_medium"));
							sort($medium);
							for($i = 0; $i < count($medium); $i++){
								$val = strtolower($medium[$i]);
								echo '<option value="'.$val.'">'.$medium[$i].'</option>';
							}
							?>
						</select>

					</div>
				</div>

				<!-- campaign -->
				<div class="control-group">
				  <label class="control-label" for="campaign"><?php _e('Campaign Name:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique campaign value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Campaign Name'); ?>"><i class="icon-question-sign"></i></a></label>
				  <div class="controls">
					 <input name="campaign" id="campaign" type="text" placeholder="ig: Something Unique...">
				  </div>
			   </div>

			   <div id="advanced-tracking-panel-lll">
				  <h3><?php _e('Advanced Tracking Options'); ?></h3>

				<!-- content -->
				<div class="control-group">
				  <label class="control-label" for="content"><?php _e('Content:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique conetnt value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Content'); ?>"><i class="icon-question-sign"></i></a></label>
				  <div class="controls">
					 <input name="content" id="content" type="text" placeholder="ig: Something Unique...">
				  </div>
			   </div>

				<!-- term -->
				<div class="control-group">
				  <label class="control-label" for="term"><?php _e('Term (keyword):'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique term (keyword) value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Term (keyword)'); ?>"><i class="icon-question-sign"></i></a></label>
				  <div class="controls">
					 <input name="term" id="term" type="text" placeholder="ig: Something Unique...">
				  </div>
			   </div>


			   </div>

			</div>

			<div class="trksit-section">
			   <h2 class="trskit-header"><?php _e('Attach Remarketing & Custom Scripts'); ?></h2>
			   <?php
				  $scripts = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "trksit_scripts WHERE script_error != 1 ORDER BY date_created DESC, label DESC" );

				  if(count($scripts)){
					 $count = 1;
					 foreach($scripts as $script):

					 $even_odd = ($count&1 ? 'odd' : 'even');

					 echo '<label class="checkbox ' . $even_odd . '"><input type="checkbox" name="trksit_scripts[]" value="' . $script->script_id . '">' . stripslashes($script->label) . '</label>';

					 $count++;

					 endforeach;

				  }else{
					 _e('You haven\'t setup any remarketing lists or custom scripts yet! <a href="./admin.php?page=trksit-settings&tab=scripts">Click here to create one now!</a>');
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

			<?php wp_nonce_field('trksit_generate_url','trksit_generate_step2'); ?>
			<input type="submit" class="btn btn-success" data-loading-text="<?php _e('Please wait...'); ?>" value="<?php _e('Generate Tracking URL...'); ?>" />

		 </div>

		 <div class="trksit_col right">

			<h2><?php _e('Sharing Preview'); ?></h2>
			<p><?php _e('Below is an example of how your link will appear when shared on a social media channel such as Facebook or Linkedin.'); ?></p>

			<div id="preview">
			   <div class="image"><img src=""></div>
			   <div class="content">
				  <label for="title"><div class="title"></div></label>
				  <div class="url"><?php echo substr($_POST['destination_url'], 0, 38); if(strlen($_POST['destination_url']) > 40){echo "...";}?></div>
				  <label for="description"><div class="description"></div></label>
			   </div><div class="clear"></div>
			</div><!-- #preview -->

			<div class="control-group controls">
			   <select name="meta_image" id="preview-image-picker">
				  <?php
					 foreach($trksit_images as $image){
						echo sprintf('<option data-img-src="%s" value="%s">%s</option>', $image, $image, $image);
					 }
				  ?>
			   </select>
			</div>

			<div class="clearfix"></div>

		 </div>
		 <div class="clearfix"></div>
		 <div class="trksit_col_full">
			<input type="hidden" name="date_created" value="<?php echo date('Y-m-d');?>">
		 </div>

	  </form>
	  <?php } ?>

	  <?php
		 /* ---- Step 1 ---- */
	  } else {
	  ?>
	  <?php
		 //if now is after when it expires
		 if(time() > get_option('trksit_token_expires')){
			$trksit = new trksit();
			$trksit->wp_trksit_resetToken();
		 }

		 unset($_SESSION['opengraph']);

	  ?>

	  <div class="trksit_tab_nav">
		 <ul>
			<li class="active"><a href="#">Step 1</a></li>
			<li><a href="#">Step 2</a></li>
			<li><a href="#">Step 3</a></li>
		 </ul>
	  </div>

	  <div class="trksit_col left">

		 <h2 class="trksit-header"><?php _e('Step 1: What do you want to share?'); ?></h2>
		 <p><?php _e('Enter the website URL you would like the link to resolve to. Don\'t add any extra tracking parameters on this link.'); ?></p>

		 <form method="post" class="trksit-form step-1" id="trksit-generate">
			<div class="control-group">
			   <label class="control-label" for="url"><?php _e('Destination URL:'); ?></label>
			   <div class="controls">
				  <div class="input-append">
					 <?php wp_nonce_field('trksit_generate_url','trksit_generate_step1'); ?>
					 <input name="destination_url" id="url" type="text" class="url" value="<?php if(isset($_GET['url'])){ echo $_GET['url']; }else{ echo 'http://'; } ?>" focus />
					 <input type="submit" class="btn btn-success" id="trksit-generate-submit-step-1" value="<?php _e('Go!'); ?>" />
				  </div>
			   </div>
			</div>
		 </form>

	  </div>

	  <div class="trksit_col right">

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
<?php endif; ?>
