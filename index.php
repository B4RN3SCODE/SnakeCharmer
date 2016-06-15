<?php

session_start();

include_once("include/config.php");
include_once("include/DBCon.php");

if(count($_POST) > 0) {
	$submit_error = false;
	$inserted = false;
	$err_msg = "";
	if(!isset($_POST["action"]) || strtolower($_POST["action"]) != "submit" || !isset($_POST["fname"]) || strlen($_POST["fname"]) < 2 || !isset($_POST["lname"]) ||
		strlen($_POST["lname"]) < 2 || !isset($_POST["email"]) || strlen($_POST["email"]) < 4 || strlen($_POST["email"]) > 100 || !isset($_POST["url"]) || !validUrl($_POST["email"]) ||
		!isset($_POST["traffic"]) || !is_numeric($_POST["traffic"]) || !isset($_POST["revenue"]) || !is_numeric($_POST["revenue"])) {

		$err_msg = "Please fill out the form correctly";
		$submit_error = true;
	}

	if(!$submit_error) {
		$db = new DBCon(DB_HOST, DB_USER, DB_PASS, "scleads", null, null);
		if(!$db->Link()) {
			$err_msg = "Database issue. Please try again later";
			$submit_error = true;
		} else {
			$remote_addr = "0.0.0.0";
			if(isset($_SERVER["REMOTE_ADDR"]))
				$remote_addr = $_SERVER["REMOTE_ADDR"];
			elseif(isset($_SERVER["REMOTE_HOST"]))
				$remote_addr = $_SERVER["REMOTE_HOST"];
			elseif(isset($_SERVER["HTTP_CLIENT_IP"]))
				$remote_addr = $_SERVER["HTTP_CLIENT_IP"];

			$fn = $db->EscapeQueryStmt($_POST["fname"]);
			$ln = $db->EscapeQueryStmt($_POST["lname"]);
			$e = $db->EscapeQueryStmt($_POST["email"]);
			$u = $db->EscapeQueryStmt($_POST["url"]);
			$t = (int)$db->EscapeQueryStmt($_POST["traffic"]);
			$r = (int)$db->EscapeQueryStmt($_POST["revenue"]);


			$sql = "SELECT COUNT(*) AS TOT FROM Leads WHERE Email = '{$e}' AND SiteUrl = '{$u}'";
			$db->setQueryStmt($sql);
			$db->Query();
			$exists = $db->GetRow();
			if(isset($exists["TOT"]) && intval($exists["TOT"]) > 0) {
				$submit_error = true;
				$err_msg = "You have already signed up for {$u}";
			} else {

				$sql = "INSERT INTO Leads (FirstName, LastName, Email, SiteUrl, Traffic, MonthlyRev, LeadTime, IpAddress) VALUES ('{$fn}', '{$ln}', '{$e}', '{$u}', {$t}, {$r}, CURRENT_TIMESTAMP, '{$remote_addr}')";
				$db->setQueryStmt($sql);
				if(!$db->Query()) {
					$submit_error = true;
					$err_msg = "Database error. Please try again later";
				}

				if($db->GetLastInsertedId() > 0) {
					$inserted = true;

					mail("eliasg@conversionvoodoo.com,tylerb@conversionvoodoo.com", "Snake Charmer Sign Up", "New user info:\nName: {$fn} {$ln}\nEmail: {$e}\nSite: {$u}\nRev.: {$r}\nTraffic: {$t}", "From: snakecharmer@conversionvoodoo.com");

				}
			}
		}
	}

	$_SESSION["submit_error"] = $submit_error;
	$_SESSION["err_msg"] = $err_msg;
	$_SESSION["inserted"] = $inserted;

	header("Location: /");
	exit;

} else {
	if(isset($_SESSION["submit_error"])) {
		$submit_error = $_SESSION["submit_error"];
		unset($_SESSION["submit_error"]);
	}
	if(isset($_SESSION["err_msg"])) {
		$err_msg = $_SESSION["err_msg"];
		unset($_SESSION["err_msg"]);
	}
	if(isset($_SESSION["inserted"])) {
		$inserted = $_SESSION["inserted"];
		unset($_SESSION["inserted"]);
	}
}

