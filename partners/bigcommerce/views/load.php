<?php
$LOAD_USER = (isset($LOAD_USER) && !is_null($LOAD_USER) && !(strpos($LOAD_USER, "@") === false)) ? $LOAD_USER : "User";
list($LOAD_USERNAME, $LOAD_EMAILDOMAIN) = explode("@", $LOAD_USER, 2);
?>
<html>
	<head></head>
	<body>
		<div>
			<div style="margin:20px 100px 0 px 0 px;">
				<h3>Welcome, <?php echo $LOAD_USERNAME; ?></h3>
				<span>To install SnakeCharmer on your site, paste the following code in your &lt;head&gt;&lt;/head&gt; tag:</span>
				<textarea cols="275" rows="12" readonly="readonly">
	<!-- SnakeCharmer for <?php echo $LOAD_DOMAIN; ?> -->
	&lt;script type="text/javascript"&gt;window.SC_AUTO_INIT=false;&lt;/script&gt;
	&lt;script type="text/javascript" id="SCJS" src="//d61fqxuabx4t4.cloudfront.net/snakecharmer/js/sc.min.js?license=<?php echo $LOAD_LICENSE; ?>&themeId=<?php echo $LOAD_THEMEID; ?>&ghost=false"&gt;&lt;/script&gt;
	&lt;script type="text/javascript"&gt;
	window.SC = new SC();
	window.SC._loadPlugins.push('http://sc.conversionvoodoo.com/plugins/test.js','http://sc.conversionvoodoo.com/plugins/sctracker.js');
	window.SC.ini();
	&lt;/script&gt;
				</textarea>
			</div>
		</div>
	</body>
</html>
