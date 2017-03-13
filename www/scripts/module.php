<?php

class mysql {

	// Подключение к БД
	function connect($db_host, $db_login, $db_passwd, $db_name) {
		mysql_connect($db_host, $db_login, $db_passwd) or die ("MySQL Error: " . mysql_error());
		mysql_query("set names utf8") or die ("<br>Invalid query: " . mysql_error()); // Данные в utf8
		mysql_select_db($db_name) or die ("<br>Invalid query: " . mysql_error());
	}
	
	// Запрос к БД и его производные
	function query($query, $type, $num) {
		if ($q=mysql_query($query)) {
			switch ($type) {
				case 'num_row' : return mysql_num_rows($q); break;
				case 'result' : return mysql_result($q, $num); break;
				case 'accos' : return mysql_fetch_assoc($q); break;
				case 'none' : return $q;
				default: return $q;
			}
		} else {
			print 'Mysql error: '.mysql_error();
			return false;
		}
	}
	
	// Добавление записи о студенте в БД
	function add_student($group_name, $name, $surname, $patronymic, $birthdate, $document) {
		if($this->query("SELECT * FROM students WHERE name='".$name."' and surname='".$surname."' and patronymic='".$patronymic."' and birthdate='".$birthdate."';", 'num_row', '') == 0) {
			$group_find_count = $this->query("SELECT * FROM groups WHERE name_group='".$group_name."';", 'num_row', '');
			if ($group_find_count == 0) {
				$this->query( "INSERT INTO groups(name_group) VALUES ('$group_name')",'none','');
				$group_id = $this->query("SELECT * FROM groups WHERE name_group='".$group_name."';", 'result', 0);
				$this->query( "INSERT INTO students(group_id, name, surname, patronymic, birthdate, document)".
				" VALUES ('$group_id','$name','$surname','$patronymic','$birthdate','$document')",'none','');
				return "true";
			}
			else if($group_find_count == 1) {
				$group_id = $this->query("SELECT * FROM groups WHERE name_group='".$group_name."';", 'result', 0);
				$this->query( "INSERT INTO students(group_id, name, surname, patronymic, birthdate, document)".
				" VALUES ('$group_id','$name','$surname','$patronymic','$birthdate','$document')",'none','');
				return "true";
			}
		}
		else return "false";
	}
		
	//	Сообщение о добавлении записи
	function success_print() {
		return '<h4>Запись успешно добавлена!</h4>';
	}
	
	//	Формирование списка ошибок
	function error_print() {
		$r='<h4>Произошли следующие ошибки:</h4>'."\n";
		$r.='<li>Запись о данном студенте уже содержится в БД</li>';
		return $r;
	}
}

class registration {
	
	//	Проверка входных данных при регистрации
	function check_new_user($login, $passwd, $passwd2, $mail) {
		if (empty($login) or empty($passwd) or empty($passwd2) or empty($mail)) $error[]='Все поля обязательны для заполнения';
		if ($passwd != $passwd2) $error[]='Введенные пароли не совпадают';
		if (strlen($login)<3 or strlen($login)>30) $error[]='Длинна логина должна быть от 3 до 30 символов';
		if (strlen($passwd)<3 or strlen($passwd)>30) $error[]='Длинна пароля должна быть от 3 до 30 символов';
		if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) $error[]='Не корректный email';
		
		// Проверяем наличее аналогичных данных в БД
		$db = new mysql();
		$login = $login;
		if ($db->query("SELECT * FROM users WHERE login_user='".$login."';", 'num_row', '')!=0) $error[]='Пользователь с таким именем уже существует';
		if ($db->query("SELECT * FROM users WHERE mail_user='".$mail."';", 'num_row', '')!=0) $error[]='Пользователь с таким email уже существует';

		// Возвращаем массив ошибок или положительный ответ
		if (isset($error)) return $error;
		else return 'success';
	}

	//	Регистрация
	function reg($login, $passwd, $passwd2, $mail) {
		if (($this->check_new_user($login, $passwd, $passwd2, $mail))=='success') {
			$db = new mysql();
			if ($db->query("INSERT INTO `users` (`id_user`, `login_user`, `passwd_user`, `mail_user`) VALUES (NULL, '".$login."', '".md5($passwd)."', '".$mail."');", '', '')) {
				return 'success';
			} 
			else {
				return 'Возникла ошибка при регистрации нового пользователя. Свяжитесь с администрацией';
			}
		} else {
			return $this->error_print($this->check_new_user($login, $passwd, $passwd2, $mail));
		}
	}
	
	//	Формирование списка ошибок
	function error_print($error) {
		$r='<h4>Произошли следующие ошибки:</h4>'."\n".'<ul>';
		foreach($error as $key=>$value) {
			$r.='<li>'.$value.'</li>';
		}
		return $r.'</ul>';
	}
	
	//	Успешная авторизация
	function success_print() {
		$r='<h4>Регистрация прошла успешно!</h4>'."\n".'Теперь Вы можете войти, используя данные указанные при регистрации.';
		return $r;
	}
}

