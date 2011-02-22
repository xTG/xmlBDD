<html>
<head>
	<title>xmlBDD :: DEMO</title>
	<style>
		body {
			background: #000;
			color: #fff;
			font-weight: bold;
			font-style: Arial;
			font-size: 15px;
		}
		a, a:visited {
			color: #919191;
		}
		a:hover {
			color: #fff;
		}
		fieldset {
			background: #919191;
			color: #fff;
			border: #fff solid 1px;
			padding: 5px;
			margin: 10px;
		}
		fieldset legend {
			background: #919191;
			color: #000;
			font-size: 12px;
			border: #fff solid 1px;
			padding: 2px;
			padding-left: 10px;
			padding-right: 10px;
		}
		h1 {
			text-align: center;
			font-size: 25px;
			border-bottom: #fff 1px solid;
			margin-bottom: 10px;
		}
		h2 {
			text-align: left;
			font-size: 18px;
			text-decoration: underline;
			background: #fff;
			color: #000;
			padding: 2px;
			margin-bottom: 5px;
		}
	</style>
</head>
<body>
<?php
// fonction d'affichage des résultats demo
function pr($v,$titre = "",$var_dump = true){
	echo"<fieldset>";
	if( !empty($titre) )
		echo "<legend>$titre</legend>";
	echo "<pre>";
	if( $var_dump === true )
		var_dump($v);
	else
		print_r($v);
	echo"</pre></fieldset>";
}

// inclusion de la class
require_once('xmlBDD.class.php');

echo "<h1>Page de démonstration</h1>";

if( isSet($_GET['demo']) )
{
	try
	{
		// suppression de la table si elle existe
		@unlink("bdd/magasin/jouet.table.xml");

		// ouverture de la BDD
		$bdd = new xmlBDD("bdd/magasin");

		//##########################################################################################################################
		//##########################################################################################################################
		echo "<h2>Requête CREATE</h2>";
		// Création de la table jouet
		$bdd->create("jouet",array(
					'idJouet' => array('auto-increment' => true, 'primary' => true, 'type' => "integer", 'taille' => 10),
					'nomJouet' => array('type' => "text", 'taille' => 15),
					'descriptionJouet' => array('type' => "text", 'taille' => 300),
					'dateCreationJouet' => array('type' => "datetime")
				));
		pr('$bdd->create("jouet",array(
	\'idJouet\' => array(\'auto-increment\' => true, \'primary\' => true, \'type\' => "integer", \'taille\' => 10),
	\'nomJouet\' => array(\'type\' => "text", \'taille\' => 15),
	\'descriptionJouet\' => array(\'type\' => "text", \'taille\' => 300),
	\'dateCreationJouet\' => array(\'type\' => "datetime")
));',"",false);

		//##########################################################################################################################
		//##########################################################################################################################
		echo "<h2>Requête INSERT</h2>";
		$bdd->insert(array(	'idJouet' => 1,
							'nomJouet' => "Peluche",
							'descriptionJouet' => "Une peluche toute douce.",
							'dateCreationJouet' => date("d/m/Y H:i:s")
					),
		"jouet");
		$bdd->insert(array(	'idJouet' => 2,
							'nomJouet' => "Casse-tête",
							'descriptionJouet' => "C'est un casse-tête en bois.",
							'dateCreationJouet' => date("d/m/Y")
					),
		"jouet");
		$bdd->insert(array(	'idJouet' => 3,
							'nomJouet' => "Ballon",
							'descriptionJouet' => "Un ballon rouge gonflable.",
							'dateCreationJouet' => time()
					),
		"jouet");
		pr('$bdd->insert(array(	\'idJouet\' => 1,
			\'nomJouet\' => "Peluche",
			\'descriptionJouet\' => "Une peluche toute douce.",
			\'dateCreationJouet\' => date("d/m/Y H:i:s")
		),
	"jouet");
$bdd->insert(array(	\'idJouet\' => 2,
			\'nomJouet\' => "Casse-tête",
			\'descriptionJouet\' => "C\'est un casse-tête en bois.",
			\'dateCreationJouet\' => date("d/m/Y")
		),
	"jouet");
