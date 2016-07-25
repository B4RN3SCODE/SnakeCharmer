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

	// plugin info
	this._version = '1.1.1';
	this._pluginName = "SCTracker";
	this._pluginId = "SC57738";


	// properties
	this._testData = {
		TestId: 0,
		PageId: 0,
		MVElmId: 0,
		MVElmName: ''
	};

	this._dataSet = false;
	this._converted = false;

	this._eventName = 'hackyevent';

	this._eventListenerSet = false;



	// convert endpoint
	this._convertUri = '//sc.conversionvoodoo.com/include/convert.php';


	this._cookieName = 'sctrackerec';
	this._cookieValue = {};





	/*
	 * load
	 * Function called by SnakeCharmer system in order
	 * to run the plugin. This function is required and
	 * should act like a main() function where all the
	 * functionality is called from
	 *
	 * @param event event name to trigger when plugin loads
	 * @return void
	 */
	this.load = function(event) {
		// make sure SC exists
		if(!window.SC || typeof window.SC == 'undefined') {
			console.error('SCBCMain : Cannot find SC... idk how this is even running then.~!?!~.');
			return void 0;
		}


		if(!!event) {
			$(document).trigger(event);
			window.SC.removePluginLoadedEvent(this._pluginName);
		}


	};





	/*
	 * getCookieVal
	 * Gets the cookie value
	 *
	 * @return void
	 */
	this.getCookieVal = function() {
		var tmpCookie = window.SC.getCookie(this._cookieName);
		this._cookieValue = (!!tmpCookie && tmpCookie.length > 0) ? JSON.parse(tmpCookie) : {};
	};





	/*
	 * inCookie
	 * Checks to see if an event is already been cookied
	 *
	 * @param eid event ID
	 * @return true if exists in cookie
	 */
	this.inCookie = function(eid) {
		return (this._cookieValue.eventId && typeof this._cookieValue.eventId != 'undefined' && this._cookieValue.eventId == eid);
	};





	/*
	 * updateCookie
	 * Updates the cookie to track seen notifications
	 * using SC.setCookie function
	 *
	 * @param eid int event ID
	 * @param data object of convert data
	 * @return void
	 */
	this.updateCookie = function(eid, data) {
		this.getCookieVal();

		if(!eid || typeof eid == 'undefined' || parseInt(eid) < 1) {
			eid = this._cookieValue.eventId;
		}

		var cookieChanged = false;
		if(!this.inCookie(eid)) {

			var o = {eventId: eid};
			for(var prop in data) {
				o[prop] = data[prop];
			}
			this._cookieValue = o;
			cookieChanged = true;

		} else {

			for(var prop in data) {
				if(this._cookieValue[prop] != data[prop]) {
					this._cookieValue[prop] = data[prop];
					cookieChanged = true;
				}
			}

		}

		if(cookieChanged) {
			window.SC.setCookie(this._cookieName, JSON.stringify(this._cookieValue), 2);
		}
	};




	/*
	 * setTestData
	 * Sets the test data info
	 *
	 * @param obj object of data
	 * @return void
	 */
	this.setTestData = function(obj) {
		this._dataSet = false;
		for(var prop in obj) {
			if(prop in this._testData) {
				this._testData[prop] = obj[prop];
			}
		}
		this._dataSet = true;
		if(this._eventListenerSet) {
			$(document).trigger(this._eventName);
			this._eventListenerSet = false;
			$(document).off(this._eventName);
		}
	};




	/*
	 * convert
	 * tracks a conversion
	 *
	 * @param sssssssssssssssssuper boolean true if super convert default false
	 * @param leadVal float value for lead
	 * @param othVars array of other variables to store
	 * @return void
	 */
	this.convert = function(sssssssssssssssssuper, leadVal, othVars) {
		this.getCookieVal();
		if(this._converted) { return void 0; }
		sssssssssssssssssuper = (sssssssssssssssssuper === true);
		leadVal = (leadVal && typeof leadVal != 'undefined' && leadVal > 0 && !isNaN(parseFloat(leadVal))) ? parseFloat(leadVal) : 0;
		if(!othVars || typeof othVars == 'undefined' || othVars.length < 1) {
			othVars = [];
		}


		var scriptParams = '?license='+window.SC.getLicense()+'&testid='+this._testData.TestId;
		if(sssssssssssssssssuper) {
			scriptParams += '&super=true';
		}
		if(leadVal > 0) {
			scriptParams += '&leadval='+leadVal.toString();
		}
		if(othVars.length < 1) {
			if(this._cookieValue && this._cookieValue.eventId && typeof this._cookieValue.eventId != 'undefined' && this._cookieValue.promoType &&
				typeof this._cookieValue.promoType != 'undefined' && this._cookieValue.promoType.length > 0) {

				othVars.push({promoType: this._cookieValue.promoType});

			}
		} else {
			var hasPromoType = false;
			for(var i = 0; i < othVars.length; i++) {
				if(othVars[i].promoType && typeof othVars[i].promoType != 'undefined' && othVars[i].promoType.length > 0) {
					hasPromoType = true;
					break;
				}
			}

			if(!hasPromoType) {
				if(this._cookieValue && this._cookieValue.eventId && typeof this._cookieValue.eventId != 'undefined' && this._cookieValue.promoType &&
					typeof this._cookieValue.promoType != 'undefined' && this._cookieValue.promoType.length > 0) {

					othVars.push({promoType: this._cookieValue.promoType});
				}
			}
		}

		if(othVars.length > 0) {
			scriptParams += '&othvars='+encodeURIComponent(JSON.stringify(othVars));
		}


		window.SC.addScript(document.location.protocol+this._convertUri+scriptParams, true);
		this._converted = true;
	};



	/*
	 * setUpConver
	 * hack
	 *
	 * @param sssssssssssssssssuper boolean true if super convert default false
	 * @param leadVal float value for lead
	 * @param othVars array of other variables to store
	 * @return void
	 */
	this.setUpConvert = function(sssssssssssssssssuper, leadVal, othVars) {
		if(this._converted) { return void 0; }
		if(!othVars || typeof othVars == 'undefined' || othVars.length < 1) {
			othVars = [];
		}
		if(this._dataSet) {
			this.convert(sssssssssssssssssuper, leadVal, othVars);
		} else {
			this._eventListenerSet = true;
			var me = this;
			$(document).on(this._eventName, function() {
				me.convert(sssssssssssssssssuper, leadVal, othVars);
			});
		}
	};




}

window.SC.addPlugin(new SCTracker());
