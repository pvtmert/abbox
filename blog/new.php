<?php
	$ct = time();
	if(!empty($_POST))
	{
		file_put_contents($pfx."/".$_POST["ct"].".".$ext,$_POST["data"]);
		header("location: ./");
		exit(0);
	}
?>
<html>
	<head>
		<title><?php echo $ct; ?></title>
		<style type="text/css">
			form, textarea { font-family:monospace; font-size:16px; }
			body { text-align:center; }
		</style>
	</head>
	<body>
		<form action="?new" method=POST >
			<textarea name=data rows=25 cols=80 autofocus ></textarea><br>
			<input type=hidden name=ct value=<?php echo $ct; ?> ></input>
			<input type=submit ></input>
		</form>
	</body>
</html>
