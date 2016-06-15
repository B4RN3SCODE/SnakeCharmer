/*
 * SCBCMain
 * Snake Charmer for Big Commerce pluting (main)
 *
 * @author		Tyler J Barnes
 * @contact		b4rn3scode@gmail.com
 * @version		1.1.1
 */

/*+++++++++++++++++++++++++++++++++++++++++++++++++++++/
 * Change Log | todo list
 *
 *
 *++++++++++++++++++++++++++++++++++++++++++++++++++++*/


// strict mode
'use strict';

/*
 * main object
 *
 * @return void
 */
var SCBCMain = function() {

	/*	PROPS	*/

	// plugin info
	this._version = '1.1.1';
	this._pluginName = "Snake Charmer for Big Commerce (main)";
	this._pluginId = "SC315501";

	// required props

	// big commerce store hash
	this._storeHash = "";





	/*
	 * load
	 * Function called by SnakeCharmer system in order
	 * to run the plugin. This function is required and
	 * should act like a main() function where all the
	 * functionality is called from
	 *
	 * @return void
	 */
	this.load = function() {

	};




}

window.SC.addPlugin(new SCBCMain());
