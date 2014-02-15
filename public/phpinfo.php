<html xmlns:og='http://ogp.me/ns#'>
<head>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>

    <title>jQuery Example</title>
<script type="text/javascript">

$(document).ready(function() {
  $.ajaxSetup({ cache: true });
  $.getScript('//connect.facebook.net/en_UK/all.js', function(){
    FB.init({
      appId: '84480145981',
      //status : true, // check login status
      //cookie : true, // enable cookies to allow the server to access the session
      //xfbml : true // parse XFBML
    });     
    $('#loginbutton,#feedbutton').removeAttr('disabled');
    FB.getLoginStatus(updateStatusCallback);
  });
});
</script>
<meta property="fb:admins" content="704017191"/>
<meta property="fb:app_id" content="84480145981"/>
<meta property="og:url" content="http://atticho.no-ip.biz/tagbees_refactored/phpinfo.php" />
<meta property="og:image" content="https://fbstatic-a.akamaihd.net/rsrc.php/v2/y6/r/YQEGe6GxI_M.png" />
<meta property="og:site_name" content="Facebook Developers" />
<meta property="og:title" content="Social Plugins" />
<meta property="og:type" content="article" />
<meta property="og:locale" content="en_US" />
<meta property="og:description" content="Social plugins let you see what your friends have liked, commented on or shared on sites across the web." />
</head>
<body>
  
<!--<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=84480145981";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>-->

<div class="fb-comments" data-href="http://example.com/comments" data-numposts="5" data-colorscheme="light"></div>

<div class="fb-comments" data-href="http://atticho.no-ip.biz/tagbees_refactored/phpinfo.php" data-numposts="5" data-colorscheme="light"></div>

<div class="fb-like" data-href="https://www.facebook.com/JustinBieber" data-layout="standard" data-action="like" data-show-faces="true" data-share="true"></div>

<?php 
echo getenv('APPLICATION_ENV');
//echo phpinfo();?>
</body>
</html>