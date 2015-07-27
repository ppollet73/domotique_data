<?php

class karotz {
	// TODO Gérer les erreurs
	private $parametersClass;
	private $eedomusClass;
	
	function __construct($eedomus){
		$this->eedomusClass=$eedomus;
	}
	
	function ColorTemp($TempId,$KarotzIp)
	{
		
			$temp=$this->eedomusClass->getPeriphValue($TempId,TRUE);
			if ($temp <0) {
				$color='0000FF'; //Bleu
			} elseif ($temp < 5) {
				$color='00FFFF'; //Cyan
			} elseif ($temp < 10) {
				$color='00FF00'; //Vert
			} elseif ($temp < 20) {
				$color='FF00FF'; //Rose
			} elseif ($temp < 30) {
				$color='FFFF00'; //Jaune
			}else {
				$color='FF0000'; //Rouge
			}
			$file = file_get_contents("http://".$KarotzIp."/cgi-bin/leds?pulse=1&color=" . $color);
			echo $file;
	}
}

?>