class auth {
	
	//	Авторизация
	function authorization($login, $passwd) {
		$db = new mysql();
		
		if ($db->query("SELECT * FROM `users` WHERE  `login_user` =  '".$login."' AND  `passwd_user` = '".$passwd."';", 'num_row', '')==1) {
			//Пользователь найден в БД, логин и пароль совпадают
			$_SESSION['id_user']=$db->query("SELECT * FROM `users` WHERE  `login_user` =  '".$login."' AND  `passwd_user` = '".$passwd."';", 'result', 0);
			$_SESSION['login_user']=$login;
			$r_code = $this->generateCode(15);
			
			// запись уже есть - обновляем
			if ($db->query("SELECT * FROM `session` WHERE `id_user`=".$_SESSION['id_user'].";", 'num_row', '')==1) {				
				$db->query("UPDATE `session` SET `code_sess` = '".$r_code."', `user_agent_sess` = '".$_SERVER['HTTP_USER_AGENT']."' WHERE `id_user` = ".$_SESSION['id_user'].";", '', '');
			} 
			// записи нет - добавляем
			else {				
				$db->query("INSERT INTO `session` (`id_user`, `code_sess`, `user_agent_sess`) VALUES ('".$_SESSION['id_user']."', '".$r_code."', '".$_SERVER['HTTP_USER_AGENT']."');", '', '');
			}
			setcookie("id_user", $_SESSION['id_user'], time()+3600*24*14); // cookie хранятся две недели
			setcookie("code_user", $r_code, time()+3600*24*14);
			return true;
		}
		// пользователь не найден в БД, или пароль не соответствует введенному
		else {			
			if ($db->query("SELECT * FROM  `users` WHERE  `login_user` =  '".$login."';", 'num_row', 0)==1) 
				$error[]='Введен не верный пароль';
			else 
				$error[]='Такой пользователь не существует';
			$_SESSION['error'] = $this->error_print($error);
			return false;
		}
	}	
	
	//	Формирование списка ошибок
	function error_print($error) {
		$r='<h4>Произошли следующие ошибки:</h4>'."\n".'<ul>';
		foreach($error as $key=>$value) {
			$r.='<li>'.$value.'</li>';
		}
		return $r.'</ul>';
	}
	
	//	Функция генерации случайной строки
	function generateCode($length) { 
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789"; 
		$code = ""; 
		$clen = strlen($chars) - 1;   
		while (strlen($code) < $length) { 
			$code .= $chars[mt_rand(0,$clen)];   
		} 
		return $code; 
	}
	
	//	Проверка авторизации
	function check() {
		if (isset($_SESSION['id_user']) and isset($_SESSION['login_user'])) return true;
		else {
			// проверяем наличие cookie
			if (isset($_COOKIE['id_user']) and isset($_COOKIE['code_user'])) {
				// cookie есть - сверяем с таблицей сессий
				$db = new mysql();
				$id_user=$_COOKIE['id_user'];
				$code_user=$_COOKIE['code_user'];
				if ($db->query("SELECT * FROM `session` WHERE `id_user`=".$id_user.";", 'num_row', '')==1) {
					// Есть запись в таблице сессий, сверяем данные
					$data = $db->query("SELECT * FROM `session` WHERE `id_user`=".$id_user.";", 'accos', '');
					if ($data['code_sess']==$code_user and $data['user_agent_sess']==$_SERVER['HTTP_USER_AGENT']) {
						// Данные верны, стартуем сессию
						$_SESSION['id_user']=$id_user;
						$_SESSION['login_user']=$db->query("SELECT login_user FROM `users` WHERE  `id_user` = '".$id_user."';", 'result', 0);
						// обновляем cookie
						setcookie("id_user", $_SESSION['id_user'], time()+3600*24*14);
						setcookie("code_user", $code_user, time()+3600*24*14);
						return true;
					} else return false; // данные в таблице сессий не совпадают с cookie
				} else return false; // в таблице сессий не найден такой пользователь
			} else return false;
		}
	}
	
	// разрушаем сессию, удаляем cookie и отправляем на главную
	function exit_user() {		
		session_destroy();
		setcookie("id_user", '', time()-3600);
		setcookie("code_user", '', time()-3600);
		header("location: index.html");
	}
}
?>