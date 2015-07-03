<?php
/**
 *
 * Classe eedomus qui permet d'utiliser les fonctions de l'API
 * @author  Mickael Vialat/Pierre Pollet
 * @version 1.1
 *
 * 11/2014: Pierre POLLET 
 * 			Ajout des fonctions getPeriphList,getPeriphHistory,anonymize 
 */
class eedomus {
     var $api_user;
     var $api_secret;
     var $url_server;
     var $url_local;
     var $error;
 
     function __construct($configFile) 
     { 
          $this->url_server = "https://api.eedomus.com";
          $this->url_local= "http://".$configFile->showParam('Eedomus','eedomus_adresseIp');
          $this->error = "";
     } 
 
     function setEeDomusServer($url_server)
     {
          $this->url_server = $url_server;
     }
 
     function autoLoginInfo()
     {
        if (isset($_GET['api_user'])) 
          $this->api_user = $_GET['api_user'];
        else
          if (isset($_POST['api_user'])) 
            $this->api_user = $_POST['api_user'];
          else
            $this->api_user = "";
 
        if (isset($_GET['api_secret'])) 
          $this->api_secret = $_GET['api_secret'];
        else
          if (isset($_POST['api_secret'])) 
            $this->api_secret = $_POST['api_secret'];
          else
            $this->api_secret = "";
 
     }
 
     function setLoginInfo($api_user,  $api_secret)
     {
          $this->api_user = $api_user;
          $this->api_secret =  $api_secret;
     }
 
     function getError()
     {
        return $this->error;
     }
 
     function getPeriphValue($periph_id,$local)
     {    
     	  if ($local) {$baseUrl=$this->url_local;} else {$baseUrl=$this->url_server;}
     	  
          $url =  $baseUrl."/get?action=periph.caract&periph_id=".$periph_id."&api_user=".$this->api_user."&api_secret=".$this->api_secret;
          $arr = json_decode(utf8_encode(file_get_contents($url))); 
 
          //print_r($arr);
          if ($arr->success==1)
            return $arr->body->last_value;
          else
          {
            $this->error = "Impossible de récupérer la valeur du périphérique (".$periph_id.")";
 
            return 0;
          }
     }
 
     function setPeriphValue($periph_id, $value)
     {
         $url =  $this->url_server."/set?action=periph.value&periph_id=".$periph_id."&value=".$value."&api_user=".$this->api_user."&api_secret=".$this->api_secret;
 
         return file_get_contents($url); 
     }
 
     function getPeriphList($local)
     {
     	if ($local) {$baseUrl=$this->url_local;} else {$baseUrl=$this->url_server;}
     	$url =  $baseUrl."/get?action=periph.list&api_user=".$this->api_user."&api_secret=".$this->api_secret;
     	$arr = json_decode(utf8_encode(file_get_contents($url)));
     
     	//print_r($arr);
     	if ($arr->success==1)
     		return $arr;
     	else
     	{
     		$this->error = "Impossible de récupérer la valeur du périphérique (".$periph_id.")";
     		return 0;
     	}
     }
     
     function getPeriphHistory($periphId,$dateStart,$dateEnd,$log)
     {
        $ParamDate="";
     	if (isset($dateStart))
     	{
     		$ParamDate="&start_date=".urlencode($dateStart);
     	}
     	if (isset($dateEnd))
     	{
     		$ParamDate=$ParamDate."&end_date=".urlencode($dateEnd);
     	}
     	// Requête pour la récupération de l'historique des actions
     	$timestart=microtime(true);
     	$urlHistorique =  $this->url_server."/get?action=periph.history&periph_id=".$periphId."&api_user=".$this->api_user."&api_secret=".$this->api_secret.$ParamDate."";
     	$contents=file_get_contents($urlHistorique);
     	// traitement du cas de l'apostrophe qui fait planter le json_decode
     	$contents=str_replace("\\'","'",$contents);
     	$result=json_decode(utf8_encode($contents));
     	//$last_error=json_last_error();
     		
     	$timeend=microtime(true);
     	$time=round(($timeend-$timestart),2);
     	$log->debug(urldecode($this->anonymize($urlHistorique)));
     	$log->info("api request took ".$time."s");
     	
     	return $result;
     }
     
     function anonymize($url)
     {
     	return str_replace($this->api_secret,"*****",(str_replace($this->api_user,"****",$url)));
     	//return $url;	
     }
}


?>
