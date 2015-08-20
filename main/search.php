<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/mysql.php");
	$con = db_conn($host,$user,$pass,$db);
//	session_start();
	$uid = session_chk($con,session_id());
	if(isset($_GET["buy"]))
	{
		cart_set($con,$uid,null);
		header("location: ?cart");
		exit(0);
	}
	if(empty($_GET) || (isset($_GET["q"]) && empty($_GET["q"])) || (isset($_GET["u"]) && empty($_GET["u"])))
	{
		header("location: /");
		exit(0);
	}
	function nores($arr)
	{
		if(empty($arr))
			echo "<div>Sorry no matching results!</div>\n";
	}
	$myself = "&nbsp;(you)";

	$qtype = "q";
	$qstr = "";
	$qarr = array("q","u","o","f","r","i");
	foreach($qarr as &$type)
		if(isset($_GET[$type]))
			$qstr = $_GET[$qtype = $type];
	$btxt = "results for ".substr($qstr,0,15);
	$title = "results for ".$qstr;
//	if($qtype == "r" || $qtype == "f") $btxt = $qtype." follow of ".$qstr;
	if(isset($_GET["cart"]))
	{
		$btxt = "your cart";
		$title = "cart";
	}
	$page = 0;
	if(isset($_GET["p"]))
		$page = intval($_GET["p"]);
	$max_results = 20;
	$i = 0;
	if(substr($qstr,0,1) == "/")
	{
		switch(true)
		{
			case (substr($qstr,1,3) == "acc"):
				header("location: /user.php");
				break;
			case (substr($qstr,1,4) == "home"):
				header("location: /");
				break;
			case (substr($qstr,1,3) == "msg"):
				header("location: /message.php");
				break;
			case (substr($qstr,1,5) == "order"):
				header("location: /orders.php");
				break;
			case (substr($qstr,1,4) == "quit"):
				header("location: /login.php?logout");
				break;
			case (substr($qstr,1,2) == "co"):
				header("location: //abbox.co");
				break;
			case (substr($qstr,1,2) == "io"):
				header("location: //abbox.io");
				break;
			default:
				header("location: /".substr($qstr,1));
		}
		exit(0);
	}
