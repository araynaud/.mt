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
	this.messageDiv = $("#{0}Message, .status".format(this.settings.id));
	this.subtitlesDiv = $(this.settings.subtitlesDiv);
	this.createPlayer();

   	this.setupEvents();
   	Html5Player[this.playerKey]=this;
}

if(!window.MediaPlayer)
	MediaPlayer = Html5Player;

Html5Player.YouTubeReady=false;
function onYouTubeIframeAPIReady()
{
	Html5Player.YouTubeReady=true;
}

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
		youtube: true,
		size: 1,
		autostart: false,		
		repeat: true,
		uiMode: "video",
		"class": "shadow photoBorder",
		subtitlesDiv: "#videoSubtitles",
		playlist: {	position: "right", size: 300, showAll: true } //, maxHeight: 400 }
	},
	slide:
	{
		key: "slide",
		type: "video",
		id:"slideshowContainer",
		youtube: true,
		size: 1,
		autostart: true,
		repeat: false,
		uiMode: "slideshow",
		"class": "slide top left",
		subtitlesDiv: "#slideshowSubtitles",
		hidden:true
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

Html5Player.loadPlaylist = function(playerKey, mediaFiles, start)
{
	var hp = Html5Player.getInstance(playerKey);
	if(hp.type=="video")
		mediaFiles=mediaFiles.filter(MediaFile.isVideoStream); 
	return hp.loadPlaylist(mediaFiles, start);
};

Html5Player.prototype.setupEvents = function()
{
    var hp=this;
    this.player.addEventListener("ended", function(e)
    {
	    var sl=hp.slideshow;
   		if(sl && sl.play)
			sl.showNextImage();
    	else if(hp.settings.repeat || !hp.isLastItem())
	    	hp.playNext();
 	});

    this.player.addEventListener("play",  function(e)
    { 
    	hp.setMessage("playing"); 
		hp.settings.autostart = true;
	    var sl=hp.slideshow;
		if(sl && !sl.play)
			sl.togglePlay(true);
    });

    this.player.addEventListener("pause", function(e)
    {
		if(hp.player.ended) return;
	   	hp.setMessage("paused"); 
		hp.settings.autostart = false;
	    var sl=hp.slideshow;
		if(sl && sl.play)
			sl.togglePlay(false);
    });

	var preventDefault = function(e) { return false; };
    this.player.addEventListener("keyup", preventDefault);
    this.player.addEventListener("keydown", preventDefault);

    // 4. The API will call this function when the video player is ready.
    this.ytPlayerReady = function(event) 
    {
		event.target.play = event.target.playVideo;
		event.target.pause= event.target.pauseVideo;
		event.target.stop = event.target.stopVideo;
   };

	// 5. The API calls this function when the player's state changes.
	var done = false;
	this.ytPlayerStateChange = function(event) 
	{
		switch(event.data)
		{
			case YT.PlayerState.PLAYING:
				hp.setMessage("yt playing " + event.data);
				break;

			case YT.PlayerState.PAUSED:
				hp.setMessage("yt paused " + event.data);
				break;

			case YT.PlayerState.ENDED:
				hp.setMessage("yt ended " + event.data);
				var sl=hp.slideshow;
		   		if(sl && sl.play)
					sl.showNextImage();
		    	else if(hp.settings.repeat || !hp.isLastItem())
			    	hp.playNext();
		}
	};
};

Html5Player.seekLoopStart = function(e)
{
	if(this.currentTime >= this.loopEnd) 
		this.currentTime= this.loopStart; 
}

Html5Player.prototype.loop = function(start, end)
{
	this.player.loopStart = valueOrDefault(start, 0);
	this.player.loopEnd = valueOrDefault(end, this.player.duration - .2);
	this.player.currentTime = this.player.loopStart;
    this.player.addEventListener("timeupdate", Html5Player.seekLoopStart);
};

Html5Player.prototype.rewind = function(sec)
{
	sec = valueOrDefault(sec,5);
	this.player.currentTime -= sec;
}

Html5Player.prototype.forward = function(sec)
{
	sec = valueOrDefault(sec,5);
	this.player.currentTime += sec;
}

Html5Player.prototype.disableLoop = function()
{
  this.player.removeEventListener("timeupdate", Html5Player.seekLoopStart);
};


Html5Player.getInstance = function(playerKey)
{
	if(!Html5Player[playerKey])
		return new Html5Player(playerKey);
	return Html5Player[playerKey];
};

//create playlist from HTML
Html5Player.prototype.loadFromHtml = function(start)
{
	if(isEmpty(this.mediaFiles))
	{
		this.jqplayer.hide();
		return;
	}

    var hp=this;
    this.trackLinks = this.playlistDiv.find("a.track");
    this.trackLinks.click(function(e)
    {
        e.preventDefault();
        link = $(this);
        hp.loadFile(link.parent().index());
        hp.play();
    });

	return this.loadFile(start);
};

Html5Player.prototype.createPlayer = function()
{
	this.initSize();

	this.player = $.makeElement(this.type, { id: this.settings.id + "_" + this.type, controls: true, preload: "auto" });
	if(this.settings.style)
		this.player.css(this.settings.style);
	if(this.settings["class"])
		this.player.addClass(this.settings["class"]);
	if(this.settings.hidden)
		this.player.hide();

	this.player.prependTo(this.container);
	this.jqplayer = this.player;
	this.player = this.player[0];

	var hp=this;
	if(this.type=="video")
	{
	    this.player.addEventListener("timeupdate", function() { hp.showSubtitles(); } );
	    this.player.addEventListener("seeked", function()
	    {
	      hp.findNextSubtitle();
//	      UI.addStatus("seeked: {0} / index: {1}".format(this.currentTime, hp.subtitleIndex)); 
	  	});
	}
	if(this.type!="video" || !this.settings.youtube || !Html5Player.YouTubeReady) return;

//	loadJavascript(config.youtube.iframeApiUrl);
	this.ytid = this.settings.id + "_youtube";
	this.yt = $.makeElement("div", {id: this.ytid}).appendTo(this.container);	
};

Html5Player.findNextSubtitle = function(hp)
{
	return hp.findNextSubtitle();
}

Html5Player.prototype.findNextSubtitle = function()
{
	if(!this.currentFile || !this.currentFile.subtitles) 
		return this.subtitleIndex=0;

	var subtitles = this.currentFile.subtitles;
	var time = this.player.currentTime;
	for(var i=0; i < subtitles.length; i++)
		if(subtitles[i].time >= time) 
			return this.subtitleIndex = i>1 ? i-1 : 0;
	return 0;
}

Html5Player.showSubtitles = function(hp)
{
	if(!hp || !hp.currentFile || !hp.currentFile.subtitles) return;
	hp.showSubtitles();
}

Html5Player.prototype.showSubtitles = function()
{
	if(!this.currentFile || !this.currentFile.subtitles) return;

	var subtitles = this.currentFile.subtitles;
	var time = this.player.currentTime; //Math.roundMultiple(this.player.currentTime, .1);
//	UI.addStatus("currentTime:" + time);

	var nextSub = subtitles[this.subtitleIndex];
	if(!nextSub || time < nextSub.time) return;
	
	this.subtitlesDiv.html(nextSub.text);
//	UI.addStatus(time + ": " + nextSub.time +": "+ nextSub.text);
	this.subtitleIndex++;
}

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
	this.controlDiv.append(icon);	
};

