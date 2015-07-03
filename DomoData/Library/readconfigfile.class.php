<?php
class readconfigfile
/**
 *
* Classe ReadConfigFile qui permet de lire le fichier de configuration
* @author  Pierre Pollet
* @version 1.0
*
*/
{
	public $ConfigParams;

	function __construct()
	{
		if (getenv('EnvVirt')=="DOCKER")
		{
			$this->ConfigParams['Database']['Host']=getenv('DbHost');
			$this->ConfigParams['Database']['Port']=getenv('DbPort');
			$this->ConfigParams['Database']['DBSchema']=getenv('DbSchema');
			$this->ConfigParams['Database']['Login']=getenv('DbLogin');
			$this->ConfigParams['Database']['Password']=getenv('DbPassword');
			
			$this->ConfigParams['Eedomus']['eedomus_apiuser']=getenv('eedomus_apiuser');
			$this->ConfigParams['Eedomus']['eedomus_apisecret']=getenv('eedomus_apisecret');
			$this->ConfigParams['Eedomus']['eedomus_adresseIp']=getenv('eedomus_adresseIp');
		}
		else
		{
			if (strtolower(substr(php_uname('s'),0,7))=='windows')
				$filename=dirname (__FILE__). "\..\..\config.ini";
			else
				$filename=dirname (__FILE__). "/../config.ini";
			//Analyse avec les sections
			$this->ConfigParams = parse_ini_file($filename, TRUE);
		}
	}

	function showParam($section,$name)
	{
		return $this->ConfigParams[$section][$name];
	}

}

