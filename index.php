<?php
/**
 * Created by IntelliJ IDEA.
 * User: tom
 * Date: 14/11/2015
 * Time: 02:12
 */


require_once 'vendor/autoload.php';
require_once 'config.php';

// use GeoIp2\Database\Reader;
use Jenssegers\Agent\Agent;

//connect
$logdb = new PDO("sqlite:" . $dbfolder . $dbname);
//$ipReader = new Reader($dbGeofolder . '/data/GeoLite2-City.mmdb');

$app = new \Slim\Slim();

$app->get('/', function () {
    echo "Oops!";
});

$app->get('/counters', function () {

    $counters = array();
    $logdb = $GLOBALS['logdb'];
    $agent = new Agent();

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

    $br = array();
    $os = array();

// get agents
    $list = $logdb->query("SELECT browser, count(*) as c FROM tracker GROUP BY browser");

    foreach ($list->fetchAll(PDO::FETCH_ASSOC) as $item) {
//        echo $item['browser'];
        $agent->setUserAgent($item['browser']);
        $browser = $agent->browser();
        $platform = $agent->platform();

//        echo $agent->platform();
//        echo $agent->browser();

        $key = !empty($browser) ? $browser : 'unkwn';
        if (!array_key_exists($key, $br)) {
            $br[$key] = 0;
        }
        $br[$key] += $item['c'];

        $key = !empty($platform) ? $platform : 'unkwn';

        if (!array_key_exists($key, $os)) {
            $os[$key] = 0;
        }
        $os[$key] += $item['c'];

    }

    foreach ($br as $k => $v) {
        $counters['agent']['browsers'][] = array("value" => $v, "label" => $k);
    }

    foreach ($os as $k => $v) {
        if ($k == "") $k = "unknown";
        $counters['agent']['os'][] = array("value" => $v, "label" => $k);
    }

    echo json_encode($counters);
});


$app->get('/stats', function () {
    $counters = array();
    $logdb = $GLOBALS['logdb'];

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
    asort($origins);


    $list = $logdb->query("SELECT strftime('%Y-%m-%d %H:00', d) as dh, count(*) as keyboard FROM tracker  WHERE strftime('%Y-%m-%d',d) >= date('now','-7 days') and origin='keyboard' GROUP BY strftime('%Y%m%d%H', d) ORDER BY dh ASC");
    $tmp = $list->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tmp as $v) {
        if (!array_key_exists($v['dh'], $origins)) {
            $origins[$v['dh']] = array('dh' => $v['dh'], 'keyboard' => $v['keyboard']);
        } else {
            $origins[$v['dh']]['keyboard'] = $v['keyboard'];
        }
    }
    asort($origins);

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
});


$app->get('/map', function () {

    $geoip = array();
    $logdb = $GLOBALS['logdb'];

// get tracked data
    $list = $logdb->query("SELECT iso_code, city_name, latitude, longitude, count(*) as c FROM tracker WHERE datetime(d) >= datetime('now', '-12 hours') GROUP BY iso_code, city_name, latitude, longitude");

    foreach ($list->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $geoip[] = array('counter' => $item['c'],
            'isoCode' => strtolower($item['iso_code']),
            'city' => $item['city_name'],
            'latitude' => $item['latitude'],
            'longitude' => $item['longitude']);
    }


    echo json_encode($geoip);

});

$app->get('/map_large', function () {

    $logdb = $GLOBALS['logdb'];

    $stats = array();
    $stats[] = array('Country', 'Clicks');

// get status per country
    $list = $logdb->query("SELECT country_name, count(*) as c FROM tracker WHERE country_name is not null GROUP BY country_name");

    foreach ($list->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $stats[] = array($item['country_name'], (int)$item['c']);
    }

    echo json_encode($stats);

});


$app->run();