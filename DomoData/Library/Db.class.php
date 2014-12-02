<?php
class Db{
	//TODO faire la gestion d'erreur
	private $db;
	private $connection;
	
	function __construct($configFile)
	{
		$structure = new NotORM_Structure_Convention(
				$primary = "id%s", // id$table
				$foreign = "%s_id%s", // id_$table
				$table = "`%s`"
		);
	
		$this->connection = new PDO('mysql:host='.$configFile->showParam('Database','Host')
									   .';port='.$configFile->showParam('Database','Port')
									   .';charset=UTF8'
							           .';dbname='.$configFile->showParam('Database','DBSchema'),
									    $configFile->showParam('Database','Login'), 
									    $configFile->showParam('Database','Password'));
		$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		$this->db = new NotORM($this->connection,$structure);
	}
		
	function DbLoadCaracteristiques($eedomus) 
	/*
	 * Charge toutes les caractéristiques des périphériques/pièces/usage en base
	 */
	{
		// Création ou mise à jour de la liste des périphériques
		$listeCaracteristiques=$eedomus->getPeriphList(FALSE)->body;
		foreach($listeCaracteristiques as $caracteristique) 
		{   
			$this->db->rooms()->insert_update(array("idRooms" => $caracteristique->room_id),array("RoomsEedomusLabel" => $caracteristique->room_name), array("RoomsEedomusLabel" => $caracteristique->room_name)); 
			$this->db->usages()->insert_update(array("idUsages" => $caracteristique->usage_id),array("UsagesName" => $caracteristique->usage_name), array("UsagesName" => $caracteristique->usage_name));
			$periph=array("DevicesEedomusLabel" => $caracteristique->name,
						  "Rooms_idRooms" => $caracteristique->room_id,
					      "Usages_idUsages" => $caracteristique->usage_id);

			$this->db->devices()->insert_update(array("idDevices" => $caracteristique->periph_id),$periph,$periph);
		}
	}
	
	function DbLoadPeriphData($Eedomus,$PeriphId,$dateStart,$dateEnd,$debugLevel)
	/*
	 * Charge les données d'un périphérique (y compris l'historique)
	*/
	{
	    echo "dateStart:".$dateStart."<BR>";
		if (strtotime($dateStart) < strtotime("2008-01-01 00:00:00"))
		{
			// si aucun historique, on considère que l'historique commence le 01/01/2008 à 00h00
			$dateStart="2008-01-01 00:00:00";
			echo "dateStart not sent<BR>";
		}
		
		
		do
		{
			echo "New dateStart:".$dateStart."<BR>";
			set_time_limit(120);
			// Réquête pour la récupération de l'historique des actions
			$periphHistory= $Eedomus->getPeriphHistory($PeriphId,$dateStart,$dateEnd,$debugLevel);
			$periphHistoryData=$periphHistory->body->history;
			
			/*permet de savoir combien il y a de valeur historisé et de ne rien faire si il n'y en a qu'une puisque l'api  
			/*eedomus retourne toujours au moins une ligne
			*/
			$NbRows = count($periphHistoryData);
			
			if(isset($periphHistoryData) && $NbRows >1)
			{
				// Trie du tableau
				usort($periphHistoryData, array('Db','custom_sort'));
	
				// on renomme les clés du tableau pour que ça marche avec not ORM et ajout de la colonne ApiId
				foreach ($periphHistoryData as &$row)
				{
					$row['DevicesRawValue'] = $row['0'];
					$row['DevicesRawDate'] = $row['1'];
					$row['DevicesRawApiId'] = $PeriphId;
						unset( $row['0'] );
						unset( $row['1'] );
				}

				foreach ($periphHistoryData as $datahistory)
				{
					$this->db->DevicesRaw()->insert($datahistory);
					$dateStart=$datahistory['DevicesRawDate'];
					//echo "$dateStart<BR>";
					//var_dump($datahistory);
				}
			 }
		
		}
		while(isset($periphHistory) && isset($periphHistory->history_overflow) && $periphHistory->history_overflow == 10000);
	
		//update dans la table devices du champ DevicesLastUpdated
		$periph=array("DevicesLastUpdated" => $dateStart);
		if ($debugLevel>0) echo "Db.class:Last date is ".$dateStart."<BR>";
		$this->db->devices()->insert_update(array("idDevices" => $PeriphId),$periph,$periph);
				 
	}
	
	function DbLoadPeriphsData($Eedomus)
	/*
	 * Charge les données de tous les périphériques en base
	 */
	{
		// boucle sur tous les devices ayant le champ DevicesSynchro a TRUE
		$Devices = $this->db->Devices()
		    ->select("idDevices, DevicesLastUpdated")
		    ->where("DevicesSynchro=1") 
		    ->order("idDevices");

		// import des données de tous les devices correspondants
		foreach ($Devices as $Device) 
		{ 
			// import des données de tous les devices correspondants    
			$this->DbLoadPeriphData($Eedomus,$Device["idDevices"],$Device["DevicesLastUpdated"],null);
		}
	}
	
	private static function custom_sort($a,$b)
	 {
		return strtotime($a[1])>strtotime($b[1]);
	}
}



?>