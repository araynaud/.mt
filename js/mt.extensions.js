//global variable test functions
function isMissing(variable)
{
	return typeof variable==="undefined" || variable===null;
}

//for string or array
function isEmpty(variable)
{
	if(isMissing(variable)) return true;
	if(!isMissing(variable.length)) return variable.length === 0;
	if(isObject(variable))	return !Object.hasKeys(variable);
	return false;
}

function isDefined(variable, ctx)
{
	if(!ctx) ctx = window;
	if(!variable) return ctx;
	var arr = variable.split(".");
	for(var i=0; i < arr.length; i++)
	{
		if(isMissing(ctx[arr[i]])) return false;
		ctx = ctx[arr[i]];
	}
	return true;
}

function valueIfDefined(variable, ctx)
{
	if(!ctx) ctx = window;
	if(!variable) return ctx;
	var arr = variable.split(".");
	for(var i=0; i < arr.length; i++)
	{
		if(isMissing(ctx[arr[i]])) return ctx[arr[i]];
		ctx = ctx[arr[i]];
	}
	return ctx;
}

function valueOrDefault(variable, defaultValue)
{
	return isMissing(variable) ? defaultValue : variable;
}

function isNumber(input)
{
    return typeof(input)=="number";
}

function isBoolean(input)
{
    return typeof(input)=="boolean";
}

function isString(input)
{
    return typeof(input)=="string" || Object.getType(input) == "String";
}

function isObject(input)
{
    return !isMissing(input) && typeof(input)=="object";
}

function isFunction(input)
{
    return typeof(input)=="function";
}

function isArray(input)
{
	return Object.getType(input) === "Array";
}

function isObjectNotArray(input)
{
	return isObject(input) && !isArray(input);
}

function getVar(name)
{
    return window[name];
}

//This code loads the IFrame Player API code asynchronously.
function loadJavascript(src)
{
	var tag = document.createElement('script');
	tag.src = src;
	var firstScriptTag = document.getElementsByTagName('script')[0];
	firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
}

//String EXTENSION METHODS
String.PATH_SEPARATOR="/";
String.setSeparator = function(sep)
{
	if(!sep) sep="/";
	String.PATH_SEPARATOR=sep;
};

//combine paths with /
String.combine3 = function()
{
	var pathArray=Array.fromArguments(arguments, true); //, String.PATH_SEPARATOR);
	var path = pathArray.join(String.PATH_SEPARATOR);
	path = path.replace(String.PATH_SEPARATOR +  String.PATH_SEPARATOR, String.PATH_SEPARATOR);
	return path;
}

//count start // chars
String.isRootedPath = function(str)
{
	if(!str) return false;
	for(var i=0; i<2 && i<str.length; i++)
		if(str[i] != String.PATH_SEPARATOR)
			return i;
	return i;
};

String.combine = function()
{
	//split each arg level
	var args = Array.fromArguments(arguments);
	//find first root, remove what is before
	for(var i=0; i<args.length; i++)
		if(String.isRootedPath(args[i]))
		{
			if(i) args.splice(0,i);
			break;
		}
	
	var pathArray = args.flatten(String.PATH_SEPARATOR);
	var hasDomain = String.startsWith(args[0], "//");
	var isRooted = String.startsWith(args[0], String.PATH_SEPARATOR);
	//filter pathArray
	for(var i=0; i<pathArray.length; )
	{
		if(isEmpty(pathArray[i]) || pathArray[i]===".")
			pathArray.splice(i,1);
		else if(i>1 && pathArray[i]==".." && pathArray[i-1]!="..")
			pathArray.splice(i-1,2);
		else
			i++;
	}
	var path = pathArray.join(String.PATH_SEPARATOR);
	//restore initial slashes
	if(hasDomain)
		path = "//" + path;
	else if(isRooted)
		path = String.PATH_SEPARATOR + path;
	return path;
};

//filename functions
String.getFilename = function(url)
{
	url = url.substringBefore("?");
	url = url.substringAfter("/", true, true);
	return url.urlDecode();
};

String.prototype.getFilename = function()
{
	return String.getFilename(this);
};

String.getParent = function(url)
{
	url = url.substringBefore("?");
	url = url.substringBefore("/", true, true);
	return url.urlDecode();
};

String.prototype.getParent = function()
{
	return String.getParent(this);
};

String.getFilenameNoExt = function(url)
{
	var name=String.getFilename(url);
	name = name.substringBefore(".", true);
	return name;
};

String.prototype.getFilenameNoExt = function()
{
	return String.getFilenameNoExt(this);
};

String.getExt = function(url)
{
	return url.substringAfter(".",true);
};

