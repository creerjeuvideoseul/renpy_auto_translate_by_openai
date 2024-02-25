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

/*
$customConfig = new Gpt3TokenizerConfig();
$customConfig
    ->vocabPath('custom_vocab.json') // path to a custom vocabulary file
    ->mergesPath('custom_merges.txt') // path to a custom merges file
    ->useCache(false)
*/

use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;
use Gioni06\Gpt3Tokenizer\Gpt3TokenizerConfig;

include("renpy_translation_include.php");

// https://github.com/Gioni06/GPT3Tokenizer
# $defaultConfig = new Gpt3TokenizerConfig(); 

$config = new Gpt3TokenizerConfig();
$tokenizer = new Gpt3Tokenizer($config);

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
$SymbolOfSeparate = ";";
$etat = 0; // non traduit 
$test1 = "";
$test2 = "";
$withCSVFile = false;

$tarifAPI = 100000; // # 1 € !
$cptWord = 0;
$cptCharacteres = 0;
$alltraduction = ""; // va contenir toute lse trads.

echo "<span style='font-size:30px;'><strong>ETAPE 1 :</strong> We reed the file and parse it in database for user: <strong>$idUSER</strong> <br>";
echo "Check langage : <strong>".$langueCheck."</strong><br>"; 
echo "Prompt : <strong>".$promptOPENAI."</strong><br>"; 
echo "<a href='2-renpy_translation_api.php'>Step 2 : Translate BY API (Paid)</a>";
echo "<br><br><br><br>";
 
// Si $chemin est un dossier => on appelle la fonction explorer() pour chaque élément (fichier ou dossier) du dossier$chemin

