<?php
//	header("content-type: text/plain");
	spl_autoload_register(function($class){
		require preg_replace('{\\\\|_(?!.*\\\\)}', DIRECTORY_SEPARATOR, ltrim($class, '\\')).'.php';
	});
	use \Michelf\Markdown;
	function entry($file)
	{
		$ret = "\n"
				."<div class=ent _title='"
				.date("Y-m-d H:i:s",pathinfo($file,PATHINFO_FILENAME))
				."' id="
				.pathinfo($file,PATHINFO_FILENAME)
				." >\n"
				.Markdown::defaultTransform(file_get_contents($file))
				."</div>\n";
		return $ret;
	}
	function elist($arr)
	{
		$ret = "\n<ul><br>\n";
		foreach($arr as &$i)
			$ret .= "<li>\n<a href='?"
				.pathinfo($i,PATHINFO_FILENAME)
				."' >"
				.date("Y-m-d H:i:s",pathinfo($i,PATHINFO_FILENAME))
				."</a><br>\n</li><br>\n";
		return $ret."</ul>";
	}
	$pfx = "../../uploads/txt";
	$ext = "md";
	$max = 10;
	$ent = 0;
	if(isset($_GET["new"]))
	{
		require_once("new.php");
		exit(0);
	}
	if(!empty($_GET))
		foreach($_GET as $k => $v)
			if(intval($k) != 0)
				$ent = intval($k);
	$ents = scandir($pfx."/",SCANDIR_SORT_DESCENDING);
	$rm = array();
	for($i=0;$i<sizeof($ents);$i++)
		if(!strncmp($ents[$i],".",1) || pathinfo($ents[$i],PATHINFO_EXTENSION) !== $ext)
			$rm[] = $i;
	foreach($rm as &$r)
		unset($ents[$r]);
	$ents = array_values($ents);
?>
<html>
	<head>
		<title>-4bb0x- d3v bl0g</title>
		<script type="text/javascript" src="jquery.js"></script>
		<script type="text/javascript" src="velocity.min.js" ></script>
		<meta name="viewport" content="initial-scale=0.8, maximum-scale=1, width=device-width, user-scalable=no" />
		<style type="text/css">
			@import url("//fonts.googleapis.com/css?family=Ubuntu:300,300italic,400,400italic,500,500italic,700,700italic&subset=latin,greek,cyrillic");
			@keyframes load {
				from { -webkit-filter:grayscale(1); }
				to   { -webkit-filter:grayscale(0); }
			}
			body { font-weight:lighter; font-family:Ubuntu,monospace,courier; animation:load 1s ease-in-out 0s 1 normal; }
			a, li { text-decoration:none; color:#ff5722; font-size:1.1em; }
			.head { background-color:#ff5722; color:white; display:block; border-radius:16px; }
			.ent { margin:10px 2%; padding:10px 20px; box-shadow:inset 0 0 0.8em black; border-radius:16px; overflow:auto; border:1px solid #ff5722; max-height:calc(90% - 100px); }
		</style>
	</head>
	<body>
		<?php
			echo "<a href='.' class=head >\n";
			echo "<div ><center>\n";
				echo "<pre>\n";
				system("figlet -w0 'abbox'");
				echo "</pre>\n";
			echo "</center></div>\n";
			echo "</a>\n";
			if(isset($_GET["list"]))
				echo elist($ents);
			else
				if($ent)
					echo entry($pfx."/".$ent.".".$ext);
				else
					for($i=0;$i<$max && $i<sizeof($ents);$i++)
						echo entry($pfx."/".$ents[$i])."<hr />\n";
			if(!isset($_GET["list"]))
				echo "<center><a href=?list >-L157-</a></center>\n";
			else
				echo "<center><a href=. >-M41N-</a></center>\n";
		?>
	<script type="text/javascript">
//$(".ent").css({'opacity':'0'});
$(document).ready(function() {
	$(window).scroll( function(){
		$('.ent').each( function(i){
			var ob = $(this).offset().top + $(this).outerHeight();
			var wb = $(window).scrollTop() + $(window).height();
			if( wb > ob )
				$(this).animate({'opacity':'1'},500);
			else
				$(this).animate({'opacity':'0'},500);
		});
	});
	//$(document).scrollTop(1);
	$(".ent").each(function(x) { $(this).velocity("slideDown", { delay:(x*333), duration:500 }); });
});
//window.onload = function() { $(document).scrollTop(1); }
	</script>
	</body>
</html>
