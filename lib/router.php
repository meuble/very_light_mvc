<?php

  require_once 'request.php';

  class Router {
    public function call() {
      try {
        $request = new Request(array_merge(array_merge($_GET, $_POST), $_FILES));
        $controller = $this->getController($request);
        $action = $this->getAction($request);
        $controller->call($action);
      } catch (Exception $e) {
        $this->handleError($e);
      }
    }

    private function getController(Request $request) {
      $controller = "";
      if ($request->hasParam('controller')) {
        $controller = $request->getParam('controller');
        $controller = strtolower($controller);
      }

      $controllerClass = ucfirst($controller) . "Controller";

      $controllerFile = "app/controllers/" . $controller . "_controller.php";
      if (file_exists($controllerFile)) {
        require_once($controllerFile);
        $controller = new $controllerClass();
        $controller->setRequest($request);
        return $controller;
      } else {
        throw new Exception("Unknown route for '$controllerClass' controller");
      }
    }

    private function getAction(Request $request) {
      $action = "index";
      $verb = $request->getVerb();
      if ($request->hasParam('_method')) {
        $verb = $request->getParam('_method');
      }

      if ($request->hasParam('id')) {
        if ($verb == 'GET') {
          $action = "show";
        } elseif ($verb == 'POST') {
          $action = "update";
        } elseif ($verb == "DELETE") {
          $action = "destroy";
        }
      } elseif ($verb == 'POST') {
        $action = "create";
      }

      if ($request->hasParam('action')) {
        $action = $request->getParam('action');
      }
      return $action;
    }

    private function handleError(Exception $exception) {
      $view = new View('error', 'shared');
      $view->render(array('errorMessage' => $exception->getMessage()));
    }

  }
?>