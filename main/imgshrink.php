<?php
	$cdir = "uploads/cache";
	$_GET["i"] = base64_decode($_GET["i"]);
	$hash = md5($_GET["i"]);
	header("content-type: image/jpeg");
	$_GET["i"] = $_SERVER["DOCUMENT_ROOT"]."/".urldecode($_GET["i"]);
	if(!isset($_GET["i"]) || empty($_GET["i"]) || !file_exists($_GET["i"]))
		exit(0);
	$pic = realpath($_GET["i"]);
	if(file_exists($cdir."/".$hash) && filemtime($pic) <= filemtime($cdir."/".$hash))
	{
		header("content-length: ".filesize($cdir."/".$hash));
		header("last-modified: ".
			gmdate("D, d M Y H:i:s e",
				filemtime($cdir."/".$hash)) );
		header("cache-control: public, max-age=86400");
		ob_clean();
		flush();
		readfile($cdir."/".$hash);
		exit(0);
	}
	$img = new \Imagick($pic);
	$img->resizeImage(320,240,Imagick::FILTER_POINT,1,true);
	file_put_contents($cdir."/".$hash,$img->getImageBlob());
	header("content-length: ".$img->getImageLength());
	header("last-modified: ".gmdate("D, d M Y H:i:s e",filemtime($pic)) );
	header("cache-control: public, max-age=86400");
	ob_clean();
	flush();
	echo $img->getImageBlob();
	exit(0);
?>
