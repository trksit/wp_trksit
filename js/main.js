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
	$(".trksit-copy-btn").on('click', function (e) {
	
		e.preventDefault();
		
	}).each(function () {
	
	$(this).zclip({
		path: "/wp-content/plugins/wp_trksit/js/swf/ZeroClipboard.swf",
		copy: function() { return $(this).data("trksit-link"); },
		afterCopy:function(){ alert($(this).data("trksit-link") + " has been copied to your clipboard."); },
		clickAfter: false
	});
	
	});
	
	// SETTINGS
	var $jquery_options_field = $("#trksit_jquery");
	$("#trksit_jquery_radio button").on('click', function(e){
		$jquery_options_field.val($(this).val());
	});
	
	
	
});