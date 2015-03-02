jQuery(document).ready(function($){

   // HELP POPOVERS
   var $help_pops = $('.trksit-help');

   $help_pops.popover({
	  'placement':'right',
	  'trigger':'click'
   });

   $help_pops.click(function(){
	  return false;
   });

   $(document).on('click', function(){
	  $help_pops.popover('hide');
   });

   //PREVIEW FUNCTIONS

   $('#preview-image-picker').imagepicker();

   //IMAGE
   //when the image select changes, change the image preview
   $('select#preview-image-picker').change(function(){
	   $('#preview .image img').attr('src', $('select#preview-image-picker option:selected').val());
   });

   if($('select#preview-image-picker option').length){
   	   var imgsrc = $('select#preview-image-picker option:selected').val();
   	   var imagetag = '<img src="' + imgsrc + '" alt="" />';
   	   $("#preview .image").prepend(imagetag);
   }

   //TITLE
   //change preview on keyup
   $('input#title').keyup(function(){
	  $('#preview .content .title').text($(this).val());
   });
   $('#preview .content .title').text($('input#title').val());

   //DESCRIPTION
   $('input#description').keyup(function(){
	  $('#preview .content .description').text($(this).val());
   });
   $('#preview .content .description').text($('input#description').val());

   // COPY BUTTONS

   // Set location of SWF File
   ZeroClipboard.config( { swfPath: "/wp-content/plugins/wp_trksit/js/swf/ZeroClipboard.swf" } );

   var client = new ZeroClipboard($(".trksit-copy-btn"));
   client.on("ready", function(event){

	  // Use the data attribute associated with the link
	  client.on("copy", function(event){
		 event.clipboardData.setData('text/plain', event.target.getAttribute('data-trksit-link'));
	  });

   });
   client.on( 'error', function(event) {
	  console.log( 'ZeroClipboard error of type "' + event.name + '": ' + event.message );
	  ZeroClipboard.destroy();
   } );


   // SETTINGS
   var $jquery_options_field = $("#trksit_jquery");
   $("#trksit_jquery_radio button").on('click', function(e){
	  $jquery_options_field.val($(this).val());
   });

   //DASHBOARD
   //$('#trks_dashboard').css('display','none');
   $('#trks_dashboard_par').css('display','block');
   if( jQuery().dataTable ){
	  /*
	   *$('#trks_dashboard').dataTable({
	   *   "fnInitComplete": function(oSettings, json) {
	   *   },
	   *   "aaSorting": [[ 0, "desc" ]],
	   *});
	   */
	  var ajaxurl = "/wp-admin/admin-ajax.php";
	  $('#trks_dashboard').dataTable({
	  	  "ajax": ajaxurl + "?action=nopriv_generate_datatable",
	  	  "order": [[ 0, "desc" ]]
	  });
   }

   if( jQuery().datepicker ){
	  $("#trksit_start_date" ).datepicker({
		 defaultDate: "+1w",
		 changeMonth: true,
		 numberOfMonths: 1,
		 onClose: function( selectedDate ) {
			$( "#trksit_end_date" ).datepicker( "option", "minDate", selectedDate );
		 }
	  });

	  $("#trksit_end_date" ).datepicker({
		 defaultDate: "+1w",
		 changeMonth: true,
		 numberOfMonths: 1,
		 onClose: function( selectedDate ) {
			$( "#trksit_start_date" ).datepicker( "option", "maxDate", selectedDate );
		 }
	  });
   }

   //change the name of trks.it menu (change the name of the first link to Dashboard)
   $('li#toplevel_page_trksit-dashboard ul.wp-submenu a.wp-first-item').text('Dashboard');

   $("#add-script").click(function(){
   	  // clean the form out
   	  $("#trksit_script_label").val("");
   	  $("#trksit_script_platform option:selected").removeAttr("selected");
   	  $("#trksit_script").val("");
   	  $("#script-id").val("");
   });

   $("#script_cancel").click(function(){
   	  var url = $(this).attr('data-url');
   	  window.location = url;
   });

	$("#trksit_settings_form").validate();
	$("#trksit_add_script_form").validate();

	//Makes IE honor autofocus attribute on inputs
	$('[autofocus]:not(:focus)').eq(0).focus();

	//Handle "other" platform
	$("#trksit_script_platform").change(function(){
		if($(this).val() == "other"){
			$("#trksit_script_platform_other").show();
		}
	});

});
