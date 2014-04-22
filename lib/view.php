<?php
  require_once 'settings.php';

  class View {
    private $file;
    private $title;
  
    public function __construct($action, $controller) {
      $file = CWD . "/app/views/";
      if ($controller != "") {
        $file = $file . $controller . "/";
      }
      $this->file = $file . $action . ".php";
    }

    public function render($data, $layout = null) {
      $content = $this->renderFile($this->file, $data);
      $root = Settings::get("root", "/");
      $layout = '/app/views/layouts/' . $layout;
      $view = $this->renderFile(CWD . $layout,
        array_merge($data, array('title' => $this->title, 'content' => $content, 'root' => $root)));
      echo $view;
    }

    private function renderFile($file, $data) {
      if (file_exists($file)) {
        extract($data);
        ob_start();
        require $file;
        return ob_get_clean();
      } else {
        throw new Exception("File '$fichier' not found");
      }
    }

    public function redirectUrlInFacebook() {
      $out = $this->pathAsAppData();
      $path = ($_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) == "/" ? null : "app_data=".json_encode($out);
      $url = "http://www.facebook.com/pages/" . Settings::get('page_name') . "/" . Settings::get('page_id') . "?sk=app_". Settings::get('app_id');
      if(!is_null($path)){
        $url = $url . "&" . $path;
      }
      return $url;
    }

    public function pathAsAppData() {
      list($path, $params) = explode("?", ($_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']));
      if(!is_null($params)){
        $array = array();
        $params = explode("&", $params);
        foreach ($params as &$value) {
          $arg = explode("=", &$value);
          $array[$arg[0]] = $arg[1];
        }
        $array['p'] = $path;
        return $array;
      }else{
        return array('p' => $path);
      }
    }
  }
?>