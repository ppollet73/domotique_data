<?php

require './vendor/autoload.php';

use Swagger\Annotations as SWG;
use Swagger\Swagger;



//setting TimeZone
date_default_timezone_set("Europe/Paris");


// Autoloading api classes
function DomoData_autoloader($class) {
	if (file_exists('./Library/freebox/'. strtolower($class) . '.php')){
		include './Library/freebox/' . strtolower($class) . '.php';
	}
	else{
		include './Library/' . strtolower($class) . '.class.php';
	}
}
spl_autoload_register('DomoData_autoloader');


// Tell log4php to use our configuration file.
Logger::configure('configLog.php');

// Fetch a logger, it will inherit settings from the root logger
$log = Logger::getRootLogger();
$log->setLevel(LoggerLevel::getLevelDebug());
//$log->setLevel(LoggerLevel::getLevelTrace());
// Logs: TRACE>DEBUG>INFO>WARN>ERROR>FATAL

/*$log->trace("My first message."); // Not logged because TRACE < WARN
$log->debug("My second message."); // Not logged because DEBUG < WARN
$log->info("My third message."); // Not logged because INFO < WARN
$log->warn("My fourth message."); // Logged because WARN >= WARN
$log->warn("-----------------"); // Logged because WARN >= WARN
$log->error("My fifth message."); // Logged because ERROR >= WARN
$log->fatal("My sixth message."); // Logged because FATAL >= WARN*/

$log->info("------------------------- NEW CONNECTION --------------------------------"); 

// config File read
$configFile=new ReadConfigFile;

//DB Connexion
$Db=new Db($configFile);

//eedomus Init
$eedomus = new eeDomus($configFile);
$eedomus_apiuser  =$configFile->showParam('Eedomus','eedomus_apiuser');
$eedomus_apisecret=$configFile->showParam('Eedomus','eedomus_apisecret');
$eedomus->setLoginInfo($eedomus_apiuser,$eedomus_apisecret);

//Slim Init
//http://stackoverflow.com/questions/6807404/slim-json-outputs
class mySlim extends Slim\Slim {
	function JsonOutput($data) {
		switch($this->request->headers->get('Accept')) {
			case 'application/json':
			default:
				$this->response->headers->set('Content-Type', 'application/json');
				$this->response->status('200');
				$this->response->body(json_encode($data));

		}
	}
	function XmlOutput($data){
		$this->response->status('200');
		$this->response->headers->set('Content-Type', 'application/xml');
		$xml = new SimpleXMLElement('<root/>');
		$result=array_flip($data);
		array_walk_recursive($result, array ($xml, 'addChild'));

		$this->response->body($xml->asXML());

	}
}
$app = new mySlim(
		array(
				'debug' => TRUE,
				'templates.path' => './Library/CustomErrors'
		));

// notFound page Init
$app->notFound(function () use ($app) {
	//TODO améliorer la présentation de cette page
	$app->render('Custom404.html');
});

/********************************
*       FIN INITIALISATION
*********************************/
/*
 * @SWG\Info(
 * title="Domodata API",
 * version="0.3",
 * description="This is a the help of Domodata framework. <br><br> The base URL for all below calls is <STRONG>http://&lt;@API&gt;/.....</STRONG><br><br> You can log issues <a href='https://github.com/ppollet73/domotique_data/issues' target=_blank>here</a>",
 * contact="dev4domotique@gmail.com"
 * )
 */

/*
 * @SWG\Swagger(
 *   schemes={"http"},
 *   basePath="/",
 * 	  @SWG\Tag(
 *		name="peripheriques",
 *		description="Toutes les operations sur les peripheriques eedomus"
 *		)
 *  )
 */



/**************************************
 * Get Periphs Data through API
 ***************************************/
	/*
	
	 * @SWG\Get(
	 *   path="/periphs/data",
	 *   tags={"peripheriques"},
	 	 *   summary="stockage des donnees des peripheriques en base",
	 *   @SWG\Response(
	 *     response=200,
	 *     description="status success"
	 *   ),
	 *   @SWG\Response(
	 *     response="default",
	 *     description="an ""unexpected"" error"
	 *   )
	 * )
	 */

	
$app->get('/periphs/data', function() use ($app,$log,$eedomus,$Db){
	//TODO gerer correctement le retour xml
		
	
	//header("Content-type: text/xml;");
	// insert into DB characteristics of devices
	$Db->DbLoadCaracteristiques($eedomus);
    
	//
	//chargement dernières données ou historique de tous les périphs ayant Synchro = TRUE
	$Db->DbLoadPeriphsData($eedomus,$log);

	// Close DB Connexion
	//	TODO Close DB Connexion
});

	$app->get('/', function () use ($app){
		//$app->render ('freedom/Help.php');
		$app->redirect ('/help/index.html');
	});
		$app->get('/help/', function () use ($app){
			//$app->render ('freedom/Help.php');
			$app->redirect ('/help/index.html');
		});

/*****************************************
* START Launching the slim application
*****************************************/
	$app->run();
?>
