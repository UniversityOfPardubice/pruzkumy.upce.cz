<?php

for ($sheet = 1; $sheet<=12; $sheet++) {
  $worksheet = $workbook->getSheet($sheet);
  $rows = $worksheet->getHighestRow();

  $oddeleniMap = array (
    'INT-JIP' => array('odbornost'=>'1I1', 'typPece'=>'I', 'oddeleni'=>'Inter', 'pracoviste'=>'Interní oddělení - JIP'),
    'ARO' => array('odbornost'=>'7I8', 'typPece'=>'I', 'oddeleni'=>'ARO', 'pracoviste'=>'Anesteziologicko-resuscitační oddělení'),
    'aro' => array('odbornost'=>'7I8', 'typPece'=>'I', 'oddeleni'=>'ARO', 'pracoviste'=>'Anesteziologicko-resuscitační oddělení'),
    'INT-1' => array('odbornost'=>'1H1', 'typPece'=>'S', 'oddeleni'=>'Inter', 'pracoviste'=>'Interní oddělení I.'),
    'INT-2' => array('odbornost'=>'1H1', 'typPece'=>'S', 'oddeleni'=>'Inter', 'pracoviste'=>'Interní oddělení II.'),
    'NEU' => array('odbornost'=>'2H9', 'typPece'=>'S', 'oddeleni'=>'Neuro', 'pracoviste'=>'Neurologie'),
    'DET-JIP' => array('odbornost'=>'3I1', 'typPece'=>'I', 'oddeleni'=>'Pedia', 'pracoviste'=>'Dětské oddělení JIP'),
    'DET-NOV' => array('odbornost'=>'3H4', 'typPece'=>'S', 'oddeleni'=>'Pedia', 'pracoviste'=>'Dětské oddělení - novorozenci'),
    'DET-MAL' => array('odbornost'=>'3H1', 'typPece'=>'S', 'oddeleni'=>'Pedia', 'pracoviste'=>'Dětské oddělení - malé děti'),
    'DET-VEL' => array('odbornost'=>'3H1', 'typPece'=>'S', 'oddeleni'=>'Pedia', 'pracoviste'=>'Dětské oddělení JIP'),
    'CHIR-OB' => array('odbornost'=>'5H1', 'typPece'=>'S', 'oddeleni'=>'Chiru', 'pracoviste'=>'Všeobecná chirurgie'),
    'CHIR-TRA' => array('odbornost'=>'5H1', 'typPece'=>'S', 'oddeleni'=>'Chiru', 'pracoviste'=>'Chirurgie - traumatologie'),
    'CHR-JIP_' => array('odbornost'=>'5I1', 'typPece'=>'I', 'oddeleni'=>'Chiru', 'pracoviste'=>'Chirurgie - JIP'),
    'GYN' => array('odbornost'=>'6H3', 'typPece'=>'S', 'oddeleni'=>'Gynek', 'pracoviste'=>'Gynekologie'),
    'POR' => array('odbornost'=>'6H3', 'typPece'=>'S', 'oddeleni'=>'Gynek', 'pracoviste'=>'Porodnice'),
    'URL' => array('odbornost'=>'7H6', 'typPece'=>'S', 'oddeleni'=>'Urolo', 'pracoviste'=>'Urologické oddělení'),
    'ORL' => array('odbornost'=>'7H1', 'typPece'=>'S', 'oddeleni'=>'ORL', 'pracoviste'=>'ORL oddělení'),
    'TRN Žamberk' => array('odbornost'=>'2H5', 'typPece'=>'S', 'oddeleni'=>'Pneum', 'pracoviste'=>'TRN Žamberk'),
    'NEU-4/I2' =>  array('odbornost'=>'4I2', 'typPece'=>'I', 'oddeleni'=>'Neuro', 'pracoviste'=>'Neurologie NEU-4/I2'),
  );

  for ($i=2; $i<=$rows; $i++) {
    $oddeleni = trim($worksheet->getCellByColumnAndRow(6, $i)->getValue());
    if (!$oddeleni) {
      $oddeleni = trim($worksheet->getCellByColumnAndRow(13, $i)->getValue());
    }
    $datumVzniku = trim($worksheet->getCellByColumnAndRow(14, $i)->getValue());
    $typInfekce = trim($worksheet->getCellByColumnAndRow(3, $i)->getValue());
    $puvodciExploded = array(trim($worksheet->getCellByColumnAndRow(4, $i)->getValue()));
    $rezistenceExploded = array(trim($worksheet->getCellByColumnAndRow(21, $i)->getValue()));

    if (!$oddeleni && !$datumVzniku && !$typInfekce) {
      continue;
    }

    $poznamky = array();
    $puvodceJiny = array();

    if (!isset($oddeleniMap[$oddeleni])) {
      throw new Exception('Neznámé oddělení "' . $oddeleni . '"');
    }
    if (!isset($typyInfekciMap[$typInfekce])) {
    echo"<pre>";print_r($typyInfekciMap);
      throw new Exception('Neznámý typ infekce "' . $typInfekce. '"');
    }

    $rezistence = array();
    $puvodci = array();
    foreach($puvodciExploded as $p) {
      $p = trim($p);
      if ($p) {
        if (!isset($puvodciMap[$p])) {
          throw new Exception('Neznámý původce "' . $p . '"');
        }
        if (is_array($puvodciMap[$p])) {
          $puvodci[] = $puvodciMap[$p]['puvodce'];
          if (isset($puvodciMap[$p]['puvodceJiny'])) {
            $puvodceJiny[] = $puvodciMap[$p]['puvodceJiny'];
          }
          if (isset($puvodciMap[$p]['rezistence'])) {
            $rezistence = array_merge($rezistence, $puvodciMap[$p]['rezistence']);
          }
          if (isset($puvodciMap[$p]['poznamka'])) {
            $poznamky[] = $puvodciMap[$p]['poznamka'];
          }
        } else {
          $puvodci[] = $puvodciMap[$p];
        }
      }
    }

    foreach($rezistenceExploded as $r) {
      $r = trim($r);
      if ($r) {
        if (!isset($rezistenceMap[$r])) {
          throw new Exception('Neznámá rezistence "' . $r . '"');
        }
        if (is_array($rezistenceMap[$r])) {
          if (isset($rezistenceMap[$r]['rezistence'])) {
            $rezistence[] = $rezistenceMap[$r]['rezistence'];
          }
          $poznamky[] = $rezistenceMap[$r]['poznamka'];
        } else {
          $rezistence[] = $rezistenceMap[$r];
        }
      }
    }

    if (preg_match('/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/', $datumVzniku)) {
      $datumVzniku=mktime(0, 0, 0,
        preg_replace('/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/', '\2', $datumVzniku),
        preg_replace('/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/', '\1', $datumVzniku),
        preg_replace('/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/', '\3', $datumVzniku));
    }
    elseif (preg_match('/([0-9]{1,2})\.([0-9]{1,2})/', $datumVzniku)) {
      $datumVzniku=mktime(0, 0, 0,
        preg_replace('/([0-9]{1,2})\.([0-9]{1,2})/', '\2', $datumVzniku),
        preg_replace('/([0-9]{1,2})\.([0-9]{1,2})/', '\1', $datumVzniku));
    } elseif (is_numeric($datumVzniku)) {
      $datumVzniku=($datumVzniku - 25569) * 86400;
    } else {
      throw new Exception('Neznámý formát data "' . $datumVzniku . '"');
    }
    $data[] = array(
      'oddeleni'=>$oddeleniMap[$oddeleni]['oddeleni'],
      'pracoviste'=>$oddeleniMap[$oddeleni]['pracoviste'],
      'odbornost'=>$oddeleniMap[$oddeleni]['odbornost'],
      'typPece'=>$oddeleniMap[$oddeleni]['typPece'],
      'typInfekce'=>$typyInfekciMap[$typInfekce],
      'puvodci'=>array_unique($puvodci),
      'puvodceJiny'=>implode(', ', $puvodceJiny),
      'rezistence'=>array_unique($rezistence),
      'datumVzniku'=>date('Y-m-d', $datumVzniku),
      'poznamka'=>implode("\n", $poznamky)
    );
  }
}

$sql = 'DELETE FROM lime_survey_725855 WHERE `725855X194X3421`="'.mysql_real_escape_string($nemocnice, $db).'"';
if (!mysql_query($sql, $db)) {
  throw new Exception("Could not perform select query - " . mysql_error($db) . "\n" . $sql);
}