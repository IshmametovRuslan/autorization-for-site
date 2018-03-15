<?php
include 'config.php';

global $link;

if ( empty( $link ) ) {
	$link = mysqli_connect( HOST, LOGIN, PASSWORD, DATABASE );
}

/**
 * Функция осуществляющая запрос к БД
 *
 * @param $query
 *
 * @return bool|mysqli_result
 *
 */
function do_query( $query ) {
	global $link;
	mysqli_set_charset( $link, 'utf8' );
	$result = mysqli_query( $link, $query );

	return $result;
}

/**
 * Функция подключения файлов
 *
 * @param $file
 *
 */
function file_connection( $file ) {
	include $file;
}

/**
 * Функция получения url'а сайта
 *
 * @return string
 */
function get_root_url() {
	$protocol = stripos( $_SERVER['SERVER_PROTOCOL'], 'https' ) === true ? 'https://' : 'http://';

	$port = ! empty( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '';

	$url = $protocol . $_SERVER["SERVER_NAME"] . $port . dirname( $_SERVER["SCRIPT_NAME"] );

	// удаление последнего слэша в строке
	$url = preg_replace( '{/$}', '', $url );

	return $url;
}

/**
 * Функция определения запрошенной страницы
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
 * Функция регистрации пользователя
 */
function registration() {
	if ( ! empty( $_POST['email_login'] ) && ! empty( $_POST['password_login'] ) && ! empty( $_POST['first_name'] ) && ! empty( $_POST['last_name'] ) ) {
		$err = [];

		if ( strlen( $_POST['email_login'] ) < 7 or strlen( $_POST['email_login'] ) > 255 ) {
			$err[] = "Email не должен быть меньше 7 символов и не больше 255";
		}

		if ( ! preg_match( "/[0-9a-z_\.\-]+@[0-9a-z_\.\-]+\.[a-z]{2,4}/i", $_POST['email_login'] ) ) {
			$err[] = "Некорректный Email";
		}

		if ( strlen( $_POST['password_login'] ) < 6 or strlen( $_POST['password_login'] ) > 255 ) {
			$err[] = "Password не должен быть меньше 6 символов и не больше 255";
		}

		if ( count( $err ) == 0 ) {

			$first_name = $_POST['first_name'];

			$last_name = $_POST['last_name'];

			$email = $_POST['email_login'];

			$password = encript_password( $_POST['password_login'] );

			do_query( "INSERT INTO user_auth SET email='" . $email . "', password='" . $password . "', first_name='" . $first_name . "', last_name='" . $last_name . "'" );
			$query = do_query( "SELECT count(*) FROM users WHERE email='{$_POST['email_login']}'" );

			if ( mysqli_num_rows( $query ) > 0 ) {
				$err[] = "Пользователь с таким email существует";
			}
			header( "location:" . get_root_url());
		} else {
			echo "<strong>При регистрации произошли следующие ошибки:</strong><br>";
			foreach ( $err as $error ) {
				echo $error . "<br>";
			}
		}
	}
}


/**
 * Функция разлогинивания
 */
function logout() {
	if ( get_page() == 'logout' ) {
		user_logout();
	}
}

/**
 * Функция логаута
 *
 * @param $args
 */
function user_logout( $args = '' ) {
	setcookie( 'user', '', time() - 60 * 60 * 24 );
	if ( ! empty( $args ) && is_array( $args ) ) {
		$args = '?' . implode( '&', $args );
	}
	$url = get_root_url() . $args;

	header( "Location: " . $url );
	die();
}

/**
 * Функция авторизации пользователя
 *
 */
function autorization_user() {
	if ( isset( $_POST['email_login'] ) && isset( $_POST['password_login'] ) ) {

		$email    = $_POST['email_login'];
		$password = encript_password( $_POST['password_login'] );
		$sql      = "SELECT COUNT(*) FROM user_auth WHERE email='{$email}' AND password='{$password}'";
		$result   = do_query( $sql );
		$rows     = $result->fetch_row();

		if ( $rows[0] == 1 ) {
			setcookie( 'user', implode( ';', [ $email, $password ] ), time() + 60 * 60 * 24 );
			$url = get_root_url();
		} else {
			$url = '?p=error_login';
			user_logout( $url );
		}
		header( "Location: " . $url );
		die();
	}
}

function is_user_logged_in() {
	global $link;
	if ( $link ) {
		if ( ! empty( $_COOKIE['user'] ) ) {

			list( $email, $password ) = explode( ';', esc_sql( $_COOKIE['user'] ) );

			if ( ! empty( $email ) && ! empty( $password ) ) {
				$sql    = "SELECT COUNT(*) FROM users WHERE email='{$email}' AND password='{$password}'";
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
 * Функция шифрования пароля
 *
 * @param $password
 *
 * @return string
 */
function encript_password( $password ) {
	$password = md5( md5( trim( $password ) ) );

	return $password;
}