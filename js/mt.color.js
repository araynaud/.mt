//class to handle and convert RGBa colors
function Color(data)
{
	this.rgbArray = [0,0,0,1];
	//if data is a Color object: create a copy copy its rgbArray
	if(Object.isInstanceOf(data,"Color"))
		this.rgbArray = data.rgbArray.slice(0);
	else if(isArray(data))
		this.rgbArray = Color.array2Int(data);
	else if(isString(data) && data.startsWith("rgb"))
		this.rgbArray = Color.parseRGBA(data);
	else if(isString(data))
		this.rgbArray = Color.parseHex(data);
};

Color.R=0;
Color.G=1;
Color.B=2;
Color.A=3;
Color.Max = 255;

//create a copy of a color.
Color.clone = function(c)
{
	//copy rgbArray
	return new Color(c);
};

//test equality
Color.equals = function(c1,c2)
{
	//copy rgbArray
	return c1.toHexARGB() == c2.toHexARGB();
};

Color.prototype.equals = function(c2)
{
	//copy rgbArray
	return Color.equals(this, c2);
};

//set color byte. 
//index: accept "R" or 1
//value: accept int, float or hex
Color.prototype.setByte = function(index, value)
{
	var i = valueOrDefault(Color[index], index);
	this.rgbArray[i] = value;
	return this;
};

Color.prototype.getByte = function(index, value)
{
	var i = valueOrDefault(Color[index], index);
	return this.rgbArray[i];
};

Color.parseRGBA = function(color)
{
	var rgb = color.substringAfter("(").substringBefore(")");
	var rgbArray=rgb.split(",");
	for (var i=0;i<rgbArray.length;i++)
		rgbArray[i] = parseValue(rgbArray[i]);

	return rgbArray;
};

Color.array2Int = function(rgbArray)
{
	rgbArray = rgbArray.slice(0); //clone to avoid modifying
	for (var i=0;i<rgbArray.length;i++)
		rgbArray[i] = parseValue(rgbArray[i]);
	return rgbArray;
};

Color.parseHex = function(color)
{
	if(color[0]=="#")
		color = color.substring("1");
	var len = color.length / bSize;
	var bSize = (len<=4) ? 1 : 2; //1 or 2 chars per byte?
	var colorInt = Color.hex2int(color);
	var rgbArray = [];
	for (var i=0;i<len;i++)
	{
		var b=color.substring(0,bSize);
		rgbArray[i] = Color.hex2int(b);
		color = color.substring(bSize);
	}
	return rgbArray;
};


Color.int2hex = function(b,len)
{
	b = valueOrDefault(b,0);
	len = valueOrDefault(len,2);
	var s =	b.toString(16);
	if(len < s.length) s=s.substring(0,len);
	if(s.length==1) s = "0"+s;
	return s;
};

Color.hex2int = function(hexValue)
{
	return parseInt(hexValue , 16);
};

//array to rgb() without A
Color.prototype.toRGB = function()
{
	var prefix="rgb";
	var color = "{0}({1})".format(prefix, this.rgbArray.slice(0,3).join(", "));
	return color;
};

//array to rgb() or rgba()
Color.prototype.toRGBA = function(forceA)
{
	var prefix="rgb";
	if(forceA && this.rgbArray.length==3)
		this.setByte("A",1);

	if(this.rgbArray.length==4) 
		prefix+="a";
	var color = "{0}({1})".format(prefix, this.rgbArray.join(", "));
	return color;
};

//array to hex RGB #DDDDDD
Color.prototype.toHexRGB = function()
{
	var color= "#";
	for (var i=0;i<Color.A;i++)
		color += Color.int2hex(this.rgbArray[i]);
	return color;
};

//array to hex ARGB #FFDDDDDD
Color.prototype.toHexARGB = function()
{
	var color = "#" + this.opacityHex();
	for (var i=0;i<Color.A;i++)
		color += Color.int2hex(this.rgbArray[i]);
	return color;
};

//get opacity float 0 <= 1
Color.prototype.opacity = function()
{
	if(this.rgbArray && this.rgbArray.length==4)
		return this.rgbArray[Color.A];
	return 1;
};

//get opacity int  0 <= 255
Color.prototype.opacityInt = function()
{
	return Math.round(Color.Max * this.opacity());
};

Color.prototype.opacityHex = function()
{
	return Color.int2hex(this.opacityInt());
};

Color.prototype.toHexRGB = function()
{
	var prefix="rgb";
	if(this.rgbArray.length==4) prefix+="a";
	var color = "{0}({1})".format(prefix, this.rgbArray.join(", "));
	return color;
};
