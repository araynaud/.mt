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
	this.zoom=true;
	this.play=false;
	this.timer=null;
	this.tnIndex=1;
	this.depth=0;
	this.preLoadedImage=null;
	this.alignX = "center"; //"left", "right"
	this.alignY = "center"; //"top", "bottom"
	this.changeMode=true;
	this.zoomFunction="scale";
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

	//make references		
	Slideshow.instances = Slideshow.instances || [];
	if(this.id)
		Slideshow.instances[this.id]=this;
	else
		Slideshow.instances.push(this);
}

//instance methods to set options
Slideshow.prototype.setOptions = function(options)
{
	if(isObject(options))
		Object.merge(this,options);

	this.setupElements();
	this.setInterval();
	this.setContainer();
	this.transition.setOptions(this);
	this.setStart(this.start);

	if(!isEmpty(this.transition.elements))
		this.transition.elements.bindReset("click", Slideshow.slideOnClick);

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
	if(this.mplayer)
		this.mplayerSlide = this.mplayer.getElement();
};

Slideshow.slideOnClick = function(e)
{
	//return UI.slideshow.toggleZoom();

	var img = $(this);
	var coord = Slideshow.getClickCoord(e, img);
//	UI.slideshow.setStatus(coord);
	if(coord.rx < .25 && coord.ry < .25)
		UI.setMode();
	else if(coord.rx < .25)
		UI.slideshow.showNextImage(-1);
	else if(coord.rx > .75 && coord.ry < .25)
		UI.slideshow.togglePlay();
	else if(coord.rx > .75)
		UI.slideshow.showNextImage(1);
	else
		UI.slideshow.toggleZoom();
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
//	var mx = Math.round(img.borderWidth() / 2);
//	var my = Math.round(img.borderHeight() / 2);
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

//accept filenames or int: if start is a string, find its position in this.pics array
Slideshow.prototype.setStart = function(start)
{
	if(isMissing(start))	
		return this.currentIndex;
	if(this.pics.length == 0)
		this.currentIndex = 0;
	else
		this.currentIndex = this.getPicPosition(start);
	
	return this.currentIndex;
};

Slideshow.prototype.setPics = function(mediaFiles)
{
	this.pics = mediaFiles;
};

Slideshow.prototype.getPicPosition = function(input)
{
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
//	alert("Slideshow.display:{0} / {1} pics.".format(start,this.pics.length));
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
//	var iconsdir = String.combine(Album.serviceUrl ,"icons");
	this.play=valueOrDefault(playState,!this.play);
	this.setStatus(this.play ? "playing" : "paused");
	var icon = this.play ? "pause.png" : "play.png";
	this.elements.playButton.attr("src", String.combine(Album.serviceUrl ,"icons", "media-" + icon));

//TODO: add config setting. slideshow.playaudio
//	if(config.slideshow.playAudio && this.play && MediaPlayer.audio)
//		MediaPlayer.audio.play();

	if(this.currentFile.isVideo())
		this.mplayer.togglePlay(this.play);
	else if(this.play)
	{
		this.setInterval();
		this.showNextImage();
	}
	else
		clearTimeout(this.timer);
};

Slideshow.prototype.nextTransition = function()
{
	this.type = this.transition.next();
	this.setStatus("transition: " + this.transition.getLabel());
};

Slideshow.prototype.toggleOption = function()
{
	if(this.currentFile.isImage())
		UI.slideshow.showImage(null,"crossFade");
	else
	{
		this.styleSlide(this.mplayerSlide);
		this.fitVideo();
	}
};

Slideshow.prototype.toggleZoom = function()
{
	this.zoom=!this.zoom;
	this.setStatus("zoom: " + this.zoom);
	if(this.currentFile.isImage())
		this.showImage(null, this.zoomFunction);
	else
		this.fitVideo();
};

Slideshow.prototype.toggleControls = function()
{
	$("#slideshowContainer .controls").fadeToggle('slow');
	$('#fbComments').fadeToggle('slow');
};

Slideshow.prototype.setImageLink = function(href, text)
{
	this.elements.imageLink.html(text).attr("href",href);
};

Slideshow.prototype.validIndex = function(i)
{
	return modulo(i, this.pics.length);
};

Slideshow.prototype.previousIndex = function()
{
	return this.validIndex(this.currentIndex - this.increment);
};

Slideshow.prototype.nextIndex = function()
{
	return this.validIndex(this.currentIndex + this.increment);
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
		if(pic.tnsizes[tnIndex] <=0)
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

Slideshow.prototype.showImage = function(index, transitionFunction)
{	
	if(isEmpty(this.pics)) return;
	this.setStart(index);
	this.setStatus();

	var fileChange = (this.pics[this.currentIndex] != this.currentFile);
	this.currentFile = this.pics[this.currentIndex];

	this.preLoadedImage = this.loadImage();
	if(this.currentFile.isVideo())
		this.styleSlide(this.mplayerSlide);
	else
	{
		this.currentImg = this.transition.getNextSlide();
		this.styleSlide(this.currentImg);
	}

	if(this.preLoadedImage.complete)
		this.displayLoadedImage(transitionFunction, fileChange);
	else
	{
		var ss=this;
		$(this.preLoadedImage).load(function() { ss.displayLoadedImage(transitionFunction, fileChange); });
	}
	UI.editDiv.appendTo("#slideshowControls").show();
};

Slideshow.prototype.styleSlide = function(el)
{
	el.toggleClass("margin", album.margin);
	el.toggleClass("shadow", album.shadow &&  !this.currentFile.isTransparent());
	el.toggleClass("photoBorder", album.border && !this.currentFile.isTransparent());

	if(window.UI && UI.divStyles)
		el.attr("style", UI.divStyles());
};

Slideshow.prototype.displayLoadedImage = function(transitionFunction, fileChange)
{
	if(this.currentFile.isVideoStream() && fileChange)
	{
		this.hideImage();
		this.transition.inProgress=false;
		if(this.mplayer)
		{
			this.mplayer.loadMediaFile(this.currentFile);
			this.fitVideo();

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
			//this.mplayer.pause();
			this.mplayer.hide(this.transition.duration);
		}

		this.fitImage();
		this.transition.execute(transitionFunction);

		if(this.play)
			this.autoShowNextImage();
	}	

	this.addStatus("fileChange:" + fileChange);
	this.elements.imageText.html("({0}/{1})".format(this.currentIndex + 1, this.pics.length));
	this.setImageLink(this.currentFile.getFileUrl(), this.currentFile.name || this.currentFile.title);
	this.showComments(this.currentFile);
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
	UI.renderTemplate("tagTemplate", this.elements.tags, Object.values(mediaFile.tags), null, {action: "removetag"});
};

// ---- IMAGE display functions -------

//use slideshowObj.js / fitImage in container
//fit long, short side, stretch
//move small icon thumbnails above caption


Slideshow.prototype.fitVideo = function () 
{
	if(!this.currentFile.isVideoStream()) return;

	if(!this.zoom)
		return this.mplayer.setSize();

	var bw = 60; // image.borderMarginWidth();
	var bh = 80; //image.borderMarginHeight();

	var preRatio = 16/9;
	if(this.currentFile.width && this.currentFile.height)
		preRatio = this.currentFile.width / this.currentFile.height;
	var wWidth  = this.elements.container.width()  - bw;
	var wHeight = this.elements.container.height() - bh;
	var wRatio = wWidth / wHeight;
	var width  = Math.min(wWidth, this.currentFile.width || 0);
	var height = Math.min(wHeight, this.currentFile.height || 0);
	if (this.zoom && preRatio > wRatio) //if too wide, fit width
	{
		width = wWidth;
		height = width / preRatio;
	}
	else if (this.zoom) //or fit height;
	{
		height = wHeight;
		width = height * preRatio;
	}

	this.mplayer.resize(width, height);
};

Slideshow.prototype.fitImage = function (image, preLoaded) 
{
	preLoaded = valueOrDefault(preLoaded, this.preLoadedImage);
	image = valueOrDefault(image, this.currentImg);
	if (!preLoaded || !image) return;

	if (image.attr("src") !== preLoaded.src)
		image.attr("src", preLoaded.src);

	image.css("margin", "");
	image.toggleClass("margin", album.margin);
	var bw= image.borderMarginWidth();
	var bh= image.borderMarginHeight();

	var preRatio = preLoaded.width / preLoaded.height;
	var wRatio = (this.elements.container.width() - bw ) / (this.elements.container.height() - bh);

	var height = preLoaded.height;
	var width = preLoaded.width;
	image.width(width);
	image.height(height);
	if (this.zoom && preRatio > wRatio) //if too wide, fit width
	{
		width = this.elements.container.width() - bw;
		height = width / preRatio;
	}
	else if (this.zoom) //or fit height;
	{
		height = this.elements.container.height() - bh;
		width = height * preRatio;
	}

	image.width(width);
	image.height(height);
//	this.setStatus("{0} x {1}".format(width, height));

	//position image
	this.setMargins(image);
};

Slideshow.prototype.setMargins = function(image)
{
	var mx = image.marginWidth()/2;
	var my = image.marginHeight()/2;	

	var x = this.elements.container.width() - image.outerWidth(true);
	if (this.alignX == "center") x/=2;

	var y = this.elements.container.height() - image.outerHeight(true);
	if (this.alignY == "center") y/=2;

	image.css("margin-left", this.alignX == "left" ? mx : x+mx);
	image.css("margin-right", this.alignX == "right" ? mx : x+mx);
	image.css("margin-top", this.alignY == "top" ? my : y+my);
	image.css("margin-bottom", this.alignY == "bottom" ? my : y+my);

//	this.addStatus("l:{0} r:{1} t:{2} b:{3}".format(image.css("margin-left"),image.css("margin-right"),image.css("margin-top"),image.css("margin-bottom")));
//	this.addStatus("x:{0} y:{1}".format(image.css("left"), image.css("top")));

//	this.addStatus("o x:{0} y:{1}".format(image.offset().left, image.offset().top));
//	this.addStatus("p x:{0} y:{1}".format(image.position().left, image.position().top));
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


