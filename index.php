<?php
require_once 'core/functions.php';

$currentUser = getCurrentUser();
if (!$currentUser) {
    /* если пользователь не залогинен - отправляем на страницу register */
    redirect('register');
}

/**
 * Действия при нажатии Добавить.
 */
if (!empty(getValueFromRequest('description')) && empty(getValueFromRequest('action'))) {
    changeTask(0, 'add', getValueFromRequest('description'));
}

/**
 * Устанавливаем тип сортировки задач
 */
if (!empty(getValueFromRequest('sort_by'))) {
    setSortType(getValueFromRequest('sort_by'));
}

/**
 * Действия, если была нажата одна из ссылок - Изменить, Выполнить или Удалить
 */
if (!empty(getValueFromRequest('id')) && !empty(getValueFromRequest('action'))) {
    changeTask(
        (int)getValueFromRequest('id'),
        getValueFromRequest('action'),
        getValueFromRequest('description')
    );
}

/**
 * Действия при нажатии Переложить ответственность
 */
if (!empty(getValueFromRequest('assigned_user_id'))) {
    /* формат assigned_user_id - user_x-task_y */
    $str = explode('-', getValueFromRequest('assigned_user_id'));
    $assigned_user_id = (int)str_replace('user_', '', $str[0]);
    $taskID = (int)str_replace('task_', '', $str[1]);
    changeTask($taskID, 'set_assigned_user', null, $assigned_user_id);
}

?>

<!DOCTYPE html>
<html lang="ru">
  <head>
    <title>Домашнее задание по теме <?= $homeWorkNum ?> <?= $homeWorkCaption ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="./css/styles.css">
  </head>
  <body>
    <header>
      <div class="container">
        <p class="greet">Здравствуйте, <?= $currentUser['login'] ?>!</p>
        <a class="logout" href="./logout.php">Выход</a>
      </div>
    </header>
    <h1>Список дел на сегодня</h1>
    <div class="form-container">

      <form class="form" method="POST">
        <input type="text" name="description" placeholder="Описание задачи"
               value="<?= getValueFromRequest('action') === 'edit' ?
                   getDescriptionForTask((int)getValueFromRequest('id')) : '' ?>"/>
        <input type="submit" name="save"
               value="<?= getValueFromRequest('action') === 'edit' ? 'Сохранить' : 'Добавить' ?>"/>
      </form>

      <form class="form" method="POST">
        <label>Сортировать по:
          <select name="sort_by">
            <option <?= getSortType() === 'date_created' ? 'selected' : '' ?> value="date_created">Дате добавления
            </option>
            <option <?= getSortType() === 'is_done' ? 'selected' : '' ?> value="is_done">Статусу</option>
            <option <?= getSortType() === 'description' ? 'selected' : '' ?> value="description">Описанию</option>
          </select>
        </label>
        <input type="submit" name="sort" value="Отсортировать"/>
      </form>

      <table>
        <tr>
          <th>Описание задачи</th>
          <th>Дата добавления</th>
          <th>Статус</th>
          <th>Управление задачей</th>
          <th>Ответственный</th>
          <th>Автор</th>
          <th>Закрепить задачу за пользователем</th>
        </tr>

        <?php foreach (getOwnerTasks($currentUser['login']) as $task) : ?>
        <tr>
          <td><?= htmlspecialchars($task['description']) ?></td>
          <td><?= $task['date_added'] ?></td>
          <td>
            <span style='color: <?= getStatusColor($task['is_done']) ?>;'><?= getStatusName($task['is_done']) ?></span>
          </td>
          <td>
            <a href='?id=<?= $task['id'] ?>&action=edit'>Изменить</a>

            <?php if ($task['assigned_user_login'] === $currentUser['login']): ?>
              <a href='?id=<?= $task['id'] ?>&action=done'>Выполнить</a>
            <?php endif; ?>

            <a href='?id=<?= $task['id'] ?>&action=delete'>Удалить</a>
          </td>
          <td><?= $task['assigned_user_login'] ?></td>
          <td><?= $task['owner_user_login'] ?></td>
          <td>
            <form method='POST'>
              <label title="Выберите пользователя из списка">
                <select name='assigned_user_id'>
                  <?php foreach (getUserList() as $user) : ?>
                    <option <?= $user['login'] === $task['assigned_user_login'] ? 'selected' : '' ?> value="<?=
                    getNameOptionList($user['id'], $task['id']) ?>"><?= $user['login'] ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <input type='submit' name='assign' value='Переложить ответственность'/>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>

      </table>

      <p><strong>Также, посмотрите, что от Вас требуют другие люди:</strong></p>

      <table>
        <tr>
          <th>Описание задачи</th>
          <th>Дата добавления</th>
          <th>Статус</th>
          <th>Управление задачей</th>
          <th>Ответственный</th>
          <th>Автор</th>
        </tr>

        <?php foreach (getOtherTasks($currentUser['login']) as $task) : ?>
        <tr>
          <td><?= htmlspecialchars($task['description']) ?></td>
          <td><?= $task['date_added'] ?></td>
          <td>
            <span style='color: <?= getStatusColor($task['is_done']) ?>;'><?= getStatusName($task['is_done']) ?></span>
          </td>
          <td>
            <a href='?id=<?= $task['id'] ?>&action=edit'>Изменить</a>

            <?php if ($task['assigned_user_login'] === $currentUser['login']): ?>
              <a href='?id=<?= $task['id'] ?>&action=done'>Выполнить</a>
            <?php endif; ?>

            <a href='?id=<?= $task['id'] ?>&action=delete'>Удалить</a>
          </td>
          <td><?= $task['assigned_user_login'] ?></td>
          <td><?= $task['owner_user_login'] ?></td>
        </tr>
        <?php endforeach; ?>

        <table>

    </div>
  </body>
</html>
