<?php
/**
 * Created by PhpStorm.
 * User: h.zidi
 * Date: 04/11/13
 * Time: 15:33
 *
 */

// no-cache headers - complete set
header("Expires: Sat, 01 Jan 1980 00:00:00 GMT"); // Some time in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// image related headers
header('Accept-Ranges: bytes');
header('Content-Length: ' . filesize( dirname(__FILE__) . '/blank.gif') ); // How many bytes we're going to send
header('Content-Type: image/gif');



// configs & imports
require_once 'config.php';
require_once 'vendor/autoload.php';
use GeoIp2\Database\Reader;

// sniffer
$browser = $_SERVER['HTTP_USER_AGENT']; // get the browser name
$ip = $_SERVER['REMOTE_ADDR']; // get the IP address
$referrer = isset($_GET['r']) ? $_GET['r'] : "none"; //  page from which visitor came
$origin = isset($_GET['o']) ? $_GET['o'] : "-"; //  origin of the dislike (fb only)


// connect
$logdb = new PDO("sqlite:" . $dbfolder . $dbname);
$ipReader = new Reader($dbGeofolder . '/data/GeoLite2-City.mmdb');

// check if database file exists first
// first shot, create the tables
// init
if (!file_exists($dbfolder . $dbname)) {
    $logdb->exec("CREATE TABLE hits(id INTEGER PRIMARY KEY, counter INTEGER)");
    $logdb->exec(
        "CREATE TABLE tracker (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        browser TEXT default '',
        ip varchar(15) NOT NULL default '',
        d DATETIME NOT NULL default CURRENT_TIMESTAMP,
        referrer TEXT NOT NULL default '' ,
        iso_code TEXT default '',
        country_name TEXT default '',
        city_name TEXT default '',
        latitude TEXT default '0',
        longitude TEXT default '0',
        origin TEXT default '-')"
    );
    $logdb->exec("INSERT INTO hits(id, counter) VALUES (1, 0)");
}


// get the geo-data
$record = $ipReader->city($ip);
$iso_code = isset($record->country) ? $record->country->isoCode : '?';
$country_name = isset($record->country) ? $record->country->name : '?';
$city_name = isset($record->city) ? $record->city->name : '?';
$latitude = isset($record->location) ? $record->location->latitude : '0';
$longitude = isset($record->location) ? $record->location->longitude : '0';

// and boom ...
$logdb->exec("UPDATE hits SET counter=counter+1 WHERE id=1");

// track it!
$logdb->exec("INSERT INTO tracker(browser, ip, referrer, iso_code, country_name, city_name, latitude, longitude, origin)
              VALUES ('$browser','$ip', '$referrer', '$iso_code', '$country_name', '$city_name', '$latitude', '$longitude', '$origin')
              ");


// close connection
$logdb = null;
$ipReader->close();

// fake image
echo file_get_contents(dirname(__FILE__) . '/blank.gif');
?>