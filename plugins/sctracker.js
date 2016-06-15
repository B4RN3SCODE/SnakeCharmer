/*
 * SCTracker
 * Snake Charmer Tracking Plugin
 * Conversion tracking system integration
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
var SCTracker = function() {
	this._version = '1.1.1';
	this._pluginName = "SCTracker";
	this._pluginId = "SC57738";

	this.load = function() {
		if(SC.pluginLoaded(this._pluginId)) {
			SC.reportPluginLoadError(this._pluginName, this._pluginId);
			return false;
		}
		console.log('sc tracker load function called');
	};
}

window.SC.addPlugin(new SCTracker());
