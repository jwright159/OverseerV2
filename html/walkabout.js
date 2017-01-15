// HTML elements are global.
var canvas = document.getElementById("canvas")
var draw = canvas.getContext("2d");
var work = document.getElementById("working");

// The big thing. Defining one of these will start the game with the specified map.
// Metadata will presumably be attached to the map, later.
function game(map) {
	// Stores game specific variables.
	// Set proper canvas resolution for pov.
	var canvas= document.getElementById("canvas");
	canvas.height=map.tiles.size*16;
	canvas.width=map.tiles.size*16;	
	this.mode=1;
	// Init variables
	this.map=map;
	this.tiles=map.tiles;
	this.yoffset=0;
	this.xoffset=0;
	this.keys=[];
	this.redraw=[1,1];
	// Key listeners save current key state into global variable keys.
	this.keysdown = function keysdown(e) {
		//console.log("keypressed " + e.key)
		this.keys[e.key]=1;
	}.bind(this);
	this.keysup = function keysup(e) {
		this.keys[e.key]=0;
	}.bind(this);
	document.onkeydown=this.keysdown;
	document.onkeyup=this.keysup;
	this.tick=function() {
		// Welcome to the main event loop!
		// Rendering...
		if (this.redraw[0]!=this.yoffset || this.redraw[1]!=this.xoffset) {
			if (this.mode) {
				window.draw.putImageData(this.map,this.xoffset,this.yoffset);
			} else {
				this.slowrender();
			}
			this.redraw=[this.yoffset,this.xoffset];
		}
		if (this.keys["w"] && this.yoffset<0) this.yoffset++;
		if (this.keys["s"] && this.yoffset>-map.height+canvas.height) this.yoffset--;
		if (this.keys["a"] && this.xoffset<0) this.xoffset++;
		if (this.keys["d"] && this.xoffset>-map.height+canvas.width) this.xoffset--;
	}.bind(this);
	this.slowrender= function() {
		var ytiles=canvas.height/this.tiles.size;
		var xtiles=canvas.width/this.tiles.size;
		var xot=-this.xoffset/this.tiles.size;
		var yot=-this.yoffset/this.tiles.size;
		for (var x=0; x<xtiles;x++) {
			for (var y=0;y<ytiles;y++) {
				draw.putImageData(this.tiles[this.map.tiledata[Math.round(x+xot)][Math.round(y+yot)]],x*this.tiles.size,y*this.tiles.size);
			}
		}
	}.bind(this);
	window.setInterval(this.tick,12);

}

// loads image, but doesn't actually. it makes an invisible temporary image with the source so that the browser downloads it, but it doesn't block until the image is downloaded.
// make sure to add a .onload hook to the returned object, it'll call it when it's done.
function loadimage(uri) {
	img = document.createElement("img");
	img.style="display:none";
	img.src=uri;
	return img;
}
// returns array of imagedata given the img (element) of the tiles, and the height/width of an individual tile.
function tilesheet(img,squaresize) {
	work.width=img.width;
	work.height=img.height;
	drawer = work.getContext("2d");
	drawer.drawImage(img,0,0);
	tiles=[]
	var count=0;
	for (var x = 0;x<img.width;x+=squaresize) {
		for (var y = 0; y<img.height;y+=squaresize) {
			tiles[count]=drawer.getImageData(x,y,squaresize,squaresize);
			count++;
		}
	}
	console.log("Loaded " + count + " tiles!")
	tiles.size=squaresize;
	return tiles;
}
// returns imagedata for a map given a 2d array of tile indexes and a tileset.
function genmap(tiles,map) {
	work.width=map.length*tiles.size;
	work.height=map[0].length*tiles.size;
	drawer=work.getContext("2d");
	for (var x=0;x<map.length;x++) {
		for (var y=0;y<map[0].length;y++) {
			drawer.putImageData(tiles[map[x][y]],x*tiles.size,y*tiles.size);
		}
	}
	pic = drawer.getImageData(0,0,work.width,work.height);
	pic.width=work.width;
	pic.height=work.height;
	pic.tiles=tiles;
	pic.tiledata=map;
	return pic;
}

// temporary function
function randommap(tiles,size) {
	map = [];
	for (var x=0;x<size;x++) {
		map[x]=[];
		for (var y=0; y<size;y++) {
			map[x][y]=Math.round(Math.random()*(tiles.length-1));
		}
	}
	return genmap(tiles,map);
} 
a = loadimage("spritesheet.png");
a.onload=function(){
	// why does canvas have built in antialiasing
	window.game = new game(randommap(tilesheet(a,16),32));
}








