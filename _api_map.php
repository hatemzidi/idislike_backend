<?php
/**
 * Created by PhpStorm.
 * User: h.zidi
 * Date: 04/11/13
 * Time: 15:33
 *
 */

// configs & imports
require_once 'config.php';
require_once 'vendor/autoload.php';
use GeoIp2\Database\Reader;

//connect
$logdb = new PDO("sqlite:" . $dbfolder . $dbname);

$ipReader = new Reader($dbGeofolder . '/data/GeoLite2-City.mmdb');

$geoip = array();

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

// close connection
$logdb = null;
$ipReader->close();
?>