Html5Player.prototype.getPlayPauseIcon = function()
{
	return this.isPlaying() ? "icons/pause.png" : "icons/play.png";
};

//render playlist from mediaFiles array
Html5Player.prototype.loadPlaylist = function(mediaFiles, start)
{
	if(isEmpty(mediaFiles))
	{
		this.jqplayer.hide();
		return;
	}

	if(!isArray(mediaFiles))
		return this.loadPlaylist([mediaFiles]);

	this.mediaFiles = mediaFiles;

	if(!isEmpty(this.playlistDiv) && this.settings.playlist)
	{
		UI.renderTemplate("playlistLinkTemplate", this.playlistDiv, mediaFiles);
		this.playlistDiv.width(this.settings.playlist.size);
		this.playlistDiv.css("max-width", this.settings.playlist.maxWidth || "");
	}

	if(mediaFiles.length>1)
		this.setupIcons();

	return this.loadFromHtml(start);
}

Html5Player.prototype.loadMediaFile = Html5Player.prototype.loadPlaylist;

Html5Player.prototype.getFilePosition = function(input)
{
	if(isEmpty(this.mediaFiles.length))
		return 0;
	if(isMissing(input) && this.current > this.mediaFiles.length)
		return 0;
	if(isMissing(input))
		return this.current;
//object: search by name
	if(isObject(input))
		input = input.id || input.name  || input.filename;
//string: search by name
	if(isString(input))
	{
		var i=this.mediaFiles.getElementPosition(input,["id","name"]);
		return i>=0 ? i : 0;
	}
//number
	return this.validIndex(input);
};

Html5Player.prototype.validIndex = function(i)
{
	return modulo(i, this.mediaFiles.length);
};

