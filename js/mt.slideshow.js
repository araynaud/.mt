function Slideshow(options)
{
	this.allowFacebook=false;
	this.currentFile=null;
	// Duration of image (in milliseconds)
	this.interval = 4000;
	// Duration of transitions (in milliseconds)
	this.duration = 1000;
	this.type=1;

	this.currentIndex = 0;
	//add pics URLs in array
	this.pics = [];
	this.increment=1;
	this.start=0;
	this.animate=false;
	this.animStartZoom=1;
	this.animEndZoom=2;
	this.controls=true;
	this.zoom=1;
	this.play=false;
	this.autoPlayAudio=false;
	this.timer=null;
	this.tnIndex=1;
	this.depth=0;
	this.preLoadedImage=null;
	this.alignX = "center"; //"left", "right"
	this.alignY = "center"; //"top", "bottom"
	this.changeMode=true;
	this.zoomFunction="none";
	this.elements = {
		slides: "img.slide",
		statusBar: ".status",
		speed: "#speed",
		description: "#slideshowCaption",
		tags: "#slideshowTags",
		imageText: "#imageText",
		imageLink: "#imageLink",
		indexButton: "img#indexButtonBig",
		prevButton: "img#prevButtonBig",
		nextButton: "img#nextButtonBig",
		playButton: "img#playButtonBig",
		comments: "#fbComments"
	};
	this.elementSelector = this.elements.slides;
	this.transition = new Transition(this);
	this.setOptions(options);
	this.angle=0;

	//make references		
	Slideshow.instances = Slideshow.instances || [];
	if(this.id)
		Slideshow.instances[this.id]=this;
	else
		Slideshow.instances.push(this);
}

Slideshow.zoom = { labels: {none: "none", long: "fit", short: "cover" }}; // width: "fit width",  height: "fit height"} };

Slideshow.initZoomTypes = function ()
{
	if(isEmpty(Slideshow.zoom.types))
		Slideshow.zoom.types = Object.keys(Slideshow.zoom.labels);
};

//instance methods to set options
Slideshow.prototype.setOptions = function(options)
{
	Slideshow.initZoomTypes();

	if(isObject(options))
		Object.merge(this,options);

	this.setupElements();
	this.setInterval();
	this.setContainer();
	this.transition.setOptions(this);
	this.setStart(this.start);

	if(window.MediaPlayer)
		this.setMediaPlayer(MediaPlayer.getInstance("slide"));

	var sl = this;
	if(!isEmpty(this.transition.elements))
		this.transition.elements.bindReset("click", function(e) {sl.slideOnClick(e, $(this));} );

	//if(!UI.clientIs("mobile"))
	//this.transition.elements.bindReset("mousemove", Slideshow.slideOnHover);
};

Slideshow.prototype.setupElements = function()
{
	if(!this.elements) return;
	for (var el in this.elements)
		this.elements[el] = $(this.elements[el]);
	return this.elements;
}

Slideshow.prototype.setMediaPlayer = function(mediaPlayer)
{
	this.mplayer = mediaPlayer;
	mediaPlayer.slideshow = this;
};

Slideshow.prototype.slideOnClick = function(e, img)
{
	//return UI.slideshow.toggleZoom();
	var coord = Slideshow.getClickCoord(e, img);
//	UI.slideshow.setStatus(coord);
	if(coord.rx < .25 && coord.ry < .25 && window.UI)
		UI.setMode();
	else if(coord.rx < .25)
		this.showNextImage(-1);
	else if(coord.rx > .75 && coord.ry < .25)
		this.togglePlay();
	else if(coord.rx > .75)
		this.showNextImage(1);
	else
		this.toggleZoom();
};

