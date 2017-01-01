// HTML elements are global.
var draw = document.getElementById("canvas").getContext("2d");
var work = document.getElementById("working");
// loads image, but doesn't actually. it makes an invisible temporary image with the source so that the browser downloads it, but it doesn't block until the image is downloaded.
// make sure to add a .onload hook to the returned object, it'll call it when it's done.
function game(map) {
	// Stores game specific variables.
	this.map=map;
	this.yoffset=0;
	this.xoffset=0;
	this.keys=[];
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
		window.draw.putImageData(this.map,this.xoffset,this.yoffset);
		if (this.keys["w"] && this.yoffset<0) this.yoffset++;
		if (this.keys["s"] && this.yoffset>-map.height+canvas.clientHeight) this.yoffset--;
		if (this.keys["a"] && this.xoffset<0) this.xoffset++;
		if (this.keys["d"] && this.xoffset>-map.height+canvas.clientWidth) this.xoffset--;
	}.bind(this);
	window.setInterval(this.tick,12);
}
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
	return drawer.getImageData(0,0,work.width,work.height);
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
	//Generate a global tilesheet from our now loaded image, and then a global map from the tilesheet.
	window.tiles=tilesheet(a,16);
	window.map=randommap(window.tiles,32);
	// Key listeners save current key state into global variable keys.
	window.game = new game(window.map);
}








