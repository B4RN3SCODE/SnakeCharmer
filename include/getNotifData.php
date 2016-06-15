<?php
include("config.php");
include("glob.php");
include("DBCon.php");

/************************************************************
 * getNotifData
 * Gets event, notification, page data
 *
 * EXPECTS:
 *  - license (string license number)
 *  - page (string page uri to verify against page/event)
 *
 * @author			Tyler J Barnes
 * @contact			b4rn3scode@gmail.com
 * @version			0.0.0.0.1
 * @doc				TBD
 *************************************************************/
/*+++++++++++++++++++++++++++++++++++++++++++++++++++++/
 * Change Log | todo list
 *
 *
 *
 *++++++++++++++++++++++++++++++++++++++++++++++++++++*/

// request origin -- url components for validation
$_ORIGIN_ = (isset($_SERVER["HTTP_REFERER"]) && validUrl($_SERVER["HTTP_REFERER"])) ? getDomain($_SERVER["HTTP_REFERER"]) : STR_EMP;

// license number
$_LICENSE_ = (isset($_REQUEST["license"]) && !empty($_REQUEST["license"]) && strlen($_REQUEST["license"]) > 0) ? $_REQUEST["license"] : STR_EMP;
// page uri
$_PAGE_ = (isset($_REQUEST["page"]) && !empty($_REQUEST["page"]) && strlen($_REQUEST["page"]) > 0) ? $_REQUEST["page"] : STR_EMP;
// dispatch codes sent
$_DCODES_ = (isset($_REQUEST["dcodes"]) && !empty($_REQUEST["dcodes"])) ? explode(";", $_REQUEST["dcodes"]) : array();


if(empty($_LICENSE_) || $_LICENSE_ == STR_EMP) {
	end_proc("window.SC.reportRequestFaulure('Bad License string');");
}

if(empty($_PAGE_) || $_PAGE_ == STR_EMP) {
	end_proc("window.SC.reportRequestFaulure('Bad Page string');");
}

// database object
$db = new DBCon();
if(!$db->Link()) {
	end_proc("window.SC.reportRequestFaulure('Failed to connect to database');");
}

if(!validLicense($db, $_LICENSE_, $_ORIGIN_)) {
	end_proc("window.SC.reportInvalidLicense('Invalid Request');");
}


// return data
$_DATA_ = array();
// event ids
$tmp_eids = array();

$tmp_page_event_data = getPageEventData($db, $_LICENSE_, $_PAGE_, $_DCODES_);
$has_data = false;

foreach($tmp_page_event_data as $idx => $tmp_data) {

	$has_data = true; // theres at least one record

	$tmp_eids[] = $tmp_data["EID"];
	unset($tmp_data);

}


// no need to continue if there isnt data
if(!$has_data) {
	end_proc("/* No Data */");
}

// actions
$tmp_actions = getEventActions($db, $tmp_eids);
// make sure theres at least on action
if(count($tmp_actions) < 1 || !isset($tmp_actions[0]["EAction"])) {
	end_proc("/* No Actions Set Up For Event */");
}

$tmp_notifications = getNotifications($db, $_LICENSE_, $tmp_eids);
$tmp_nids = array();

// clean up unused stuff
unset($tmp_eids, $has_data);

// check for notifs
$has_notifications = false;
// store the ids temporarily
foreach($tmp_notifications as $idx => $tmp_data) {

	$has_notifications = true;

	$tmp_nids[] = $tmp_data["NID"];
	unset($tmp_data);

}

$tmp_links = getNotificationLinks($db, $_LICENSE_, $tmp_nids);

if($has_notifications) {
	$_DATA_ = array(
		"page_event"	=>	$tmp_page_event_data,
		"actions"		=>	$tmp_actions,
		"notifications"	=>	$tmp_notifications,
		"links"			=>	$tmp_links,
	);
}

// clean up unused shit
unset($tmp_page_event_data, $tmp_actions, $tmp_notifications, $tmp_links, $has_notifications, $tmp_nids);

header("Content-Type: application/javascript");

// echo the results
echo "window.SC._notificationData = " . json_encode($_DATA_, JSON_NUMERIC_CHECK) . ";if(!window.SC.setUpEvents()) { console.log('no event fo you'); }";
// bye bye
exit;



//////////////////////////////////////////////////////////////
/////////////// functions ////////////////////////////////////
//////////////////////////////////////////////////////////////


function validLicense(DBCon $db, $lic, $dom) {

	$lic = $db->EscapeQueryStmt($lic);
	$dom = $db->EscapeQueryStmt($dom);

	$db->setQueryStmt("SELECT COUNT(*) AS tot FROM Account WHERE Domain = '{$dom}' AND License = '{$lic}'");
	$db->Query();
	$isValid = $db->GetRow();
	return (intval($isValid["tot"]) > 0);
}

