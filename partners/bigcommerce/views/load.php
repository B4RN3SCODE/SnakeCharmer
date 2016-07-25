<?php
$LOAD_USER = (isset($LOAD_USER) && !is_null($LOAD_USER) && !(strpos($LOAD_USER, "@") === false)) ? $LOAD_USER : "User";
list($LOAD_USERNAME, $LOAD_EMAILDOMAIN) = explode("@", $LOAD_USER, 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
<title>Install Snake Charmer for Big Commerce On Your Site</title>

<!-- Bootstrap -->
<link href="//d61fqxuabx4t4.cloudfront.net/snakecharmer/partners/bigcommerce/load/css/bootstrap.min.css" rel="stylesheet">
<link href="//d61fqxuabx4t4.cloudfront.net/snakecharmer/partners/bigcommerce/load/css/bc_style.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Merriweather+Sans:300,400,700" rel="stylesheet" type="text/css">
<link href="https://fonts.googleapis.com/css?family=Merriweather:300,400,700,900" rel="stylesheet" type="text/css">


<script type="text/javascript" src="//sc.conversionvoodoo.com/partners/bigcommerce/js/bcnotify.js"></script>


<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<div class="container-fluid bc_form">
  <div class="container">
    <div class="row">
    <div class="col-sm-12">
    	<div class="bc_header">
        	<img class="bc_logo" src="//d61fqxuabx4t4.cloudfront.net/snakecharmer/images/snakecharmer_logo.png" height="35px">
        </div>
    </div>
      <div class="col-sm-12">
        <p>Welcome, <?php echo $LOAD_USERNAME; ?>!</p>
        <div class="bc_code_copy">
        	<small>To use SnakeCharmer you will need to paste the following code within the head tag of your store.</small>
            <br><br>
         	<textarea style="font-size:.8em;">

<!-- SnakeCharmer for <?php echo $LOAD_DOMAIN; ?> -->
&lt;script type="text/javascript"&gt;window.SC_AUTO_INIT=false;&lt;/script&gt;
&lt;script type="text/javascript" id="SCJS" src="//d61fqxuabx4t4.cloudfront.net/snakecharmer/js/sc.min.js?license=<?php echo $LOAD_LICENSE; ?>&themeId=<?php echo $LOAD_THEMEID; ?>&ghost=false"&gt;&lt;/script&gt;
&lt;script type="text/javascript"&gt;
window.SC = new SC();
window.SC._loadPlugins.push('http://sc.conversionvoodoo.com/plugins/scbcmain.js');
window.SC.ini();
&lt;/script&gt;

            </textarea>
        </div>
        <div class="bc_code_copy">
        <div class="row">
        	<div class="col-sm-4">
            	<div class="panel panel-default">
                	<div class="panel-heading">
                    	<h5>How It Works</h5>
                    </div>
                	<div class="panel-body">
                        <p>SnakeCharmer is a system that tracks user interaction with your store and offers promotions based on each action.</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
            	<div class="panel panel-default">
                	<div class="panel-heading">
                    	<h5>Set Up Events</h5>
                    </div>
                	<div class="panel-body">
                        <p>To set up events and promotions please contact us.</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
            	<div class="panel panel-default">
                	<div class="panel-heading">
                    	<h5>Contact Us</h5>
                    </div>
                	<div class="panel-body">
                       <button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#contactModal"> Contact Us </button>
                    </div>
                </div>
            </div>
        </div>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- Modal -->
<div class="modal fade" id="contactModal" tabindex="-1" role="dialog" aria-labelledby="contactModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content" id="frmNotify">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="contactModalLabel">Contact Us</h4>
      </div>

        <div class="modal-body">
			<input type="hidden" id="license" name="license" value="<?php echo $LOAD_LICENSE; ?>">
          <div class="form-group">
            <label for="name">Your Name</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="John Smith">
          </div>
          <div class="form-group">
            <label for="email">Email address</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="email@domain.com" value="<?php echo $LOAD_USER; ?>">
          </div>
          <div class="form-group">
            <label for="message">Your Message</label>
            <textarea type="text" class="form-control" id="message" name="message" placeholder="Im ready to set up my events and promotions... let me know how!"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="btnNotifySubmit">
          Send Message
          </button>
        </div>

    </div>
  </div>
</div>




<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="//d61fqxuabx4t4.cloudfront.net/snakecharmer/partners/bigcommerce/load/js/bootstrap.min.js"></script>
<script>
	window.BCN = new BCNotify();
	BCN.init();
</script>

</body>
</html>
