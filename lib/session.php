<?php
  class Session {
    public function __construct() {
      session_start();
    }

    public function destroy() {
      session_destroy();
    }

    public function setAttribute($key, $value) {
      $_SESSION[$key] = $value;
    }

    public function hasKey($key) {
      return (isset($_SESSION[$key]) && $_SESSION[$key] != "");
    }

    public function getKey($key) {
      if ($this->hasKey($key)) {
        return $_SESSION[$key];
      } else {
        return null;
      }
    }
  }