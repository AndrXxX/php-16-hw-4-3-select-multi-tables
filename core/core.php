<?php
require_once 'functions.php';
session_start();
$homeWorkNum = '4.3';
$homeWorkCaption = 'SELECT из нескольких таблиц.';

const HOST = 'localhost';
/*const DB = 'id3521811_global';
const USER = 'id3521811_andrew';
const PASS = '20xxx02';*/
const DB = 'global';
const USER = 'andrew';
const PASS = '2002';
const TASK_STATE_COMPLETE = 2;
const TASK_STATE_IN_PROGRESS = 1;

/*session_name('sid');
session_start();*/
/*-----------*/
/**
 * Устанавливаем системные настройки
 */
ini_set("display_errors","1"); // Показ ошибок
ini_set("display_startup_errors","1");
ini_set('error_reporting', E_ALL);
mb_internal_encoding('UTF-8'); // Кодировка по умолчанию

/**
 * Системные переменные и константы
 */
define('HOME', dirname(__DIR__)); // Серверный путь к сайту
define('URL', 'http://' . $_SERVER['HTTP_HOST']); // URL-путь к сайту
define('SkyBlog_VERSION', '1.0.0'); // Версия движка (!НЕ МЕНЯТЬ!)

/**
 * Инициализация PDO, подключение к БД
 */
$db = new PDO('mysql:host=' . HOST .';dbname=' . DB, USER, PASS, array(
    PDO::ATTR_PERSISTENT => true
)) or die('Cannot connect to MySQL server :(');
$db->query("SET NAMES utf8");


/**
 * Загружаем классы
 */
spl_autoload_register(function($name) {
    $file = dirname(__DIR__) . '/core/' . $name . '.class.php';
    if(!file_exists($file)) {
        throw new Exception('Autoload class: File '.$file.' not found');
    }

    require $file;
});

$user = new User;
$userID = $user->userID;

/*-----------*/
