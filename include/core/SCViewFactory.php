<?php
include_once("include/core/SCView.php");
/*
 * SCViewFactory
 *
 * @author			Tyler Barnes
 * @contact			b4rn3scode@gmail.com
 * @version			1.0
 ***************************************************/
/*+++++++++++++++++++++++++++++++++++++++++++++++++*
 * 				Change Log
 *
 *+++++++++++++++++++++++++++++++++++++++++++++++++*/

class SCViewFactory {

	public static function getView($module, $view) {
		$module = $GLOBALS["APP"]["MODULE_MAP"][strtolower($module)];
		$vw_pth = "modules/{$module}/views/{$view}.php";

		if(!file_exists($vw_pth)) {
			die("Cant find view: {$vw_pth}");
		}
		include_once($vw_pth);
		return new $view();
	}
}
?>

