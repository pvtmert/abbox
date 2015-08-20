<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/mysql.php");
	$con = db_conn($host,$user,$pass,$db);
	$parms = array("n","d","p"); //t
//	session_start();
	$uid = session_chk($con,session_id());
	$upic = "/user.php?pic=".$uid;
	if(isset($_GET["pic"]))
	{
		$upic = "uploads/items/".$_GET["pic"]."/lite.jpg";
		if(isset($_GET["hd"]))
			$upic = "uploads/items/".$_GET["pic"]."/0.jpg";
		if(!file_exists($upic))
			$upic = $defimg;
		header("content-type: image/jpeg");
		header("content-length: ".filesize($upic));
		header("last-modified: ".gmdate("D, d M Y H:i:s e",filemtime($upic)) );
		//header("expires: 0");
		header("cache-control: public, max-age=86400");
		ob_clean();
		flush();
		readfile($upic);
		exit(0);
	}
	if(isset($_GET["done"]))
	{
		foreach($parms as &$parm)
			if(!isset($_POST[$parm]) || empty($_POST[$parm]))
			{
				if($_POST[$parm] == 0 && $parm == "p")
					continue;
				header("location: /");
				exit(0);
			}
		if($uid == NULL)
		{
			header("location: /");
			exit(0);
		}
		$type = "orders";
		if(isset($_POST["t"]))
			$type = $_POST["t"];
		if(!isset($_GET["edit"]))
			$hash = item_reg($con,$uid,$_POST["n"],$_POST["d"],$_POST["p"],$type);
		else{
			$item = item($con,$_GET["edit"]);
			if(!user_perm($con,$uid)["w"] && $uid != $item["owner"])
			{
				header("location: /");
				exit(0);
			}
			$type = $item["type"];
			if(isset($_POST["t"]) && !empty($_POST["t"]))
				$type = $_POST["t"];
			item_edit($con,$_GET["edit"],$_POST["n"],$_POST["d"],$_POST["p"],$type);
			$hash = $_GET["edit"];
			$followers = follow_of_obj($con,$_GET["edit"]);
			foreach($followers as &$follower)
				notify_upd_obj($con,$follower["srcid"],$_GET["edit"]);
		}
		if(!isset($_GET["edit"]))
			mkdir("uploads/items/".$hash);
		if(isset($_FILES["s"]))
		{
			move_uploaded_file($_FILES["s"]["tmp_name"],"uploads/stl/".$hash);
			exec("bash thingiview/php/compiler.sh ".("uploads/stl/".$_GET["hash"])." ".("uploads/cache/".$_GET["hash"]));
		}
		for($i=0;$i<sizeof($_FILES["i"]["error"]);$i++)
		{
			if($_FILES["i"]["error"][$i] != 0)
				continue;
			//move_uploaded_file($_FILES["i"]["tmp_name"][$i],"uploads/items/".$hash."/".$i.".jpg");
			if($i == 0)
			{
				$img = new \Imagick(realpath($_FILES["i"]["tmp_name"][$i]));
				$img->resizeImage(480,320,Imagick::FILTER_POINT,1,true);
				file_put_contents("uploads/items/".$hash."/"."lite".".jpg",$img->getImageBlob());
			}
			$img = new \Imagick(realpath($_FILES["i"]["tmp_name"][$i]));
			$img->resizeImage(1600,1200,Imagick::FILTER_POINT,1,true);
			file_put_contents("uploads/items/".$hash."/".$i.".jpg",$img->getImageBlob());
		}
		header("location: /item.php?hash=".$hash);
		exit(0);
	}
	$btxt = "make a new ";
	if(isset($_GET["model"]))
		$btxt = $btxt."model";
	if(isset($_GET["order"]))
		$btxt = $btxt."order";
	if(isset($_GET["new"]))
	{
		echo "<head>\n";
		require_once($_SERVER["DOCUMENT_ROOT"]."/inc.php");
		echo "</head>\n";
?>
<div id=null ></div>
<script type="text/javascript" src="thingiview/javascripts/Three.js"></script>
<script type="text/javascript" src="thingiview/javascripts/plane.js"></script>
<script type="text/javascript" src="thingiview/javascripts/thingiview.js"></script>
<script type="text/javascript" >
thingiurlbase = "/thingiview/javascripts";
thingiview = new Thingiview("null");
var r,x;
function parser(e) {
	f = e.target.files[0];
	x = f;
	r = new FileReader();
	r.onload = (function(obj) {
		return function(e) {
			x = e;
		}
	})(f);
}
</script>
<?php
		echo "<div class='tabs'>\n";
		echo "<a href='?new&order'><div class='tab' ".(isset($_GET["order"])?"on":"off")." >order</div></a>\n";
		echo "<a href='?new&model'><div class='tab' ".(isset($_GET["model"])?"on":"off")." >model</div></a>\n";
		echo "</div>\n";
		require_once("header.php");
//		echo "<hr>\n";
		if(!isset($_GET["model"]) && !isset($_GET["order"]))
		{
			echo "<div>please select type of item</div>\n";
			exit(0);
		}
		echo "<form class='reg-form' action='/item.php?done' method=POST enctype='multipart/form-data' ><br>\n";
		echo "<input type=text name=n placeholder='title'></input><br>\n";
		echo "<textarea name=d placeholder='description' rows=5 ></textarea><br>\n";
		echo "<input type=text name=p placeholder='price'></input><br>\n";
		if(isset($_GET["model"]))
		{
			echo "<select name=t >\n";
			foreach($sections as &$sect)
				echo "\t<option value='".$sect."' >".$sect."</option>\n";
			echo "</select><br>\n";
			echo "stl file:<br>\n<input type=file name='s' id=stl onchange='parser(event)' ></input><br>\n";
		}
		echo "images:<br>\n<input type=file name='i[]' multiple=multiple ></input><br>\n";
		echo "<input type=submit value=send ></input><br>\n";
		echo "</form>\n";
		exit(0);
	}
	if(isset($_GET["edit"]) && isset($_GET["hash"]))
	{
		echo "<head>\n";
		require_once("inc.php");
		echo "</head>\n";
		$item = item($con,$_GET["hash"]);
		echo "<form class='reg-form' action='/item.php?done&edit=".$_GET["hash"]."' method=POST enctype='multipart/form-data' ><br>\n";
		echo "<input type=text name=n value='".$item["name"]."' placeholder='title'></input><br>\n";
		echo "<textarea name=d placeholder='description' rows=5 >".$item["desc"]."</textarea><br>\n";
		echo "<input type=text name=p value='".$item["price"]."' placeholder='price'></input><br>\n";
		if($item["type"] != "orders")
		{
			echo "<select name=t >\n";
			foreach($sections as &$sect)
				echo "\t<option value='".$sect."' ".(($item["type"] == $sect)?"selected":"nope")." >".$sect."</option>\n";
			echo "</select><br>\n";
		}
		echo "images:<br>\n<input type=file name='i[]' multiple=multiple ></input><br>\n";
		echo "<input type=submit value=edit ></input><br>\n";
		echo "<a href='?hash=".$_GET["hash"]."&del'>delete</a>\n";
		echo "</form>\n";
		require_once("header.php");
		exit(0);
	}
	if(isset($_GET["follow"]))
	{
		if($uid == NULL)
		{
			header("location: /");
			exit(0);
		}
		if(follow_q_obj($con,$uid,$_GET["hash"]))
			follow_del_obj($con,$uid,$_GET["hash"]);
		else
			follow_add_obj($con,$uid,$_GET["hash"]);
		header("location: ?hash=".$_GET["hash"]);
		exit(0);
	}
	if(isset($_GET["cart"]))
	{
		if($uid == NULL)
		{
			header("location: /");
			exit(0);
		}
		if(cart_chk($con,$uid,$_GET["hash"]))
			cart_del($con,$uid,$_GET["hash"]);
		else
			cart_add($con,$uid,$_GET["hash"]);
		if(isset($_GET["q"]))
		{
			header("location: /search.php?q=".$_GET["q"]);
			exit(0);
		}
		if(isset($_GET["cret"]))
		{
			header("location: /search.php?cart");
			exit(0);
		}
		header("location: ?hash=".$_GET["hash"]);
		exit(0);
	}
	if(!isset($_GET["hash"]))
	{
		header("location: /");
		exit(0);
	}
	if(isset($_GET["del"]) && isset($_GET["hash"]))
	{
		$item = item($con,$_GET["hash"]);
		if($uid == NULL || (!user_perm($con,$uid)["x"] && $uid != $item["owner"]))
		{
			header("location: /");
			exit(0);
		}
		foreach(glob("uploads/items/".$_GET["hash"]."/*") as &$pic)
			unlink($pic);
		rmdir("uploads/items/".$_GET["hash"]);
		item_del($con,$_GET["hash"]);
		header("location: /");
		exit(0);
	}
	$item = item($con,$_GET["hash"]);
	if(!$item)
	{
		if(!empty($_SERVER['HTTP_REFERER']))
	 		header("location: ".$_SERVER['HTTP_REFERER']);
		else
			header("location: /");
		exit(0);
	}
	$comms = comments($con,$item["hash"]);
	$owner = user($con,$item["owner"]);
	notify_read_obj($con,$uid,$_GET["hash"]);
	notify_read_comm($con,$uid,$_GET["hash"]);
	$btxt = htmlentities($item["name"]);
	$title = $item["name"];
	shuffle($accounts);
