<?php

class User
{
    protected $user;

    function __construct()
    {
        if (!empty($_SESSION['user']['login'])) {
            $this->user = $this->getUser($_SESSION['user']['login']);
        }
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

    /**
     * Добавляет пользователя в БД (если пользователя с таким именем в базе нет)
     * @param $login
     * @param $password
     * @return bool
     */
    function setUser($login, $password)
    {
        if ($this->getUser($login)) {
            return false;
        }
        $sqlAdd = "INSERT INTO user (login, password) VALUES (?, ?)";
        $statement = getConnection()->prepare($sqlAdd);
        $statement->execute([$login, $password]);
        return true;
    }

    /**
     * Реализует механизм регистрации и последующей авторизации
     * @param $login
     * @param $password
     * @return bool
     */
    public function register($login, $password)
    {
        $_SESSION['loginErrors'] = [];
        if (!$this->setUser($login, $this->getHash($password))) {
            $_SESSION['loginErrors'][] = 'Регистрация не удалась: такой пользователь уже есть';
            return false;
        }
        return $this->checkForLogin($login, $password);
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
     * Реализует механизм авторизации
     * @param $login
     * @param $password
     * @return bool
     */
    protected function login($login, $password)
    {
        $user = !empty($login) && !empty($password) ? $this->getUser($login) : null;
        /* Ищем пользователя по логину */
        if ($user !== null && $user['password'] === $this->getHash($password)) {
            $_SESSION['user'] = $user;
            $this->user = $user;
            $_SESSION['user_id'] = $this->user['id']; // Создаем ID в сессиии
            return true;
        }
        return false;
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
    public function getLoginErrors()
    {
        return $_SESSION['loginErrors'] ?? null;
    }

    /**
     * Возвращает массив задач созданных пользователем $userName
     * @return array
     */
    public function getOwnerTasks()
    {
        $sort = $this->getSortType();
        $sql = "
        SELECT task.id, task.user_id, task.assigned_user_id, task.description, task.is_done, task.date_added, 
          owner_user.login AS owner_user_login, assigned_user.login AS assigned_user_login
        FROM task
        JOIN user AS owner_user ON owner_user.id=task.user_id
        JOIN user AS assigned_user ON assigned_user.id=task.assigned_user_id
        WHERE owner_user.login = ?
        ORDER BY $sort ASC;";
        $statement = getConnection()->prepare($sql);
        $statement->execute([$this->getUserName()]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Извлекает тип сортировки
     * @return string
     */
    public function getSortType()
    {
        return $_SESSION['sort'] ?? 'date_added';
    }

    /**
     * Возвращает имя пользователя (логин)
     * @return null
     */
    public function getUserName()
    {
        return $this->getCurrentUser('login');
    }

    /**
     * Возвращает текущего пользователя (если есть) или его параметр при наличии $param
     * @param null $param
     * @return null
     */
    public function getCurrentUser($param = null)
    {
        if (isset($param)) {
            return $this->user[$param] ?? null;
        }
        return $this->user ?? null;
    }

    /**
     * Задает тип сортировки
     * @param $sort
     */
    public function setSortType($sort)
    {
        $_SESSION['sort'] = in_array($sort, ['date_added', 'is_done', 'description']) ? $sort : 'date_added';
    }

    /**
     * Возвращает массив задач для пользователя $userName, которые были созданы другими пользователями
     * @return array
     */
    public function getOtherTasks()
    {
        $sort = $this->getSortType();
        $sql = "
        SELECT task.id, task.user_id, task.assigned_user_id, task.description, task.is_done, task.date_added, 
          owner_user.login AS owner_user_login, assigned_user.login AS assigned_user_login
        FROM task
        JOIN user AS owner_user ON owner_user.id=task.user_id
        JOIN user AS assigned_user ON assigned_user.id=task.assigned_user_id
        WHERE owner_user.login <> ? AND assigned_user.login = ?
        ORDER BY $sort ASC;";
        $statement = getConnection()->prepare($sql);
        $statement->execute([$this->getUserName(), $this->getUserName()]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Возвращает список пользователей из БД
     */
    public function getUserList()
    {
        $sql = "SELECT id, login FROM user ORDER BY login;";
        $statement = getConnection()->prepare($sql);
        $statement->execute([]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Создает / изменяет задачу по ID
     * @param $taskID
     * @param $action
     * @param null $taskDescription
     * @param null $assignedUserID
     */
    public function changeTask($taskID, $action, $taskDescription = null, $assignedUserID = null)
    {
        $pdoParameters = [];
        switch ($action) {
            case 'add':
                $userID = $this->getCurrentUser('id');
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
     * Возвращает название статуса задачи
     * @param $id
     * @return string
     */
    public function getStatusName($id)
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
    public function getStatusColor($id)
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
     * Возвращает строку вида user_1-task_10 для генерации названия вариантов селектора
     * @param $userID
     * @param $taskID
     * @return string
     */
    public function getNameOptionList($userID, $taskID)
    {
        return !empty($userID) && !empty($taskID) ? 'user_' . $userID . '-task_' . $taskID : '';
    }

    /**
     * Извлекает из БД описание задачи по $taskID
     * @param $taskID
     * @return string
     */
    public function getDescriptionForTask($taskID)
    {
        if (empty($taskID)) return '';
        $statement = getConnection()->prepare("SELECT description FROM task WHERE id = ?");
        $statement->execute([$taskID]);
        return $statement->fetch(PDO::FETCH_ASSOC)['description'];
    }
}