Slideshow.slideOnHover = function(e)
{
	var img = $(this);
	var coord = Slideshow.getClickCoord(e, img);
//	UI.slideshow.setStatus(coord);
	
	//display icons conditionally
	this.elements.indexButton.toggle(coord.rx < .25 && coord.ry < .25);
	this.elements.prevButton.toggle(coord.rx < .25 && coord.ry >= .25);
	this.elements.playButton.toggle(coord.rx > .75 && coord.ry < .25);
	this.elements.nextButton.toggle(coord.rx > .75 && coord.ry >= .25);
};

Slideshow.getClickCoord = function(e,img)
{
	var off=img.offset();
	var cx = e.clientX - Math.round(off.left);
	var cy = e.clientY - Math.round(off.top);
	return { x: cx, y: cy, rx : Math.roundDigits(cx / img.outerWidth(), 2), ry : Math.roundDigits(cy / img.outerHeight(), 2) };
};

Slideshow.prototype.setContainer = function(container)
{
	if(container)
		this.elements.container = $(container);
	if(isEmpty(this.elements.container)) 
		this.elements.container = $(window);
	return this.elements.container;
};

Slideshow.prototype.setDepth = function(depth)
{
	this.depth=depth;
};

Slideshow.prototype.displayInterval = function()
{
	this.elements.speed.html(this.interval/1000 + "s");
	this.setStatus("total: " + formatTime(this.totalDuration()));
};

Slideshow.prototype.setInterval = function(interval)
{	
	if(interval)
	{
		this.interval=parseValue(interval);
		this.transition.duration=this.interval/4;
	}
	this.displayInterval();
};

Slideshow.prototype.setSpeed = function(factor)
{	
	factor=parseValue(factor);
	this.interval*=Math.pow(2,factor);
	this.transition.duration=this.interval/4;
	this.displayInterval();
};

Slideshow.prototype.faster = function()
{
	this.setSpeed(-1);
};	

Slideshow.prototype.slower = function()
{
	this.setSpeed(+1);
};	

Slideshow.prototype.setPics = function(mediaFiles)
{
	this.pics = mediaFiles;
};

//accept filenames or int: if start is a string, find its position in this.pics array
Slideshow.prototype.setStart = function(start)
{
	this.currentIndex = this.getPicPosition(start);
	return this.currentIndex;
};

Slideshow.prototype.getPicPosition = function(input)
{
	if(isEmpty(this.pics.length))
		return 0;
	if(isMissing(input) && this.currentIndex > this.pics.length)
		return 0;
	if(isMissing(input))
		return this.currentIndex;
//object: search by name
	if(isObject(input))
		input = input.id || input.name  || input.filename;
//string: search by name
	if(isString(input))
	{
		var i=this.pics.getElementPosition(input,["id","filename","name"]);
		return i>=0 ? i : 0;
	}
//number
	return this.validIndex(input);
};

Slideshow.prototype.remove = function(file)
{
	file=valueOrDefault(file, this.currentIndex);
	var position=this.getPicPosition(file);
	this.pics.splice(position,1);
	if(position==this.pics.length) 
		this.currentIndex--;
};

Slideshow.prototype.display = function(start)
{
	this.setStart(start);
	if(isEmpty(this.pics)) return;
	if(!this.elements.container) this.setContainer();

	if(window.UI && UI.setMode)
		UI.setMode("slideshow");
	this.showImage();
	return this;
};

Slideshow.prototype.hideImage = function()
{
	this.transition.execute(this.changeMode ? "crossFade" : "none", false);
};

Slideshow.prototype.togglePlay = function(playState)
{
	this.play=valueOrDefault(playState,!this.play);
	this.setStatus(this.play ? "playing" : "paused");

	var icon = this.play ? "pause.png" : "play.png";
	icon = String.combine(Album.serviceUrl ,"icons", "media-" + icon);
	this.elements.playButton.attr("src", icon);

	if(this.currentFile.isVideoStream())
	{
		this.mplayer.togglePlay(this.play);
		this.togglePlayAudio(!this.play);
	}
	else if(this.play)
	{
		this.togglePlayAudio(true);
		this.setInterval();
		this.showNextImage();
	}
	else
		clearTimeout(this.timer);
};

