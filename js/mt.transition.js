function Transition(options)
{
	this.elIndex = 0;
	this.type = 1;
	this.clear = false;
	this.maxType = 0;
	this.duration=1000;
	this.elementSelector = null;
	this.container = null;
	this.clear=true;
	this.changeMode=false;

	this.setOptions(options);
}

//instance methods to set options
Transition.prototype.setOptions = function(options)
{
	if(isObject(options))
		Object.merge(this,options);

	if(isString(this.elementSelector) && !isEmpty(this.container))
		this.elements =  this.container.find(this.elementSelector);
	else if(this.elementSelector)
		this.elements =  $(this.elementSelector);

	this.swap();
	this.alt.hide();
	this.inProgress = false;

	Transition.initFunctions();
	this.functions= this.maxType<=1 ? Transition.functions : Transition.functions.slice(0, this.maxType+1);
	this.maxType=this.functions.length - 1;
};

Transition.labels = 
{	none: "none",	random: "Random",	crossFade: "Cross fade",	
	slideHorizontal: "Horizontal slide",	slideVertical: "Vertical slide",	slideCorner: "Slide & zoom top",	
	scale: "Scale",	explode: "Explode" 
};

Transition.initFunctions = function ()
{
	if(!isEmpty(Transition.types)) return;
	Transition.types = [];
	Transition.functions = [];
	var i = 0;
	for (key in Transition.labels)
	{
		Transition.functions[i]=key;
		Transition.types[key]=i++;
	}
};

Transition.prototype.swap = function(doSwap)
{
	doSwap=valueOrDefault(doSwap,true);
	if(isEmpty(this.elements))
		this.elements =  $(this.elementSelector);
	if(doSwap)
		this.elIndex = 1 - this.elIndex;
	this.current = this.elements.eq(this.elIndex);
	this.alt = this.elements.eq(1 - this.elIndex);
	return this.elIndex;
};

Transition.prototype.getCurrentSlide = function()
{
	if(isEmpty(this.current))	this.swap(false);
	return this.current;
};

Transition.prototype.getNextSlide = function()
{
	if(isEmpty(this.alt))	this.swap(false);
	return this.alt;
};

//hide both slides, with or without transition
Transition.prototype.hideElements = function()
{
	if(this.changeMode)
		this.elements.fadeOut(UI.transition.duration / 2);
	else
		this.elements.hide(); 
};

Transition.prototype.showCurrent = function(opts,show)
{
	show=valueOrDefault(show,true);
	if(!show || isEmpty(this.current))  return;
	this.current.show(opts);
	if(opts) delete opts.complete;
};

Transition.prototype.hideAlt = function(opts,hide)
{
	hide=valueOrDefault(hide,true);	
	if(hide && !isEmpty(this.alt) && this.alt.is(":visible"))
		this.alt.hide(opts);
};

//call one of the transition functions
Transition.prototype.execute = function(key,show,hide)
{
	//if(this.inProgress) return false;
	show=valueOrDefault(show,true);
	hide=valueOrDefault(hide,true);	

	this.inProgress = true;
	this.swap();
	var transitionFunction=this.getFunctionName(key);

	return this[transitionFunction](show, hide);
};

//common transition on end
Transition.prototype.end = function()
{
	if(this.onComplete)	this.onComplete(); //optional function passed in constructor
	if(this.clear)	this.alt.html("");  
	this.inProgress = false;
	//UI.setStatus("transition.end");
};

Transition.prototype.next = function(inc)
{
	inc = valueOrDefault(inc,1);
	this.type = modulo(this.type+inc, this.functions.length);
	return this.type;
};

Transition.prototype.previous = function(inc)
{
	inc = valueOrDefault(inc,1);
	this.type = modulo(this.type-inc, this.functions.length);
	return this.type;
};

Transition.prototype.setType = function(key)
{
	if(isString(key))
		key = Transition.types[key]; 
	if(key)
		this.type = modulo(key, this.functions.length);
	return this.type;
};

Transition.prototype.getFunctionName = function(key)
{
	if(isString(key))
		return key;
	if(isNumber(key))
		key = modulo(key, this.functions.length);
	else
		key = this.type;
	return this.functions[key];
};

Transition.prototype.getFunction = function(key)
{
	var functionName = this.getFunctionName(key);
	return this[functionName];
};

Transition.prototype.getLabel = function(key)
{
	var functionName = this.getFunctionName(key);
	return Transition.labels[functionName];
};

// ============ Jquery effect functions ===========
Transition.prototype.none = function(show,hide)
{
	this.showCurrent(null,show);
	this.hideAlt(null,hide);
	this.end();
};

Transition.prototype.baseTransition = function(show, hide, opts, hideOpts)
{
	var trans=this;
	var end = function(){ trans.end(); };
	var defaultOpts = {duration: this.duration, complete: end }; // easing: "easeInOutCirc"};
	opts = Object.merge(defaultOpts, opts, true);

	if(show) this.showCurrent(opts);
	if(!hide) return;
	Object.merge(opts, hideOpts, true);
	this.hideAlt(opts);
};

Transition.prototype.crossFade = function(show,hide)
{
	var opts = { effect: "fade" }; // duration: this.duration, complete: end};
	this.baseTransition(show, hide, opts);
};

Transition.prototype.slideCorner = function(show,hide)
{
//	var opts={duration: this.duration}; //, complete: end};
	this.baseTransition(show, hide);
};

Transition.prototype.slideVertical = function(show,hide)
{
	this.slideHorizontal(show,hide,["up","down"]);
};

Transition.prototype.slideHorizontal = function(show,hide,directions)
{
	directions = valueOrDefault(directions,["left","right"]);
	var di= this.increment==1 ? 1 : 0;
	var opts = { effect: "slide", direction: directions[di] }; // duration: this.duration, complete: end};
	var hideOpts = { direction: directions[1-di]};
	this.baseTransition(show, hide, opts, hideOpts);
};

Transition.prototype.explode = function(show,hide)
{
	var opts = { effect: "scale" };
	var hideOpts = { effect: "explode" };

	if(hide && !isEmpty(this.alt) && this.alt.is(":visible"))
	{	
		this.alt.css("top",this.alt.css("margin-top"));
		this.alt.css("left",this.alt.css("margin-left"));
		this.alt.css("margin",0);
	}
	this.baseTransition(show, hide, opts, hideOpts);
};

Transition.prototype.scale = function(show,hide)
{
	var opts = { effect: "scale" };
	this.baseTransition(show, hide, opts);
};

//always put random in last in function array
Transition.prototype.random = function(show,hide)
{
	//call one of other transitions
	//prevents getting last element to avoid infinite recursive call
	var rndType = 2 + Math.floor(Math.random()*(this.functions.length-2));
	//UI.setStatus(this.getLabel(rndType));
	var transitionFunction=this.getFunctionName(rndType);
	return this[transitionFunction](show,hide);
};
