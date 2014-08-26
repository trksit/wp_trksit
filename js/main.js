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
   $('#preview .image img').attr('src', $('select#preview-image-picker option:selected').val());

   //TITLE
   //change preview on keyup
   $('input#title').keyup(function(){
	  $('#preview .content .title').text($(this).val());
   });
   $('#preview .content .title').text($('input#title').val());

   //DESCRIPTION
   $('textarea#description').keyup(function(){
	  $('#preview .content .description').text($(this).val());
   });
   $('#preview .content .description').text($('textarea#description').val());

   // COPY BUTTONS

   // Set location of SWF File
   ZeroClipboard.config( { swfPath: "/wp-content/plugins/wp_trksit/js/swf/ZeroClipboard.swf" } );

   var client = new ZeroClipboard($(".trksit-copy-btn"));
   client.on("ready", function(event){

	  // Use the data attribute associated with the link
	  client.on("copy", function(event){
		 event.clipboardData.setData('text/plain', event.target.getAttribute('data-trksit-link'));
	  });

	  client.on("aftercopy", function(event){
		 console.log('Copied text to clipboard: ' + event.data['text/plain']);
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
	  $('#trks_dashboard').dataTable({
		 "fnInitComplete": function(oSettings, json) {
		 },
		 "aaSorting": [[ 1, "desc" ]],
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
});
