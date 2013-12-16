// ----- KEYBOARD functions --------
window.UI = window.UI || {};

//handle keypress events
var preventedKeys=[32,39]; //prevent space:page down , left: scroll right
UI.preventKeys = function(event)
{	
	var keyCode=event.keyCode;
	if(!keyCode) keyCode=event.which;
	if(!preventedKeys.contains(keyCode)) return;
//	UI.setStatus("preventKeys:  k:{0} {1} w:{2} c:{3} => {4}".format(event.keyCode, event.key, event.which, event.charCode, keyCode));
	event.preventDefault();
};

UI.preventKeyEvent = function(event)
{	
	var keyCode=event.keyCode;
	if(!keyCode) keyCode=event.which;
	UI.setStatus("preventKeys:  k:{0} {1} w:{2} c:{3} => {4}".format(event.keyCode, event.key, event.which, event.charCode, keyCode));
	event.preventDefault();
};

UI.keys = 
{
	"index":
	{
		// on index page : left and right to go to previous/next page
		 37: function() { UI.selectNextPage(-1); },	//left arrow: previous
		 39: function() { UI.selectNextPage(1); },	//right arrow: next
		'I': function() { $("#cb_reverse").toggleChecked(); },	//reverse sort
		'D': function() { $("#cb_dirsFirst").toggleChecked(); },	//sort with dirs first
		'G': function() { $("#cb_group").toggleChecked(); },	//group by sort field
		'R': function() { $("#cb_rotate").toggleChecked(); },	//rotate images
		'C': function() { $("#cb_caption").toggleChecked(); },	//captions
		'B': function() { $("#cb_border").toggleChecked(); },	//borders
		'M': function() { $("#cb_margin").toggleChecked(); },	//margins
		'T': function() { $("#cb_transpose").toggleChecked(); },	//T: transpose columns
		'O': function() { $('#cb_displayOptions').toggleChecked(); }, //options
		'S': function() { $('#cb_searchOptions').toggleChecked(); }, //search options
		'X': function() { $('#dd_sort').selectNextOption(); },
		'Z': function() { $('#dd_fit').selectNextOption(); },
		' ': function() { UI.slideshow.display();  },	 //SPACE: slideshow
		'0': UI.setColumns,
		'1': UI.setColumns,
		'2': UI.setColumns,
		'3': UI.setColumns,
		'4': UI.setColumns,
		'5': UI.setColumns,
		'6': UI.setColumns,
		'7': UI.setColumns,
		'8': UI.setColumns,
		'9': UI.setColumns,
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
		// numpad:	107:+	109:-	106:*	111:/(FF:quick find)
		107: function() { UI.zoom(); },
		109: function() { UI.zoom(-1); },
		//192:~`
		//46:delete, 45:insert
		'E': function() { UI.setColumnWidths(); } 
	},
	"slideshow":
	{
		37: function() { UI.slideshow.showNextImage(-1) },	//left arrow: previous
		39: function() { UI.slideshow.showNextImage(+1) },	//right arrow: next
		38:	function() { UI.slideshow.faster(); },	//up: interval / 2
		40:	function() { UI.slideshow.slower(); },	//down: interval * 2
		36: function() { UI.slideshow.showImage(0);	},//home: first
		35: function() { UI.slideshow.showImage(-1); },	//end: last
		27: function() { UI.setMode(); },		// ESC key: close slideshow
		32: function() { UI.slideshow.togglePlay(); },	 //SPACE: play/pause
		45: function() { UI.confirmFileAction("move","best"); },		// Insert key: move to best
		46: function() { UI.confirmFileAction("move"); },	 //Delete: delete /.bad
		'P': function() { UI.confirmFileAction("move",".."); },	 //P: move to parent
		'T': function() { UI.slideshow.nextTransition(); },	//T: next transition
		'C': function() { UI.slideshow.toggleControls(); },	//C: controls
		'Z': function() { UI.slideshow.toggleZoom(); }, //Z: Zoom
		//B: borders, M: Margin
		'B': function() { UI.toggleOption("border"); },
		'M': function() { UI.toggleOption("margin"); },
		'S': function() { UI.toggleOption("shadow"); },		
		'R': function() { UI.toggleOption("rotate"); }
	},
	"video": 
	{
		32: function() { MediaPlayer.video.togglePlay(); },
		//N: next music track
		'N': function() { MediaPlayer.video.playNext(); },
		'P': function() { MediaPlayer.video.playPrevious(); },
		'L': function() { MediaPlayer.video.togglePlaylist(); },

		107: function() { MediaPlayer.video.nextSize(1) },
		109: function() { MediaPlayer.video.nextSize(-1) },

		173: function() { MediaPlayer.video.nextSize(-1); },
		61: function() { MediaPlayer.video.nextSize(1); },
		189: function() { MediaPlayer.video.nextSize(-1); },
		187: function() { MediaPlayer.video.nextSize(1); },

		'V': function() { UI.setMode(); },		// V key: same
		// ESC key: back to index, close player?
		27 : function()
		{ 
			// if just exiting fullscreen player with ESC, do not go back to index
			if(!UI.preventEsc) 
				 UI.setMode();
			//do it next time
			UI.preventEsc=false;
		}
	},
//common keys in all modes
	"common":
	{
	//M: play/pause music function(state, duration, effect, direction, callback)
		'H': function() { UI.toggleOption("titleContainer"); },	//header
		' ': function() { if(window.MediaPlayer && MediaPlayer.audio) MediaPlayer.audio.togglePlay(); },
		'A': function() { if(window.MediaPlayer && MediaPlayer.audio) MediaPlayer.audio.togglePlay(); },
	//N: next music track
		'N': function() { if(window.MediaPlayer && MediaPlayer.audio) MediaPlayer.audio.playNext(); },
		'P': function() { if(window.MediaPlayer && MediaPlayer.audio) MediaPlayer.audio.playPrevious(); },
		'L': function() { if(window.MediaPlayer && MediaPlayer.audio) MediaPlayer.audio.togglePlaylist(); },
		'I': function() { UI.displayBrowserInfo(); },
	//S key: slideshow
//		'S': function() { UI.slideshow.display(); },		
	//V: video player
		'V': UI.playAllVideos,
	//F: Facebook
//		'F': function() { if(typeof toggleFacebookComments !=="undefined") toggleFacebookComments(); }
		'F': function() { UI.toggleOption("downloadFileList"); },		
	}
};

//handle keyup events
UI.handleKeys = function(event)
{
	var target=$(event.target);
	if(target.is(":text")) return;

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
