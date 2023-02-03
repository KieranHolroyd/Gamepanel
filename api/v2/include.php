<?php
session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . "/db.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/User.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/Permissions.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/Helpers.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/Config.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/DiffViewer.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/Parsedown.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/Interviews.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/classes/Meetings.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php";

foreach (scandir('./controller') as $dir) {
	if ($dir != '.' && $dir != '..')
		require_once './controller/' . $dir;
}