Slideshow.prototype.togglePlayAudio = function(state)
{	
	if(this.autoPlayAudio && window.MediaPlayer && MediaPlayer.audio)
		MediaPlayer.audio.togglePlay(state);
}

Slideshow.prototype.nextTransition = function()
{
	this.type = this.transition.next();
	this.setStatus("transition: " + this.transition.getLabel());
};

Slideshow.prototype.toggleOption = function()
{
	if(this.currentFile.isImage())
		this.showImage(null,"crossFade");
	else
	{
		this.styleSlide();
		this.fitImage();
	}
};

Slideshow.prototype.toggleZoom = function()
{
	this.animStartZoom = modulo(this.animStartZoom+1, Slideshow.zoom.types.length);
	this.animEndZoom = modulo(this.animEndZoom+1, Slideshow.zoom.types.length);
	this.zoom = modulo(this.zoom+1, Slideshow.zoom.types.length);
	if(this.animate)
		this.setStatus("zoom: {0} / {1}".format(
			Slideshow.zoom.labels[Slideshow.zoom.types[this.animStartZoom]],
			Slideshow.zoom.labels[Slideshow.zoom.types[this.animEndZoom]]));
	else
		this.setStatus("zoom: " + Slideshow.zoom.labels[Slideshow.zoom.types[this.zoom]]);

	this.fitImage(true);
};

Slideshow.prototype.toggleAnimate = function(state)
{
	this.animate = valueOrDefault(state, !this.animate);
	this.setStatus("animate: " + this.animate);
};

Slideshow.prototype.toggleControls = function()
{
	this.controls = !this.controls;
	$("#slideshowContainer .controls").toggle(this.controls);
	$('#fbComments').fadeToggle(this.controls);
};

Slideshow.prototype.setImageLink = function(href, text)
{
	this.elements.imageLink.html(text).attr("href",href);
};

Slideshow.prototype.validIndex = function(i)
{
	return modulo(i, this.pics.length);
};

Slideshow.prototype.previousIndex = function(incr)
{
	incr = valueOrDefault(incr, this.increment);
	return this.validIndex(this.currentIndex - incr);
};

Slideshow.prototype.nextIndex = function(incr)
{
	incr = valueOrDefault(incr, this.increment);
	return this.validIndex(this.currentIndex + incr);
};

Slideshow.prototype.previousFile = function(incr)
{
	return this.pics[this.previousIndex(incr)];
};

Slideshow.prototype.nextFile = function(incr)
{
	return this.pics[this.nextIndex(incr)];
};

Slideshow.prototype.loadImage = function(index)
{	
	index=valueOrDefault(index,this.currentIndex);
		
	if(!this.pics[index].image)
	{
		this.pics[index].image = new Image();
		this.pics[index].image.src = this.getPicUrl(index);
	}
	return this.pics[index].image;
};

//if image.php : leave as is
//else : append path + file
Slideshow.prototype.getPicUrl = function(index)
{
	index=valueOrDefault(index,this.currentIndex);
	var pic=this.pics[index];

//if image smaller than slideshow or animated, use original
	if(pic.isImage() && (pic.animated || !pic.tnsizes || this.tnIndex >= pic.tnsizes.length))
		return pic.getFileUrl();

//otherwise get or generate thumbnail

	//wait for image to be generated, then return newly generated static image url
	var tnIndex = this.tnIndex;
	if(pic.tnsizes)
	{
		tnIndex = Math.min(this.tnIndex, pic.tnsizes.length - 1);
		if(pic.needsThumbnail(tnIndex))
		{
			this.setStatus("loading...");
			var url = pic.getThumbnailUrlAjax(tnIndex);
			this.setStatus();
			return url;
		}
	}
	return pic.getThumbnailUrl(tnIndex);
};

Slideshow.prototype.getMediaFile = function(index)
{
	if(isEmpty(this.pics))	return null;
	index=valueOrDefault(validIndex(index), this.currentIndex);
	return this.pics[index];
};

