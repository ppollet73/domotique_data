<?php
/**
  * Classe de connexion à la Freebox. 
  * 
  * N'hésitez pas à la surclasser pour définir vos propres méthodes s'appuyant
  * sur celles qui sont présentes ici.
  *
  * Exemple d'utilisation :
  * <?php
  * require('freebox_client.class.php');
  * $freebox = new FreeboxClient('http://10.0.0.2', 'freebox', 'monmdp');
  *
  * // Listons le contenu du disque dur interne de la Freebox.
  * $contenu = $freebox->interroger_api( 'fs.list', array('/Disque dur') );
*/
class FreeboxClient
{
  private $url_serveur;
  private $identifiant;
  private $mot_de_passe;
  
  private $cookie;
  private $csrfToken;
  
  /**
    * Constructeur classique
    * @param string URL de votre freebox
    * @param string Identifiant de connexion (saisir «freebox» par défaut)
    * @param string Votre mot de passe
  */
  public function __construct( $url_serveur, $identifiant, $mot_de_passe )
  {
    // On assigne les paramètres aux variables d'instance.
    $this->uri = $url_serveur;
    $this->identifiant = $identifiant;
    $this->mot_de_passe = $mot_de_passe;
    
    // Connexion automatique puis récupération du cookie et du token csrf.
    $return = explode('.', $this->recuperer_cookie());
    $this->cookie = $return[0];
    $this->csrfToken= $return[1];
  }
  
  /**
    * Interroger l'API de la Freebox.
    * @param string le nom de la méthode à appeler (ex. conn.status)
    * @param array paramètres à passer
    * @return mixed le retour de la méthode appelée.
  */
  public function interroger_api( $methode, $parametres = array() )
  {
    // On détermine la page à appeler en fonction du nom de la méthode.
    $page_a_appeler = explode('.', $methode);
    $page_a_appeler = "{$page_a_appeler[0]}.cgi";
    
    // Initialisation de la connexion avec CURL.
    $ch = curl_init();
    
    // En cas de problèmes, vous pouvez décommenter ces lignes
    //curl_setopt($ch, CURLOPT_HEADER, 1);
    //curl_setopt($ch, CURLOPT_VERBOSE, 1);
    
    curl_setopt($ch, CURLOPT_URL, $this->uri.'/'.$page_a_appeler);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    
    // On passe le cookie à la requête, c'est important.
    curl_setopt($ch, CURLOPT_COOKIE, 'FBXSID='.$this->cookie);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json'
    ));
    
    // On respecte le formalisme JSON/RPC
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
      'jsonrpc' => '2.0',
      'method' => $methode,
      'params' => $parametres
    )));
    
    $retour_curl = curl_exec($ch);
    curl_close($ch);
    
    // On essaye de décoder le retour JSON.
    $retour_json = json_decode( $retour_curl, true );
    
    // Gestion minimale des erreurs.
    if( $retour_json === false )
      throw new Exception("Erreur dans le retour JSON !");
    if( isset($retour_json['error']) ) 
      throw new Exception( json_encode($retour_json) );
    
    // Ce qui nous intéresse est dans l'index «result»
    return $retour_json['result'];
  }
  
  public function gestionWifi($ordre)
  {
  	// On détermine la page à appeler en fonction du nom de la méthode.
  	//$page_a_appeler = explode('.', $methode);
  	$page_a_appeler = "wifi.cgi";
  
  	// Initialisation de la connexion avec CURL.
  	$ch = curl_init();
  
  	// En cas de problèmes, vous pouvez décommenter ces lignes
  	//curl_setopt($ch, CURLOPT_HEADER, 1);
  	//curl_setopt($ch, CURLOPT_VERBOSE, 1);
  
  	curl_setopt($ch, CURLOPT_URL, $this->uri.'/'.$page_a_appeler);
  	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  	curl_setopt($ch, CURLOPT_POST, 1);
  	curl_setopt($ch, CURLOPT_VERBOSE, 1);
  	
  
  	// On passe le cookie à la requête, c'est important.
  	curl_setopt($ch, CURLOPT_COOKIE, 'FBXSID='.$this->cookie);
  
  	curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest'));
  
  	// WIFI OFF
  	switch ($ordre)
  	{
  		case 'OFF' :
  			$data = array('channel' => '11', 'ht_mode' => '20', 'method' => 'wifi.ap_params_set', 'config' => 'Valider', 'csrf_token' => $this->csrfToken);
  			break;
  		case 'ON' :
  			$data = array('enabled'=> 'on', 'channel' => '11', 'ht_mode' => '20', 'method' => 'wifi.ap_params_set', 'config' => 'Valider', 'csrf_token' => $this->csrfToken);
  			break;
  	}
  	
  	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  
  	$retour_curl = curl_exec($ch);
  	curl_close($ch);
  
  	// On essaye de décoder le retour JSON.
  	//$retour_json = json_decode( $retour_curl, true );
  
  	// Gestion minimale des erreurs.
  	//if( $retour_json === false )
  		//throw new Exception("Erreur dans le retour JSON !");
  	//if( isset($retour_json['error']) )
  		//throw new Exception( json_encode($retour_json) );
  
  	// Ce qui nous intéresse est dans l'index «result»
  	//return $retour_json['result'];
  	return $retour_curl;
  }  
  /**
    * Récupération du cookie de session.
    * @return l'identifiant de la session.
  */
  private function recuperer_cookie( )
  {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $this->uri.'/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    // On doit lire le header pour récupérer le cookie, il va donc nous
    // falloir le retourner.
    curl_setopt($ch, CURLOPT_HEADER, 1);
    
    // On se connecte via ces deux paramètres.
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array(
      'login' => $this->identifiant,
      'passwd' => $this->mot_de_passe
    ));
    
    $r = curl_exec($ch);
    curl_close($ch);
    
    // Récupération du cookie dans les entêtes à l'aide d'une expression 
    // régulière.
    $ptn = '/FBXSID=\"([^"]*)/';
    preg_match($ptn, $r, $matches);
    
    $ptn = '/X-FBX-CSRF-Token:(.*)\sLocation.*/';
    preg_match($ptn, $r, $csrf);
    
        
    // En cas de problème, on jette une exception.
    if( count($matches) != 2 )
      throw new Exception("Pas de cookie retourné !");
    
    // On retourne l'identifiant de la session.
    return $matches[1].".".trim($csrf[1]);
  }
}

