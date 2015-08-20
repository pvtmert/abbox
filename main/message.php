<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/mysql.php");
	$con = db_conn($host,$user,$pass,$db);
//	session_start();
	$uid = session_chk($con,session_id());
	if($uid == NULL)
	{
		header("location: /");
		exit(0);
	}
	userext_seen($con,$uid);
	if(isset($_GET["get"]))
	{
		$msg = messages_bw($con,$uid,$_GET["get"]);
		$min = sizeof($msg)-101;
		if($min < 0)
			$min = 0;
		for($i=$min;$i<sizeof($msg);$i++)
		{
			echo "<pre class='box rounded ".(($msg[$i]["from"] == $uid)?("r"):("l"))."' >\n";
			echo "<a href='/user.php?id=".$msg[$i]["from"]."'>";
			echo "<div class='bg-base cover rounded ppic ".(($msg[$i]["from"] == $uid)?("l"):("l"))."' data-bg='/user.php?pic=".$msg[$i]["from"]."' ></div>\n";
			echo "</a>";
			echo $msg[$i]["when"]."\n";
			echo htmlentities($msg[$i]["content"]);
			echo "</pre>\n";
			//echo "<hr >\n";
		}
		echo "<pre class=box >Last Seen: ".userext($con,$_GET["get"])["lastseen"]."</pre>\n";
		?><script type="text/javascript" >
		var list = document.querySelectorAll("[data-bg]");
		for (var i = 0; i < list.length; i++)
		{
			var url = list[i].getAttribute('data-bg');
			list[i].style.backgroundImage="url('" + url + "')";
		}</script><?php
		notify_read_msg($con,$uid,$_GET["get"]);
		exit(0);
	}
	if(isset($_GET["done"]) && isset($_POST["t"]) && !empty($_POST["m"]) && !empty($_POST["t"]))
	{
		$cont = "";
		if(isset($_POST["m"]))
			$cont = $_POST["m"];
		if(strlen($cont) < 2)
			exit(0);
		$attach = "";
		if(isset($_POST["attach"]))
			$attach = $_POST["attach"];
		$mid = message($con,$uid,$_POST["t"],$cont,$attach);
		header("location: ?msg=".$_POST["t"]);
		notify_recv_msg($con,$_POST["t"],$uid);
		exit(0);
	}
	$btxt = "messages for ".user_getname($con,$uid);
	$title = "messages";
?>
<html>
	<head><?php require_once($_SERVER["DOCUMENT_ROOT"]."/inc.php"); ?></head>
	<body id=page class="sp" >
	<div class="messages">
	<div class="msglist" >
	<?php
		$msgs = messages_all($con,$uid);
		$msgarr = array();
		foreach($msgs as &$msg)
			if($msg["from"] != $uid)
				$msgarr[$msg["from"]] = $msg;
			else
				$msgarr[$msg["to"]] = $msg;
		foreach($msgarr as &$msg)
		{
			$fixer = (($msg["from"] != $uid)?($msg["from"]):($msg["to"]));
			$musr = user($con,$fixer);
			if(empty($musr))
				continue;
			$uname = user_getname($con,$musr["id"]);
			echo "<a class='rounded msgitem' data-real='".$uname."' data-id='".$fixer."' href='?get=".$msg["from"]."' >"."<div class='bg-base cover rounded ppic_s' data-bg='/user.php?pic=".$fixer."' ></div>".$uname."\n".$msg["when"]."</a>\n";
		}
	?>
	</div>
	<div class="rounded cont" id="msgcont" ></div>
	<form method=POST action="?done" class="msgsendform" >
	<input type=hidden name=t id=tobox ></input>
	<textarea id=msg class='box' cols=40 rows=4 maxlength=500 required placeholder='write here...' name=m disabled ></textarea>
	<input type=submit value='send' id=msgsub ></input>
	</form>
	</div>
	<script type="text/javascript">
	function msgscroll(tf) {
		if(tf && document.getElementById("msgcont").scrollTop < document.getElementById("msgcont").scrollHeight - 520)
			return;
		$('#msgcont').scrollTop(document.getElementById("msgcont").scrollHeight);
	}
	function sender() {
		who = document.getElementById('tobox').value;
		$.post("?done",{ t: who, m: document.getElementById("msg").value });
		document.getElementById("msg").value = "";
		$("#msgcont").load( "?get=" + who , function() { msgscroll(false); } );
		return false;
	}
	function opener(msg) {
		document.getElementById("msg").removeAttribute("disabled");
		$("#msgcont").load( "?get=" + msg.getAttribute("data-id"), function() { msgscroll(false); } );
		document.getElementById("tobox").value = msg.getAttribute("data-id");
		$("#msg").focus();
		$(".selected").toggleClass('selected');
		$(msg).toggleClass('selected');
		document.title = msg.getAttribute("data-real") + " | abbox messaging";
		return false;
	}
	function kphandler(e)
	{
		if(e.which == 13)
			if(!e.shiftKey)
				sender();
	}
	msgitems = document.getElementsByClassName("msgitem");
	for(i=0;i<msgitems.length;i++)
	{
		msgitems[i].setAttribute("onclick","opener(msgitems["+i+"]); msgscroll(false);");
		//msgitems[i].href="#";
		msgitems[i].removeAttribute("href");
	}
<?php if(isset($_GET["msg"])) echo "$('#msgcont').load( '?get=".$_GET["msg"]."',function() { msgscroll(false); } ); document.getElementById('tobox').value = '".$_GET["msg"]."'; $('[data-id=".$_GET["msg"]."]').toggleClass('selected'); document.getElementById('msg').removeAttribute('disabled'); window.onload = function() { $('#msg').focus(); }; \n"; ?>
		setInterval(function() {
			tgt = document.getElementById("tobox");
			if(tgt.value)
				$('#msgcont').load( '?get=' + tgt.value , function() { msgscroll(true); } );
		},999);
document.getElementById("msgsub").setAttribute("onclick","return sender();");
	document.getElementById("msg").setAttribute("onkeyup","kphandler(event);");
	</script>
			<?php require_once("header.php"); ?>
	</body>
</html>
