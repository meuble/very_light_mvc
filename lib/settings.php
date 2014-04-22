<?php
  class Settings {
    private static $settings;
  
    public static function get($key, $defaultValue = null) {
      $value = null;
      $set = self::getSettings();
      if (isset($set[$key])) {
        $value = $set[$key];
      } else {
        $value = $defaultValue;
      }
      return $value;
    }

    private static function getSettings() {
      if (self::$settings == null) {
        $configPath = CWD . "/config/development.ini";
        if (!file_exists($configPath)) {
          $configPath = CWD . "/config/production.ini";
        }
        if (!file_exists($configPath)) {
          throw new Exception("No setting files found");
        } else {
          self::$settings = parse_ini_file($configPath);
        }
      }
      return self::$settings;
    }
  }
?>