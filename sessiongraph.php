<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/global_functions.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/database.php';

$session = unserialize(urldecode($_GET['chain']));
$characterids = $session;
$characternumber = sizeof($characterids);
$mult = $characternumber * 10;
if ($mult < 200) $mult = 200;
$radius = ($mult * 1.5) / 2;
$angle2 = deg2rad(360) / $characternumber;
$angle = 0;
$image = imagecreatetruecolor($mult * 2, $mult * 2);
$black = ImageColorAllocate($image, 0, 0, 0);
$white = ImageColorAllocate($image, 255, 255, 255);
imagefill($image, 0, 0, $white);
$fontsize = $characternumber / 40;
if ($fontsize < 5) $fontsize = 5;


imageellipse($image, $mult, $mult, $mult * 1.5, $mult * 1.5, $black);
if(sizeof($characterids) > 2)
{
	foreach($characterids as $key => $chars)
	{
		$angle3 = $angle;
		$chara = getChar($chars);
		$name = $chara['name'];
		$namewidth = ($fontsize * strlen($name) * cos(deg2rad($angle3))) / 2;
		$color = $chara['colour'];
		$playercol = ImageColorAllocate($image, hex2RGB($color)['red'], hex2RGB($color)['green'], hex2RGB($color)['blue']);
		$nameheight = ($fontsize * strlen($name) * sin($angle)) / 2;
		if((rad2deg($angle) > 90) && (rad2deg($angle) < 270))
		{
			$angle3 = deg2rad(rad2deg($angle) - 180);
			$nameheight = (-$fontsize * strlen($name) * sin($angle)) / 2;
		}
		if(rad2deg($angle) > 270)
		{
			$angle3 = deg2rad(rad2deg($angle));
		}
		$circlex = $mult + $radius * cos($angle);
		$circley = $mult + $radius * sin($angle);
		imagettftext($image, $fontsize, -rad2deg($angle3), $circlex - $namewidth, $circley - $nameheight, $playercol, dirname(__FILE__) . '/fonts/ascii.ttf', $name);
		$angle = $angle + $angle2;
	}
}
header('Content-type: image/png');
ImagePNG($image);
ImageDestroy($image);
