<?php

class Home {

	public static function getAccountList(DBCon $db, $userid = 0) {
		$userid = $db->EscapeQueryStmt($userid);
		$sql = "SELECT a.Id, a.Name, a.Domain FROM Account AS a INNER JOIN UserAccess AS ua ON a.Id = ua.AccountId WHERE ua.UserId = {$userid}";

		$db->setQueryStmt($sql);
		$db->Query();

		return $db->GetAll();
	}
}
?>
