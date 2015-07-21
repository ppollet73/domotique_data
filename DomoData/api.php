<?php

require './vendor/autoload.php';

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

/**************************************
 * Get Periphs Data through API
 ***************************************/
/**  @SWG\Resource(
 *   apiVersion="0.0.11",
 *   swaggerVersion="1.2",
 *   basePath="https://localhost/api",
 *   resourcePath="meteo",
 *   description="Meteo operations",
 *   produces="['application/json','application/xml','text/plain','text/html']"
* )
*/
$app->get('/periphs/data', function() use ($app,$log,$eedomus,$Db){
	//TODO gerer correctement le retour xml
		
	/**
	 *
	 * @url GET custom
	 *
	 * @SWG\Api(
	 *   path="/meteo/tempressentie/{temp}/{vent}/{unit}",
	 *   @SWG\Operation(
	 *     method="GET",
	 *     summary="temperature with chill effect",
	 *     notes="temperature which take into account wind effect",
	 *     type="Param",
	 *     nickname="ChillEffectTemperature",
	 *     @SWG\Parameter(
	 *       name="STORED_eedomus_apiuser",
	 *       description="userid for eedomus api",
	 *       required=false,
	 *       type="integer",
	 *       format="int64",
	 *       paramType="form",
	 *       minimum="1.0",
	 *       maximum="100000.0"
	 *     ),
	 *     @SWG\Parameter(
	 *       name="STORED_eedomus_apisecret",
	 *       description="userid for eedomus api",
	 *       required=false,
	 *       type="integer",
	 *       format="int64",
	 *       paramType="form",
	 *       minimum="1.0",
	 *       maximum="100000.0"
	 *     ),
	 *     @SWG\Parameter(
	 *       name="temp",
	 *       description="API_ID of temperature device",
	 *       required=true,
	 *       type="string",
	 *       format="int64",
	 *       paramType="path",
	 *       minimum="1.0",
	 *       maximum="100000.0"
	 *     ),
	 *     @SWG\Parameter(
	 *       name="wind",
	 *       description="API_ID of wind device",
	 *       required=true,
	 *       type="float",
	 *       format="int64",
	 *       paramType="path",
	 *       minimum="1.0",
	 *       maximum="100000.0"
	 *     ),
	 *     @SWG\Parameter(
	 *       name="unit",
	 *       description="unit choosen",
	 *       required=true,
	 *       type="float",
	 *       format="int64",
	 *       paramType="path",
	 *       minimum="1.0",
	 *       maximum="100000.0"
	 *     ),
	 *     @SWG\ResponseMessage(code=400, message=""),
	 *     @SWG\ResponseMessage(code=404, message="")
	 *   )
	 * )
	 */
	//header("Content-type: text/xml;");
	// insert into DB characteristics of devices
	$Db->DbLoadCaracteristiques($eedomus);
    
	//
	//chargement dernières données ou historique de tous les périphs ayant Synchro = TRUE
	$Db->DbLoadPeriphsData($eedomus,$log);

	// Close DB Connexion
	//	TODO Close DB Connexion
});

/********************************
* START DOC part
*******************************/
	$app->get('/api-docs/:resource', function($resource) use ($app)
	{
		//TODO revoir la manière de créer cette page en fonction des exemples présents dans le github de swagger-ui
		$swagger = new Swagger('.');
		header("Content-Type: application/json");
		echo $swagger->getResource($resource, array('output' => 'json'));
	});
	
	$app->get('/api-docs/', function() use ($app)
	{
		$app->redirect('/swagger-docs/api-docs.json');
	});

/*****************************************
* START Launching the slim application
*****************************************/
	$app->run();
?>
