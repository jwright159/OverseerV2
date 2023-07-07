<br>
<?php
if (!empty($_SESSION['inv']) && !empty($_SESSION['imeta'])) {
	$newinv = mysqli_real_escape_string($connection, implode("|", $_SESSION['inv']) . "|");
	$newmeta = mysqli_real_escape_string($connection, implode("|", $_SESSION['imeta']) . "|");
} else {
	$newinv = "";
	$newmeta = "";
}
if ($newinv != $charrow['inventory'] || $newmeta != $charrow['metadata']) { //inventory was changed on this page, update it in the database
	mysqli_query($connection, "UPDATE Characters SET inventory = '$newinv', metadata = '$newmeta' WHERE ID = $cid");
}

checkNotifications($charrow);
$symbol = "'/" . $me->symbol . "'";
$background='no';
if ($charrow['dreamingstatus']=='Prospit') $background='prospit';
elseif ($charrow['dreamingstatus']=='Derse') $background='derse';
?>
			</div><?php // id: content-area ?>
			<footer>
				<?php
					if (!empty($_SESSION['character'])) {
						echo "Fatigue($me->wakefatigue) Dreamself fatigue($me->dreamfatigue)<br>";
						if ($me->wakefatigue > 1025) echo "Waking fatigue penalty: " . (($me->wakefatigue - 1025) / 10) . "%<br>";
						if ($me->dreamfatigue > 1025) echo "Dreamself fatigue penalty: " . (($me->dreamfatigue - 1025) / 10) . "%<br>";
					}
				?>
				Page generated in <span id="pagegentime">???</span> seconds and loaded in <span id="pageloadtime"><span style="color: blue;">calculating </span></span>ms.
			</footer>
		</div><?php // id: content-container ?>
	</div><?php // id: layout-container ?>
	<svg width=400 height=220 style="position: fixed; left: 0px; top: 0px; pointer-events: none;">
		<defs>
			<mask id="maskedtext">
				<rect x="0" y="0" width="100%" height="100%" fill="white"/>
				<circle cx=81.5 cy=110 r=112 />
			</mask>
		</defs>
		<circle cx=339 cy=143 r=5 fill="#FFF" pointer-events="all" />
		<rect x=140 y=115 width=153 height=33 fill=#ccc pointer-events="all" />
		<rect x=200 y=115 width=144 height=18 fill=#ccc pointer-events="all" />
		<rect x=200 y=125 width=144 height=18 fill=#FFF pointer-events="all" />
		<rect x=186 y=125 width=153 height=23 fill=#FFF mask="url(#maskedtext)" pointer-events="all" />
		<circle cx=359 cy=88 r=34 fill="#72E655" pointer-events="all" />
		<circle cx=81.5 cy=110 r=106.5 fill="#72E655" pointer-events="all" />
		<rect x=0 y=0 width=359 height=122 fill=#72e655 pointer-events="all" />
		<rect x=0 y=0 width=393 height=88 fill=#72e655 pointer-events="all" />
	</svg>

	<script src="/js/jquery.min.js"></script>
	<script src="/js/nanoscroller.js"></script>
	<script type="text/javascript" src="/js/jquery.tooltipster.min.js"></script>

	<?php
	//announcement code

	$system = mysqli_query($connection, "SELECT * from `System`;");
	$systemarray = mysqli_fetch_array($system);
	$announcements = explode("|", urldecode($systemarray['announcements']));
	$once = false;
	if($announcements[0]!=''){
		array_pop($announcements);
		foreach($announcements as $announcement){
			$announce = explode("@", $announcement);
			if(strtotime("now")>$announce[1] && strtotime("now")<$announce[2] && !$once){
				echo '<div id="announcement-container">
				<div class="nano" id ="announcement">
				<div class="nano-content"><strong><center>ANNOUNCEMENTS</center></strong>';
				$once=true;
			}
			if(strtotime("now")>$announce[1]  && strtotime("now")<$announce[2])
				echo stripcslashes($announce[0]) . '<br>';
		}
		if($once) echo '</div></div></div>';
	}
	?>


	<div id="avatar" style="background: url(<?php echo  $symbol; ?>) no-repeat center center, white;"></div>
	<div style="position: fixed; top: 160px; left: 0px; width: 162px; height: 26px; text-align: center;">
		<div style="position: relative; background-color:#f3f3f3; display: inline;padding: 3px; border-radius: 2px; box-shadow: 3px 3px 0 rgba(0,0,0,0.3); white-space: nowrap;"><?php echo $me->class . " of " . $me->aspect; ?></div>
	</div>
	<div class="statbox" id="charactername" style="text-overflow: clip; white-space: nowrap; overflow: hidden;"><?php echo $me->name; ?></div>
	<a href="/"><div id="button-charswitch"></div></a>
	<div class="statbox" id="echeladder" style="text-overflow: clip; white-space: nowrap; overflow: hidden;"><a href="/abilities.php"><img src="/images/header/echeladder.png"> <?php echo $me->echeladder; ?></a></div>
	<div class="statbox" id="powerlevel" style="text-overflow: clip; white-space: nowrap; overflow: hidden;"><a href="/portfolio.php"><img src="/images/header/powerlevel.png"> <?php echo $me->strife->power; ?></a></div>
