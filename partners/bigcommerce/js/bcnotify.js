/*
 * BCNotify
 * Notify admin client ready to set stuff up
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
var BCNotify = function(frmId) {
	/*	PROPS	*/

	// form
	this._formId = (!!frmId && frmId.length > 0) ? frmId : 'frmNotify';
	this._form = null;
	this._btnSubmitId = null;

	// input elms
	this._inputs = {
		name: 'name',
		email: 'email',
		message: 'message',
		license: 'license'
	};

	// input vals
	this._name = null;
	this._email = null;
	this._message = null;
	this._license = null;

	// auth from submit endpoint
	this._authEndpoint = '//sc.conversionvoodoo.com/partners/bigcommerce/js/adminNotify.php';

	// loading image src
	this._loadingImg = '//d61fqxuabx4t4.cloudfront.net/snakecharmer/partners/bigcommerce/auth/images/load.gif';

	// ready status
	this._formReady = false;

	// process started
	this._procStarted = false;
	// process ended (at least been submitted)
	this._procEnded = false;


	/*	END PROPS	*/


	/*
	 * init
	 * Initializes
	 *
	 * @return void
	 */
	this.init = function() {
		this._form = $('#'+this._formId);
		if(this._form.length < 1 || $('body').find(this._form).length < 1) {
			console.error('BCNotify : Could not find form');
			// TODO REMOVE AFTER TEST
			alert('Cant find form');
			return void 0;
		}

		this._btnSubmitId = this._form.find('button[type=submit]')[0].id;

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
			console.error('BCNotify : Button ID undefined');
			// TODO REMOVE
			alert('btn id undefined');
			return void 0;
		}

		var me = this;
		$('#'+this._btnSubmitId).on('click', function() {
			if(me.runProcess()) {
				me.submitForm();
			} else {
				console.log('BCNotify : do not run');
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
		div += 'Name: '+this._name;
		div += '<br>Email: '+this._email;
		div += '<br>Message: '+this._message;
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
		// get input vals
		this._name = $('#'+this._inputs.name).val();
		this._email = $('#'+this._inputs.email).val();
		this._message = $('#'+this._inputs.message).val();
		this._license = $('#'+this._inputs.license).val();


		// validate
		var err = false;
		var errStr = '';
		if(!this._name || this._name.length < 1) {
			err = true;
			errStr += 'name ';
		}
		if(!this._email || this._email.length < 1 || this._email.split('@').length < 1) {
			err = true;
			errStr += 'email ';
		}
		if(!this._message || this._message.length < 1) {
			err = true;
			errStr += 'message';
		}
		if(err) {
			this.showFormInvalid(errStr);
			this._formReady = false;
			return void 0;
		}

		this._formReady = true;
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
			//if(!this._formInvalidated) {
				//this.showUnknownFormStatusError();
			//}
			console.log('BCNotify : form not ready');
			return void 0;
		}
		var scriptParams = '?name='+this._name+'&email='+this._email+'&message='+this._message+'&license='+this._license;
		this.showLoading();
		this.addScript(document.location.protocol+this._authEndpoint+scriptParams, 'scrSubFrm', true);
	};



	/*
	 * submitError
	 * Submit error handling
	 *
	 * @return void
	 */
	this.submitError = function() {
		var div = '<h3>There was an error sending your message</h3><h5>Try again later, or contact<br>tylerb@conversionvoodoo.com</h5>';
		$('#'+this._formId).html(div);
	};



	/*
	 * submitSuccess
	 * Submit success stuff
	 *
	 * @return void
	 */
	this.submitSuccess = function() {
		var div = '<h3>Your message was sent successfully!</h3><br /><h5>A representative will contact you soon</h5>';

		$('#'+this._formId).html(div);
	};




}
