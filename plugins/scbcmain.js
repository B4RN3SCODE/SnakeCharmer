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

	// products ( array of objects {product ID : id, cart count : product count, viewed: true|false} )
	this._products = [];

	// event criteria ( array of objects {eventId: id, criteria: {}} )
	this._eventCriteria = [];

	// track event criteria met
	this._eventCriteriaMet = [];



	/////////////////////////////////////////////
	// TODO: figure out if these will be needed
	// to track which types of criteria was met
	/////////////////////////////////////////////
	//		this._viewedProductsMet = [];
	//		this._cartProductsMet = [];
	/////////////////////////////////////////////
	/////////////////////////////////////////////



	// data attributes
	this._dataAttributes = {
		productId: 'prodid',
		cartCount: 'prodqty'
	};

	// track type of criteria events
	this._hasCartProducts = false;
	this._hasViewedProducts = false;




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
		}


		if(this._products.length > 0) {
			this.checkCriteria();
		}

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
	 * addProduct
	 * Adds a product to the array of products
	 *
	 * @param productid product id
	 * @param cartcount cart count (default 0)
	 * @param viewed boolean true for product view (default false)
	 * @return void
	 */
	this.addProduct = function(productid, cartcount, viewed) {
		if(!productid || typeof productid == 'undefined' || productid < 1) {
			return void 0;
		}
		cartcount = (!!cartcount && cartcount > 0) ? cartcount : 0;
		viewed = (viewed === true);
		if(!this.hasProduct(productid)) {
			this._products.push({
				id: productid,
				cartCount: cartcount,
				viewed: viewed
			});
		} else {
			this.updateProduct(productid, {cartCount: cartcount, viewed: viewed});
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
			this.addProduct(productid, 0, false);
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
				this.addProduct(parseInt($(productList[i]).data(this._dataAttributes.productId)), parseInt($(productList[i]).data(this._dataAttributes.cartCount)));
			}
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
		this.addProduct(productId, 0, true);
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
		for(var i = 0; i < this._eventCriteriaMet.length; i++) {
			window.SC.handleTimeOutOrCriteria(this._eventCriteriaMet[i]);
		}
	};




}

window.SC.addPlugin(new SCBCMain());
