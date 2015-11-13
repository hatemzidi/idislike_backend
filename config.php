<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 30/05/2014
 * Time: 20:43
 */

$mode = "dev"; // dev | prod

// config

if ($mode == 'dev') {
  $dbfolder = "data/";
  $dbGeofolder = __DIR__;
  error_reporting(E_ALL ^ E_NOTICE);
  ini_set('display_errors', 1);
} else {
  $dbfolder = $_SERVER["DOCUMENT_ROOT"] . "/data/";
  $dbGeofolder = $_SERVER['DOCUMENT_ROOT'];
}

header('Access-Control: allow *');
header('Access-Control-Allow-Origin: *');

$dbname = "counter.sq3";