String.prototype.getExt = function()
{
	return String.getExt(this);
};

String.urlDecode = function(url) 
{
	return decodeURIComponent(url.replace(/\+/g, " "));
};

String.prototype.urlDecode = function() 
{
	return decodeURIComponent(this.replace(/\+/g, " "));
};

String.urlEncode = function(url) 
{
	return encodeURIComponent(url).replace("&", "%26");
};

String.prototype.urlEncode = function() 
{
	return encodeURIComponent(this).replace("&", "%26");
};

String.prototype.quote = function() 
{
	return "'" + this + "'";
};

//FORMAT STRING
String.format = function()
{
	var s = arguments[0];
	for (var i = 0; i < arguments.length - 1; i++)
	{       
		var reg = new RegExp("\\{" + i + "\\}", "gm");
		var value=arguments[i+1];
		if(isObject(value) && JSON)
			value=JSON.stringify(value);
		s = s.replace(reg, value);
	}
	return s;
};

String.prototype.format = function()
{
	var s = this;
	var reg;
	for (var i = 0; i < arguments.length; i++)
	{       
		reg = new RegExp("\\{" + i + "\\}", "gm");
		var value=arguments[i];
		if(isObject(value) && JSON)
			value=JSON.stringify(value);
		s = s.replace(reg, value);
	}
	reg = new RegExp("\\{\\d\\}", "gm");
	s = s.replace(reg, "");
	return s;
};

String.toBold = function (str)
{
	return 	"<b>" + str + "</b>";
};

String.toLink = function (text)
{
	var href = valueOrDefault(href, "#"+ text);
	return 	"<a href='{1}'>{0}</a>".format(text, href);
};

String.parseKeywords = function(text, words, format, multiple)
{
	if(isEmpty(text) || isEmpty(words)) return text;
	
	format = valueOrDefault(format, String.toBold);
	//format: if function, call it, if string: use as format string, otherwise, default function
	//in replace: format(match, position, text);
	if(isString(format))
	{
		formatString = format;
		format = function(word) { return formatString.format(word); };
	}

	if(isArray(words))	
		words = words.join("|");

	var regex = "\\b(" + words + ")\\b";
//	if(multiple)
//		regex = "\\b(" + words + ")\\b(" + words + ")\\b";

//	console.log(regex);
	regex = new RegExp(regex, "gi");
	text = text.replace(regex, format);
	return text;
};

String.prototype.parseKeywords = function(words, format, multiple)
{
	return String.parseKeywords(this.toString(), words, format, multiple);
};

String.accentChars = /[áàâÁÀÂçÇéèêëÉÈÊËïîíÏÎÍôóőöÔÓŐÖűúùüŰÚÙÜÿŸæœÆŒ]/g;
String.accentTranslate = { 
	"á": "a", "à": "a",	"â": "a", "ã": "a",
	"Á": "A", "À": "A",	"Â": "A", "Ã": "A",
	"ç": "c", "Ç": "C",
	"é": "e", "è": "e",	"ê": "e", "ë": "e",
	"É": "E", "È": "E",	"Ê": "E", "Ë": "E",
	"ï": "i", "î": "i",	"í": "i",
	"Ï": "I", "Î": "I",	"Í": "I",
	"ô": "o", "ó": "o", "ő": "o", "ö": "o",
	"Ô": "O", "Ó": "O", "Ő": "O", "Ö": "O",
	"ű": "u", "ú": "u", "ù": "u", "ü": "u",
	"Ű": "U", "Ú": "U", "Ù": "U", "Ü": "U",
	"ÿ": "y", "Ÿ": "Y",
	"æ":"ae", "œ":"oe", "Æ":"AE", "Œ":"OE"
};

String.cleanupAccents = function(s) 
{
	return s.toString().cleanupAccents();
};

String.prototype.cleanupAccents = function() 
{
	if(!String.accentChars)
		String.accentChars = new RegExp(Object.keys(String.accentTranslate).join(''), 'g');
	return this.replace(String.accentChars, function(match) { return String.accentTranslate[match] || match; });
};

String.prototype.hasAccent = function() 
{
	return String.accentTranslate.hasOwnProperty(this);
}

String.makeTitle = function(str)
{
	if(isMissing(str)) return "";
	return str.toString().makeTitle();
};

