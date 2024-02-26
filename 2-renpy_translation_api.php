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
$targetFileData = false;
$data = "";
$texteSource = "";

$ztime = microtime(true);

echo "STEP2 : CALL API API, (we take all line etat != 2, 2 = already done), and we send it to API.<br><br>";
echo "<a href='3-renpy_translation_create_rpy.php'>WAIT! this page finished : and after, lauch step3 : Write a newfile rpy</a><br>";

# On prépare le PROMPT POUR CHAT GPT :
# https://platform.openai.com/docs/api-reference/chat/create
/*
$result = $client->chat()->create([
	'model' => 'gpt-3.5-turbo',
	'messages' => [
		['prompt' => 'Je vais te donner des phrases. Tu dois me les traduire en anglais. Renvoie moi la chaine traduite et rien d\'autre.']
	] 
	#   'temperature' => 0,  'max_tokens' => 10,
]);
*/

// Si $chemin est un dossier => on appelle la fonction explorer() pour chaque élément (fichier ou dossier) du dossier$chemin
if( is_dir($dirParse) ){
	 
	$me = opendir($dirParse); 
	while( $fileTranslate = readdir($me) ){

		if (substr($fileTranslate, -3) == "rpy")
		{
			echo "<h2>in progress : ".htmlentities($dirParse.$fileTranslate)."</h2><br>";
			$fileTranslateMysql = $mysqli->real_escape_string($fileTranslate);

				
			// On va chercher toutes les variables à traduire :
			$sql = "SELECT * FROM translation_text 
				WHERE tt_file = '".$fileTranslateMysql."' AND ta_id = '".$idUSER."' ORDER BY tt_line ASC ";
			$result = $mysqli->query($sql);
			echo $sql."<br>";

			while ($row = $result->fetch_assoc()) {
				
				if ($row['tt_translate'] == '')
					$texteSource = $row['tt_data']; // On attrape la ligne d'avant !
				
				if ($row['tt_etat'] != 2 && $row['tt_translate'] && $texteSource)
				{
					echo "=> <strong>Source </strong>: @".$texteSource."@ translate in @".$row['tt_translate']."@<br>";

					$tt_translate = $row['tt_translate'];
					
					// On nettoie à la chaine à traduire.
					if ($texteSource && preg_match("@^old \"@", trim($texteSource)))
						$texteSourceClean = preg_replace("@^old \"@", "", trim($texteSource));
					elseif ($texteSource && preg_match("@^# (.*) \"@", trim($texteSource)))
						$texteSourceClean = preg_replace("@^# (.*) \"@", "", trim($texteSource));
 					else
						$texteSourceClean = preg_replace("@^# \"@", "", trim($texteSource));

					if ($texteSourceClean == "")
					{
						if ($texteSource && preg_match("@^# ([a-zA-Z0-9\_\.]{1,}) \"@", trim($texteSource)))
							$texteSourceClean = preg_replace("@^# ([a-zA-Z0-9\_\.]{1,}) \"@", "", trim($texteSource));
					}


					if (substr($texteSourceClean, -1) == '"')
						$texteSourceClean = substr(trim($texteSourceClean), 0, -1);

					echo "=><strong>CLEAN</strong> : @".$texteSourceClean."@<br>";

					// On traduit
					$response = traductionByAPI(trim($texteSourceClean), $langueSRC, $langueTarget, $row['id_tt']);
					
					if ($response !== false)
					{
						// On remplace la zone "" avec la réponse de API.
						$tt_translate = $row['tt_translate'];
						$tranlateFinal = preg_replace("@\"\"@", '"'.$response.'"', $tt_translate);
						
						if ($tranlateFinal) $tranlateFinal = $mysqli->real_escape_string($tranlateFinal);
	  				    // mise à jour en bdd de la valeur traduite.
						$sql2 = "UPDATE translation_text 
						SET 
							tt_translate = '".$tranlateFinal."', 
							tt_etat = 2 
						WHERE 
							tt_file = '".$fileTranslateMysql."' 
							AND ta_id = '".$idUSER."' 
							AND id_tt = '".$row['id_tt']."'
						";
						// echo $sql2."<br>";
						$result2 = $mysqli->query($sql2);
	  
						echo "<br>";
					}
					elseif ($row['tt_etat'] == 2)
						echo "Already translate ETAT=2 @".$texteSourceClean."@<br>";
					else
						echo display_error("No text found @".$texteSourceClean."@");

					set_time_limit(0);
				}
			} 
  		}
	}
}

$ztime = microtime(true) - $ztime ;
echo "<strong>Time ".ConvertisseurTime($ztime)."</strong>";

$mysqli->close(); 
  