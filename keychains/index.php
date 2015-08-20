<?php
	header("location: http://abbox.com/");
	exit(0);
	$btxt = "order materials from abbox";
	$title = "cool stuff from abbox!";
	if(!isset($_GET["sid"]) && empty($_GET))
	{
		header("location: //abbox.com/login.php?info&r=".
			base64_encode("//".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]));
		exit(0);
	}else if(isset($_GET["sid"]))
		session_id(base64_decode($_GET["sid"]));
	session_set_cookie_params(60*60*24,'/',implode('.',array_slice(explode('.',$_SERVER['HTTP_HOST']),-2)),false,false);
	require_once("../mysql.php");
	$con = db_conn($host,$user,$pass,$db);
	$ret = base64_encode("//".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."&try#");
	$uid = session_chk($con,session_id());
	$txt = "login";
	$uname = "";
	$upwd = "";
	if(isset($_GET["try"]) && isset($_GET["u"]))
		$uname = $_GET["u"];
	if(isset($_GET["try"]) && isset($_GET["p"]))
		$upwd = base64_decode($_GET["p"]);
	if($uid != NULL && isset($_GET["panel"]) && isset($_GET["h"]) && !empty($_GET["h"]))
	{
		header("content-type: text/plain");
		mysql_query("update `shop_orders` set `okay` = '1' where id = '".$_GET["h"]."';",$con) or die("!!error");
		//echo "<script> window.close(); </script>\n";
		header("location: ".$_SERVER['HTTP_REFERER']);
		exit(0);
	}
