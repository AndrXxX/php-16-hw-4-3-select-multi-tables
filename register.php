<?php
require_once 'core/functions.php';

if (isPost()) {
    if ((getParam('sign_in') && checkForLogin(getParam('login'), getParam('password'))) OR
        (getParam('register') && register(getParam('login'), getParam('password')))) {
            redirect('index');
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
  <head>
    <title>Домашнее задание по теме <?= $homeWorkNum ?> <?= $homeWorkCaption ?></title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
  </head>
  <body>
    <header>
      <div class="container">
        <p>Введите данные для регистрации или войдите, если уже регистрировались:</p>
      </div>
    </header>

    <section>
      <div class="container">

        <h1>Авторизация</h1>

        <?php
            if (!empty(getLoginErrors())):
                foreach (getLoginErrors() as $error):
        ?>

        <p><?= $error ?></p>

        <?php
                endforeach;
            endif;
        ?>

        <form class="form" method="POST" id="login-form">
          <div class="form-group">
            <label>Логин
              <input class="form-control" type="text" name="login">
            </label>
          </div>
          <div class="form-group">
            <label>Пароль
              <input class="form-control" type="password" name="password">
            </label>
          </div>

          <input type="submit" class="btn btn-prime" name="sign_in" value="Вход">
          <input type="submit" class="btn" name="register" value="Регистрация"/>
        </form>

      </div>
    </section>
  </body>
</html>