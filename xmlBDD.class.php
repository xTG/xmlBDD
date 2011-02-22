<?php
/*
* Class xmlBDD
* Permet de créer et d'utiliser une base de donnée formée de fichiers XML.
* @author Baptiste ROUSSEL
* @version 1.0
*/

header('Content-Type: text/html; charset=UTF-8');
class xmlBDD
{

	/*
	* @type String lien vers la base de donnée (dossier)
	*/
	private $_bdd = "";

	/*
	* @type String nom de la base de donnée
	*/
	private $_bddNom = "";

	/*
	* @type array(mixed) Liste des tables
	*		array('nomTable' => array('nom' => String, 'table' => xmlBDDTable))
	*/
	private $_bddTables = array();

	private $_messages = array();

	/*
	* Constructeur
	* @param String lien vers la base de donnée (dossier)
	* @param Bool (facultatif,false) charger toutes les tables en mémoire à la création de l'objet
	* @param Bool (facultatif,false) création de la base de donnée
	* @param Bool (facultatif,true) création d'un fichier .htaccess lors de la création de la bdd
	*/
	public function __construct($bdd, $chargerTable = false, $creation = false, $htaccess = true)
	{
		// Inclusion de la class xmlBDDException
		require_once("xmlBDDException.class.php");

		// Vérification des paramètres
		if( !is_string($bdd) )
		{
			throw new xmlBDDException("xmlBDD::__construct()@param BDD : type String");
			return;
		}
		if( !is_bool($chargerTable) )
		{
			throw new xmlBDDException("xmlBDD::__construct()@param chargerTable : type Bool");
			return;
		}
		if( !is_bool($creation) )
		{
			throw new xmlBDDException("xmlBDD::__construct()@param creation : type Bool");
			return;
		}
		if( !is_bool($htaccess) )
		{
			throw new xmlBDDException("xmlBDD::__construct()@param htaccess : type Bool");
			return;
		}

		// Inclusion de la class xmlBDDTable
		require_once("xmlBDDTable.class.php");

		// Récupération du nom de la bdd
		$this->_bdd = $bdd;
		$tmp = explode('/',$bdd);
		$this->_bddNom = (is_array($tmp))?$tmp[count($tmp) - 1]:$bdd;

		if( $creation === false )
		{
			// la BDD doit exister
			if( !empty($this->_bdd) && is_dir($this->_bdd) )
			{
				// Récupération des tables
				$tables = glob( $this->_bdd . "/*.table.xml");
				foreach($tables as $t)
				{
					$tmp = explode('/',$t);
					$tmp = explode('.',$tmp[count($tmp) - 1]);
					$table = $tmp[0];
					if( $chargerTable === true)
						$this->_bddTables[$table] = array('nom' => $table, 'chemin' => $t, 'table' => new xmlBDDTable($t));
					else
						$this->_bddTables[$table] = array('nom' => $table, 'chemin' => $t, 'table' => null);
				}
			}
			else // Erreur : la bdd n'existe pas
				throw new xmlBDDException("xmlBDD::__construct() : La base de donnée `%1` n'existe pas.",$this->_bddNom);
		}
		else
		{
			// Création de la bdd
			if( is_dir($bdd) )
			{
				// Elle existe déjà, on la supprime et on la recréé
				$fichiers = glob($bdd . "/*");
				$creation = true;
				foreach($fichiers as $f)
				{
					$retour = @unlink($f);
					if( $retour === false )
						$creation = false;
				}
				// suppression du htaccess
				@unlink($bdd . "/.htaccess");
				$retour = rmdir($bdd);
				if( $retour === false )
					$creation = false;
			}
			// Création de la bdd
			$retour = mkdir($bdd,"777");
			if( $htaccess === true )
			{
				// création du htaccess
				$contenu = "deny from all";
				$fhtaccess = fopen($bdd . "/.htaccess","a+");
				if( $fhtaccess !== false )
				{
					fprintf($fhtaccess,"%s",$contenu);
					fclose($fhtaccess);
					$this->_messages['htaccess'] = true;
				}
			}
			if( $retour === false )
				$creation = false;
			$this->_messages['creation'] = $creation;
		}
	}

	/*
	* Vérifie si la table existe
	* @param String nom de la table
	* @param Bool (facultatif,true) charge la table en mémoire
	* @return Bool
	*/
	public function is_table($table, $chargerTable = true)
	{
		// Vérification des paramètres
		if( !is_string($table) )
		{
			throw new xmlBDDException("xmlBDD::is_table()@param table : type String");
			return false;
		}
		if( !is_bool($chargerTable) )
		{
			throw new xmlBDDException("xmlBDD::is_table()@param chargerTable : type Bool");
			return false;
		}

		// Vérification que la table existe
		if( isSet($this->_bddTables[$table]) )
		{
			if( $chargerTable === true && is_null($this->_bddTables[$table]['table']) )
			{
				// Si la table n'a pas été chargé on la charge
				$chemin = (isSet($this->_bddTables[$table]['chemin']))? $this->_bddTables[$table]['chemin'] : $this->_bdd . "/" . $table . ".table.xml";
				$this->_bddTables[$table] = array('nom' => $table, 'chemin' => $chemin, 'table' => new xmlBDDTable($chemin));
			}
			return true;
		}
		return false;
	}