Html5Player.prototype.loadFile = function(index)
{
	this.current = this.getFilePosition(index);

	this.currentFile = this.mediaFiles[this.current];
	this.subtitleIndex=0;
	if(this.currentFile.isVideoStream())
		this.currentFile.loadSubtitles();

	var isEmbedded = this.isEmbeddedVideo();
	if(isEmbedded)
	{
		this.player.src = ""; // null; //stop HTML5 player
		this.loadYoutubePlayer(this.currentFile.id);
	}
	else
	{
		this.player.title  = this.currentFile.title;
	    this.player.src    = this.currentFile.getFileUrl(this.currentFile.isVideoStream());
		this.player.poster = this.currentFile.getThumbnailUrl(1);
		this.player.load();
	}
	if(this.jqiframe) this.jqiframe.toggle(isEmbedded);
	if(this.jqplayer) this.jqplayer.toggle(!isEmbedded);

	this.displaySelectedItem();
	this.setSize();
 
	if(window.UI && this.settings.uiMode)
		UI.setMode(this.settings.uiMode);

    if(this.settings.autostart)
	    this.play();

	return this;
};

Html5Player.prototype.isEmbeddedVideo = function(index)
{
	return this.currentFile && this.currentFile.isExternalVideoStream();
};

Html5Player.prototype.loadYoutubePlayer = function(videoId)
{
	if(!Html5Player.YouTubeReady) return;
	
	if(this.youtubePlayer && this.settings.autostart)
		this.youtubePlayer.loadVideoById(videoId);
	else if(this.youtubePlayer)
		this.youtubePlayer.cueVideoById(videoId);
	else
	{
	    this.youtubePlayer = new YT.Player(this.ytid,
	    {
//	          width: this.settings.width, 
//	          height: this.settings.height,
	          videoId: videoId,
	          playerVars: { 'autoplay': this.settings.autostart}, //, 'controls': 1 },
	          events: { 'onStateChange': this.ytPlayerStateChange }
	    });
	}

	this.jqiframe = $("#"+this.ytid);
	if(!this.currentFile.width)		this.currentFile.width = this.jqiframe.width();
	if(!this.currentFile.height)	this.currentFile.height = this.jqiframe.height();

	if(this.settings.style)
		this.jqiframe.css(this.settings.style);
	if(this.settings["class"])
		this.jqiframe.addClass(this.settings["class"]);
};

Html5Player.prototype.activePlayer = function()
{ 
	return this.isEmbeddedVideo() ? this.youtubePlayer : this.player;
};

Html5Player.prototype.getPlayerElement = function()
{
	return this.isEmbeddedVideo() ? this.jqiframe : this.jqplayer;
};

Html5Player.prototype.inactivePlayer = function()
{ 
	return this.isEmbeddedVideo() ? this.player : this.youtubePlayer;
};

Html5Player.prototype.isPlaying = function()
{ 
	if(!this.isEmbeddedVideo())
		return !this.player.paused; 
	if(this.youtubePlayer)
		return this.youtubePlayer.getPlayerState()==YT.PlayerState.PLAYING; 
	return false;
};

//call upon player onplay event
//jwplayer mapped function
Html5Player.prototype.play = function()
{
	var pl = this.activePlayer();
	if(pl && pl.play)
		pl.play();
	else if(pl && pl.playVideo)
		pl.playVideo();
};

Html5Player.prototype.pause = function()
{
	var pl= this.activePlayer();
	if(pl && pl.pause)
		pl.pause();
	else if(pl && pl.pauseVideo)
		pl.pauseVideo();
};

Html5Player.prototype.togglePlay = function(state)
{
	state = valueOrDefault(state, this.player.paused);
//	if(state == !this.player.paused) return;

	if(state)
		this.play();
	else
		this.pause();
};

Html5Player.prototype.playNext = function(incr)
{
	incr = valueOrDefault(incr, 1);
	this.loadFile(this.current + incr);
};

Html5Player.prototype.playPrevious = function(incr)
{
	incr = valueOrDefault(incr, 1);
	this.loadFile(this.current - incr);
};

Html5Player.prototype.isLastItem = function()
{
	return this.current === (this.mediaFiles.length - 1);
};

Html5Player.prototype.getElement = function()
{
	var el  = this.settings.container || this.settings.id;
	return $("#"+el);
};

Html5Player.prototype.show = function(options)
{
	return this.getPlayerElement().show(options);
};

Html5Player.prototype.hide = function(options)
{ 
	return this.getPlayerElement().hide(options);
};

Html5Player.prototype.toggle = function(options)
{ 
	return this.getPlayerElement().toggle(options);
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
	if(this.jqiframe)
		this.jqiframe.css({width: width, height: height});
};

Html5Player.prototype.displaySelectedItem = function(index)
{
	if(!this.settings.playlist) return;

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
	this.messageDiv.html(text);
};
