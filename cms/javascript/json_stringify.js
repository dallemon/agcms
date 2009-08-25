//http://www.sitepoint.com/blogs/2009/08/19/javascript-json-serialization/
//This might not produce as valid JSON as http://www.JSON.org/json2.js 2009-08-17
var JSON = JSON || {};

// implement JSON.stringify serialization
JSON.stringify = JSON.stringify || function (obj) {
	var t = typeof (obj);
	if (t != "object" || t == "undefined" || obj === null) {
		// simple data type
		if (t == "string")
			//TODO we need to escape "?
			obj = '"'+obj.replace(/"/g, '\\"')+'"';
			
		if (t == "undefined" || t == "null" )
			obj = '""';
		
		return String(obj);
	} else if(typeof obj.toJSON == "function") {
		return obj.toJSON();
	} else {
		var n, v, json = [], arr = (obj && obj.constructor == Array);
		
		for (n in obj) {
			v = JSON.stringify(obj[n]);
			json[json.length] = (arr ? "" : '"' + n + '":') + String(v);
		}   
	
		return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
	}
};
// recurse array or object
if (typeof Date.prototype.toJSON !== 'function') {
	Date.prototype.toJSON = function (key) {
		return isFinite(this.valueOf()) ? this.getUTCFullYear() + '-' + f(this.getUTCMonth() + 1) + '-' + f(this.getUTCDate()) + 'T' + f(this.getUTCHours()) + ':' + f(this.getUTCMinutes()) + ':' + f(this.getUTCSeconds()) + 'Z' : null
	};
	String.prototype.toJSON = Number.prototype.toJSON = Boolean.prototype.toJSON = function (key) {
		return this.valueOf();
	}
}