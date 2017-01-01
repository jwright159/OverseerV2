<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/captchalogue.php');

$base = imagecreatefrompng("base_captchalogue.png");
imagesavealpha($base, true);
imagealphablending($base, false);

$blackp = imagecolorallocatealpha($base, 0, 0, 0, 0);
// TODO: find a way to make this actually transparent
$transparent = imagecolorallocatealpha($base, 255, 255, 255, 0);

if (isset($_GET['code'])) {
	// render the punched holes
	$code = Code::from_string($_GET['code']);
	
	for($col = 0; $col < 4; $col++) {
		for($row = 0; $row < 2; $row++) {
			$digit = $code->digits[$col*2 + $row];
			for($bit = 0; $bit < 6; $bit++) {
				if (($digit >> (5 - $bit)) & 1 == 1) {
					$x = 43 + $col*44;
					$y = 83 + ($row*6 + $bit)*22;

					imagefilledrectangle($base, $x, $y, $x+35, $y+11, $blackp);
					imagefilledrectangle($base, $x+2, $y+2, $x+33, $y+9, $transparent);
				}
			}
		}
	}
}

header("Content-type: image/png");
imagepng($base);
imagedestroy($base);

?>
