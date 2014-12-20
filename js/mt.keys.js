// ----- KEYBOARD functions --------
window.UI = window.UI || {};

//handle keypress events
var preventedKeys=[32,39]; //prevent space:page down , left: scroll right
UI.preventKeys = function(event)
{	
	var target=$(event.target);
	if(target.is(":text, .description")) return;
	var keyCode=event.keyCode;
	if(!keyCode) keyCode=event.which;
	if(!preventedKeys.contains(keyCode)) return;
//	UI.setStatus("preventKeys:  k:{0} {1} w:{2} c:{3} => {4}".format(event.keyCode, event.key, event.which, event.charCode, keyCode));
	event.preventDefault();
};

UI.keys = 
{
	"index":
	{
		// on index page : left and right to go to previous/next page
		 37: function() { UI.selectNextPage(-1); },	//left arrow: previous
		 39: function() { UI.selectNextPage(1); },	//right arrow: next
		'I': function() { UI.setOption("reverse"); },	//reverse sort
		'D': function() { UI.setOption("dirsFirst"); },	//sort with dirs first
		'G': function() { UI.setOption("group"); },	//group by sort field
		'R': function() { UI.setOption("rotate"); },	//rotate images
		'C': function() { UI.setOption("caption"); },	//captions
		'B': function() { UI.setOption("border"); },	//borders
		'M': function() { UI.setOption("margin"); },	//margins
		'T': function() { UI.setOption("transpose"); },	//T: transpose columns
		'O': function() { UI.setOption("displayOptions"); }, //options
		'S': function() { UI.setOption("searchOptions"); }, //search options
		'X': function() { $('#dd_sort').selectNextOption(); },
		'Z': function() { $('#dd_fit').selectNextOption(); },
		'P': function() { UI.rotatePages(); },

		'0': UI.setColumns,
		'1': UI.setColumns,
		'2': UI.setColumns,
		'3': UI.setColumns,
		'4': UI.setColumns,
		'5': UI.setColumns,
		'6': UI.setColumns,
		'7': UI.setColumns,
		'8': UI.setColumns,
//		'9': UI.setColumns, conflicts with tab when searching
		 96: UI.setColumns, //0 numpad
		 97: UI.setColumns,	//1 numpad
		 98: UI.setColumns,	//2 numpad
		 99: UI.setColumns,	//3 numpad
		100: UI.setColumns,	//4 numpad
		101: UI.setColumns,	//5 numpad
		102: UI.setColumns,	//6 numpad
		103: UI.setColumns,	//7 numpad
		104: UI.setColumns,	//8 numpad
		105: UI.setColumns,	//9 numpad
		// 61:+= 173?	171: _-
		//61: function() { $('#dd_page').selectNextOption(); },
		173: function() { $('#dd_page').selectNextOption(-1); },
		61: function() { $('#dd_page').selectNextOption(); },
		189: function() { $('#dd_page').selectNextOption(-1); },
		187: function() { $('#dd_page').selectNextOption(); },
		//192:~`
		//46:delete, 45:insert
		'E': function() { UI.setColumnWidths(); } 
	},
	"article":
	 {
		'R': function() { UI.setOption("rotate"); },	//rotate images
		'C': function() { UI.setOption("caption"); },	//captions
		'B': function() { UI.setOption("border"); },	//borders
		'M': function() { UI.setOption("margin"); },	//margins
		'O': function() { UI.setOption("displayOptions"); }, //options
		'S': function() { UI.setOption("searchOptions"); }, //search options
		'H': function() { UI.setOption("shadow"); },	//shadow
		'F': function() { UI.setOption("fadeIn"); }	//fade
	 },
	"slideshow":
	{
		37: function() { UI.slideshow.showNextImage(-1) },	//left arrow: previous
		39: function() { UI.slideshow.showNextImage(+1) },	//right arrow: next
		38:	function() { UI.slideshow.faster(); },	//up: interval / 2
		40:	function() { UI.slideshow.slower(); },	//down: interval * 2
		36: function() { UI.slideshow.showImage(0);	},//home: first
		35: function() { UI.slideshow.showImage(-1); },	//end: last
		27: function() { UI.setPrevMode(); },		// ESC key: close slideshow
		' ': function() {
			if(UI.slideshow.mplayer && UI.slideshow.mplayer.playerFocus()) return;
			UI.addStatus("active: " +document.activeElement.id);
			UI.slideshow.togglePlay(); 
		},	 //SPACE: play/pause
		45: function() { UI.fileActionAjax({action:'addtag', to:'best'}); },		// Insert key: move to best
		46: function() { UI.fileActionAjax({action:'delete'}, true); },	 //Delete: delete /.bad
		'P': function() { UI.fileActionAjax({action:'move', to:'..'}, true); },	 //P: move to parent
		'T': function() { UI.slideshow.nextTransition(); },	//T: next transition
		'C': function() { UI.slideshow.toggleControls(); },	//C: controls
		'Z': function() { UI.slideshow.toggleZoom(); }, //Z: Zoom
		'X': function() { UI.slideshow.toggleAnimate(); },

		//B: borders, M: Margin
		'B': function() { UI.toggleOption("border"); },
		'M': function() { UI.toggleOption("margin"); },
		'S': function() { UI.toggleOption("shadow"); },		
		'R': function() { UI.toggleOption("rotate"); },

		107: function() { MediaPlayer.slide.nextSize(1) },
		109: function() { MediaPlayer.slide.nextSize(-1) },

		173: function() { MediaPlayer.slide.nextSize(-1); },
		61: function() { MediaPlayer.slide.nextSize(1); },
		189: function() { MediaPlayer.slide.nextSize(-1); },
		187: function() { MediaPlayer.slide.nextSize(1); }
	},
	"video": 
	{
		' ': function()
		{ 
			if(!MediaPlayer.video.playerFocus())
				MediaPlayer.video.togglePlay();
		},
		39: function() { MediaPlayer.video.playNext(); },
		37: function() { MediaPlayer.video.playPrevious(); },
		'L': function() { MediaPlayer.video.togglePlaylist(); },

		107: function() { MediaPlayer.video.nextSize(1) },
		109: function() { MediaPlayer.video.nextSize(-1) },

		173: function() { MediaPlayer.video.nextSize(-1); },
		61: function() { MediaPlayer.video.nextSize(1); },
		189: function() { MediaPlayer.video.nextSize(-1); },
		187: function() { MediaPlayer.video.nextSize(1); },

		'V': function() { UI.setPrevMode(); },		// V key: same
		// ESC key: back to index, close player?
		27 : function()
		{ 
			// if just exiting fullscreen player with ESC, do not go back to index
			if(!UI.preventEsc) 
				 UI.setPrevMode();
			//do it next time
			UI.preventEsc=false;
		}
	},
//common keys in all modes
	"common":
	{
	//M: play/pause music function(state, duration, effect, direction, callback)
		'H': function() { UI.toggleOption("titleContainer"); },	//header
		' ': function() { UI.slideshow.display();  },	 //SPACE: slideshow
//		' ': function() { if(window.MediaPlayer && MediaPlayer.audio) MediaPlayer.audio.togglePlay(); },
		'A': function() { if(window.MediaPlayer && MediaPlayer.audio) MediaPlayer.audio.togglePlay(); },
	//N: next music track
		'N': function() { if(window.MediaPlayer && MediaPlayer.audio) MediaPlayer.audio.playNext(); },
		'P': function() { if(window.MediaPlayer && MediaPlayer.audio) MediaPlayer.audio.playPrevious(); },
		'L': function() { if(window.MediaPlayer && MediaPlayer.audio) MediaPlayer.audio.togglePlaylist(); },
		'I': function() { UI.displayBrowserInfo(); },
	//V: video player
		'V': function() { UI.playAllVideos() },
	//F: Facebook
//		'F': function() { if(window.toggleFacebookComments) toggleFacebookComments(); }
		'F': function() { UI.toggleOption("downloadFileList"); },

		// numpad:	107:+	109:-	106:*	111:/(FF:quick find)
		107: function() { UI.zoom(); },
		109: function() { UI.zoom(-1); }
	}
};

//handle keyup events
UI.handleKeys = function(event)
{
	var target=$(event.target);
	if(target.is(":text, .description")) return;

	var keyCode=event.keyCode;
	if(!keyCode) keyCode=event.which;
	var chr = String.fromCharCode(keyCode);

	var callback = UI.keys[UI.mode][keyCode] || UI.keys[UI.mode][chr] || UI.keys.common[keyCode] || UI.keys.common[chr];
	if(callback) 
		callback(keyCode);

//	UI.setStatus("mode:{5} e.k:{0} e.w:{1} e.c:{2} => kC{3} chr:{4}".format(event.keyCode, event.which, event.charCode, keyCode, chr, UI.mode));
};

UI.setupKeyboard = function()
{
	$(document).keypress(UI.preventKeys);	
	$(document).keyup(UI.handleKeys);
	//prevent changing drop down value when scrolling page after selecting option
	$("select").change(function() { this.blur(); });
};
