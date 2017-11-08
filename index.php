<?php
require_once 'core/core.php';

if (!$user->getCurrentUser()) {
    /* если пользователь не залогинен - отправляем на страницу register */
    redirect('register');
}

/**
 * Действия при нажатии Добавить.
 */
if (!empty(getParam('description')) && empty(getParam('action'))) {
    $user->changeTask(0, 'add', getParam('description'));
}

/**
 * Устанавливаем тип сортировки задач
 */
if (!empty(getParam('sort_by'))) {
    $user->setSortType(getParam('sort_by'));
}

/**
 * Действия, если была нажата одна из ссылок - Изменить, Выполнить или Удалить
 */
if (!empty(getParam('id')) && !empty(getParam('action'))) {
    $user->changeTask(
        (int)getParam('id'),
        getParam('action'),
        getParam('description')
    );
}

/**
 * Действия при нажатии Переложить ответственность
 */
if (!empty(getParam('assigned_user_id'))) {
    /* формат assigned_user_id - user_x-task_y */
    $str = explode('-', getParam('assigned_user_id'));
    $assigned_user_id = (int)str_replace('user_', '', $str[0]);
    $taskID = (int)str_replace('task_', '', $str[1]);
    $user->changeTask($taskID, 'set_assigned_user', null, $assigned_user_id);
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
        <p class="greet">Здравствуйте, <?= $user->getUserName() ?>!</p>
        <a class="logout" href="./logout.php">Выход</a>
      </div>
    </header>
    <h1>Список дел на сегодня</h1>
    <div class="form-container">

      <form class="form" method="POST">
        <input type="text" name="description" placeholder="Описание задачи"
               value="<?= getParam('action') === 'edit' ?
                   $user->getDescriptionForTask((int)getParam('id')) : '' ?>"/>
        <input type="submit" name="save"
               value="<?= getParam('action') === 'edit' ? 'Сохранить' : 'Добавить' ?>"/>
      </form>

      <form class="form" method="POST">
        <label>Сортировать по:
          <select name="sort_by">
            <option <?= $user->getSortType() === 'date_created' ? 'selected' : '' ?> value="date_created">Дате
              добавления
            </option>
            <option <?= $user->getSortType() === 'is_done' ? 'selected' : '' ?> value="is_done">Статусу</option>
            <option <?= $user->getSortType() === 'description' ? 'selected' : '' ?> value="description">Описанию
            </option>
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

        <?php foreach ($user->getOwnerTasks() as $task) : ?>
        <tr>
          <td><?= htmlspecialchars($task['description']) ?></td>
          <td><?= $task['date_added'] ?></td>
          <td>
            <span
              style='color: <?= $user->getStatusColor($task['is_done']) ?>;'><?= $user->getStatusName($task['is_done']) ?></span>
          </td>
          <td>
            <a href='?id=<?= $task['id'] ?>&action=edit'>Изменить</a>

            <?php if ($task['assigned_user_login'] === $user->getUserName()) : ?>
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
                  <?php foreach ($user->getUserList() as $currentUser) : ?>
                    <option <?= $currentUser['login'] === $task['assigned_user_login'] ? 'selected' : '' ?>
                      value="<?=$user->getNameOptionList($currentUser['id'], $task['id']) ?>"><?= $currentUser['login'] ?>
                    </option>
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

        <?php foreach ($user->getOtherTasks() as $task) : ?>
        <tr>
          <td><?= htmlspecialchars($task['description']) ?></td>
          <td><?= $task['date_added'] ?></td>
          <td>
            <span style='color: <?= $user->getStatusColor($task['is_done']) ?>;'>
                <?= $user->getStatusName($task['is_done']) ?>
            </span>
          </td>
          <td>
            <a href='?id=<?= $task['id'] ?>&action=edit'>Изменить</a>

            <?php if ($task['assigned_user_login'] === $user->getUserName()): ?>
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
