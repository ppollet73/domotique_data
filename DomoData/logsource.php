<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

$filename='/var/log/Domodata/DomoData.log';
$file = fopen($filename, 'r');
fseek($file,0,SEEK_END);
$pos = ftell($file);

while (true) {
	fseek($file, $pos);
	while ($line = fgets($file)) {
		$DecodedLine=utf8_encode($line);
		echo "data: {$DecodedLine}\n\n";;
		flush();
	}
	$pos = ftell($file);
	sleep(1);
}
fclose($file);

?>