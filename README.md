**Snake Charmer Widget Application Documentation**


Overview:

SC is an installable application that will
provide a convenient way to interact with users
with the prupose of gathering, displaying, or
soliciting information


	Author:		Tyler J Barnes
	Contact:	b4rn3scode@gmail.com
	Version:	1.1.2
	License:	TDA

**Dependencies**
 - jQuery


Example Installation:

(note: the UI is being built to generate the installation code for users -
for now, contact Tyler Barnes, *see above*, for license keys, theme ids,
and other information)


```html
<script type="text/javascript" id="SCJS" src="//www.conversionvoodoo.com/sc/js/sc.js?license={license key}&themeId={theme id}"></script>
```




**Framework**:

 - Initialization
	- SnakeCharmer will auto initialize once the page is loaded.  To avoid the application from auto init'ing, before the script is loaded, set `window.SC_AUTO_INIT = false;` *(either in a script tag or in a JS file loaded before SnakeCharmer)*.
	- Auto-Initialization: quick, simple. This will use default config and property values to construct the application. Querying for the theme and notification data, setting the theme and events up, and triggering listeners will be performed automatically.  **Once the app is loaded and initialized you cannot modify aspects of the app**.
	- Manual-Initialization: flexible, added capabilities. If a user decides to manually initialize SnakeCharmer, they are able to change the way the application performs. User can create custom events, custom notifications, and tigger those events to display the notifications.  The user can do more simple things like change the notification sound, also.

 - Theme
	- Getting Theme Data: Getting theme data will generate a script tag with the theme data.  This script tag will call a function to set up the theme.
	- Setting Up The Theme: This sets up the widget. Each element, color scheme, and customized styling will be applied to both the notification widget and the sidebar.

 - Events
	- Getting Event Data: Getting event data will generate a script tag with all the event and notification data for that page. This script will call a function to set up the events.
	- Setting Up Events: This adds event listeners to the specified elements, or however the event is set up, to trigger and view notifications.

 - Cookies
	- Application: The application stores cookies to track triggered events and seen notifications.
	- Events: Events store "success" cookies when they have been successfully recorded as triggered via snake charmer's server.

 - Custom Events/Notifications
	- Events: ** TODO - add documentation when feature is complete **
	- Notifications: ** TODO - add documentation when feature is complete **

 - Customizable Properties
	- List: *config, defaultGetThemeUri, defaultGetNotifDataUri, defaultEventTriggeredUri, defaultNotifSeenUri, notificationSoundFile, eventCookieName, defaultCookieExpire*


**API**:


**Example Usage**:

 - Initializing Widget:
	- manual init (if `window.SC_AUTO_INIT` is set to `false`)

	```javascript
	window.SC = new SnakeCharmer(); // create object
	window.SC.ini(); // init
	```

	- init with config
	```javascript
	var conf = {
		license: "<lic string>",
		themeId: "<id>",
	};
	window.SC = new SnakeCharmer(conf); // create object
	window.SC.ini(); // init
	```


 - Getting Events & Notifications

 ** TODO - add documentation when example is complete **

 - Loading Themes

 ** TODO - add documentation when example is complete **

 - Adding Custom Events

 ** TODO - add documentation when example is complete **

 - Adding Custom Notifications

 ** TODO - add documentation when example is complete **

 - Managing Events, Notification, Pages

 ** TODO - add documentation when example is complete **

 - MISC

	- Changing Notification Sound

		- *before SC JS file is loaded*

		`window.SC_AUTO_INIT=false;`

		- *after SC JS file is loaded*

		```javascript
		window.SC = new SnakeCharmer(); // create object
		window.SC._notificationSoundFile = '//path.to/file.mp3'; // changes the file
		window.SC.ini(); // init the app
		```

	- Changing Cookie Name/Expiration

		- *before SC JS file is loaded*

		`window.SC_AUTO_INIT=false;`

		- *after SC JS file is loaded*

		```javascript
		window.SC = new SnakeCharmer(); // create object
		window.SC._eventCookieName = 'your_cookie_name'; // change name
		window.SC._defaultCookieExpire = 30; // change number of DAYS
		window.SC.ini(); // init the app
		```

