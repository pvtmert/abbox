<?php

// server needs a good cpu!  Might need to make timeout higher if server chokes on really really big models...
//set_time_limit(3000);

include('convert.php');

//$file = $_GET['file'];

$file_parts = pathinfo($file);
$file_parts["extension"] = "stl";

$handle = fopen($file, 'rb');

if ($handle == FALSE) {
  trigger_error("Failed to open file $file");
}

function getStringContents($handle)
{
  $contents = "";

  while (!feof($handle))
    $contents .= fgets($handle);

  return preg_replace('/$\s+.*/', '', $contents);
}

switch($file_parts['extension']) {
  case 'stl':
	$contents = getStringContents($handle);
    if (stripos($contents, 'solid') === FALSE || true) {
      $result = parse_stl_binary($handle);
    } else {
      $result = parse_stl_string($contents);
    }
    break;
  case 'obj':
    $result = parse_obj_string(getStringContents($file));
    break;
}

$file = ("../../uploads/cache/".$_GET["hash"]);
file_put_contents($file,json_encode($result));
set_time_limit(0);
header("location: /stlcache/".$_GET["hash"]);
exit(0);
/*
header("content-length: ".filesize($file));
header("content-type: text/plain");
readfile($file);
system("cat ".$file);
$fp = fopen($file,"rb");
while(!feof($fp))
{
	print(fread($fp, 1024*8));
	ob_flush();
	flush();
}
fclose($fp);
flush();
*/
?>
