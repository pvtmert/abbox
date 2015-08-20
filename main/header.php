<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/mysql.php");
	$conh = db_conn($host,$user,$pass,$db);
//	if(empty(session_id())) session_start();
	$uid = session_chk($conh,session_id());
	$logged = ($uid != NULL)?true:false;
	$hcusr = user($con,$uid);
	$total = 0;
	$res = mysql_query("select * from items where type = 'orders';",$con) or die("db error");
		while($item = mysql_fetch_array($res,MYSQL_ASSOC))
			$total += intval($item["price"]);
	$uname = $hcusr["name"];
	if(!empty($hcusr["real"]))
		$uname = explode(' ',trim($hcusr["real"]))[0];
	if(!isset($btxt))
		if($uid != NULL)
			$btxt = "hello ".ucwords($uname)."!";
		else
			$btxt = "welcome!";
	//$btxt = "welcome".(($uid != NULL)?(" ".ucwords($uname)):"")."!";
	if($uid == NULL) echo "<div class='warning' onclick='dismiss(this);' ><div class='bg-base contain icon r' data-bg='/images/cookie".rand(1,2).".png'></div><p l >we may use your cookies to track your interests...<br>click to dismiss...</p></div>\n";

?>
<?php if($uid == NULL) { ?>
<div class='warning' onclick='dismiss(this);' ><div class='bg-base contain icon l' data-bg='/images/undercons<?php echo rand(1,2); ?>.png'></div><p r >This site is under construction, may not behave properly!<br>Click to dismiss this message.</p></div>
<script type="text/javascript" >
	function dismiss(obj) {
		$(obj).css({opacity:"0"});
		setTimeout(function() {
		$(obj).css({display:"none"});
			//alert(window.devicePixelRatio);
		},333);
		sessionStorage.setItem("dismiss","yup");
	}
		if(sessionStorage.getItem("dismiss"))
			$(".warning").css({display:"none"});
</script>
<?php } ?>
<div class="header" >
	<a href='<?php echo (isset($home))?($home):"/"; ?>' ><div class="logo bg-base contain" ></div></a><br>
	<a href=/orders.php class="income" >$<?php echo $total; ?></a>
	<h4 class="message" ><?php echo $btxt; ?></h4>
	<div class="options" >
	<?php
	if($uid != NULL)
	{
		echo "<a href=/item.php?new>new item</a>\n";
		echo "<a id=accset href=/user.php?id=".$uid.">account</a>\n";
		echo "<a href=/login.php?logout>logout</a>\n";
		userext_seen($con,$uid);
	}else{
	?>
		<form action="/login.php" method=POST >
			<input type=hidden name=r value='<?php echo base64_encode("//".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]); ?>' ></input>
			<input type=hidden name=t value=<?php echo time(); ?> ></input>
			<input id=usr type=text name=u placeholder="username or email" onkeyup="logregfix();" tabindex=2 ></input>
			<input type=password name=p placeholder="password" tabindex=3 ></input>
			<br>
			<input id=login type=submit value="login"></input><input id=reg type=submit name=reg value="register"></input>
		</form>
	<?php
	}
	if(!isset($qstr))
		$qstr = "";
	if(!isset($qtype))
		$qtype = "q";
	?>
	</div>
	<div class="hbox" >
		<form action="/search.php" method=GET >
			<input type=text name=<?php echo $qtype; ?> autofocus tabindex=1 value="<?php echo $qstr; ?>" placeholder="search" autocomplete="off" onKeyDown="scrll(event);" ></input>
			<input type=submit value='' data-bg="/images/search.png" class="bg-base contain" ></input>
		</form>
		<?php
			if($uid != NULL)
			{
				$ctot = cart_sum($con,$uid);
				if($ctot > 9999)
					$ctot = intval($ctot/1000)."k";
				echo "<a class='bg-base contain' data-bg='/images/cart.png' href='/search.php?cart'>(".$ctot."$)</a>\n";
				echo "<a class='bg-base contain' data-bg='/images/msg.png' href='/message.php'>(".sizeof(notifications($conh,$uid)).")</a>\n";
			}
		?>
	</div>
</div>
<script type="text/javascript">
	//document.getElementById("login").style.display = "none";
	//document.getElementById("reg").style.display = "inline-block";
	function logregfix()
	{
		<?php if($uid != NULL) echo "return;"; ?>
		if(document.getElementById("usr").value.length == 0)
		{
			document.getElementById("login").style.display = "none";
			document.getElementById("reg").style.display = "block";
		}else{
			document.getElementById("login").style.display = "block";
			document.getElementById("reg").style.display = "none";
		}
	}
	//document.getElementById("page").style.opacity = 0;
	//window.onload = function() { logregfix(); $('#page').animate({opacity:1},500); };
	for(i=0;i<5;i++)
		setTimeout(function() { logregfix(); },250*i);
	function imgfixer() {
		var list = document.querySelectorAll("[data-bg]");
		for (var i = 0; i < list.length; i++)
		{
			var url = list[i].getAttribute('data-bg');
			list[i].style.backgroundImage="url('" + url + "')";
		}
	}
	imgfixer();
	function notify() {
		if(<?php echo (($uid != NULL)?"false":"true"); ?>)
			return;
		flst = {
			m:["new message","/message.php?msg=","/user.php?pic="],
			i:["item update","/item.php?hash=","/item.php/?pic="],
			u:["user update","/user.php?id=","/user.php?pic="],
			c:["new comment","/item.php?hash=","/item.php?pic="]
		};
		nlst = <?php echo json_encode(notifications($conh,$uid)); ?>;
		Notification.requestPermission(/*function(p){ if(p === "granted") new Notification("Welcome to abbox"); }*/);
		if(Notification.permission === "granted") {
			for(i=0;i<nlst.length && i<10;i++)
			{
				vals = "";
				switch(nlst[i].type)
				{
					case "m": vals = flst.m; break;
					case "i": vals = flst.i; break;
					case "c": vals = flst.c; break;
					case "u": vals = flst.u; break;
				}
				opts = {
					body: nlst[i].msg + "\n" + nlst[i].when,
					icon: vals[2]+nlst[i].target,
					_tag: nlst[i].type,
					sound: "/images/notification.mp3"
				}
				n = new Notification(vals[0],opts);
				url = vals[1]+nlst[i].target;
				console.log(url);
				n.addEventListener("click",function() { window.location = url; });
				//setTimeout(n.close.bind(n),5000);
				var a = new Audio(opts.sound);
				a.play();
			}
		}
	}
	notify();
	function scrll(e) {
		var much = 64;
		if(e.which == 40)
			window.scrollTo(0,window.scrollY+much);
	//		$("html, body").animate({scrollTop: window.scrollY+much},200);
		if(e.which == 38)
			window.scrollTo(0,window.scrollY-much);
	//		$("html, body").animate({scrollTop: window.scrollY-much},200);
	}
</script>
<?php
	if($_SERVER["SCRIPT_NAME"] != "/item.php" || $uid == NULL)
	{
		echo "<a href='//abbox.io/'><div class='footer' >\n";
		require_once("footer.php");
		echo "</div></a>\n";
	}
?>
<?php
	$not = notifications($conh,$uid);
	foreach($not as &$n)
		notify_ok($conh,$n["nid"]);
?>

