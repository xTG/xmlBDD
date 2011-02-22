<?php

/**
 * Class xmlBDDTable
 * @author Baptiste ROUSSEL
 * @version 1.1
 */
/*
* @changelog
*	Ajout du type datetime
*	Prise en compte des types de données
*	Ajout d'opérateurs pour la clause where : =, !=, >, >=, <, <=, like
*/

class xmlBDDTable
{
	/*
	* @type String chemin vers la table (fichier)
	*/
	private $_table = "";

	/*
	* @type String nom de la table
	*/
	private $_tableNom = "";

	/*
	* @type String Format des dates
	*/
	private $_dateFormat = "d/m/Y H:i:s";

	/*
	* @type array(String) Liste des opérateurs simples
	*/
	private $_operateurs = array(	"=" => "=",
									"!=" => "!=",
									">" => ">",
									">=" => ">=",
									"<" => "<",
									"<=" => "<="
						);

	/*
	* @type SimpleXMLElement Schéma de la table
	*/
	private $_schemaXML = null;

	/*
	* @type array(String) Schéma de la table
	*/
	private $_schema = array();

	/*
	* @type array(String) Liste des champs rangés par attribut
	*/
	private $_listeTypes = array(	'integer' => array(),
									'text' => array(),
									'datetime' => array(),
									'inconnu' => array()
							);

	/*
	* @type array(String) composition de la clé primaire
	*/
	private $_primay = array();

	/*
	* @type SimpleXMLElement N-uplets de la table
	*/
	private $_contenu = null;

	/*
	* @type array(String) liste des valeurs prises par les clés primaires
	*/
	private $_primaryVal = array();

	/*
	* @type Bool indique si la table a subit une modification
	*/
	private $_tableModifie = false;

	/*
	* Constructeur
	* @param String Chemin vers la table
	* @param array(String) (facultatif) schéma
	* @param String (facultatif) chemin vers la bdd
	*/
	public function __construct($table, $schema = array())
	{
		// Récupération du nom de la table
		$this->_table = $table;
		$tmp = explode('/',$table);
		$tmp = explode('.',$tmp[count($tmp) - 1]);
		$this->_tableNom = $tmp[0];

		if( file_exists($table) )
		{
			// récupération du XML
			$xml = @simplexml_load_file($table, NULL, LIBXML_NOCDATA);
			// Construction du schéma
			$s = $this->_schemaXML = $xml->schema;
			for( $i = 0; $i < count($s->champs) ; $i++)
			{
				$attributes = $s->champs[$i]->attributes();
				$nom = (String)$attributes['nom'];
				$auto_increment = ($attributes['auto-increment'] == '1')?true:false;
				$primary = ($attributes['primary'] == '1')?true:false;
				$type = (String)$attributes['type'];
				$taille = intval($attributes['taille']);
				$this->_schema[$nom] = array(	'nom' => $nom,
												'auto-increment' => $auto_increment,
												'primary' => $primary,
												'type' => $type,
												'taille' => $taille
											);
				if( isSet($this->_listeTypes[$type]) )
					$this->_listeTypes[$type][] = $nom;
				else
					$this->_listeTypes['inconnu'][] = $nom;
				if( $primary === true )
					$this->_primary[] = $nom;
			}

			// Récupération des n-uplets
			$this->_contenu = $xml->contenu;

			// Remplissage des valeurs prises par les clés primaires
			if( count($this->_primary) > 0)
			for($i = 0; $i < count($this->_contenu->nuplet) ; $i++)
			{
				foreach($this->_primary as $key)
				{
					$val = (array)$this->_contenu->nuplet[$i]->$key;
					$this->_primaryVal[$key][] = $val[0];
				}
			}
		}
		elseif( !empty($schema) )
		{
			// Création de la table
			foreach($schema as $nom => $params)
			{
				if( !isSet($params['type']) )
				{
					throw new xmlBDDException("xmlBDDTable::__construct() : Schéma invalide.",$table);
					return ;
				}
				$this->_schema[$nom] = array(	'nom' => $nom,
												'auto-increment' => (isSet($params['auto-increment']))?$params['auto-increment']:false,
												'primary' => (isSet($params['primary']))?$params['primary']:false,
												'type' => $params['type'],
												'taille' => (isSet($params['taille'])) ? $params['taille'] : ""
											);
				if( isSet($this->_listeTypes[$params['type']]) )
					$this->_listeTypes[$params['type']][] = $nom;
				else
					$this->_listeTypes['inconnu'][] = $nom;
				if(isSet($params['primary']) && $params['primary'] === true )
				{
					$this->_primary[] = $nom;
					$this->_primaryVal[$nom] = array();
				}
			}
			$this->_contenu = new SimpleXMLElement("<nuplet></nuplet>");
			$this->_tableModifie = true;
		}else
			throw new xmlBDDException("xmlBDDTable::__construct() : La table `%1` n'existe pas.",$table);
	}

