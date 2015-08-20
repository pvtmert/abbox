<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/mysql.php");
	$con = db_conn($host,$user,$pass,$db);
	session_start();
	$uid = session_chk($con,session_id());
	//header("location: message.php");
	if(!isset($_GET["id"]))
		header("location: /");

?>
<html>
	<head><?php require_once($_SERVER["DOCUMENT_ROOT"]."/inc.php"); ?></head>
	<body id=page >

	<?php require_once("header.php"); ?>
	</body>
</html>
