<?php 

// anti flood
if (!isset($_SESSION)) {
    session_start();
}
// anti flood protection
if ($_SESSION['last_session_request'] > time() - 2){
	echo "0";
    exit(0);
}
$_SESSION['last_session_request'] = time();

// go, and add email

$file = '../website/emails.txt'; 

$msg  = date("d.m.y H:i") . "|" . $_SERVER['REMOTE_ADDR'] . "|" . $_REQUEST['name'] . "|";
$msg .= $_REQUEST['email'] . "|" . $_REQUEST['subject'] . "|" . $_REQUEST['message'] ."\n";

	$fp = fopen($file, "a+"); 
	if ( $fp ) {
		fwrite($fp, $msg); 
		echo "1";
		fclose($fp); 
	} else
		 header('HTTP/1.0 404 Not Found');
		
?>