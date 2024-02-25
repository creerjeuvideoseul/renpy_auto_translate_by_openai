<?php 
/************************************************************
				CREATE BY SecretGame18
/************************************************************
NO COMMERCIAL USE.
You can use this script, modify etc if you "show the creator".
You can't use it for win money.

Try my games : 
https://www.lucie-adult-game.com/
https://www.corrupted-paradise.com/
Patreon : https://www.patreon.com/lucie_adult_game

If you use it or like it, please support me on Patreon

/************************************************************
						How to install ?
/************************************************************
Create an account on OpenAI Playground. Obtain a Key.

Intall webserver with php >8.1
Exemple xampp
Open httpd.conf, ajouter SetEnv OPENAI_API_KEY sk-**********************************

Install composer for add plugin : https://getcomposer.org/download/
Lauch and install composer-setup.exe

After, lauch in line command (in c:\xampp\httpdocs\)
composer require openai-php/client
composer require guzzlehttp/guzzle
composer require openai-php/client

Download zip : https://github.com/openai-php/client

Add credit (with credit card on https://platform.openai.com/)
You can test with : http://localhost/openai.php 

Documentation about api php and openai :

https://github.com/openai-php/client/blob/main/src/Resources/Chat.php
https://platform.openai.com/docs/api-reference/chat/create

***********************************************************/

require_once __DIR__ . '/vendor/autoload.php';

// Database connexion :
$mysqli = new mysqli("localhost", "root", "*******", "trad");
if (mysqli_connect_errno()) {
    printf("Échec de la connexion : %s\n", mysqli_connect_error());
    exit();
}

$yourApiKey = getenv('OPENAI_API_KEY'); // From httpd.conf
$client = OpenAI::client($yourApiKey);

// Path local, and folder where we put the translate file :
$dirServer = "C:\\xampp\\htdocs\\renpy_auto_translate\\"; // Where are the script ?
$dirParse = "file_to_translate\\";						  // Where are rpy file to translate ?
$folderTarget = "..\\file_translate\\";					  // Where are the destination ?

// Check langage in script / Security
$langueCheck = "english"; // Control about langage in script (ex : translate english charles_9f0aefa7) 
$idUSER = 1; // If you want a compartiment on bdd.

// What do I have to do openai with the lines of text you're going to send?
// Choose a destination language, here, englis, ... or chinise etc...
$promptOPENAI = "I will give you sentence. Translate it in english. The main thing is to respect the original text as well as the code. Ne traduisez pas les variables entre crocher [] and you must preserve them and never translate them, but of course you will translate the rest of the sentence.";

## IF YOU HAVE THIS ERROR MESSAGE,
## error setting certificate verify locations:
## CAfile: C:\wamp64\www/cacert.pem
## CApath: none"
## install : http://drive.google.com/file/d/1Mp37eBSF9l-HbByB4eN776iKyyq2Fu3b/view?usp=sharing (it's my certificate)

##################### END PARAMETERS ##################################


# NOT USE IN DEFAULT : 

function display_error($txt) {
	return "<br><div style='font-size:30px; color:red;'>".$txt."</div>";
}

