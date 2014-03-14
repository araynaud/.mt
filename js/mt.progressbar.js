
/*
Upload progress bar:
mt.progress.js
setMax
setProgress
hide, show.
*/

// constructor for player instance
function ProgressBar(options)
{
	this.max = 100;
	this.progress = 0;
	this.container = ".progressBar";
	this.displayValue = "duration";
	this.displayMax=false;
	this.displayFunction=null;
	Object.merge(this, options, false, true);
	this.container=$(this.container);
	this.progressDiv = this.container.find(".progress");
	this.progressLabel = this.container.find(".progressValue");
	this.remainingLabel = this.container.find(".remainingValue");
	this.setProgress();
	this.startTime = new Date();
};

ProgressBar.prototype.setMax = function(value)
{
	this.max=value;
};

ProgressBar.prototype.hide = function(args)
{
	this.container.hide(args);
};

ProgressBar.prototype.show = function(args)
{
	this.container.show(args);
};

ProgressBar.prototype.toggle = function(args)
{
	this.container.toggleEffect(args);
};


ProgressBar.prototype.reset = function(value)
{
	this.startTime = new Date();
	this.setProgress();
};

ProgressBar.prototype.elapsedTime = function(format)
{
	this.duration = new Date() - this.startTime;
	return format ? ProgressBar.formatSeconds(this.duration) : this.duration;
};

ProgressBar.formatSeconds = function(value,digits)
{
	digits = valueOrDefault(digits,2);
	return Math.roundDigits((value) / 1000, digits) + "s";
};

ProgressBar.prototype.totalTime = function(format)
{
	var elapsedTime = this.elapsedTime();
	if(!this.progress) return 0;
	duration = elapsedTime * this.max / this.progress;
	return format ? ProgressBar.formatSeconds(duration) : duration;
};

ProgressBar.prototype.remainingValue = function(format)
{
	return this.max - this.progress;
};

ProgressBar.prototype.remainingTime = function(format)
{
	var elapsedTime = this.elapsedTime();
	if(!this.progress) return 0;
	duration =  elapsedTime * this.remainingValue() / this.progress;
	return format ? ProgressBar.formatSeconds(duration) : duration;
};

ProgressBar.prototype.setProgress = function(value)
{
	if(!value) value=0;

	this.progress=value;
	if(this.isMax()) 
		this.progress=this.max;
	this.refresh();
};

ProgressBar.prototype.refresh = function()
{
	this.percent = Math.round(100 * this.progress / this.max) + "%";
	this.progressDiv.width(this.percent);
	var displayValue = this[this.displayValue];
	if(this.displayValue != "percent" && isFunction(this.displayFunction)) 
		displayValue = this.displayFunction(displayValue);
	this.progressLabel.html(displayValue);
	this.remainingLabel.html("{0} remaining / {1} total.".format(this.remainingTime(true), this.totalTime(true)));
	if(this.displayMax)
	{
		displayValue=this.max;
		if(isFunction(this.displayFunction)) 
			displayValue = this.displayFunction(this.max);
		this.progressLabel.append(" / " + displayValue);
	}
	return this.progress;
};

ProgressBar.prototype.addProgress = function(value)
{
	return this.setProgress(this.progress+value);
};

ProgressBar.prototype.isMax = function()
{
	return this.progress >= this.max;
};