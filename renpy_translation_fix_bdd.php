<?

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

$folderTarget = "fill\\";
$suffixe_file = ".rpy";

echo "ETAPE 4 : Correction des balises non fermées.<br>";

// Connexion bDD :
$mysqli = new mysqli("localhost", "root", "root", "renpy_translate");
if (mysqli_connect_errno()) {
    printf("Échec de la connexion : %s\n", mysqli_connect_error());
    exit();
}
 
$balise = "{mc}";
$baliseFin = "{/mc}";

$sqlTotal = "";
// Par defaut que PREVIEW, execute=1 pour balancer
echo "<h1>Attention à ne pas faire 2 fois.</h1>";

// On va chercher toutes les variables à traduire :
$sql = "SELECT * FROM `translate_cache` WHERE `tc_source` LIKE '%".$balise."%' ";
$result = $mysqli->query($sql);
echo $sql;

while ($row = $result->fetch_assoc()) {

	$tc_source = $row['tc_source'];
	$tc_translate = trim($row['tc_translate']);
	$tc_translate_src = $tc_translate;
	$id_tc = $row['id_tc'];

	if (!preg_match('#^'.$balise.'#', $tc_translate))
		$tc_translate = preg_replace('#^\{#', "", $tc_translate);

	echo "<br>Source : @".$tc_source."@<br>";
	echo "Translate&nbsp;&nbsp; : @<strong>".$tc_translate."</strong>@<br>";

/*
{t}**
et {t} à la fin
*/
	// $tc_translate = preg_replace('#'.$balise.'$#', $baliseFin, $tc_translate); // Si {mc} à la fin, alors on remplace par {/mc}

	if (!preg_match('#^'.$balise.'#', $tc_translate)) // Si ne commence pas par {mc}
	{
		$tc_translate = $balise.$tc_translate; // Alors on ajoute {mc}
	}
	if (!preg_match('#'.$baliseFin.'$#', $tc_translate)) // Si ne fini pas par {/mc}
	{
		$tc_translate = $tc_translate.$baliseFin;  // Alors on ajoute {/mc}
	}
	// $tc_translate = preg_replace("#\\'#", "'", $tc_translate);

// {t}*WTF! That's not possible, not even in the office! {t}*{/t}
	echo "Correction : @<strong>".$tc_translate."</strong>@<br>";

	if ($tc_translate_src != $tc_translate) 
	{
		$tc_translate = $mysqli->real_escape_string($tc_translate);
		// $tc_translate = preg_replace("#\\'#", "'", $tc_translate);
		echo "Ap Escape : @".$tc_translate."@<br>";

		$sql2 = "UPDATE translate_cache SET tc_translate = \"".$tc_translate."\" WHERE id_tc = '".$id_tc."' ";
		if(isset($_GET['execute']) && $_GET['execute'] == 1) $result2 = $mysqli->query($sql2);
		echo "<span style='color:#990000'>".$sql2."</span><br>";
		$sqlTotal .= "<span style='color:#990000'>".$sql2."</span><br>";
	}
	else
		echo "Pas de modif";
	echo "<br><br>";
}

print $sqlTotal."";


exit(); 


$mysqli->close(); 
  