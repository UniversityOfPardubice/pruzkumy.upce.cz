<?php

$worksheet = $workbook->getActiveSheet();

$rows = $worksheet->getHighestRow();

$rok = 0;
for ($i=2; $i<=$rows; $i++) {
  /*
    (0-A Oddělení) - z odbornosti
    1-B Pracoviště
    (2-C Typ péče) - z odbornosti
    3-D Odbornost
    4-E Datum vzniku
    5-F Typ infekce
    6-G Původce
    7-H Rezistence
  */
  $pracoviste = trim($worksheet->getCellByColumnAndRow(1, $i)->getValue());
  $odbornost = trim($worksheet->getCellByColumnAndRow(3, $i)->getValue());
  $datumVzniku = trim($worksheet->getCellByColumnAndRow(4, $i)->getValue());
  $typInfekce = trim($worksheet->getCellByColumnAndRow(5, $i)->getValue());
  $puvodciRaw = trim($worksheet->getCellByColumnAndRow(6, $i)->getValue());
  $rezistenceRaw = trim($worksheet->getCellByColumnAndRow(7, $i)->getValue());
  
  if (!$odbornost || !$datumVzniku) {
    continue;
  }
  echo $i."\n";
  
  $poznamky = array();
  $puvodceJiny = array();
  
  if (!isset($odbornostiMap[$odbornost])) {
    throw new Exception('Neznámá odbornost "' . $odbornost . '"');
  }
  if (!isset($typyInfekciMap[$typInfekce])) {
    throw new Exception('Neznámý typ infekce "' . $typInfekce. '"');
  }
  
  $puvodciExploded = preg_split('/[,;]/', $puvodciRaw);
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

  $rezistenceExploded = preg_split('/[,;]/', $rezistenceRaw);
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
  
  if (preg_match('/([0-9]{1,2})[.,]+([0-9]{1,2})[.,]+([0-9]{4})/', $datumVzniku)) {
    $datumVzniku=mktime(0, 0, 0,
      preg_replace('/([0-9]{1,2})[.,]+([0-9]{1,2})[.,]+([0-9]{4})/', '\2', $datumVzniku),
      preg_replace('/([0-9]{1,2})[.,]+([0-9]{1,2})[.,]+([0-9]{4})/', '\1', $datumVzniku),
      preg_replace('/([0-9]{1,2})[.,]+([0-9]{1,2})[.,]+([0-9]{4})/', '\3', $datumVzniku));
    $rok = preg_replace('/([0-9]{1,2})[.,]+([0-9]{1,2})[.,]+([0-9]{4})/', '\3', $datumVzniku);
  } elseif (preg_match('/([0-9]{1,2})[.,]([0-9]{1,2})[.,]/', $datumVzniku)) {
    if (!$rok) {
      throw new Exception('Neznámý formát data "' . $datumVzniku . '"; chybí rok');
    }
    $datumVzniku=mktime(0, 0, 0,
      preg_replace('/([0-9]{1,2})[.,]([0-9]{1,2})[.,]/', '\2', $datumVzniku),
      preg_replace('/([0-9]{1,2})[.,]([0-9]{1,2})[.,]/', '\1', $datumVzniku),
      $rok);
  } elseif (is_numeric($datumVzniku)) {
    $datumVzniku=($datumVzniku - 25569) * 86400;
  } else {
    throw new Exception('Neznámý formát data "' . $datumVzniku . '"');
  }
  $data[] = array(
    'oddeleni'=>$odbornostiMap[$odbornost]['oddeleni'],
    'pracoviste'=>$pracoviste,
    'odbornost'=>$odbornostiMap[$odbornost]['odbornost'],
    'typPece'=>$odbornostiMap[$odbornost]['typPece'],
    'typInfekce'=>$typyInfekciMap[$typInfekce],
    'puvodci'=>array_unique($puvodci),
    'puvodceJiny'=>implode(', ', $puvodceJiny),
    'rezistence'=>array_unique($rezistence),
    'datumVzniku'=>date('Y-m-d', $datumVzniku),
    'poznamka'=>implode("\n", $poznamky)
  );
}
