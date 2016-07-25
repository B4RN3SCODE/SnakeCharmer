<?php
// required dependencies
include_once("config.php");
include_once("glob.php");
include_once("DBCon.php");
include_once(dirname(__FILE__) . "/../cts/ConversionVoodoo.php");


$license = (isset($_REQUEST["license"]) && !empty($_REQUEST["license"]) && !is_null($_REQUEST["license"]) && strlen($_REQUEST["license"]) > 0) ? $_REQUEST["license"] : null;
$referrer = (isset($_REQUEST["referrer"]) && !empty($_REQUEST["referrer"]) && !is_null($_REQUEST["referrer"]) && strlen($_REQUEST["referrer"]) > 0) ? $_REQUEST["referrer"] : "";
$page = (isset($_REQUEST["page"]) && !empty($_REQUEST["page"]) && !is_null($_REQUEST["page"]) && strlen($_REQUEST["page"]) > 0) ? $_REQUEST["page"] : "";


if(is_null($license)) {
	// todo handle
}

$db = new DBCon();
if(!$db->Link()) {
	// todo handle
	end_proc("/* bad db */");
}

$license = $db->EscapeQueryStmt($license);
$referrer = $db->EscapeQueryStmt($referrer);
$page = $db->EscapeQueryStmt($page);

$ctsdata = getCTSData($db, $license);

if(!isset($ctsdata["TestId"])) {
	// todo handle
	end_proc("/* bad */");
}

$LOAD_SNAKE_CHARMER = false;

// attempt to track
try {


	$detect = new Mobile_Detect;
	$device = "desktop";
	if($detect->isTablet()) {
		$device = "tablet";
	} elseif($detect->isMobile()) {
		$device = "mobile";
	}


	// Test id
	$test_id = (int)$ctsdata["TestId"];
	$cvo_pageid = (int)$ctsdata["PageId"];
	$cvo_mvelmid = (int)$ctsdata["MVElmId"];
	$cvo_mvelmname = $ctsdata["MVElmName"];


	$cvo = new CTS_Tracking(array(
		'test_id' => $test_id,
		'other_vars' => array(
			'traffic_type' => $device,
			'referrer'	=>	$referrer,
			'page'	=>	$page,
		),
	));


	// will make sure its the lander page file
	$is_land_page = false;

	// Splitter code
	if(!$cvo_include = $cvo->split()) {
		// todo handle
	}

	// Tracking code

	$cvo_mvelementids = array($cvo_mvelmid);
	$cvo_mv_element = $cvo->track($cvo_pageid, $cvo_mvelementids);

	if(isset($cvo_mv_element[$cvo_mvelmname]) && $cvo_mv_element[$cvo_mvelmname] == SC_MV_OPT_ON) {
		$LOAD_SNAKE_CHARMER = true;
	}


} catch(Exception $e) {
	// todo handle
}


$js = "";

if($LOAD_SNAKE_CHARMER) {
	$js = "window.SC.run(true);";
} else {
	$js = "window.SC.run(false);";
}

$obj = json_encode($ctsdata, JSON_NUMERIC_CHECK);
$js .= " window.SC.pluginBind('SCTracker.setTestData', [{$obj}]);";

end_proc($js);



/*
 * getCTSData
 * gets the CTS Test data
 *
 * @param db DBCon object to use
 * @param lic string license
 * @return array of data
 */
function getCTSData(DBCon $db, $lic = "") {


	$sql = "SELECT ctst.TestId, ctsp.PageId, ctsmv.MVElmId, ctsmv.MVElmName
			FROM CTSTest AS ctst
			INNER JOIN Account AS a
			ON ctst.AccId = a.Id
			INNER JOIN CTSTestPage AS ctsp
			ON ctst.TestId = ctsp.CTSTestId
			INNER JOIN CTSTestMVElement AS ctsmv
			ON ctst.TestId = ctsmv.CTSTestId
			WHERE a.License = '{$lic}'";

	if(!$db->Query($sql)) {
		// todo handle
		return array();
	}

	$data = $db->GetRow();

	return $data;

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
