<?php
date_default_timezone_set("America/Los_Angeles");

error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
ini_set("log_errors", 1);
ini_set("ignore_repeated_errors", 0);
ini_set("track_errors", 1);
ini_set("html_errors", 1);

include_once(dirname(__FILE__) . "/app/BigCommerceAppAdmin.php");
/*
 * Load callback from BigCommerce
 */
$signedReq = (isset($_REQUEST["signed_payload"]) && !is_null($_REQUEST["signed_payload"]) &&
				strlen($_REQUEST["signed_payload"]) > 0 && !(strpos($_REQUEST["signed_payload"], ".") === false)) ? $_REQUEST["signed_payload"] : null;

if(is_null($signedReq)) {
	$ERR_MSG = "INVALID SIGNED REQUEST RECEIVED";
	include_once("views/error.php");
	exit;
}

$loadApp = new BigCommerceAppAdmin("load", array());

try {

	$loadApp->initLoad($signedReq);
	$loadApp->handleLoadRequest();

	if($loadApp->loadStateSuccess()) {

		$data = $loadApp->getLoadResponseData();
		$LOAD_USER_DATA = $loadApp->getLoadDecodedData("user");
		$LOAD_USER = $LOAD_USER_DATA["email"];
		$LOAD_DOMAIN = $data["Domain"];
		$LOAD_LICENSE = $data["License"];
		$LOAD_THEMEID = $data["ThemeId"];
		include_once("views/load.php");
		exit;

	} else {

		$ERR_MSG = $loadApp->getLoadMessage();
		include_once("views/error.php");
		exit;

	}

} catch(Exception $e) {

	$ERR_MSG = "Ambiguous error received with message: {$e->getMessage()}";
	include_once("views/error.php");
	exit;

}
?>