/*
 * getThemeSkin
 * gets the page event data
 *
 * @param db (DBCon) Database object used to query
 * @param lic (string) the license number
 * @param pg (string) the page
 * @return array of theme data
 */
function getPageEventData(DBCon $db, $lic = "", $pg = "", array $dcodes = array()) {

	$lic = $db->EscapeQueryStmt($lic);
	$pg = $db->EscapeQueryStmt($pg);
	$strip_pg = strip_url_params($pg);

	// removes http, https, ://, and www. (if it exists) from url
	// that way http://example.com/page works the same if accessed by https://www.example.com/page
	$pg = preg_replace("~^(.+)\:\/\/(www)?\.?~", "", $pg);
	$strip_pg = preg_replace("~^(.+)\:\/\/(www)?\.?~", "", $strip_pg);

	/* parse dcodes */
	$parsed_codes = array();
	foreach($dcodes as $i => $c) {
		$splt = explode("-", $c);
		if($splt && count($splt) == 2) {
			$parsed_codes[intval($splt[1])] = $splt[0];
		}
	}

	unset($dcodes);

	$sql = "SELECT
	e.Id AS EID, et.Type AS EType, e.SubjectAttr AS EIdentifier, e.SubjectVal AS EAttrVal, e.TimeOut AS TimeOut, e.Queued AS Queued,
	p.Uri AS PageUri, CASE WHEN p.IgnoreParams > 0 THEN true ELSE false END AS IgnoreParams, 'false' as HasTriggered
		FROM Event AS e
		INNER JOIN EventType AS et
		ON e.EventTypeId = et.Id
		INNER JOIN PageEvent AS pe
		ON e.Id = pe.EventId
		INNER JOIN Page AS p
		ON pe.PageId = p.Id
		INNER JOIN Account AS a
		ON e.AccId = a.Id
		WHERE e.Active = 1 AND e.Del <> 1 AND et.Del <> 1 AND p.Active = 1 AND p.Del <> 1 AND a.Active = 1 AND a.Del <> 1 AND ((p.Uri LIKE '%{$pg}' AND p.IgnoreParams = 0) OR (p.Uri LIKE '%{$strip_pg}' AND p.IgnoreParams = 1)) AND a.License = '{$lic}'";

	$db->setQueryStmt($sql);
	if(!$db->Query()) {
		// TODO HANDLE ERROR
		return array();
	}

	$edata = $db->GetAll();

	// set the DispatchCode -- random string hashed with md5 algrthm
	$now = time();
	foreach($edata as $idx => &$e) {
		if(isset($parsed_codes[intval($e["EID"])]) && !empty($parsed_codes[intval($e["EID"])]) && !is_null($parsed_codes[intval($e["EID"])])) {
			$e["DispatchCode"] = $parsed_codes[intval($e["EID"])];
		} else {
			$salts = getDispatchSalts();
			$dc = md5("{$salts[0]}-{$now}-{$salts[1]}-{$e["EID"]}-{$salts[2]}");
			$db->setQueryStmt("INSERT INTO DispatchCode (DispatchCode) VALUES ('{$dc}')");
			if($db->Query()) {
				$e["DispatchCode"] = $dc;
			}
		}
		unset($e);
	}

	return $edata;
}


/*
 * getElmAttributes
 * Gets the event actions
 *
 * @param db (DBCon) database obj to use
 * @param eIds (array) list of event record ids
 * @return array of the attributes
 */
function getEventActions(DBCon $db, array $eIds = array("0")) {
	$str = implode(",",$eIds);

	$sql = "SELECT a.Name AS EAction, ea.EventId AS EID FROM Action AS a INNER JOIN EventAction AS ea ON a.Id = ea.ActionId WHERE a.Del <> 1 AND ea.EventId IN ({$str});";
	$db->setQueryStmt($sql);
	$db->Query();
	return $db->GetAll();
}



/*
 * getNotifications
 * Gets notifications
 *
 * @param db (DBCon) database obj to use
 * @param lic (string) the license number
 * @param eIds array of event ids
 * @return array of notifications
 */
