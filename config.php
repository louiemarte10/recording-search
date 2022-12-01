<?php

require '../common/Common.php';
require '../common/Login.php';

Common::init(null);

if (Login::isLoggedIn()){
	$roles = Login::info('roles');
	define('FULL_SEARCH', $roles && count(array_intersect([6,9,16,17,24,72,73], $roles)) > 0);
} else {
	define('FULL_SEARCH', false);
}
