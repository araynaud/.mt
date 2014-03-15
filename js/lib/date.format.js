/*
 * Date Format 1.2.3
 * (c) 2007-2009 Steven Levithan <stevenlevithan.com>
 * MIT license
 *
 * Includes enhancements by Scott Trenda <scott.trenda.net>
 * and Kris Kowal <cixar.com/~kris.kowal/>
 *
 * Accepts a date, a mask, or a date and a mask.
 * Returns a formatted version of the given date.
 * The date defaults to the current date/time.
 * The mask defaults to dateFormat.masks.default.
 */

// Some common format strings
var df = {};
df.language = "EN";
df.masks = {
	"default":      "ddd mmm dd yyyy HH:MM:ss",
	shortDate:      "m/d/yy",
	monthYear:     	"mmmm yyyy",
	monthDay:     	"mmmm d",
	monthDayTime:  	"mmmm d HH:MM",
	mediumDateTime: "mmm d, yyyy HH:MM",
	mediumDate:     "mmm d, yyyy",
	longDate:       "mmmm d, yyyy",
	fullDate:       "dddd, mmmm d, yyyy",
	shortTime:      "h:MM TT",
	mediumTime:     "h:MM:ss TT",
	longTime:       "h:MM:ss TT Z",
	isoDate:        "yyyy-mm-dd",
	isoTime:        "HH:MM:ss",
	isoDateTime:    "yyyy-mm-dd'T'HH:MM:ss",
	isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"
};

df.strings = {
	EN: {
		shortDayNames: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
		dayNames: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
		shortMonthNames: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
		monthNames: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ],
		masks: {
			"default":      "ddd mmm dd yyyy HH:MM:ss",
			shortDate:      "m/d/yy",
			monthDay:     	"mmmm d",
			monthDayTime:  	"mmmm d HH:MM",
			mediumDateTime: "mmm d, yyyy HH:MM",
			mediumDate:     "mmm d, yyyy",
			longDate:       "mmmm d, yyyy",
			fullDate:       "dddd, mmmm d, yyyy"
		},
		sinceFormat: "{0} ago"
	},
	FR: {
		shortDayNames: ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"],
		dayNames: ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"],
		shortMonthNames: ["Jan", "Fév", "Mar", "Avr", "Mai", "Jun", "Jul", "Août", "Sep", "Oct", "Nov", "Déc"],
		monthNames:	["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"],
		masks: {
			"default":      "ddd mmm dd yyyy HH:MM:ss",
			shortDate:      "d/m/yy",
			monthYear:     	"mmmm yyyy",
			monthDay:     	"d mmmm",
			monthDayTime:  	"d mmmm HH:MM",
			mediumDateTime: "d mmm yyyy HH:MM",
			mediumDate:     "d mmm yyyy",
			longDate:       "d mmmm yyyy",
			fullDate:       "dddd d mmmm yyyy"
		},
		units: {
			second: "seconde",
			minute: "minute",
			hour: "heure",
			day: "jour",
			month: "mois",
			year: "an"
		},
		sinceFormat: "il y a {0}"
	}
};

// Internationalization strings

df.getMasks = function()
{
	if(window.config && config.language && df.strings[config.language])
		df.language=config.language;
	df.i18n = df.strings[df.language];
	Object.merge(df.masks, df.i18n.masks, true);
	return df;
};

