/*
 * SnakeCharmer
 * Snake Charmer Application
 *
 * @author		Tyler J Barnes
 * @contact		b4rn3scode@gmail.com
 * @version		1.2.2
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
 * @param config object with config values
 * @return void
 */
var SnakeCharmer = function(config) {
	// version
	this._version = '1.2.2';
	// config
	this._config = (!!config && (typeof config).toLowerCase() == 'object') ? config : {};
	// domain
	this._siteDomain = '';
	// stores the page title
	this._defaultPageTitle = '';
	// jQuery
	this._$ = ('undefined'==typeof jQuery) ? -1 : jQuery;
	// theme data
	this._themeData = {};
	// notification data
	this._notificationData = {};
	// html for widget
	this._widget = (this._$ === -1) ? '': this._$('<div id="SCWidget" class="sc_main" style="z-index:999;"></div>');
	// rewrite cache
	this._widgetElmsRemoved = [];
	// sidebar
	this._sidebar = (this._$ === -1) ? '': this._$('<div id="SCSB" class="sc_main" style="z-index:999;"><div class="bigchat"><div id="ChatClose" class="header"><div class="name"></div><div class="time"></div><div class="close">Close</div></div><div class="primarychat"></div></div></div>');
	// tracks the state of whats displayed
	this._displayState = { widget: false, sidebar: false };
	// default getThemeUri
	this._defaultGetThemeUri = '//zoltan.conversionvoodoo.com/include/getTheme.php';
	// default getNotifDataUri
	this._defaultGetNotifDataUri = '//zoltan.conversionvoodoo.com/include/getNotifData.php';
	// default eventTriggered url
	this._defaultEventTriggeredUri = '//zoltan.conversionvoodoo.com/include/eventTriggered.php';
	// default notifSeen url
	this._defaultNotifSeenUri = '//zoltan.conversionvoodoo.com/include/notifSeen.php';
	// boot url
	this._defaultBootUri = '//sc.conversionvoodoo.com/include/boot.php';


	// notification sound location
	//		** note: to change this, after using SC_AUTO_INIT = false to
	//			prevent the app from auto initializing, set SC._notificationSoundFile
	//			to point to the new sound location -- then do SC.ini()
	this._notificationSoundFile = '//d61fqxuabx4t4.cloudfront.net/snakecharmer/media/notif.mp3';
	// audio object to play
	this._audio = null;

	// cookie name(s) & stuff
	this._eventCookieName = 'snevdat';
	this._defaultCookieExpire = 10; // days

	// notification timeout
	this._notificationTimeout = 2; //seconds


	// events manual trigger... dont store trigger or write cookies
	this._dispatchedCodes = [];
	// pool for queued events
	this._eventPool = [];
	// event in queue waiting to be triggered
	this._eventQueue = [];
	// timer set for managing queue
	this._queueTime = null;
	// tracks queue timer state
	this._queueTimeActive = false;
	// custom js to execute after event triggers
	this._injectScript = [];

	// ids to show
	this._queuedMsgIds = [];
	// timer
	this._notifTimer = null;
	//track that timer
	this._notifTimerActive = false;


	// custom event tracker
	this._customEvents = 0;


	// identifiers
	this._identifiers = {
		id: '#',
		class: '.',
		tag: '',
	};


	// temporary property to test effect of widget
	// ** REMOVE IMMEDIATELY AFTER TEST COMPLETE
	this._ghostMode = false;
	// END GHOST MODE REMOVE ME WHEN TEST DONE



	// array of plugins to load and run with SC
	// see documentation on how to build plugins
	// that comply with sc framework
	this._plugins = [];
	// plugins to load
	this._loadPlugins = ['//d61fqxuabx4t4.cloudfront.net/snakecharmer/plugins/sctrackerm.js'];
	// running plugins
	this._loadedPlugins = [];


	// this is sorta hacky but need it for now
	this._notificationDataCompleteEventListenerSet = false;
	// event anem
	this._notificationDataCompleteEventName = 'notifdatacomplete';

	// plugin load event
	this._pluginLoadedEventName = 'pluginloadcomplete';
	this._pluginLoadedEvents = {};


	// track to see if should play sound
	this._playNotificationSound = true;





	/*
	 * ini
	 * initializes the app setting config
	 *
	 * @return false if failure
	 */
	this.ini = function() {
		var loc = window.location;

		// insert css for SC
		this.installJsCss();

		// make sure jQuery is loaded
		if(this._$ == -1 || typeof this._$ == 'undefined') {
			this.reportDependencies();
			return false;
		}

		// mandatory config vars
		var conf = ['license','themeId'];
		// make sure all config is correct
		for(var i in conf) {
			if(!(conf[i] in this._config)) {
				console.info('Using default configuration for Snake Charmer');
				this._config = undefined;
				break;
			}
		}

		// set default config if undefined
		if(typeof this._config == 'undefined') {
			this._config = this.getDefaultConfig();
		} else {
			// let user override page URI for now
			this._config.pageUri = (!!this._config.pageUri && this._config.pageUri.length > 0) ? this._config.pageUri : loc.protocol+'//'+loc.hostname+loc.pathname;
		}

		// boot the app
		this.boot();
	};






	/*
	 * boot
	 * boots the app by determining if SC should
	 * exec and run or just run plugins
	 *
	 * @return void
	 */
	this.boot = function() {
		var u = "";
		var r = document.referrer;
		if(!!r && r.length > 0) {
			u = (new URL(r)).host;
		}
		var scriptParams = '?license='+this._config.license+'&referrer='+encodeURIComponent(u)+'&page='+encodeURIComponent(window.location.pathname);
		// check to see if cv force is applied
		var cvForce = window.location.search.split('&');
		for(var s = 0; s < cvForce.length; s++) {
			var match = cvForce[s].match(/cvforce|cvmvforce/g);
			if(match && match != null && match.length > 0) {
				scriptParams += '&'+cvForce[s];
			}
		}
		this.addScript(document.location.protocol+this._defaultBootUri+scriptParams, true);
	};




	/*
	 * run
	 * runs the app
	 *
	 * @param exec boolean true if run as normal (false=do not run... for A/B testing)
	 * @return void
	 */
	this.run = function(exec) {
		exec = (exec === true);

		// set domain beacuse plugins will use it
		this._siteDomain = this.getSiteDomain();

		// check for plugins and set up to load em
		if(this._loadPlugins.length > 0) {
			this.setUpPluginLoad();
		}

		// init audio obj
		this._audio = new Audio(this._notificationSoundFile);

		// set page title
		this._defaultPageTitle = this._$('title').text();


		if(exec) {

			// check for ghost mode and hide widget
			// *** TEMPORARY TEST ***
			if(this._ghostMode) {
				this._$('head').append('<style>#SCWidget,#SCSB{display:none;}</style>');
			}
			// ** END TEMP ADDITION **

			this.getThemeData();

		} else {

			// reset this wanker because setCookie wont work
			this._ghostMode = false;

			// if the event listener is set, but we arent running
			// the main widget, make sure plugins load (mainly tracking plugin)
			if(this._notificationDataCompleteEventListenerSet) {
				this._$(document).trigger(this._notificationDataCompleteEventName);
			}

		}
	};




	/*
	 * getLicense
	 * gets the license
	 *
	 * @return string license
	 */
	this.getLicense = function() {
		return this._config.license;
	};




	/*
	 * installCss
	 * installs snake charmer css
	 *
	 * @param d document
	 * @return void
	 */
	this.installJsCss = function() {
		this._$('head').append('<link type="text/css" rel="stylesheet" href="//d61fqxuabx4t4.cloudfront.net/snakecharmer/css/sc.style.min.css"><script src="//d61fqxuabx4t4.cloudfront.net/snakecharmer/js/autosize.min.js"></script><link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700" rel="stylesheet" type="text/css"><link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">');
	};




	/*
	 * getDefaultConfig
	 * gets default configuration values for app
	 *
	 * @return object of config vars and values
	 */
	this.getDefaultConfig = function() {

		var ret = {}; // config to return

		// get src of SCJS file so we can parse for configuration
		var tmpuri = this._$('#SCJS').attr('src');
		var pieces = tmpuri.split('?')[1].split('&');
		var tmp = [];
		for(var p = 0; p < pieces.length; p++) {
			tmp = pieces[p].split('=');
			// temp addition to test effect
			if(tmp[0].toLowerCase() == 'ghost' && tmp[1].toLowerCase() == 'true') {
				this._ghostMode = true;
				continue;
			}
			// end temp addition
			ret[tmp[0]] = tmp[1];
		}
		ret.pageUri = window.location.protocol+'//'+window.location.hostname+window.location.pathname+window.location.search;

		return ret;
	};




	/*
	 * getSiteDomain
	 * gets the domain of the site of current url
	 *
	 * @return string domain
	 */
	this.getSiteDomain = function() {
		var host = window.location.host;
		var dom = host.match(/[a-z0-9\-]{1,63}\.[a-z\.]{2,10}$/g);
		return (dom != null && 'undefined' != typeof dom && dom.length > 0) ? dom[0] : host;
	};




	/*
	 * ajax
	 * ajax functionality for the app
	 * feel free to pass these parameters to the function
	 * in any manner you want to... any order.  the logic
	 * will figure out what each parameter is.
	 *
	 * error callback function is not to be passed because
	 * the framework will automatically handle an ajax error
	 * only a success function is available to be overwritten,
	 * otherwise the default ajax callback will be used
	 *
	 * @param url string target url
	 * @param data object of data to send
	 * @param type string type of request default POST *NOT REQUIRED*
	 * @param succ function to exectue on success
	 * @return true if successful
	 */
	this.ajax = function() {
		var args = arguments, u, d, t, e, s, has_data = false;

		if(args.length < 2) {
			console.error('Invalid arguments passed to ajax function. Required: target_url, data_to_send');
			return false;
		}

		for(var i in args) {

			if('object' == (typeof args[i]).toLowerCase()) {
				for(var p in args[i]) {
					has_data = true;
					break;
				}
				d = args[i];
				if(!has_data) {
					console.warn('No data passed to SnakeCharmer.ajax function [empty object]. Sending request with no data');
				}


			} else if('function' == (typeof args[i]).toLowerCase()) {

				s = args[i];

			} else if('string' == (typeof args[i]).toLowerCase()) {

				if(args[i].toUpperCase() == 'POST' || args[i].toUpperCase() == 'GET') {
					t = args[i].toUpperCase();
				} else if(this.validUrl(args[i])) {
					u = args[i];
				}


			}
		}

		e = this.defaultAjaxErrCb;
		t = (!t || t == null || t == undefined || typeof t == 'undefined') ? 'POST' : t;
		s = (!s || s == null || s == undefined || typeof s == 'undefined') ? this.defaultAjaxSuccCb : s;

		//this._$.ajaxSetup({
			//cache: false,
			//headers: {
				//'Cache-Control': 'no-cache, no-store, must-revalidate'
			//}
		//});
		this._$.ajax({
			url: u, data: d, type: t, error: e,	success: function(d) { s(d); }
		});

		return true;
	};



	/*
	 * getThemeData
	 * gets the theme data for the widget
	 *
	 * @return void
	 */
	this.getThemeData = function() {

		if(!this._config.getThemeUri || typeof this._config.getThemeUri == 'undefined') {
			this._config.getThemeUri = this._defaultGetThemeUri;
		}

		var scriptParams = '?theme='+this._config.themeId+'&license='+this._config.license;

		this.addScript(document.location.protocol+this._config.getThemeUri+scriptParams, true);

	};



	/*
	 * getNotifData
	 * gets notification, event, and page data
	 *
	 * @reutn void
	 */
	this.getNotifData = function() {

		if(!this._config.getNotifDataUri || typeof this._config.getNotifDataUri == 'undefined') {
			this._config.getNotifDataUri = this._defaultGetNotifDataUri;
		}

		var scriptParams = '?page='+this._config.pageUri+'&license='+this._config.license;
		var dcodes = this.getDisplatchedCookies();
		if(dcodes.length > 0) {
			scriptParams += '&dcodes='+dcodes.join(';');
		}
		this.addScript(document.location.protocol+this._config.getNotifDataUri+scriptParams, true);

	};




	/*
	 * addScript
	 * adds a js script to document
	 *
	 * @param s string full source url
	 * @param asnc bool asyncronous
	 * @return void
	 */
	this.addScript = function(s, asnc) {

		if(!!s && this.validUrl(s)) {
			asnc = (asnc === true);

			var ns2 = document.createElement('script');
			ns2.type = 'text/javascript';
			ns2.async = asnc;
			ns2.src = s;
			var es2 = document.getElementsByTagName('script')[0];
			es2.parentNode.insertBefore(ns2, es2);

		}

	};




	/*
	 * setUpTheme
	 * sets the theme up and renders the
	 * defined elements
	 *
	 * @return void
	 */
	this.setUpTheme = function() {
		var hasData = false;
		for(var p in this._themeData) {
			hasData = true;
			break;
		}
		if(!hasData) {
			return false;
		}
		var tmpstr = '', tmp, outter_class = 'chatbox', set_inner_html = true, show_closer = true;

		for(var x = 0; x < this._themeData.elements.length; x++) {
			 tmp = this._themeData.elements[x];

			if(tmp.ElmTag == 'img') { // NOTE could add more elm types in this condition
				outter_class = 'icon';
				set_inner_html = false;
				show_closer = false;
			}

			this._widget.append(this._$('<div></div>').addClass(outter_class).attr('id', outter_class+'_'+x.toString()));
			if(tmp.ElmUseCloseTag == 1) {
				tmpstr = '<'+tmp.ElmTag+' id="'+tmp.ElmId+'"></'+tmp.ElmTag+'>';
			} else {
				tmpstr = '<'+tmp.ElmTag+' id="'+tmp.ElmId+'" />';
			}
			this._widget.find('#'+outter_class+'_'+x.toString()).append(tmpstr);
			/* TODO
			 * 		add in style, height, width shit here
			 */
			if(tmp.ElmShowCount > 0) {
				this._widget.find('#icon_'+x.toString()).append(this._$('<div class="notification"><small>0</small></div>'));
			}

			if(set_inner_html) {
				this._widget.find(tmp.ElmId).html((tmp.ElmInnerHtml===null)?'':tmp.ElmInnerHtml);
			}

			if(show_closer) {
				this._widget.find('#chatbox_'+x.toString()).append(this._$('<div class="closer"><i class="fa fa-close"></i></div>'));
			}


			// ADD ATTRIBUTES
			for(var y = 0; y < this._themeData.attributes.length; y++) {
				var ytmp = this._themeData.attributes[y];
				if(ytmp.ElmRecordId == tmp.ElmRecordId) {
					this._widget.find('#'+tmp.ElmId).attr(ytmp.ElmAttribute,ytmp.ElmAttributeValue);
					//this._themeData.attributes.splice(y,1); // remove from array so we are eliminating attribes as we use them
				}
			}
			// END ATTRIBUTES
		}
		// clean up
		delete this._themeData.attributes;

		return true;
	};



	/*
	 * setUpEvents
	 * sets up events and the notifications to be displayed
	 *
	 * @return void
	 */
	this.setUpEvents = function(d) {
		var hasData = false;
		for(var p in this._notificationData) {
			hasData = true;
			break;
		}
		if(!hasData) {
			return false;
		}
		// reference for callback function when triggering event
		var me = this;


		// iterate through all events to set everything up
		for(var x = 0; x < this._notificationData.page_event.length; x++) {

			// action list
			var action_list = [];
			// join string of action list
			var action_str = '';
			// notification list
			var notification_list = [];

			var tmp = this._notificationData.page_event[x];

			// check to see if we should queue the event
			if(tmp.Queued > 0) {
				this.poolEvent(tmp);
				continue;
			}

			if(tmp.HasTriggered === true) {
				continue;
			}

			action_list = this.getEventActions(tmp.EID);
			action_str = action_list.join(', ');

			// iterate through notifications and find their links
			for(var j = 0; j < this._notificationData.notifications.length; j++) {
				this._notificationData.notifications[j].links = []; // will need to add links to appropriate notif

				// iterate through links to check for their notification id
				for(var n = 0; n < this._notificationData.links.length; n++) {

					// validate notification
					if(this._notificationData.links[n].NID == this._notificationData.notifications[j].NID) {
						this._notificationData.notifications[j].links.push(this._notificationData.links[n].LinkUri);
					}

				} // END for loop for links

				// only add if event ids match
				if(this._notificationData.notifications[j].EID == tmp.EID) {
					// push the object to the notification list to pass into event function
					notification_list.push(this._notificationData.notifications[j]);
				}

			} // END for loop for notifications

			this.triggerEvent(this._identifiers[tmp.EIdentifier]+tmp.EAttrVal,action_str,tmp,notification_list);

		} // END for loop for events


		if(this._notificationDataCompleteEventListenerSet) {
			this._$(document).trigger(this._notificationDataCompleteEventName);
			$(document).off(this._notificationDataCompleteEventName);
		}

		return true;
	};






	/*
	 * triggerEvent
	 * triggers event with appropriate shit
	 *
	 * @param eid event id int
	 * @param notifs array of notification objects
	 * @return void or whatever i need it to at the time haha
	 */
	this.triggerEvent = function(idnt, act_str, e, notifs) {
		var eid = e.EID;
		var me = this;
		if(e.EType.toLowerCase() == 'timed') {

			this.poolEvent(e);
			this.timeOut(e.EID, parseFloat(e.TimeOut));

		} else if(e.EType.toLowerCase() == 'criteria') {

			this.poolEvent(e);

		} else {

			this._$(idnt).on(act_str, function() {

				if(e.HasTriggered === true) {
					return false;
				}
				e.HasTriggered = true;
				me.setEventCookie(eid);

				// record the event triggering
				if(!me.eventTriggered(e)) {
					console.warn('Failed to record triggered event [ '+eid+' ]');
				}

				me.handleEventNotifData(e, notifs);

				if(me.hasInjectedJs(eid)) {
					me.customScript(eid);
				}

			});

		}


		// if cookie set with event already triggered & in cookie then manually trigger the event
		if(this.manualTrigger(eid)) {
			this._dispatchedCodes.push(e.DispatchCode);

			var elst = act_str.split(',');
			for(var ev = 0; ev < elst.length; ev++) {
				// TODO fix this.... ANGULAR sites
				// one of the indexes is undefined somehow. no idea right now
				if("undefined" == typeof elst[ev]) {
					continue;
				}
				var act_trimmed = elst[ev].trim();
				setTimeout(function(){me._$(idnt).trigger(act_trimmed);},1000);
			}
		}


	};




	/*
	 * handleEventNotifData
	 * handles notification data
	 *
	 * @param
	 * @return void
	 */
	this.handleEventNotifData = function(e, notifs) {
		var eid = e.EID;
		var me = this;
		var has_notifs = false, cnt = isNaN(parseInt(this._$('body').find('.notification small').text())) ? 0:parseInt(this._$('body').find('.notification small').text());
		for(var i = 0; i < notifs.length; i++) {

			if(notifs[i].NID > 0 && (!!notifs[i].NBody || !!notifs[i].NMedia || !!notifs[i].NTitle)) {

				if(notifs[i].EID == eid && notifs[i].HasSeen !== true)
					cnt++;


				has_notifs = true;

			} else {
				console.error('Invalid notification list passed to triggerEvent');
				return false;
			}
		}

		var add_to_sidebar = (this._displayState.sidebar && has_notifs);
		var popUpMsg = '';
		if(has_notifs) {
			if(!add_to_sidebar) {
				for(var elm = 0; elm < this._widgetElmsRemoved.length; elm++) {
					this._widget.prepend(this._widgetElmsRemoved[elm]);
				}
				this._widgetElmsRemoved = []; // reset the list
				/* TODO
				* change this to an iteration of the IDs set up in setUpTheme so we can do .text to the correct notification element...
				* if there is more than one element set up in the them to display notif msg boxes or whatever, we need to fill them
				* ONLY if there are the same number (or more) notifications to be displayed
				*/
				popUpMsg = notifs[0].NBody || notifs[0].NTitle || 'View message...';
				if(popUpMsg.length > 40) {
					popUpMsg = popUpMsg.substr(0,40) + '...';
				}
				this._widget.find('.chatbox span,.chatbox p, .chatbox input, .chatbox label').text(popUpMsg);
				this._widget.find('.notification small').text(cnt.toString());
				// TODO REMOVE IF STMT WHEN TEST DONE
				if(this._ghostMode === false) {
					this._$('title').text('('+cnt.toString()+') '+this._defaultPageTitle);
				}
				// END TMP TST IF STMT
			}
		}

		if(!add_to_sidebar) {
			if(!this._displayState.widget) {
				this.renderWidget(false);
			}

			if(cnt > 0) {
				this.playNotifSound();
			}

		} else {
			this.playNotifSound();
			this.viewNotifications(e, notifs,true);
			this.renderWidget(true);
		}

		this._$('.closer').on('click', function() {
			me._widgetElmsRemoved.push(me._$(this).parent());
			me._$(this).parent().remove();
		});
		this._$('#SCWidget .icon img, #SCWidget .chatbox:nth-child(1)').on('click', function() {
			me.removeWidget(true);
			me.viewNotifications(e, notifs,true);
		});

	};





	/*
	 * clearEventQueue
	 * clears event queue
	 *
	 * @return void
	 */
	this.clearEventQueue = function() {

		var q_splices = [];

		for(var i = 0; i < this._eventQueue.length; i++) {

			this.triggerPooledEvent(this._eventQueue[i]);
			q_splices.push(i);

		}

		for(var x = 0; x < q_splices.length; x++) {
			this._eventQueue.splice(q_splices[x],1);
		}
	};



	/*
	 * clearEventPool
	 * clears event queue either by setting up triggers
	 * or just emptying the queue
	 *
	 * @param trig bool (false to just empty queue)
	 * @return void
	 */
	this.clearEventPool = function(trig) {
		if(!trig || trig === false) {
			this._eventPool = [];
			return void 0;
		}

		for(var i = 0; i < this._eventPool.length; i++) {
			this.triggerPooledEvent(this._eventPool[i].id);
		}

	};




	/*
	 * triggerPooledEvent
	 * triggers a queued event
	 *
	 * @param eid event id
	 * @param acts array of actions
	 * @return void
	 */
	this.triggerPooledEvent = function(eid, acts) {
		if(!eid || eid < 0) {
			return void 0;
		}

		acts = ('object' == typeof acts && acts.length > 0) ? acts : [];

		//var event_splices = [];
		// iterate through queued events for id
		for(var i = 0; i < this._eventPool.length; i++) {

			if(this._eventPool[i].id == eid) {

				// hack to prevent infinite loop with queued timed events...
				// TODO fucking fix this so there isnt an infinite loop
				if(this._eventPool[i].e.EType.toLowerCase() == 'timed') {

					// THIS IS THE HACK

					this.timeOut(this._eventPool[i].e.EID, parseFloat(this._eventPool[i].e.TimeOut)); // NOTE: to figure out infinite loop, determine why sending to triggerEvent (which then sends to timeOut) and the event prop values never change.... this is where it goes back and forth between those two functions

					// END HACKY HACK

				} else {

					var actions = (acts.length > 0) ? acts.join(', ') : this.getEventActions(eid).join(', ');

					var notif_lst = [];
					// get the notifications for the event
					for(var j = 0; j < this._notificationData.notifications.length; j++) {

						if(this._notificationData.notifications[j].EID == eid) {
							notif_lst.push(this._notificationData.notifications[j]);
						}

					} // end notifs for

					this.triggerEvent(this._identifiers[this._eventPool[i].e.EIdentifier]+this._eventPool[i].e.EAttrVal,actions,this._eventPool[i].e,notif_lst);

				} // end hacky else


			} // end id match if


		} // end pool iteration

	};




	/*
	 * startQueuer
	 * starts a periodic check of the queue to
	 * fire pooled events when they are queued
	 * ** events must be pooled before queued to trigger **
	 *
	 * @return void
	 */
	this.startQueuer = function() {
		if(this._queueTimeActive) {
			return void 0;
		}

		var me = this;
		this._queueTime = setInterval(function() {
			if(me._eventQueue.length > 0) {
				me.clearEventQueue();
			}
		}, 1000);

		me._queueTimeActive = this._queueTimeActive = true;

	};




	/*
	 * stopQueuer
	 * stops the queuer from monitoring queued events
	 *
	 * @return void
	 */
	this.stopQueuer = function() {
		if(!this._queueTimeActive)
			return void 0;

		clearInterval(this._queueTime);

		this._queueTime = null;
		this._queueTimeActive = false;

	};




	/*
	 * poolEvent
	 * adds event to the pool
	 *
	 * @param ev event object
	 * @return void
	 */
	this.poolEvent = function(ev) {
		if(!ev || 'object' != typeof ev) {
			return void 0;
		}
		this._eventPool.push({id:ev.EID,e:ev});

	};




	/*
	 * queueEvent
	 * adds event to queue
	 *
	 * @param i event id
	 * @param timer bool true to start queuer (default true)
	 * @return void
	 */
	this.queueEvent = function(i, timer) {
		timer = (!(timer === false));
		this._eventQueue.push(i);
		if(timer) {
			this.startQueuer();
		}

	};




	/*
	 * getEventActions
	 * gets actions for an event
	 *
	 * @param eid event id
	 * @return array of actions
	 */
	this.getEventActions = function(eid) {

		var action_list = [];

		// iterate through actions see if they are for this event
		for(var y = 0; y < this._notificationData.actions.length; y++) {
			// check event IDs
			if(this._notificationData.actions[y].EID == eid) {
				action_list.push(this._notificationData.actions[y].EAction);
			}
		}
		return (action_list.length > 0) ? action_list : [];

	};




	/*
	 * getEventCriteria
	 * gets the event criteria if there is any
	 *
	 * @return array of event criteria
	 */
	this.getEventCriteria = function() {
		var criteria = [];

		for(var i = 0; i < this._notificationData.page_event.length; i++) {

			if(!!this._notificationData.page_event[i].Criteria && !(typeof this._notificationData.page_event[i].Criteria == 'undefined') &&
				this._notificationData.page_event[i].Criteria != null) {

				criteria.push({eventId: this._notificationData.page_event[i].EID, promoType: this._notificationData.page_event[i].PromoType, criteria: JSON.parse(this._notificationData.page_event[i].Criteria)});

			}

		}

		return criteria;
	};




	/*
	 * timeOut
	 * adds a timeout to execute a time-based event
	 *
	 * @param eid event id
	 * @param to int timeout in seconds
	 * @return void
	 */
	this.timeOut = function(eid, to) {
		if(!eid || eid < 1) {
			console.warn('Cannot add time out to event... invalid event id');
			return void 0;
		}

		if(!to || isNaN(parseInt(to)) || !(parseFloat(to) > 0)) {
			console.warn('Using default timeOut time of 1 second');
			to = 1;
		}

		var me = this;
		setTimeout(function() {
			me.handleTimeOutOrCriteria(eid);
		}, (parseFloat(to)*1000));

	};




	/*
	 * handleTimeOutOrCriteria
	 * handles timeout logic or events that are
	 * fired based on criteria
	 *
	 * @param eid event id
	 * @return custom event name
	 */
	this.handleTimeOutOrCriteria = function(eid) {
		if(!eid || eid < 1) {
			return void 0;
		}

		for(var x = 0; x < this._eventPool.length; x++) {
			if(this._eventPool[x].id == eid) {
				this._eventPool[x].e.EIdentifier = 'tag';
				this._eventPool[x].e.EAttrVal = 'body';
				this._eventPool[x].e.EType = 'UserAction';
			}
		}

		this._customEvents++;
		var custom_name = 'custev'+this._customEvents.toString();
		this.triggerPooledEvent(eid,[custom_name]);

		this._$('body').trigger(custom_name);

	};




	/*
	 * hasInjectedJs
	 * checks to see if event has js to execute after
	 * its been triggered
	 *
	 * @param event id
	 * @return true if it has js to inject
	 */
	this.hasInjectedJs = function(eid) {

		if(!eid || eid < 1) {
			return false;
		}

		for(var s = 0; s < this._injectScript.length; s++) {
			if(this._injectScript[s].event == eid) {
				return true;
			}
		}

		return false;
	};




	/*
	 * customScript
	 * executes custom script
	 *
	 * @param event id
	 * @return void
	 */
	this.customScript = function(eid) {

		for(var s = 0; s < this._injectScript.length; s++) {
			if(this._injectScript[s].event == eid) {
				this._injectScript[s].func();
			}
		}

	};




	/*
	 * viewNotifications
	 * opens side bar and stuff
	 *
	 * @param event
	 * @param list of notifications
	 * @param rend bool if sidbar should render
	 * @return void
	 */
	this.viewNotifications = function(E,n,rend) {
		var e = E.EID;

		this._sidebar.find('.bigchat .header .name').text(this._themeData.sidebar.SBTitle);
		this._sidebar.find('.bigchat .header .time').text('just now'); // lazy as fuck right now

		var ids = [];
		var d, ts; // date object, time string
		var qeids = [];

		var me = this;

		for(var i = 0; i < n.length; i++) {
			var ml_display = (i > 0) ? 'none' : 'block';
			var m_id = 'ml', cb_id = 'cb';
			while(this._$('#'+m_id+'0').length > 0) {
				m_id += '_';
			}
			while(this._$('#'+cb_id+'0').length > 0) {
				cb_id += '_';
			}

			if(n[i].EID != e) {
				continue;
			}

			ids.push(n[i].NID);
			d = new Date();
			ts = (d.getHours() < 10) ? '0'+d.getHours().toString() : d.getHours().toString();
			ts += ':';
			ts += (d.getMinutes() < 10) ? '0'+d.getMinutes().toString() : d.getMinutes().toString();


			/*
			 * TODO
			 * 		check if any Style attributes are set in
			 * 		SC._themeData.sidbar and use inline
			 * 		css as STYLE attributes so users can restyle shit
			 * 		-----do so in this mess somewhere:
			 */


			this._sidebar.find('.primarychat').append(this._$('<div></div>').attr('id', m_id+i.toString()).attr('data-evid', e.toString()).attr('data-noid', n[i].NID.toString()).addClass('message').addClass('left').css('display', ml_display));
			this._sidebar.find('.primarychat').append(this._$('<div></div>').addClass('timestamp').text(ts).css('display', ml_display));
			this._sidebar.find('#'+m_id+i.toString()).append('<div class="icon"><img src="'+this._themeData.sidebar.SBImg+'" /></div>');
			this._sidebar.find('#'+m_id+i.toString()).append(this._$('<div></div>').attr('id', cb_id+i.toString()).addClass('chatbubble'));

			if(!!n[i].NTitle) {
				this._sidebar.find('#'+cb_id+i.toString()).append(this._$('<b><p></p></b>').text(n[i].NTitle));
			}
			if(!!n[i].NMedia) {
				this._sidebar.find('#'+cb_id+i.toString()).append(n[i].NMedia);
			}
			if(!!n[i].NBody) {
				this._sidebar.find('#'+cb_id+i.toString()).append(this._$('<p></p>').text(n[i].NBody));
			}
			for(var idx = 0; idx < n[i].links.length; idx++) {
				this._sidebar.find('#'+cb_id+i.toString()).append('<p><a href="'+n[i].links[idx]+'" target="_blank">'+n[i].links[idx]+'</a></p>');
			}


			if(i > 0) {
				this._queuedMsgIds.push('#'+m_id+i.toString());
			}

			n[i].HasSeen = true;

			// check for linked event in queue
			if(n[i].QdEvent.length > 0) {
				for(var qe = 0; qe < n[i].QdEvent.length; qe++) {
					qeids.push(n[i].QdEvent[qe]);
				}
			}


		}

		if(this._queuedMsgIds.length > 0) {
			this.showChatMsgs();
		}


		if(!this.notificationSeen(E,ids)) {
			console.warn('Failed to record seen notifications');
			console.log(e,ids);
		}

		ids = d = ts = undefined; // clean up

		if(!this._displayState.sidebar) {
			this.renderSidebar(rend);
		}

		if(qeids.length > 0) {
			for(var z = 0; z < qeids.length; z++) {
				this.queueEvent(qeids[z]);
			}
		}

		this._$('#ChatClose').on('click', function() {
			me.removeSidebar();
			me.renderWidget(true);
			me._$('#SCWidget .notification small').text("0");
		});

	};





	/*
	 * showChatMsgs
	 * shows msgs
	 *
	 * @param message div id
	 * @return void
	 */
	this.showChatMsgs = function() {
		if(this._queuedMsgIds.length > 0) {
			this.startNotifInterval();
		}
	};






	/*
	 * startNotifInterval
	 * starts notif interval
	 *
	 * @return void
	 */
	this.startNotifInterval = function() {
		if(this._notifTimerActive === true) {
			return false;
		}

		var me = this;
		this._notifTimer = setInterval(function() {
			me._$(me._queuedMsgIds[0]).show();
			me._$(me._queuedMsgIds[0]).next('.timestamp').show();
			me._queuedMsgIds.splice(0,1);
			if(me._queuedMsgIds.length < 1) {
				me.killNotifInterval();
			}
		}, (me._notificationTimeout * 1000));

		this._notifTimerActive = true;
	};






	/*
	 * killNotifInterval
	 * kills the interval
	 *
	 * @return void
	 */
	this.killNotifInterval = function() {
		clearInterval(this._notifTimer);
		this._notifTimerActive = false;
	};




	/*
	 * renderWidget
	 * renders the widget
	 */
	this.renderWidget = function(show_state) {
		show_state = (show_state === true);
		if(this._displayState.widget) {
			return false;
		}
		if(show_state) {
			//this._$('.closer').parent().remove();
			this._$('#SCWidget').show();

		} else {
			this._$('body').append(this._widget);
		}

		this._displayState.widget = true;

		return true;
	};



	/*
	 * removeWidget
	 * removes the widget
	 */
	this.removeWidget = function(hide_state) {
		hide_state = (hide_state === true);
		if(!this._displayState.widget) {
			return false;
		}
		if(hide_state) {
			// add removed items to the cache so we can
			// prepend them back onto the widget later
			var me = this;
			this._$.each(this._$('.closer'), function(i,e) {
				me._widgetElmsRemoved.push(me._$(e).parent());
			});

			me = undefined;

			this._$('.closer').parent().remove();
			this._$('#SCWidget').hide();

		} else {
			this._$('#SCWidget').remove();
		}

		this._displayState.widget = false;

		return true;
	};



	/*
	 * renderSidebar
	 * renders the sidebar
	 */
	this.renderSidebar = function() {
		if(this._displayState.sidebar) {
			return false;
		}
		this._$('body').append(this._sidebar);
		this._displayState.sidebar = true;
		this._$('title').text(this._defaultPageTitle);
		this._$('body').append('<script id="tmpScScr">autosize(document.querySelectorAll("textarea"));</script>');

		return true;
	};



	/*
	 * removeSidebar
	 * removes the sidebar
	 */
	this.removeSidebar = function() {
		if(!this._displayState.sidebar) {
			return false;
		}

		this._$('#SCSB').remove();
		this._displayState.sidebar = false;

		this._$('#tmpScScr').remove();

		this._sidebar = this._$('<div id="SCSB" class="sc_main" style="z-index:999;"><div class="bigchat"><div id="ChatClose" class="header"><div class="name"></div><div class="time"></div><div class="close">Close</div></div><div class="primarychat"></div></div></div>');

		return true;
	};




	/*
	 * playNotifSound
	 * plays a sound for notifications
	 *
	 * @return void
	 */
	this.playNotifSound = function() {

		// TEMPORARY TESTING ADDITION REMOVE WHEN DONW
		if(this._ghostMode) {
			return void 0;
		}
		// END TEST ADDITION REMOVE WHEN DONE

		if(!this._playNotificationSound) { return void 0; }

		if(typeof this._audio == 'object' && this._audio instanceof Audio) {

			this._audio.play();

		} else {

			console.warn('Notification sound not instance of Audio Object.');
			console.log('Audio obj:',this._audio);

		}

	};





	/*
	 * manualTrigger
	 * decides if a manual trigger should be done
	 * based on cookie
	 *
	 * @param eid event id
	 * @return bool true if should manually trigger
	 */
	this.manualTrigger = function(eid) {
		var v = this.getCookie(this._eventCookieName);
		var o;

		if(!!v && v.length > 0) {

			o = JSON.parse(v);

		} else {
			return false;
		}

		var e = '_'+eid.toString()+'_';

		if(!o || !o[e] || typeof o[e] == 'undefined') {
			return false;
		}

		return true;
	};




	/*
	 * defaultAjaxSuccCb
	 * decides what to do by default with ajax data
	 * on success
	 *
	 * @param d data from ajax handler
	 * @return void
	 */
	this.defaultAjaxSuccCb = function(d) {
		console.log(d);
	};




	/*
	 * defaultAjaxErrCb
	 * default ajax error callback
	 *
	 * @return void
	 */
	this.defaultAjaxErrCb = function() {
		console.error('Ajax failure... contact support or whatever');
	};




	/*
	 * getParamNames
	 * gets parameters for a given function
	 *
	 * @param func function to get the param of
	 * @return array of functions
	 */
	this.getParamNames = function(func) {
		var regx = /([^\s,]+)/g;
		var funcStr = func.toString().replace(/((\/\/.*$)|(\/\*[\s\S]*?\*\/))/mg,'');
		var result = funcStr.slice(funcStr.indexOf('(')+1, funcStr.indexOf(')')).match(regx);

		return (result === null) ? [] : result;
	};




	/*
	 * validUrl
	 * checks if param is a valid url
	 *
	 * @param u url to check
	 * @return true if valid url
	 */
	this.validUrl = function(u) {
		u = u.replace(/https\:\/\/|http\:\/\//g,'');
		var regx = /[-a-zA-Z0-9\:\%\.\_\+\~\#\=]{2,256}\.[a-z]{2,10}\b([-a-zA-Z0-9\:\%\_\+\.\~\#\?\&\/\/\=]*)/;
		var result = u.match(regx);

		return (result != null && result.length > 0);
	};




	/*
	 * eventDispatched
	 * checks to see if event already dispatched
	 *
	 * @param dc event dispatch code
	 * @return true if already fired
	 */
	this.eventDispatched = function(dc) {

		if(!!dc && dc.length > 0) {
			// iterate through dispatched codes
			for(var s = 0; s < this._dispatchedCodes.length; s++) {

				// check for match
				if(dc == this._dispatchedCodes[s]) {
					return true;
				}

			} // end for

		} // end if

		return false;
	};



	/*
	 * eventTriggered
	 * records an event being triggered
	 *
	 * @param event id
	 * @return false if failure
	 */
	this.eventTriggered = function(e) {

		// TODO REMOVE WHEN TEST DONE
		if(this._ghostMode) { return true; }
		// END TMP TEST STUFF


		if(!e || 'object' != typeof e) {
			return false;
		}

		if(!e.DispatchCode || e.DispatchCode.length < 1) {
			return false;
		}
		if(this.getCookie(e.DispatchCode).length > 0) {
			return true;
		}

		if(this.eventDispatched(e.DispatchCode)) {
			return true;
		}


		var tmp = this._$('script[data-dispatch='+e.DispatchCode+']');
		if(tmp.length > 0) {
			return true;
		}


		var scriptParams = '?eid='+e.EID+'&dc='+e.DispatchCode;

		this._$('head').prepend(this._$('<script type="text/javascript"></script>').attr('src',this._defaultEventTriggeredUri+scriptParams).attr('data-dispatch',e.DispatchCode).prop('async',true));

		return true;

	};




	/*
	 * notificationSeen
	 * records when notification is seen
	 *
	 * @param e event object
	 * @param array of notification ids
	 * @return false if fails
	 */
	this.notificationSeen = function(e,nids) {

		// TODO REMOVE WHEN TEST DONE
		if(this._ghostMode) { return true; }
		// END TMP TEST STUFF


		// make sure event objec is valid
		if(!e || !e.EID || !e.DispatchCode || e.DispatchCode < 1) {
			return false;
		}

		// make sure nids are all valid
		// 	then check to see if there is a
		//	notification that has not been seen
		var unseen_cnt = 0;
		for(var n = 0; n < nids.length; n++) {

			if(nids[n] < 1) {
				return false;
			}

			if(!this.notifHasSeen(e.EID,nids[n])) {
				this.setEventNotifCookie(e.EID,nids[n]);
				unseen_cnt++;
			}
		}

		if(unseen_cnt == 0) {
			console.log('all notifications have been seen');
			return true;
		}

		// stirng of nids
		var nid_str = nids.join('-'), dc_nid = e.DispatchCode+'-'+nid_str;

		var tmp = this._$('script[data-n-dispatch='+dc_nid+']');
		if(tmp.length > 0) {
			return true;
		}

		var scriptParams = '?eid='+e.EID+'&dc='+e.DispatchCode+'&nids='+nid_str;

		this._$('head').prepend(this._$('<script type="text/javascript"></script>').attr('src',this._defaultNotifSeenUri+scriptParams).attr('data-n-dispatch',dc_nid).prop('async',true));

		return true;

	};



	/*
	 * setCookie
	 * sets a cookie
	 *
	 * @param n name
	 * @param v value
	 * @param ex expiration
	 * @return void
	 */
	this.setCookie = function(n,v,ex) {
		// TODO REMOVE WHEN TEST DONE
		if(this._ghostMode) { return void 0; }
		// END TMP TEST STUFF


		if((typeof ex).toLowerCase()=='undefined'&&ex!==0) {
			ex=2;
		}
		var d = new Date();
		d.setTime(d.getTime() + (ex*24*60*60*1000));
		var expires = 'expires='+d.toUTCString();
		document.cookie = n+'='+v+'; '+expires+'; domain='+this._siteDomain+'; path=/;';
	};



	/*
	 * getCookie
	 *
	 * @param cname cookie name
	 * @return cookie value
	 */
	this.getCookie = function(cname) {
		var name = cname + "=";
		var ca = document.cookie.split(';');
		for(var i=0; i<ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1);
			if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
		}
		return "";
	};




	/*
	 * getDispatchedCookies
	 * gets all cookies set after event triggered
	 *
	 * @return array of dispatch codes and eids
	 */
	this.getDisplatchedCookies = function() {
		var lst = [];
		var ca = document.cookie.split(';');
		for(var i=0; i<ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1);
			var c2 = c.split('=');
			if(!!c2[1] && c2[1].trim().toLowerCase() == 'trig-success') lst.push(c2[0]);
		}
		return lst;
	};




	/*
	 * setEventCookie
	 * sets cookie for when an event is triggered
	 *
	 * @param eid int event id
	 * @return void
	 */
	this.setEventCookie = function(eid) {
		var o = this.getEventCookieObject(eid);
		this.setCookie(this._eventCookieName,JSON.stringify(o),this._defaultCookieExpire);
	};



	/*
	 * setEventNotifCookie
	 * sets cookie for when an event is triggered
	 *
	 * @param eid int event id
	 * @param nid int notification id
	 * @return void
	 */
	this.setEventNotifCookie = function(eid,nid) {
		var o = this.getEventCookieObject(eid);
		var e = '_'+eid.toString()+'_';
		if(!this.notifHasSeen(eid,nid)) {
			o[e].push(nid);
		}
		this.setCookie(this._eventCookieName,JSON.stringify(o),this._defaultCookieExpire);
	};




	/*
	 * notifHasSeen
	 * determines whether or not a notif has been seen
	 *
	 * @param eid int event id
	 * @param nid int netif id
	 * @return true if seen
	 */
	this.notifHasSeen = function(eid,nid) {
		var o = this.getEventCookieObject(eid);
		var e = '_'+eid.toString()+'_';
		var has_seen = false;
		for(var x = 0; x < o[e].length; x++) {
			if(o[e][x] == nid) {
				has_seen  =true;
			}
		}

		return has_seen;
	};



	/*
	 * getEventCookieObject
	 * gets event object from cookie val
	 *
	 * @return object
	 */
	this.getEventCookieObject = function(eid) {
		if(eid < 1) return {};

		var e = '_'+eid.toString()+'_';
		var v = this.getCookie(this._eventCookieName);
		var o = (!!v && v.length > 0) ? JSON.parse(v) : {};
		if(!o || !o[e] || typeof o[e] == 'undefined') {
			o = (typeof o == 'object' || o instanceof Object) ? o : {};
			o[e] = [];
		}
		return o;
	};




	/*
	 * loadPluginsCallBack
	 * callback for load plugins
	 *
	 * @return void
	 */
	this.loadPluginsCallBack = function() {
		console.log('plugin loaded');
		window.SC.initPlugins();
	};




	/*
	 * loadPlugins
	 * loads plugins for the widget
	 *
	 * TODO REDESIGN HOW THIS ALL WORKS...
	 * THIS IS TESTING
	 *
	 * @return void
	 */
	this.loadPlugins = function() {
		for(var p = 0; p < this._loadPlugins.length; p++) {
			try {
				this.ajax(this._loadPlugins[p], 'GET', this.loadPluginsCallBack);
			} catch(e) {
				console.log(e);
			}
		}

	};





	/*
	 * setUpPluginLoad
	 * sets up event for things to do other things
	 *
	 * @return void
	 */
	this.setUpPluginLoad = function() {
		this._notificationDataCompleteEventListenerSet = true;
		var me = this;
		$(document).on(this._notificationDataCompleteEventName, function() {
			me.loadPlugins();
		});
	};




	/*
	 * pluginLoaded
	 * checks to see if plugin load function has already been
	 * called
	 *
	 * @param plugin id string
	 * @return true if already loaded
	 */
	this.pluginLoaded = function(plugin) {
		for(var i = 0; i < this._loadedPlugins.length; i++) {
			if(this._loadedPlugins[i] == plugin) {
				return true;
			}
		}
		return false;
	};




	/*
	 * initPlugins
	 * initializes all the loaded plugins, or initializes
	 * plugins by id
	 *
	 * @param ids array of plugin ids.. if empty will init all
	 * @return void 0
	 */
	this.initPlugins = function(ids) {

		if(!ids || 'undefined' == (typeof ids).toLowerCase() || ids == '*') {
			for(var p = 0; p < this._plugins.length; p++) {
				if(!this.pluginLoaded(this._plugins[p]._pluginId)) {
					try {
						if(!this.hasPluginLoadedEvent(this._plugins[p]._pluginName)) {
							this.addPluginLoadedEvent(this._plugins[p]._pluginName, this.generatePluginLoadedEventName(this._plugins[p]._pluginName));
						}
						this._plugins[p].load(this.getPluginLoadedEventName(this._plugins[p]._pluginName));
						this._loadedPlugins.push(this._plugins[p]._pluginId);
					} catch(e) {
						console.log(e);
					}
				} else {
					continue;
					console.info('Tried to reinitialize plugin "'+this._plugins[p]._pluginName+'" ['+this._plugins[p]._pluginId+']');
				}
			}
		} else {
			for(var id = 0; id < ids.length; id++) {
				for(var p = 0; p < this._plugins.length; p++) {
					if(this._plugin[p]._pluginId == ids[id]) {
						if(!this.pluginLoaded(ids[id])) {
							try {
								if(!this.hasPluginLoadedEvent(this._plugins[p]._pluginName)) {
									this.addPluginLoadedEvent(this._plugins[p]._pluginName, this.generatePluginLoadedEventName(this._plugins[p]._pluginName));
								}
								this._plugins[p].load(this.getPluginLoadedEventName(this._plugins[p]._pluginName));
								this._loadedPlugins.push(this._plugins[p]._pluginId);
							} catch(e) {
								console.log(e);
							}
							break;
						} else {
							continue;
							console.info('Tried to reinitialize plugin "'+this._plugins[p]._pluginName+'" ['+this._plugins[p]._pluginId+']');
						}
					}
				}
			}
		}

	};




	/*
	 * addPlugin
	 * adds a plugin to the app
	 *
	 * @param plugin object to add
	 * @return void
	 */
	this.addPlugin = function(plugin) {
		if(!this.validSCPlugin(plugin)) {
			console.warn("Failed to add invalid plugin:"); console.log(plugin);
			return void 0;
		}

		if(this.hasPlugin(plugin)) {
			console.info("Plugin '"+plugin._pluginName+"' ["+plugin._pluginId+"] attempted to load twice.");
			return void 0;
		}

		this._plugins.push(plugin);
	};




	/*
	 * validSCPlugin
	 * checks for basic requirements of a plugin
	 *
	 * @param plugin object to validate
	 * @return true if valid plugin
	 */
	this.validSCPlugin = function(plugin) {
		return (!!plugin && !("undefined" == (typeof plugin._pluginName).toLowerCase()) && plugin._pluginName.length > 0 &&
				!("undefined" == (typeof plugin._pluginId).toLowerCase()) && plugin._pluginId.length &&
				(typeof plugin.load).toLowerCase() == "function");
	};




	/*
	 * reportPluginLoadError
	 * reports when a plugin was already loaded
	 *
	 * @param name plugin name
	 * @param id plugin id
	 * @return void
	 */
	this.reportPluginLoadError = function(name, id) {
		var str = 'Plugin "%name%" [%id%] is already running. Check out the plugin data to debug why the plugin is loading multiple times';
		console.warn(str.replace(/\%name\%/g, name).replace(/\%id\%/g, id));
		console.info('loadPlugins: ', this._loadPlugins); console.log('plugins: ', this._plugins); console.log('loadedPlugins: ', this._loadedPlugins);
	};




	/*
	 * hasPlugin
	 * checks to see if plugin is already loaded
	 *
	 * @param plugin object to check for
	 * @return true if already has plugin
	 */
	this.hasPlugin = function(plugin) {
		if(!this.validSCPlugin(plugin)) {
			return false;
		}
		for(var p = 0; p < this._plugins.length; p++) {
			if(this._plugins[p]._pluginId == plugin._pluginId) {
				return true;
			}
		}
		return false;
	};




	/*
	 * pluginApply
	 * calls a function found in a plugin...
	 * *BETA* prob gonna remove
	 *
	 * @param pluginFunc string plugin_name.function
	 * @param data (object) data to pass to function
	 * @return void
	 */
	this.pluginApply = function(pluginFunc, data) {
		var targetInfo = pluginFunc.split('.');
		if(targetInfo.length != 2) {
			console.log('SC: cannot apply function with params:', pluginFunc, data);
			return void 0;
		}

		var pluginName = targetInfo[0];
		var func = targetInfo[1];

		var has_plugin = false;

		for(var i = 0; i < this._plugins.length; i++) {
			if(this._plugins[i]._pluginName.toLowerCase() == pluginName.toLowerCase() && typeof this._plugins[i][func] == 'function') {
				has_plugin = true;
				this._plugins[i][func].apply(this._plugins[i], data);
				break;
			}
		}

		if(!has_plugin) {
			console.log('SC: could not find plugin/function', pluginFunc, data);
		}

	};




	/*
	 * pluginBind
	 * binds event/function call to plugin
	 *
	 * @param pluginFunc string plugin_name.function
	 * @param data (object) data to pass to function
	 * @return void
	 */
	this.pluginBind = function(pluginFunc, data) {
		var targetInfo = pluginFunc.split('.');
		var pluginName = targetInfo[0];
		var xist = false;
		for(var i = 0; i < this._plugins.length; i++) {
			if(this._plugins[i]._pluginName.toLowerCase() == pluginName.toLowerCase()) {
				xist = true;
				break;
			}
		}

		if(xist) {
			this.pluginApply(pluginFunc, data);
		} else {

			if(!this.hasPluginLoadedEvent(pluginName)) {
				this.addPluginLoadedEvent(pluginName, this.generatePluginLoadedEventName(pluginName));
			}
			var me = this;
			$(document).on(this.getPluginLoadedEventName(pluginName), function() {
				me.pluginApply(pluginFunc, data);
			});

		}

	};




	/*
	 * hasPluginLoadedEvent
	 * checks to see if plugin has event
	 *
	 * @param name string plugin name
	 * @return true if event name exists
	 */
	this.hasPluginLoadedEvent = function(name) {
		if(!!name) {

			return (this._pluginLoadedEvents && typeof this._pluginLoadedEvents[name] != 'undefined' && !!this._pluginLoadedEvents);

		} else return false;
	};




	/*
	 * addPluginLoadedEvent
	 * adds an event listener name to cache
	 *
	 * @param name string name
	 * @param val string val
	 * @return void
	 */
	this.addPluginLoadedEvent = function(name, val) {
		if(!!name && !!val) {
			val = val.toLowerCase();
			this._pluginLoadedEvents[name] = val;
		}
	};




	/*
	 * removePluginLoadedEvent
	 * removes one
	 *
	 * @param name string name
	 * @return void
	 */
	this.removePluginLoadedEvent = function(name) {
		$(document).off(this._pluginLoadedEvents[name]);
		delete this._pluginLoadedEvents[name];
	};




	/*
	 * getPluginLoadedEventName
	 * get the event name
	 *
	 * @param name string name
	 * @return string value
	 */
	this.getPluginLoadedEventName = function(name) {
		return this._pluginLoadedEvents[name];
	};




	/*
	 * generatePluginLoadedEventName
	 * generates an event listener name
	 *
	 * @param name pluginname
	 * @return string name
	 */
	this.generatePluginLoadedEventName = function(name) {
		if(!!name) {
			return this._pluginLoadedEventName+name.toLowerCase()+(Math.round(Math.random()*1000)).toString();
		} else {
			console.warn('bad name, foo');
			return '';
		}
	};




	/*
	 * reportEventFailure
	 * reports that there was an error when trying to store
	 * triggered event data
	 *
	 * @param m string message
	 * @return void
	 */
	this.reportEventFailure = function(m) {
		console.warn(m);
	};



	/*
	 * reportNotifSuccess
	 * reports successful notif views
	 *
	 * @param array of ids
	 * @return void
	 */
	this.reportNotifSuccess = function(ids) {
		console.info('following notification ids viewed and tracked: '+ids.toString());
	};



	/*
	 * reportInvalidLicense
	 * reports the license is invalid
	 *
	 * @param m message
	 * @return void
	 */
	this.reportInvalidLicense = function(m) {
		console.warn('Invalid License Detected. Request Response:');
		console.info(m);
	};



	/*
	 * reportRequestFaulure
	 * reports request failure
	 *
	 * @param m message
	 * @return void
	 */
	this.reportRequestFaulure = function(m) {
		console.warn('Request to SC server failed. Message:');
		console.info(m);
	};



	/*
	 * reportDependencies
	 * reports the fact SC needs shit to work
	 *
	 * @return void
	 */
	this.reportDependencies = function() {
		console.error('Snake Charmer Relies on jQuery. Please include jQuery library on your web page');
	};



};
// END SC

if('undefined' == typeof jQuery || !jQuery) {
	(new SnakeCharmer).reportDependencies();
} else {

	// auto initialize
	jQuery(document).ready(function() {
		if(typeof window.SC_AUTO_INIT == 'undefined' || window.SC_AUTO_INIT !== false) {
			window.SC = new SnakeCharmer();
			window.SC.ini();
		}
	});
	// end auto initialize

}
