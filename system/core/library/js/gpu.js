(function () {
require=(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
/**
 * @copyright 2015 Apple Inc. All rights reserved.
 */
'use strict';

/**
 * @name module:ac-function.once
 *
 * @function
 *
 * @desc Creates a function that executes `func` only once
 *
 * @param {Function} func
 *        The function to be executed once
 *
 * @returns {Function}
 */
module.exports = function once(func) {
	var result;

	return function() {
		if (typeof result === 'undefined') {
			result = func.apply(this, arguments);
		}

		return result;
	};
};

// ac-function@1.3.0

},{}],2:[function(require,module,exports){
/**
 * @copyright 2015 Apple Inc. All rights reserved.
 */
'use strict';

/**
 * @name module:globals
 * @private
 */
module.exports = {
	/**
	 * @name module.globals.getWindow
	 *
	 * @function
	 *
	 * @desc Get the window object.
	 *
	 * @returns {Window}
	 */
	getWindow: function () {
		return window;
	},

	/**
	 * @name module.globals.getDocument
	 *
	 * @function
	 *
	 * @desc Get the document object.
	 *
	 * @returns {Document}
	 */
	getDocument: function () {
		return document;
	},

	/**
	 * @name module.globals.getNavigator
	 *
	 * @function
	 *
	 * @desc Get the navigator object.
	 *
	 * @returns {Navigator}
	 */
	getNavigator: function () {
		return navigator;
	}
};

// ac-feature@2.6.0

},{}],3:[function(require,module,exports){
/**
 * @copyright 2015 Apple Inc. All rights reserved.
 */
'use strict';

/** @ignore */
var globalsHelper = require('./helpers/globals');
var once = require('@marcom/ac-function/once');

/**
 * @name module:ac-feature.webGLAvailable
 *
 * @function
 *
 * @desc Returns the availability of the HTML5 webGL API.
 *
 * @returns {Boolean} `true` if the browser supports webGL, otherwise `false`.
 */
function webGLAvailable() {
	var documentObj = globalsHelper.getDocument();
	var canvas = documentObj.createElement('canvas');

	if (typeof canvas.getContext === 'function') {
		return !!(canvas.getContext('webgl') || canvas.getContext('experimental-webgl'));
	}

	return false;
}

module.exports = once(webGLAvailable);
module.exports.original = webGLAvailable;

// ac-feature@2.6.0

},{"./helpers/globals":2,"@marcom/ac-function/once":1}],4:[function(require,module,exports){
/**
 * @module ac-gpu-reporter
 * @copyright 2016 Apple Inc. All rights reserved.
 */
'use strict';

module.exports = {
	GPUReporter: require('./ac-gpu-reporter/GPUReporter')
};

// ac-gpu-reporter@0.1.1

},{"./ac-gpu-reporter/GPUReporter":5}],5:[function(require,module,exports){
/**
 * @copyright 2016 Apple Inc. All rights reserved.
 */
'use strict';

/** @ignore */
var webGLAvailable 	= require('@marcom/ac-feature/webGLAvailable');
var defaultList 	= require('./defaults');


/**
 * @name module:ac-gpu-reporter.GPUReporter
 * @class
 *
 * @desc Finds the user's GPU through the Unmasked Renderer string
 * exposed through webGL.
 *
 * @param {Object} [options]
 *        Options to initialize the instance with
 */
function GPUReporter() {
	// Empty constructor.
}

var proto = GPUReporter.prototype;


proto.BLACKLISTED 	=  1;
proto.WHITELISTED 	=  2;
proto.NOT_LISTED 	=  4;
proto.NOT_FOUND 	=  8;
proto.NO_WEBGL 		= 16;

/**
 * @name module:ac-gpu-reporter.GPUReporter#getGPUClass
 *
 * @function
 *
 * @desc Finds the string of the Unmasked renderer, i.e. the GPU.
 *
 * @returns {String} The GPU name, or null if unable to find.
 */

proto.getGPUClass = function(extras) {

	var gpuName;
	var currentLists = this._extendLists(extras);

	if(webGLAvailable()) {
		gpuName = this.getGPU();

		if(gpuName !== null) {
			if(this._matchesList(gpuName, currentLists.whitelist )) {
				return this.WHITELISTED;
			} else if(this._matchesList(gpuName, currentLists.blacklist)) {
				return this.BLACKLISTED;
			} else {
				return this.NOT_LISTED;
			}
		} else {
			return this.NOT_FOUND;
		}
	} else {
		return this.NO_WEBGL;
	}
};

/**
 * @name module:ac-gpu-reporter.GPUReporter#getGPU
 *
 * @function
 *
 * @desc Finds the string of the Unmasked renderer, i.e. the GPU.
 *
 * @returns {String} The GPU name, or null if unable to be found.
 */
proto.getGPU = function() {
	var canvas, gl, extension;
	canvas		 = document.createElement('canvas');

	gl	= canvas.getContext('webgl')
				|| canvas.getContext('experimental-webgl')
				|| canvas.getContext('moz-webgl');
	if(gl) {
		extension = gl.getExtension("WEBGL_debug_renderer_info");
		if(extension!==null) {
			return gl.getParameter(extension.UNMASKED_RENDERER_WEBGL);
		}
		return gl.getParameter(gl.VERSION);
	}
	return null;
};



/**
 * @name module:ac-gpu-reporter.GPUReporter#_matchesList
 *
 * @function
 * @private
 *
 * @desc Matches the term to any entry in the list.
 *
 * @param {String} term
 *        The String representing the reference - in this case, the User's GPU
 *
 * @param {Array} list
 *        A list of GPUs, either the blacklist or the whitelist.
 *
 * @returns {Boolean} Whether the term matches one of the entries.
 */
/** @ignore */
proto._matchesList = function(term, list) {
	var i;
	for(i =0;i<list.length;i++) {
		if(this._matchesEntry(term, list[i])) {
			return true;
		}
	}
	return false;
};


/**
 * @name module:ac-gpu-reporter.GPUReporter#_matchesEntry
 *
 * @function
 * @private
 *
 * @desc Matches an individual entry against the reference string
 *
 * @param {String} term
 *        The String representing the reference - in this case, the User's GPU
 *
 * @param {String} entry
 *        The String representing the term - in this case, an entry in a list.
 *
 * @returns {Boolean} Whether the term contains all components of the entry.
 */
/** @ignore */
proto._matchesEntry = function(term, entry) {

	var termLower 			= term.toLowerCase();
	var entryPieces 		= entry.toLowerCase().split(" ");
	var containsAllPieces 	= true;
	var i;

	for(i = 0;i < entryPieces.length;i++) {
		if(termLower.indexOf(entryPieces[i]) === -1) {
			containsAllPieces = false;
		}
	}
	return containsAllPieces;
};


/**
 * @name module:ac-gpu-reporter.GPUReporter#_extendLists
 *
 * @function
 * @private
 *
 * @desc Extends the black- and white-lists with additional entries
 *
 * @param {Array} extras
 *        The JSON containing the extra whitelist and blacklist entries
 *
 * @returns {Array} The expanded black and whitelists,
 *	from a copy of the original.
 */
/** @ignore */
proto._extendLists = function (extras) {

	var i;
	var currentLists = JSON.parse(JSON.stringify(defaultList));

	if(extras !== undefined) {
		if(extras.blacklist !== undefined
			&& extras.blacklist.length > 0) {
			for(i =0 ;i< extras.blacklist.length;i++) {
				currentLists.blacklist.push(extras.blacklist[i]);
			}
		}

		if(extras.whitelist !== undefined
			&& extras.whitelist.length > 0) {
			for(i =0 ;i< extras.whitelist.length;i++) {
				currentLists.whitelist.push(extras.whitelist[i]);
			}
		}
	}
	return currentLists;
};


module.exports = GPUReporter;

// ac-gpu-reporter@0.1.1

},{"./defaults":6,"@marcom/ac-feature/webGLAvailable":3}],6:[function(require,module,exports){
module.exports ={
	"blacklist": [
		"Intel HD Graphics 5300",
		"AMD Radeon R5 Graphics",
		"Apple A7 GPU"
		],
	"whitelist": [
		"Radeon FirePro D700",
		"GeForce GT 750 M",
		"Apple A8 GPU",
		"Apple A9 GPU",
		"Apple A9X GPU"
		]
};
// ac-gpu-reporter@0.1.1

},{}],"@marcom/gpu-tracker/GPUTracker":[function(require,module,exports){
'use strict';

var GPUReporter = require('@marcom/ac-gpu-reporter').GPUReporter;

module.exports = function GPUTracker() {

	var reporter = new GPUReporter();

	var gpuName = reporter.getGPU();
	// GPU Reporter will return a string or null, and Johnson said if no info is available, to pass null for the value.

	if(!gpuName){
		gpuName = 'Unknown device';
	}

	var pageTrackingDataMetaTag = document.createElement('meta');
	pageTrackingDataMetaTag.setAttribute('property', 'analytics-s-page-tracking-data');
	pageTrackingDataMetaTag.content = gpuName;
	document.getElementsByTagName('head')[0].appendChild(pageTrackingDataMetaTag);

};
},{"@marcom/ac-gpu-reporter":4}],"@marcom/gpu-tracker":[function(require,module,exports){
'use strict';

module.exports = {
	GPUTracker : require("./GPUTracker")
};
},{"./GPUTracker":"@marcom/gpu-tracker/GPUTracker"}]},{},[]);

var GPUTracker = require("@marcom/gpu-tracker/GPUTracker");
GPUTracker();

}());
