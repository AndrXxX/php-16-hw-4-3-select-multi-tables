<?php
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
 * Реализует механизм проверок при авторизации
 * @param $login
 * @param $password
 * @return bool
 */
function checkForLogin($login, $password)
{
    $_SESSION['loginErrors'] = [];
    if (!login($login, $password)) {
        $_SESSION['loginErrors'][] =
            'Авторизация не удалась: не найден пользователь, неправильный логин или неправильный пароль';
        return false;
    }
    return true;
}

function register($login, $password)
{
    $_SESSION['loginErrors'] = [];
    if (!setUser($login, getHash($password))) {
        $_SESSION['loginErrors'][] = 'Регистрация не удалась: такой пользователь уже есть';
        return false;
    }
    return checkForLogin($login, $password);
}

/**
 * Реализует механизм авторизации
 * @param $login
 * @param $password
 * @return bool
 */
function login($login, $password)
{
    $user = !empty($login) && !empty($password) ? getUser($login) : null;
    /* Ищем пользователя по логину */
    if ($user !== null && $user['password'] === getHash($password)) {
        $_SESSION['user'] = $user;
        return true;
    }
    return false;
}

/**
 * Возвращает список ошибок, произошедших во время входа
 * @return mixed
 */
function getLoginErrors()
{
    return $_SESSION['loginErrors'] ?? null;
}

/**
 * Возвращает подключение к БД
 * @return PDO
 */
