<?php
// required dependencies
include_once("config.php");
include_once("glob.php");
include_once("DBCon.php");
include_once(dirname(__FILE__) . "/../cts/ConversionVoodoo.php");


$valid_othvars = array("promoType" => "valid", "referrer" => "valid", "page" => "valid", "traffic_type" => "valid");


$license = (isset($_REQUEST["license"]) && !empty($_REQUEST["license"]) && !is_null($_REQUEST["license"]) && strlen($_REQUEST["license"]) > 0) ? $_REQUEST["license"] : null;
$othvars = (isset($_REQUEST["othvars"]) && !empty($_REQUEST["othvars"]) && !is_null($_REQUEST["othvars"]) && strlen($_REQUEST["othvars"]) > 0 && json_decode($_REQUEST["othvars"], true)) ? json_decode($_REQUEST["othvars"], true) : array();
$testId = (isset($_REQUEST["testid"]) && is_numeric($_REQUEST["testid"]) && $_REQUEST["testid"] > 0) ? intval($_REQUEST["testid"]) : 0;
$super = (isset($_REQUEST["super"]) && !empty($_REQUEST["super"]) && $_REQUEST["super"] == "true");
$leadVal = (isset($_REQUEST["leadval"]) && is_numeric($_REQUEST["leadval"]) && $_REQUEST["leadval"] > 0) ? floatval($_REQUEST["leadval"]) : 0;

if(is_null($license) || $testId < 1) {
	// todo hanle
	end_proc("/* shit stuff */");
}


$db = new DBCon();
if(!$db->Link()) {
	// todo handle
}


$license = $db->EscapeQueryStmt($license);
$testId = $db->EscapeQueryStmt($testId);
if($leadVal > 0) {
	$leadVal = $db->EscapeQueryStmt($leadVal);
}


if(!validData($db, $license, $testId)) {
	end_proc("/* poop data */");
}


try {

	$other_vars = array();
	foreach($othvars as $i => $data) {
		foreach($data as $var => $val) {
			if(isset($valid_othvars[$var]) && $valid_othvars[$var] == "valid" && !empty($val) && strlen($val) > 0) {
				$other_vars[$var] = $db->EscapeQueryStmt($val);
			}
		}
	}

	$cvo = new CTS_Tracking(array(
		'test_id' => $testId,
		'other_vars' => $other_vars,
	));


	if(!isset($other_vars["promoType"]) || is_null($other_vars["promoType"]) || empty($other_vars["promoType"])) {
		$other_vars["promoType"] = "";
	}

	// store the variables -- if any
	if(count($other_vars) > 0) {
		$cvo->variable($other_vars);
	}


	$js = "";

	if(!$super) {
		$cvo->converted();
		$js = "window.SC.pluginBind('SCTracker.updateCookie', [0, {converted: true, promoType: '{$other_vars["promoType"]}'}]);";
	} else {
		if($leadVal > 0) {
			$cvo->converted($super, null, null, $leadVal);
			$js = "window.SC.pluginBind('SCTracker.updateCookie', [0, {superConverted: true, leadVal: {$leadVal}, promoType: '{$other_vars["promoType"]}'}]);";
		} else {
			$cvo->converted(1);
			$js = "window.SC.pluginBind('SCTracker.updateCookie', [0, {superConverted: true, promoType: '{$other_vars["promoType"]}'}]);";
		}
	}

	end_proc("/* congrats, tracked */

{$js}

	");


} catch(Exception $e) {
	end_proc("/* caught e */");
}




/*
 * validData
 * validates the data
 *
 * @param db DBCon object to use
 * @param lic string license
 * @param testid int test id
 * @return true if valid data
 */
function validData(DBCon $db, $lic = "", $testid = 0) {

	$sql = "SELECT COUNT(*) AS TOT FROM Account INNER JOIN CTSTest ON Account.Id = CTSTest.AccId WHERE Account.License = '{$lic}' AND CTSTest.TestId = {$testid}";

	if(!$db->Query($sql)) {
		// todo handle
		return false;
	}

	$tot = $db->GetRow();

	return (isset($tot["TOT"]) && intval($tot["TOT"]) > 0);
}




/*
 * end_proc
 * ends the process with a message for the user
 * used on failure
 *
 * @param msg (string) message to echo
 * @return void
 */
function end_proc($msg) {
	header("Content-Type: application/javascript");
	echo $msg;
	exit;
}
?>
