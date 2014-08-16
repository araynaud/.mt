function Html5Player(settings)
{
	//if data is a string: get settings by key name
	if(isString(settings))
	{
		this.playerKey=settings;
		this.settings = Html5Player.playerSettings[settings];
	}
	else if(isObject(settings))
	{
		this.settings = settings;
		this.playerKey=this.settings.id;
	}

	this.type = this.settings.type || "audio";
	this.playerKey = this.settings.key || this.settings.id;

    this.current = 0;
    this.container = $("#" + this.settings.id);	
    this.playlistDiv = $("#{0}Playlist".format(this.settings.id));
	this.messsageDiv = $("#{0}Message".format(this.settings.id));
	this.createPlayer();

   	this.setupEvents();
   	Html5Player[this.playerKey]=this;
}

if(!window.MediaPlayer)
	MediaPlayer = Html5Player;

Html5Player.playerSettings=
{
	audio:
	{	
		key: "audio",	
		type: "audio",
		id:"musicPlayer",
		autostart: false,		
		repeat: true,
		style: { width: "100%" },
		playlist: {	position: "top", showAll: false, maxHeight: 200 }
	},
	video:
	{
		key: "video",
		type: "video",
		id:"videoPlayer",
		size: 0,
		autostart: false,		
		repeat: true,
		uiMode: "video",
		class: "shadow photoBorder",
		playlist: {	position: "right", size: 300, showAll: true } //, maxHeight: 400 }
	},
	slide:
	{
		key: "slide",
		type: "video",
		id:"slidePlayer",
		size: 1,
		container: "videoSlide",
		autostart: true,
		repeat: false,
		uiMode: "slideshow",
		playlist: {	position: "down", showAll: false, maxHeight: 400 }
	}

};

Html5Player.videoPlayerSizes=[
	["small", 400,225],
	["default",640,360],
	["large", 960,540],
	["hd", 1280,720]
];

Html5Player.init = function(playerKey)
{
	playerKey = valueOrDefault(type, "audio");
	return Html5Player.getInstance(playerKey).loadFromHtml();
};

Html5Player.loadPlaylist = function(playerKey, mediaFiles)
{
	var h5p = Html5Player.getInstance(playerKey);
	if(h5p.type=="video")
		mediaFiles=mediaFiles.filter(MediaFile.isLocalVideoStream); 
	return h5p.loadPlaylist(mediaFiles);
};

Html5Player.prototype.setupEvents = function()
{
    var h5p=this;
    this.player.addEventListener("ended", function(e)
    {
    	if(h5p.repeat || !h5p.isLastItem())
	    	h5p.playNext();
   		else if(h5p.slideshow && h5p.slideshow.play)
			h5p.slideshow.showNextImage();
 	});

    this.player.addEventListener("play",  function(e)
    { 
//    	if(window.console) console.log("player.play");
    	h5p.setMessage("playing"); 
		h5p.settings.autostart = true;
		if(h5p.slideshow && !h5p.slideshow.play)
			h5p.slideshow.togglePlay(true);
    });

    this.player.addEventListener("pause", function(e)
    {
//    	if(window.console) console.log("player.pause");
		if(h5p.player.ended) return;
	   	h5p.setMessage("paused"); 
		h5p.settings.autostart = false;
		if(h5p.slideshow && h5p.slideshow.play)
			h5p.slideshow.togglePlay(false);
    });

	var h5p=this;
	var preventDefault = function(e) { return false; };
    this.player.addEventListener("keyup", preventDefault);
    this.player.addEventListener("keydown", preventDefault);
};

Html5Player.getInstance = function(playerKey)
{
	if(!Html5Player[playerKey])
		return new Html5Player(playerKey);
	return Html5Player[playerKey];
};

//create playlist from HTML
Html5Player.prototype.loadFromHtml = function()
{
	if(isEmpty(this.mediaFiles))
	{
		$(this.player).hide();
		return;
	}

    var h5p=this;
    this.trackLinks = this.playlistDiv.find("a.track");
    this.trackLinks.click(function(e)
    {
        e.preventDefault();
        link = $(this);
        h5p.loadFile(link.parent().index());
    });

	return this.loadFile();
};

Html5Player.prototype.createPlayer = function()
{
	this.initSize();

	this.player = $.makeElement(this.type, {
		id: this.settings.id + "_" + this.type,
		preload: "auto"
		,controls: true
	});
	if(this.settings.style)
		this.player.css(this.settings.style);
	if(this.settings.class)
		this.player.addClass(this.settings.class);

	this.player.appendTo(this.container);
	this.jqplayer = this.player;
	this.player = this.player[0];

	if(this.type!="video") return;
	
	this.iframe = $.makeElement("iframe", {id: this.settings.id + "_iframe"});
	if(this.settings.style)
		this.iframe.css(this.settings.style);
	if(this.settings.class)
		this.iframe.addClass(this.settings.class);

	this.iframe.appendTo(this.container);
	this.jqiframe = this.iframe;
	this.iframe = this.iframe[0];
};

