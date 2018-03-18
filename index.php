<?php

error_reporting(E_ALL);

include 'functions.php';

get_file('header.php');

if (is_user_logged_in() == true){
	get_file('close_page.php');
	if (get_page() == 'user_logout'){
		user_logout();
	}
}else{
	if (get_page() == 'registration') {
		get_file('registration.php');
	}else {
		get_file('authorization.php');
	}
}