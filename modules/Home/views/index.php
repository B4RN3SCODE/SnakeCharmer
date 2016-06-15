<?php
class index extends SCView {
	public function display() {
		$this->_viewTpl = "index";

		include_once("models/Home.php");

		$acc_data = Home::getAccountList($GLOBALS["APP"]["INSTANCE"]->_dbAdapter, 1);

		$tmplt = file_get_contents("modules/Home/templates/home.tpl");
		$this->_tplData = "";

		foreach($acc_data as $i => $data) {
			$this->_tplData .= str_replace("%%ACCNAME%%", $data["Name"], $tmplt);
			$this->_tplData .= str_replace("%%ACCID%%", $data["Id"], $tmplt);
		}


		$vwData = $this->LoadView();
	}
}
?>
