<?php
/**
 * Created by PhpStorm.
 * User: h.zidi
 * Date: 04/11/13
 * Time: 15:33
 *
 */
// configs
require_once 'config.php';
require_once 'lib/getBrowser.php';

//connect
$logdb = new PDO("sqlite:" . $dbfolder . $dbname);


$counters = array();

// well ...
$statement = $logdb->query("SELECT counter FROM hits WHERE id=1");
$record = $statement->fetch(PDO::FETCH_ASSOC);
$counters['total'] = $record['counter'];

// get referrers
$list = $logdb->query("SELECT referrer, count(*) as c FROM tracker GROUP BY referrer");
foreach ($list->fetchAll(PDO::FETCH_ASSOC) as $item) {
  $key = !empty($item['referrer']) ? $item['referrer'] : 'unkwn';
  $counters['referrers'][] = array("value" => $item['c'], 'label' => $key);
}

// get origin
$list = $logdb->query("SELECT origin, count(*) as c FROM tracker WHERE origin !='-' GROUP BY origin");
foreach ($list->fetchAll(PDO::FETCH_ASSOC) as $item) {
  $key = !empty($item['origin']) ? $item['origin'] : 'unkwn';
  $counters['origins'][] = array("value" => $item['c'], 'label' => $key);
}

$r = array();

// get agents
$list = $logdb->query("SELECT browser, count(*) as c FROM tracker GROUP BY browser");
foreach ($list->fetchAll(PDO::FETCH_ASSOC) as $item) {
  $record = new OS_BR($item['browser']);
  $r['browser'][$record->getBrowser()] += $item['c'];
  $r['os'][$record->getOS()] += $item['c'];
}
foreach( $r['browser'] as $k => $v) {
  $counters['agent']['browsers'][] = array("value" => $v , "label" => $k);
}
foreach( $r['os'] as $k => $v) {
  if ( $k == "" ) $k = "unknown";
  $counters['agent']['os'][] = array("value" => $v , "label" => $k);
}


// get stats by hour
$list = $logdb->query("SELECT strftime('%H', d) as h, count(*) as v FROM tracker GROUP BY h");
$counters['hourstats'] = $list->fetchAll(PDO::FETCH_ASSOC);

// get stats by hour per day
$list = $logdb->query("SELECT strftime('%Y-%m-%d %H:00', d) as dh, count(*) as v FROM tracker GROUP BY strftime('%Y%m%d%H0', d)
");
$counters['daystats'] = $list->fetchAll();




echo json_encode($counters);

// close connection
$logdb = null;
?>