/*
	// CURL PHP API.
	curl https://api.API.com/v2/translate \ 
	-d auth_key=[yourAuthKey] \ 
	-d "text=Hello, world"  \ 
	-d "target_lang=DE"
*/
function traductionByAPI($textToTranslate, $langueSRC, $langueTarget, $idTTLine) {

	global $mysqli, $idUSER, $options, $client, $_GET, $promptOPENAI;

	if ($textToTranslate == "")
		return false;

	$checkTranslate = $mysqli->real_escape_string($textToTranslate);

	// Cache API, if already translate, take it directly.
	$sql2 = "SELECT * FROM translate_cache
		WHERE tc_source = '".$checkTranslate."' 
		AND tc_langage_src = '".$langueSRC."' 
		AND tc_langage_target = '".$langueTarget."' 
		LIMIT 1 
	";
	$result2 = $mysqli->query($sql2);

	// IF the data in CACHE, we exit.
	while ($row2 = $result2->fetch_assoc()) {
		print "<b>USE CACHE</b> : ".$row2['tc_translate']."<br>";
		return $row2['tc_translate'];
	}

	print "<b>Send to OPENAI PLayground</b> : ".$textToTranslate."<br>";

	if ($textToTranslate != "" and $promptOPENAI) {
		# différencier premier tour, et la suite.
		$result = $client->chat()->create([
			'model' => 'gpt-3.5-turbo',
			'messages' => [
				['role' => 'user', 'content' => $promptOPENAI,
				['role' => 'user', 'content' => $textToTranslate],
			],

			#   'temperature' => 0,  'max_tokens' => 10,
		]);
	}

	$responseArray = false;
	$errorTab = false;

	if (!$result) {  
        error_log("Result OPENAI ERROR"); 
        print display_error("Result OPENAI ERROR<br>"); 
  
		echo "<pre>";
		var_dump($result);
		echo "</pre>";

    } 
 
	$response = stripslashes($result->choices[0]->message->content);
	echo "REPONSE |". $response."|<br>"; 
	
	$urlCall = "OPENAI"; 
	$urlCall = $mysqli->real_escape_string($urlCall);

	if ($errorTab) $errorTab = $mysqli->real_escape_string($errorTab);
	if ($response) 
	{
		// On retire ce qui pourrait avoir après les " de fin.
		// ex :  |"It is necessary for you to discover who you are following, the one that I see in you since the beginning." id mc1_juliette_be_devil_dc1127b8|
		// ex :  |{mc}Hold on, we are going to play a bit with the remote control.{/mc}" (interact=False)|
		if (preg_match("@\" @", $response))
		{ 
			$response = preg_replace("@\" (.*)@", "", $response); // On remplace tout ce qui est après "", par rien.
			$response = preg_replace("@^\"@", "", $response); // on remplace les " du début;
			echo "Remove ID/interact : ".htmlentities($response)."<br>";
		}
		$response = $mysqli->real_escape_string($response);
	}
	// Full log de ce qui c'est passé :
	$sql = "INSERT INTO `translation_request` (`tt_id`, `tr_send`, `tr_response`, tr_error, `tr_date`, `ta_id`) 
		VALUES ('".$idTTLine."', '".$urlCall."', '".$response."', '".$errorTab."', now(), '".$idUSER."');";
	$result = $mysqli->query($sql);
	// echo $sql;
	
	if ($response) 
	{
		$responseAPI = $mysqli->real_escape_string($response);
	
		// echo "On stock le retour sans traitement de l'API : @".$responseAPI."@<br>";

		// On stock le retour sans traitement de l'API.
		if ($responseAPI)
		{
			$sql = "INSERT INTO `translate_cache` (`tc_source`, `tc_translate`, `tc_langage_src`, `tc_langage_target`) 
				VALUES ('".$checkTranslate."', '".$responseAPI."', '".$langueSRC."', '".$langueTarget."');";
			$result = $mysqli->query($sql);
		} 
		return $response;
	}
	else
	{
		echo display_error("Erreur retour API ");
		print_r($response); 
		return false;
	}
}

function ConvertisseurTime($Time){
     if($Time < 3600){ 
       $heures = 0; 
       
       if($Time < 60){$minutes = 0;} 
       else{$minutes = round($Time / 60);} 
       
       $secondes = floor($Time % 60); 
       } 
       else{ 
       $heures = round($Time / 3600); 
       $secondes = round($Time % 3600); 
       $minutes = floor($secondes / 60); 
       } 
       
       $secondes2 = round($secondes % 60); 
      
       $TimeFinal = "$heures h $minutes min $secondes2 s"; 
       return $TimeFinal; 
}











