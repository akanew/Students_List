<?php
	require_once 'scripts/module.php';
	
	// ��������� ����������� � �� "university"
	$db_2_host = 'localhost';
	$db_2_login = 'root';
	$db_2_passwd = '';
	$db_2_name = 'university';

	// ����������� � �� "university"
	$db_2 = new mysql();
	$db_2 -> connect($db_2_host, $db_2_login, $db_2_passwd, $db_2_name);
?>