	/*
	* Destructeur, met à jour le fichier
	*/
	public function __destruct()
	{
		if( $this->_tableModifie === true && !empty($this->_table) )
		{
			// Mise à jour du fichier
			$entete = "<?xml version=\"1.0\" encoding=\"UTF8\"?>";
			$strXml = "$entete
<{$this->_tableNom}>
\t<schema>
%1%\t</schema>
\t<contenu>
%2%\t</contenu>
</{$this->_tableNom}>";
			// Préparation du schéma
			foreach($this->_schema as $nom => $params)
			{
				$child = "\t<champs%1%/>";
				$child = str_replace("%1%"," nom=\"$nom\"%1%",$child);
				$child = str_replace("%1%"," type=\"{$params['type']}\"%1%",$child);
				$child = str_replace("%1%"," taille=\"{$params['taille']}\"%1%",$child);
				if( isSet($params['auto-increment']) )
					$child = str_replace("%1%"," auto-increment=\"{$params['auto-increment']}\"%1%",$child);
				if( isSet($params['primary']) )
					$child = str_replace("%1%"," primary=\"{$params['primary']}\"%1%",$child);
				$child = str_replace("%1%","",$child);
				$strXml = str_replace("%1%","\t". $child . "\n%1%",$strXml);
			}
			$strXml = str_replace("%1%","",$strXml);

			// Préparation des données
			if( isSet($this->_contenu->nuplet) )
			for($i = 0; $i < count($this->_contenu->nuplet) ; $i++)
			{
				$child = "\t\t<nuplet>\n";
				foreach($this->_contenu->nuplet[$i] as $nom => $valeur)
				{
					$child .= "\t\t\t<$nom><![CDATA[$valeur]]></$nom>\n";
				}
				$child .= "\t\t</nuplet>\n%2%";
				$strXml = str_replace("%2%",$child,$strXml);
			}
			$strXml = str_replace("%2%","",$strXml);

			// Ecriture dans le fichier XML
			if( ($ftable = @fopen($this->_table,"w")) != false )
			{
				fwrite($ftable,$strXml);
				fclose($ftable);
			}
			else
				throw new xmlBDDException("xmlBDDTable::__construct() : Impossible de modifier la table `%1`, vérifiez les droits en écriture.",$this->_tableNom);

		}
	}

	/*
	* Retourne le schéma de la table
	* @param SimpleXMLElement
	*/
	public function schema()
	{
		return $this->_schema;
	}