Slideshow.prototype.getPicFilename = function(index)
{
	index=valueOrDefault(validIndex(index), this.currentIndex);
	return this.pics[index].filename;
};

//show image #currentIndex
//use 2 images alternatively: slide0 , slide1
//change alt img that is invisible, then fade

Slideshow.prototype.getCurrentSlide = function(next)
{
	if(this.mplayer && this.currentFile.isVideoStream())
		return this.mplayer.getPlayerElement();
	if(next || !this.currentImg)
		this.currentImg = this.transition.getNextSlide();
	return this.currentImg;
};

Slideshow.prototype.styleSlide = function(next)
{
	el = this.getCurrentSlide(next);
//	this.setStatus("currentSlide: " + el.attr("id"));
	el.toggleClass("margin", album.margin);
	el.toggleClass("shadow", album.shadow &&  !this.currentFile.isTransparent());
	el.toggleClass("photoBorder", album.border && !this.currentFile.isTransparent());

	this.angle = album.rotate ? $.randomBetween(-10, 10) : 0;

	if(window.UI && UI.divStyles)
		el.attr("style", UI.divStyles(this.angle));
};

Slideshow.prototype.showImage = function(index, transitionFunction)
{	
	if(isEmpty(this.pics)) return;
	this.setStart(index);
	this.setStatus();

	var fileChange = (this.pics[this.currentIndex] != this.currentFile);
	this.currentFile = this.pics[this.currentIndex];

	this.preLoadedImage = this.loadImage();

	if(this.preLoadedImage.complete || this.currentFile.isVideoStream())
		this.displayLoadedImage(transitionFunction, fileChange);
	else
	{
		var ss=this;
		$(this.preLoadedImage).load(function() { ss.displayLoadedImage(transitionFunction, fileChange); });
	}

	if(window.UI && UI.displayEdit)
		UI.displayEdit("#slideshowControls");
};

Slideshow.prototype.displayLoadedImage = function(transitionFunction, fileChange)
{
	//this.elements.prevButton.parent().backgroundImage(this.previousFile(1).getThumbnailUrl(0));
	//this.elements.nextButton.parent().backgroundImage(this.nextFile(1).getThumbnailUrl(0));

	if(this.currentFile.isVideoStream() && fileChange)
	{
		this.hideImage();
		this.transition.inProgress=false;

		if(this.currentFile.stream=="youtube")
			this.togglePlayAudio(!this.play);

		if(this.mplayer)
		{
			this.mplayer.loadMediaFile(this.currentFile);
			this.styleSlide();
			this.fitImage();

			var opts = {duration: this.transition.duration};
			if(this.play)
			{
				var sl=this;
				opts.complete = function() { sl.mplayer.play(); }
			}
			this.mplayer.show(opts);
		}
	}
	else if(this.currentFile.isImage())
	{
		if(this.mplayer)
		{
			var pl= this.play;
			this.mplayer.pause();
			this.play = pl;
			this.mplayer.hide(this.transition.duration);
		}

		this.styleSlide(true);
		if(this.animate && this.play && this.animEndZoom != this.zoom)
		{
			this.fitImage(false, this.animStartZoom);
			var sl=this;
			setTimeout(function() { sl.animateImage(); }, this.transition.duration);
		}
		else
			this.fitImage();
		this.transition.execute(transitionFunction);

		if(this.play)
		{
			this.togglePlayAudio(this.play);
			this.autoShowNextImage();
		}
	}	

//	this.addStatus("fileChange:" + fileChange);
	this.elements.imageText.html("({0}/{1})".format(this.currentIndex + 1, this.pics.length));
	this.setImageLink(this.currentFile.getFileUrl(), this.currentFile.title || this.currentFile.name);
	this.showComments(this.currentFile);
};


