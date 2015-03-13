jQuery( document ).ready( function( $ ){

	// HELP POPOVERS
	var $help_pops = $( '.trksit-help' );

	$help_pops.popover({
		'placement': 'right',
		'trigger'  : 'click'
	});

	$help_pops.click(function(){
		return false;
	});

	$( document ).on( 'click', function(){
		$help_pops.popover( 'hide' );
	});

	//PREVIEW FUNCTIONS

	$( '#preview-image-picker' ).imagepicker();

	//IMAGE
	//when the image select changes, change the image preview
	$( 'select#preview-image-picker' ).change( function(){
		$( '#preview .image img' ).attr( 'src', $( 'select#preview-image-picker option:selected' ).val() );
	});

	if ( $( 'select#preview-image-picker option' ).length ) {
		var imgsrc = $( 'select#preview-image-picker option:selected' ).val();
		var imagetag = '<img src="' + imgsrc + '" />';
		$( '#preview .image' ).prepend(imagetag);
	}

	//TITLE
	//change preview on keyup
	$( 'input#title' ).keyup( function(){

		$( '#preview .content .title' ).text( $( this ).val() );

	});

	$( '#preview .content .title' ).text( $( 'input#title' ).val() );

	//DESCRIPTION
	$( 'input#description' ).keyup( function(){

		$( '#preview .content .description' ).text( $( this ).val() );

	});

	$( '#preview .content .description' ).text( $( 'input#description' ).val() );

	// Cookie functions
	function createCookie(name,value,days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
	}

	function readCookie(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
		}
		return null;
	}

	function eraseCookie(name) {
		createCookie(name,"",-1);
	}

	// COPY BUTTONS

	//Check to see if Flash is present
	var hasFlash = false;

	if(FlashDetect.installed){
		hasFlash = true;
		createCookie("trksit_flash_status", "true", 7);
	} else {
		hasFlash = false;
		eraseCookie("trksit_flash_status");
	}

	// Set location of SWF File
	ZeroClipboard.config( { swfPath: '/wp-content/plugins/wp_trksit/js/swf/ZeroClipboard.swf' } );

	// ZeroClipboard functions for non-generated items
	if ( hasFlash ) {
		var client = new ZeroClipboard($( '.trksit-copy-btn'));
		client.on( 'ready', function(event){

			// Use the data attribute associated with the link
			client.on( 'copy', function(event){
				event.clipboardData.setData( 'text/plain', event.target.getAttribute( 'data-trksit-link' ) );
			});

		});
		client.on( 'error', function(event) {
			console.log( 'ZeroClipboard error of type "' + event.name + '": ' + event.message );
			ZeroClipboard.destroy();
		} );
	}

	// ZeroClipboard functions to work with jQuery DataTable plugin generated links
	$( '#trks_dashboard' ).on( 'hover', 'tr', function( e ){
		if(hasFlash){
			e.preventDefault();
			var zc = new ZeroClipboard($( '.trksit-copy-btn'));

			zc.on( 'copy', function(event){
				event.clipboardData.setData( 'text/plain', event.target.getAttribute( 'href') );
			});

			zc.on( 'error', function(event){
				console.log( 'ZeroClipboard error of type "' + event.name + '": ' + event.message );
				ZeroClipboard.destroy();
			});
		}
	});

	$( '#trks_dashboard' ).on( 'click', '.trksit-copy-btn', function( e ){
		if ( hasFlash ) {
			e.preventDefault();
		}
	});

	// SETTINGS
	var $jquery_options_field = $( '#trksit_jquery' );
	$( '#trksit_jquery_radio button' ).on( 'click', function( e ){
		$jquery_options_field.val( $( this ).val() );
	});

	//DASHBOARD
	//$( '#trks_dashboard' ).css( 'display','none');
	//$( '#trks_dashboard_par' ).css( 'display','block' );

	if ( jQuery().dataTable ) {

		var ajaxurl = '/wp-admin/admin-ajax.php';

		$( '#trks_dashboard' ).dataTable({
			'processing'   : true,
			'serverSide'   : true,
			'paging'       : true,
			'searching'    : true,
			'ajax': {
				'url'      : ajaxurl + '?action=nopriv_generate_datatable',
				'type'     : 'GET'
			},
			'order' 	   : [[ 0, 'desc' ]],
			'aoColumnDefs' : [
						        { 'bSortable': false, 'aTargets': [ 2, 3, 4, 8 ] }
						     ],
			'language'     : {
							 	'emptyTable': 'You don\'t have any link data yet. <a href="/wp-admin/admin.php?page=trksit-generate">Click here to create your first link!</a>'
			                 }
		});

	}

	if ( jQuery().datepicker ) {

		$( '#trksit_start_date' ).datepicker({
			defaultDate      : '+1w',
			changeMonth      : true,
			numberOfMonths   : 1,
			onClose          : function( selectedDate ) {
				$( '#trksit_end_date' ).datepicker( 'option', 'minDate', selectedDate );
			}
		});

		$( '#trksit_end_date' ).datepicker({
			defaultDate      : '+1w',
			changeMonth      : true,
			numberOfMonths   : 1,
			onClose          : function( selectedDate ) {
				$( '#trksit_start_date' ).datepicker( 'option', 'maxDate', selectedDate );
			}
		});

	}

	//change the name of trks.it menu (change the name of the first link to Dashboard)
	//$( 'li#toplevel_page_trksit-dashboard ul.wp-submenu a.wp-first-item' ).text( 'Dashboard' );

	$( '#add-script' ).click( function(){
		// clean the form out
		$( '#trksit_script_label' ).val( '' );
		$( '#trksit_script_platform option:selected' ).removeAttr( 'selected' );
		$( '#trksit_script' ).val( '' );
		$( '#script-id' ).val( '' );
	});

	$( '#script_cancel' ).click( function(){
		var url = $(this).attr( 'data-url' );
		window.location = url;
	});

	$( '#trksit_settings_form' ).validate();
	$( '#trksit_add_script_form' ).validate();

	//Makes IE honor autofocus attribute on inputs
	$( '[autofocus]:not(:focus)' ).eq(0).focus();

	//Handle "other" platform
	$( '#trksit_script_platform' ).change( function(){

		if ( $( this ).val() == 'other' ){
			$( '#trksit_script_platform_other' ).show();
		}

	});

});


