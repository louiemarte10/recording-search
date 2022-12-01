#!/usr/bin/php
<?php

if (empty($argv[1]) || !preg_match('/^[0-9]{4,}-[0-9]{13}$/', $argv[1])){
	exit(1);
}

require '../common/Common.php';

$db = Common::DB('cdr');

$res = $db->query("SELECT recording FROM recordings WHERE cdr_id = '{$argv[1]}'");

if ($row = $res->fetch_row()){
	echo $row[0];
}