//TODO: animate from zoom start to zoom end
//add this.startZoom
//end: to this.zoom
//animate for how long: interval, interval/2, transition.duration
Slideshow.prototype.animateImage = function()
{
	this.fitImage(true, this.animEndZoom);
	if(album.rotate && this.angle)
		this.currentImg.animateRotate(this.angle, 0, this.interval / 2);
};

Slideshow.prototype.showNextImage = function(increment)
{
	if(this.pics.length<=1) return;
	if(this.transition.inProgress)
		return;

	increment = parseValue(increment);
	if(!isNaN(increment))
		this.increment=increment;
//for next image
	this.transition.increment=this.increment;
	this.showImage(this.nextIndex());
};

Slideshow.prototype.autoShowNextImage = function()
{
	clearTimeout(this.timer);
	ss=this;
	this.timer = setTimeout(function(){ ss.showNextImage(this.increment); } , this.interval);
};

Slideshow.prototype.showComments = function(mediaFile)
{
	this.elements.description.html(mediaFile.description||"");
	if(window.UI && UI.renderTemplate)
		UI.renderTemplate("tagTemplate", this.elements.tags, Object.values(mediaFile.tags), null, {action: "removetag"});
};

// ---- IMAGE display functions -------

//use slideshowObj.js / fitImage in container
//fit long, short side, stretch
//move small icon thumbnails above caption

Slideshow.prototype.fitImage = function (animate, zoomLevel) 
{
	zoomLevel = valueOrDefault(zoomLevel, this.zoom);
	var image = this.getCurrentSlide();
	if (!this.preLoadedImage || !image) return;

	if(this.currentFile.isImage() && image.attr("src") !== this.preLoadedImage.src)
		image.attr("src", this.preLoadedImage.src);

	var size = this.getImageSize(zoomLevel);
	if(animate)
		image.animate(size, this.interval / 2);
	else
		image.css(size);
};

Slideshow.prototype.getImageSize = function (zoomLevel)
{
	zoomLevel = valueOrDefault(zoomLevel, this.zoom);

	var ibm = this.getImageBorderMargins();
	var size = { height: this.preLoadedImage.height, width: this.preLoadedImage.width };
	if(this.currentFile.isVideoStream() && this.currentFile.height && this.currentFile.width)
		size = { height: this.currentFile.height, width: this.currentFile.width};
	var preRatio = this.currentFile.getRatio() || size.height && size.width ? (size.width / size.height) : 1.5;
	var wRatio = (this.elements.container.width() - ibm.bmw) / (this.elements.container.height() - ibm.bmh);

	if(zoomLevel)
	{
		var zoomType = Slideshow.zoom.types[zoomLevel];
		var fitWidth = (zoomType == "width") || (preRatio > wRatio && zoomType=="long")  || (preRatio <= wRatio && zoomType=="short");
		if (fitWidth) //fit width
		{
			size.width = this.elements.container.width() - ibm.bmw;
			size.height = size.width / preRatio;
		}
		else //or fit height;
		{
			size.height = this.elements.container.height() - ibm.bmh;
			size.width = size.height * preRatio;
		}
	}
	this.getImageMargins(ibm, size);
	return size;
};

Slideshow.prototype.getImageBorderMargins = function () 
{
	var ibm = {};
	ibm.bw = album.border ? 20 : 0; //image.borderWidth();
	ibm.mw = album.margin ? 40 : 0; //image.marginWidth();
	ibm.bmw = ibm.bw + ibm.mw;

	ibm.bh = album.border ? 20 : 0; //image.borderHeight();
	ibm.mh = album.margin ? 40 : 0; //image.marginHeight();
	ibm.bmh = ibm.bh + ibm.mh;

	return ibm;
};