function validUrl($str = STR_EMP) {
	$str = trim(preg_replace("~http\:\/\/|https\:\/\/|www\.~", "", $str));
	return preg_match("~[-a-zA-Z0-9\:\%\.\_\+\~\#\=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9\:\%\_\+\.\~\#\?\&\/\/\=]*)~", $str);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
<title>SnakeCharmer - Increase your conversions</title>

<!-- Bootstrap -->
<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/ui/style.css" rel="stylesheet">
<link href='https://fonts.googleapis.com/css?family=Merriweather+Sans:300,400,700' rel='stylesheet' type='text/css'>
<link href='https://fonts.googleapis.com/css?family=Merriweather:300,400,700,900' rel='stylesheet' type='text/css'>

<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<?php
if(isset($submit_error) && $submit_error === true) {
	echo "<div class=\"alert alert-danger\"><center>{$err_msg}</center></div>";
} elseif(isset($submit_error) && $submit_error === false && isset($inserted) && $inserted === true) {
	echo "<div class=\"alert alert-success\"><center>You have successfully signed up for Snake Charmer.  We will contact you soon</center></div>";
}
?>
<div class="container-fluid main">
  <div class="background_video">
    <video width="100%" height="100%" autoplay loop muted>
      <source src="video/background_video.webm" type="video/webm">
    </video>
  </div>
  <div class="overlay_shadow"></div>
  <div class="overlay"></div>
  <div class="container">
  	<nav class="navbar navbar-default">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="/"><img src="images/logo.png"></a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li><a href="/demo">See it in action</a></li>
      </ul>
      <ul class="nav navbar-nav navbar-right">
      	<li><a class="learnButton">Learn More</a></li>
        <li><a class="btn btn-sm signup tryButton">Sign Up</a></li>
      </ul>
    </div><!-- /.navbar-collapse -->
</nav>
    <div class="row">
      <div class="col-sm-12">
        <h1>Increase your conversions.</h1>
        <h2>Automatically engage your customers based on their behaviors to reduce your bounce rate and increase conversions, <b><i>guaranteed</i></b>.</h2>
        <center><a class="btn try tryButton">Try It Now</a> <a class="btn learn learnButton">Learn More</a></center> </div>
    </div>
  </div>
  <div class="texture"></div>
</div>
<!-- CUSTOMER SECTION -->
<div class="container-fluid customers" id="learnMore">
  <div class="container">
    <div class="row">
      <div class="col-sm-12"> <small>We’ve worked with the world’s most succesful companies</small>
        <center>
          <img src="images/customer_logos.png">
        </center>
        <hr>
      </div>
    </div>
  </div>
</div>
<!--POKER FACE SECTION -->
<div class="container-fluid poker">
  <div class="container">
    <div class="row">
      <div class="col-sm-12">
        <p>Your users can’t hold a poker face very well. Their behavior while browsing  is full of signs that may reveal when they are stuck or about to bounce off your page. <br>
          <br>
          <b>What if you could intercept at that moment?</b></p>
      </div>
    </div>
  </div>
</div>
<!--DEMO SECTION -->
<div class="container-fluid demo">
  <div class="container">
    <div class="row">
      <div class="col-sm-6">
        <ul>
          <li>
            <h3>Know your audience</h3>
            <p>Track the behavior and intent of every visitor on your site or user of your application. Segment and understand your audience at an individual level in real time.</p>
          </li>
          <li>
            <h3>Relevant experiences in real time</h3>
            <p>Timing is everything! Deliver highly relevant, personalized experiences, messages and offers to your audiences in real time to optimize your sales funnel.</p>
          </li>
          <li>
            <h3>Strategy is your friend</h3>
            <p>Every action your user takes, or lack thereof, is an opportunity you can use in your favor. Help customers feel relieved when making purchases by giving them the information they need.</p>
          </li>
        </ul>
      </div>
      <div class="col-sm-6">
        <div class="browser">
        	<video width="100%" height="100%" autoplay loop muted>
      			<source src="video/SnakeCharmer_Demo.webm" type="video/webm">
    		</video>
        </div>
      </div>
    </div>
  </div>
</div>
<!--RECOVERED SECTION -->
<div class="container-fluid recovered">
	<div class="white_wave"></div>
  <div class="container">
    <div class="row">
    	<div class="col-sm-6">
        	<h5><span>$</span>2,324,234</h5>
            <small>in sales recovered</small>
        </div>
    </div>
  </div>
</div>
<!--BULLETS SECTION -->
<div class="container-fluid bullets">
  <div class="white_wave"></div>
  <div class="container">
    <div class="row">
      <div class="col-sm-4">
        <h4>Guaranteed <br><b>conversion increase</b>.</h4>
        <center>
          <div class="icon_first">
          	<img width="64px" src="images/emoji_18.gif" class="emoji emoji_one">
            <img width="64px" src="images/emoji_3.gif" class="emoji emoji_two">
          </div>
        </center>
        <p>We guarantee that SnakeCharmer will increase your conversion rates by at least 5% or we will refund every dime that you’ve paid.</p>
      </div>
      <div class="col-sm-4">
        <h4>We do all the <br><b>heavy lifting</b>.</h4>
        <center>
           <div class="icon_second">
          	<img width="64px" src="images/emoji_10.gif" class="emoji emoji_third">
            <img width="64px" src="images/emoji_20.gif" class="emoji emoji_fourth">
            <img width="64px" src="images/emoji_17.gif" class="emoji emoji_fifth">
          </div>
        </center>
        <p>We will take care of the setup, including code installation and all tests, so your team just sits back and watches the numbers roll in.</p>
      </div>
      <div class="col-sm-4">
        <h4>You get all the <br><b>customer love</b>.</h4>
        <center>
          <div class="icon_third">
          	<img width="64px" src="images/emoji_11.gif" class="emoji emoji_sixth">
            <img width="64px" src="images/emoji_9.gif" class="emoji emoji_seventh">
          </div>
        </center>
        <p>Sit back and watch as SnakeCharmer guides your customers through friction points in your sales funnels, leaving you to worry about banking checks.</p>
      </div>
    </div>
  </div>
</div>
<!--OFFER SECTION -->
<div class="container-fluid offer">
	<div class="texture"></div>
  <div class="container">
    <div class="row">
    	<div class="col-sm-12">
        	<p>Are you ready to capture the sales you've given up on?<br>
            <b>Because they are still yours for the taking.</b>
            </p>
        	<div class="offer_box">
            	<form id="tryForm" action="" method="POST">
					<input type="hidden" name="action" value="submit" />
                	<div class="col-sm-6">
                  <div class="form-group">
                    <label for="firstName">First name</label>
                    <input type="text" class="form-control input-lg" id="firstName" name="fname" placeholder="John">
                  </div>
                  </div>
                  <div class="col-sm-6">
                  <div class="form-group">
                    <label for="lastName">Last name</label>
                    <input type="text" class="form-control input-lg" id="lastName" name="lname" placeholder="Smith">
                  </div>
                  </div>
                  <div class="col-sm-12">
                  <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control input-lg" id="email" name="email" placeholder="hello@domain.com">
                  </div>
                  </div>
                  <div class="col-sm-12">
                  <div class="form-group">
                    <label for="url">Website URL</label>
                    <input type="url" class="form-control input-lg" id="url" name="url" placeholder="http://www.yoursite.com">
                  </div>
                  </div>
                  <div class="col-sm-6">
                  <div class="form-group">
                    <label for="traffic">Website traffic</label>
                    <select name="traffic" class="form-control input-lg">
                    	<option selected disabled>- select -</option>
                        <option value="1">Less than 10,000</option>
                        <option value="2">10,000 - 50,000</option>
                        <option value="3">50,000 - 100,000</option>
                        <option value="4">100,000 - 500,000</option>
                        <option value="5">500,000 - 1,000,000</option>
                        <option value="6">1,000,000 - 5,000,000</option>
                        <option value="7">5,000,000 - 10,000,000</option>
                        <option value="8">10,000,000+</option>
                    </select>
                  </div>
                  </div>
                  <div class="col-sm-6">
                  <div class="form-group">
                    <label for="revenue">Monthly revenue</label>
                    <select name="revenue" class="form-control input-lg">
                    	<option selected disabled>- select -</option>
                        <option value="1">Less than $5,000</option>
                        <option value="2">$5,000 - $10,000</option>
                        <option value="3">$10,000 - $25,000</option>
                        <option value="4">$25,000 - $50,000</option>
                        <option value="5">$50,000 - $100,000</option>
                        <option value="6">$100,000 - $500,000</option>
                        <option value="7">$500,000 - $1,000,000</option>
                        <option value="8">$1,000,000+</option>
                    </select>
                  </div>
                  </div>
                  <center><button type="submit" class="btn try">Get started now</button></center>
                </form>
                <center><small>with a <span>100%, money-back</span> guarantee</small></center>
            </div>
        </div>
    </div>
  </div>
</div>

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
	$(".tryButton").click(function() {
		$('html, body').animate({
			scrollTop: $("#tryForm").offset().top
		}, 1000);

		setTimeout(function () {
			$('.offer_box').addClass('flash_border');
		}, 1000);

		setTimeout(function () {
			$('.offer_box').removeClass('flash_border');
		}, 5000);

	});

	$(".learnButton").click(function() {
		$('html, body').animate({
			scrollTop: $("#learnMore").offset().top
		}, 500);

	});

});

</script>
</body>
</html>
<?php session_destroy(); ?>
