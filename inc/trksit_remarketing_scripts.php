<?php

$google_remarketing = '<script type="text/javascript">
/* <![CDATA[ */
var google_conversion_id = {{: google_id :}};
var google_custom_params = window.google_tag_params;
var google_remarketing_only = true;
/* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<div style="display:inline;">
<img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/{{: google_id :}}/?value=0&guid=ON&script=0"/>
</div>
</noscript>';

$adroll_remarketing = '<script type="text/javascript">
adroll_adv_id = "{{: adroll_id :}}";
adroll_pix_id = "{{: adroll_pixel :}}";
(function () {
var oldonload = window.onload;
window.onload = function(){
   __adroll_loaded=true;
   var scr = document.createElement("script");
   var host = (("https:" == document.location.protocol) ? "https://s.adroll.com" : "http://a.adroll.com");
   scr.setAttribute("async", "true");
   scr.type = "text/javascript";
   scr.src = host + "/j/roundtrip.js";
   ((document.getElementsByTagName("head") || [null])[0] ||
    document.getElementsByTagName("script")[0].parentNode).appendChild(scr);
   if(oldonload){oldonload()}};
}());
</script>';

$facebook_remarketing = '<script>
(function() {
  var _fbq = window._fbq || (window._fbq = []);
  if (!_fbq.loaded) {
    var fbds = document.createElement(\'script\');
    fbds.async = true;
    fbds.src = \'//connect.facebook.net/en_US/fbds.js\';
    var s = document.getElementsByTagName(\'script\')[0];
    s.parentNode.insertBefore(fbds, s);
    _fbq.loaded = true;
  }
  _fbq.push([\'addPixelId\', \'{{: facebook_pixel :}}\']);
})();
window._fbq = window._fbq || [];
window._fbq.push([\'track\', \'PixelInitialized\', {}]);
</script>
<noscript>
<img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/tr?id={{: facebook_pixel :}}&amp;ev=PixelInitialized" />
</noscript>';