?>
<html>
	<head>
		<?php require_once("../inc.php"); ?>
		<style>
		.category .ipc { width:32vh; height:32vh; padding:5px; color:transparent; white-space:pre-wrap; overflow-wrap:break-word; }
		.itempics .onepic { width:256px;height:256px; }
		.itempics { overflow-x:auto !important; white-space:nowrap; max-height:265px; }
		.card { overflow-wrap:break-word; }
		.card:hover { color:white; background-color:#ff5722; }
		.category .item:hover { box-shadow:inset 0 0 0 100em rgba(0,0,0,0.8); !important; color:white; text-size:8px; }
		.reg-form#order input:not([type=checkbox]), .reg-form textarea, .reg-form select { width:66% !important; }
		.reg-form#order { width:50%;position:relative;margin:0;float:right; }
		.lefty { display:inline-block;width:50%;margin:0;float:left;text-align:center; }
		.ardesc { width:90%;position:relative;margin:0;white-space:pre-wrap;max-height:50%;overflow:auto;min-height:0; }
		.reg-form#login { background-color:rgba(255,255,255,0.9); border-radius:16px; width:50%; display:inline-block; padding-bottom:33px; border:1px solid #ff5722; }
		.reg-form#login input:not([type=checkbox]), .reg-form textarea, .reg-form select { width:50%; }
		@media (max-width: 960px) { .reg-form#order, .lefty { width:100%; float:none; clear:both; } .reg-form#login, .ardesc { width:95%; }  .category .ipc { width:42vh; height:42vh; } }
		#fbg { position:fixed;top:0;left:0;right:0;bottom:0;z-index:-2;filter:blur(2px);-webkit-filter:blur(2px); }
		#fbg img { opacity:0; vertical-align:bottom; padding:0px; }
			#fbg { background-color:#ff5722; }
		body { height:calc(100% - 128px); }
		#vbg { z-index:-1; position:fixed; top:0; left:0; right:0; bottom:0; text-align:center; vertical-align:middle; }
		#vbg iframe, #vbg video { position:relative; min-width:50%; min-height:50%; margin:0; padding:0; overflow:hidden; display:block; vertical-align:middle; left:25%; box-shadow:0 0 2em #ff5722, inset 0 0 1em #ff5722; background-color:transparent; }
		/* @media (max-width:960px) { #vbg iframe, #vbg video { left:5%; top:25%; } } */
		</style>
		<script type="text/javascript" src="/jquery.collagePlus.min.js"></script>
		<script type="text/javascript" src="mobile-detect.min.js" ></script>
		<script type="text/javascript" src="xpl0d3.js"></script>
	</head>
	<body onload="init()">
		<?php
			if( $uid != NULL )
			{
				//echo "login = ok (".$uid.")<br>\n";
				$usr = user($con,$uid);
				$ext = userext($con,$uid);
				if(isset($_GET["panel"]) && user_perm($con,$uid)["w"])
				{
					echo "<table class='list' >\n";
					$res = mysql_query("select * from `shop_orders` where `okay` = '0' order by `time` desc;",$con) or die("!error");
					echo "<tr>\n";
					echo "<th>okay?</th>\n";
					echo "<th>user</th>\n";
					echo "<th>mail</th>\n";
					echo "<th>order#</th>\n";
					echo "<th>which</th>\n";
					echo "<th>other</th>\n";
					echo "<th>when</th>\n";
					echo "<th>phone</th>\n";
					echo "</tr>\n";
					while($i = mysql_fetch_array($res,MYSQL_ASSOC))
					{
						echo "<tr>\n";
						echo "<td><a style='color:#ff5722' _target=_blank href='?panel&h=".$i["id"]."'>OK</a></td>\n";
						$usr = user($con,$i["uid"]);
						echo "<td>".$usr["name"]."</td>\n";
						echo "<td>".$usr["mail"]."</td>\n";
						foreach($i as $k => &$j)
							if($k != "uid" && $k != "okay")
								if($k != "sel")
									echo "<td>".$j."</td>\n";
								else
									echo "<td><a style='color:#ff5722;' href='?sel=".$j."' >#".$j."</a></td>\n";
						echo "<td>".userext($con,$i["uid"])["phone"]."</td>\n";
						echo "</tr>\n";
					}
					mysql_free_result($res);
					echo "</table>\n";
				}else if(isset($_GET["order"])) {
					if(empty($_POST))
					{
						header("location: /");
						exit(0);
					}
					mysql_query("insert into `shop_orders` VALUES(default,'".$uid."','".$_POST["sel"]."','0','".mysql_real_escape_string(htmlentities($_POST["ex"]))."',current_timestamp());",$con) or die("error!");
					$pid = mysql_insert_id();
					if(isset($_POST["a"]))
					{
						mysql_query("update `users` set `real` = '".mysql_real_escape_string($_POST["real"])."' where id = '".$uid."';",$con) or die("error!!");
						mysql_query("update `userext` set addr = '".mysql_real_escape_string($_POST["addr"])."', phone = '".$_POST["phone"]."' where uid = '".$uid."';",$con) or die("error!!!");
					}
					echo "<pre style='max-width:100%;white-space:pre-wrap;'>\n";
					echo "your order #".$pid."_".$_POST["sel"]." is taken...\n";
					echo "for ".$_POST["addr"]."\n".$_POST["phone"]."\n";
					echo "from ".$_POST["real"]."\n";
					echo "</pre>\n";
					exec("/bin/bash /opt/slack 'msg' '#general' '<@kamran> hey! you have new order: #".$pid." from ".$_POST["real"]." (".$uid.") ".$_POST["phone"]." for ".$_POST["sel"]."\nclick here: //abbox.co/?panel ';");
				}else if(isset($_GET["sel"])) {
					//echo $_GET["sel"]."<br>\n";
					$res = mysql_query("select * from `shop_items` where id = '".$_GET["sel"]."';",$con) or die("error!!!!");
					$i = mysql_fetch_array($res,MYSQL_ASSOC);
					echo "<div class='lefty' >\n";
					echo "<div class='box rounded category cat-slider itempics block' >\n";
					for($j=0;$j<10;$j++)
						if(file_exists("uploads/shop/".$_GET["sel"]."_".$j.".jpg"))
							echo "<div class='bg-base cover onepic' data-bg='uploads/shop/".$_GET["sel"]."_".$j.".jpg' ></div>\n";
					echo "</div><br>\n";
					echo "<div class='box category block ardesc' >";
					echo $i["name"]." "."$".$i["price"]."\n";
					echo $i["desc"];
					echo "</div>\n";
					echo "</div>\n";
					$phn = "";
					if(intval($ext["phone"]) != 0)
						$phn = $ext["phone"];
					echo "<form id=order action='?order' method=POST class='reg-form' enctype='multipart/form-data' >\n";
					echo "<input type=hidden name=sel value='".$_GET["sel"]."' ></input><br>\n";
					echo "<input type=text name=real placeholder='name' value='".$usr["real"]."' required ></input><br>\n";
					echo "<textarea rows=3 name=addr placeholder='address' required >".$ext["addr"]."</textarea><br>\n";
					echo "<input type=text name=phone placeholder='phone' value='".$phn."' required ></input><br>\n";
					echo "<textarea rows=4 name=ex placeholder='notes...' ></textarea><br>\n";
					echo "<input type=checkbox name=a id=a checked ></input>\n";
					echo "<label for=a >also update my profile at abbox</label><br>\n";
					echo "<input type=submit value='order' ></input><br>\n";
					echo "</form>\n";
					echo "<br><br><br><br><br><br>\n";
				}else{
					echo "<br><br>\n";
					$res = mysql_query("select * from `shop_items` where active = '1';",$con) or die("error!!!!!");
					$arr = array();
					while($i = mysql_fetch_array($res,MYSQL_ASSOC))
						$arr[] = $i;
					mysql_free_result($res);
					shuffle($arr);
					foreach($arr as &$i)
					{
						echo "<a style='color:#ff5722' href='?sel=".$i["id"]."' >";
						echo "<div class='box category card' style='width:auto;max-height:none;' >\n";
						$img = "uploads/shop/".$i["id"]."_0.jpg";
						if(!file_exists($img))
							$img = $defimg;
						echo "<div class='bg-base cover item ipc' data-bg='".$img."' >";
						echo substr($i["desc"],0,255);
						echo "</div><br>\n";
						echo $i["name"]."<br>\n";
						echo "$".$i["price"]."<br>\n";
						echo "</div>";
						echo "</a>\n";
						echo "<script> window.history.replaceState('Object', 'abbox', '/'); </script>";
					}
				}
			}else{
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
				<!-- iframe src="//www.youtube.com/embed/fOjmZkpiARs?autoplay=1&controls=0&disablekb=1&enablejsapi=1&fs=0&loop=1&modestbranding=1&playsinline=1&rel=0&showinfo=0&autohide=1&color=white&iv_load_policy=3" frameborder="0"></iframe -->
				<canvas id="sourcecopy" style="position:relative;width:100%;height:100%;display:none;"></canvas>
				<?php
				echo "</div>\n";
			?>
			<br><br>
			<form id=login action="//abbox.com/login.php" method=POST class='reg-form' ><br>
			<?php
				if(isset($_GET["reg"]))
					switch(true)
					{
						case isset($_GET["missing"]):
							echo "use different mail and supply all required fields<br>\n";
						case isset($_GET["short"]):
							echo "password cannot be that short<br>\n";
					}
				else if(isset($_GET["pwd"]))
					echo "invalid password!<br>\n";
				else if(isset($_GET["try"]))
					echo "looks like you don't have account... let me register you!<br>\n";
			?>
				<input type=hidden name=t value=<?php echo time(); ?> ></input><br>
				<input type=hidden name=r value=<?php echo $ret; ?> ></input><br>
				<input type=<?php echo empty($uname)?'text':'email'; ?> name=<?php echo empty($uname)?'u':'m'; ?> placeholder="e-mail" value='<?php echo $uname; ?>' required autofocus ></input><br>
				<input type=password name=p placeholder="password" value='<?php echo $upwd; ?>' required ></input><br>
		<?php
				if(isset($_GET["pwd"]))
					$txt = "re-try?";
				else if(isset($_GET["try"]))
				{
					$txt = "register";
					echo "<input type=checkbox name=a id=a required></input>\n";
					echo "<label for=a >i agree terms and conditions</label><br>\n";
					echo "<input name=done type=hidden ></input>\n";
					echo "<input name=u value='".$uname."' type=hidden ></input>\n";
				}
				echo "<input type=submit value='".$txt."' ></input><br>\n";
				echo "<br></form>\n";
				echo "<script> window.history.replaceState('Object', 'abbox', '/'); </script>";
			}
		?>
		<div class="header" >
			<a href='/' ><div class="logo bg-base contain" ></div></a><br>
			<h4 class="message" ><?php echo $btxt; ?></h4>
			<div class="options" >
			<a href='//abbox.com' >abbox.com</a>
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
		<canvas id="output" style="width:100%;height:100%;position:fixed;" onmousedown="dropBomb(event, this)"></canvas>
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
	</body>
</html>
