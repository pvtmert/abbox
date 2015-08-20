<?php
	require_once("../../mysql.php");
	$con = db_conn($host,$user,$pass,$db);
	session_start();
	$uid = session_chk($con,session_id());
	//if($uid == NULL) exit(0);
	header("content-type: text/plain");
	if(!isset($_GET["hash"])) exit(0);
	set_time_limit(120);
	$file = ("../../uploads/cache/".$_GET["hash"]);
	if(file_exists($file) && !isset($_GET["raw"]))
	{
		header("location: /stlcache/".$_GET["hash"]);
		exit(0);
		/*
		header("content-length: ".filesize($file));
		readfile($file);
		$fp = fopen($file,"rb");
		while(!feof($fp))
		{
			print(fread($fp, 1024*8));
			ob_flush();
			flush();
		}
		fclose($fp);
		flush();
		exit(0);
		*/
	}
	$file = ("../../uploads/stl/".$_GET["hash"]);
	if(isset($_GET["raw"]) && file_exists($file))
	{
		header("content-length: ".filesize($file));
		readfile($file);
		exit(0);
	}

	exec("bash compiler.sh ".("../../uploads/stl/".$_GET["hash"])." ".("../../uploads/cache/".$_GET["hash"]));
	readfile( ("../../uploads/cache/".$_GET["hash"]) );
	exit(0);

	//$_GET["file"] = $file;
	set_time_limit(333);
	ini_set('memory_limit','192M');
	require_once("json.php");
	exit(0);
	//$_GET['url'] = $file;
	//require_once("download.php");
?>