Slideshow.prototype.getImageMargins = function(ibm, position)
{
	position = valueOrDefault(position,{});

	var mx = ibm.mw/2;
	var my = ibm.mh/2;	
	var outerWidth  = ibm.bmw + position.width;
	var outerHeight = ibm.bmh + position.height;

	var x = this.elements.container.width() - outerWidth;
	if (this.alignX == "center") x/=2;

	var y = this.elements.container.height() - outerHeight;
	if (this.alignY == "center") y/=2;

	position.marginLeft   = this.alignX == "left"   ? mx : x + mx;
	position.marginRight  = this.alignX == "right"  ? mx : x + mx;
	position.marginTop    = this.alignY == "top"    ? my : y + my;
	position.marginBottom = this.alignY == "bottom" ? my : y + my;
	
	return position;
};

Slideshow.prototype.toFullScreen = function(image,container)
{
	image=$(image);

	container=this.setContainer(container);
	
	var	iHeight = image.height();
	var	iWidth  = image.width(); 
	var iRatio = iWidth / iHeight;
	
	var	pHeight = container.height();
	var	pWidth = container.width(); 
	var pRatio = pWidth / pHeight;

	var imgPos=image.offset();
	$("#fullImg").remove();
	var ssrc=image.attr("src");
	var fullImg=$.makeElement("img", {id: "fullImg", src: ssrc});
	fullImg.css("position","absolute");
	fullImg.css("z-index",10);
	fullImg.css("height",iHeight);
//	fullImg.offset(imgPos);
	fullImg.css("left",imgPos.left);
	fullImg.css("top",imgPos.top);
	fullImg.addClass("shadow");
	fullImg.click(function(){	$(this).fadeOut("fast");	});
	$("body").append(fullImg);
	var futureTop=container.scrollTop();
	var futureWidth=pHeight * iRatio;
	var centerLeft=(pWidth-futureWidth) /2;
	fullImg.animate({height: pHeight, left: centerLeft, top:futureTop}, "slow");
//	image.hide("slow");
};

//fit image in container element or window size
//img element
//image: Image object
//zoom: 
//0: do not resize
//1: resize if image larger that container
//2: always resize image

//clip: 
//false or "l": fit long side, no clipping (default)
//"s": fit short side, clipping
//"w": fit width, clipping
//"h": fit height, clipping
Slideshow.prototype.fitImageInParent = function(img, image, container, zoom, clip)
{
	//container element must have position: relative
	img=$(img);
	if(!image)
		image=img;
	else
		img.attr("src", image.src);

	container=this.setContainer(container);

	var	iHeight = image.height();
	var	iWidth  = image.width(); 
	var iRatio = iWidth / iHeight;
	
	var	pHeight = container.height();
	var	pWidth = container.width(); 
	var pRatio = pWidth / pHeight;

	var wider = iRatio >= pRatio;
	
	//fit long side, keep aspect ratio
	if (!zoom)
	{
		this.resetImageSize(img);
		return;
	}

	if(iWidth > pWidth)
	{
		iWidth  = image.width(pWidth); 
		img.width(iWidth);
	}
		
	if(xor(wider , clip)) //if too wide, fit width
	{
		iWidth  = pWidth;
		iHeight = iWidth / iRatio;
	}
	else //or fit height;
	{
		iHeight = pHeight;
		iWidth  = iHeight * iRatio;
	}
	//TODO: fit short side, keep aspect ratio
	img.width(iWidth);
	img.height(iHeight);
		
	//center image
	var centerX=(pWidth  - iWidth ) /2;
	var centerY=(pHeight - iHeight) /2;
	img.position({ top: centerY, left: centerX});
	
	this.setStatus("x:{0} y:{1} - {2} x {3} {4}".format(centerX, centerY, iWidth, iHeight, wider ? "wider" : "taller"));
};

Slideshow.prototype.setStatus = function(text)
{
	text=text || "";
	if(isObject(text))
		text=JSON.stringify(text);

	this.elements.statusBar.html(text);
};

Slideshow.prototype.addStatus = function(text)
{
	if(!text) return;
	if(isObject(text))
		text=JSON.stringify(text);
	this.elements.statusBar.append("\n" + text);
};


Slideshow.prototype.totalDuration = function()
{
	if(!this.pics) return 0;
	return this.pics.sum(MediaFile.getDuration);
};


