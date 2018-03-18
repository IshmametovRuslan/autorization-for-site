<?php

include 'config.php';

global $link;

/**
 * Функция подключения к БД
 */
if ( empty( $link ) ) {
	$link     = @mysqli_connect( HOST, LOGIN, PASSWORD, DATABASE );
	$redirect = false;
	if ( ! $link ) {
		print( 'Ошибка при подключении к серверу MySQL: ' . mysqli_connect_error() );
		$redirect = true;
	}
}

/**
 * Функция определения запршенной страницы
 *
 * @return string
 *
 */
function get_page() {
	$page = '';
	if ( ! empty( $_GET['p'] ) ) {
		$page = $_GET['p'];
	}

	return $page;
}

/**
 * Функция выполнения запроса к БД
 *
 * @param $query
 *
 * @return bool|mysqli_result|string
 *
 */
function do_query( $query ) {
	global $link;
	if ( $link ) {
		mysqli_set_charset( $link, 'utf8' );

		$result = mysqli_query( $link, $query );
		if ( ! $result ) {
			return mysqli_error( $link );
		}

		return $result;
	}

	return false;
}

/**
 * Функция хэширования пароля пользователя
 *
 * @param $password
 *
 * @return string
 *
 */
function password_hashing( $password ) {
	$password = md5( md5( trim( $password ) ) );

	return $password;
}

/**
 * Функция регистрации пользователя
 *
 */
function registration_user() {
	if ( isset( $_POST['regButton'] ) ) {
		if ( ! empty ( $_POST['first_name'] ) && ! empty ( $_POST['last_name'] ) && ! empty( $_POST['email_login'] ) &&
		     ! empty ( $_POST['password_login'] ) ) {
			$error = [];

			if ( ! preg_match( "/^[a-zа-яё]/i", $_POST['first_name'] ) or ! preg_match( "/^[a-zа-яё]/i", $_POST['last_name'] ) ) {
				$error[] = "Имя и фамилия должны содержать только буквы";
			}

			if ( ! preg_match( "/[0-9a-z_\.\-]+@[0-9a-z_\.\-]+\.[a-z]{2,4}/i", $_POST['email_login'] ) ) {
				$error[] = "Некорректный Email";
			}

			if ( strlen( $_POST['password_login'] ) < 6 or strlen( $_POST['password_login'] ) > 100 ) {
				$error[] = "Пароль не должен быть короче 6 и не более 100 символов";
			}

			if ( count( $error ) == 0 ) {
				$firstName = $_POST['first_name'];
				$lastName  = $_POST['last_name'];
				$email     = $_POST['email_login'];
				$password  = password_hashing( $_POST['password_login'] );
				do_query( "INSERT INTO user_reg SET email = '" . $email . "', password = '" . $password . "', first_name = '" . $firstName . "', last_name = '" . $lastName . "'" );
				$query = do_query( "SELECT count(*) FROM user_reg WHERE email = '{$_POST['email_login']}'" );
				if ( mysqli_num_rows( $query ) > 0 ) {
					$error[] = "Пользователь с таким Email уже существует";
				}
				header( "Location: index.php" );
			} else {
				echo "<strong>При регистрации произошли следующие ошибки:</strong>";
				foreach ( $error as $value ) {
					echo $value . "<br>";
				}
			}
		}
	}
}

registration_user();

/**
 * Функция авторизации пользователя
 *
 */
function authorization_user() {

	if ( isset( $_POST['authButton'] ) ) {

		if ( ! empty( $_POST['email_login'] ) && ! empty( $_POST['password_login'] ) ) {

			$email    = $_POST['email_login'];
			$password = password_hashing( $_POST['password_login'] );
			$sql      = "SELECT count(*) FROM user_reg WHERE email = '{$_POST['email_login']}'";
			$result   = do_query( $sql );
			$rows     = $result->fetch_row();

			if ( $rows[0] == 1 ) {
				setcookie( 'user', implode( ';', [ $email, $password ] ), time() + 60 * 60 * 24 );
			} else {
				echo 'Пользователя с таким логином и паролем не существует.';
			}
			header( "Location: index.php" );

			die();
		}
	}
}

authorization_user();

/**
 * Функция проверки залогинен ли пользователь
 * @return bool
 *
 */
function is_user_logged_in() {
	global $link;
	if ( $link ) {
		if ( ! empty ( $_COOKIE['user'] ) ) {
			list( $email, $password ) = explode( ';', mysqli_real_escape_string( $link, $_COOKIE['user'] ) );
			if ( ! empty( $email ) && ! empty( $password ) ) {
				$sql    = "SELECT COUNT(*) FROM user_reg WHERE email='{$email}' AND password='{$password}'";
				$result = do_query( $sql );
				$rows   = $result->fetch_row();

				if ( $rows[0] == 1 ) {
					return true;
				}
			}
		}

		return false;
	}
}

/**
 * Функция выхода из профиля пользователя
 *
 */
function user_logout() {
	setcookie( 'user', '', time() - 60 * 60 * 24 );
	header( "Location: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] );
	die();
}

/**
 * Функция подключения файлов
 *
 * @param $file
 */
function get_file( $file ) {
	include $file;
}