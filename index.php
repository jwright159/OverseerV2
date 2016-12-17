<?php
require_once('inc/database.php');
require_once('includes/global_functions.php');
require_once('inc/autoload.php');
session_start();
if (!empty($_SESSION['username'])) { // If the user is already logged in, get accrow.
	$username = $_SESSION['username'];
	$accresult = mysqli_query($connection, "SELECT * FROM `Users` WHERE `username` = '" . $_SESSION['username'] . "' LIMIT 1;");
	$accrow = mysqli_fetch_array($accresult);
}
function showloginmsg() {
	if (isset($_SESSION['loginmsg'])) {
		echo('<br>' . $_SESSION['loginmsg'] . '<br>');
		unset($_SESSION['loginmsg']);
	}
}
function getcharbgcolor($charcolor) {
	$charcolorhsl = Mexitek\PHPColors\Color::hexToHsl($charcolor);
	if ($charcolorhsl['L'] > 0.7) {
		$newL = 0.1;
		$textc = '#fff';
	} else {
		$newL = 0.9;
		$textc = '#000';
	}
	$charcolorhsl['L'] = $newL;
	return('background: #' . Mexitek\PHPColors\Color::hslToHex($charcolorhsl) . '; color: ' . $textc .';');
}
function showCaptcha() {
	global $captcha;
	$captcha = true;
	echo('<div class="g-recaptcha" data-sitekey="6LcsfQgTAAAAAKGVoQbr1nNjVrD88UnYHHrZDaxr" data-size="compact"></div>');
}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>The Overseer Project v2</title>
		<meta name="description" content="The Overseer Project is a free text-based roleplaying game based on Homestuck's SBURB system, featuring Alchemy, Strifing, Denizens, Quests and more">
		<meta name="keywords" content="homestuck,SBURB,rpg,game,browser game,simulator,roleplaying,rp,overseer project,alchemy,strifing">
		<style type="text/css">
			html, body { height: 100%; background-color: #2287ba; overflow: hidden; font-family: "Courier New", Courier, monospace;}
			form { text-align: center; }
			hr { border-color: black; }
			#leftcolumn { position: fixed; top: 0px; left: 0px; width: 179px; height: 100%; }
			#middlecolumn { position: fixed; top: 0px; left: 179px; width: calc(100% - 470px); height: calc(100% - 10px); margin-top: 3px; margin-bottom: 7px;
				background-color: #2e7496; border-radius: 8px; text-align: center;}
			#rightcolumn { position: fixed; top: 0; right: 0; height: 100%; width: 243px; margin-right: 12px;
				padding-left: 11px; padding-right: 11px; background: url(images/title/logintexture.png) repeat-y 3px, #44aed7; overflow: auto; }
			input[type="text"],input[type="password"] {
				width: calc(100% - 4px);
				display: block;
				border: 2px inset; padding: 1px;
			}
			.character { text-overflow: ellipsis; white-space: nowrap; overflow: hidden;}
			#content-container { background-color: #2287ba; width: calc(100% - 14px); position: absolute; top: 124px; bottom: 0px; margin: 7px; border-radius: 3px;}
			#content-container2 { background-color: #44aed7; border-radius: 3px; margin: 6px; margin-top: 7px; padding: 5px; position: absolute; top: 26px; bottom: 0px; width: calc(100% - 22px); }
			#navlinks { background-color: #44aed7; margin: 6px; border-radius: 3px; height: 17px; padding: 2px; }
			#navlinks a { color: white; text-decoration: none; font-size: 15px; vertical-align: top;}
			#controlbuttons { margin-top: 8px; text-align: center; }
			#charheader { background: url(/images/title/characters.png) center center no-repeat; height: 20px; margin: 15px 0; }
			#charsel { text-align: left; }
			.character { margin: 7px 0; height: 64px; border-radius: 16px; background-color: white; position: relative; }
			.charimg { position: absolute; left: 0; top: 0; width: 64px; height: 64px; border-radius: 16px; box-shadow: #66c8d9 4px 0; }
			.chartext { position: absolute; left: 68px; top: 0; margin: 0 4px; font-size: 14px; line-height: 16px; }
			#login-header { background: url(/images/title/login.png) center center no-repeat; height: 40px; margin-top: 42px; padding-bottom: 30px; }
			#news-header { height: 40px; margin: 26px 0; background: url(/images/title/news.png) center center no-repeat; }
			#ad-header { background: url(/images/title/ads.png) center center no-repeat; margin-top: 42px; margin-bottom: 16px; height: 40px; }
			#pw_adbox_71403_3_0 { margin-left: 9px; }
			.g-recaptcha { width: 164px; margin: 0 auto; }
		</style>
		<script>
		function validateChar() {
				var Class = document.forms["charForm"]["class"].value;
				var Aspect = document.forms["charForm"]["aspect"].value;
				if (Class == "Null" || Aspect == "Null" || Class == "Select..." || Aspect == "Select...") {
						alert("You need to pick a class and aspect!");
						return false;
				}
		}
		</script>
		<link href="/css/jquery.contextMenu.css" rel="stylesheet" type="text/css">
	</head>
	<body>
		<div id="leftcolumn">
			<div id="ad-header"></div>
			<!-- Project Wonderful Ad Box Code -->
			<div id="pw_adbox_71403_3_0"></div>
			<script type="text/javascript"></script>
			<noscript><map name="admap71403" id="admap71403"><area href="http://www.projectwonderful.com/out_nojs.php?r=0&c=0&id=71403&type=3" shape="rect" coords="0,0,160,600" title="" alt="" target="_blank" /></map>
			<table cellpadding="0" cellspacing="0" style="width:160px;border-style:none;background-color:#ffffff;"><tr><td><img src="http://www.projectwonderful.com/nojs.php?id=71403&type=3" style="width:160px;height:600px;border-style:none;" usemap="#admap71403" alt="" /></td></tr><tr><td style="background-color:#ffffff;" colspan="1"><center><a style="font-size:10px;color:#0000ff;text-decoration:none;line-height:1.2;font-weight:bold;font-family:Tahoma, verdana,arial,helvetica,sans-serif;text-transform: none;letter-spacing:normal;text-shadow:none;white-space:normal;word-spacing:normal;" href="http://www.projectwonderful.com/advertisehere.php?id=71403&type=3" target="_blank">Ads by Project Wonderful!  Your ad here, right now: $0</a></center></td></tr></table>
			</noscript>
			<!-- End Project Wonderful Ad Box Code -->
		</div>
		<div id="middlecolumn">
			<div style="height: 129px; width: 100%; position: relative;">
				<a href="/"><img style="position: absolute; top: 0; bottom: 0; left: 0; right: 0; margin: auto; max-width: calc(100% - 14px); padding-top: 7px; padding-left: 7px; padding-right: 7px;" src="images/title/title.png"></a>
			</div>
			<div id="content-container">
				<div id="navlinks"><a href="http://theoverseerproject.tumblr.com/">NEWS</a> <img src="images/title/smallgrist.png"> <a href="/?credits">CREDITS</a> <img src="images/title/smallgrist.png"> <a href="http://forums.overseer2.com/">FORUM</a> <img src="images/title/smallgrist.png"> <a href="http://the-overseer.wikia.com/">WIKI</a> <img src="images/title/smallgrist.png"> <a href="/?changelog">CHANGELOG</a> <img src="images/title/smallgrist.png"> <a href="http://forums.overseer2.com/viewforum.php?f=5">BUGS</a> <img src="images/title/smallgrist.png"> <a href="/?faq">FAQ</a> <img src="images/title/smallgrist.png"> <a href ="http://theoverseerproject.tumblr.com/ask">HELP</a></div>
				<div id="content-container2">
<?php if (isset($_GET['changelog'])) include($_SERVER['DOCUMENT_ROOT'] . '/inc/title/changelog.html');
elseif (isset($_GET['about'])) include($_SERVER['DOCUMENT_ROOT'] . '/inc/title/about.html');
elseif (isset($_GET['faq'])) include($_SERVER['DOCUMENT_ROOT'] . '/inc/title/faq.html');
elseif (isset($_GET['credits'])) include($_SERVER['DOCUMENT_ROOT'] . '/inc/title/credits.html');
			else { // no other pages requested so they must want the news! ?>
					<div id="news-header"></div>
					<div id="news" style="overflow-y: scroll; height: calc(100% - 92px); width: 100%; background-color: #66c8d9; border-radius: 3px;"><?php include($_SERVER['DOCUMENT_ROOT'] . '/inc/tumblr.php'); ?></div>
<?php } ?>
				</div>
			</div>
		</div>
		<div id="rightcolumn">
<?php if (empty($_SESSION['username'])) { // If the user isn't logged in...
			if (isset($_GET['register'])) { // If the user is asking for the registration page... ?>
			<?php showloginmsg(); ?>
			<form action="addaccount.php" method="post">
				Username: <input id="username" name="username" type="text" required autofocus><br>
				Password: <input id="password" name="password" type="password" required><br>
				Confirm password: <input id="confirmpw" name="confirmpw" type="password" required><br>
				Email: <input id="email" name="email" type="text"><br>
				Confirm email: <input id="cemail" name="cemail" type="text"><br>
				Note: The Overseer Project uses these emails for the sole purpose of account recovery, should you forget your password. We will never give your email to any third parties, or send you anything without your permission.<br>
				You can always change your email through the Account Settings page.<br>
<?php showCaptcha(); ?>
				<input type="submit" value="Register">
			</form>
<?php } else { // Nothing else is being requested and the user isn't trying to register, so make them log in! ?>
			<div id="login-header"></div>
<?php showloginmsg(); ?>
			<form id='login' action='login.php' method='post'>
				<input type="text" id="username" name="username" placeholder="Username" autofocus>
				<input type="password" id="password" name="password" placeholder="Password">
				<input type="submit">
			</form>
			<br>
			<a href ='resetpass.php'>Forgot your password?</a><br>
			<a href="/?register"><img src="/images/title/registertext.png"></a>
<?php } } else { // The user is logged in now, so...
	if (isset($_GET['newsession'])) { // If the user wants to register a session, we give them the session form. ?>
			<?php showloginmsg(); ?>
			<form action="addsession.php" method="post">
				Session name: <input id="sessionname" name="sessionname" type="text"><br>
				Password: <input id="password" name="password" type="password"><br>
				Confirm password: <input id="confirmpw" name="confirmpw" type="password"><br>
			<input type="submit" value="Create session">
			</form>
<?php } else if (isset($_GET['newchar'])) { // If the user wants to make a new character, we give them the character form. ?>
			<?php showloginmsg(); ?>
			<form name="charForm" action="addcharacter.php" onsubmit="return validateChar()" method="post"> Character name: <input id="charname" name="charname" type="text" /><br />
				Session name: <input id="session" name="session" type="text"><br>
				Session password: <input id="sessionpw" name="sessionpw" type="password"><br>
				Select class:<select name="class">
					<option value="Null">Select...</option>
				<?php
				$class_result = mysqli_query($connection, "SELECT Class, passivefactor, activefactor FROM Class_modifiers;");
				while($class_row = mysqli_fetch_array($class_result)) {
					if($class_row[0] == 'Default') continue;
					echo '<option value="'.$class_row[0].'">';
					echo $class_row[0];
					if($class_row[1] > $class_row[2]) {
						echo ' (Passive, '.$class_row[1].'%)';
					} else {
						echo ' (Active, '.$class_row[2].'%)';
					}
					echo '</option>';
				}
				?>
				</select><br>
				Select aspect:<select name="aspect">
					<option value="Null">Select...</option>
				<?php
				$aspect_result = mysqli_query($connection, "SELECT Aspect FROM Aspect_modifiers;");
				while($aspect_row = mysqli_fetch_array($aspect_result)) {
					echo '<option value="'.$aspect_row[0].'">';
					echo $aspect_row[0];
					echo '</option>';
				}
				?>
					</select><br>
				Dreaming status: <select name="dreamer">
					<option value="Unawakened">Unawakened</option>
					<option value="Prospit">Prospit</option>
					<option value="Derse">Derse</option>
				</select><br>
				<input type="submit" value="Create character" />
			</form>
<?php } else { // The user OBVIOUSLY doesn't want any other pages, and they're already logged in, so we give them the character select page. ?>
			<div id="controlbuttons">
				<a href="/?newchar"><div style="width: 64px; height: 64px; border-radius: 18px; background: url(images/title/addchar.png) no-repeat center center, #b8e0ef; display: inline-block;"></div></a>
				<a href="/?newsession"><div style="width: 64px; height: 64px; border-radius: 18px; background: url(images/title/addsession.png) no-repeat center center, #b8e0ef; display: inline-block;"></div></a>
				<a href="/logout.php"><div style="width: 64px; height: 64px; border-radius: 18px; background: url(images/title/logout.png) no-repeat center center, #b8e0ef; display: inline-block;"></div></a>
			</div>
			<div id="charheader"></div>
<?php showloginmsg(); ?>
			<div id="charsel">
				<?php
				$chars = explode("|", $accrow['characters']);
				$i = 0;
				$charquery = "SELECT `ID`,`name`,`session`,`symbol`,`colour` FROM Characters WHERE ";
				$foundone = false;
				while (!empty($chars[$i])) {
								$foundone = true;
								$charquery .= "ID = " . strval($chars[$i]) . " OR ";
								$i++;
				}
				if ($foundone) {
								$charquery = substr($charquery, 0, -4);
								$charresult = mysqli_query($connection, $charquery);
								while ($row = mysqli_fetch_array($charresult)) {
												if (empty($sname[$row['session']])) {
																$sesrow = loadSessionrow($row['session']);
																$sname[$row['session']] = $sesrow['name'];
												} 
				$symbol = "'" . $row['symbol'] . "'";?>
				<a title="<?php echo($sname[$row['session']]); ?>" href="/changechar.php?c=<?php echo($row['ID']); ?>">
					<div class="character" charid="<?php echo($row['ID']); ?>" style="<?php echo(getcharbgcolor('#'.$row['colour'])); ?>;">
						<div class="charimg" style="background: url(<?php echo($symbol); ?>) center center no-repeat, #fff;"></div>
						<div class="chartext">
							<span style="text-decoration: underline;">Name</span>:<br>
							<span style="color: <?php echo('#'.$row['colour']); ?>;"><?php echo($row['name']); ?></span><br>
							<span style="text-decoration: underline;">Session</span>:<br>
							<?php echo($sname[$row['session']]); ?>
						</div>
					</div>
				</a>
		
<?php } ?>
To delete a character, right click it and select delete.
				</div>
<?php } else { // Account has no characters ?>
			<div>No characters found</div>
<?php } } } ?>
		</div>
		<script src="/js/jquery.min.js"></script>
		<script src="/js/jquery.ui.position.js"></script>
		<script src="/js/jquery.contextMenu.js"></script>
		<script type="text/javascript">
			$.contextMenu({
				selector: '.character',
				callback: function(key, options) {
					switch (key) {
						case 'delete':
							var m = "/delchar.php?c=" + $(this).attr('charid');
							window.location = m;
							break;
					}
				},
				items: {
					"delete": {name: "Delete"}
				}
			});
		</script>
<?php if (isset($captcha) && ($captcha == true)) echo("    <script src='https://www.google.com/recaptcha/api.js'></script>\n"); ?>
<!-- Project Wonderful Ad Box Loader -->
<script type="text/javascript">
	 (function(){function pw_load(){
			if(arguments.callee.z)return;else arguments.callee.z=true;
			var d=document;var s=d.createElement('script');
			var x=d.getElementsByTagName('script')[0];
			s.type='text/javascript';s.async=true;
			s.src='//www.projectwonderful.com/pwa.js';
			x.parentNode.insertBefore(s,x);}
	 if (window.attachEvent){
		window.attachEvent('DOMContentLoaded',pw_load);
		window.attachEvent('onload',pw_load);}
	 else{
		window.addEventListener('DOMContentLoaded',pw_load,false);
		window.addEventListener('load',pw_load,false);}})();
</script>
<!-- End Project Wonderful Ad Box Loader -->
<!-- Google Analytics -->
<script>
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

	ga('create', 'UA-77908832-1', 'auto');
	ga('send', 'pageview');

</script>
<!-- End Google Analytics -->
	</body>
</html>