function getNotifications(DBCon $db, $lic = "", array $eIds = array("0")) {

	$lic = $db->EscapeQueryStmt($lic);
	$str = implode(",", $eIds);

	$sql = "SELECT n.Id AS NID, n.Title AS NTitle, n.Media AS NMedia, n.Body AS NBody, e.Id AS EID, 'false' AS HasSeen
		FROM Notification AS n
		INNER JOIN EventNotification AS en
		ON n.Id = en.NotificationId
		INNER JOIN Event AS e
		ON en.EventId = e.Id
		INNER JOIN Account AS a
		ON e.AccId = a.Id
		WHERE n.Active = 1 AND n.Del <> 1 AND e.Active = 1 AND e.Del <> 1 AND a.Active = 1 AND a.Del <> 1 AND a.License = '{$lic}' AND e.Id IN ({$str})";

	$db->setQueryStmt($sql);
	if(!$db->Query()) {
		// TODO something
		return array();
	}

	$tmp = $db->GetAll();
	foreach($tmp as $idx => $t_data) {
		$tmp[$idx]["QdEvent"] = getNotificationQueuedEvents($db, $t_data["NID"]);
	}

	return $tmp;
}



/*
 * getNotificationLinks
 * Gets links for notifications
 *
 * @param db (DBCon) database obj to use
 * @param lic (string) the license number
 * @param nids array of notification ids
 * @return array of links
 */
function getNotificationLinks(DBCon $db, $lic = "", array $nids = array("0")) {

	$lic = $db->EscapeQueryStmt($lic);
	$str = implode(",", $nids);

	$sql = "SELECT l.Uri AS LinkUri, nl.NotificationId AS NID FROM Link AS l INNER JOIN Account AS a ON l.AccId = a.Id INNER JOIN NotificationLink AS nl ON l.Id = nl.LinkId WHERE a.Active = 1 AND a.Del <> 1 AND l.Active = 1 AND l.Del <> 1 AND a.License = '{$lic}' AND nl.NotificationId IN ({$str});";

	$db->setQueryStmt($sql);
	if(!$db->Query()) {
		// TODO something
		return array();
	}
	return $db->GetAll();
}



/*
 * getNotificationQueuedEvents
 * Gets queued events for a notification
 *
 * @param db (DBCon) database obj to use
 * @param nid int notif id
 * @return array of event ids
 */
function getNotificationQueuedEvents(DBCon $db, $nid = 0) {

	if($nid < 1) {
		return array();
	}

	$nid = $db->EscapeQueryStmt($nid);

	$sql = "SELECT EventId FROM NotificationQueuedEvent WHERE NotificationId = {$nid} AND Active = 1 AND Del = 0;";

	$db->setQueryStmt($sql);
	if(!$db->Query()) {
		// TODO something
		return array();
	}

	$eids = array();

	$tmp = $db->GetAll();
	foreach($tmp as $idx => $t_data) {
		$eids[] = intval($t_data["EventId"]);
	}

	return $eids;
}




/*
 * validUrl
 * checks that param is valid url
 *
 * @param str: url
 * @return true if valid
 */
function validUrl($str = STR_EMP) {
	$str = trim(preg_replace("~http\:\/\/|https\:\/\/|www\.~", "", $str));
	return preg_match("~[-a-zA-Z0-9\:\%\.\_\+\~\#\=]{2,256}\.[a-z]{2,10}\b([-a-zA-Z0-9\:\%\_\+\.\~\#\?\&\/\/\=]*)~", $str);
}


/*
 * getDomain
 * gets the domain
 *
 * @param url to get domain from
 * @return string domain
 */
function getDomain($url = STR_EMP) {
	preg_match("/[a-z0-9\-]{1,63}\.[a-z\.]{2,10}$/", parse_url($url, PHP_URL_HOST), $_domain_tld);
	return $_domain_tld[0];
}



/*
 * getDispatchSalts
 * gets strings for the dispatch code salt
 *
 * @return array salt strings
 */
function getDispatchSalts() {
	$alpha_num = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	$lng = strlen($alpha_num);
	$salts = array();
	for($i = 0; $i < 3; $i++) {
		$salts[$i] = "";
		for($j = 0; $j < 3; $j++) {
			$salts[$i] .= $alpha_num[mt_rand(0,$lng-1)];
		}
	}

	return $salts;
}



/*
 * strip_url_params
 * strips a url of the parameters
 *
 * @param url string url
 * @return string new url or FALSE on faulure
 */
function strip_url_params($url) {
	if(!validUrl($url)) {
		return false;
	}

	if(strpos($url, "?") === false) {
		return $url;
	}

	$url_pieces = parse_url($url);
	$url_pieces["scheme"] .= "://";

	if(isset($url_pieces["query"])) {
		unset($url_pieces["query"]);
	}
	if(isset($url_pieces["fragment"])) {
		unset($url_pieces["fragment"]);
	}

	$ret = "";
	foreach($url_pieces as $component => $val) {
		$ret .= "$val";
	}

	return $ret;
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
