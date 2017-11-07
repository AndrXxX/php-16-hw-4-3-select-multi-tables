<?php
require_once 'functions.php';
session_start();
$homeWorkNum = '4.3';
$homeWorkCaption = 'SELECT из нескольких таблиц.';

const HOST = 'localhost';
const DB = 'global';
const USER = 'andrew';
const PASS = '2002';
const TASK_STATE_COMPLETE = 2;
const TASK_STATE_IN_PROGRESS = 1;

/**
 * Устанавливаем системные настройки
 */
ini_set("display_errors", "1"); // Показ ошибок
ini_set("display_startup_errors", "1");
ini_set('error_reporting', E_ALL);
mb_internal_encoding('UTF-8'); // Кодировка по умолчанию

/**
 * Загружаем классы
 */
spl_autoload_register(function($name) {
    $file = dirname(__DIR__) . '/core/' . $name . '.class.php';
    if (!file_exists($file)) {
        throw new Exception('Autoload class: File ' . $file . ' not found');
    }

    require $file;
});

/**
 * Создаем пользователя
 */
$user = new User;