//Flash detect plugin
//
/*
Copyright (c) Copyright (c) 2007, Carl S. Yestrau All rights reserved.
Code licensed under the BSD License: http://www.featureblend.com/license.txt
Version: 1.0.4
*/
var FlashDetect = new function(){
    var self = this;
    self.installed = false;
    self.raw = "";
    self.major = -1;
    self.minor = -1;
    self.revision = -1;
    self.revisionStr = "";
    var activeXDetectRules = [
        {
            "name":"ShockwaveFlash.ShockwaveFlash.7",
            "version":function(obj){
                return getActiveXVersion(obj);
            }
        },
        {
            "name":"ShockwaveFlash.ShockwaveFlash.6",
            "version":function(obj){
                var version = "6,0,21";
                try{
                    obj.AllowScriptAccess = "always";
                    version = getActiveXVersion(obj);
                }catch(err){}
                return version;
            }
        },
        {
            "name":"ShockwaveFlash.ShockwaveFlash",
            "version":function(obj){
                return getActiveXVersion(obj);
            }
        }
    ];
    /**
     * Extract the ActiveX version of the plugin.
     *
     * @param {Object} The flash ActiveX object.
     * @type String
     */
    var getActiveXVersion = function(activeXObj){
        var version = -1;
        try{
            version = activeXObj.GetVariable("$version");
        }catch(err){}
        return version;
    };
    /**
     * Try and retrieve an ActiveX object having a specified name.
     *
     * @param {String} name The ActiveX object name lookup.
     * @return One of ActiveX object or a simple object having an attribute of activeXError with a value of true.
     * @type Object
     */
    var getActiveXObject = function(name){
        var obj = -1;
        try{
            obj = new ActiveXObject(name);
        }catch(err){
            obj = {activeXError:true};
        }
        return obj;
    };
    /**
     * Parse an ActiveX $version string into an object.
     *
     * @param {String} str The ActiveX Object GetVariable($version) return value.
     * @return An object having raw, major, minor, revision and revisionStr attributes.
     * @type Object
     */
    var parseActiveXVersion = function(str){
        var versionArray = str.split(",");//replace with regex
        return {
            "raw":str,
            "major":parseInt(versionArray[0].split(" ")[1], 10),
            "minor":parseInt(versionArray[1], 10),
            "revision":parseInt(versionArray[2], 10),
            "revisionStr":versionArray[2]
        };
    };
    /**
     * Parse a standard enabledPlugin.description into an object.
     *
     * @param {String} str The enabledPlugin.description value.
     * @return An object having raw, major, minor, revision and revisionStr attributes.
     * @type Object
     */
    var parseStandardVersion = function(str){
        var descParts = str.split(/ +/);
        var majorMinor = descParts[2].split(/\./);
        var revisionStr = descParts[3];
        return {
            "raw":str,
            "major":parseInt(majorMinor[0], 10),
            "minor":parseInt(majorMinor[1], 10),
            "revisionStr":revisionStr,
            "revision":parseRevisionStrToInt(revisionStr)
        };
    };
    /**
     * Parse the plugin revision string into an integer.
     *
     * @param {String} The revision in string format.
     * @type Number
     */
    var parseRevisionStrToInt = function(str){
        return parseInt(str.replace(/[a-zA-Z]/g, ""), 10) || self.revision;
    };
    /**
     * Is the major version greater than or equal to a specified version.
     *
     * @param {Number} version The minimum required major version.
     * @type Boolean
     */
    self.majorAtLeast = function(version){
        return self.major >= version;
    };
    /**
     * Is the minor version greater than or equal to a specified version.
     *
     * @param {Number} version The minimum required minor version.
     * @type Boolean
     */
    self.minorAtLeast = function(version){
        return self.minor >= version;
    };
    /**
     * Is the revision version greater than or equal to a specified version.
     *
     * @param {Number} version The minimum required revision version.
     * @type Boolean
     */
    self.revisionAtLeast = function(version){
        return self.revision >= version;
    };
    /**
     * Is the version greater than or equal to a specified major, minor and revision.
     *
     * @param {Number} major The minimum required major version.
     * @param {Number} (Optional) minor The minimum required minor version.
     * @param {Number} (Optional) revision The minimum required revision version.
     * @type Boolean
     */
    self.versionAtLeast = function(major){
        var properties = [self.major, self.minor, self.revision];
        var len = Math.min(properties.length, arguments.length);
        for(i=0; i<len; i++){
            if(properties[i]>=arguments[i]){
                if(i+1<len && properties[i]==arguments[i]){
                    continue;
                }else{
                    return true;
                }
            }else{
                return false;
            }
        }
    };
    /**
     * Constructor, sets raw, major, minor, revisionStr, revision and installed public properties.
     */
    self.FlashDetect = function(){
        if(navigator.plugins && navigator.plugins.length>0){
            var type = 'application/x-shockwave-flash';
            var mimeTypes = navigator.mimeTypes;
            if(mimeTypes && mimeTypes[type] && mimeTypes[type].enabledPlugin && mimeTypes[type].enabledPlugin.description){
                var version = mimeTypes[type].enabledPlugin.description;
                var versionObj = parseStandardVersion(version);
                self.raw = versionObj.raw;
                self.major = versionObj.major;
                self.minor = versionObj.minor;
                self.revisionStr = versionObj.revisionStr;
                self.revision = versionObj.revision;
                self.installed = true;
            }
        }else if(navigator.appVersion.indexOf("Mac")==-1 && window.execScript){
            var version = -1;
            for(var i=0; i<activeXDetectRules.length && version==-1; i++){
                var obj = getActiveXObject(activeXDetectRules[i].name);
                if(!obj.activeXError){
                    self.installed = true;
                    version = activeXDetectRules[i].version(obj);
                    if(version!=-1){
                        var versionObj = parseActiveXVersion(version);
                        self.raw = versionObj.raw;
                        self.major = versionObj.major;
                        self.minor = versionObj.minor;
                        self.revision = versionObj.revision;
                        self.revisionStr = versionObj.revisionStr;
                    }
                }
            }
        }
    }();
};
FlashDetect.JS_RELEASE = "1.0.4";
