<?php
   //error_reporting(E_ALL);
   //ini_set('display_errors', 1);
?>

<?php if($_GET['page'] == 'trksit-generate'): ?>

<div class="wrap" id="trksit-wrap"><!--wrap-->
   <h2 class="trksit-header top"><img src="<?php echo plugins_url( '/wp_trksit/img/trksit-icon-36x36.png' , dirname(__FILE__) ); ?>" class="trksit-header-icon" /><?php echo __( 'Generate a New Trks.it URL', 'trksit_menu' ); ?></h2>

   <?php
	  /* ---- Step 3 ---- */
	  if($_POST["meta_title"] && ( !empty($_POST) && check_admin_referer('trksit_generate_url','trksit_generate_step2') )){

		 $trksit = new trksit();
		 if(time() > get_option('trksit_token_expires')){
			$trksit->wp_trksit_resetToken();
		 }
		 $shortURL = $trksit->wp_trksit_shortenURL($_POST);
	  ?>
	  <div class="trksit_tab_nav">
		 <ul>
			<li><a href="./admin.php?page=trksit-generate">Step 1</a></li>
			<li><a href="#">Step 2</a></li>
			<li class="active"><a href="#">Step 3</a></li>
		 </ul>
	  </div>
	  <div class="trksit_col left">
		 <h2><?php _e('Here\'s Your Tracking URL:'); ?> <?php echo $shortURL; ?></h2>
	  </div>
	  <div class="trksit_col right"></div>

	  <?php
		 /* ---- Step 2 ---- */
	  } else if($_POST['destination_url'] && ( !empty($_POST) && check_admin_referer('trksit_generate_url','trksit_generate_step1') )){

		 $trksit = new trksit();
		 //if now is after when it expires
		 if(time() > get_option('trksit_token_expires')){
			$trksit->wp_trksit_resetToken();
		 }
		 $trksit->wp_trksit_parseURL($_POST['destination_url']);

	  ?>
	  <div class="trksit_tab_nav">
		 <ul>
			<li><a href="./admin.php?page=trksit-generate&url=<?php echo urlencode($_POST['destination_url']);?>">Step 1</a></li>
			<li class="active"><a href="#">Step 2</a></li>
			<li><a href="#">Step 3</a></li>
		 </ul>
	  </div>

	  <form class="trksit-form"  method="post">
		 <div class="trksit_col left">

			<div class="trksit-section">

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
					 <input name="meta_title" id="title" type="text" maxlength="100" value="<?php echo substr($trksit->wp_trksit_getTitle(), 0, 100);?>">
				  </div>
			   </div>
			   <div class="control-group">
				  <label class="control-label" for="description"><?php _e('Description:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('The description to be used when sharing the content. This is pulled from the pages Open Graph data if it already exists. If not, it defaults to the meta description of the page.'); ?>" data-original-title="<?php _e('Description'); ?>"><i class="icon-question-sign"></i></a></label>
				  <div class="controls">
					 <textarea name="meta_description" id="description" rows="5" maxlength="255"><?php echo substr($trksit->wp_trksit_getDescription(), 0, 157); if(strlen($trksit->wp_trksit_getDescription()) > 157){echo "...";}?></textarea>
				  </div>
			   </div>
			</div>

			<div class="trksit-section">

			   <h2 class="trksit-header"><?php _e('Analytics Tracking Data'); ?> <button class="btn btn-small" id="advanced-toggle" data-toggle="button"><?php _e('Show Advanced Options'); ?></button></h2>

			   <div class="control-group">
				  <label class="control-label" for="campaign"><?php _e('Campaign Name:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique campaign value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Campaign Name'); ?>"><i class="icon-question-sign"></i></a></label>
				  <div class="controls">
					 <input name="campaign" id="campaign" type="text" placeholder="ig: Something Unique...">
				  </div>
			   </div>

			   <div id="advanced-tracking-panel">
				  <h3><?php _e('Advanced Tracking Options'); ?></h3>
				  <div class="control-group">
					 <label class="control-label" for="source"><?php _e('Source:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique source value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Campaign Source'); ?>"><i class="icon-question-sign"></i></a></label>
					 <div class="controls">
						<select name="source" id="source">
						   <option value="" selected><?php _e('Auto Detect (recommended)'); ?></option>
						   <option value="custom"><?php _e('Custom'); ?></option>
						</select>
					 </div>
				  </div>
				  <div class="control-group">
					 <label class="control-label" for="medium"><?php _e('Medium:'); ?> <a class="trksit-help" data-toggle="popover" data-content="<?php _e('Use this field to define a unique medium value to be sent into your Google Analytics dashboard.'); ?>" data-original-title="<?php _e('Campaign Medium'); ?>"><i class="icon-question-sign"></i></a></label>
					 <div class="controls">
						<input name="medium" id="medium" type="text" placeholder="ig: Social Media">
					 </div>
				  </div>
			   </div>

			</div>

			<div class="trksit-section">
			   <h2 class="trskit-header"><?php _e('Add Scripts'); ?></h2>
			   <?php
				  $scripts = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "trksit_scripts ORDER BY date_created DESC, label DESC" );

				  if(count($scripts)){
					 $count = 1;
					 foreach($scripts as $script):

					 $even_odd = ($count&1 ? 'odd' : 'even');

					 echo '<label class="checkbox ' . $even_odd . '"><input type="checkbox" name="trksit_scripts[]" value="' . $script->script_id . '">' . stripslashes($script->label) . '</label>';

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
			   foreach($trksit->wp_trksit_getOGMetaArray() as $property => $content){
				  echo sprintf('<input type="hidden" name="%s" value="%s">', $property, $content);
			   }
			?>

			<?php wp_nonce_field('trksit_generate_url','trksit_generate_step2'); ?>
			<input type="submit" class="btn btn-success" data-loading-text="<?php _e('Please wait...'); ?>" value="<?php _e('Generate Tracking URL...'); ?>" />

		 </div>

		 <div class="trksit_col right">

			<h2><?php _e('Sharing Preview'); ?></h2>
			<p><?php _e('Below is an example of how your link will appear when shared on a social media channel such as Facebook or Twitter. Use the fields to the left to customize to your liking!'); ?></p>

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
					 foreach($trksit->imgArray as $image){
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
					 <input name="destination_url" id="url" type="text" class="url" value="<?php if($_GET['url']){ echo $_GET['url']; }else{ echo 'http://'; } ?>" focus />
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


</div><!-- #trksit-wrap -->
<?php endif; ?>
