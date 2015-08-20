<?php
	if(!isset($_GET["new"]))
	$home = "/?new";
	$max_items = 24;
	require_once($_SERVER["DOCUMENT_ROOT"]."/mysql.php");
	$con = db_conn($host,$user,$pass,$db);
//	session_start();
	$uid = session_chk($con,session_id());
	$page = 0;
	if(isset($_GET["p"]))
		$page = intval($_GET["p"]);
	$cl = " ";
	if( isset($_GET["new"]) || isset($_GET["cat"]) )
		$cl = "sp";
?>
<html>
	<head>
		<meta name="google-site-verification" content="mnQoEHkx7OSqnfzh3P4EuF-Y8NnOFIyvJAMv__ISuT8" />
		<?php require_once($_SERVER["DOCUMENT_ROOT"]."/inc.php"); ?>
	</head>
	<body id=page class="<?php echo $cl; ?>">
		<?php
		if(isset($_GET["new"]))
		{
			echo "<a class='cat-title' href='/' ><h2>newest</h2></a>\n";
			echo "<div class='box category cat-singular' id='new' >\n";
			$res = mysql_query("select * from items where type != 'orders' order by `create` desc;",$con) or die("db error");
			for($j=0;$j<($page*$max_items);$j++)
				$item = mysql_fetch_array($res,MYSQL_ASSOC);
			for($i=0;$i<$max_items;$i++)
			{
				$item = mysql_fetch_array($res,MYSQL_ASSOC);
				if(!$item) break;
				echo "<a href='/item.php?hash=".$item["hash"]."'><div class='bg-base item cover' data-bg='/item.php?pic=".$item["hash"]."' ><div class=ininfo >".htmlentities(substr($item["name"],0,11))."</div><div class=ipinfo >$".$item["price"]."</div></div></a>\n";
			}
			mysql_free_result($res);
			echo "</div>\n<hr>\n";
			if($page)
				echo "<a class='rounded morebtn' href='?new&p=".($page-1)."' > previous </a>\n";
			if($i+1 > $max_items)
				echo "<a class='rounded morebtn' href='?new&p=".($page+1)."' > next </a>\n";

		}
		else if(!isset($_GET["cat"]))
		{
			echo "<a class='cat-title' href='/?new' ><h2>newest</h2></a>\n";
			echo "<div class='box category twolines' id='new' >\n";
			$res = mysql_query("select * from items where type != 'orders' order by `create` desc;",$con) or die("db error");
			for($j=0;$j<($page*$max_items);$j++)
				$item = mysql_fetch_array($res,MYSQL_ASSOC);
			for($i=0;$i<$max_items;$i++)
			{
				$item = mysql_fetch_array($res,MYSQL_ASSOC);
				if(!$item) break;
				echo "<a href='/item.php?hash=".$item["hash"]."'><div class='bg-base item cover' data-bg='/item.php?pic=".$item["hash"]."' ><div class=ininfo >".htmlentities(substr($item["name"],0,11))."</div><div class=ipinfo >$".$item["price"]."</div></div></a>\n";
//				if(($i + 1)%($max_items/3) == 0) echo "<br>\n";
			}
			echo "</div>\n<hr>\n";
			mysql_free_result($res);
			foreach($sections as &$sect)
			{
				echo "<a class='cat-title' href='/?cat=".$sect."' ><h2>". $sect ."</h2></a><!-- hr -->\n";
				echo "<div class='box category' id='".$sect."' >\n";
				$res = mysql_query("select * from items where type = '".$sect."' order by `create` desc;",$con) or die("db error");
				for($j=0;$j<($page*$max_items);$j++)
					$item = mysql_fetch_array($res,MYSQL_ASSOC);
				for($i=0;$i<$max_items;$i++)
				{
					$item = mysql_fetch_array($res,MYSQL_ASSOC);
					if(!$item) break;
					echo "<a href='/item.php?hash=".$item["hash"]."'><div class='bg-base item cover' data-bg='/item.php?pic=".$item["hash"]."' ><div class=ininfo >".htmlentities(substr($item["name"],0,11))."</div><div class=ipinfo >$".$item["price"]."</div></div></a>\n";
				}
				mysql_free_result($res);
				echo "</div>\n<hr>\n";
			}
		}else{
			if($_GET["cat"] != "orders" && !in_array($_GET["cat"],$sections))
			{
				header("location: /");
				exit(0);
			}
			if($_GET["cat"] == "orders")
				$btxt = "orders to be made";
			$sect = $_GET["cat"];
			echo "<a href='?cat=".$sect."' class='cat-title' ><h2>". $sect ."</h2></a>\n";
			echo "<div class='box category cat-singular' id='".$sect."'>\n";
			$res = mysql_query("select * from items where type = '".$sect."' order by `create` desc;",$con) or die("db error");
			for($j=0;$j<($page*$max_items);$j++)
				$item = mysql_fetch_array($res,MYSQL_ASSOC);
			for($i=0;$i<$max_items;$i++)
			{
				$item = mysql_fetch_array($res,MYSQL_ASSOC);
				if(!$item) break;
				echo "<a href='"."/item.php?hash=".$item["hash"]."' ><div class='item bg-base cover' data-bg='/item.php?pic=".$item["hash"]."' ><div class=ininfo>".htmlentities(substr($item["name"],0,11))."</div><div class=ipinfo>$".$item["price"]."</div></div></a>\n";
			}
			mysql_free_result($res);
			echo "</div>\n<hr>\n";
			if($page)
				echo "<a class='rounded morebtn' href='?cat=".$_GET["cat"]."&p=".($page-1)."' > previous </a> |\n";
			if($i+1 > $max_items)
				echo "<a class='rounded morebtn' href='?cat=".$_GET["cat"]."&p=".($page+1)."' > next </a>\n";
		}
		?>
		<?php require_once("header.php"); ?>
	</body>
</html>
<?php
	db_end($con);
?>
