<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/mysql.php");
	$con = db_conn($host,$user,$pass,$db);
//	session_start();
	$uid = session_chk($con,session_id());
	$perm = user_perm($con,-1); // false perms
	if($uid != NULL)
		$perm = user_perm($con,$uid);
	//$can_comment = $perm["w"];
	if($uid != NULL && isset($_GET["item"]) && !empty($_POST["c"]))
	{
		$last = last_comment($con,$_GET["item"]);
		comment_add($con,$_GET["item"],$uid,$_POST["c"]);
		header("location: /item.php?hash=".$_GET["item"]);
		$orig = item($con,$_GET["item"]);
		notify_comm_item($con,$orig["owner"],$_GET["item"]);
		notify_comm_comm($con,$last["uid"],$_GET["item"]);
		$followers = follow_of_obj($con,$_GET["item"]);
		foreach($followers as &$follower)
			notify_comm_flw($con,$follower["srcid"],$_GET["item"]);
		exit(0);
	}
	if($uid != NULL && isset($_GET["del"]))
	{
		$comm = comment($con,$_GET["del"]);
		comment_del($con,$_GET["del"]);
		header("location: /item.php?hash=".$comm["item"]);
		exit(0);
	}
	if($uid != NULL && isset($_GET["okay"]) && !empty($_POST["c"]))
	{
		$comm = comment($con,$_GET["okay"]);
		comment_edit($con,$_GET["okay"],$_POST["c"]);
		header("location: /item.php?hash=".$comm["item"]);
		exit(0);
	}
	if($uid == NULL || !isset($_GET["edit"]))
	{
		header("location: /");
		exit(0);
	}
?>
<html>
	<head>
		<?php require_once($_SERVER["DOCUMENT_ROOT"]."/inc.php"); ?>
	</head>
	<body id="edit" >
	<?php
		$comm = comment($con,$_GET["edit"]);
		echo "<form action='/comment.php?okay=".$_GET["edit"]."' method=POST >\n";
		echo "<textarea cols=40 rows=13 maxlength=500 required placeholder='Comment here...' name=c >".$comm["content"]."</textarea><br>\n";
		echo "<input type=submit ></input><br>\n";
		echo "</form>\n";
	?>
	<?php require_once("header.php"); ?>
	</body>
</html>
<?php db_end($con); ?>
