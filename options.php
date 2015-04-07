<div id="optionsContainer" class="noprint footerLeftCorner">
<?php if(!isMobile()) {?>
	<div class="fixedLeft left">
		<img class="iconTrans" id="zoomOutIcon" alt="zoom -" src="icons/zoom-out.png" onclick="UI.zoom(-1);"/>
		<br/>
		<img class="iconTrans" id="scrollerIcon" src="icons/media-down.png" alt="play" title="play (space)" onclick="UI.scrollPages()"/>
		<div class="upload">
			<input id="cb_select_all" type="checkbox" label="X" title="Select All/none"/>
		</div>
	</div>
	<div class="fixedRight right">
		<input id="cb_titleContainer" type="checkbox" class="lOption" label="H" title="Header"/>
		<input id="cb_downloadFileList" type="checkbox" class="lOption" label="Files" title="Files"/>
		<br/>
		<img class="iconTrans" id="zoomInIcon" alt="zoom +" src="icons/zoom-in.png" onclick="UI.zoom();"/>
		<br/>
		<img class="iconTrans" id="rotatorIcon" src="icons/media-play64.png" alt="play" title="rotate pages (P)" onclick="UI.rotatePages()"/>
	</div>
<?php }?>

	<div class="floatL toolbar">
<?php if(isMobile()) {?>
		<img class="iconTrans" id="zoomInIcon" style="height: 15mm;" alt="zoom +" src="icons/zoom-in.png" onclick="UI.zoom();"/><br/>
<?php }?>
		<input id="cb_searchOptions" type="checkbox" class="lOption" label="S" title="Search" icon="icons/search16.png"/>
		<input id="cb_displayOptions" type="checkbox" class="lOption" label="O" title="Options"/>
	</div>	

	<div id="searchOptions" class="floatL toolbar translucent shadowIn hidden" direction="left">
		<div class="inlineBlock nowrap">
			Search: <input id="search_name" type="text" style="width: 150px" title="Search"/>
			<!--select id='dd_search_depth'>
				<option value="0">this dir only</option>
				<option value="1">subdirs</option>
				<option value="t">all subdirs</option>
				<option value="-1">parent dir</option>
				<option value="-10">all parent dirs</option>
			</select-->
		</div>
		<img id="searchIcon" alt="search" src="icons/search24.png"/>
		<img id="clearSearchIcon" alt="clear" src="icons/delete.png"/>
	</div>

	<div id="displayOptions" class="floatL toolbar translucent shadowIn hidden" direction="left">
		<div class="inlineBlock nowrap">
			Sort: <?php displaySortOptions("sOption");?>
			<input class="dOption" id="cb_group" type="checkbox" label="G" title="Group"/>
		</div>
		<div class="inlineBlock nowrap">
			Page: <?php displayPaginateOptions("dOption");?> files
		</div>
		<div class="inlineBlock nowrap">
			Columns: 
			<select class="dOption" id="dd_columns">
				<option value="0">no</option>
				<option>1</option>
				<option>2</option>
				<option>3</option>
				<option>4</option>
				<option>5</option>
				<option>6</option>
				<option>7</option>
				<option>8</option>
				<option>9</option>
				<option>10</option>
			</select>
			<span id="columnOptions">
				Fit:
				<select class="dOption" id="dd_fit">
					<option>height</option>
					<option>width</option>
				</select>
				<input class="dOption" id="cb_percent" type="checkbox" label="%" title="percent"/>
				<input class="dOption" id="cb_transpose" type="checkbox" label="T" title="Transpose"/>
			</span>
			<span id="rowOptions" class="spaceLeft">
				<?php displaySizeOptions("dOption");?>
			</span>
		</div>
		<div class="inlineBlock nowrap">
			<input class="dOption" id="cb_rotate" type="checkbox" label="R" title="Rotate Images"/>
			<input class="dOption" id="cb_border" type="checkbox" label="B" title="Photo border"/>
			<input class="dOption" id="cb_margin" type="checkbox" label="M" title="Margin"/>
			<input class="dOption" id="cb_caption" type="checkbox" label="C" title="Show captions"/>
			<input class="dOption" id="cb_shadow" type="checkbox" label="Sh" title="Shadow"/>
			<input class="dOption" id="cb_fadeIn" type="checkbox" label="Fa" title="Fade"/>
		</div>
	</div>
</div>
