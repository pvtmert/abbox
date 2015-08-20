<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/mysql.php");
	$con = db_conn($host,$user,$pass,$db);
//	session_start();
	$r = base64_encode($_SERVER['HTTP_REFERER']);
	if(isset($_GET["r"]) && !empty($_GET["r"]))
		$r = $_GET["r"];
	if(isset($_POST["r"]) && !empty($_POST["r"]))
		$r = $_POST["r"];
	if(isset($_GET["info"]) && !isset($_POST["t"]))
	{
		header("content-type: text/plain");
		$uid = session_chk($con,session_id());
		echo "// ";
		if($uid != NULL)
			echo "ok";
		else
			echo "fail";
		echo "\n";
		echo "var abbox = {sess:'".session_id()."',id:'".($uid)."'};\n";
		echo "console.log(abbox);\n";
		echo "// ".$_COOKIE["PHPSESSID"]."\n";
		if(!empty($r))
			header("location: ".base64_decode($r)."?".md5(rand())."&sid=".base64_encode(session_id()));
		else if(isset($_SERVER['HTTP_REFERER']))
			header("location: ".$_SERVER['HTTP_REFERER']);
		else
			header("location: /");
		exit(0);
	}
	if(isset($_GET["done"]) || isset($_POST["done"]))
	{
		$err = "";
		if( !isset($_POST["u"]) || !isset($_POST["m"]) || !isset($_POST["p"]) /* || !isset($_POST["v"]) */ || !isset($_POST["a"]) || intval(strpos($_POST["m"],"@")) < 1 )
		{
			$err = "reg&missing";
			if(!empty($r))
				header("location: ".base64_decode($r).$err);
			else
				header("location: ?".$err);
			exit(0);
		}
		if(strlen($_POST["p"]) < 4)
		{
			$err = "reg&short";
			if(!empty($r))
				header("location: ".base64_decode($r).$err);
			else
				header("location: ?".$err);
			exit(0);
		}
		if(false)
		if($_POST["p"] != $_POST["v"])
		{
			$err = "reg&match";
			if(!empty($r))
				header("location: ".base64_decode($r).$err);
			else
				header("location: ?".$err);
			exit(0);
		}
		$nm = username($con,$_POST["u"]);
		$ml = usermail($con,$_POST["m"]);
		if($nm || $ml)
		{
			$err = "reg&exists";
			if(!empty($r))
				header("location: ".base64_decode($r).$err);
			else
				header("location: ?".$err);
			exit(0);
		}
		$real = "";
	//	if(isset($_POST["r"]))
	//		$real = $_POST["r"];
		$_POST["u"] = explode("@",$_POST["u"])[0];
		user_reg($con,$_POST["u"],$_POST["m"],$_POST["p"],$real);
		$usr = username($con,$_POST["u"]);
		session_reg($con,$usr["id"],session_id());
		echo "registration complete!";
		if(!empty($r))
			header("location: ".base64_decode($r).$err);
		else if(isset($_SERVER['HTTP_REFERER']))
			header("location: ".$_SERVER['HTTP_REFERER']);
		else
			header("location: /");
		exit(0);
	}
	if(isset($_GET["edit"]))
	{
		header("content-type: text/plain");
		if(strlen($_POST["p"]) < 4 && strlen($_POST["p"]) > 0)
		{
			header("location: /user.php?short");
			exit(0);
		}
		if($_POST["p"] != $_POST["v"])
		{
			header("location: /user.php?match");
			exit(0);
		}
		if(intval(strpos($_POST["m"],"@") < 1))
		{
			header("location: /user.php?mail");
			exit(0);
		}
		$real = "";
		if(isset($_POST["r"]))
			$real = $_POST["r"];
		$usr = user($con,session_chk($con,session_id()));
		user_update($con,$usr["id"],$usr["name"],$_POST["m"],$real,$_POST["p"]);
		if(isset($_FILES["i"]) && $_FILES["i"]["error"] == 0)
		{
			$img = new \Imagick(realpath($_FILES["i"]["tmp_name"]));
			$img->resizeImage(800,600,Imagick::FILTER_POINT,1,true);
			file_put_contents("uploads/users/".$usr["id"].".jpg",$img->getImageBlob());
		}
		$exts = userext($con,$usr["id"]);
		$lng = "";
		if(isset($_POST["langs"]))
			foreach($_POST["langs"] as &$ulng)
				$lng .= $ulng.",";
		$crt = "";
		if(isset($_POST["certs"]))
			foreach($_POST["certs"] as &$ucrt)
				$crt .= $ucrt.",";
		userext_edit($con,$usr["id"],social_singular($_POST),
			$exts["worktime"],strtotime($_POST["bdate"]),$_POST["addr"],$_POST["phone"],$_POST["pph"],$_POST["comp"],$crt,$lng);
		echo "edit complete!";
		header("location: /user.php?id=".$usr["id"]."&edit");
		$followers = follow_of_usr($con,$usr["id"]);
		foreach($followers as &$follower)
			notify_upd_usr($con,$follower["srcid"],$usr["id"]);
		exit(0);
	}
	if(isset($_POST["u"]) && isset($_POST["p"]) && isset($_POST["t"]) && !(isset($_POST["reg"]) || isset($_GET["reg"])) )
	{
		$usr = 	$_POST["u"];
		$pwd = $_POST["p"];
		$time = $_POST["t"];
		$obj = username($con,$usr);
		if(!$obj)
			$obj = usermail($con,$usr);
		$ext = "";
		if(!$obj)
		{
		//	if(intval(strpos($_POST["m"],"@") > 0))
				$ext = "&u=".$_POST["u"]."&p=".base64_encode($_POST["p"]);
		//	else
		//		$ext = "&p=".base64_encode($_POST["p"]);
			if(!empty($r))
				header("location: ".base64_decode($r).$ext);
			else if(isset($_SERVER['HTTP_REFERER']))
				header("location: ".$_SERVER['HTTP_REFERER']);
			else
				header("location: /?f".$ext);
			exit(0);
		}
		$md5 = md5($obj["name"].":".$pwd);
		$s = sessions($con,$obj["id"]);
		foreach($s as &$sess)
			if(	strtotime($sess["since"]) < (time()-(24*60*60)) || $is_single_session)
				session_del($con,$sess["sessionid"]);
		if($obj["pass"] == $md5)
			session_reg($con,$obj["id"],session_id());
		else
			$ext = "&pwd";
		if(!empty($r))
			header("location: ".base64_decode($r).$ext);
		else if(isset($_SERVER['HTTP_REFERER']))
			header("location: ".$_SERVER['HTTP_REFERER']);
		else
			header("location: /");
		exit(0);
	}
	if(isset($_GET["logout"]))
	{
		session_del($con,session_id());
		session_destroy();
		if(isset($_SERVER['HTTP_REFERER']))
		if(!empty($r))
			header("location: ".base64_decode($r));
		else if(isset($_SERVER['HTTP_REFERER']))
			header("location: ".$_SERVER['HTTP_REFERER']);
		else
			header("location: /");
		exit(0);
	}
	echo "<html>\n<head>\n";
	require_once($_SERVER["DOCUMENT_ROOT"]."/inc.php");
	$btxt = "join us";
	echo "</head><body id='page' >\n";
	require_once("header.php");
	if(isset($_POST["reg"]) || isset($_GET["reg"]))
	{
		//echo "<center>\n";
		if(isset($_GET["short"]))
			echo "Password is too short!<br>\n";
		if(isset($_GET["match"]))
			echo "Passwords does not match!<br>\n";
		if(isset($_GET["exists"]))
			echo "Username or Mail already exists!<br>\n";
		if(isset($_GET["missing"]))
			echo "Missing parameters provided!<br>\n";
		echo "<form class='reg-form' action='?done' method=POST enctype='multipart/form-data' ><br>\n";
		echo "<input required type=text name=u value='".$_POST["u"]."' placeholder='username' ></input><br>\n";
		echo "<input required type=email name=m placeholder='email' ></input><br>\n";
		echo "<input required type=password name=p value='".$_POST["p"]."' placeholder='password' ></input><br>\n";
		//echo "<input required type=password name=v placeholder='Password, just to be sure' ></input><br>\n";
		echo "<input id=chkbox required type=checkbox name=a ></input><label for=chkbox > I agree terms and conditions by signing-up...</label><br>\n";
//		echo "<input type=text name=r placeholder='Real Name?' ></input><br>\n";
		echo "<input type=submit value='join'></input><br>\n";
		echo "</form>\n";
		//echo "</center>";
		exit(0);
	}
	echo "</body>\n</html>\n";
	header("location: /");
	db_end($con);
	if(isset($_GET["welcome"])) echo "ok";
?>
