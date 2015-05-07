//jQuery ELEMENTS EXTENSION FUNCTIONS
$.fn.tagName = function()
{
	return this.get(0).tagName.toLowerCase();
};

$.fn.classList = function()
{
	var classes=this.attr("class");
	if(classes && classes!="")
		return this.attr("class").split(/\s+/);
	return [];
};

$.fn.borderHeight = function()
{
	return this.outerHeight() - this.height();
};

$.fn.borderWidth = function()
{
	return this.outerWidth() - this.width();
};

$.fn.marginHeight = function()
{
	return this.outerHeight(true) - this.outerHeight();
};

$.fn.marginWidth = function()
{
	return this.outerWidth(true) - this.outerWidth();
};

$.fn.borderMarginHeight = function()
{
	return this.outerHeight(true) - this.height();
};

$.fn.borderMarginWidth = function()
{
	return this.outerWidth(true) - this.width();
};

//TODO add direction, effect attributes on element
$.fn.toggleEffect = function(state, duration, effect, direction, callback)
{
	effect = effect || this.attr("effect") || "slide";
	direction = direction || this.attr("direction") || "up";
	if(!callback && window.UI)	callback = UI[this.attr("callback")];
	if(!callback)	callback = eval(this.attr("callback"));

	if(effect=="slide" && ["up","down"].contains(direction))
	{
		if(state===true)	return this.slideDown(duration, callback);
		if(state===false)	return this.slideUp(duration, callback);
		return this.slideToggle(duration, callback);
	}

	var opts = { effect: effect, direction: direction, duration: duration, complete: callback};
	if(state===true)	return this.show(opts);
	if(state===false)	return this.hide(opts);
	return this.toggle(opts);
};

$.fn.backgroundImage = function(url)
{
	if(isMissing(url))
		return this.css("background-image");
	url = url.replace(/ /g, "%20");
	return this.css("background-image", "url(" + url + ")");
};

$.fn.outerHtml = function ()
{
    return $('<div/>').append(this.clone()).html();
};

$.fn.slideToggleLeft = function (duration)
{
	return this.toggle("slide", "left", duration);
};

//set or toggle checkbox + trigger change event
$.fn.toggleChecked = function(checked, noEvent)
{
	var state = this.prop("checked");
	if(isMissing(checked))	checked=!state;
	if(checked===state) return this;

	this.prop("checked", checked);
	if(!noEvent) this.change();	
	return this;
};

//get checkbox checked state
$.fn.isChecked = function(checked)
{
	return this.prop("checked");
};

$.fn.appendLine = function(line)
{
    return this.append(line).append("<br/>");
};

//toggle / cycle selected index
$.fn.selectNextOption = function(increment, noEvent) 
{	//toggle / cycle selected index
	if(!this.length || !this[0].options || this[0].options.length <=1) return;
	var nbOptions=this[0].options.length;
	if(!increment) increment=1;
	this[0].selectedIndex = (this[0].selectedIndex + increment + nbOptions) % nbOptions;
	if(!noEvent) this.change();	
	return this;
};

$.fn.selectOption = function(value, noEvent) 
{	//toggle / cycle selected index
	if(!this.length || !this[0].options || this[0].options.length <=1) return;
	this.val(value);
	if(!noEvent) this.change();	
	return this;
};

//set only 1 callback, reset previous bound callbacks
$.fn.bindReset = function(eventType, callback, skipEventArgs) 
{
	this.unbind(eventType);
	if(skipEventArgs)
		this.bind(eventType, function () { callback(); } );
	else
		this.bind(eventType, callback);
};

// get default css style for elements that do not exist on page
$.getStyle = function(selector, cssProperty)
{
	var attr = $.parseSelector(selector);
	var tag = attr.tag || "div";
	delete attr.tag;
	var testElement = $.makeElement(tag,attr);
	testElement.appendTo("body");
	var value = testElement.css(cssProperty);
	testElement.remove();
	return value;
}

//make "tag#id.class"
$.elementSelector = function(element)
{
	if(!element) return "";
	element = $(element);
	var result = element.tagName();
	if(element.attr("id"))
		result += "#" + element.attr("id");
	if(element.attr("class"))
		result +=  "." + element.attr("class");
		
	return result;
};

$.fn.selector = function()
{
	return $.elementSelector(this);
};

//parse "tag#id.class"
//div#divbg.bg noprint

//div
//divbg
//bg noprint

$.parseSelector = function(selector)
{
	result={};
	if(selector.containsText("."))
	{
		result["class"] = selector.substringAfter(".");
		selector = selector.substringBefore(".");
	}
	if(selector.containsText("#"))
	{
		result.id = selector.substringAfter("#");
		selector = selector.substringBefore("#");
	}
	result.tag = selector;
	return result;
};


$.makeElement = function(tag, attributes)
{
	return $("<"+tag+"/>",attributes);
};

$.isOldIE = function(version)
{
	version=valueOrDefault(version,8);
	if(navigator.userAgent.containsText("MSIE") || navigator.userAgent.containsText("Trident"))
		return document.documentMode <=version;
	return false;
};

jQuery.extend({
	random: function(x) {
	    return Math.floor(x * (Math.random() % 1));
	},
	randomBetween: function(minV, maxV) {
	  return minV + jQuery.random(maxV - minV + 1);
	}
});

$.fn.animateRotate = function(startAngle, endAngle, duration, easing, complete)
{
    return this.each(function()
    {
        var elem = $(this);
        $({deg: startAngle}).animate({deg: endAngle}, 
        {
            duration: duration,
            easing: easing,
            step: function(now)
            {
                elem.css({
                  '-moz-transform':'rotate('+now+'deg)',
                  '-webkit-transform':'rotate('+now+'deg)',
                  '-o-transform':'rotate('+now+'deg)',
                  '-ms-transform':'rotate('+now+'deg)',
                  'transform':'rotate('+now+'deg)'
                });
            },
            complete: complete || $.noop
        });
    });
};

//get link href or img urls
$.fn.getUrls = function (selector, attr, absolute)
{
	selector = valueOrDefault(selector, "img");
	attr = valueOrDefault(attr, "src");
	urls = []; 
	this.find(selector).each(function()
	{ 
		var src = absolute ? this[attr] : $(this).attr(attr);
		if(src)
			urls.push(src);
	});
	return urls;
};