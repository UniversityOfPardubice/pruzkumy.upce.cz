<?php
header('Content-Type: text/html; charset=UTF-8');
if(!(isset($_FILES['soubory']))) {
  echo '<form method="post" enctype="multipart/form-data">
  <select name="nemocnice"><option value="PARDU">Pardubice</option><option value="LITOM">Litomyšl</option><option value="CHRUD">Chrudim</option><option value="ORLIC">Ústí nad Orlicí</option><option value="SVITA">Svitavy</option></select>
  <input type="file" name="soubory[]" multiple><input type="submit" value="Odeslat"></form>';
  exit;
}
for ($fileId = 0; $fileId < sizeof($_FILES['soubory']['name']); $fileId++) {
  echo '<h1>' . $_FILES['soubory']['name'][$fileId] . '</h1>';
  require_once 'PHPExcel/Classes/PHPExcel/IOFactory.php';
  require_once('setup.php');
  $workbook = PHPExcel_IOFactory::load($_FILES['soubory']['tmp_name'][$fileId]);
  $nemocnice = $_POST['nemocnice'];

  $data = array();

  require_once('../../../database-config.php');
  $db = mysql_connect($upceDatabaseConfig['host'], $upceDatabaseConfig['username'], $upceDatabaseConfig['password']);
  mysql_select_db($upceDatabaseConfig['database'], $db);
  mysql_query('set names utf8', $db);

  switch ($nemocnice) {
    case 'CHRUD':
    case 'LITOM':
      require 'import-common.php';
      break;
    case 'SVITA':
      require 'import-svitavy.php';
      break;
    case 'ORLIC':
      require 'import-usti.php';
      break;
    case 'PARDU':
      require 'import-pardubice.php';
      break;
    default:
      die('Neznámý import pro nemocnici ' . $nemocnice);
  }

  echo '<table border="1"><tr><th>Oddělení</th><th>Pracoviště</th><th>Typ péče</th><th>Odbornost</th><th>Datum vzniku</th><th>Typ infekce</th><th>Původci</th><th>Jiný původce</th><th>Rezistence</th><th>Poznámka</th></tr>';
  foreach ($data as $pripad) {
    if (in_array('MRSA', $pripad['rezistence'])) {
      if (!in_array('StaphylococcusAureus', $pripad['puvodci'])) {
        $pripad['puvodci'][] = 'StaphylococcusAureus';
      }
    }
    $sql = 'INSERT INTO lime_survey_725855 SET submitdate=NOW(), lastpage=1, startlanguage="cs"';
    echo '<tr>';
    $sql.= ', `725855X194X3434`="'.mysql_real_escape_string($pripad['oddeleni'], $db).'"';
    echo '<td>'.$pripad['oddeleni'].'</td>';
    if ($pripad['pracoviste']) { $sql.= ', `725855X194X3433`="'.mysql_real_escape_string($pripad['pracoviste'], $db).'"'; }
    echo '<td>'.$pripad['pracoviste'].'</td>';
    $sql.= ', `725855X194X3373`="'.mysql_real_escape_string($pripad['typPece'], $db).'"';
    echo '<td>'.$pripad['typPece'].'</td>';
    $sql.= ', `725855X194X3372`="'.mysql_real_escape_string($pripad['odbornost'], $db).'"';
    echo '<td>'.$pripad['odbornost'].'</td>';
    $sql.= ', `725855X194X3422`="'.mysql_real_escape_string($pripad['datumVzniku'], $db).'"';
    echo '<td>'.$pripad['datumVzniku'].'</td>';
    $sql.= ', `725855X194X3374`="'.mysql_real_escape_string($pripad['typInfekce'], $db).'"';
    echo '<td>'.$pripad['typInfekce'].'</td>';
    foreach ($pripad['puvodci'] as $puvodce) {
      if ($puvodce) {
        $sql.= ', `725855X194X3375' . $puvodce . '`="Y"';
      }
    }
    echo '<td>'.print_r($pripad['puvodci'], true).'</td>';
    $sql.= ', `725855X194X3375other`="'.mysql_real_escape_string($pripad['puvodceJiny'], $db).'"';
    echo '<td>'.$pripad['puvodceJiny'].'</td>';
    foreach ($pripad['rezistence'] as $rezistence) {
      if ($rezistence) {
        $sql.= ', `725855X194X3428' . $rezistence . '`="Y"';
      }
    }
    echo '<td>'.print_r($pripad['rezistence'], true).'</td>';
    $sql.= ', `725855X194X3435`="'.mysql_real_escape_string($pripad['poznamka'], $db).'"';
    $sql.= ', `725855X194X3421`="'.mysql_real_escape_string($nemocnice, $db).'"';
    echo '<td>'.nl2br($pripad['poznamka']).'</td>';
    mysql_query($sql, $db) or die("Could not perform select query - " . mysql_error($db) . "\n" . $sql);
    echo '</tr>';
  }
  echo '</table>';
}
mysql_close($db);
