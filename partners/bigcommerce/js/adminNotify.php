<?php
$adminemails = "tylerb@conversionvoodoo.com, eliasg@conversionvoodoo.com";
$subject = "New Big Commerce SC Installation";
$name = (isset($_REQUEST["name"]) && strlen($_REQUEST["name"]) > 0) ? $_REQUEST["name"] : null;
$email = (isset($_REQUEST["email"]) && strlen($_REQUEST["email"]) > 0) ? $_REQUEST["email"] : null;
$message = (isset($_REQUEST["message"]) && strlen($_REQUEST["message"]) > 0) ? $_REQUEST["message"] : null;
$lic = (isset($_REQUEST["license"]) && strlen($_REQUEST["license"]) > 0) ? $_REQUEST["license"] : null;


if(is_null($name) || is_null($email) || is_null($message) || is_null($lic)) {
	$js = "window.BCN._procEnded = true; window.BCN.submitError();";
	end_proc($js);
}


$msg = "Contact/Account Information\n

Name: {$name}\n
Email: {$email}\n
SC License: {$lic}\n
\n
user attached this message:\n\n
{$message}
";


mail($adminemails, $subject, $msg, "From: scbigcommerce@conversionvoodoo.com");



$js = "window.BCN._procEnded = true; window.BCN.submitSuccess();";
end_proc($js);



function end_proc($msg) {
	header("Content-Type: application/javascript");
	echo $msg;
	exit;
}


?>