?>
<?php $fk = (($uid == NULL) && "orders" == $item["type"] ); ?>
<html>
	<head>
		<?php require_once("inc.php"); ?>
	</head>
	<body id=page >
	<?php if($item["type"] != "orders") { ?>
		<div id="viewer" class="stlviewer" onmouseleave="thingiview.setRotation(true);" onmouseover="thingiview.setRotation(false);" >an error occured</div>
		<div id="viewer_hider" class="stlviewer" style="background-color:white;" ></div>
		<div id=text ><br>double click to exit<br>leave cursor from viewer to autorotate<br>drag to rotate<br>click here to toggle viewtype</div>
	<?php } ?>
		<?php
			$edt = "";
			if($uid != NULL && user_perm($con,$uid)["x"] || $uid == $item["owner"])
				$edt = " | (<a href='?hash=".$_GET["hash"]."&edit'>edit</a>)";
		?>
		<h4 style="margin:12px;" >Price: $<?php echo $item["price"]; ?><?php echo $edt; ?></h4>
		<?php if($item["type"] != "orders") { ?>
		<div class=upperhand ><span class="follow_btn lbtn uh" id="vieweropener" >view</span><hr class="v sep" /><a href="?hash=<?php echo $item['hash']; ?>&cart" ><span class="follow_btn rbtn uh" ><?php echo (!cart_chk($con,$uid,$item["hash"])?"+":"-"); ?>cart</span></a></div>
		<?php } ?>
		<div class='box rounded category cat-slider itempics' >
		<?php
				for($i=0;$i<10;$i++)
				{
					$item_pic = "uploads/items/".$item["hash"]."/".$i.".jpg";
					if(!file_exists($item_pic))
						if($i == 0)
							$item_pic = $defimg;
						else
							continue;
					echo "<div class='bg-base cover onepic' data-bg='/".$item_pic."' ></div>\n";
				}
		?>
		</div>
		<div class="info" ><a href='/user.php?id=<?php echo $owner["id"]; ?>' ><span class="iteminfo" ><?php echo ($item["type"] != "orders")?("designed"):("ordered"); ?> by <?php echo $owner["name"]." (".$owner["level"].")"; ?></span></a><hr class=v /><?php if($item["type"] == "asdasd") { ?><a href='/?cat=<?php echo $item["type"]; ?>' ><span class="iteminfo" >on: <?php echo $item["type"]; ?></span></a><?php }else{ ?><a href="?hash=<?php echo $item['hash']; ?>&follow" ><span class="follow_btn lbtn" ><?php if(follow_q_obj($con,$uid,$item["hash"])) echo "un"; else echo "+"; ?>follow</span></a><hr class="v sep" /><span id='sharebtn' class="follow_btn rbtn" >share</span><?php } ?><hr class=v /><span class="iteminfo" >at: <?php echo $item["create"]; ?></span></div>
		<div class="box rounded itemdesc" ><?php echo htmlentities($item["desc"]); ?></div>
		<?php
			if($uid != NULL)
			{
				echo "<form id=cf class='comm-entry' action='/comment.php?item=".$item["hash"]."' method=POST >\n";
				echo "<a href='/user.php'><div class='bg-base cover userpic-ext' data-bg='".$upic."'></div></a>\n";
				echo "<textarea class='box' cols=40 rows=4 maxlength=500 required placeholder='comment here...' name=c ></textarea>\n";
				echo "<input type=submit value='send' ></input>\n";
				echo "</form>\n";
			}else
				echo "<div class='comm-sign' >Please login or signup for comment...</div>\n";
			echo "<hr>\n<div class='comments' >\n";
			$x = (sizeof($comms)>9)?(sizeof($comms)-10):0;
			if(isset($_GET["comm"]))
				$x = 0;
			for($i=$x;$i<sizeof($comms);$i++)
			{
				$comm = $comms[$i];
				if(empty($comm))
					continue;
				$cusr = user($con,$comm["uid"]);
				if(empty($cusr))
					continue;
				$uname = user_getname($con,$cusr["id"]);
				echo "<div class='box comment rounded ".(($item["owner"]==$comm["uid"])?("r"):("l"))."' >\n";
				echo "<a href='/user.php?id=".$comm["uid"]."'>";
				echo "<div class='bg-base cover rounded ppic ".(($item["owner"]==$comm["uid"])?("r"):("l"))."' data-bg='/user.php?pic=".$comm["uid"]."' ></div>\n";
				echo "</a>";
				echo "<a href='/user.php?id=".$cusr["id"]."'>".$uname." (".$cusr["level"].")</a> wrote at ".$comm["create"]."<br>\n";
				//echo "for #".$comm["item"]." ".((!empty($comm["edit"]))?("| last edited: ".$comm["edit"]):(":"))."<br>\n";
				if($uid && ($comm["uid"] == $uid || $uid == $item["owner"] || user_perm($con,$uid)["x"]))
					echo "options: <a href='/comment.php?edit=".$comm["hash"]."' >Edit</a> | <a href='/comment.php?del=".$comm["hash"]."' >Delete</a><br>\n";
				echo "<pre>".htmlentities($comm["content"])."</pre>\n";
				echo "</div><br>\n";
				if($i+1 == sizeof($comms) && !isset($_GET["comm"]) && $x)
				{
					echo "<a class='rounded' href='?hash=".$item["hash"]."&comm' ><div class='comment c morebtn' >Click here to see more...</div></a>\n";
					break;
				}
			}
			echo "</div>\n";
		?>
		<div class="share-cont" >
			<a class="rounded new" onclick="shr_close();" style="cursor:hand;" >close</a><br>
			<div class="act" >
			<?php
				$onclick = "javascript:window.open(this.href,'','menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600');return false;";
				$link = urlencode("//".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
				foreach($accounts as &$acc)
				switch($acc)
				{
					case "tw":
						echo "<a target='_blank' href='https://twitter.com/intent/tweet?url=".$link."&hashtags=abbox,3d,printer&via=abbox&text=".urlencode($item["name"])."' class='bg-base contain smlnk ".$acc."' ></a>\n";
						break;
					case "fb":
						echo "<a target='_blank' href='https://www.facebook.com/dialog/share?app_id=536513566503921&display=popup&caption=".$item["name"]."&href=".$link."&redirect_uri=".$link."' class='bg-base contain smlnk ".$acc."' ></a>\n";
						break;
					case "ggl":
						//echo "<script src='https://apis.google.com/js/platform.js' async defer></script>\n";
						echo "<a target='_blank' href='https://plus.google.com/share?url=".$link."' data-href='".$link."' class='bg-base contain smlnk ".$acc."' data-action='share' ></a>\n";
						break;
					case "pin":
						echo "<a href='https://www.pinterest.com/pin/create/button/?url=".$link."&media=".str_replace("hash","pic",$link."&hd")."&description=".urlencode($item["name"])."' class='bg-base contain smlnk ".$acc."' ></a>\n";
						break;
					default:
						echo "<a target='_blank' href='#' class='bg-base contain smlnk ".$acc."' ></a>\n";
						break;
				}
			?>
			</div>
		</div>
		<?php require_once("header.php"); ?>
		<script type="text/javascript">
			function share_toggle(e) {
				$('.share-cont').toggleClass('visib');
				return false;
			}
			$('#sharebtn').click( function(e) { return share_toggle(e); } );
			$('.share-cont .act A').click( function(e) { window.open(e.target.href,"_blank","menubar=0,titlebar=0,toolbar=0,location=0"); return share_toggle(e); } );
			$('.share-cont').click( function(e) { return share_toggle(e); } );
			$('.onepic').click(function(e) { $(this).toggleClass('oneFullPic'); } );
			//$('input[name=c').elastic();
			if(<?php echo ($fk?"true":"false"); ?>) {
				alert("please sign-in!");
				window.location = "/";
			}
			function comm_kpress_handler(e) {
				if(e.which == 13 && !e.shiftKey)
				{
					document.getElementsByName("c")[0].value = document.getElementsByName("c")[0].value.substring(0,document.getElementsByName("c")[0].value.length - 1);
					$('form#cf').trigger('submit');
				}
			}
			com = document.getElementsByName("c")[0];
			if(com != null)
				com.setAttribute("onkeyup","comm_kpress_handler(event);");
			function viewtoggle() { $("#viewer").toggleClass('oneFullPic'); $("#text").toggleClass("impblock"); }
			$('#viewer').dblclick( function(e) { viewtoggle(); } );
			//document.getElementById("viewer").addEventListener("touchend",function(e) { viewtoggle(); } );
			$('#viewer').on( "dbltap", function(e) { e.preventDefault(); viewtoggle(); } );
		</script>
	<?php if($item["type"] != "orders" && ($uid != NULL || true)) { ?>
		<script type="text/javascript" >
			planex = 200;
			planey = 200;
			pxsz = 20;
			pysz = 20;
		</script>
		<script type="text/javascript" src="thingiview/javascripts/Three.js"></script>
		<script type="text/javascript" src="thingiview/javascripts/plane.js"></script>
		<script type="text/javascript" src="thingiview/javascripts/thingiview.js"></script>
		<script type="text/javascript" >
thingiurlbase = "/thingiview/javascripts";
thingiview = new Thingiview("viewer");
var tst = "<?php echo $_GET["hash"]; ?>";
function fnorm(vert,norm)
{
	p0 = vert[1] - vert[0];
	p1 = vert[2] - vert[0];
	faceNormal = (p0*p1);
	vertexNormal = norm[0]; //(norm[0]+norm[1]+norm[2])/3;
	dot = (faceNormal*vertexNormal);
	return ( dot < 0 ) ? -faceNormal : faceNormal;
}
function viewer(obj) {
	if(!obj) {
		thingiview.setBackgroundColor("transparent;");
		thingiview.setObjectColor('#C0D8F0');
		thingiview.setObjectColor('#FFFFFF');
		thingiview.initScene();
	}
	//thingiview.loadSTL("/thingiview/php/?raw&hash="+tst);
	//thingiview.loadJSON(url);
	if(obj) {
		thingiview.setObjectMaterial('solid');
		thingiview.setRotation(true);
		thingiview.setShowPlane(true);
		console.log("...");
	}
	return;
}
var arr,mm,resp,buf;
function json2canvas(data) {
	btn.innerHTML = "...";
	resp = [];
	for(i=0;i<data.length;i+=9) {
		var t1 = [];
		for(j=0;j<3;j++) {
			var t2 = [];
			for(k=0;k<3;k++) {
				t2.push( data[i+j*3+k] );
			}
			t1.push(t2);
		}
		resp.push(t1);
	}
	var verts = [];
	var hash = {};
	var faces = [];
	for(i=0;i<resp.length;i++) {
		var ifaces = [];
		for(j=0;j<resp[i].length;j++) {
			vtx = resp[i][j];
				var vi = hash[vtx];
				if(vi == null) {
					vi = verts.length;
					verts.push(vtx);
					hash[vtx] = vi;
				}
				ifaces.push(vi);
			delete(vtx);
		}
		faces.push(ifaces);
		delete(ifaces);
	}
	mm = [ [0,0], [0,0], [0,0] ];
	for(i=0;i<verts.length;i++) {
		for(j=0;j<3;j++) {
			if(i == 0) {
				mm[j][0] = verts[i][j];
				mm[j][1] = verts[i][j];
			}
			if(verts[i][j] < mm[j][0])
				mm[j][0] = verts[i][j];
			if(verts[i][j] > mm[j][1])
				mm[j][1] = verts[i][j];
		}
	}
	for(i=0;i<verts.length;i++) {
		for(j=0;j<2;j++)
			//if( !(mm[j][1] > 0 && mm[j][0] < 0) )
				verts[i][j] -= mm[j][0] + (mm[j][1] - mm[j][0])/2;
		verts[i][2] -= mm[2][0];
	}
	arr = [verts,faces];
	thingiview.loadArray(arr);
	delete(verts,faces,hash);
	delete(arr,resp);
	return true;
}
function loader() {
	$("#vieweropener").off();
	btn.style.backgroundColor = "white";
	btn.style.border = "1px solid #ff5722";
	btn.style.color = "#ff5722";
	btn.innerHTML = "loading...";
	btn.innerHTML = "<progress max=1 >loading...</progress>";
	$.getJSON("/thingiview/php/?hash="+tst,function(data,sts,xhr) {
		json2canvas(data);
		buf = xhr;
		//console.log(xhr);
	}).fail(function() {
		fix = document.getElementById("vieweropener").innerHTML = "viewer error";
		$("#vieweropener").off();
	}).done(function() {
		$('#vieweropener').click( function(e) { viewtoggle(); $('#viewer').focus(); });
		fix = document.getElementById("vieweropener");
		fix.removeAttribute("style");
		fix.innerHTML = "view 3d";
		//viewer(true);
		return;
	}).progress(function(x) {
		console.log(x);
	});
}
<?php
	$c = "";
	$sz = filesize("stlcache/".$_GET["hash"]);
	switch(true)
	{
		case ($sz > pow(2,20)):
			$sz = $sz/pow(2,20);
			$c = "m";
			break;
		case ($sz > pow(2,10)):
			$sz = $sz/pow(2,10);
			$c = "k";
			break;
	}
?>
window.onload = function() {
	btn = document.getElementById("vieweropener");
	btn.innerHTML = "view: <?php printf("%.1f%s",$sz,$c); ?>";
	$('#vieweropener').click( function() { loader(); } );
	//loader();
	viewer(false);
}
		</script>
		<script type="text/javascript" >
			vtype = 'solid';
			$('#text').click( function(e) {
				e.preventDefault();
				switch(vtype) {
					case "solid":
						vtype = "wireframe";
						break;
					case "wireframe":
						vtype = "solid";
						break;
				}
				thingiview.setObjectMaterial(vtype);
			});
		</script>
	<?php } ?>
	<div id=null ></div>
	</body>
	<?php
		$file = ("stlcache/".$_GET["hash"]);
		if(file_exists($file) && !isset($_GET["raw"]) && false)
		{
			echo "<script type='text/javascript' >\n";
			echo "var buf = eval('";
			readfile($file);
			echo "');";
			echo "</script>\n";
		}
	?>
</html>
<?php db_end($con); ?>
