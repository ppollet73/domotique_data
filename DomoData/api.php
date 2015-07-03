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



/**************************************
 *
 * Meteo part
 *
 ***************************************/
/**  @SWG\Resource(
 *   apiVersion="0.0.11",
 *   swaggerVersion="1.2",
 *   basePath="http://localhost:8080/api",
 *   resourcePath="meteo",
 *   description="Meteo operations",
 *   produces="['application/json','application/xml','text/plain','text/html']"
* )
*/
$app->get('/meteo/tempressentie/:temp/:wind/:unit', function ($temp,$wind,$unit) use ($app,$eedomus){
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
	header("Content-type: text/xml;");
	$eedomus->TempRessentie($temp, $wind, $unit);
});

$app->put('/meteo/previsions', function () use ($app,$params,$eedomus){
		/**
		 *
		 * @url PUT custom
		 *
		 * @SWG\Api(
		 *   path="/meteo/previsions",
		 *   @SWG\Operation(
		 *     method="PUT",
		 *     summary="update meteo forecast",
		 *     notes="update meteo forecast<br> based on aurel's <a href='http://www.domo-blog.fr/les-previsions-meteo-avec-eedomus/'>work</a><br> Please read Aurel's article to know what Url to use",
		 *     nickname="UpdateMeteoForecast",
		 *     @SWG\Parameter(
		 *       name="STORED_MeteoUrl",
		 *       description="Url to be used",
		 *       required=false,
		 *       type="integer",
		 *       format="int64",
		 *       paramType="form",
		 *       minimum="1.0",
		 *       maximum="100000.0"
		 *     ),
		 *     @SWG\ResponseMessage(code=200, message="Succesfull return")
		 *   )
		 * )
		 */
		$meteo = new meteo($params,$eedomus);
		$app->XmlOutput($meteo->update());
	});

$app->get('/meteo/previsions', function () use ($app,$eedomus){
			/**
			 *
			 * @url GET custom
			 *
			 * @SWG\Api(
			 *   path="/meteo/previsions",
			 *   @SWG\Operation(
			 *     method="GET",
			 *     summary="return meteo forecast",
			 *     notes="return meteo forecast, <br> based on aurel's <a href='http://www.domo-blog.fr/les-previsions-meteo-avec-eedomus/'>work</a>",
			 *     nickname="ReturnMeteoForecast",
			 *     @SWG\ResponseMessage(code=200, message="Succesfull return")
			 *   )
			 * )
			 */
			//Redirect handled by .htaccess
		});

$app->get('/meteo/vigimeteo', function () use ($app,$eedomus){
	/**
	 *
	 * @url GET custom
	 *
	 * @SWG\Api(
	 *   path="/meteo/vigimeteo",
	 *   @SWG\Operation(
	 *     method="GET",
	 *     summary="return meteo risks",
	 *     notes="return meteo risks, <br> based on Djmomo's <a href='http://www.planete-domotique.com/blog/2014/01/03/la-vigilance-meteo-dans-votre-box-domotique-evolue/'>work</a>",
	 *     nickname="ReturnMeteoRisks",
	 *     @SWG\ResponseMessage(code=200, message="Succesfull return")
	 *   )
	 * )
	 */
	$fichierXML = "../xmlFiles/carte_vigilance_meteo.xml";

	$fichier = false;

	// Choix entre affichage ou sauvegarde
	/*$_GET_lower = array_change_key_case($_GET, CASE_LOWER);
	if (isset ($_GET_lower['json']))
	{
	$format = "json";
	header('Content-Type: application/json; charset=utf-8');
	}
	elseif (isset ($_GET_lower['xml']))
	$format = "xml";
	else*/
	//$fichier = $fichierXML;

	$app->response->headers->set('Content-Type', 'application/xml');
	$format="xml";
	$meteo = new VigilanceMeteo($format,"Etats de vigilance météorologique des départements (métropole et outre-mer) et territoires d'outre-mer français");
		$meteo->DonneesVigilance($fichier);

	});

	/*****************************************
	 *
	 * START Launching the slim application
	 *
	 *****************************************/
	$app->run();
?>