	/*
	* Retourne la liste des tables
	* @return array(String) liste des noms des tables
	*/
	public function show()
	{
		$tables = array();
		foreach($this->_bddTables as $table => $d)
			$tables[] = $table;
		return $tables;
	}

	/*
	* Retourne la structure de la table
	* @param String nom de la table
	* @return array(String)
	*			array(	'auto-increment' => Bool,
	*   				'nom' => String,
	*   				'primary' => Bool,
	*   				'type' => String
	*   			)
	*/
	public function show_table($table)
	{
		if( $this->is_table($table) )
		{
			// Table existante et chargée en mémoire
			return $this->_bddTables[$table]['table']->schema();
		}
		else // la table n'existe pas
			return array();
	}

	/*
	* Création d'une table
	* @param String Nom de la table
	* @param array(mixed) Schéma de la table
	*/
	public function create($table,$schema)
	{
		// Vérification des paramètres
		if( !is_string($table) )
		{
			throw new xmlBDDException("xmlBDD::create()@param table : type String");
			return;
		}
		if( !is_array($schema) )
		{
			throw new xmlBDDException("xmlBDD::create()@param schema : type array(String)");
			return;
		}
		// Vérification d'existance de la table
		if( $this->is_table($table,false) )
		{
			throw new xmlBDDException("xmlBDD::create() : La table `%1` existe déjà.",$table);
			return;
		}
		// Création de la table
		$t = new xmlBDDTable($this->_bdd . "/" .$table . ".table.xml",$schema,$this->_bdd);
		$chemin = $this->_bdd . "/" . $table . ".table.xml";
		$this->_bddTables[$table] = array('nom' => $table, 'chemin' => $chemin, 'table' => $t);
	}

	/*
   	* Retourne les n-uplets répondant à la requête
   	* @param String/array(String) champs à retourner
	* @param String nom de la table
   	* @param array(String) (facultatif) condition
   	* @return array(String)/Bool false en cas d'échec
	*/
	public function select($select,$table,$where = array())
	{
		// Vérification que la table existe
		if( $this->is_table($table) )
			return $this->_bddTables[$table]['table']->select($select,$where);
		else
			throw new xmlBDDException("xmlBDD::select() : La table `%1` n'existe pas.",$table);
		return false;
	}

	/*
	* Insère un enregistrement dans une table
	* @param array(mixed) Liste des valeurs
	* @param String nom de la table
	* @Bool
	*/
	public function insert($valeurs,$table)
	{
		// Vérification que la table existe
		if( $this->is_table($table) )
			return $this->_bddTables[$table]['table']->insert($valeurs);
		else
			throw new xmlBDDException("xmlBDD::insert() : La table `%1` n'existe pas.",$table);
		return false;
	}

	/*
	* Met à jour les n-uplets répondant à la requête
	* @param array(String) champs à mettre à jour
	*						array('nom' => "valeur")
	* @param String nom de la table
	* @param array(String) (facultatif) condition
	* @return Bool false en cas d'échec
	*/
	public function update($valeurs,$table,$where = array())
	{
		// Vérification que la table existe
		if( $this->is_table($table) )
			return $this->_bddTables[$table]['table']->update($valeurs,$where);
		else
			throw new xmlBDDException("xmlBDD::select() : La table `%1` n'existe pas.",$table);
		return false;
	}

	/*
	* Supprime les n-uplets ciblés par la condition
	* @param String nom de la table
	* @param array(String) condition
	* @return Bool false en cas d'échec
	*/
	public function delete($table,$where)
	{
		// Vérification que la table existe
		if( $this->is_table($table) )
			return $this->_bddTables[$table]['table']->delete($where);
		else
			throw new xmlBDDException("xmlBDD::select() : La table `%1` n'existe pas.",$table);
		return false;
	}

	/*
	* Retourne un message d'information
	* @param String nom du message
	* @return mixed/array(mixed) si aucun nom de message n'est passé en paramètre tous les messages seront renvoyés.
	*/
	public function getMessage($index = "")
	{
		if( $index == "" )
			return $this->_messages;

		if( isSet($this->_messages[$index]) )
			return $this->_messages[$index];
		else
			return "";
	}
}
?>