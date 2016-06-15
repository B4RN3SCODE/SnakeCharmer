<?php
include_once("include/core/SCController.php");
/*****************************************************
 * SCControllerFactory
 *
 * @author			Tyler Barnes
 * @contact			b4rn3scode@gmail.com
 * @version			1.0
 ***************************************************/
/*+++++++++++++++++++++++++++++++++++++++++++++++++*
 * 				Change Log
 *
 *+++++++++++++++++++++++++++++++++++++++++++++++++*/
class SCControllerFactory {

	public static function getController($module) {
		$pth = CONTROLLERS_PTH;
		$module = $GLOBALS["APP"]["MODULE_MAP"][strtolower($module)];
		$ctlName = "{$module}Controller";

		$newController = "{$pth}{$module}/{$ctlName}.php";
		if(!file_exists($newController)) {
			die("Cant find controller");
		}
		include_once($newController);
		return new $ctlName();
	}

}
?>
