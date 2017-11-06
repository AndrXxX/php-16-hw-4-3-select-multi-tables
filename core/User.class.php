<?php
//require_once '../core/functions.php';

class User
{
    public $user;
    public $userID, $password;

    function __construct()
    {
        $db = getConnection();

        if (!empty($_SESSION['user_id']) &&
            $db->query("SELECT COUNT(*) FROM user WHERE id = '" . $_SESSION['user_id'] . "' LIMIT 1")->fetchColumn() == 1) {
            # Создаем массив с данными пользователя
            $this->user = $db->query("SELECT * FROM user WHERE id = '" . $_SESSION['user_id'] . "' LIMIT 1")->fetch();

            setcookie('user_id', $this->user['id'], time() + 60 * 60 * 24 * 365);
            //setcookie('password', getHash($this->user['password']), time() + 60 * 60 * 24 * 365);

            $this->userID = $this->user['id'];
        }
    }

    /**
     * Реализует механизм авторизации
     * @param $login
     * @param $password
     * @return bool
     */
    protected function login($login, $password)
    {
        $user = !empty($login) && !empty($password) ? $this->getUser($login) : null;
        /* Ищем пользователя по логину */
        if ($user !== null && $user['password'] === getHash($password)) {
            $_SESSION['user'] = $user;
            $this->user = $user;
            $_SESSION['user_id'] = $this->user['id']; // Создаем ID в сессиии
            return true;
        }
        return false;
    }

    /**
     * Ищет пользователя по логину
     * @param $login
     * @return mixed|null
     */
    protected function getUser($login)
    {
        $sql = "SELECT * FROM user WHERE login = ? LIMIT 1";
        $statement = getConnection()->prepare($sql);
        $statement->execute([$login]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?? null;
    }

    public function register($login, $password)
    {
        $_SESSION['loginErrors'] = [];
        if (!setUser($login, getHash($password))) {
            $_SESSION['loginErrors'][] = 'Регистрация не удалась: такой пользователь уже есть';
            return false;
        }
        return $this->checkForLogin($login, $password);
    }

    /**
     * Реализует механизм проверок при авторизации
     * @param $login
     * @param $password
     * @return bool
     */
    public function checkForLogin($login, $password)
    {
        $_SESSION['loginErrors'] = [];
        if (!$this->login($login, $password)) {
            $_SESSION['loginErrors'][] =
                'Авторизация не удалась: не найден пользователь, неправильный логин или неправильный пароль';
            return false;
        }
        return true;
    }

    /**
     * Возвращает текущего пользователя (если есть) или его параметр при наличии $param
     * @param null $param
     * @return null
     */
    function getCurrentUser($param = null)
    {
        if (isset($param)) {
            return $this->user[$param] ?? null;
        }
        return $this->user ?? null;
    }

    /**
     * Уничтожает сессию и переадресует на страницу входа
     */
    public function logout()
    {
        session_destroy();
        redirect('register');
    }

    /**
     * Возвращает список ошибок, произошедших во время входа
     * @return mixed
     */
    function getLoginErrors()
    {
        return $_SESSION['loginErrors'] ?? null;
    }
}