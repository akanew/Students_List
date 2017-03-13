<?php
	session_start();
	require_once 'scripts/module.php';

	// Параметры потключения к БД "authorization"
	$db_host = 'localhost';
	$db_login = 'root';
	$db_passwd = '';
	$db_name = 'authorization';

	// Подключение к БД "authorization"
	$db = new mysql();
	$db -> connect($db_host, $db_login, $db_passwd, $db_name);
?>