<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/mysql.php");
	$con = db_conn($host,$user,$pass,$db);
//	session_start();
	$uid = session_chk($con,session_id());
	$val = explode("/",$_SERVER["REQUEST_URI"])[1];
	if(!empty($val))
	{
		$id = username($con,mysql_real_escape_string($val))["id"];
		if($id != 0)
		{
			$_GET["id"] = $id;
			require_once("user.php");
			exit(0);
		}
	}
	header("location: /");
?>
