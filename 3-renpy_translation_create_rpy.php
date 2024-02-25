<?php 
/************************************************************
CREATE BY Thanos255
NO COMMERCIAL USE.
You can use this script, modify etc if you show the creator.
You can't use it for win money.

Try my game : https://www.lucie-adult-game.com/
Patreon : https://www.patreon.com/lucie_adult_game

If you use it or like it, please support me on Patreon

***********************************************************/
include("renpy_translation_include.php");
  
$cptLine = 0;
$cptLineGroupe = 0;
$langue = "";
$labelUnique = "";
$tmp = array();
$cas = 0;
$targetFile = "";
$SourceText = "";
$TranslateText = "";
$targetText = "";
$targetFileData = false;
$data = "";
$cptLineTrad = 0;

$suffixe_file = ".rpy";
$originLangage = "";

echo "STEP 3 : Rewrite rpy file completed in repository /fill/<br>";

// Si $chemin est un dossier => on appelle la fonction explorer() pour chaque élément (fichier ou dossier) du dossier$chemin
if( is_dir($dirParse) ){
	
	if (!is_dir($dirServer.$folderTarget) && !is_dir($folderTarget))
		mkdir($folderTarget);

	$me = opendir($dirParse); 

	while( $fileTranslate = readdir($me) ){

		// Quel fichier on doit traité ? tout ceux présent dans le rep traité en 1.
		if (substr($fileTranslate, -3) == "rpy")
		{
			$cptLineTrad = 0;
			$cptLine = 0;

			echo "<br><b>On traite : ".htmlentities($dirParse.$fileTranslate)."</b><br>";

			$fileTranslateMysql = $mysqli->real_escape_string($fileTranslate);
			$fileTarget = $dirParse.$folderTarget.substr($fileTranslate, 0, -4).$suffixe_file;
			echo "<h2>Fichier de destination : ".htmlentities($fileTarget)."</h2>";
			if (file_exists($fileTarget))
				unlink($fileTarget);
			$targetFileData = fopen($fileTarget, 'a'); // On ouvre le fichier de destination

			// On va chercher toutes les lignes en BDD, if etat = 0, alors on touche à rien, on copie tel quel.
			// Si etat = 2, y'a eut une trad, on va chercher une autre colonne, la ou cela a été traduit.

			$sql = "SELECT * FROM translation_text WHERE tt_file = '".$fileTranslateMysql."' AND ta_id = '".$idUSER."' ORDER BY tt_line ASC ";
			$result = $mysqli->query($sql);
			if (isset($_GET['debug']))  echo $sql."<br><br>";
			while ($row = $result->fetch_assoc()) {

				$cptLine++;

				if ($row['tt_translate'] == '' && $row['tt_data'] && $row['tt_etat'] == 0) // On veut la ligne de la source, le old juste au dessus.
				{
					$originLangage = $row['tt_data'];  
				}
				
				// SI cela A ETE TRADUIT : 
				if ($row['tt_translate'] && $row['tt_etat'] == 2)
				{
					$data = stripslashes($row['tt_translate']);
					$dataSave = $data;
					$cptLineTrad ++;
 				}
				else // Sinon on touche à rien.
					$data = $row['tt_data'];

				if (isset($_GET['debug'])) 
					echo $data."<br>";
				
				fputs($targetFileData, rtrim($data)."\n");
			}
			echo "<h3>".$cptLine." lines wrotes dont $cptLineTrad lines translates</h3>";
			fclose($targetFileData);

			set_time_limit(0);
  		}
	}
}


$mysqli->close(); 
  