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
				'templates.path' => './CustomErrors'
		));

// notFound page Init
$app->notFound(function () use ($log,$app) {
	//TODO améliorer la présentation de cette page
	$log->info("Route not found");
	$app->render('Custom404.html');
});

/*--------------------------------------
*       FIN INITIALISATION
----------------------------------------*/
/**
 * @SWG\Info(
 * title="Domodata API",
 * version="0.3",
 * description="This is a the help of Domodata framework. <br><br> The base URL for all below calls is <STRONG>http://&lt;@API&gt;/.....</STRONG><br><br> You can log issues <a href='https://github.com/ppollet73/domotique_data/issues' target=_blank>here</a>",
 * contact="dev4domotique@gmail.com"
 * )
 */

/**
 * @SWG\Swagger(
 *   schemes={"http"},
 *   basePath="/",
 * 	  @SWG\Tag(
 *		name="peripheriques",
 *		description="Toutes les operations sur les peripheriques eedomus"
 *		),
 *	  @SWG\Tag(
 *		name="logs",
 *		description="logs du framework"
 *		),
 *	  @SWG\Tag(
 *		name="help",
 *		description="aide du framework"
 *		),
 *	  @SWG\Tag(
 *		name="karotz",
 *		description="pour jouer avec le lapin"
 *		),
 *	  @SWG\Tag(
 *		name="meteo",
 *		description="previsions, risque et vigilance"
 *		)
 *  )
 */

/*--------------------------------------
 * Get Periphs Data through API
 ----------------------------------------*/