class FreeboxConsole
{
	private $url_serveur;
	private $identifiant;
	private $mot_de_passe;
	private $id;
	private $idt;

	private $cookie;
	private $csrfToken;

	/**
	 * Constructeur classique
	 * @param string URL de votre freebox
	 * @param string Identifiant de connexion (saisir «freebox» par défaut)
	 * @param string Votre mot de passe
	 */
	public function __construct( $log, $url_serveur, $identifiant, $mot_de_passe )
	{
	 // On assigne les paramètres aux variables d'instance.
        $this->log=$log;
		$this->uri = $url_serveur;
		$this->identifiant = $identifiant;
		$this->mot_de_passe = $mot_de_passe;
		$this->UrlTelephone = "http://adsl.free.fr/adminservice_valid.pl";

		$this->recuperer_auth($log);
	}

	/**
	 * Récupérer les informations d'authentification (paramètres id/idt de l'url).
	 */
	private function recuperer_auth($log )
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->uri.'/login.pl?login='.urlencode($this->identifiant)."&pass=".urlencode($this->mot_de_passe));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt( $ch , CURLOPT_SSL_VERIFYPEER , false );
		curl_setopt( $ch , CURLOPT_SSL_VERIFYHOST , false );
	 	
		//DEBUG:$verbose = fopen('php://temp', 'rw+');
		//DEBUG:curl_setopt($ch, CURLOPT_STDERR, $verbose);
		$r = curl_exec($ch);
		//DEBUG:!rewind($verbose);
		//DEBUG:$verboseLog = stream_get_contents($verbose);
		//DEBUG:echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
		curl_close($ch);
		//$log->debug($r);
		// extraction de la ligne contenant les deux paramètres
		$line = explode("adminservice.pl", $r, 2);

		// séparation des deux parametres
		$params = substr($line[1],1,strpos($line[1],"\"")-1);
		$arr2 = explode("&amp;", $params, 5);

		$this->id =  substr($arr2[0],strpos($arr2[0],"=")+1,strlen($arr2[0]));
		$this->idt = substr($arr2[1],strpos($arr2[1],"=")+1,strlen($arr2[1]));
		$log->debug("id:".$this->id." idt:".$this->idt);


	}
	 
	public function transfertAppel($log,$Action,$NumeroTel)
	{
		switch ($Action)
		{
			case 'ON' :
				$UrlOptions = '?id='.$this->id.'&idt='.$this->idt.'&incon='.urlencode($NumeroTel).'&transinc='.urlencode('checked');
				break;
			case 'OFF' :
				$UrlOptions = '?id='.$this->id.'&idt='.$this->idt.'&transinc='.urlencode('');
				break;
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->UrlTelephone.$UrlOptions);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt( $ch , CURLOPT_SSL_VERIFYPEER , false );
		curl_setopt( $ch , CURLOPT_SSL_VERIFYHOST , false );
		
		//DEBUG:$verbose = fopen('php://temp', 'rw+');
		//DEBUG:curl_setopt($ch, CURLOPT_STDERR, $verbose);
		$r = curl_exec($ch);
		//$log->debug($r);
		//DEBUG:!rewind($verbose);
		//DEBUG:$verboseLog = stream_get_contents($verbose);
		//DEBUG:echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
		curl_close($ch);
		//print_r("<br>".$ch);
	}
}

/**
 *
 * Function pour transformer une array en fichier XML
 * @author  DJMomo
 * @version 1.0
 *
 */
function ARRAYtoXML($doc, $noeud_parent, $noeud, $array, $depth = 0){
	$indent = '';
	$return = '';
	for($i = 0; $i < $depth; $i++)
		$indent .= "\t";
		if (is_array($array))
		{
		foreach($array as $key => $item){
		if (is_numeric($key))
			$key = "key-".$key;
			$return .= "{$indent}< {$key}>\n";

			if(is_array($item))
			{
			$element = $doc->createElement($key);
			$return .= ARRAYtoXML($doc, $noeud_parent, $noeud, $item, $depth + 1);
			$noeud_parent->appendChild($element);
		}
		else
		{
		if ($item === true) $item = 1;
		if ($item === false) $item = 0;
		$return .= "{$indent}\t< ![CDATA[{$item}]]>\n";
		$element = $doc->createElement($key,utf8_encode($item));
		$noeud_parent->appendChild($element);
		}
		$return .= "{$indent}\n";
		}
		}
		else
		{
		$element = $doc->createElement($noeud,utf8_encode($array));
		$noeud_parent->appendChild($element);
		}
		return $return;
		}