<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/captchalogue.php');

?>

<h1>Difarem's Increnible Captchalogue Code Arithmetic Debugger™</h1>
<form target="" method="POST">
	<p>Code 1: <input name="code1"></p>
	<p>Code 2: <input name="code2"></p>
	<input type="submit" value="DO THE COMBINEY THING">
</form>

<style>
	img {
		width: 160px;
		height: auto;
	}
</style>

<?php

if (isset($_POST['code1']) && isset($_POST['code2'])) {
	$code1 = Code::from_string($_POST['code1']);
	$code2 = Code::from_string($_POST['code2']);
	
	echo '<p>Code 1: '.$code1->to_string().'<img src="/util/render_card.php?code='.urlencode($code1->to_string()).'">';
	echo 'Code 2: '.$code2->to_string().'<img src="/util/render_card.php?code='.urlencode($code2->to_string()).'"></p>';
	
	$and = $code1->and($code2);
	echo '<p>'.$code1->to_string().' && '.$code2->to_string().' => '.$and->to_string().'<img src="/util/render_card.php?code='.urlencode($and->to_string()).'"></p>';
	
	$or = $code1->or($code2);
	echo '<p>'.$code1->to_string().' || '.$code2->to_string().' => '.$or->to_string().'<img src="/util/render_card.php?code='.urlencode($or->to_string()).'"></p>';
	
	$xor = $code1->xor($code2);
	echo '<p>'.$code1->to_string().' ^^ '.$code2->to_string().' => '.$xor->to_string().'<img src="/util/render_card.php?code='.urlencode($xor->to_string()).'"></p>';
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php');
?>
