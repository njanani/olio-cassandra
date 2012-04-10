<?php
	require_once('phpcassa/connection.php');
	require_once('phpcassa/columnfamily.php');

	$servers[0]['host'] = '127.0.0.1';
        $servers[0]['port'] = '9160';
	$conn = new Connection('Keyspace1', $servers);        
?>
