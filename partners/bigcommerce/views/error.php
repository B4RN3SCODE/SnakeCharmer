<html>
	<body>
		<center>
			<div>
				<h2>We experienced an error. Please contact tylerb@conversionvoodoo.com<br />or your BigCommerce support representative</h2><br />
				<?php if(isset($ERR_MSG) && strlen($ERR_MSG) > 0) { ?>
				<span>Include this error message to tylerb@conversionvoodoo:</span><br />
				<small><?php echo $ERR_MSG;?></small>
				<?php } ?>
			</div>
		</center>
	</body>
</html>