?>
<html>
	<head><?php require_once($_SERVER["DOCUMENT_ROOT"]."/inc.php"); ?></head>
	<body id=page >
	<?php
		if(!($qtype == "r" || $qtype == "f" || $qtype == "i") && !isset($_GET["cart"]))
		{
	?>
	<div class='tabs' >
		<a href="?q=<?php echo $qstr; ?>" ><div class="tab" <?php echo ($qtype == "q")?"on":"off"; ?> >models</div></a>
		<a href="?o=<?php echo $qstr; ?>" ><div class="tab" <?php echo ($qtype == "o")?"on":"off"; ?> >orders</div></a>
		<a href="?u=<?php echo $qstr; ?>" ><div class="tab" <?php echo ($qtype == "u")?"on":"off"; ?> >users</div></a>
	</div>
	<?php } ?>
	<?php
	echo "<div class='search' >\n";
		if(isset($_GET["cart"]))
		{
			$total = 0;
			$cart = cart_get($con,$uid);
			nores($cart);
			foreach($cart as &$item)
			{
				$details = item($con,$item);
				$total += intval($details["price"]);
				$item_pic = "uploads/items/".$details["hash"]."/lite.jpg";
				if(!file_exists($item_pic))
					$item_pic = $defimg;
				echo "<div class='citem' >\n";
				echo "<a href='/item.php?hash=".$item."' >\n";
				echo "<div class='bg-base cover ppic l' data-bg='/".$item_pic."' ></div>\n";
				echo "<div class='name' >".htmlentities($details["name"])."</div>\n";
				echo "</a>\n";
				echo "<div class='desc' >".$details["price"]."$</div>\n";
				echo "<a href='/item.php?hash=".$details["hash"]."&cart&cret' ><div class='btn' >remove</div></a>\n";
				echo "</div>\n<hr class='spage' />\n";
			}
		}
		if(($qtype == "q" || $qtype == "o") && !isset($_GET["cart"]))
		{
			$results = search_obj($con,$qstr);
			arsort($results[0]);
			//echo "<pre>"; var_dump($results); echo "</pre>";
			nores($results[1]);
			foreach($results[0] as $key => $val)
			{
				$item = $results[1][$key];
				if($qtype == "q")
					if($item["type"] == "orders")
						continue;
				if($qtype == "o")
					if($item["type"] != "orders")
						continue;
				if($i >= sizeof($results[0]))
					break;
				if($i < $max_results * $page)
				{
					$i += 1;
					continue;
				}
				if($i >= $max_results * ($page+1))
					break;
				$addrem = "";
				if(cart_chk($con,$uid,$item["hash"]))
					$addrem = "remove from cart";
				else
					$addrem = "add to cart";
				$item_pic = "uploads/items/".$item["hash"]."/lite.jpg";
				if(!file_exists($item_pic))
					$item_pic = $defimg;
				echo "<div class='citem' >\n";
				echo "<a href='/item.php?hash=".$item["hash"]."' >\n";
				echo "<div class='bg-base cover ppic l' data-bg='/".$item_pic."' ></div>\n";
				echo "<div class='name' >".htmlentities($item["name"])."</div>\n";
				echo "</a>\n";
				$mx = 300;
				if($qtype == "q") $mx = intval($mx/2);
				echo "<div class='desc' >".htmlentities(substr($item["desc"],0,$mx))."&nbsp;</div>\n";
				if($qtype == "q")
						echo "<a href='/item.php?hash=".$item["hash"]."&cart&q=".$qstr."' ><div class='btn' >".$addrem." - ".$item["price"]."$</div></a>\n";
				echo "</div>\n<hr />\n";
				$i += 1;
			}
			if(!isset($addrem) && !empty($results[1]))
				nores(array());
		}
		if($qtype == "u")
		{
			$results = search_usr($con,$qstr);
			nores($results);
			foreach($results as &$usr)
			{
				if(empty($usr))
					continue;
				if($i >= sizeof($results))
					break;
				if($i < $max_results * $page)
				{
					$i += 1;
					continue;
				}
				if($i >= $max_results * ($page+1))
					break;
				if(follow_q_usr($con,$uid,$usr["id"]))
					$flw = "un";
				else
					$flw = "+";
				$pic = "/user.php?pic=".$usr["id"];
				echo "<div class='citem' >\n";
				echo "<a href='/user.php?id=".$usr["id"]."' >\n";
				echo "<div class='bg-base cover ppic l' data-bg='".$pic."' ></div>\n";
				echo "<div class='name' >".$usr["name"]."</div>\n";
				echo "</a>\n";
				echo "<div class='desc' >".$usr["real"]."&nbsp;</div>\n";
				if($uid != $usr["id"])
					echo "<a href='/user.php?id=".$usr["id"]."&follow&u=".$qstr."' ><div class='btn' >".$flw."follow</div></a>\n";
				else
					echo "<div>".$myself."</div>\n";
				echo "</div>\n<hr />\n";
				$i += 1;
			}
		}
		if($qtype == "f" || $qtype == "r" || $qtype == "i")
		{
			if($qtype == "r")
				$results = follows_usr($con,$qstr);
			if($qtype == "f")
				$results = follow_of_usr($con,$qstr);
			if($qtype == "i")
				$results = follows_obj($con,$qstr);
			nores($results);
			foreach($results as &$dat)
			{
				if($i >= sizeof($results))
					break;
				if($i < ($max_results * $page))
				{
					$i += 1;
					continue;
				}
				if($i >= $max_results * ($page+1))
					break;
				if($qtype == "r")
					$usr = user($con,$dat["destid"]);
				if($qtype == "f")
					$usr = user($con,$dat["srcid"]);
				if($qtype == "i")
				{
					$item = item($con,$dat["desthash"]);
					if(empty($item))
						continue;
					$addrem = "";
					if(cart_chk($con,$uid,$item["hash"]))
						$addrem = "remove from cart";
					else
						$addrem = "add to cart";
					$item_pic = "uploads/items/".$item["hash"]."/lite.jpg";
					if(!file_exists($item_pic))
						$item_pic = $defimg;
					echo "<div class='citem' >\n";
					echo "<a href='/item.php?hash=".$item["hash"]."' >\n";
					echo "<div class='bg-base cover ppic l' data-bg='/".$item_pic."' ></div>\n";
					echo "<div class='name' >".htmlentities($item["name"])."</div>\n";
					echo "</a>\n";
					echo "<div class='desc' >".htmlentities($item["desc"])."&nbsp;</div>\n";
					echo "<a href='/item.php?hash=".$item["hash"]."&cart&q=".$qstr."' ><div class='btn' >".$addrem." - ".$item["price"]."$</div></a>\n";
					echo "</div>\n<hr>\n";
				}else{
					if(empty($usr))
						continue;
					if(follow_q_usr($con,$uid,$usr["id"]))
						$flw = "un";
					else
						$flw = "+";
					$pic = "/user.php?pic=".$usr["id"];
					echo "<div class='citem' >\n";
					echo "<a href='/user.php?id=".$usr["id"]."' >\n";
					echo "<div class='bg-base cover ppic l' data-bg='".$pic."' ></div>\n";
					echo "<div class='name' >".$usr["name"]."</div>\n";
					echo "</a>\n";
					echo "<div class='desc' >".$usr["real"]."&nbsp;</div>\n";
					if($uid != $usr["id"])
						echo "<a href='/user.php?id=".$usr["id"]."&follow&u=".$qstr."' ><div class='btn' >".$flw."follow</div></a>\n";
					else
						echo "<div>".$myself."</div>\n";
					echo "</div>\n<hr />\n";
				}
				$i += 1;
			}
		}
	?>
	</div>
	<?php
		if(!isset($_GET["cart"]))
		{
			if($page)
				echo "<a class='rounded morebtn' href='?".$qtype."=".$qstr."&p=".($page-1)."' > previous </a> |\n";
			if($i >= $max_results * ($page+1) && $i)
				echo "<a class='rounded morebtn' href='?".$qtype."=".$qstr."&p=".($page+1)."' > next </a>\n";
		}
		if(isset($_GET["cart"]) && $total > 0)
			echo "<div><a href='?buy' onclick='return confirm(\"this will empty your cart! sure?\")';><span class='rounded new' >buy ".$total."$</span></a></div>\n";
		if($qtype == "f" || $qtype == "r")
		{
			$qtype = "u";
			$qstr = "";
		}
		if($qtype == "i")
		{
			$qtype = "q";
			$qstr = "";
		}
	?>
	<?php require_once("header.php"); ?>
	</body>
</html>
