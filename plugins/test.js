var testPlugin = function() {
	this._pluginName = 'test plugin';
	this._pluginId = 'test123';
	this._test = '';
	this._test2 = [];

	this.load = function() {
		if(SC.pluginLoaded(this._pluginId)) {
			SC.reportPluginLoadError(this._pluginName, this._pluginId);
			return false;
		}
		console.log('test load function called');
	}
}

window.SC.addPlugin(new testPlugin());
