<pre>
<?php
  /* $Id: transfer.php 218 2013-02-28 06:46:28Z lusl0338 $ */
  require_once('transfer-config.php');
  require_once('../../database-config.php');
  $db = mysql_connect($upceDatabaseConfig['host'], $upceDatabaseConfig['username'], $upceDatabaseConfig['password']);
  mysql_select_db($upceDatabaseConfig['database'], $db);
  mysql_query('set names utf8', $db);
  
  foreach ($transfers as $tr) {
    echo "\n" . $tr['from'] . '=>' . $tr['to'];
    $map = mysql_query('select f.qid f_qid, f.parent_qid f_pqid, f.gid f_gid, f.title f_title,
        t.qid t_qid, t.parent_qid t_pqid, t.gid t_gid, t.title t_title
      from lime_questions f
        inner join lime_questions t on (f.question_order=t.question_order AND f.title=t.title)
      where f.sid=' . $tr['from'] . ' and t.sid=' . $tr['to'] . '
      order by f.qid', $db);
    $mapping = array();
    while ($m = mysql_fetch_assoc($map)) {
      if ($m['f_pqid']) {
        $mapping[$tr['from'] . 'X'. $m['f_gid'] . 'X' . $m['f_qid'] . $m['f_title']] = $tr['to'] . 'X'. $m['t_gid'] . 'X' . $m['t_qid'] . $m['t_title'];
      } else {
        $mapping[$tr['from'] . 'X'. $m['f_gid'] . 'X' . $m['f_qid']] = $tr['to'] . 'X'. $m['t_gid'] . 'X' . $m['t_qid'];
      }
    }
    
    if ($tr['preTruncate']) {
      mysql_query('TRUNCATE lime_survey_' . $tr['to'], $db);
    }
    $dataQuery = mysql_query('SELECT * FROM lime_survey_' . $tr['from'] . ' WHERE ' . $tr['fromCondition'], $db);
    while ($d = mysql_fetch_assoc($dataQuery)) {
      echo ".";
      $query = 'INSERT INTO lime_survey_' . $tr['to'] . '(';
      $queryValues = '';
      $i = 0;
      foreach($d as $column=>$data) {
        if ($i>0) {
          $query.= ',';
          $queryValues.= ',';
        }
        $i++;
        $query.= '`' . strtr($column, $mapping) . '`';
        $queryValues.= '"' . mysql_real_escape_string($data, $db) . '"';
      }
      $query.= ') VALUES (' . $queryValues . ')';
      mysql_query($query, $db) or die("Could not perform select query - " . mysql_error($db) . "\n" . $query);
    }
  }
  
  mysql_close($db);
?>

OK