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

//connect
$logdb = new PDO("sqlite:" . $dbfolder . $dbname);


$counters = array();

// get stats by hour
$list = $logdb->query("SELECT strftime('%H', d) as h, count(*) as v FROM tracker GROUP BY h");
$counters['hourstats'] = $list->fetchAll(PDO::FETCH_ASSOC);

// get stats by hour per day
$list = $logdb->query("SELECT strftime('%Y-%m-%d %H:00', d) as dh, count(*) as v FROM tracker  WHERE strftime('%Y-%m-%d',d) >= date('now','-7 days') GROUP BY strftime('%Y%m%d%H', d)");
$counters['daystats'] = $list->fetchAll(PDO::FETCH_ASSOC);

// get stats by day
$list = $logdb->query("SELECT strftime('%Y-%m-%d %H:00', d) as dh, count(*) as v FROM tracker  WHERE strftime('%Y-%m-%d',d) >= date('now','-14 days') GROUP BY strftime('%Y%m%d', d)");
$counters['weekstats'] = $list->fetchAll(PDO::FETCH_ASSOC);

// get stats for origins for the last week
$origins = array();
$list = $logdb->query("SELECT strftime('%Y-%m-%d %H:00', d) as dh, count(*) as comment FROM tracker  WHERE strftime('%Y-%m-%d',d) >= date('now','-7 days') and origin='comment' GROUP BY strftime('%Y%m%d%H', d)  ORDER BY dh ASC");
$tmp = $list->fetchAll(PDO::FETCH_ASSOC);

foreach ($tmp as $v) {
    $origins[$v['dh']] = array('dh' => $v['dh'], 'comment' => $v['comment']);
}

$list = $logdb->query("SELECT strftime('%Y-%m-%d %H:00', d) as dh, count(*) as chat FROM tracker  WHERE strftime('%Y-%m-%d',d) >= date('now','-7 days') and origin='chat' GROUP BY strftime('%Y%m%d%H', d) ORDER BY dh ASC");
$tmp = $list->fetchAll(PDO::FETCH_ASSOC);

foreach ($tmp as $v) {
    if (!array_key_exists($v['dh'], $origins)) {
        $origins[$v['dh']] = array('dh' => $v['dh'], 'chat' => $v['chat']);
    } else {
        $origins[$v['dh']]['chat'] = $v['chat'];
    }
}

/*
$list = $logdb->query("SELECT strftime('%Y-%m-%d %H:00', d) as dh, count(*) as old FROM tracker  WHERE strftime('%Y-%m-%d',d) >= date('now','-7 days') and origin='-' GROUP BY strftime('%Y%m%d%H', d) ORDER BY dh ASC");
$tmp = $list->fetchAll(PDO::FETCH_ASSOC);

foreach ($tmp as $v) {
    if (!array_key_exists($v['dh'], $origins)) {
        $origins[$v['dh']] = array('dh' => $v['dh'], 'chat' => $v['old']);
    } else {
        $origins[$v['dh']]['old'] = $v['chat'];
    }
}*/

$counters['origins'] = array_values($origins);

echo json_encode($counters);

// close connection
$logdb = null;
?>