String.prototype.makeTitle = function(capitalize)
{
	var str = this.replace(/_/g, " ");
	str = str.replace(/\//g, " ");
	str = str.replace(/\./g, " ");
	var strClean = str.cleanupAccents();

	for(var i=str.length-1; i>0; i--)
		if(strClean.isWordEnd(i))
		{
			if(capitalize && str.getCharType(i)=="lower")
				str = str.setCharAt(i, str[i].toUpperCase());
			str = str.insert(i," ");
		}
	//str = str.replace(/  /g, " ");

	if(capitalize && str.getCharType(0)=="lower")
		str = str.setCharAt(0, str[0].toUpperCase());

	return str;
};

String.prototype.setCharAt = function(index, chr)
{
	return this.substr(0,index) + chr + this.substr(index+1);
}

String.prototype.makeFilename = function()
{
	var str = str.replace(/ /g, "");
	return str;
}

//lowercase, each first letter of word uppercase
//isWordStart
String.prototype.capitalize = function()
{
}


String.prototype.isDigit = function(index)
{
	var ch = this.charAt(index);
	return ch >= "0" && ch <= "9";
};

String.prototype.isUppercase = function(index)
{
	var ch = this.charAt(index);
	return ch >= "A" && ch <= "Z";
};

String.prototype.isLowercase = function(index)
{
	var ch = this.charAt(index);
	return ch >= "a" && ch <= "z";
};

String.prototype.getCharType = function(index)
{
	var ch=this.charAt(index);
	if(!ch) return "";
	if(ch >= "0" && ch <= "9") return "digit";
	if(ch >= "a" && ch <= "z") return "lower";
	if(ch >= "A" && ch <= "Z") return "upper";
	return "other";
};

String.prototype.diffCharType = function(index)
{
	if(index<=0)return false;
	var prev = this.getCharType(index-1);
	var cur = this.getCharType(index);
	return prev != cur;
};

String.prototype.isWordEnd = function(index)
{
	if(index<=0)return false;
	var prev = this.getCharType(index-1);
	var cur  = this.getCharType(index);
	var next = this.getCharType(index+1);
	if(prev=="upper" && cur=="upper" && next=="lower") return true;
	if(prev=="upper" && cur=="lower") return false;
	return prev != cur;
};


String.prototype.insert = function(pos, str)
{
	return this.substr(0, pos) + str + this.substr(pos);
};

String.prototype.remove = function(pos, len)
{
	return this.substr(0, pos) + this.substr(pos+len);
};

String.startsWith = function(str, sub)
{
	if(!isString(str)) return false;
	return str.startsWith(sub);
}

String.prototype.startsWith = function(sub)
{
	if(isEmpty(this) || isEmpty(sub)) return false;
	return this.indexOf(sub) == 0;
}

String.endsWith = function(str, sub)
{
	if(!isString(str)) return false;
	return str.endsWith(sub);
}

String.prototype.endsWith = function(sub)
{
	if(isEmpty(this) || isEmpty(sub)) return false;
	var end = this.lastIndexOf(sub);
	return end>=0 && this.substr(end) == sub;
}

String.prototype.containsText = function(value, caseSensitive)
{
	if(caseSensitive)
		return this.indexOf(value) !=-1;
	return this.toLowerCase().indexOf(value.toLowerCase()) !=-1;
};

String.substringBefore = function(s, sub, last, stringOrEmpty, include)
{
	var pos=last ? s.lastIndexOf(sub) : s.indexOf(sub);
	if(pos<0) 
		return stringOrEmpty ? "" : s.toString();
	if(include)
		pos+=sub.length;

	return s.toString().substring(0,pos);
};

String.prototype.substringBefore = function(sub, last, stringOrEmpty, include)
{
	return String.substringBefore(this, sub, last, stringOrEmpty, include);
};

String.substringAfter = function(s, sub, last, stringOrEmpty, include)
{
	var pos=last ? s.lastIndexOf(sub) : s.indexOf(sub);
	if(pos==-1) 
		return stringOrEmpty ? s.toString() : "";
	if(!include)
		pos+=sub.length;
	return s.toString().substring(pos);
}

String.prototype.substringAfter = function(sub, last, stringOrEmpty, include)
{
	return String.substringAfter(this, sub, last, stringOrEmpty, include);
};


String.escapeQuotes = function(str)
{
	return str.escapeQuotes();
}

String.prototype.escapeQuotes = function()
{
	var rUnescapeQuotes = /\\(['"])/g;
	var rEscapeQuotes = /\\?(['"])/g;
	return this.replace(rEscapeQuotes, "\\$1");

}

//parse CSV string, use header line for element field names
String.prototype.parseCsv = function(header)
{
	return String.parseCsv(this, header);
}

String.parseCsv = function(csv, header)
{
	var rows = [];
	if(!csv) return rows;
	
	var lines = csv.split('\n');
	var singleColumn = (lines[0].indexOf(";") < 0);
	lines.forEach(function(line)
	{
		line = line.trim();
		if(!line) return;
		if(singleColumn)
			rows.push(line);
		else
			rows.push(line.split(';'));
	});
	
	if(header === true)
		header = rows.shift();
	if(header && !singleColumn)
	{
		var list = [];
		rows.forEach(function(row)
		{
			list.push(row.toMap(header));
		});
		rows = list;
	}

//  if(keyColumn > 0 || keyColumn === 0)
//     return rows.indexBy(keyColumn);

	return rows;
}

String.append = function(str1, sep, str2)
{
    str1 = valueOrDefault(str1, "");
    return str1.toString().append(sep,str2).toString();
};

String.prototype.append = function(sep, str)
{
    if(arguments.length==1)
    {
        str=sep;
        sep="";
    }
    if(!sep) sep="";
    if(!str) return this;

    if(this.length)
        return this + sep + str;
    return str.toString();
};


//ARRAY EXTENSION METHODS

if (!Array.prototype.clone) Array.prototype.clone = function()
{
	if(this.slice)
		return this.slice(0);

	var clone = [];
	for (var i = 0; i < this.length; i++)
			clone.push(this[i]);
};

// For IE array.indexOf
if (!Array.prototype.indexOf) Array.prototype.indexOf = function(element)
{
	var len = this.length >>> 0;
	for (var i = 0; i < len; i++)
		if(this[i]===element) return i;
	return -1;
};

if (!Array.prototype.find)  Array.prototype.find = function(predicate)
{
    if (this == null)
      throw new TypeError('Array.prototype.find called on null or undefined');

    if (typeof predicate !== 'function')
      throw new TypeError('predicate must be a function');

    var list = Object(this);
    var length = list.length >>> 0;
    var thisArg = arguments[1];
    var value;

    for (var i = 0; i < length; i++) 
    {
      value = list[i];
      if(predicate.call(thisArg, value, i, list)) return value;
    }
    return undefined;
};


if (!Array.prototype.findLast)  Array.prototype.findLast = function(predicate)
{
    if (this == null)
      throw new TypeError('Array.prototype.findLast called on null or undefined');

    if (typeof predicate !== 'function')
      throw new TypeError('predicate must be a function');

    var list = Object(this);
    var length = list.length >>> 0;
    var thisArg = arguments[1];
    var value;
    for (var i = length-1; i >=0; i--) 
    {
      value = list[i];
      if(predicate.call(thisArg, value, i, list)) return value;
    }
    return undefined;
};


if (!Array.prototype.findIndex)  Array.prototype.findIndex = function(predicate)
{
    if (this == null)
       throw new TypeError('Array.prototype.findIndex called on null or undefined');
    if (typeof predicate !== 'function')
      throw new TypeError('predicate must be a function');

    var list = Object(this);
    var length = list.length >>> 0;
    var thisArg = arguments[1];
    var value;
    for (var i = 0; i < length; i++)
    {
      value = list[i];
      if (predicate.call(thisArg, value, i, list))	return i;
    }
    return -1;
};

if (!Array.prototype.findLastIndex)  Array.prototype.findLastIndex = function(predicate)
{
    if (this == null)
       throw new TypeError('Array.prototype.findIndex called on null or undefined');
    if (typeof predicate !== 'function')
      throw new TypeError('predicate must be a function');

    var list = Object(this);
    var length = list.length >>> 0;
    var thisArg = arguments[1];
    var value;
    for (var i = length-1; i >=0; i--) 
    {
      value = list[i];
      if (predicate.call(thisArg, value, i, list))	return i;
    }
    return -1;
};

//add keys to array of objects by value of field
Array.prototype.indexBy = function(fieldName, modify)
{
	var obj = modify ? this : {};
	for(var i=0; i< this.length; i++)
	{
		key = this[i][fieldName];
		if(!isMissing(key) && !obj.hasOwnProperty(key))
			obj[key] = this[i];
	}
	return obj;
};

// For IE array.filter
if (!Array.prototype.filter) Array.prototype.filter = function(fun /*, thisp*/)
{
	var len = this.length >>> 0;
	if (typeof fun != "function")
	throw new TypeError();

	var res = [];
	var thisp = arguments[1];
	for (var i = 0; i < len; i++)
	{
		if (i in this) 
		{
			var val = this[i]; // in case fun mutates this
			if (fun.call(thisp, val, i, this))
			res.push(val);
		}
	}
	return res;
};

//extract: returns filtered array and remove its element from this array
if (!Array.prototype.extract) Array.prototype.extract = function(fun /*, thisp*/)
{
	var len = this.length >>> 0;
	if (typeof fun != "function")
	throw new TypeError();

	var res = [];
	var thisp = arguments[1];
	
	for (var i = 0; i < len; i++)
	{
		if(!(i in this)) continue;
		var val = this[i]; // in case fun mutates this
		if (fun.call(thisp, val, i, this))
		{
			val=this.splice(i--, 1)[0];
			res.push(val);
		}
	}
	return res;
};

if (!Array.prototype.shuffle) Array.prototype.shuffle = function()
{
	var s = [];
	while (this.length)
	{
		var randomElement=this.splice(Math.random() * this.length, 1)[0];
		s.push(randomElement);
	}
	while (s.length)
		this.push(s.pop());
	return this;
};

if (!Array.prototype.reverse) Array.prototype.reverse = function()
{
	var s = [];
	while (this.length)
		s.push(this.splice(0, 1)[0]);
	while (s.length)
		this.push(s.pop());
	return this;
};


Array.intersect = function(array1,array2)
{
	return array1.filter(function(n) {
	    return array2.indexOf(n) != -1
	});
};

Array.prototype.intersect = function(array2, funct)
{
	return this.filter(function(n)
	{
		if(isFunction(funct))
			n = funct(n);
		if(isString(funct) && n[funct])
			n = n[funct]();
	    return array2.indexOf(n) != -1
	});
};

Array.diff = function(array1, array2)
{
	return array1.filter(function(n) {
	    return array2.indexOf(n) == -1
	});
};

Array.prototype.diff = function(array2)
{
	return this.filter(function(n) {
	    return array2.indexOf(n) == -1
	});
};

//make an object using 1 array for keys and 1 for values
//if no keys supplied: turn values into keys
Array.prototype.toMap = function(keys)
{
	var map = {};
	if(!keys) keys = this;
	var len = Math.min(keys.length, this.length);
	for(var i=0; i < len; i++)
		map[keys[i]] = this[i];
	return map;
};

Array.prototype.getHalfElements = function(alt)
{
	var mod=0;
	return this.filter(function () { mod=!mod; return alt ^ mod; } );
};

//make 1 dimensional array
//recurse into elements of type Array and concat them
//if separator passed: split string elements
Array.prototype.flatten = function(separator)
{
	var result=[];
	var arr;
	for(var i=0; i<this.length;i++)
	{
		arr=this[i];
		if(!isEmpty(separator) && isString(this[i]))
			arr=this[i].split(separator);
		else if(isArray(this[i]))
			arr = this[i].flatten(separator);
		result = result.concat(arr);
	}
	return result;
};

//transform function arguments into array 
Array.fromArguments = function(args, flat, separator)
{
	var argarray= Array.prototype.slice.call(args);
	if(flat)
		return argarray.flatten(separator);
	return argarray;
};

//divide array into N slices
//take every Nth element
Array.prototype.divideInto = function(nb,transpose)
{
	//divide in 1 or more than length = same array
	if(isEmpty(this))
		return [];
	if(isMissing(nb) || nb <=1) 
		return [this];
	nb=Math.floor(nb);
	if(nb > this.length)
		nb=this.length;
	var result = [];
	var i=0;
	if(transpose)
	{
		var remainingElements=this;
		for(i=0;i<nb;i++)
		{
			var perCol=remainingElements.length / (nb-i);
			perCol = (i+1 <= nb/2) ? Math.ceil(perCol) : Math.floor(perCol);
			result[i]=remainingElements.slice(0,perCol);
			remainingElements = remainingElements.slice(perCol);
		}
	}
	else
	{
		for(i=0;i<nb;i++)
			result.push([]);
		for(i=0;i<this.length-1;i++)
			result[i % nb].push(this[i]);
		result[nb-1].push(this[i]);
	}
	return result;
};

//divide into pages with fixed number. nb = max number per page
Array.prototype.paginate = function(nb, equalize, minPages)
{
	if(isEmpty(this))
		return [];
	minPages = valueOrDefault(minPages, 1);
	var nbPages = Math.ceil(this.length / nb);
	if(isMissing(nb) || nb <= 1 || nb > this.length || nbPages < minPages) 
		return [this];

	var result = [];
	var remaining = this.length;
	var start = 0;
	
	for(var i=0; i<nbPages; i++)
	{
		if(equalize)
			nb = Math.ceil(remaining / (nbPages-i));
		result.push(this.slice(start, start+nb));
		start += nb;
		remaining -= nb;
	}
	return result;
};

// value for filter, sort or group
Array.prototype.getElementValue = function(i,field)
{
	return Object.getFieldValue(this[i],field);
};

Object.getFieldValue = function(obj,field)
{
	var value;
	//medhod name exists: call object.method
	if(isObject(obj) && obj[field] && isFunction(obj[field])) 
		value = obj[field]();
	//call function(object)
	else if(isFunction(field))
		value = field(obj);
	//return object.field
	else if(isObject(obj))
		value = obj[field];
	//or return object if no field or function
	else
		value = obj;
	return value;
};

//get array of distinct values on a field or function of the elements
//and count for each value
Array.prototype.countBy = function(field, skipNull)
{
	var result = {};
	var i=0;
	var value;
	for(i=0;i<this.length;i++)
	{
		value = this.getElementValue(i,field);
		if(isMissing(value) && skipNull) continue;
		if(isMissing(value)) value=0;
		if(!result[value])
			result[value]=1;
		else
			result[value]++;
	}
	return result;	
}

Array.prototype.distinct = function(field, skipNull, exclude)
{
	var distinctCount = this.countBy(field, skipNull);
	if(exclude)
		for(var i in exclude)
			delete distinctCount[exclude[i]];
	return Object.keys(distinctCount);	
}


//divide array into N slices
//depending on distinct values for a field or function of the elements
Array.prototype.groupBy = function(field)
{
	var result = {};
	var i=0;
	var value;
	for(i=0;i<this.length;i++)
	{
		value = this.getElementValue(i,field);
		if(isMissing(value)) value=0;
		if(!result[value])
			result[value]=[];
		result[value].push(this[i]);
	}
	return result;
};

//search array for element
//TODO add object for search {name:"a", date:"x"}
Array.prototype.getElementPosition = function(value,key)
{
	for(var i=0;i<this.length;i++)
		if(this.elementMatches(i,value,key))
			return i;		

	return -1;
};

Array.prototype.elementMatches = function(i,value,key)
{
	var element = this[i];

	if(!$.isArray(key))
		return isObject(element) && element[key]==value || element==key || element==value;

	for(j=0;j<key.length;j++)
		if(this.elementMatches(i,value,key[j]))
			return true;
	return false;
};

Array.prototype.remove = function(index,key)
{	
	if(isString(index))
		index = this.getElementPosition(index,key);
	return this.splice(index,1);
};

Array.prototype.min = function()
{	
	return Math.min.apply(Math, this);
};

Array.prototype.max = function()
{	
	return Math.max.apply(Math, this);
};

Array.prototype.sum = function(property)
{	
	var sum=0;
	for(var i=0; i<this.length; i++)
	{
		var value = this[i];
		if(!isEmpty(property))
		{			
			if(isFunction(property))
				value = property(this[i]);
			else if(isFunction(this[i][property])) 
				value = this[i][property]();
			else value = this[i][property];
		}
		if(!isMissing(value))
			sum = sum + value;
	}
	return sum;
};

Array.prototype.avg = function(property)
{	
	return this.sum() / this.length;
};

Array.prototype.minmax = function()
{	
	var minmax={};
	if(!this.length) return minmax;
	var minValue, minIndex, maxValue, maxIndex;

	for(var i=0; i<this.length; i++)
	{		
		if(isMissing(minValue) || this[i] < minValue)
		{
			minValue=this[i];
			minIndex=i;
		}
		if(isMissing(maxValue) || this[i] > maxValue)
		{
			maxValue=this[i];
			maxIndex=i;
		}
	}
	minmax.min={index: minIndex, value: minValue};
	minmax.max={index: maxIndex, value: maxValue};
	return minmax;
};


if (!Array.prototype.contains) Array.prototype.contains = function(value)
{
	return $.inArray(value, this) !=-1;
};

//ARRAY SORT FUNCTIONS
function compareElements(a, b, reverse, caseSensitive)
{
	if(!caseSensitive && isString(a)) a = a.toUpperCase(); 
	if(!caseSensitive && isString(b)) b = b.toUpperCase(); 	
	var rev= reverse ? -1 : 1;
	if(a>b) return rev;
	if(a<b) return -rev;
	return 0;
}
	
//sort object array by a field
Array.sortObjectsBy = function(arr, sortField, reverse, caseSensitive)
{    
	arr.sort(function(a, b)
	{
		if(!sortField) 
			return compareElements(a, b, reverse, caseSensitive);
		if(isFunction(a[sortField]))
			return compareElements(a[sortField](), b[sortField](), reverse, caseSensitive);
		if(isFunction(sortField))
			return compareElements(sortField(a), sortField(b), reverse, caseSensitive);
		return compareElements(valueOrDefault(a[sortField],""), valueOrDefault(b[sortField],""), reverse, caseSensitive);
	});
	return arr;
};

Array.prototype.sortObjectsBy = function(sortField, reverse, caseSensitive)
{
	return Array.sortObjectsBy(this, sortField, reverse, caseSensitive);
};

//sort string array, case insensitive by default
Array.sortStrings = function(arr, reverse, caseSensitive)
{   
	arr.sort(function(a, b)
	{ 
	  return compareElements(a, b, reverse, caseSensitive);
	}); 
   return arr;
}

Array.prototype.sortStrings = function(reverse, caseSensitive)
{
	return Array.sortStrings(this, reverse, caseSensitive);
};

//--- OBJECT EXTENSION FUNCTIONS

// Function to merge all of the properties from one object into another
//addNew: if false, only copy o2 properties that already exist in o1
//includeFunctions: if true, copy o2 functions to o1

Object.merge = function (o1, o2, addNew, includeFunctions)
{
	if(!o2) return o1;
	for(var k in o2)
	{
		if(!addNew && !(k in o1)) continue;
		if(!includeFunctions && isFunction(o2[k])) continue;
		if(isObjectNotArray(o1[k]) && isObjectNotArray(o2[k]))
			Object.merge(o1[k], o2[k], true);
		else
			o1[k] = o2[k];
	}
	return o1;
};

//difference by keys
Object.keyDiff = function (o1, o2)
{
	if(!o2) return o1;
	var result= {};
	for(var key in o1)
	{
		if(o1.hasOwnProperty(key) && !o2.hasOwnProperty(key))
			result[key] = o1[key];
	}
	return result;
};

//intersection by keys
Object.keyIntersect = function (o1, o2)
{
	if(!o2) return o1;
	var result= {};
	for(var key in o1)
	{
		if(o1.hasOwnProperty(key) && o2.hasOwnProperty(key))
			result[key] = o1[key];
	}
	return result;
};

Object.foreach = function (obj, funct)
{
	if(!isObject(obj)) return funct(obj);
	for(var key in obj)
	{
		if(!obj.hasOwnProperty(key)) continue;
		var value = obj[key];
		funct(key, value);
	}
}

//TODO add browser info function
Object.toText = function (obj, separator, includeFunctions)
{
	if(!isObject(obj)) return obj;
	
	var msg="";
	for(k in obj)
	{
		if(isFunction(obj[k]) && !includeFunctions) continue;
		msg+= separator + k+ ": " +  Object.toText(obj[k], separator);
	}
	return isEmpty(msg) ? obj : msg;
};

Object.remap = function (obj, keyConvert)
{
	var newobj = {};
	for(var key in obj)
	{
		if(!key || key.startsWith("$")) continue;
		var newkey = key.toLowerCase();
		newobj[newkey] = obj[key];
	}
	return newobj;
};

String.prototype.appendQueryString = function(obj)
{
	return String.appendQueryString(this, obj);
}

String.appendQueryString = function(str, obj)
{
	qs = Object.toQueryString(obj);
	if(!qs) return str;
	sep = str.containsText("?") ? "&" : "?";
	return str + sep + qs;
}

Object.toQueryString = function(obj)
{
	if(!obj) return "";
	if(isString(obj)) return obj;
	result="";
	var sep="";
	for (p in obj)
	{
		if(isEmpty(obj[p]) || isFunction(obj[p])) continue;
		result += sep + p +"=" + obj[p];
		sep="&";
	}
	return result;
};

//returns constructor name?
Object.getType = function(obj)
{
	var t = Object.prototype.toString.call(obj);
// 		Object.getType(null)	"[object Null]"	String
	return t.substringAfter(" ").substringBefore("]");
};

// get type / class / constructor name
Object.getClassName = function(obj)
{
	if(!obj) return obj;
	var constr=obj.constructor; // || obj.__proto__.constructor;
	constr = constr.toString();
	return constr.substringBefore("(").substringAfter(" ");
}

// get type / class / constructor name
Object.isInstanceOf = function(obj,type)
{
	return Object.getClassName(obj) === type;
};

//get Nth element of array or object
Object.getNth = function(obj,key)
{
	if(!isMissing(obj[key])) 
		return obj[key];
	keys = Object.keys(obj);
	key = keys[key];
	if(!isMissing(key))
		return obj[key];
};

//return array of property values from object
Object.values = function (obj, skipNull)
{
    var vals = [];
    if(!obj) return vals;
    //if array, return obj
    if(isArray(obj)) return obj;
    if(!isObjectNotArray(obj)) return obj;
    //TODO if not object, return [obj]
    for(var key in obj)
        if (obj.hasOwnProperty(key) && (!isMissing(obj[key]) || !skipNull))
            vals.push(obj[key]);
    return vals;
};

if(!isFunction(Object.keys)) 
	Object.keys = function (obj, skipNull)
{
    var vals = [];
    if(!obj) return vals;
    //if array, return obj
    if(!isObjectNotArray(obj)) return obj;
    //TODO if not object, return [obj]
    for(var key in obj)
        if (obj.hasOwnProperty(key) && (!isMissing(obj[key]) || !skipNull))
            vals.push(key);
    return vals;
};

//return first key
Object.hasKeys = function (obj)
{
    if(!obj) return false;
    for(var key in obj)
        if (key && obj.hasOwnProperty(key))
            return true;
    return false;
};

Object.toArray = function(obj, skipNull)
{
    var vals = [];
    if(!obj) return vals;
    //if array, return obj
    if(isArray(obj)) return obj;
    if(!isObjectNotArray(obj)) return obj;
    //TODO if not object, return [obj]
    for(var key in obj)
        if (obj.hasOwnProperty(key) && (!isMissing(obj[key]) || !skipNull))
        {
        	var value = obj[key];
            var element = {key: key};
            if(isObjectNotArray(value))
	            Object.merge(element, obj[key], true);
	        else 
	        	element.value = value;
            vals.push(element);
        }
    return vals;
};



//--- OTHER UTILITY FUNCTIONS
function plural(nb, word, pluralWord)
{
	if(!nb) return "";
	
	if(nb>1 && pluralWord)
		word=pluralWord;
	else if(nb>1 && word[word.length-1]!="s") 
		word+="s";
	return nb + " " + word;
}

Math.roundMultiple = function(value, multiple)
{
	return multiple * Math.ceil(value/multiple);
}

Math.roundDigits = function(value, digits)
{
	var power=1;
	if(digits) power = Math.pow(10,digits);
	return Math.round(value*power)/power; 
};

function toFraction(num, den, max)
{
	den=valueOrDefault(den, 1);
	max=valueOrDefault(max, 100);
	var rest = num % den;
	var div = num / den;
	var frac = div % 1;
	if(!rest) return div;

	var fraction = div;
	var incr=1;
	for(i=2; i <= max; i+=incr)
	{
		rest = num * i % den;
		div = num * i / den;
		frac = div % 1;
		frac = Math.abs(frac - 1);
		fraction = (num * i / den).toFixed(0); 
		if(!rest || frac < .01)
			return fraction +"/"+ i;
	}
	return fraction + "%";
}


Number.prototype.leftZeroPad = function(numZeros) 
{
	var n = Math.abs(this);
	var zeros = Math.max(0, numZeros - Math.floor(n).toString().length );
	var zeroString = Math.pow(10,zeros).toString().substr(1);
	if( this < 0 )
		zeroString = '-' + zeroString;

	return zeroString+n;
};

function formatTime(seconds)
{
	seconds=Math.round(seconds);
	var minutes=Math.floor(seconds/60);
	var hours=Math.floor(minutes/60);
	seconds=seconds % 60;
	minutes=minutes % 60;
	if(minutes<10 && hours>0)	minutes="0"+minutes;
	if(seconds<10)				seconds="0"+seconds;
	var timeString="{0}:{1}".format(minutes,seconds);
	if(hours>0) 	timeString="{0}:{1}".format(hours,timeString);
	return timeString;
}

function formatSize(size)
{
	var KILO=1024;
	var units = ["B","K","M","G","T"];
	var power=0;
	while(size > KILO)
	{
		size /= KILO;
		power++;
	}
	var unit=units[power];
	if(power>0) unit += units[0];
	return Math.roundDigits(size,1) + " " + unit;
}

//convert to int or float or bool if possible
function parseValue(value)
{
	if(!isString(value)) return value;
	intValue = parseFloat(value);
	if(!isNaN(intValue)) return intValue;
	return value;
}

function parseBool(value)
{
	if(isBoolean(value)) return value;
	return value == "true" || value==1;
}

function pxToInt(value)
{
	if(!isString(value)) return value;
	return parseValue(value.replace("%","").replace("px",""));
}

function xor(a, b)
{
  return (a ? 1 : 0) ^ (b ? 1 : 0);
}

function not(funct)
{
	return function () { return !funct(); }
}

function modulo(i,max)
{
	return max ? (i + max) % max : i;
}

function tryCatchAlert(funct)
{
	try
	{
		if(isFunction(funct))
			funct();
	}
	catch(err)
	{
		alert(Object.toText(err,"\n"));
	}
}

Date.prototype.addDays = function (days)
{
	var d = new Date(this);
	d.setDate(d.getDate() + days);
	return d;
};