var dateFormat = function (date, mask, utc) 
{
	var	token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g;
	var	timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g;
	var	timezoneClip = /[^-+\dA-Z]/g;
	var	pad = function (val, len)
	{
		val = String(val);
		len = len || 2;
		while (val.length < len) val = "0" + val;
		return val;
	};

	// You can't provide utc if you skip other args (use the "UTC:" mask prefix)
	if (arguments.length == 1 && Object.prototype.toString.call(date) == "[object String]" && !/\d/.test(date))
	{
		mask = date;
		date = undefined;
	}

	// Passing date through Date applies Date.parse, if necessary
	if(!date)
		date = new Date();

	if(typeof(date)=='string')
		date = Date.fromString(date);
	if (!date || isNaN(date))
		return false; // throw SyntaxError("invalid date");

	df.getMasks();
	mask = String(df.masks[mask] || mask || df.masks["default"]);

	// Allow setting the utc argument via the mask
	if (mask.slice(0, 4) == "UTC:")
	{
		mask = mask.slice(4);
		utc = true;
	}

	var	_ = utc ? "getUTC" : "get",
		d = date[_ + "Date"](),
		D = date[_ + "Day"](),
		m = date[_ + "Month"](),
		y = date[_ + "FullYear"](),
		H = date[_ + "Hours"](),
		M = date[_ + "Minutes"](),
		s = date[_ + "Seconds"](),
		L = date[_ + "Milliseconds"](),
		o = utc ? 0 : date.getTimezoneOffset();

	var	flags = {
		d:    d,
		dd:   pad(d),
		ddd:  df.i18n.shortMonthNames[D],
		dddd: df.i18n.dayNames[D],
		m:    m + 1,
		mm:   pad(m + 1),
		mmm:  df.i18n.shortMonthNames[m],
		mmmm: df.i18n.monthNames[m],
		yy:   String(y).slice(2),
		yyyy: y,
		h:    H % 12 || 12,
		hh:   pad(H % 12 || 12),
		H:    H,
		HH:   pad(H),
		M:    M,
		MM:   pad(M),
		s:    s,
		ss:   pad(s),
		l:    pad(L, 3),
		L:    pad(L > 99 ? Math.round(L / 10) : L),
		t:    H < 12 ? "a"  : "p",
		tt:   H < 12 ? "am" : "pm",
		T:    H < 12 ? "A"  : "P",
		TT:   H < 12 ? "AM" : "PM",
		Z:    utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
		o:    (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
		S:    ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
	};

	return mask.replace(token, function ($0)
	{
		return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
	});
};

// For convenience...
Date.prototype.format = function (mask, utc) {
	return dateFormat(this, mask, utc);
};



//============= date range functions

//handle "2013-05-09 12:23:43" => "2013-05-09T12:23:43"
Date.fromString = function(dateStr, dateOnly)
{
	if(Object.isInstanceOf(dateStr, "Date"))		return dateStr;
	if(!dateStr || typeof(dateStr)!='string')		return Date();
	if(dateOnly)
		dateStr=dateStr.substringBefore(" ");

	var date = new Date(dateStr);
	if (isNaN(date))
	{
		dateStr=dateStr.replace(" ", "T");
		date = new Date(dateStr);
	}
	if (isNaN(date)) return false;
	return date;
};

//timespan between 2 dates
Date.units={
	second: 1000,
	minute: 60,
	hour: 60,
	day: 24,
	month: 30.5,
	year: 12
};

Date.diffUnits = function(from, to)
{
	var duration = Math.abs(to - from);
	var unit;
	var factor=1;
	for(var u in Date.units)
	{
		factor *= Date.units[u];
		if(duration < factor)
		{
			factor /= Date.units[u];
			break;
		} 
		unit = u;
	}
	duration = Math.round(duration / factor);
	//TODO: get sub units recursively with (duration % factor)
	return  { from: from, to: to, duration: duration, unit: unit };
};

//from date. if not passed, interval betoween this date and now
Date.prototype.diffUnits = function(from)
{
	if(!from) from = new Date();
	return Date.diffUnits(from, this);
};

Date.dateRange = function (from, to, dateOnly)
{

	if(!to && !from) return false;
	dFrom = Date.fromString(from, dateOnly);
	dTo = Date.fromString(to, dateOnly);
	var dateRange = {};

	if(!dFrom)
		dateRange.dFrom = dTo;
	else if(!dTo) 
		dateRange.dFrom = dFrom;
	else //if(dFrom && dTo && dFrom!=dTo)
	{
		dateRange = Date.diffUnits(dFrom, dTo);
		dateRange.dFrom = dFrom;
		dateRange.dTo = dTo;
	}

	return dateRange;
};

//TODO:
//if same year: March 17 - April 30, 2013
//if same month: March 17 - 30, 2013
Date.formatDateRange = function(from, to, dateOnly)
{
	var dateRange= Date.dateRange(from, to, dateOnly);
	var from;
	if(dateRange.dFrom)
		from=dateFormat(dateRange.dFrom, "longDate", dateOnly);
	if(!dateRange.duration)
		return from;
	var to=dateFormat(dateRange.dTo, "longDate", dateOnly);

	if(to == from) return to;

	return "{0} - {1} ({2})".format(from, to, Date.formatDateDiff(dateRange));
};

Date.formatDateDiff = function(dateRange)
{
	if(!df.i18n) df.getMasks();
	var unit = df.i18n.units ? df.i18n.units[dateRange.unit] : dateRange.unit;
	return plural(dateRange.duration, unit);
};

Date.timeSince = function(since)
{
	dSince=Date.fromString(since);
	if(!dSince.diffUnits) return dSince;
	return dSince.diffUnits();
};

Date.formatTimeSince = function(since, dateOnly)
{
	if(!df.i18n) df.getMasks();
	var diff = Date.formatDateDiff(Date.timeSince(since));
	var fmt = df.i18n.sinceFormat;
	return fmt ? fmt.format(diff) : diff;	
};

