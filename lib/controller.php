<?php
  require_once 'settings.php';
  require_once 'request.php';
  require_once 'view.php';


  abstract class Controller {
    protected $request;

    function __construct($array) {
      $this->layout = "layout.php";
    }

    public function getParams() {
      return $this->request->getParams();
    }

    public function setRequest(Request $request) {
      $this->request = $request;
    }

    public function beforeAction() {
      return true;
    }

    public function call($action) {
      if (method_exists($this, $action)) {
        $this->action = $action;
        if ($this->beforeAction()) {
          call_user_func_array(array($this, $this->action), array());
        }
      } else {
        $controllerClass = get_class($this);
        throw new Exception("Unknown action '$action' form $controllerClass controller");
      }
    }

    protected function render($data = array(), $action = null) {
      $viewAction = $this->action;
      if ($action != null) {
        $viewAction = $action;
      }

      $controllerClass = get_class($this);
      $viewController = strtolower(str_replace("Controller", "", get_class($this)));

      $view = new View($viewAction, $viewController);
      $view->render($data, $this->layout);
    }

    protected function redirectTo($controller, $action = null) {
      $root = Settings::get("root", "/");
      header("Location:" . $root . $controller . "/" . $action);
    }

    protected function dateFormater($day, $month, $year, $hours, $minutes) {
      $pattern = $day . '-' . $month . '-' . $year . ' ' . $hours . ':' . $minutes;
      $d =  DateTime::createFromFormat('j-n-Y H:i', $pattern);
      if ($d->format('j-n-Y H:i') == $pattern) {
        return $d;
      } else {
        return null;
      }
    }

    protected function validates_basic_auth() {
      if (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] == Settings::get('auth_user')
          && isset($_SERVER['PHP_AUTH_PW']) && $_SERVER['PHP_AUTH_PW'] == Settings::get('auth_password')) {
        return true;
      } else {
        header('WWW-Authenticate: Basic realm="restricted area"');
        header('HTTP/1.0 401 Unauthorized');
        $view = new View('login_required', 'shared');
        $view->render(array(), 'admin.php');
      }
      return false;
    }
  }
?>