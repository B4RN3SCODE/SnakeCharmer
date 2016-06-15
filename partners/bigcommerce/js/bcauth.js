/*
 * BCAuth
 * Submits auth form via JS
 *
 * @author			Tyler J Barnes
 * @contact			b4rn3scode@gmail.com
 * @version			1.0
 * @dependencies	jquery
 */

// strict mode
'use strict';

/*
 * main object
 *
 * @param frmId string form <div> id
 * @return void
 */
var BCAuth = function(frmId) {
	/*	PROPS	*/

	// form
	this._formId = (!!frmId && frmId.length > 0) ? frmId : 'frmAuth';
	this._form = null;
	this._btnSubmitId = null;

	// data attributes
	this._dataAttribs = {
		serverStamp: 'ts',
		authUid: 'uid',
		authEmail: 'em',
		authToken: 'tkn',
		authStore: 'shash',
	};

	// server data
	this._serverStamp = null;
	this._serverAuthUid = null;
	this._serverAuthEmail = null;
	this._serverAuthToken = null;
	this._serverAuthStore = null;

	// input elms
	this._inputs = {
		domain: 'domain',
		email: 'email',
		coName: 'companyName',
	};

	// input vals
	this._domain = null;
	this._email = null;
	this._coName = null;

	// invalidate server data
	this._serverDataInvalidated = false;

	// auth from submit endpoint
	this._authEndpoint = '//sc.conversionvoodoo.com/partners/bigcommerce/js/authSubmit.php';

	// loading image src
	this._loadingImg = '//d61fqxuabx4t4.cloudfront.net/snakecharmer/partners/bigcommerce/auth/images/load.gif';

	// form invalidated
	this._formInvalidated = false;

	// ready status
	this._formReady = false;

	// process started
	this._procStarted = false;
	// process ended (at least been submitted)
	this._procEnded = false;


	/*	END PROPS	*/


	/*
	 * init
	 * Initializes the auth form functionality
	 *
	 * @return void
	 */
	this.init = function() {
		this._form = $('#'+this._formId);
		if(this._form.length < 1 || $('body').find(this._form).length < 1) {
			console.error('BCAuth : Could not find form');
			// TODO REMOVE AFTER TEST
			alert('Cant find form');
			return void 0;
		}

		this._btnSubmitId = this._form.find('button[type=submit]')[0].id;
		this._serverStamp = $('body').data(this._dataAttribs.serverStamp);
		this._serverAuthUid = $('body').data(this._dataAttribs.authUid);
		this._serverAuthEmail = $('body').data(this._dataAttribs.authEmail);
		this._serverAuthToken = $('body').data(this._dataAttribs.authToken);
		this._serverAuthStore = $('body').data(this._dataAttribs.authStore);

		this.setupSubmitEvent();
	};



	/*
	 * setupSubmitEvent
	 * Sets up event listener on the submit button\
	 *
	 * @reutrn void
	 */
	this.setupSubmitEvent = function() {
		if(!this._btnSubmitId || (typeof this._btnSubmitId).toLowerCase() == 'undefined') {
			console.error('BCAuth : Button ID undefined');
			// TODO REMOVE
			alert('btn id undefined');
			return void 0;
		}

		var me = this;
		$('#'+this._btnSubmitId).on('click', function() {
			if(me.runProcess()) {
				me.submitForm();
			} else {
				console.log('BCAuth : do not run');
			}
		});

	};



	/*
	 * runProcess
	 * Checks to see if process should run
	 *
	 * @return true if process should run
	 */
	this.runProcess = function() {
		return (this._procStarted === false && this._procEnded === false);
	};




	/*
	 * addScript
	 * Adds a js script to document
	 *
	 * @param s string full source url
	 * @param asnc bool asyncronous
	 * @return void
	 */
	this.addScript = function(s, id, asnc) {

		if(!!s) {
			asnc = (asnc === true);

			var ns2 = document.createElement('script');
			ns2.type = 'text/javascript';
			ns2.async = asnc;
			ns2.src = s; ns2.id = id;
			var es2 = document.getElementsByTagName('script')[0];
			es2.parentNode.insertBefore(ns2, es2);
		}

	};



	/*
	 * showLoading
	 * Shows loading gif & replaces form
	 *
	 * @return void
	 */
	this.showLoading = function() {
		$('#'+this._formId).html('<center><img alt="Loading..." src="'+this._loadingImg+'" width="100px" height="100px"></center>');
	};



	/*
	 * showFormInvalid
	 * Shows that the form is invalid or removes the
	 * notice
	 *
	 * @param elm string input to hint
	 * @param remove bool true to remove (default false)
	 * @return void
	 */
	this.showFormInvalid = function(elm, remove) {
		elm = (!!elm) ? elm : 'everything';
		remove = (remove === true);
		if(remove) {
			$('#frmErrorMsg').remove();
		} else {
			this._formInvalidated = true;
			$('#'+this._formId).prepend('<div id="frmErrorMsg" style="font-size:.8em;"><center><span style="color:red">Please complete all form data (check '+elm+')</span></center></div>');
		}
	};




	/*
	 * showUnknownFormStatusError
	 * Shows that something went wrong but we dont know
	 *
	 * @return void
	 */
	this.showUnknownFormStatusError = function() {
		var div = '<div class="bc_form_box"><div id="frmRefresh"><center><span style="color:red;">Something went wrong.</span><br><br><span><b>Try refreshing the form</b><br>or contact tylerb@conversionvoodoo.com with this information<br>';
		div += 'Email: '+this._email;
		div += '<br>Domain: '+this._domain;
		div += '<br>Store User: '+this._serverAuthStore+'-'+this._serverAuthUid;
		div += '</span></center></div><form id="tryForm" action="" method="POST"><input type="hidden" name="action" value="refresh"><center><button type="submit" class="btn try">Refresh Form</button></center></form></div>';
		$('#'+this._formId).html(div);
	};



	/*
	 * disableSubmit
	 * Disables submit
	 *
	 * @return void
	 */
	this.disableSubmit = function() {
		$('#'+this._btnSubmitId).prop('disabled', true);
	};



	/*
	 * enableSubmit
	 * Enables submit
	 *
	 * @return void
	 */
	this.enableSubmit = function() {
		$('#'+this._btnSubmitId).prop('disabled', false);
	};




	/*
	 * setupSubmit
	 * Sets up everything to be submitted
	 *
	 * @return void
	 */
	this.setupSubmit = function() {
		this._formInvalidated = false;
		if(!!this._serverStamp && !isNaN(parseInt(this._serverStamp)) && parseInt(this._serverStamp) > 0 &&
			!!this._serverAuthToken && !!this._serverAuthStore) {

			// get input vals
			this._domain = $('#'+this._inputs.domain).val();
			this._email = $('#'+this._inputs.email).val();
			this._coName = $('#'+this._inputs.coName).val();

			// validate
			var err = false;
			var errStr = '';
			if(!this._domain || this._domain.length < 1 || this._domain.split('.').length < 1) {
				err = true;
				errStr += 'domain ';
			}
			if(!this._email || this._email.length < 1 || this._email.split('@').length < 1) {
				err = true;
				errStr += 'email ';
			}
			if(!this._coName || this._coName.length < 1) {
				err = true;
				errStr += 'company';
			}
			if(err) {
				this.showFormInvalid(errStr);
				this._formReady = false;
				return void 0;
			}


			// check to see if email changed and server token invalid
			if(this._serverAuthEmail.toLowerCase() != this._email.toLowerCase()) {
				this._serverDataInvalidated = true;
			}

			this._formReady = true;

		} else {
			this._formReady = false;
			console.error('BCAuth : Form not ready'); console.log(this._serverStamp, this._serverAuthToken, this._serverAuthStore);
			// TODO REMOVE
			alert('store not ready');
		}
	};



	/*
	 * submitForm
	 * Submits the form data
	 *
	 * @return void
	 */
	this.submitForm = function() {
		this._procStarted = true;
		this.setupSubmit();
		this.disableSubmit();
		this.showFormInvalid('',true);

		if(!this._formReady) {
			if(!this._formInvalidated) {
				this.showUnknownFormStatusError();
			}
			return void 0;
		}
		var isInvalid = (this._serverDataInvalidated) ? '&invalidated=true' : '';
		var scriptParams = '?domain='+this._domain+'&email='+this._email+'&company='+this._coName+'&ts='+this._serverStamp+'&accemail='+this._serverAuthEmail+'&uid='+this._serverAuthUid+'&tkn='+this._serverAuthToken+'&store='+this._serverAuthStore+''+isInvalid;
		this.showLoading();
		this.addScript(document.location.protocol+this._authEndpoint+scriptParams, 'scrSubFrm', true);
	};



	/*
	 * submitError
	 * Submit error handling
	 *
	 * @param msg string message to display
	 * @return void
	 */
	this.submitError = function(msg) {
		var div = '<h3>There was an error activating your account</h3><h5>Try launching the app for more info, or contact<br>tylerb@conversionvoodoo.com with this information:</h5><br><span>'+msg+'</span>';
		$('#'+this._formId).css('width','600px').css('font-size','.8em').html(div);
	};



	/*
	 * submitSuccess
	 * Submit success stuff
	 *
	 * @param obj object with account summary
	 * @return void
	 */
	this.submitSuccess = function(obj) {
		var div = '<h3>Your account has been created successfully!</h3><h5>To install SnakeCharmer on your site, start by Launching the app!</h5><br><span><b>Please note the following information:</b><br>';
		div += 'SnakeCharmer License: '+obj.License+'<br>Domain: '+obj.Domain+'<br>Company: '+obj.Name+'<br>Time Added: '+obj.TimeAdded+'<br>Auth. Complete: '+obj.AuthComplete+'<br>';
		div += 'Account Active: '+obj.BCActive+'<br># of Users: '+obj.TotUsers.toString()+'<br>Support Rep.: '+obj.SupportRep+'<br>Support Email: '+obj.SupportEmail+'<br>Support Phone: '+obj.SupportPhone+'<br></span>';

		$('#'+this._formId).css('width','600px').css('font-size','.8em').html(div);
	};




}
