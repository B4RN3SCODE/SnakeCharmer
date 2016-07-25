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

	/*	PLUGIN INFO	*/

	this._version = '1.1.1';
	this._pluginName = "SCBCMain";
	this._pluginId = "SC315501";



	/*	PROPS	*/

	// big commerce store hash
	this._storeHash = "";

	// cart
	this._cart = void 0;
	this._cartId = 'SCBCCart';
	this._cartItemName = 'SCBCCartItem';
	this._cartTotal = 0.0;

	// products ( array of objects {product ID : id, cart count : product count, viewed: true|false} )
	this._products = [];

	// event criteria ( array of objects {eventId: id, criteria: {}} )
	this._eventCriteria = [];

	// track event criteria met
	this._eventCriteriaMet = [];



	// data attributes
	this._dataAttributes = {
		productId: 'prodid',
		cartCount: 'prodqty',
		productPrice: 'prodprice'
	};

	// track type of criteria events
	this._hasCartProducts = false;
	this._hasViewedProducts = false;

	// cookie name
	this._cookieName = 'scbcmainet';
	this._cookieValue = [];




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

		this.getCookieVal();


		// get event criteria from SC
		this.getCriteria();
		// get the cart
		this.getCart();

		if(this._cart && typeof this._cart != 'undefined' && this._cart.length > 0) {
			// get cart products if the cart is found
			this.getCartProducts();
		}

		if(!!event) {
			$(document).trigger(event);
			window.SC.removePluginLoadedEvent(this._pluginName);
		}


		if(this._products.length > 0) {
			this.checkCriteria();
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
		this._cookieValue = (!!tmpCookie && tmpCookie.length > 0) ? JSON.parse(tmpCookie) : [];
	};





	/*
	 * getCart
	 * Gets the cart HTML
	 *
	 * @return void
	 */
	this.getCart = function() {
		var tmpCart = $('#'+this._cartId);
		if(tmpCart.length < 1) {
			console.error('SCBCMain : cannot find cart');
			return void 0;
		}
		this._cart = tmpCart;
	};




	/*
	 * calculateCartTotal
	 * Calculates the total cart price
	 *
	 * @return void
	 */
	this.calculateCartTotal = function() {
		var tot = 0.0;
		for(var i = 0; i < this._products.length; i++) {
			if(this._products[i].productPrice && typeof this._products[i].productPrice != 'undefined' && this._products[i].productPrice > 0) {
				tot += this._products[i].productPrice;
			}
		}
		this._cartTotal = tot;
	};




	/*
	 * addProduct
	 * Adds a product to the array of products
	 *
	 * @param productid product id
	 * @param cartcount cart count (default 0)
	 * @param price item price
	 * @param viewed boolean true for product view (default false)
	 * @return void
	 */
	this.addProduct = function(productid, cartcount, price, viewed) {
		if(!productid || typeof productid == 'undefined' || productid < 1) {
			return void 0;
		}
		cartcount = (!!cartcount && cartcount > 0) ? cartcount : 0;
		price = (!!price && price > 0) ? price : 0;
		viewed = (viewed === true);
		if(!this.hasProduct(productid)) {
			this._products.push({
				id: productid,
				cartCount: cartcount,
				productPrice: price,
				viewed: viewed
			});
		} else {
			var obj = {};
			if(cartcount > 0) { obj.cartCount = cartcount; }
			if(price > 0) { obj.productPrice = price; }
			obj.viewed = viewed;
			this.updateProduct(productid, obj);
		}
	};



	/*
	 * hasProduct
	 * Checks to see if a product already exists
	 *
	 * @param productid product id
	 * @return true if product exists
	 */
	this.hasProduct = function(productid) {
		if(!productid || typeof productid == 'undefined' || productid < 1) {
			return false;
		}
		for(var i = 0; i < this._products.length; i++) {
			if(this._products[i].id == productid) {
				return true;
			}
		}
		return false;
	};



	/*
	 * updateProduct
	 * Updates product values
	 *
	 * @param productid product id
	 * @param data object {prop : val}
	 * @return void
	 */
	this.updateProduct = function(productid, data) {
		if(!productid || typeof productid == 'undefined' || productid < 1) {
			return void 0;
		}
		var has_data = false;
		for(var p in data) {
			has_data = true;
			break
		}
		if(!has_data) { return void 0; }

		// if doesnt exist create it
		if(!this.hasProduct(productid)) {
			this.addProduct(productid, 0, 0, false);
		}


		for(var i = 0; i < this._products.length; i++) {
			if(this._products[i].id == productid) {
				for(var prop in data) {
					if(prop in this._products[i]) {
						this._products[i][prop] = data[prop];
					}
				}
				break;
			}
		}

	};



	/*
	 * getCriteria
	 * Gets criter from SC system
	 *
	 * @return void
	 */
	this.getCriteria = function() {
		if(typeof window.SC.getEventCriteria == 'undefined') {
			console.error('SCBCMain : Cannot get criteria cuz SC function doesnt exist.... this msg if for testing but im leaving it in anyway');
			return void 0;
		}

		this._eventCriteria = window.SC.getEventCriteria();
	};




	/*
	 * getCartProducts
	 * Gets the products and quantity from cart
	 *
	 * @return void
	 */
	this.getCartProducts = function() {

		var productList = this._cart.find('*[data-name='+this._cartItemName+']');
		if(productList.length > 0) {
			this._hasCartProducts = true;
			for(var i = 0; i < productList.length; i++) {
				this.addProduct(parseInt($(productList[i]).data(this._dataAttributes.productId)), parseInt($(productList[i]).data(this._dataAttributes.cartCount)), parseFloat($(productList[i]).data(this._dataAttributes.productPrice).replace(/[^0-9|\.]/g, '')));
			}
		}

		if(this._hasCartProducts) {
			this.calculateCartTotal();
		}

	};



	/*
	 * productViewed
	 * Adds or updates product viewed attribute
	 *
	 * @param productid product ID
	 * @return void
	 */
	this.productViewed = function(productId) {
		if(!productId || typeof productId == 'undefined' || isNaN(parseInt(productId))) {
			console.log('SCBCMain : cannot mark product as viewed ['+productId+']');
			return void 0;
		}
		this.addProduct(productId, 0, 0, true);
		this._hasViewedProducts = true;
	};



	/*
	 * checkCriteria
	 * Checks product information against event criteria to see
	 * if we should trigger the event
	 *
	 * @return void
	 */
	this.checkCriteria = function() {
		this._eventCriteriaMet = [];

		// iterate through the event criteria to get criteria
		for(var i = 0; i < this._eventCriteria.length; i++) {

			var tmpC = this._eventCriteria[i]; // temp criteria
			var cMet = 0; // criteria met

			// iterate throught the criteria set to make sure all
			// criter is met based on products
			for(var j = 0; j < tmpC.criteria.length; j++) {


				// check cart total criteria
				if(tmpC.criteria[j].cartTotal && typeof tmpC.criteria[j].cartTotal != 'undefined' && tmpC.criteria[j].cartTotal > 0) {

					if(this._cartTotal >= tmpC.criteria[j].cartTotal) {
						this._eventCriteriaMet.push(tmpC.eventId);
					}

				}


				// iterate through all products and check against current criteria
				for(var k = 0; k < this._products.length; k++) {

					var tmpP = this._products[k]; // temp product


					// if products match, check rest of criteria
					if(tmpP.id == tmpC.criteria[j].productId) {


						// check viewed products
						if(tmpC.criteria[j].viewed && typeof tmpC.criteria[j].viewed != 'undefined' && tmpC.criteria[j].viewed == true) {

							if(tmpP.viewed == true) {
								// TODO see if should uncomment the following or change it to
								// use products id or something
								//this._viewedProductsMet.push(tmpC.eventId);

								this._eventCriteriaMet.push(tmpC.eventId);
							}
						}


						// check for cart criteria
						if(tmpC.criteria[j].cartCount && typeof tmpC.criteria[j].cartCount != 'undefined' && tmpC.criteria[j].cartCount > 0) {
							if(tmpP.cartCount >= tmpC.criteria[j].cartCount) {


								cMet++;
								// make sure all criteria was met (not just one condition)
								if(cMet >= tmpC.criteria.length) {
									// TODO see if should uncomment the following or change it to
									// use products id or something
									//this._viewedProductsMet.push(tmpC.eventId);

									this._eventCriteriaMet.push(tmpC.eventId);


								}
							}
						}

					}

				}

			}

		}

		if(this._eventCriteriaMet.length > 0) {
			this.triggerEvents();
		}

	};




	/*
	 * triggerEvents
	 * Triggers the SC events after criteria is met
	 * using SC code
	 *
	 * @return void
	 */
	this.triggerEvents = function() {
		window.SC._playNotificationSound = false;
		var triggerClick = false;
		for(var i = 0; i < this._eventCriteriaMet.length; i++) {
			if(!this.inCookieArray(this._eventCriteriaMet[i])) {
				window.SC._playNotificationSound = true;
				triggerClick = true;
			}
			window.SC.handleTimeOutOrCriteria(this._eventCriteriaMet[i]);
		}
		if(triggerClick) {
			$('.chatbox').click();
		}

		this.updateCookie();
	};



	/*
	 * inCookieArray
	 * Checks to see if an event is already been cookied
	 *
	 * @param eid event ID
	 * @return true if exists in cookie
	 */
	this.inCookieArray = function(eid) {
		for(var i = 0; i < this._cookieValue.length; i++) {
			if(this._cookieValue[i] == eid) {
				return true;
			}
		}
		return false;
	};



	/*
	 * updateCookie
	 * Updates the cookie to track seen notifications
	 * using SC.setCookie function
	 *
	 * @return void
	 */
	this.updateCookie = function() {
		var cookieChanged = false;
		for(var i = 0; i < this._eventCriteriaMet.length; i++) {
			if(!this.inCookieArray(this._eventCriteriaMet[i])) {
				this._cookieValue.push(this._eventCriteriaMet[i]);
				cookieChanged = true;
			}
		}
		if(cookieChanged) {
			window.SC.setCookie(this._cookieName, JSON.stringify(this._cookieValue), 30);
		}
	};





	/*
	 * promoConvert
	 * Tracks a conversion for promotion
	 *
	 * @param eid event ID
	 * @return void
	 */
	this.promoConvert = function(eid) {
		var othVar = [];
		var pt = (!eid || typeof eid == 'undefined' || eid < 1) ? 'other' : this.getPromoType(eid);
		if(!!pt && pt.length > 0) {
			othVar.push({promoType: pt});
		}
		window.SC.pluginBind('SCTracker.updateCookie', [eid, {converted: true, promoType: pt}]);
		window.SC.pluginBind('SCTracker.setUpConvert', [false, 0, othVar]);
	};




	/*
	 * getPromoType
	 * Gets the promo type
	 *
	 * @param eid event ID
	 * @return string promoType
	 */
	this.getPromoType = function(eid) {
		for(var i = 0; i < this._eventCriteria.length; i++) {
			if(this._eventCriteria[i].eventId == eid) {
				return this._eventCriteria[i].promoType;
			}
		}
		return '';
	};




	/*
	 * promoClick
	 * Action to take when promo clicked
	 *
	 * @param elms elements
	 * @return void
	 */
	this.promoClick = function(elms) {
		if(elms.length < 1) { this.promoConvert(-1); }
		if(elms.length > 1) {
			var me = this;
			$.each(elms, function(i,e) {
				var v = $(e).data('evid');
				me.promoConvert(v);
				return void 0;
			});
		} else {
			var v = $(elms[0]).data('evid');
			this.promoConvert(v);
		}
	};




}

window.SC.addPlugin(new SCBCMain());