$bdd->insert(array(	\'idJouet\' => 3,
			\'nomJouet\' => "Ballon",
			\'descriptionJouet\' => "Un ballon rouge gonflable.",
			\'dateCreationJouet\' => time()
		),
	"jouet");',"Insertion d'un jeu de données",false);

		//##########################################################################################################################
		//##########################################################################################################################
		echo "<h2>Requête SELECT</h2>";
		// SELECT * FROM `jouet` WHERE `idJouet` = 1;
		$data = $bdd->select('*',"jouet",array('idJouet' => 1));
		pr($data,'$bdd->select(\'*\',"jouet",array(\'idJouet\' => 1)); // SELECT * FROM `jouet` WHERE `idJouet` = 1;');
		// SELECT `nomJouet`, `descriptionJouet` FROM `jouet` WHERE `descriptionJouet` LIKE "Un%";
		$data = $bdd->select(array("nomJouet", "descriptionJouet"),"jouet",array('descriptionJouet' => array('op' => "like", 'val' => "Un%")));
		pr($data,'$bdd->select(array("nomJouet", "descriptionJouet"),"jouet",array(\'descriptionJouet\' => array(\'op\' => "like", \'val\' => "Un%"))); // SELECT `nomJouet`, `descriptionJouet` FROM `jouet` WHERE `descriptionJouet` LIKE "Un%";');
		// SELECT `idJouet`, `nomJouet` FROM `jouet` WHERE `idJouet` > 2;
		$data = $bdd->select(array("idJouet", "nomJouet"),"jouet",array('idJouet' => array('op' => ">", 'val' => 2)));
		pr($data,'$bdd->select(array("idJouet", "nomJouet"),"jouet",array(\'idJouet\' => array(\'op\' => ">", \'val\' => 2))); // SELECT `idJouet`, `nomJouet` FROM `jouet` WHERE `idJouet` > 2;');

		//##########################################################################################################################
		//##########################################################################################################################
		echo "<h2>Requête DELETE</h2>";
		$bdd->delete("jouet",array('idJouet' => 2));
		pr('$bdd->delete("jouet",array(\'idJouet\' => array(\'op\' => "=", \'val\' => 2)));',"",false);
		$data = $bdd->select('*',"jouet",array('idJouet' => array('op' => "=", 'val' => 2)));
		pr($data,"Vérification : SELECT * FROM `jouet` WHERE `idJouet` = 2;");

		//##########################################################################################################################
		//##########################################################################################################################
		echo "<h2>Requête UPDATE</h2>";
		// UPDATE `jouet` SET `nomJouet` = 'ballon rouge' WHERE `idJouet` = 3;
		$data = $bdd->update(array('nomJouet' => "ballon rouge"),"jouet",array('idJouet' => 3));
		pr($data,'$bdd->update(array(\'nomJouet\' => "ballon rouge"),"jouet",array(\'idJouet\' => 3)); // UPDATE `jouet` SET `nomJouet` = \'ballon rouge\' WHERE `idJouet` = 3;');
		$data = $bdd->select('*',"jouet");
		pr($data,"Vérification : SELECT * FROM `jouet`;");

		//##########################################################################################################################
		//##########################################################################################################################
		// fermeture de la BDD et mise à jour des opérations
		unset($bdd);
		echo "Fichier XML généré pour la sauvegarde des données : <a href=\"bdd/magasin/jouet.table.xml\">jouet.table.xml</a><br /><br />";
	}
	catch(xmlBDDException $message)
	{
		// Affichage de l'exception
		$m = "$message";
		pr($m,"xmlBDDException");
	}
}
else
{
	echo "Le script DEMO va créer la base de donnée avant de vous lancer la démonstration. Si une base de donnée est déjà existante elle sera détruite et recréée.<br />";

	echo "Si le processus s'est déroulé correctement veuillez <a href=\"?demo\">cliquer ici pour accéder à la DEMO</a>.<br />";
	echo "Si des erreurs sont apparues veuillez vérifier que le dossier bdd existe et qu'il est possède les droits en écriture.";
	try
	{
		if( !is_dir("bdd") )
			mkdir("bdd","777");
		$bdd = new xmlBDD("bdd/magasin",false,true);
		$str = "Création de la BDD : ";
		$str .= ($bdd->getMessage('creation') === true)?"OK":"Erreur";
		$str .= "\r\nCréation du fichier .htaccess : ";
		$str .= ($bdd->getMessage('htaccess') === true)?"OK":"Erreur";
		$str .= "\r\n\r\nProcessus de création terminé.";
		pr($str,'$bdd = new xmlBDD("bdd/magasin",false,true);',false);
	}
	catch(xmlBDDException $message)
	{
		// Affichage de l'exception
		$m = "$message";
		pr($m,"xmlBDDException");
	}
}
?>
</body>
</html>