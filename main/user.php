<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/mysql.php");
	$con = db_conn($host,$user,$pass,$db);
//	session_start();
	$uid = session_chk($con,session_id());
	$perms = user_perm($con,$uid);
	if(isset($_GET["pic"]))
	{
		$upic = "uploads/users/".$_GET["pic"].".jpg";
		if(!file_exists($upic) || $uid == NULL)
			$upic = $defimg;
		header("content-type: image/jpeg");
		header("content-length: ".filesize($upic));
		header("last-modified: ".gmdate("D, d M Y H:i:s e",filemtime($upic)) );
		//header("expires: 0");
		header("cache-control: private, max-age=86400");
		ob_clean();
		flush();
		readfile($upic);
		exit(0);
	}
	//header("content-type: text/plain");
	if(isset($_GET["follow"]) && isset($_GET["id"]) && $uid != NULL)
	{
		if(follow_q_usr($con,$uid,$_GET["id"]))
			follow_del_usr($con,$uid,$_GET["id"]);
		else
			follow_add_usr($con,$uid,$_GET["id"]);
		if(isset($_GET["u"]))
		{
			header("location: /search.php?u=".$_GET["u"]);
			exit(0);
		}
		header("location: ?id=".$_GET["id"]);
		exit(0);
	}
	if(isset($_GET["list"]))
	{
		var_dump(users($con));
		exit(0);
	}
	if(!isset($_GET["id"]) && $uid == NULL)
	{
		header("location: /");
		exit(0);
	}
	if(!isset($_GET["id"]) && $uid != NULL)
		$_GET["id"] = $uid;
	echo "<!--\n";
	$usr = user($con,$_GET["id"]);
	$ext = userext($con,$_GET["id"]);
	if($uid == $_GET["id"])
	{
		echo "\tThis is you!\n";
	}else{
		notify_read_usr($con,$uid,$_GET["id"]);
	}
	if($perms["x"])
	{
		echo "\n\n\t All sessions currently logged in:\n";
		var_dump(session_list($con));
	}
	echo "-->\n";
	$upic = "user.php?pic=".$_GET["id"];
	$links = social_parse($ext["sm_links"]);
	shuffle($accounts);
	$langs = file("language-codes.csv",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	$langs[0] = "";
	$langs = str_replace('"',"",$langs);
	$certs = file("certificates.txt",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	$uname = user_getname($con,$uid);
	$btxt = ucwords($uname)."'s account";
	$title = $uname;
?>
<html>
	<head>
		<?php require_once($_SERVER["DOCUMENT_ROOT"]."/inc.php"); ?>
	</head>
	<body id=page >
	<div class="uinfo" >
		<?php
			if(isset($_GET["mail"]))
				echo "Problem with mail!<br>\n";
			if(isset($_GET["match"]))
				echo "Passwords did not match!<br>\n";
			if(isset($_GET["short"]))
				echo "Password is too short!<br>\n";
			if(isset($_GET["edit"]))
				echo "Updated successfully!<br>\n";
		?>
		<?php if((user_perm($con,$uid)["x"] || $uid == $_GET["id"]) && isset($_GET["fix"])) { ?>
		<form enctype='multipart/form-data' class="reg-form" action="/login.php?edit" method=POST >
		<!--
			<input disabled type=text value='<?php echo $usr["id"]; ?>' ></input><br>
			<input disabled type=text value='<?php echo $usr["name"]; ?>' ></input><br>
			<input disabled type=text value='Registered: <?php echo $usr["create"]; ?>' ></input><br>
			<input <?php echo ($perms["x"])?"":"disabled"; ?> type=text value='access: <?php echo $usr["level"]; ?>' ></input><br>
		-->
			<input placeholder="e-mail" name=m type=text value='<?php echo $usr["mail"]; ?>' ></input><br>
			<input placeholder="real name" name=r type=text value='<?php echo $usr["real"]; ?>' ></input><br>
			picture:<br><input type=file name="i" ></input><br>
			<input type=password name=p placeholder="new password"></input><br>
			<input type=password name=v placeholder="password verification"></input><br>
			<br>
			<input type=date name=bdate placeholder="birth date" value="<?php echo date("Y-m-d",strtotime($ext["bdate"])); ?>"></input><br>
			<textarea rows=3 name=addr placeholder="address" ><?php echo $ext["addr"]; ?></textarea><br>
			<input type=text name=phone placeholder="phone" value="<?php echo $ext["phone"]; ?>"></input><br>
			<input type=text name=pph placeholder="hourly price" value="<?php echo $ext["pph"]; ?>"></input><br>
			<input type=text name=comp placeholder="company" value="<?php echo $ext["comp"]; ?>"></input><br>
			<br>
			<?php
				foreach($accounts as &$acc)
					echo "<input type=text name=".$acc." placeholder='".$anames[$acc]."' value='".$links[$acc]."'></input><br>\n";
				echo "<br>\nlanguages:<br>\n";
				echo "<div class='rounded selist' >\n";
				foreach($langs as &$lang)
				{
					if(empty($lang))
						continue;
					$ar =  explode(",",$lang);
					echo "<input type=checkbox name='langs[]' value=".$ar[0]." ".((strpos($ext["langs"],$ar[0]) !== false)?"checked":"")." >&nbsp;".$ar[1]."</input><br>\n";
				}
				echo "</div>\n<br>\n";
				echo "<br>\ncertificates:<br>\n";
				echo "<div class='rounded selist' >\n";
				foreach($certs as &$cert)
				{
					if(empty($cert))
						continue;
					$ar =  explode(",",$cert);
					echo "<input type=checkbox name='certs[]' value=".$ar[0]." ".((strpos($ext["certs"],$ar[0]) !== false)?"checked":"")." >&nbsp;".$ar[1]."</input><br>\n";
				}
				echo "</div>\n<br>\n";
			?>
			<br>
			<input type=submit value=save ></input><br>
		</form>
		<!-- pre>
User Logins:
<?php var_dump(sessions($con,$uid)); ?>
--- END OF LOG ---
		</pre -->
		<?php }else{ ?>
		<div class="nohand" ><div class="bg-base upic" data-bg="<?php echo $upic; ?>" ></div></div>
		<div class="lhand" >
			<!-- div>UID: <?php echo $usr["id"]; ?></div -->
			<?php
				echo "<div>"."username: ".$usr["name"]."</div>\n";
				echo "<div>"."real name: ".htmlentities($usr["real"])."</div>\n";
				echo "<div>"."birthday: ".substr($ext["bdate"],0,10)."</div>\n";
				echo "<div>"."phone: ".$ext["phone"]."</div>\n";
				echo "<div>"."e-mail: ".str_replace("@","[at]",$usr["mail"])."</div>\n";
				echo "<div>"."hourly price: ".$ext["pph"]."$</div>\n";
				echo "<div>"."following: <a href='/search.php?r=".$_GET["id"]."' >".sizeof(follows_usr($con,$_GET["id"]))."</a></div>\n";
			?>
		</div>
<!--
	<div>here since: <?php echo $usr["create"]; ?></div>
	<div>access: <?php echo $usr["level"]; ?></div>
-->
		<div class="rhand" >
			<?php
				echo "<div>"."last seen: ".$ext["lastseen"]."</div>\n";
				echo "<div>"."experience: ".$ext["worktime"]."hrs</div>\n";
				echo "<div>"."company: ".$ext["comp"]."</div>\n";
				echo "<div>"."languages:\n";
				foreach($langs as &$lang)
				{
					if(empty($lang))
						continue;
					$ar =  explode(",",$lang);
					if(strpos($ext["langs"],$ar[0]) !== false)
						echo $ar[1].","."\n";
				}
				echo "</div>\n";
				echo "<div>"."certificates:\n";
				foreach($certs as &$cert)
				{
					if(empty($cert))
						continue;
					$ar =  explode(",",$cert);
					if(strpos($ext["certs"],$ar[0]) !== false)
						echo $ar[1].","."\n";
				}
				echo "</div>\n";
				echo "<div>"."address: ".$ext["addr"]."</div>\n";
				echo "<div>followers: <a href='/search.php?f=".$_GET["id"]."' >".sizeof(follow_of_usr($con,$_GET["id"]))."</a></div>\n";
			?>
		</div>
		<div class="bottomhand" >
			<?php
				foreach($accounts as &$acc)
					if(isset($links[$acc]) && !empty($links[$acc]))
						echo "<a href='".(strpos("http",$alinks[$acc]) === false ? "//":"").$alinks[$acc].$links[$acc]."' class='bg-base contain smlnk ".$acc."' ></a>\n";
			?>
		</div>
		<?php } ?>
	</div>
	<?php
		if($uid != NULL && $uid != $usr["id"])
		{
			echo "<a class='' href='?id=".$usr["id"]."&follow' ><span class='iteminfo follow_btn lbtn'>".
				(follow_q_usr($con,$uid,$usr["id"])?"un":"+")
				."follow</span></a>";
			echo "<hr class='v sep' />";
			echo "<a class='' href='/message.php?msg=".$usr["id"]."' ><span class='iteminfo follow_btn rbtn'>message</span></a>\n";

		}
	?>
		<hr>
		<div>
		<?php
			$items = itemsby_type($con,$_GET["id"],"orders",true);
			if(!empty($items))
			{
			//if(!empty($items)) echo "<hr>Models:\n";
			if($uid == $_GET["id"])
				echo "<a class='rounded new' href=/item.php?new&model >new model</a><br>\n";
			else
				echo "<span class='rounded new' >models</span><br>\n";
			echo "<div class='box category cat-slider userslid' >\n";
			foreach($items as &$item)
			{
				if(empty($item))
					continue;
				$item_pic = "uploads/items/".$item["hash"]."/lite.jpg";
				if(!file_exists($item_pic))
					$item_pic = $defimg;
				echo "<a href='/item.php?hash=".$item["hash"]."' ><div class='bg-base cover item' data-bg='/".$item_pic."' ><div class=ininfo >".htmlentities(substr($item["name"],0,11))."</div><div class=ipinfo >$".$item["price"]."</div></div></a>\n";
			}
			echo "</div>\n";
			}
		?>
		</div><div>
		<?php
			$items = itemsby_type($con,$_GET["id"],"orders",false);
			if(!empty($items))
			{
			//if(!empty($items)) echo "<hr>Orders:\n";
			if($uid == $_GET["id"])
				echo "<a class='rounded new' href=/item.php?new >new order</a><br>\n";
			else
				echo "<span class='rounded new' >orders</span><br>\n";
			echo "<div class='box category cat-slider userslid' >\n";
			foreach($items as &$item)
			{
				if(empty($item))
					continue;
				$item_pic = "uploads/items/".$item["hash"]."/lite.jpg";
				if(!file_exists($item_pic))
					$item_pic = $defimg;
				echo "<a href='/item.php?hash=".$item["hash"]."' ><div class='bg-base cover item' data-bg='/".$item_pic."' ><div class=ininfo >".htmlentities(substr($item["name"],0,11))."</div><div class=ipinfo >$".$item["price"]."</div></div></a>\n";
			}
			echo "</div>\n";
			}
		?>
		</div><div>
		<?php
			$items = follows($con,$_GET["id"]);
			if(!empty($items))
			{
			//if(!empty($items)) echo "<hr>Orders:\n";
			echo "<a class='rounded new' href='/search.php?i=".$_GET["id"]."' >following</a><br>\n";
			echo "<div class='box category cat-slider userslid' >\n";
			foreach($items as &$item)
				if(!empty($item["desthash"]))
				{
					$item_r = item($con,$item["desthash"]);
					if(empty($item_r))
						continue;
					$item_pic = "uploads/items/".$item["desthash"]."/lite.jpg";
					if(!file_exists($item_pic))
						$item_pic = $defimg;
					echo "<a href='/item.php?hash=".$item["desthash"]."' ><div class='bg-base cover item' data-bg='/".$item_pic."' ><div class=ininfo >".htmlentities(substr($item_r["name"],0,11))."</div><div class=ipinfo >$".$item_r["price"]."</div></div></a>\n";
				}
			echo "</div>\n";
			}
		?>
		</div>
		<?php require_once("header.php"); ?>
		<script type="text/javascript" >
			//<?php echo ($uid == $_GET["id"])?"true":"false"; ?> &&
			if(<?php echo (!isset($_GET["fix"]) && (user_perm($con,$uid)["x"] || $uid == $_GET["id"]))?"true":"false"; ?>)
			{
				document.getElementById('accset').text = 'settings';
				document.getElementById('accset').href = '/user.php?id=<?php echo $_GET["id"]; ?>&fix';
			}
			/*
			}else{
				document.getElementById('accset').text = 'new message';
				document.getElementById('accset').href = '/message.php?msg=<?php echo $_GET["id"]; ?>';
			}
			*/
		</script>
	</body>
</html>
<?php db_end($con); ?>
