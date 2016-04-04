<?php
class params{
	//TODO faire la gestion d'erreur
	private $db;
	private $connection;
	
	function __construct($db)
	{
		$this->db = $db;
	}
	
	function add($ParamName,$ParamValue)
	{
		$this->db->parameters()->insert_update(
				array("ParamName" => htmlentities(strtolower($ParamName), ENT_QUOTES,'iso-8859-1')), // unique key
				array("ParamValue" => htmlentities(strtolower($ParamValue), ENT_QUOTES,'iso-8859-1'))//, // insert values if the row doesn't exist
				);
		return "Parameter created or updated";
	}

	function delete($ParamName)
	{
		//TODO gérer la possibilité de rollbacker une suppression
		$Parameter = $this->db->parameters("ParamName = ?", strtolower($ParamName))->fetch();
		$Parameter->delete();
		return "Parameter deleted";
	}

	function showParam($ParamName)
	{
		$result=array();
		if ($ParamName <> '')
		{
			$Parameter = $this->db->parameters("ParamName = ?", strtolower($ParamName))->fetch();
			if ($Parameter){
				$result=array($Parameter['ParamName'] => $Parameter['ParamValue']);
			}
			else{
				//TODO modifier le retour d'erreur pour inclure, le code http 404/410 suivant les cas
				$result=array($ParamName => "Parametre inexistant");
			}

		}
		else
		{
			foreach($this->db->parameters() as $parameters) { // get all parameters
				$result = $result + array($parameters['IdParam'] => htmlentities($parameters['ParamValue']));
			}

		}
		return $result;
	}
}


?>