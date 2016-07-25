<?php
$TIMESTAMP = time();
// really simple way to try to preserve data integrity... verify this hash on post
$AUTHTOKEN = md5(sprintf(BC_AUTHRESP_TOKEN, $TIMESTAMP, $STORE_USER_DATA["uid"], $STORE_USER_DATA["store"]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
<title>Snake Charmer for Big Commerce</title>

<!-- Bootstrap -->
<link href="//d61fqxuabx4t4.cloudfront.net/snakecharmer/partners/bigcommerce/auth/css/bootstrap.min.css" rel="stylesheet">
<link href="//d61fqxuabx4t4.cloudfront.net/snakecharmer/partners/bigcommerce/auth/css/bc_style.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Merriweather+Sans:300,400,700" rel="stylesheet" type="text/css">
<link href="https://fonts.googleapis.com/css?family=Merriweather:300,400,700,900" rel="stylesheet" type="text/css">

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="//d61fqxuabx4t4.cloudfront.net/snakecharmer/partners/bigcommerce/auth/js/bootstrap.min.js"></script>

<script type="text/javascript" src="//sc.conversionvoodoo.com/partners/bigcommerce/js/bcauth.js"></script>

<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body data-ts="<?php echo $TIMESTAMP; ?>" data-uid="<?php echo $STORE_USER_DATA["uid"]; ?>" data-em="<?php echo $STORE_USER_DATA["email"]; ?>" data-tkn="<?php echo $AUTHTOKEN; ?>" data-shash="<?php echo $STORE_USER_DATA["store"]; ?>">
<div class="container-fluid bc_form">
  <div class="container">
    <div class="row">
    <div class="col-sm-12">
    	<div class="bc_header">
        	<img class="bc_logo" src="//d61fqxuabx4t4.cloudfront.net/snakecharmer/images/snakecharmer_logo.png" height="35px">
        </div>
    </div>
      <div class="col-sm-12">
        <p>Increase your store's revenue in 15 minutes.<br>
          <b>Get started now!</b> </p>
        <div class="bc_form_box" id="frmAuth">

			<center><span>Activate or Re-Activate your account</span></center>
            <div class="col-sm-12">
              <div class="form-group">
                <label for="domain">Domain</label>
                <input type="text" class="form-control input-lg" id="domain" name="domain" placeholder="yourstore.com">
              </div>
            </div>
            <div class="col-sm-12">
              <div class="form-group">
                <label for="email">Email</label>
                <?php
                if(isset($STORE_USER_DATA["email"]) && !is_null($STORE_USER_DATA["email"]) && strlen($STORE_USER_DATA["email"]) > 0) { ?>
                <input type="email" class="form-control input-lg" id="email" name="email" placeholder="hello@domain.com" value="<?php echo $STORE_USER_DATA["email"]; ?>">
                <?php } else { ?>
                <input type="email" class="form-control input-lg" id="email" name="email" placeholder="hello@domain.com">
                <?php } ?>
              </div>
            </div>
            <div class="col-sm-12">
              <div class="form-group">
                <label for="companyName">Company Name</label>
                <input type="text" class="form-control input-lg" id="companyName" name="companyName" placeholder="Your Store">
              </div>
            </div>
            <center>
              <button type="submit" class="btn try" id="btnSubmit" >Activate My Account</button>
            </center>

          <center>
            <small>with a <span>100%, money-back</span> guarantee</small>
          </center>
        </div>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
	window.BCA = new BCAuth();
	BCA.init();
</script>
</body>
</html>
