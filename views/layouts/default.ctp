<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
<?php print $html->charset('UTF-8');?>
<title><?php echo $title_for_layout?></title>
<meta name="title" content="<?php echo $title_for_layout?>" />
<meta name="description" content="Cool Pictures contains some of the funniest images on the internet." />
<meta name="keywords" content="cool,pictures,images,funny,cute" />
<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="http://feeds.feedburner.com/coolpictures" />
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
<link rel="stylesheet" type="text/css" href="/css/style.css?ver=1.1" />
<script type="text/javascript" src="/js/jquery-1.2.1.min.js"></script>
<script type="text/javascript" src="/js/coolimages.js?ver=1.1"></script>
</head>

<body>
<div id="menu" class="clearfix">
<form action="/f/">
<ul>
  <li><a href="/" title="Cool Pictures Index">Cool Pictures</a></li>
  <li><a href="/popular/" title="Most Viewed Cool Pictures">Popular</a></li>
  <li><a href="/top100/" title="Highest Rated Cool Pictures">Top 100</a></li>
  <li><a href="/random" title="Random Picture">Random</a></li>
  <li><a href="/about/" title="About Cool Pictures">About</a></li>
  <li class="search"><input
 onfocus="if(this.value=='Search the site...')value='';"
 onblur="if(this.value=='')value='Search the site...';"
 value="Search the site..." alt="Search the site..."
 name="f" id="f" type="text" /></li>
</ul>
</form>
</div>
<div id="warning"><?php $session->flash();?></div>

<?php echo $content_for_layout ?>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? 
"https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + 
"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
var pageTracker = _gat._getTracker("UA-2850477-3");
pageTracker._initData();
pageTracker._trackPageview();
</script>
</body>

</html>
