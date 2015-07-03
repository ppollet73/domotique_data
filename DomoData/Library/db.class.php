<?php
class Db{
	private $db;
	private $connection;
	
	function __construct($configFile)
	{
		$structure = new NotORM_Structure_Convention(
				$primary = "id%s", // id$table
				$foreign = "%s_id%s", // $table_id$table
				$table = "`%s`" // escape table_name
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
	{	$now=date("Y-m-d H:i:s");
		// Création ou mise à jour de la liste des périphériques
		$listeCaracteristiques=$eedomus->getPeriphList(FALSE)->body;
		foreach($listeCaracteristiques as $caracteristique) 
		{   
			$this->db->rooms()->insert_update(array("idRooms" => $caracteristique->room_id),array("RoomsEedomusLabel" => $caracteristique->room_name), array("RoomsEedomusLabel" => $caracteristique->room_name)); 
			$this->db->usages()->insert_update(array("idUsages" => $caracteristique->usage_id),array("UsagesName" => $caracteristique->usage_name), array("UsagesName" => $caracteristique->usage_name));
			$periph=array("DevicesEedomusLabel" => $caracteristique->name,
						  "Rooms_idRooms" => $caracteristique->room_id,
					      "Usages_idUsages" => $caracteristique->usage_id,
						  "DevicesLastUpdatedCharacteristics" => $now );

			$this->db->devices()->insert_update(array("idDevices" => $caracteristique->periph_id),$periph,$periph);
		}
	}
	
	function DbLoadPeriphData($Eedomus,$PeriphId,$dateStart,$dateEnd,$log)
	/*
	 * Charge les données d'un périphérique (y compris l'historique)
	*/
	{
		$FlagLastUpdate=FALSE;
		$ImportHistory=FALSE;
		$NbImportedRows=0;
		$PreviousValue=null;
		$FirstRow=TRUE;
		$timestartTotal=microtime(true);
		//$DEBUG=0;
		
		if (strtotime($dateStart) <= strtotime("2000-01-01 00:00:00"))
		{
			// si aucun historique, on considère que l'historique commence le 01/01/2000 à 00h00
			$dateStart="2000-01-01 00:00:00";
			$log->debug("Start Date not sent, beginning import of all data history");
			$ImportHistory=TRUE;
		}
				
		do
		{
			$log->debug("Start Date for Datacollection is ".$dateStart);
			$timestart=microtime(true);
			set_time_limit(400);
			// Réquête pour la récupération de l'historique des actions
			$periphHistory= $Eedomus->getPeriphHistory($PeriphId,$dateStart,$dateEnd,$log);
			//var_dump($periphHistory);
			if (!isset($periphHistory->body->history))
			{
				$log->debug("Issue when Getting History for:".$PeriphId);
				$log->debug("Error is: ".$periphHistory->body->error_msg);
				//$log->debug("DEBUG:".$periphHistory);
				//print_r($periphHistory);
				$periphHistoryData=null;
			}
			else 
			{
				$periphHistoryData=$periphHistory->body->history;
			}
	
			
			/*permet de savoir combien il y a de valeur historisé et de ne rien faire si il n'y en a qu'une puisque l'api  
			/*eedomus retourne toujours au moins une ligne
			*/
			$NbRows = count($periphHistoryData);
			$timestart=microtime(true);
			if(isset($periphHistoryData) && $NbRows >1)
			{
				//update dans la table devices du champ DevicesLastUpdated
				if (!$FlagLastUpdate)
				{
					//$LastUpdate=date("Y-m-d H:i:s",strtotime($periphHistoryData[0][1])+1);
					$LastUpdate=$periphHistoryData[0][1];
					$periph=array("DevicesLastUpdated" => $LastUpdate);
					$log->debug("Last update is ".$LastUpdate);
					$this->db->devices()->insert_update(array("idDevices" => $PeriphId),$periph,$periph);
					$FlagLastUpdate=TRUE;
				}
	
				// on renomme les clés du tableau pour que ça marche avec not ORM et ajout de la colonne ApiId
				$periphHistoryDataCopy=$periphHistoryData;
				$PreviousValue=$periphHistoryDataCopy[0][1];
				foreach ($periphHistoryData as &$row)
				{
					$row['DevicesRawValue'] = $row['0'];
					$row['DevicesRawDate'] = $row['1'];
					$row['DevicesRawApiId'] = $PeriphId;
					$row['DevicesRawDuration'] = null;
					if (!$FirstRow)
					{
						$datetime1 = new DateTime($PreviousValue);
						$datetime2 = new DateTime($row['DevicesRawDate']);
						$interval = date_diff($datetime2,$datetime1);
						$row['DevicesRawDuration']=$interval->format("%H:%I:%S");
						$ArrPreviousValue = next($periphHistoryDataCopy);
						$PreviousValue=$ArrPreviousValue['1'];
					}
					$FirstRow=FALSE;
					unset( $row['0'] );
					unset( $row['1'] );
				}
				//unset la première valeur, on peut pas calculer l'historique
				unset ($periphHistoryData[0]);
				
				$this->db->DevicesRaw()->insert_multi($periphHistoryData);
				$NbImportedRows=$NbImportedRows+($NbRows-1);
				// attention ici, on ne prends pas $lastIndex -1 car l'index commence à 1 vu que le premier élément a été "unset"
				$lastindex= count($periphHistoryData);
				
				if ($ImportHistory)
				{
					$periph=array("DevicesFirstKnownHistory" => $periphHistoryData[$lastindex]['DevicesRawDate']);
					$this->db->devices()->insert_update(array("idDevices" => $PeriphId),$periph,$periph);
				}
				$log->trace($periphHistoryData);

				//pour remonter le temps dans le cas de recupération de l'historique
				if (isset($periphHistory->history_overflow))
				{
					$dateEnd = $periphHistoryData[$lastindex]['DevicesRawDate'];
					$log->debug("Overflow historique, on remonte le temps pour continuer le chargement");
					$log->debug("dateEnd set to ".$dateEnd);
						
					// check de la cohérence dateEnd vs dateStart
					if (strtotime($dateEnd)<strtotime($dateStart))
					{
						$dateEnd=$dateStart;
						$log->debug("dateEnd(".$dateEnd.") < dateStart(".$dateStart.") !");
					}
				}
			}
			$timeend=microtime(true);
			$time=round(($timeend-$timestart)*1000,0);
			$log->info("Insert in DB took ".$time."ms for ".($NbRows-1)." rows imported");
			// re-initialisation a TRUE pour la prochaine itération
			$FirstRow=TRUE;
			//$DEBUG=$DEBUG+1;
		}
		while(isset($periphHistory) && isset($periphHistory->history_overflow) && $periphHistory->history_overflow == 10000);// && $DEBUG<3);
	
		if ($ImportHistory)
		{
				$periph=array("DevicesHistoryImported" => 1);
				$log->debug("History fully imported for periphId ".$PeriphId);
				$this->db->devices()->insert_update(array("idDevices" => $PeriphId),$periph,$periph);
		}
		$timeendTotal=microtime(true);
		$timeTotal=round(($timeendTotal-$timestartTotal),1);
		$log->info("Total Insert in DB took ".$timeTotal."s for ".$NbImportedRows." rows imported");
		
	}
	
	function DbLoadPeriphsData($Eedomus,$log)
	/*
	 * Charge les données de tous les périphériques en base
	 */
	{
		$log->info("Start update of all devices");
		
		// boucle sur tous les devices ayant le champ DevicesSynchro a TRUE
		$Devices = $this->db->Devices()
		    ->select("idDevices, DevicesLastUpdated, DevicesEedomusLabel,DevicesHistoryImported,DevicesFirstKnownHistory")
		    ->where("DevicesSynchro=1") 
		    ->order("idDevices")
			;

		// import des données de tous les devices correspondants
		foreach ($Devices as $Device) 
			{ 
			// import des données de tous les devices correspondants
			$DeviceName=utf8_decode($Device["DevicesEedomusLabel"]);
			/* si DevicesFirstKnownHistory != '' et DevicesHistoryImported = 0 alors
			 * alors l'import de l'historique n'a pas réussi.
			 * on relance l'import de l'historique
			 * la récupération des dernières données ne se fera qu'au lancement suivant
			 */
			$log->debug("---------------------------------------------");
			if (isset($Device["DevicesFirstKnownHistory"]) && $Device["DevicesHistoryImported"]==0)
				{
				$log->info("Recovering history of ".$DeviceName."(".$Device["idDevices"].")");
				$this->DbLoadPeriphData($Eedomus,$Device["idDevices"],"2000-01-01 00:00:00",$Device["DevicesFirstKnownHistory"],$log);
				$log->info("End of Recovering history of ".$DeviceName."(".$Device["idDevices"].")");
				}
			else 
				// on ne recupere que les dernières données
				{
				$log->info("Start Updating ".$DeviceName."(".$Device["idDevices"].")");
				$this->DbLoadPeriphData($Eedomus,$Device["idDevices"],$Device["DevicesLastUpdated"],null,$log);
				$log->info("End  Update of ".$DeviceName."(".$Device["idDevices"].")");
				}
			// update the output
			flush();
			}
		$log->info("End  Update of all devices");
	}
	
	function DbGetPeriphs($log)
	{
		$log->info("Creating devices list by room");
		$Rooms = $this->db->rooms()
		->select("idRooms,RoomsEedomusLabel")
		->order("idRooms")
		;

		$Devices = $this->db->devices()
		->select("idDevices,DevicesEedomusLabel,Rooms_idRooms,DevicesSynchro")
		->order("Rooms_idRooms")
		;
		
		foreach ($Rooms as $Room)
		{
			echo "<optgroup label=\"".utf8_decode($Room["RoomsEedomusLabel"])."\">\n";
			$Devices->where("Rooms_idRooms",$Room["idRooms"]);

			foreach ($Devices as $Device)
			{
				$DeviceName=utf8_decode($Device["DevicesEedomusLabel"]);
				$Selected="";
				if ($Device["DevicesSynchro"] ==1) 
				{
					$Selected="selected=\"selected\"";
				}
				echo "<option class=\"peripheriques\" value=\"".$Device["idDevices"] ."\" " .$Selected." >".$DeviceName."</option>\n";				
			}
			$Devices->ResetWhere();
			echo "</optgroup>\n";
		}
	}
	
	private static function custom_sort($a,$b)
	 {
		return strtotime($a[1])>strtotime($b[1]);
	}
}

?>