	/*
	* Retourne les n-uplets répondant à la requête
	* @param String/array(String) champs à retourner
	* @param array(String) (facultatif) condition
	* @return array(String)/Bool false en cas d'échec
	*/
	public function select($select, $where = array())
	{
		// Vérification des paramètres
		if( is_string($select) || is_array($select) )
		{
			if( is_array($where) )
			{
				// Vérification des champs du select
				if( is_array($select) )
				{
					foreach($select as $key => $champs)
					{
						if( !isSet($this->_schema[$champs]) )
						{
							throw new xmlBDDException("xmlBDDTable::select() : Le champs `%1` n'existe pas dans la table `%2`.",$champs,$this->_tableNom);
							return false;
						}
					}
				}
				else
				{
					// Sélection de tous les champs
					if( $select == "*" )
					{
						$select = array();
						foreach($this->_schema as $cle)
							$select[] = $cle['nom'];
					}
				}
				// Vérification des champs du where
				foreach($where as $champs => $valeur)
				{
					if( !isSet($this->_schema[$champs]) )
					{
						throw new xmlBDDException("xmlBDDTable::select() : Le champs `%1` n'existe pas dans la table `%2`.",$champs,$this->_tableNom);
						return false;
					}
					if( is_array($valeur) && (!isSet($valeur['op']) || !isSet($valeur['val']) || (isSet($valeur['op']) && (!in_array($valeur['op'],array('>','<',">=","<=",'=',"!=")) && $valeur['op'] != "like"))) )
					{
						throw new xmlBDDException("xmlBDDTable::select() : La condition where est incorrecte.");
						return false;
					}
				}
				// Parcours des n-uplets
				$resultats = array();
				if( isSet($this->_contenu->nuplet) )
				for($i = 0; $i < count($this->_contenu->nuplet) ; $i++)
				{
					$r = array();
					if( empty($where) )
					{
						// aucune condition WHERE
						if( is_array($select) )
						{
							// Sélection de plusieurs champs
							foreach($select as $champs)
							{
								$tmp = (array)$this->_contenu->nuplet[$i]->$champs;
								$r[$champs] = ( !empty($tmp[0]) ) ? $this->cast($tmp[0],$champs) : $this->cast("",$champs);
								if( in_array($champs,$this->_listeTypes['datetime']) )
									$r[$champs] = date($this->_dateFormat,$r[$champs]);
							}
						}
						else
						{
							// Sélection d'un seul champs
							$tmp = (array)$this->_contenu->nuplet[$i]->$select;
							$r[$select] = ( !empty($tmp[0]) ) ? $this->cast($tmp[0],$champs) : $this->cast("",$champs);
							if( in_array($select,$this->_listeTypes['datetime']) )
								$r[$select] = date($this->_dateFormat,$r[$select]);
						}
					}
					else
					{
						// Condition WHERE
						$trouve = true;
						foreach($where as $nom => $valeur)
						{
							$val =$this->_contenu->nuplet[$i]->$nom;
							if( is_array($valeur) )
							{
								// Application de l'opérateur
								if( $this->op($valeur['op'],$val,$valeur['val']) !== true)
								{
									$trouve = false;
									break;
								}
							} // opérateur non spécifié, application de l'égalité
							elseif( $this->_contenu->nuplet[$i]->$nom != $valeur )
							{
								$trouve = false;
								break;
							}
						}
						if( $trouve === true )
						{
							// L'enregistrement correspond à la recherche
							if( is_array($select) )
							{
								// Sélection de plusieurs champs
								foreach($select as $champs)
								{
									$tmp = (array)$this->_contenu->nuplet[$i]->$champs;
									$r[$champs] = ( !empty($tmp[0]) ) ? $this->cast($tmp[0],$champs) : $this->cast("",$champs);
									if( in_array($champs,$this->_listeTypes['datetime']) )
										$r[$champs] = date($this->_dateFormat,$r[$champs]);
								}
							}
							else
							{
								// Sélection d'un seul champs
								$tmp = (array)$this->_contenu->nuplet[$i]->$select;
								$r[$select] = ( !empty($tmp[0]) ) ? $this->cast($tmp[0],$champs) : $this->cast("",$champs);
								if( in_array($select,$this->_listeTypes['datetime']) )
									$r[$select] = date($this->_dateFormat,$r[$select]);
							}
						}
					}
					if( !empty($r) )
						$resultats[] = $r;
				}
				return $resultats;
			}else
				throw new xmlBDDException("xmlBDDTable::select()@param where:Array(String)");
		}else
			throw new xmlBDDException("xmlBDDTable::select()@param select:String/Array(String)");
		return false;
	}

	/*
	   * @param array(String) Liste des valeurs
	*/
	public function insert($valeurs)
	{
		// Vérification des paramètres
		if( !is_array($valeurs) )
		{
			throw new xmlBDDException("xmlBDDTable::insert()@param valeurs:Array(String)");
			return false;
		}

		// Vérification des champs
		if( count(array_diff_key($this->_schema,$valeurs)) > 0 || count(array_diff_key($valeurs,$this->_schema)) > 0 )
		{
			throw new xmlBDDException("xmlBDDTable::insert() : Le nombre de paramètres ne correspond pas au schéma de la table `%1`.",$this->_tableNom);
			return false;
		}
		foreach($valeurs as $champs => $valeur)
		{
			if( !isSet($this->_schema[$champs]) )
			{
				throw new xmlBDDException("xmlBDDTable::insert() : Le champs `%1` n'existe pas dans la table `%2`.",$champs,$this->_tableNom);
				return false;
			}elseif( in_array($champs,$this->_primary) && in_array($valeur,$this->_primaryVal[$champs]) )
			{
				throw new xmlBDDException("xmlBDDTable::insert() : Valeur déjà existante pour la clé %1 dans la table `%2`.",$champs,$this->_tableNom);
				return false;
			}
		}

		// Insertion du n-uplet
		$nuplet = $this->_contenu->addChild('nuplet');
		foreach($valeurs as $nom => $valeur)
		{
			$nuplet->addChild($nom,$this->cast($valeur,$nom));
			if(in_array($nom,$this->_primary))
				$this->_primaryVal[$nom][] = $valeur;
		}
		$this->_tableModifie = true;
		return true;
	}

