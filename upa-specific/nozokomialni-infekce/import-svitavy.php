<?php

$worksheets = array(1, 2, 3);

foreach ($worksheets as $worksheetId) {
  $worksheet = $workbook->getSheet($worksheetId);
  $rows = $worksheet->getHighestRow();

  $oddeleniMap = array (
    'INT' => array('odbornost'=>'101', 'typPece'=>'S', 'oddeleni'=>'Inter', 'pracoviste'=>'INT'),
    'AROL' => array('odbornost'=>'7I8', 'typPece'=>'I', 'oddeleni'=>'ARO', 'pracoviste'=>'AROL'),
    'LDN' => array('odbornost'=>'9U7', 'typPece'=>'N', 'oddeleni'=>'LDN', 'pracoviste'=>'LDN'),
    'CHIL' => array('odbornost'=>'501', 'typPece'=>'S', 'oddeleni'=>'Chiru', 'pracoviste'=>'CHIL'),
    'CHIJ' => array('odbornost'=>'5I1', 'typPece'=>'I', 'oddeleni'=>'Chiru', 'pracoviste'=>'CHIJ'),
    'PEDV' => array('odbornost'=>'301', 'typPece'=>'S', 'oddeleni'=>'Pedia', 'pracoviste'=>'PEDV'),
    'PEDK' => array('odbornost'=>'301', 'typPece'=>'S', 'oddeleni'=>'Pedia', 'pracoviste'=>'PEDK'),
    'NOVO' => array('odbornost'=>'301', 'typPece'=>'S', 'oddeleni'=>'Pedia', 'pracoviste'=>'NOVO'),
    'GYNL' => array('odbornost'=>'603', 'typPece'=>'S', 'oddeleni'=>'Gynek', 'pracoviste'=>'GYNL'),
    'IJIP' => array('odbornost'=>'1I1', 'typPece'=>'I', 'oddeleni'=>'Inter', 'pracoviste'=>'IJIP'),
    'PSL' => array('odbornost'=>'305', 'typPece'=>'S', 'oddeleni'=>'Psych', 'pracoviste'=>'PSL'),
    'ORL' => array('odbornost'=>'701', 'typPece'=>'S', 'oddeleni'=>'ORL', 'pracoviste'=>'ORL'),
    'UROL' => array('odbornost'=>'706', 'typPece'=>'S', 'oddeleni'=>'Urolo', 'pracoviste'=>'UROL'),
    'Urol' => array('odbornost'=>'706', 'typPece'=>'S', 'oddeleni'=>'Urolo', 'pracoviste'=>'UROL'),
  );

  for ($i=2; $i<=$rows; $i++) {
    $oddeleni = trim($worksheet->getCellByColumnAndRow(2, $i)->getValue());
    $datumVzniku = trim($worksheet->getCellByColumnAndRow(4, $i)->getValue());
    $typInfekce = trim($worksheet->getCellByColumnAndRow(6, $i)->getValue());
    $puvodciExploded = array();
    for ($j=7; $j<=9; $j++) {
      $puvodciExploded[] = trim($worksheet->getCellByColumnAndRow($j, $i)->getValue());
    }
    $rezistenceExploded = array();
    for ($j=10; $j<=11; $j++) {
      $rezistenceExploded[] = trim($worksheet->getCellByColumnAndRow($j, $i)->getValue());
    }

    if (!$oddeleni && !$datumVzniku && !$typInfekce) {
      continue;
    }

    $poznamky = array();
    $puvodceJiny = array();

    if (!isset($oddeleniMap[$oddeleni])) {
      throw new Exception('Neznámé oddělení "' . $oddeleni . '"');
    }
    if (!isset($typyInfekciMap[$typInfekce])) {
      throw new Exception('Neznámý typ infekce "' . $typInfekce. '"');
    }

    $puvodci = array();
    $rezistence = array();
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