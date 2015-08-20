<?php
	$ret = base64_encode("//".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."&k");
	$txt = "say my name...";
	$keyfile = "admkey";
	$delay = 60*10;
	require_once("../mysql.php");
	$btxt = "check your account";
	$con = db_conn($host,$user,$pass,$db);
	$admins = array(
		intval(crc32("-0001")/100),
		intval(crc32("-0002")/100),
		intval(crc32("-0003")/100),
	);
	if(isset($_GET["admin"]) && !isset($_GET["q"]))
	{
		$n = rand(-99999,-1);
		$c = intval(crc32($n)/10000);
		file_put_contents($keyfile,time().":".$c.":".$n."\n");
		system("/bin/bash /opt/slack msg '@kamran' 'your access code is: ".$c."\nfrom: ".$_SERVER["REMOTE_ADDR"]."\n'");
	}
	if(file_exists($keyfile))
	{
		$ff = file($keyfile,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
		$cols = explode(":",$ff[0]);
		if(time() < intval($cols[0])+$delay)
			$admins[] = intval($cols[1]);
		if(isset($_GET["admin"]))
			$btxt = "valid until ".date("Y-m-d H:i:s",$cols[0]+$delay)."\n";
	}
 ?>
 <html>
 	<head>
 		<?php require_once("../inc.php"); ?>
		<style>
		.reg-form#q { background-color:rgba(255,255,255,0.9); border-radius:16px; width:50%; display:inline-block; padding-bottom:33px; border:1px solid #ff5722; }
		.reg-form#q input:not([type=checkbox]), .reg-form textarea, .reg-form select { width:50%; }
		@media (max-width: 960px) { .reg-form#q, .ardesc { width:95%; }  .category .ipc { width:42vh; height:42vh; } }
		#fbg { position:fixed;top:0;left:0;right:0;bottom:0;z-index:-2;filter:blur(2px);-webkit-filter:blur(2px); }
		#fbg img { opacity:0; vertical-align:bottom; padding:0px; }
			#fbg { background-color:#ff5722; }
		body { height:calc(100% - 128px); }
		#vbg { z-index:-1; position:fixed; top:0; left:0; right:0; bottom:0; text-align:center; vertical-align:middle; }
		#vbg iframe, #vbg video { position:relative; min-width:50%; min-height:50%; margin:0; padding:0; overflow:hidden; display:block; vertical-align:middle; left:25%; box-shadow:0 0 2em #ff5722, inset 0 0 1em #ff5722; background-color:transparent; }
		table.list { width:66%; display:inline-block; font-size:18px; }
		table.list tr th { font-weight:bold; }
		</style>
		<script type="text/javascript" src="/jquery.collagePlus.min.js"></script>
		<script type="text/javascript" src="mobile-detect.min.js" ></script>
	</head>
	<body>
		<?php
		if(!isset($_GET["q"]))
		{
		?>
			<form id=q action="" method=GET class='reg-form' ><br>
				<input type=hidden name=t value=<?php echo time(); ?> ></input><br>
				<input type=hidden name=r value=<?php echo $ret; ?> ></input><br>
				<?php
					if(isset($_GET["admin"]))
						echo "<input type=hidden name=admin ></input>\n";
					echo "<input type=text name=q placeholder='".(isset($_GET["admin"])?"special ":"")."code' required autofocus ></input><br>\n";
				 ?>
				<br>
				<input type=submit value='<?php echo $txt; ?>' ></input><br>
			<br></form>
		<?php
		echo "<div id=fbg >";
		$files = array();
		exec("find ./uploads/ -type d -name users -prune -o \! -iname '*lite.jpg' -iname '*.jpg' -print",$files);
		shuffle($files);
		foreach($files as &$f)
			echo "<img src='imgshrink.php?i=".base64_encode(urlencode($f))."' />";
		echo "</div>\n";
		echo "<div id=vbg >\n";
		?>
		<video id=sourcevid autoplay muted loop preload="auto"></video>
		<script type="text/javascript">
		var md = new MobileDetect(window.navigator.userAgent);
		if(!md.mobile())
			$('video')[0].src = "images/mov.mp4";
		else $('video')[0].remove();
		</script>
		<?php }else if(!isset($_GET["admin"])) { ?>
			<div style="text-align:center;">
			<table class=list >
				<tr><!--th>#id</th--><th>name</th><th>surname</th><th>balance</th></tr>
				<tr><?php
				$res = mysql_query("select * from elchuzade.iotab where code = '".mysql_real_escape_string(intval($_GET["q"]))."';",$con);
				$data = mysql_fetch_array($res,MYSQL_ASSOC);
				mysql_free_result($res);
				if($data == NULL)
				{
					header("location: /?404");
					exit(0);
				}
				echo "<!--td>".$data["id"]."</td--><td>".$data["name"]."</td><td>".$data["surname"]."</td><td>".floatval($data["discount"])." TL</td>\n";
				?></tr>
			</table>
			</div>
		<?php }else if(in_array(intval($_GET["q"]),$admins)) { ?>
			<div style="text-align:center;">
			<table class=list >
				<tr><th>#id</th><th>cid</th><th>name</th><th>surname</th><th>balance</th><th>internal</th><th>phone</th><th>d-level</th></tr>
				<?php
				$res = mysql_query("select * from elchuzade.iotab ;",$con);
				while($data = mysql_fetch_array($res,MYSQL_ASSOC))
					echo "<tr><td>".$data["id"]."</td><td>".$data["code"]."</td><td>".$data["name"]."</td><td>".$data["surname"]."</td><td>".floatval($data["discount"])." TL</td><td>".floatval($data["internal"])." TL</td><td>".$data["phone"]."</td><td>".$data["level"]."</td></tr>\n";
				mysql_free_result($res);
				?>
			</table>
			</div>
		<?php }else{
			header("location: /");
			exit(0);
		} ?>
<!-- ###################################################################### -->
	<div class="header" >
		<a href='/' ><div class="logo bg-base contain" ></div></a><br>
		<h4 class="message" ><?php echo $btxt; ?></h4>
		<div class="options" >
		<a href='?admin' >administrative login</a>
		<?php
			if($uid != NULL)
			{
				if(user_perm($con,$uid)["w"])
					echo "<a href='?panel'>manage</a>\n";
				echo "<a href='//abbox.com/login.php?logout&r=".
					base64_encode("//".$_SERVER["HTTP_HOST"])."' id=quit >logout</a>\n";
			}
		?>
		</div>
	</div>
	<script type="text/javascript" >
	function imgfixer() {
		var list = document.querySelectorAll("[data-bg]");
		for (var i = 0; i < list.length; i++)
		{
			var url = list[i].getAttribute('data-bg');
			list[i].style.backgroundImage="url('" + url + "')";
		}
	}
	imgfixer();
	$(window).load(function () {
		$('#fbg').collagePlus({'targetHeight':160});
	});
	$('.onepic').click(function(e) { $(this).toggleClass('oneFullPic'); } );
	</script>
	<?php if(!isset($_GET["admin"]) || !isset($_GET["q"])) { ?>
	<script> window.history.replaceState('Object','abbox','/'); </script>
	<?php } ?>
	</body>
</html>
