<?php
  error_reporting(0);
  define('CWD', realpath(dirname(__FILE__) . '/../'));
  date_default_timezone_set('Europe/Paris');
  setlocale(LC_ALL, 'fr_FR');

  $GLOBALS['folder'] = 'fr';
  $GLOBALS['facebook_id'] = null;

  require_once CWD . '/lib/settings.php';
  require_once CWD . '/lib/session.php';
  require_once CWD . '/lib/request.php';
  require_once CWD . '/lib/view.php';
  require_once CWD . '/lib/controller.php';
  require_once CWD . '/lib/router.php';
  require_once CWD . '/lib/model.php';
  require_once CWD . '/lib/uploaded_file.php';
  require_once CWD . '/app/helpers/application_helper.php';

  $connection = new mysqli(Settings::get('db_host'), Settings::get('db_user'), Settings::get('db_password'), Settings::get('db_name'), null, Settings::get('db_socket'));

  if ($connection->connect_error)
    die(sprintf('Unable to connect to the database. %s', $conn->connect_error));

  BaseModel::setConnection($connection);

  function __autoload($className) {
    $file = null;
    $model = CWD . '/app/models/' . strtolower($className) . '.php';

    if (file_exists($model)) {
      $file = $model;
    }
    $controller = CWD . '/app/controllers/' . strtolower($className) . '.php';
    if ($file == null && file_exists($controller)) {
      $file = $controller;
    }
    require_once $file;
  }
?>