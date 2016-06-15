<?php
include("config.php");
include("glob.php");
include("DBCon.php");

/************************************************************
 * eventTriggered
 * records seen notifications
 *
 * EXPECTS:
 *  - eid event id
 *  - dc dispatch code
 *  - nids list of notification ids
 *
 * @author			Tyler J Barnes
 * @contact			b4rn3scode@gmail.com
 * @version			0.0.0.0.1
 * @doc				TBD
 *************************************************************/


$ERR = false;
$MSG = STR_EMP;

$EID = (isset($_REQUEST["eid"]) && !empty($_REQUEST["eid"]) && is_numeric($_REQUEST["eid"])) ? intval($_REQUEST["eid"]) : 0;
$DC = (isset($_REQUEST["dc"]) && !empty($_REQUEST["dc"]) && strlen($_REQUEST["dc"]) > 0) ? $_REQUEST["dc"] : null;
$NIDS = (isset($_REQUEST["nids"]) && !empty($_REQUEST["nids"]) && strlen($_REQUEST["nids"]) > 0) ? explode("-", $_REQUEST["nids"]) : array();

if($EID < 1) {
	$ERR = true;
	$MSG = "invalid event id";
}

if(is_null($DC)) {
	$ERR = true;
	$MSG = "ivalid dispatch code";
}

if(count($NIDS) < 1) {
	$ERR = true;
	$MSG = "invalid nids";
}

$db = new DBCon();
if(!$db->Link()) {
	$ERR = true;
	$MSG = "db error";
}

$recorded = record($db, $EID, $DC, $NIDS);

$js = "";

foreach($recorded as $idx => $record_state) {
	if(!$record_state["success"]) {
		$ERR = true;
		$MSG .= $record_state["msg"];
	}
}

if($ERR) {
	$js = "/* {$MSG} */";
} else {
	$nid_str = implode(",",$NIDS);
	$js = "/* all notifs stored */ window.SC.reportNotifSuccess([{$nid_str}]);";
}


header("Content-Type: application/javascript");

echo $js;

exit;

function record(DBCon $db, $eid, $dc, array $nids) {

	$ret = array();

	$eid = $db->EscapeQueryStmt($eid);
	$dc = $db->EscapeQueryStmt($dc);

	if(!codeExists($db, $dc)) {
		return $ret;
	}

	foreach($nids as $nid) {

		$nid = $db->EscapeQueryStmt($nid);

		$sql = "INSERT INTO NotificationView (NotificationId, EventId, TimeStamp, DispatchCodeId) VALUES ({$nid}, {$eid}, CURRENT_TIMESTAMP, (SELECT Id FROM DispatchCode WHERE DispatchCode = '{$dc}'))";
		$db->setQueryStmt($sql);

		if(!$db->Query()) {

			$ret[] = array("success"=>false, "msg"=>"notif id {$nid} failed");

		} else {

			if($db->GetLastInsertedId() > 0) {
				$ret[] = array("success"=>true, "msg"=>"notif id {$nid} seen");
			}
		}
	}

	return $ret;

}


function codeExists(DBCon $db, $dc) {

	$db->setQueryStmt("SELECT COUNT(*) AS tot FROM DispatchCode WHERE DispatchCode = '{$dc}'");
	$db->Query();
	$tot = $db->GetRow();

	return (intval($tot["tot"]) > 0);
}

?>
