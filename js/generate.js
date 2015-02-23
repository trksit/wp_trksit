jQuery(document).ready(function($){	

	// STEP 1

	$('#url').focus();

	// STEP 2

	$('#advanced-toggle').click(function(e){
	
		e.preventDefault();
		
		$(this).toggleClass('advanced-on');
		
		$('#advanced-tracking-panel').toggle();
		
		if($(this).text() == 'Show Advanced Options'){
			$(this).text('Hide Advanced Options');
			//$(this).addClass('btn-danger');
		}else{
			$(this).text('Show Advanced Options');
			//$(this).removeClass('btn-danger');
		}
		
	});

  $('select#source').change(function(){
      if ($(this).val() == 'custom') {
          $('<input/>', {
              'name' : $(this).attr('name'),
              'type' : 'text',
              'placeholder' : 'ig: Facebook'
          }).insertAfter($(this)); 
          $(this).attr('name', '');
      } else {
          $('input[name=source]').remove();
          $(this).attr('name', 'source');
      }
  });


	
	// VALIDATION
	
	// STEP 1
	
	$('#trksit-generate.step-1').validate({
		onfocusout: false,
		rules: {
			destination_url: {
			  required: true,
			  url: false
			}
		},
		messages: {
      errorClass: "alert-danger",
			destination_url: {
			  required: "We can't shorten a link if you don't give us one!",
			  url: "Invalid URL, please check and try again."
			}
		},
		errorPlacement: function(error, element) {
			error.insertAfter("#trksit-generate-submit-step-1");
		}
	});
	
});
