<?php
//	header("Access-Control-Allow-Origin: *");
//	header('P3P: CP="CAO PSA OUR"');
	session_cache_limiter(false);
	header("pragma: cache");
	$is_single_session = false;
	$accounts = array("fb","tw","vk","ggl","pin","inst","in","tmb");
	$anames = array(
		"fb" => "facebook",
		"tw" => "twitter",
		"vk" => "vkontakte",
		"ggl" => "google plus",
		"pin" => "pinterest",
		"inst" => "instagram",
		"in" => "linkedin",
		"tmb" => "tumblr",
	);
	$alinks = array(
		"fb" => "facebook.com/",
		"tw" => "twitter.com/",
		"vk" => "vk.com/",
		"ggl" => "plus.google.com/",
		"pin" => "pinterest.com/?",
		"inst" => "instagram.com/",
		"in" => "linkedin.com/",
		"tmb" => "tumblr.com/",
	);
	$defimg = "images/noimg.jpg";
	$con = false;
	session_set_cookie_params(60*60*24,'/',implode('.',array_slice(explode('.',$_SERVER['HTTP_HOST']),-2)),false,false);
	if(file_exists($_SERVER["DOCUMENT_ROOT"]."/sections.txt"))
		$sections = file($_SERVER["DOCUMENT_ROOT"]."/sections.txt",FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	$host = "localhost:3306";
	$user = "abbox";
	$pass = "nopasswordforyou";
	$db = "abbox";
	$domains = array("abbox.co","abbox.io");
//	if(isset($_GET["PHPSESSID"])) setcookie("PHPSESSID",$_GET["PHPSESSID"]);
//	if(isset($_COOKIE["PHPSESSID"])) session_id($_COOKIE["PHPSESSID"]);
	session_start();
//	foreach($domains as &$dom)
//		setcookie(session_name(),session_id(),time()+24*60*60,"/",$dom);
	function db_conn($addr,$user,$pass,$schema)
	{
		$db = mysql_connect($addr,$user,$pass) or die("error connecting db");
		mysql_select_db($schema);
		//$con = $db;
		return $db;
	}
	function db_end($dbcon)
	{
		mysql_close($dbcon);
	}
	function user($db,$id)
	{
		$res = mysql_query("select * from users where id = '".$id."';",$db);
		$obj = mysql_fetch_array($res,MYSQL_ASSOC);
		mysql_free_result($res);
		return $obj;
	}
	function username($db,$name)
	{
		$res = mysql_query("select * from users where name = '".$name."';",$db);
		$obj = mysql_fetch_array($res,MYSQL_ASSOC);
		mysql_free_result($res);
		return $obj;
	}
	function usermail($db,$mail)
	{
		$res = mysql_query("select * from users where mail = '".$mail."';",$db);
		$obj = mysql_fetch_array($res,MYSQL_ASSOC);
		mysql_free_result($res);
		return $obj;
	}
	function users($db)
	{
		$ret = array();
		$res = mysql_query("select * from users order by `create` desc;",$db);
		while($user = mysql_fetch_array($res,MYSQL_ASSOC))
			$ret[] = $user;
		mysql_free_result($res);
		return $ret;
	}
	function user_reg($db,$user,$mail,$pass,$real)
	{
		$res = mysql_query("insert into users VALUES( default,'".mysql_real_escape_string($user)."','".mysql_real_escape_string($real)."','".mysql_real_escape_string($mail)."','10',md5('".$user.":".$pass."'),current_timestamp() );",$db) or die("error creating user");
		$curr_id = mysql_insert_id();
		$res = userext_create($db,"".$curr_id,"",0,"",null,0,0);
		//mysql_free_result($res);
		return $curr_id;
	}
	function user_update($db,$id,$name,$mail,$real,$pass)
	{
		$q_pass = " ";
		if(!empty($pass))
			$q_pass = ", pass = '".md5($name.":".$pass)."' ";
		$res = mysql_query("update users set `real` = '".mysql_real_escape_string($real)."', mail = '".mysql_real_escape_string($mail)."' ".$q_pass." where id = '".$id."';",$db) or die("error modifying user");
		//mysql_free_result($res);
	}
	function user_lvl($db,$id,$lvl)
	{
		$res = mysql_query("update users set level = '".$lvl."' where id = '".$id."';",$db) or die("error changing level");
		//mysql_free_result($res);
	}
	function user_perm($db,$id)
	{
		$rwx = array(
			"r" => false,
			"w" => false,
			"x" => false,
			"n" => false,
		);
		if($id < 0)
			return $rwx;
		$res = mysql_query("select level from users where id = '".$id."';",$db) or die("error on db");
		$level = mysql_fetch_array($res,MYSQL_ASSOC)["level"];
		mysql_free_result($res);
		if($level > 0)
			$rwx["n"] = true;
		if($level >= 25)
			$rwx["r"] = true;
		if($level >= 50)
			$rwx["w"] = true;
		if($level > 75)
			$rwx["x"] = true;
		if($level > 99)
			$rwx["s"] = true;
		return $rwx;
	}
	function session_reg($db,$uid,$sessid)
	{
		$res = mysql_query("insert into sessions values ('".$sessid."','".$uid."',current_timestamp(),'".$_SERVER["REMOTE_ADDR"]."');",$db) or die("error registering session");
		//mysql_free_result($res);
	}
	function session_del($db,$sessid)
	{
		$res = mysql_query("delete from sessions where sessionid = '".$sessid."';",$db) or die("error unregistering session");
		//mysql_free_result($res);
	}
	function session_clear($db,$uid)
	{
		$res = mysql_query("delete from sessions where userid = '".$uid."';",$db) or die("error clearing sessions");
		//mysql_free_result($res);
	}
	function session_chk($db,$sessid)
	{
		$res = mysql_query("select * from sessions where sessionid = '".$sessid."';",$db) or die("error checking session");
		$ret = mysql_fetch_array($res)["userid"]; //mysql_num_rows($res);
		mysql_free_result($res);
		return $ret;
	}
	function sessions($db,$uid)
	{
		$ret = array();
		$res = mysql_query("select * from sessions where userid = '".$uid."' order by `since` asc;",$db) or die("error getting sessions");
		while($session = mysql_fetch_array($res,MYSQL_ASSOC))
			$ret[] = $session;
		mysql_free_result($res);
		return $ret;
	}
	function session_list($db)
	{
		$ret = array();
		$res = mysql_query("select * from sessions order by since desc;",$db) or die("error listing sessions");
		while($session = mysql_fetch_array($res,MYSQL_ASSOC))
			$ret[] = $session;
		mysql_free_result($res);
		return $ret;
	}
	function item_reg($db,$uid,$name,$desc,$price,$type)
	{
		$hash = md5($uid.":".date("Y-m-d H:i:s"));
		$res = mysql_query("insert into items values ('".$hash."',current_timestamp(),'".mysql_real_escape_string($name)."','".mysql_real_escape_string($desc)."','".$uid."','".$price."','".$type."');",$db) or die("error registering item");
		//mysql_free_result($res);
		return $hash;
	}
	function item_del($db,$hash)
	{
		$res = mysql_query("delete from items where hash = '".$hash."';",$db) or die("error unregistering item");
		//mysql_free_result($res);
	}
	function item_edit($db,$hash,$name,$desc,$price,$type)
	{
		$res = mysql_query("update items set `price` = '".$price."', `desc` = '".mysql_real_escape_string($desc)."', `name` = '".mysql_real_escape_string($name)."', `type` = '".$type."' where `hash` = '".$hash."';",$db) or die("error updating item");
		//mysql_free_result($res);
	}
	function item_send($db,$hash,$to)
	{
		$res = mysql_query("select * from items where hash = '".$hash."';",$db) or die("error getting ts while moving");
		$val = mysql_fetch_array($res,MYSQL_ASSOC)["create"];
		mysql_free_result($res);
		$res = mysql_query("update items set owner = '".$to."', hash = md5('".$to.":".$val."') where hash = '".$hash."';",$db) or die("error assigning while moving");
		mysql_free_result($res);
		return $val;
	}
	function item_search($db,$kwd)
	{
		$ret = array();
		$res = mysql_query("select * from items where name like '%".$kwd."%' or `desc` like '%".$kwd."%' order by `create` desc;",$db) or die("error listing items");
		while($item = mysql_fetch_array($res,MYSQL_ASSOC))
			$ret[] = $item;
		mysql_free_result($res);
		return $ret;
	}
	function item($db,$id)
	{
		$res = mysql_query("select * from items where hash = '".$id."';",$db) or die("error getting item");
		$ret = mysql_fetch_array($res,MYSQL_ASSOC);
		mysql_free_result($res);
		return $ret;
	}
	function items($db)
	{
		$ret = array();
		$res = mysql_query("select * from items order by `create` desc;",$db) or die("error listing all items");
		while($item = mysql_fetch_array($res,MYSQL_ASSOC))
			$ret[] = $item;
		mysql_free_result($res);
		return $ret;
	}
	function itemsby($db,$uid)
	{
		$ret = array();
		$res = mysql_query("select * from items where owner = '".$uid."' order by `create` desc;",$db) or die("error listing user items");
		while($item = mysql_fetch_array($res,MYSQL_ASSOC))
			$ret[] = $item;
		mysql_free_result($res);
		return $ret;
	}
	function itemsby_type($db,$uid,$type,$not)
	{
		$ret = array();
		$res = mysql_query("select * from items where owner = '".$uid."' and type ".($not?"!=":"=")." '".$type."' order by `create` desc;",$db) or die("error listing user items by type");
		while($item = mysql_fetch_array($res,MYSQL_ASSOC))
			$ret[] = $item;
		mysql_free_result($res);
		return $ret;
	}
	function comments($db,$item)
	{
		$ret = array();
		$res = mysql_query("select * from comments where item = '".$item."' order by `create` ;",$db) or die("error getting comments");
		while($comm = mysql_fetch_array($res,MYSQL_ASSOC))
			$ret[] = $comm;
		mysql_free_result($res);
		return $ret;
	}
	function comment($db,$hash)
	{
		$res = mysql_query("select * from comments where hash = '".$hash."';",$db) or die("error getting comment");
		$comm = mysql_fetch_array($res,MYSQL_ASSOC);
		mysql_free_result($res);
		return $comm;
	}
	function comment_add($db,$item,$uid,$comm)
	{
		$hash = md5($uid.":".date("Y-m-d H:i:s"));
		$res = mysql_query("insert into comments values ('".$hash."','".$uid."',current_timestamp(),NULL,'".$item."','".mysql_real_escape_string($comm)."');",$db) or die("error registering comment");
		//mysql_free_result($res);
		return $hash;
	}
	function comment_del($db,$hash)
	{
		$res = mysql_query("delete from comments where hash = '".$hash."';",$db) or die("error deleting comment");
		//mysql_free_result($res);
	}
	function comment_edit($db,$hash,$comm)
	{
		$res = mysql_query("update comments set content = '".mysql_real_escape_string($comm)."', edit = current_timestamp() where hash = '".$hash."';",$db) or die("error modifying comment");
		//mysql_free_result($res);
	}
	function userext_create($db,$id,$sm,$wt,$bd,$addr,$num,$pph)
	{
		$res = mysql_query("insert into userext values ('".$id."',null,'".$sm."','".intval($wt)."','".date("Y-m-d H:i:s",$bd)."','".mysql_real_escape_string($addr)."','".$num."','".intval($pph)."',current_timestamp(),default,default,default);",$db) or die("error adding user exts");
		//mysql_free_reuslt($res);
	}
	function userext_edit($db,$id,$sm,$wt,$bd,$addr,$num,$pph,$comp,$cert,$lang)
	{
		$res = mysql_query("update userext set sm_links = '".$sm."', worktime = '".intval($wt)."', bdate = '".date("Y-m-d H:i:s",$bd)."', addr = '".mysql_real_escape_string($addr)."', phone = '".$num."', pph = '".intval($pph)."', lastseen = current_timestamp(), comp = '".$comp."', certs = '".$cert."', langs = '".$lang."' where uid = '".$id."';",$db) or die("error updating exts");
		//mysql_free_result($res);
	}
	function userext_del($db,$id)
	{
		$res = mysql_query("delete from userext where uid = '".$id."';",$db) or die("error deleting exts");
		//mysql_free_result($res);
	}
	function userext_clear($db,$id)
	{
		$res = mysql_query("update userext set sm_links = NULL, worktime = 0, bdate = NULL, addr = NULL, num = NULL, pph = 0, lastseen = NULL, comp = NULL, cert = NULL, lang = NULL where uid = '".$id."';",$db) or die("error cleaning exts");
		//mysql_free_result($res);
	}
	function userext($db,$id)
	{
		$res = mysql_query("select * from userext where uid = '".$id."';",$db) or die("error getting exts");
		$ext = mysql_fetch_array($res,MYSQL_ASSOC);
		mysql_free_result($res);
		return $ext;
	}
	function userext_seen($db,$id)
	{
		$res = mysql_query("update userext set lastseen = current_timestamp() where uid = '".$id."';",$db) or die("error updating lastseen");
		//mysql_free_result($res);
	}
	function follow_add_usr($db,$id,$usr)
	{
		$res = mysql_query("insert into followtab values ('".$id."','".$usr."','');",$db) or die("error adding follow #1");
		//mysql_free_result($res);
	}
	function follow_add_obj($db,$id,$hash)
	{
		$res = mysql_query("insert into followtab values ('".$id."','','".$hash."');",$db) or die("error adding follow #2");
		//mysql_free_result($res);
	}
	function follow_del_usr($db,$id,$usr)
	{
		$res = mysql_query("delete from followtab where srcid = '".$id."' and destid = '".$usr."';",$db) or die("error removing follow #1");
		//mysql_free_result($res);
	}
	function follow_del_obj($db,$id,$hash)
	{
		$res = mysql_query("delete from followtab where srcid = '".$id."' and desthash = '".$hash."';",$db) or die("error removing follow #2");
		//mysql_free_result($res);
	}
	function follow_of_usr($db,$id)
	{
		$res = mysql_query("select * from followtab where destid = '".$id."';",$db) or die("error getting follow #1");
		$arr = array();
		while($usr = mysql_fetch_array($res,MYSQL_ASSOC))
			$arr[] = $usr;
		mysql_free_result($res);
		return $arr;
	}
	function follow_of_obj($db,$hash)
	{
		$res = mysql_query("select * from followtab where desthash = '".$hash."';",$db) or die("error getting follow #2");
		$arr = array();
		while($usr = mysql_fetch_array($res,MYSQL_ASSOC))
			$arr[] = $usr;
		mysql_free_result($res);
		return $arr;
	}
	function follow_q_usr($db,$id,$usr)
	{
		$res = mysql_query("select * from followtab where destid = '".$usr."' and srcid = '".$id."';",$db) or die("error follow? #1");
		$ret = mysql_fetch_array($res,MYSQL_ASSOC);
		mysql_free_result($res);
		return $ret;
	}
	function follow_q_obj($db,$id,$hash)
	{
		$res = mysql_query("select * from followtab where desthash = '".$hash."' and srcid = '".$id."';",$db) or die("error follow? #2");
		$ret = mysql_fetch_array($res,MYSQL_ASSOC);
		mysql_free_result($res);
		return $ret;
	}
	function follows($db,$id)
	{
		$res = mysql_query("select * from followtab where srcid = '".$id."';",$db) or die("error getting follow #3");
		$arr = array();
		while($usr = mysql_fetch_array($res,MYSQL_ASSOC))
			$arr[] = $usr;
		mysql_free_result($res);
		return $arr;
	}
	function follows_usr($db,$id)
	{
		$res = mysql_query("select * from followtab where srcid = '".$id."' and destid != '0';",$db) or die("error getting follow #4");
		$arr = array();
		while($usr = mysql_fetch_array($res,MYSQL_ASSOC))
			$arr[] = $usr;
		mysql_free_result($res);
		return $arr;
	}
	function follows_obj($db,$hash)
	{
		$res = mysql_query("select * from followtab where srcid = '".$hash."' and desthash != '';",$db) or die("error getting follow #5");
		$arr = array();
		while($usr = mysql_fetch_array($res,MYSQL_ASSOC))
			$arr[] = $usr;
		mysql_free_result($res);
		return $arr;
	}
	function message($db,$id,$to,$msg,$attach)
	{
		$res = mysql_query("insert into messages values (default,'".$id."','".$to."','".mysql_real_escape_string($msg)."','".$attach."','',current_timestamp());",$db) or die("error registering message");
		//mysql_free_result($res);
		return mysql_insert_id();
	}
	function messages($db,$id)
	{
		$res = mysql_query("select * from messages where `to` = '".$id."';",$db) or die("error getting messages #1");
		$arr = array();
		while($msg = mysql_fetch_array($res,MYSQL_ASSOC))
			$arr[] = $msg;
		mysql_free_result($res);
		return $arr;
	}
	function messages_all($db,$id)
	{
		$res = mysql_query("select * from messages where `from` = '".$id."' or `to` = '".$id."' order by `when` desc;",$db) or die("error getting messages #4");
		$arr = array();
		while($msg = mysql_fetch_array($res,MYSQL_ASSOC))
			$arr[] = $msg;
		mysql_free_result($res);
		return $arr;
	}
	function messages_sent($db,$id)
	{
		$res = mysql_query("select * from messages where `from` = '".$id."';",$db) or die("error getting messages #2");
		$arr = array();
		while($msg = mysql_fetch_array($res,MYSQL_ASSOC))
			$arr[] = $msg;
		mysql_free_result($res);
		return $arr;
	}
	function message_del($db,$msgid)
	{
		$res = mysql_query("delete from messages where msgid = '".$msgid."';",$db) or die("error deleting message");
		//mysql_free_result($res);
	}
	function message_get($db,$msgid)
	{
		$res = mysql_query("select * from messages where msgid = '".$msgid."';",$db) or die("error getting msg");
		$ret = mysql_fetch_array($res,MYSQL_ASSOC);
		mysql_free_result($res);
		return $ret;
	}
	function messages_bw($db,$from,$to)
	{
		$cmd = "((`from` = '".$from."' and `to` = '".$to."') or (`from` = '".$to."' and `to` = '".$from."'))";
		$res = mysql_query("select * from messages where ".$cmd." ;",$db) or die("error getting messages #3");
		$arr = array();
		while($msg = mysql_fetch_array($res,MYSQL_ASSOC))
			$arr[] = $msg;
		mysql_free_result($res);
		return $arr;
	}
	function social_parse($soc_csv)
	{
		$fields = str_getcsv($soc_csv);
		$links = array();
		foreach($fields as &$field)
		{
			if(empty($field))
				continue;
			$base = explode(":",$field,2);
			$links[$base[0]] = $base[1];
		}
		return $links;
	}
	function social_singular($soc_arr)
	{
		if(empty($soc_arr))
			return "none:none";
		$data = "";
		global $accounts;
		foreach($accounts as &$acc)
			if(!empty($soc_arr[$acc]))
				$data .= "".$acc.":".$soc_arr[$acc].",";
		return $data;
	}
	function cart_get($db,$id)
	{
		$res = mysql_query("select * from userext where uid = '".$id."';",$db) or die("error getting cart");
		$cart = mysql_fetch_array($res,MYSQL_ASSOC)["cart"];
		mysql_free_result($res);
		if(empty($cart))
			return array();
		$arr = str_getcsv($cart);
		$fixed = array();
		for($i=0;$i<sizeof($arr);$i++)
			if(!empty($arr[$i]))
				$fixed[] = $arr[$i];
		return $fixed;
	}
	function cart_set($db,$id,$arr)
	{
		$str = "";
		foreach($arr as &$item)
			if(!empty($item))
				$str .= $item.",";
		$res= mysql_query("update userext set cart = '".$str."' where uid = '".$id."';",$db) or die("error setting cart");
		//mysql_free_result($res);
	}
	function cart_add($db,$id,$hash)
	{
		$curr = cart_get($db,$id);
		$curr[] = $hash;
		cart_set($db,$id,$curr);
	}
	function cart_del($db,$id,$hash)
	{
		$curr = cart_get($db,$id);
		$new = array();
		for($i=0;$i<sizeof($curr);$i++)
			if($curr[$i] != $hash)
				$new[] = $curr[$i];
		cart_set($db,$id,$new);
	}
	function cart_chk($db,$id,$hash)
	{
		$arr = cart_get($db,$id);
		foreach($arr as &$i)
			if($i == $hash)
				return true;
		return false;
	}
	function cart_sum($db,$id)
	{
		$total = 0;
		$cart = cart_get($db,$id);
		foreach($cart as &$item)
		{
			$details = item($db,$item);
			$total += intval($details["price"]);
		}
		return $total;
	}
	function search_obj($db,$str)
	{
		$arr = array();
		$val = array();
		$rat = array("","desc","name");
		$keywords = explode(" ",$str);
		if(strlen($keywords[0]) < 2)
			return array(array(),array());
		for($i=1;$i<sizeof($rat);$i++)
		{
			foreach($keywords as &$key)
			{
				if(empty($key))
					continue;
				$res = mysql_query("select * from items where `".$rat[$i]."` like '%".mysql_real_escape_string($key)."%' order by `create` desc;",$db) or die("error in search query #1");
				while($item = mysql_fetch_array($res,MYSQL_ASSOC))
				{
					$arr[$item["hash"]] = $item;
					$prc = 0;
					if(isset($val[$item["hash"]]))
						$prc = intval($val[$item["hash"]]);
					$val[$item["hash"]] = $prc+2;
				}
				mysql_free_result($res);
			}
		}
		return array($val,$arr);
	}
	function search_usr($db,$name)
	{
		$arr = array();
		$keywords = explode(" ",$name);
		if(strlen($keywords[0]) < 2)
			return array();
		foreach($keywords as &$key)
		{
			if(empty($key))
				continue;
			$res = mysql_query("select * from users where `name` like '%".mysql_real_escape_string($key)."%' or `real` like '%".mysql_real_escape_string($key)."%' order by `create` desc;",$db) or die("error in user query");
			while($user = mysql_fetch_array($res,MYSQL_ASSOC))
				$arr[] = $user;
			mysql_free_result($res);
		}
		return $arr;
	}
	function notify($db,$id,$src,$msg)
	{
		$open = explode(":",$src,2);
		$res = mysql_query("insert into notifications values (default,'0','".$id."','".$open[0]."','".$open[1]."','".$msg."',current_timestamp());",$db) or die("error notifying");
		//mysql_free_result($res);
		return mysql_insert_id();
	}
	function notify_ok($db,$nid)
	{
		$res = mysql_query("update notifications set `read` = '1' where `nid` = '".$nid."';",$db) or die("error in read notification");
		//mysql_free_result($res);
	}
	function notify_upd_usr($db,$id,$dest)
	{
		$txt = "has updated profile";
		$comp = "u:".$dest;
		return notify($db,$id,$comp,$txt);
	}
	function notify_upd_obj($db,$id,$dest)
	{
		$txt = "has updated information";
		$comp = "i:".$dest;
		return notify($db,$id,$comp,$txt);
	}
	function notify_recv_msg($db,$id,$dest)
	{
		$txt = "has messaged you";
		$comp = "m:".$dest;
		return notify($db,$id,$comp,$txt);
	}
	function notify_comm_item($db,$id,$dest)
	{
		$txt = "has commented your item";
		$comp = "c:".$dest;
		return notify($db,$id,$comp,$txt);
	}
	function notify_comm_comm($db,$id,$dest)
	{
		$txt = "has replied your comment";
		$comp = "c:".$dest;
		return notify($db,$id,$comp,$txt);
	}
	function notify_comm_flw($db,$id,$dest)
	{
		$txt = "has commented followed item";
		$comp = "c:".$dest;
		return notify($db,$id,$comp,$txt);
	}
	function notify_del($db,$nid)
	{
		$res = mysql_query("delete from notifications where `nid` = '".$nid."';",$db) or die("error deleting notification");
		//mysql_free_result($res);
	}
	function notifications($db,$uid)
	{
		if($uid == NULL)
			return array();
		$res = mysql_query("select * from notifications where `uid` = '".$uid."' and `read` = '0' order by `when` desc;",$db) or die("error getting notifications");
		$ret = array();
		while($i = mysql_fetch_array($res,MYSQL_ASSOC))
			$ret[] = $i;
		mysql_free_result($res);
		return $ret;
	}
	function notify_read_msg($db,$id,$dest)
	{
		$res = mysql_query("update notifications set `read` = '1' where `type` = 'm' and `target` = '".$dest."' and `uid` = '".$id."';",$db) or die("error in read notification #1");
		//mysql_free_result($res);
	}
	function notify_read_obj($db,$id,$dest)
	{
		$res = mysql_query("update notifications set `read` = '1' where `type` = 'i' and `target` = '".$dest."' and `uid` = '".$id."';",$db) or die("error in read notification #2");
		//mysql_free_result($res);
	}
	function notify_read_usr($db,$id,$dest)
	{
		$res = mysql_query("update notifications set `read` = '1' where `type` = 'u' and `target` = '".$dest."' and `uid` = '".$id."';",$db) or die("error in read notification #3");
		//mysql_free_result($res);
	}
	function notify_read_comm($db,$id,$dest)
	{
		$res = mysql_query("update notifications set `read` = '1' where `type` = 'c' and `target` = '".$dest."' and `uid` = '".$id."';",$db) or die("error in read notification #4");
		//mysql_free_result($res);
	}
	function last_comment($db,$item)
	{
		$res = mysql_query("select * from comments where item = '".$item."' order by `create` desc;",$db) or die("error getting last comment");
		$comm = mysql_fetch_array($res,MYSQL_ASSOC);
		mysql_free_result($res);
		return $comm;
	}
	function user_getname($db,$id)
	{
		$usr = user($db,$id);
		$uname = $usr["name"];
		if(!empty($usr["real"]))
			$uname = explode(' ',trim($usr["real"]))[0];
		return ($uname);
	}
?>