//TODO pass optional controlDiv id 
//todo play icon even if only 1 item
// onclick functions? instance or static?
Html5Player.prototype.setupIcons = function()
{
	if(!this.player) return;
	if(!this.controlDiv)
		this.controlDiv=$("#{0}Controls".format(this.settings.id));

	this.controlDiv.html("");
	this.controlDiv.append('<img id="{0}PrevButton" class="icon icontr" title="previous (P)" src="icons/arrow-first.png" alt="previous" onclick="MediaPlayer.{1}.playPrevious();"/>'.format(this.settings.id, this.playerKey));
	var icon = this.getPlayPauseIcon();
	this.controlDiv.append('<img id="{0}PlayButton" class="icon icontr" title="play/pause (M)" src="{2}" alt="play" onclick="MediaPlayer.{1}.togglePlay();"/>'.format(this.settings.id, this.playerKey, icon));
	this.controlDiv.append('<img id="{0}NextButton" class="icon icontr" title="next (N)" src="icons/arrow-last.png" alt="next" onclick="MediaPlayer.{1}.playNext();"/>'.format(this.settings.id, this.playerKey));
	
	var iconAttr={
		id: this.settings.id + "PlaylistButton",
		src: "icons/playlist.png",
		"class": "icon icontr",
		alt: "playlist",
		title: "playlist",
		onclick: "MediaPlayer.{0}.togglePlaylist()".format(this.playerKey)
	};
	icon=$.makeElement("img", iconAttr);
	this.controlDiv.append(icon);	
	if(!isMissing(this.settings.size))
	{
		iconAttr={
			id: this.settings.id + "{0}ResizeButtonD",
			src: "icons/24-zoom-actual.png",
			"class": "icon icontr",
			alt: "smaller",
			title: "resize player",
			onclick: "MediaPlayer.{0}.nextSize(-1)".format(this.playerKey)
		};
		icon=$.makeElement("img", iconAttr);//.html(iconAttr.alt);
		this.controlDiv.append(icon);	

		iconAttr={
			id: this.settings.id + "{0}ResizeButtonL",
			src: "icons/24-zoom-fill.png",
			"class": "icon icontr",
			alt: "larger",
			title: "resize player",
			onclick: "MediaPlayer.{0}.nextSize(1)".format(this.playerKey)
		};

		icon=$.makeElement("img", iconAttr);
		this.controlDiv.append(icon);	
	}
	
	iconAttr={
		id: this.settings.id + "{0}CloseButton",
		src: "icons/delete.png",
		"class": "icon icontr",
		alt: "close",
		title: "close player",
		onclick: "MediaPlayer.{0}.remove()".format(this.playerKey)
	};
	icon=$.makeElement("img", iconAttr);
	this.controlDiv.append(icon);	

	iconAttr={
		id: this.settings.id + "{0}uploadIcon",
		src: "icons/upload16.png",
		"class": "icon upload",
		alt: "upload",
		title: "upload files",
		onclick: "UI.uploadMusicFiles()"
	};
	icon=$.makeElement("img", iconAttr);
//	this.controlDiv.append(icon);	
};

Html5Player.prototype.getPlayPauseIcon = function()
{
	return this.isPlaying() ? "icons/pause.png" : "icons/play.png";
};

//render playlist from mediaFiles array
Html5Player.prototype.loadPlaylist = function(mediaFiles)
{
	if(isEmpty(mediaFiles))
	{
		this.jqplayer.hide();
		return;
	}

	if(!isArray(mediaFiles))
		return this.loadPlaylist([mediaFiles]);

	this.mediaFiles = mediaFiles;

	if(!isEmpty(this.playlistDiv) && mediaFiles.length>1)
	{
		UI.renderTemplate("playlistLinkTemplate", this.playlistDiv, mediaFiles);
		this.playlistDiv.width(this.settings.playlist.size);
		this.playlistDiv.css("max-width", this.settings.playlist.maxWidth || "");
	}

	if(mediaFiles.length>1)
		this.setupIcons();


	return this.loadFromHtml();
}

Html5Player.prototype.loadMediaFile = Html5Player.prototype.loadPlaylist;

