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
$stats = array();
$stats[] = array('Country', 'Clicks');

// get status per country
$list = $logdb->query("SELECT country_name, count(*) as c FROM tracker WHERE country_name is not null GROUP BY country_name");

foreach ($list->fetchAll(PDO::FETCH_ASSOC) as $item) {
    $stats[] = array($item['country_name'], (int)$item['c']);
}


echo json_encode($stats);

// close connection
$logdb = null;
?>