<?php
include("config.php");
include("glob.php");
include("DBCon.php");

/************************************************************
 * eventTriggered
 * records a triggered event
 *
 * EXPECTS:
 *  - eid event id
 *  - dc dispatch code
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

if($EID < 1) {
	$ERR = true;
	$MSG = "invalid event id";
}

if(is_null($DC)) {
	$ERR = true;
	$MSG = "ivalid dispatch code";
}

$db = new DBCon();
if(!$db->Link()) {
	$ERR = true;
	$MSG = "db error";
}

$recorded = record($db, $EID, $DC);

$js = "";

if($recorded["success"]) {
	$js = "/* event triggered */ window.SC.setCookie('{$DC}-{$EID}','trig-success',window.SC._defaultCookieExpire)";
} else {
	$js = "window.SC.reportEventFailure('{$recorded["msg"]}'); // failed to record event triggered";
}


header("Content-Type: application/javascript");

echo $js;

exit;

function record(DBCon $db, $eid, $dc) {

	$ret = array("success" => false, "msg" => "");

	$eid = $db->EscapeQueryStmt($eid);
	$dc = $db->EscapeQueryStmt($dc);

	if(!codeExists($db, $dc)) {
		return array("success" => false, "msg" => "dispatch code does not exist");
	}

	$sql = "INSERT INTO EventTrigger (EventId, TimeStamp, DispatchCodeId) VALUES ({$eid}, CURRENT_TIMESTAMP, (SELECT Id FROM DispatchCode WHERE DispatchCode = '{$dc}'))";
	$db->setQueryStmt($sql);

	if(!$db->Query()) {

		$ret["msg"] = $db->GetLastErrorMsg();

	} else {

		if($db->GetLastInsertedId() > 0) {
			$ret["success"] = true;
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