function getConnection()
{
    $host = HOST;
    $db = DB;
    return new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
        USER,
        PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

/**
 * Ищет пользователя по логину
 * @param $login
 * @return mixed|null
 */
function getUser($login)
{
    $sql = "SELECT * FROM user WHERE login = ?";
    $statement = getConnection()->prepare($sql);
    $statement->execute([$login]);
    return $statement->fetch(PDO::FETCH_ASSOC) ?? null;
}

/**
 * Добавляет пользователя в БД (если пользователя с таким именем в базе нет)
 * @param $login
 * @param $password
 * @return bool
 */
function setUser($login, $password)
{
    if (getUser($login)) {
        return false;
    }
    $sqlAdd = "INSERT INTO user (login, password) VALUES (?, ?)";
    $statement = getConnection()->prepare($sqlAdd);
    $statement->execute([$login, $password]);
    return true;
}

/**
 * Проверяет, является ли метод ответа POST
 * @return bool
 */
function isPost()
{
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

/**
 * Проверяет установлен ли параметр $name в запросе
 * @param $name
 * @return null
 */
function getParam($name)
{
    return $_REQUEST[$name] ?? null;
}

/**
 * Возвращает текущего пользователя (если есть) или его параметр при наличии $param
 * @param null $param
 * @return null
 */
function getCurrentUser($param = null)
{
    if (isset($param)) {
        return $_SESSION['user'][$param] ?? null;
    }
    return $_SESSION['user'] ?? null;
}

/**
 * Отправляет переадресацию на указанную страницу
 * @param $action
 */
function redirect($action)
{
    header('Location: ' . $action . '.php');
    die;
}

/**
 * Уничтожает сессию и переадресует на страницу входа
 */
function logout()
{
    session_destroy();
    redirect('register');
}

/**
 * Возвращает хеш md5 от полученного параметра
 * @param $password
 * @return string
 */
function getHash($password)
{
    return md5($password);
}

/**
 * Возвращает содержимое $_REQUEST[$fieldName] или пустую строку
 * @param $fieldName
 * @return string
 */
function getValueFromRequest($fieldName)
{
    return $_REQUEST[$fieldName] ?? '';
}

/**
 * Возвращает название статуса задачи
 * @param $id
 * @return string
 */
function getStatusName($id)
{
    switch ($id) {
        case TASK_STATE_IN_PROGRESS:
            return 'В процессе';
            break;
        case TASK_STATE_COMPLETE:
            return 'Завершено';
            break;
        default:
            return '';
            break;
    }
}

/**
 * Возвращает цвет для выделения статуса задачи
 * @param $id
 * @return string
 */
function getStatusColor($id)
{
    switch ($id) {
        case TASK_STATE_IN_PROGRESS:
            return 'orange';
            break;
        case TASK_STATE_COMPLETE:
            return 'green';
            break;
        default:
            return 'red';
            break;
    }
}

/**
 * Возвращает список пользователей из БД
 */
function getUserList()
{
    $sql = "SELECT id, login FROM user ORDER BY login;";
    $statement = getConnection()->prepare($sql);
    $statement->execute([]);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Возвращает массив задач созданных пользователем $userName
 * @param $userName
 * @return array
 */
function getOwnerTasks($userName)
{
    $sort = getSortType();
    $sql = "
        SELECT task.id, task.user_id, task.assigned_user_id, task.description, task.is_done, task.date_added, 
          owner_user.login AS owner_user_login, assigned_user.login AS assigned_user_login
        FROM task
        JOIN user AS owner_user ON owner_user.id=task.user_id
        JOIN user AS assigned_user ON assigned_user.id=task.assigned_user_id
        WHERE owner_user.login = ?
        ORDER BY $sort ASC;";
    $statement = getConnection()->prepare($sql);
    $statement->execute([$userName]);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Возвращает массив задач для пользователя $userName, которые были созданы другими пользователями
 * @param $userName
 * @return array
 */
function getOtherTasks($userName)
{
    $sort = getSortType();
    $sql = "
        SELECT task.id, task.user_id, task.assigned_user_id, task.description, task.is_done, task.date_added, 
          owner_user.login AS owner_user_login, assigned_user.login AS assigned_user_login
        FROM task
        JOIN user AS owner_user ON owner_user.id=task.user_id
        JOIN user AS assigned_user ON assigned_user.id=task.assigned_user_id
        WHERE owner_user.login <> ? AND assigned_user.login = ?
        ORDER BY $sort ASC;";
    $statement = getConnection()->prepare($sql);
    $statement->execute([$userName, $userName]);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Задает тип сортировки
 * @param $sort
 */
function setSortType($sort)
{
    $_SESSION['sort'] = in_array($sort, ['date_added', 'is_done', 'description']) ? $sort : 'date_added';
}

/**
 * Извлекает тип сортировки
 * @return string
 */
function getSortType()
{
    return $_SESSION['sort'] ?? 'date_added';
}

/**
 * Создает / изменяет задачу по ID
 * @param $taskID
 * @param $action
 * @param null $taskDescription
 * @param null $assignedUser
 */
function changeTask($taskID, $action, $taskDescription = null, $assignedUserID = null)
{
    $pdoParameters = [];
    switch ($action) {
        case 'add':
            $userID = getCurrentUser('id');
            $sql = "INSERT INTO task (description, is_done, date_added, user_id, assigned_user_id) 
                    VALUES (?, ?,  NOW(), ?, ?);";
            $pdoParameters = [$taskDescription, TASK_STATE_IN_PROGRESS, $userID, $userID];
            break;
        case 'edit':
            if (!empty($taskDescription)) {
                $sql = "UPDATE task SET description = ? WHERE id = ?";
                $pdoParameters = [$taskDescription, $taskID];
            }
            break;
        case 'done':
            $sql = "UPDATE task SET is_done = ? WHERE id = ?";
            $pdoParameters = [TASK_STATE_COMPLETE, $taskID];
            break;
        case 'delete':
            $sql = "DELETE FROM task WHERE id = ?";
            $pdoParameters = [$taskID];
            break;
        case 'set_assigned_user':
            $sql = "UPDATE task SET assigned_user_id = ? WHERE id = ?";
            $pdoParameters = [$assignedUserID, $taskID];
            break;
    }

    if (!empty($sql)) {
        $statement = getConnection()->prepare($sql);
        $statement->execute($pdoParameters);

        if (!headers_sent()) {
            header('Location: index.php');
            exit;
        }
    }
}

/**
 * Возвращает строку вида user_1-task_10 для генерации названия вариантов селектора
 * @param $userID
 * @param $taskID
 * @return string
 */
function getNameOptionList($userID, $taskID)
{
    return !empty($userID) && !empty($taskID) ? 'user_' . $userID . '-task_' . $taskID : '';
}

/**
 * Извлекает из БД описание задачи по $taskID
 * @param $taskID
 * @return string
 */
function getDescriptionForTask($taskID)
{
    if (empty($taskID)) return '';
    $statement = getConnection()->prepare("SELECT description FROM task WHERE id = ?");
    $statement->execute([$taskID]);
    return $statement->fetch(PDO::FETCH_ASSOC)['description'];
}