<?php if ($me->inmedium) { ?>
	<div style="position: fixed; top: 47px; left: 177px; width: 164px;" class="statbar"><div id="healthbar" class="statbarinner" style="width: <?php echo $me->strife->healthpercent; ?>%;"></div></div>
	<div style="position: fixed; top: 78px; left: 202px; width: 139px;" class="statbar"><div id="aspectbar" class="statbarinner" style="width: <?php echo $me->strife->energypercent; ?>%;"></div></div>
	<div style="position: fixed; left: 145px; top: 41px; width: 36px; height: 36px; border-radius: 18px; background-color: #ffffff; text-align:center; vertical-align: center;" title="Health: <?php echo ceil($me->strife->healthpercent); ?>% [<?php echo $me->strife->health.'/'.$me->strife->maxhealth; ?>]">
		<img src="/images/header/healthchum.png" style="margin-top: 3px;">
	</div>
	<div style="position: fixed; left: 170px; top: 72px; width: 36px; height: 36px; border-radius: 18px; background-color: #ffffff; text-align:center; vertical-align: center;" title="Aspect: <?php echo ceil($me->strife->energypercent); ?>% [<?php echo $me->strife->energy.'/'.$me->strife->maxenergy; ?>]">
		<img src="/images/symbols/aspect_<?php echo strtolower($me->aspect); ?>.png" style="width: 100%; height: 100%;">
	</div>
<?php } ?>
	<a href="/porkhollow.php"><img src="/images/header/boondollars.png" style="position: fixed; left: 189px; top: 123px;"></a>
	<div style="position: fixed; left: 216px; top: 128px;"><?php echo $me->boondollars; ?></div>
	<span id="overseerlogo"></span>
	<div id="navbar" style="position: fixed; top: 0px; left: 358px; height: 75px; background-color: white; border-radius: 0 0 8px 38px;">
		<nav style="margin: 10px;">
			<ul>
				<li class="navbutton"><a href="#"><img src="/images/header/chummy.png" title="Player"></a>
					<ul>
						<li><a href="/overview.php">Character Profile</a></li>
						<li><a href="/logread.php">Character Log</a></li>
						<li><a href="/sprite.php">Sprite</a></li>
					</ul>
				</li>
				<li class="navbutton"><a href="#"><img src="/images/header/rancorous.png" title="Strife"></a>
					<ul>
						<li><a href="/strifedisplay.php">Strife!</a></li>
						<li><a href="/portfolio.php">Portfolio</a></li>
						<li><a href="/abilities.php">Abilities</a></li>
						<li><a href="/aspectpatterns.php">Aspect Patterns</a></li>
						<li><a href="/fraymotifs.php">Fraymotifs</a></li>
					</ul>
				</li>
				<li class="navbutton"><a href="#"><img src="/images/header/compass.png" title="Exploration"></a>
					<ul>
						<li><a href="/dungeons.php">Dungeons</a></li>
						<li><a href="/consorts.php">Consorts</a></li>
						<li><a href="/mercenaries.php">Consort Mercenaries</a></li>
					</ul>
				</li>
				<li class="navbutton"><a href="/gristwire.php"><img src="/images/header/gristy.png" title="Grist"></a></li>
				<li class="navbutton"><a href="#"><img src="/images/header/inventory.png" title="Inventory"></a>
					<ul>
						<li><a href="/inventory.php">Inventory</a></li>
						<li><a href="/alchemy.php">Alchemy</a></li>
						<li><a href="/quickitemcreate.php">Quick Item Creator</a></li>
						<li><a href="/submissions.php">Item Submissions</a></li>
						<li><a href="/submitart.php">Art Submitter</a></li>
					</ul>
				</li>
				<li class="navbutton"><a href="#"><img src="/images/header/atheneum.png" title="Atheneum"></a>
					<ul>
						<li><a href="/atheneum.php">Atheneum</a></li>
						<li><a href="/catalogue.php">Item Catalogue</a></li>
						<li><a href="/itemlist.php">Item List</a></li>
					</ul>
				</li>
		 		<li class="navbutton"><a href="#"><img src="/images/header/spirograph.png" title="SBURB"></a>
					<ul>
						<li><a href="/sburbserver.php">SBURB Server</a></li>
						<li><a href="/sburbdevices.php">SBURB Client</a></li>
						<li><a href="/sessionadmin.php">SBURB Administrative Console</a></li>
					</ul>
				</li>
				<li class="navbutton"><a href="#"><img src="/images/header/pester.png" title="Social"></a>
					<ul>
						<li><a href="/sessioninfo.php">Session Viewer</a></li>
						<li><a href="/sessionstats.php">Session Stats</a></li>
						<li><a href="/sessionmates.php">Session Mates</a></li>
						<li><a href="/chainviewer.php">Chain Viewer</a></li>		
					</ul>
				</li>

				<li class="navbutton"><a href="/dreamtransition.php"><img src="/images/header/sleep.png" title="Sleep"></a></li>
				<li class="navbutton"><a href="#"><img src="/images/header/whatpumpkin.png" title="Meta Stuff"></a>
					<ul>
						<li><a href="/changelog.php">Changelog</a></li>
						<li><a href="/abilityscan.php">New Ability Scanner</a></li>
						<li><a href="/devtools/rewards.php">Gift items</a></li>
						<li><a href="/devtools/logviewer.php">Log viewer</a></li>
						<li><a href="/devtools/debuglog.php">Debug log</a></li>
						<li><a href="/devtools/cheatpolice.php">Cheat log</a></li>
						<li><a href="/devtools/itemedit.php">Fabricate objects</a></li>
						<li><a href="/devtools/art.php">Art approver</a></li>
						<li><a href="/devtools/announcer.php">Announcer</a></li>
					</ul>
				</li>
			</ul>
		</nav>
	</div>
	<!-- Achievement overlay -->
	<script>
        $(document).ready(function() {
            $('.tooltip').tooltipster({
                minWidth: 250,
                offsetY: 40,
                offsetX: -10,
                contentAsHTML: true
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('.tooltip2').tooltipster({
                theme: 'my-custom-theme',
                minWidth: 250,
                offsetY: 40,
                offsetX: -10,
                contentAsHTML: true
            });
        });
    </script>

    <!-- Derse/Prospit backgrounds -->
    <script type="text/javascript">
    $(document).ready(function() {
        $('body').css('background-image', 'url(/images/Backgrounds/<?php echo $background;?>background.jpg)');
    	});
	</script>	

	<!-- Scrollbars for banner-->
	<script> $(".nano").nanoScroller(); </script>

	<script type="text/javascript">
		document.getElementById('pagegentime').innerHTML = '<?php $loadfinishtime = explode(' ', microtime()); $loadfinishtime = $loadfinishtime[1] + $loadfinishtime[0]; echo round(($loadfinishtime - $loadtime), 4);?>';
		window.onload = function () { setTimeout( function () { document.getElementById('pageloadtime').innerHTML = (performance.timing.loadEventEnd - performance.timing.requestStart); }, 1000) }
	</script>
<?php 
// Save the character object.  Does nothing if there is nothing changed.
$me->save();

// More of this epic fancy error code handler stuff.
if ((($_SERVER['HTTP_HOST'] == "dev.overseer2.com") || (explode(":", $_SERVER['HTTP_HOST'])[0] == "localhost")) && isset($errorlog)) echo '<div style="background-color: #F88; padding: 5px; margin: 10px; border-radius: 8px;">' . $errorlog . '</div>';
require_once "bugcatcher.php"; ?>
	</body>
</html>