// Si le dossier existe.
if( is_dir($dirParse) ){
	
	// On recherche les fichiers RPY.
	$me = opendir($dirParse);
	echo "New file found : ".str_replace($dirServer, "", $dirParse)."<br>";
	while( $fileTranslate = readdir($me) ){

		if (substr($fileTranslate, -3) == "rpy")
		{
			echo "<b>".htmlentities($dirParse.$fileTranslate)."</b><br>";

			// Nom du fichier sécurisé :
			$fileTranslateMysql = $mysqli->real_escape_string($fileTranslate);

			// Ouverture du fichier en lecture seule
			$handle = fopen($dirParse.$fileTranslate, 'r');

			// Suppression des datas existants sur ce fichier.
			$mysqli->query("DELETE FROM translation_text WHERE ta_id = 1 AND tt_file = '".$fileTranslateMysql."' "); 
			if ($targetFile && file_exists($targetFile)) {
				if (unlink($targetFile) === false)
				{
					print display_error("Impossible d'ouvrir le fichier de destination $targetFile. Fermez Excel !");
					die();
				}
			}

			$cptLine = 0;

			// Si on a réussi à ouvrir le fichier
			if ($handle) {

				// Tant que l'on est pas à la fin du fichier
				while (!feof($handle))	{

					// On lit la ligne courante, on va tout lire de manière séquentiel, puis monter en mémoire chaque "type" de ligne.
					$buffer = fgets($handle); // Contient la ligne.
					$cptLine ++;

					// CAS 1  :  c'est à dire pas NEW/OLD
					/*
						# game/script_part1.rpy:1999
						translate english day5_living_room_test_lucie_2d0d402c:

							# t "J'ai aussi ressenti un moment fort hier, et je pense qu'on devrait aller plus loin!"
							t ""
					*/

					// Si c'est la ligne de début du groupe d'une traduction :
					if (preg_match("@^# game/@", $buffer)) 
					{
						if (isset($_GET['debug'])) echo "CAS 1 : LINE 1 : ".htmlentities($buffer)."<br>";
						$idUniqueInProgress = $buffer; // # game/screens.rpy:321, on stock la signature
						$cptLineGroupe = 0;
						$langue = "";
						$targetText = "";
						$labelUnique = "";
						$cas = 1; // On est dans le cas 1, c'est à dire pas NEW/OLD
						$cptLineGroupe ++; // ligne 1 faite.
					}

					// 2 eme ligne 
					// On cherche : 
					// translate english day5_living_room_test_lucie_2d0d402c:
					// On sauge la ligne vierge ici car elle ne rentre dans aucun cas.
					if ($cas == 1 && $cptLineGroupe == 1 && preg_match("@^(translate )([^ ]*) ([^ ]*)$@", $buffer, $tmp)) 
					{
						/* Résultat du preg_match : 
						  0 => string 'translate english day_history_1_729787bd:' (length=43)
						  1 => string 'translate ' (length=10)
						  2 => string 'english' (length=7)
						  3 => string 'day_history_1_729787bd:
						*/
						$langue = $tmp['2'];
						if ($langueCheck != $langue) {
							print display_error("LANGUE DIFFERENCE DE CELUI ATTENDU : $langueCheck != $langue");
							die();
						}

						$labelUnique = $tmp['3']; // Label unique ----------- exemple : day_history_1_729787bd
						$cptLineGroupe ++; // ligne 2 faite.
						if (isset($_GET['debug'])) echo "CAS 1 : LINE 2 : ".htmlentities($langue)." ".htmlentities($labelUnique)."OK<br>";
						unset($tmp);
					}

					// 3 eme ligne du groupe de 5 lignes.
					// le old ou le # t 
					if ($cas == 1 && $cptLineGroupe == 2 && preg_match("@^    # @", $buffer, $tmp)) 
					{
						if (isset($_GET['debug'])) echo "CAS 1 : LINE 3 : Ancienne valeur # : ".htmlentities($buffer)."<br>";

						// Contient le texte source.
						// # t "J'ai aussi ressenti un moment fort hier, et je pense qu'on devrait aller plus loin!"
						$SourceText = $buffer;
						$cptLineGroupe ++;
					}
					
					// Derniere ligne, la 4 eme ligne. 
					// le old ou le t SAUF NEW ! et sauf VIDE. en gros : 
					// (4espaces) t "" [a-zA-Z0-9\_\.]*
					if ($cas == 1 && $cptLineGroupe == 3 && (preg_match("@^    (.*) \"\"@", $buffer) OR preg_match("@^    \"\"@", $buffer))) 
					{
						if (isset($_GET['debug'])) echo "CAS 1 : LINE 4 : Traduction : ".htmlentities($buffer)."<br>";
						$cptLineGroupe = 0;
						$targetText = $buffer;

						
						// On retire ce qui pourrait avoir après les " de fin.
						// ex :  mc1 "" (interact=False) 
						// ex :  mc1 "" id mc1_juliette_be_devil_dc1127b8
						if (preg_match("@^    ([a-zA-Z0-9\_\.]*) \"\" @", $targetText))
						{ 
							$targetText = preg_replace("@ \"\" (.*)@", " \"\"", $targetText); // On remplace tout ce qui est après "", par rien.
							echo "CAS 1 : LINE 4 : remove ID/interact : ".htmlentities($targetText)."<br>";
						}
						

						// Pour le compte de mot à la fin pour le cout.
						$cptWord += str_word_count($SourceText); // combien de mot dans la chaine ?
						$cptCharacteres += strlen($SourceText);

					}
 

		// CAS 2 :
		/*
		translate english strings:

			# game/screens.rpy:321
			old "Nouvelle partie"
			new "New game"

			# game/script_part1.rpy:179
			old "Ne pas lui donner tout de suite"
			new ""
		*/
					// CAS 2 NEW/OLD :
					// 2 eme ligne : translate english strings:
					// LINE 1 ON commence par :  
					if ($buffer && preg_match("@^translate ".$langueCheck." strings:@", $buffer)) 
					{
						/*
						  0 => string 'translate english strings:' (length=28)
						  1 => string 'translate ' (length=10)
						  2 => string 'english' (length=7)
						  3 => string 'strings:
						*/
						$labelUnique = "";
						$targetText = "";
						$cptLineGroupe = 0; // ligne 2 faite.
						$cptLineGroupe ++; // ligne 1 faite.
						$cas = 2;
						if (isset($_GET['debug'])) echo "CAS 2 : LINE 1 : ".htmlentities($langueCheck)." strings OK<br>";
						unset($tmp);
					}

					// Si c'est la ligne de début du groupe d'une traduction :
					if ($cas == 2 && $cptLineGroupe == 1 && preg_match("@^    # ([a-zA-Z0-9\_\.]{1,})/@", $buffer)) 
					{
						if (isset($_GET['debug'])) echo "CAS 2 : LINE 2 : ".htmlentities($buffer)."<br>";
						$labelUnique = $buffer;// # game/screens.rpy:321 --------- Stock la signature
						$cptLineGroupe ++; // ligne 1 faite.
					}

					// on cherche la 3 eme ligne. le old ou le # t 
					if ($cas == 2 && $cptLineGroupe == 2 && preg_match("@^    old \"@", $buffer))
					{
						if (isset($_GET['debug'])) echo "CAS 2 : LINE 3 : Ancienne valeur  : ".htmlentities($buffer)."<br>";
						//$SourceText = preg_replace("@^old \"@", "", substr(trim($buffer), 0, -1)); // on retire le " de fin et le old "
						//if ($SourceText == "")
						$SourceText = $buffer;

						$cptLineGroupe ++;
					}

					// on cherche la 3 eme ligne. le old ou le # t 
					if ($cas == 2 && $cptLineGroupe == 3 && preg_match("@^    new \"@", $buffer)) 
					{
						if (isset($_GET['debug'])) echo "CAS 2 : LINE 4 : La traduction  : ".htmlentities($buffer)."<br>";
						// $targetText = preg_replace("@new \"@", "", substr(trim($buffer), 0, -1)); // on retire le " de fin et le new "
//						if (trim($targetText) == "")
						$targetText = $buffer;

						$cptWord += str_word_count($SourceText);
						$cptCharacteres += strlen($SourceText);

						$alltraduction = $alltraduction." ".$SourceText;

						$cptLineGroupe = 1; // 1 est important, car on ne reviendra pas dans le bloc 1. On reprend direct avec un game.
					}


			/*
			Savoir si c'est déjà traduit :
				# t "(*En pensée*) {t}*J'ai bien dormi! Je suis en pleine forme! \nQuel jour sommes-nous? Dimanche! Mais c'est aujourd'hui que Lucie arrive! Il ne faut pas trop que je traîne!*{/t} "
				t "traduit"  
			ou
				# game/script_part1.rpy:201
				old "La reprendre"
				new "azeaze"

			*/


					// DANS LE CAS 1 ET 2

					// On vérifie si y'a pas dejà une trad dedans :
					if ($targetText)
					{
						$test1 = trim($targetText);
						$test2 = trim($SourceText);

						// CAS NEW "" ou ""
						if ($test1 == 'new ""' OR preg_match('@^(.*) ""@', $test1) OR preg_match('@^""$@', $test1)) // Vide à traduire.
							$etat = 0; // C'est cool on veut trad

						// Si remplit DANS la meme langue, à virer.
						elseif ("# ".$test1 == $test2) // Si remplit DANS la meme langue. 
							$etat = 1;

						// old "Window" == # new "Window" ?
						elseif (preg_replace("@new \"@", "", $test1) == preg_replace("@old \"@", "", $test2))
							$etat = 1;

						else // Déjà traduit ou remplit.
							$etat = 2;

						$targetText = preg_replace("(\r\n|\n|\r)", "", $targetText); // We remove caracteres \n because API don't like it
					}
					else
						$etat = 0;

					$targetText = $mysqli->real_escape_string($targetText);
					$buffer = $mysqli->real_escape_string($buffer);

					// On insére CHAQUE LIGNE dans mysql, et on remplit uniquement 
					$mysqli->query("INSERT into translation_text (`tt_file`, `tt_line`, `tt_data`, `tt_translate`, `tt_etat`, `tt_case`, `tt_langue`, ta_id) 
					VALUES ('".$fileTranslateMysql."', '".$cptLine."', '".$buffer."', '".$targetText."', '".$etat."', '".$cas."', '".$langueCheck."', '".$idUSER."')");

					if ($targetText)
						$targetText = "";

				} 

				echo htmlentities($fileTranslateMysql)." done, ".$cptLine." lines ";
				echo "<br><span style='font-size:25px;'>".$cptWord." mots et ". $cptCharacteres." charactères.<br></span><br>";
	

				/*On ferme le fichier*/
				fclose($handle);
				//fclose($targetFileData);
				set_time_limit(0);
			}
			else {
				print display_error("Impossible d'ouvrir le fichier source : ".$dirParse.$fileTranslate);
				die();
			}
		}
	}
}

$text = "This is some text";
$tokens = $tokenizer->encode($alltraduction); // $tokenizer->encodeInChunks($text, 5)

//print_r($tokens);

// $tokens = [1212,318,617,2420]
$text = $tokenizer->decode($tokens);
$numberOfTokens = $tokenizer->count($text);

echo "<br><span style='font-size:45px;'>".$cptWord." words and ". $cptCharacteres." characteres.<br></span>";
echo "<h2>Cout estimé par tokenizer : numberOfTokens $numberOfTokens</h2><br>";

$mysqli->close(); 
  