Html5Player.prototype.loadFile = function(index)
{
	index = valueOrDefault(index, this.current);
	this.current = modulo(index, this.mediaFiles.length);

	this.currentFile = this.mediaFiles[this.current];

	this.jqiframe.show();
	var isEmbedded = this.currentFile.isExternalVideoStream();
	if(isEmbedded)
	{
		this.iframe.src = this.currentFile.getFileUrl();
	}
	else
	{
		this.player.title  = this.currentFile.title;
	    this.player.src    = this.currentFile.getFileUrl(this.currentFile.isVideoStream());
		this.player.poster = this.currentFile.getThumbnailUrl(1);
	   this.player.load();
	}
	this.jqiframe.toggle(isEmbedded);
	this.jqplayer.toggle(!isEmbedded);

	this.displaySelectedItem();
	this.setSize();
 
	if(this.settings.uiMode)
		UI.setMode(this.settings.uiMode);

    if(this.settings.autostart)
	    this.player.play();

	return this;
};

//call upon player onplay event
//jwplayer mapped function
Html5Player.prototype.play = function()
{
	if(this.player && !this.isPlaying())
		this.player.play();
};

Html5Player.prototype.pause = function()
{
	if(this.player && this.isPlaying())
		this.player.pause();
};

Html5Player.prototype.togglePlay = function(state)
{
	state = valueOrDefault(state, this.player.paused);
//	if(state == !this.player.paused) return;

	if(state)
		this.player.play();
	else
		this.player.pause();

};

Html5Player.prototype.isPlaying = function()
{ 
	return !this.player.paused; 
}

Html5Player.prototype.playNext = function(incr)
{
	incr = valueOrDefault(incr, 1);
	this.loadFile(this.current + incr);
}

Html5Player.prototype.playPrevious = function(incr)
{
	incr = valueOrDefault(incr, 1);
	this.loadFile(this.current - incr);
}

Html5Player.prototype.isLastItem = function()
{
	return this.current === (this.mediaFiles.length - 1);
}

Html5Player.prototype.getElement = function()
{
	var el  = this.settings.container || this.settings.id;
	return $("#"+el);
};

Html5Player.prototype.show = function(options)
{
	return this.getElement().show(options);
};

Html5Player.prototype.hide = function(options)
{ 
	return this.getElement().hide(options);
};

Html5Player.prototype.toggle = function(options)
{ 
	return this.getElement().toggle(options);
};

Html5Player.prototype.initSize = function()
{
	if(isMissing(this.settings.size)) return;

	var size=Html5Player.videoPlayerSizes[this.settings.size];
	this.settings.width=size[1];
	this.settings.height=size[2];
//	this.setMessage(size[0]);
}

Html5Player.prototype.nextSize = function(incr)
{
	incr=valueOrDefault(incr,1);
	this.settings.size = modulo(this.settings.size + incr, Html5Player.videoPlayerSizes.length);
	return this.setSize();
};

Html5Player.prototype.setSize = function()
{
	if(isMissing(this.settings.size)) return;
	var size=Html5Player.videoPlayerSizes[this.settings.size];
	this.setMessage(size[0]);

	this.playlistDiv.css("max-height", size[2]);
	if(!this.player) return;

	var width = (this.currentFile && this.currentFile.ratio) ? this.currentFile.ratio * size[2] : size[1];
	return this.resize(width, size[2]);
};

Html5Player.prototype.resize = function(width, height)
{
	this.jqplayer.css({width: width, height: height});
	this.jqiframe.css({width: width, height: height});
};

Html5Player.prototype.displaySelectedItem = function(index)
{
    var link = this.trackLinks.eq(this.current);
    var selectedItem = link.parent().addClass("selectedItem");
    var otherItems = selectedItem.siblings().removeClass("selectedItem");

	selectedItem.slideDown("slow");
	otherItems.toggleEffect(this.settings.playlist.showAll, "slow");
};

Html5Player.prototype.togglePlaylist = function()
{
	this.settings.playlist.showAll = !this.settings.playlist.showAll;
	this.displaySelectedItem();
};

Html5Player.prototype.getPlaylistItem = function(index)
{
	return isEmpty(this.mediaFiles) ? null : this.mediaFiles[index];
};

Html5Player.prototype.countPlaylistItems = function()
{
	return isEmpty(this.playlist) ? 0 : this.playlist;
};

Html5Player.prototype.hasPlaylist = function()
{
	return !isEmpty(this.playlist);
};

Html5Player.prototype.playerFocus = function()
{
	return (document.activeElement == this.player);
};

Html5Player.prototype.displayEvent = function(event)
{
	this.setMessage(Object.toText(event," "));
};

Html5Player.prototype.setMessage = function(text, event)
{
	text = Object.toText(text," ");
	this.messsageDiv.html(text);
};
