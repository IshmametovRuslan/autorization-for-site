<?php

include 'functions.php';

get_file('header.php');


if (is_user_logged_in() == true){
	get_file('close_page.php');
}else{
	get_file('authorization.php');
}