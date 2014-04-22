<?php
  require_once 'session.php';

  class Request {
    private $params;
    private $session;
    private $verb;
    private $userAgent;
    private $server;

    public function __construct($params) {
      $this->params = $params;
      $this->parseServerInfo();
      $this->session = new Session();
    }

    public function getSession() {
      return $this->session;
    }

    public function hasParam($key) {
      return (isset($this->params[$key]) && $this->params[$key] != "");
    }

    public function getParams() {
      return $this->params;
    }

    public function getParam($key) {
      if ($this->hasParam($key)) {
        return $this->params[$key];
      } else {
        return null;
      }
    }

    public function getVerb() {
      return $this->verb;
    }

    public function getUserAgent() {
      return $this->userAgent;
    }

    public function getServer() {
      return $this->server;
    }

    private function parseServerInfo() {
      $this->server = $_SERVER;
      $this->verb = $this->server['REQUEST_METHOD'];
      $this->userAgent = $this->server['HTTP_USER_AGENT'];
      $this->ssl = isset($this->server['HTTPS']) && $this->server['HTTPS'] != "";
    }
  }
?>