$app->get('/periphs/data', function() use ($app,$log,$eedomus,$Db){
	/**
	 *
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
	//TODO gerer correctement le retour xml
	$log->info("Enter route GET:/periphs/data");
	
	//header("Content-type: text/xml;");
	// insert into DB characteristics of devices
	$Db->DbLoadCaracteristiques($eedomus);
    
	//
	//chargement dernières données ou historique de tous les périphs ayant Synchro = TRUE
	$Db->DbLoadPeriphsData($eedomus,$log);

	// Close DB Connexion
	//	TODO Close DB Connexion
});

/*--------------------------------------
 * Karotz operations
 ----------------------------------------*/
$app->put('/karotz/colortemp/:TempId/:KarotzIp', function($TempId,$KarotzIp) use ($app,$log,$eedomus)
{
	/**
	*
	* @SWG\Put(
	*   path="/karotz/colortemp/{TempId}/{KarotzIp}",
	*   tags={"karotz"},
	*   summary="led en fonction de la temperature",
	*   description="change la couleur du karotz en fonction de la temperature d'un peripherique",
	*   @SWG\Response(
	*     response=200,
	*     description="status success"
	*   ),
	*   @SWG\Response(
	*     response="default",
	*     description="an ""unexpected"" error"
	*   ),
	*   @SWG\Parameter(
	*    name="TempId",
    *    in="integer",
    *    description="Api Id du peripherique de temperature",
    *    required=true,
    *    type="integer",
    *    format="int64"
	*   ),
	*   @SWG\Parameter(
	*    name="KarotzIp",
    *    in="ip",
    *    description="adresse @ip du Karotz",
    *    required=true,
    *    type="integer",
    *    format="int64"
	*   )
	* )
	*/
	$log->info("Enter route PUT:/karotz/colortemp");
	$karotz = new karotz($eedomus);
	$karotz->ColorTemp($TempId,$KarotzIp);
	
});

/*--------------------------------------
 * Meteo part
 ----------------------------------------*/
$app->get('/meteo/vigimeteo', function () use ($log,$app,$eedomus){
	/** @SWG\Get(
	* path="/meteo/vigimeteo",
	*   tags={"meteo"},
	*   summary="le risque meteo",
	*   description="base sur le travail de  Djmomo <a href='http://www.planete-domotique.com/blog/2014/01/03/la-vigilance-meteo-dans-votre-box-domotique-evolue/'></a>",
	*   @SWG\Response(
	*     response=200,
	*     description="status success"
	*   ),
	*   @SWG\Response(
	*     response="default",
	*     description="an ""unexpected"" error"
	*   )
	*   )
	*/
	$log->info("Enter route GET:/meteo/vigimeteo");
	$fichierXML = "../xmlFiles/carte_vigilance_meteo.xml";
	$fichier = false;

	$app->response->headers->set('Content-Type', 'application/xml');
	$format="xml";
	$meteo = new VigilanceMeteo($format,"Etats de vigilance météorologique des départements (métropole et outre-mer) et territoires d'outre-mer français");
	$meteo->DonneesVigilance($fichier);

});
$app->put('/meteo/previsions/:key/:ville', function ($key,$ville) use ($app,$log,$eedomus){
	/** @SWG\Put(
	 * path="/meteo/previsions/{key}/{ville}",
	 *   tags={"meteo"},
	 *   summary="update des previsions meteo",
	 *   description="update le fichier de previsions meteo, <br> base sur le travail d'Aurel <a href='http://www.domo-blog.fr/les-previsions-meteo-avec-eedomus/'></a>",
	 *   @SWG\Response(
	 *     response=200,
	 *     description="status success"
	 *   ),
	 *   @SWG\Response(
	 *     response="default",
	 *     description="an ""unexpected"" error"
	 *   ),
	*   @SWG\Parameter(
	*    name="key",
    *    in="integer",
    *    description="ApiKey de weather Online",
    *    required=true,
    *    type="integer",
    *    format="int64"
	*   ),
	*   @SWG\Parameter(
	*    name="ville",
    *    in="string",
    *    description="Nom de la ville",
    *    required=true,
    *    type="string",
    *    format="string"
	*   )
	 *   )
	*/
	// Crolles fpbh3mz8ywnqyxw5h8gg2zfb
	$log->info("Enter route PUT:/meteo/previsions/:key/:ville");
	$meteo = new meteo($eedomus);
	$app->XmlOutput($meteo->update($key,$ville));
});
$app->get('/meteo/previsions', function () use ($app,$log,$eedomus){
	/** @SWG\Get(
	 * path="/meteo/previsions",
	 *   tags={"meteo"},
	 *   summary="retourne  previsions meteo",
	 *   description="retourne le fichier xml des previsions meteo",
	 *   @SWG\Response(
	 *     response=200,
	 *     description="status success"
	 *   ),
	 *   @SWG\Response(
	 *     response="default",
	 *     description="an ""unexpected"" error"
	 *   )
	 *   )
	*/
	$log->info("Enter route GET:/meteo/previsions");
	header("Content-Type: application/xml");
	$app->redirect ('/xml/previsions.xml');
});
/*--------------------------------------
 * Show help
 ----------------------------------------*/
$app->get('/', function() use ($app,$log){
	/**
	 *
	 * @SWG\Get(
	 *   path="/",
	 *   tags={"help"},
	 *   summary="cette aide",
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
	$log->info("Enter route GET:/");
		$app->redirect ('/help/index.html');
	});
$app->get('/help/', function() use ($app,$log){
	     /**
		 *
		 * @SWG\Get(
		 *   path="/help",
		 *   tags={"help"},
		 *   summary="cette aide",
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
		$log->info("Enter route GET:/help/");
			$app->redirect ('/help/index.html');
		});

/*--------------------------------------
 * Show Logs
 ----------------------------------------*/
$app->get('/log/', function() use ($app,$log){
	/**
	 *
	 * @SWG\Get(
	 *   path="/log",
	 *   tags={"logs"},
	 *   summary="pour voir la mecanique interne en live",
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
	$log->info("Enter route GET:/log/");
	$app->redirect ('./logview.html');
			});

/*--------------------------------------
 * Freebox Console
 ----------------------------------------*/
$app->put('/freebox/TransfertAppel/:Action/:NumTel', function($Action,$NumTel) use ($app,$log){	
	
	$log->info("Enter route PUT:/freebox/TransfertAppel/:Action/:NumTel");
	$freeboxConsoleLogin="0476088439";
	$freeboxConsolePassword="a7b4c1d5";

	// Connectez-vous en utilisant vos identifiants (le login est freebox par défaut).
	$freeboxConsole = new FreeboxConsole($log,'http://subscribe.free.fr/login', $freeboxConsoleLogin, $freeboxConsolePassword );
	
	switch (strtolower($action))
	{
		case 'on' :
			$contenu = $freeboxConsole->transfertAppel($log,'ON',$NumTel);
			$resultat="Transfert activé sur ".$NumTel;
			break;
		case 'off' :
			$contenu = $freeboxConsole->transfertAppel($log,'OFF','');
			$resultat="Transfert désactivé";
			break;
		default:
			$resultat="Not a valid action";
	}
	
	print_r( $resultat );
});
/*--------------------------------------
* Run slim application
----------------------------------------*/
$app->run();
?>