	/*
	* Mets à jour les n-uplets répondant à la requête
	* @param array(String) champs à mettre à jour
	*						array('nom' => "valeur")
	* @param array(String) (facultatif) condition
	* @return array(String)/Bool false en cas d'échec
	*/
	public function update($valeurs, $where = array())
	{
		// Vérification des paramètres
		if( is_array($valeurs) )
		{
			if( is_array($where) )
			{
				// Vérification des champs à mettre à jour
				foreach($valeurs as $champs => $valeur)
				{
					if( !isSet($this->_schema[$champs]) )
					{
						throw new xmlBDDException("xmlBDDTable::update() : Le champs `%1` n'existe pas dans la table `%2`.",$champs,$this->_tableNom);
						return false;
					}
				}

				// Vérification des champs du where
				foreach($where as $champs => $valeur)
				{
					if( !isSet($this->_schema[$champs]) )
					{
						throw new xmlBDDException("xmlBDDTable::update() : Le champs `%1` n'existe pas dans la table `%2`.",$champs,$this->_tableNom);
						return false;
					}
					if( is_array($valeur) && (!isSet($valeur['op']) || !isSet($valeur['val']) || (isSet($valeur['op']) && (!in_array($valeur['op'],array('>','<',">=","<=",'=',"!=")) && $valeur['op'] != "like"))) )
					{
						throw new xmlBDDException("xmlBDDTable::update() : La condition where est incorrecte.");
						return false;
					}
				}
				// Parcours des n-uplets
				if( isSet($this->_contenu->nuplet) )
				{
					for($i = 0; $i < count($this->_contenu->nuplet) ; $i++)
					{
						$r = array();
						if( empty($where) )
						{
							// aucune condition WHERE
							// Update de tous les champs
							foreach($valeurs as $champs => $valeur)
							{
								$this->_contenu->nuplet[$i]->$champs = $this->cast($valeur,$champs);
								$this->_tableModifie = true;
							}
						}
						else
						{
							// Condition WHERE
							$trouve = true;
							foreach($where as $nom => $valeur)
							{
								$val =$this->_contenu->nuplet[$i]->$nom;
								if( is_array($valeur) )
								{
									// Application de l'opérateur
									if( $this->op($valeur['op'],$val,$valeur['val']) !== true)
									{
										$trouve = false;
										break;
									}
								}
								else
								{
									if( $this->_contenu->nuplet[$i]->$nom != $valeur )
									{
										$trouve = false;
										break;
									}
								}
							}
							if( $trouve === true )
							{
								// L'enregistrement correspond à la recherche
								// Update des champs
								foreach($valeurs as $champs => $valeur)
									$this->_contenu->nuplet[$i]->$champs = $this->cast($valeur,$champs);
								$this->_tableModifie = true;
							}
						}
					}
				}
				return true;
			}else
				throw new xmlBDDException("xmlBDDTable::update()@param where:Array(String)");
		}else
			throw new xmlBDDException("xmlBDDTable::update()@param valeurs:Array(String)");
		return false;
	}

	/*
   * Supprime les n-uplets répondant à la requête
   * @param array(String) (facultatif) condition
   * @return array(String)/Bool false en cas d'échec
	*/
	public function delete($where = array())
	{
		// Vérification des paramètres
		if( is_array($where) )
		{
			// Vérification des champs du where
			foreach($where as $champs => $valeur)
			{
				if( !isSet($this->_schema[$champs]) )
				{
					throw new xmlBDDException("xmlBDDTable::update() : Le champs `%1` n'existe pas dans la table `%2`.",$champs,$this->_tableNom);
					return false;
				}
				if( is_array($valeur) && (!isSet($valeur['op']) || !isSet($valeur['val']) || (isSet($valeur['op']) && (!in_array($valeur['op'],array('>','<',">=","<=",'=',"!=")) && $valeur['op'] != "like"))) )
				{
					throw new xmlBDDException("xmlBDDTable::delete() : La condition where est incorrecte.");
					return false;
				}
			}
			// Parcours des n-uplets
			if( isSet($this->_contenu->nuplet) )
			{
				for($i = 0; $i < count($this->_contenu->nuplet) ; $i++)
				{
					$r = array();
					if( empty($where) )
					{
						// aucune condition WHERE
						// Suppression de tous les champs
						unset($this->_contenu->nuplet[$i]);
						$this->_tableModifie = true;
					}
					else
					{
						// Condition WHERE
						$trouve = true;
						foreach($where as $nom => $valeur)
						{
							$val =$this->_contenu->nuplet[$i]->$nom;
							if( is_array($valeur) )
							{
								// Application de l'opérateur
								if( $this->op($valeur['op'],$val,$valeur['val']) !== true)
								{
									$trouve = false;
									break;
								}
							}
							else
							{
								if( $this->_contenu->nuplet[$i]->$nom != $valeur )
								{
									$trouve = false;
									break;
								}
							}
						}
						if( $trouve === true )
						{
							// L'enregistrement correspond à la recherche
							// Suppression des champs
							unset($this->_contenu->nuplet[$i]);
							$this->_tableModifie = true;
						}
					}
				}
				return true;
			}
		}else
			throw new xmlBDDException("xmlBDDTable::update()@param where:Array(String)");
		return false;
	}

	/*
	* Permet de typer convenablement les paramètres qui sont de base des chaînes de caractères
	* @param mixed Valeur du paramètre
	* @param String Nom du champs de la table
	* @return mixed Valeur typée en fonction du type de champs de la table
	*/
	private function cast($val,$champs)
	{
		if( !isSet($this->_schema[$champs]['type']) )
			return $val; // le champs n'existe pas, on ne peux rien typer
		else
		{
			// Typage
			switch($this->_schema[$champs]['type'])
			{
				case 'integer' :
					return intval($val);
				break;
				case 'text' :
					return (String)$val;
				break;
				case 'datetime' :
					// cas d'un timestamp
					if( filter_var($val,FILTER_VALIDATE_INT) !== false )
					{
						if( date("d/m/Y H:i:s",$val) !== false )
							return intval($val);
						else
							return 0; //timestamp invalide
					} // cas d'une date française avec ou sans heure
					elseif(preg_match('`^(((0[1-9])|(1\d)|(2\d)|(3[0-1]))\/((0[1-9])|(1[0-2]))\/(\d{4})(((([[:space:]]?)(([0-1][0-9])|([2][0-3]))(:[0-5][0-9]))((:[0-5][0-9])?))?))$`',$val))
					{
						$val = str_replace("/","-",$val); // transformation pour indiquer un format européen à strtotime()
						return strtotime($val);
					}
					else
						return 0; // date invalide
				break;
				default : // type inconnu
					return $val;
				break;
			}
			return $val;
		}
	}

	private function op($op,$v1,$v2)
	{
		switch($op)
		{
			case '='  : return $v1 == $v2;
			case '!=' : return $v1 != $v2;
			case '>'  : return $v1 >  $v2;
			case '>=' : return $v1 >= $v2;
			case '<' : return $v1 < $v2;
			case '<=' : return $v1 <= $v2;
			case 'like' : return $this->like($v2,$v1);
			default : return false;
		}

	}

	/*
	* Emulation de la fonction LIKE de MySQL
	* @param String Motif (ex : Je*n%)
	* @param String Valeur (ex : Jeanne)
	* @return Bool
	*/
	private function like($motif,$val)
	{
		if( !is_string($val) )
			$val = (String)$val;

		$motif = str_replace("*",".",$motif);
		$motif = str_replace("%","(.*)",$motif);
		$motif = "(" . $motif . ")";
		$like = preg_match('#^'.$motif.'$#',$val);
		$like = ( $like == 1 )? true : false;

		return $like;
	}
}
?>