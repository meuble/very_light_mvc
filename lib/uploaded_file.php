<?php
  class UploadedFile {
    private $file;
    private $name;

    public function __construct($fileParams) {
      if (is_array($fileParams)) {
        $this->file = $fileParams;
      } else {
        $this->name = $fileParams;
      }
    }

    public function validate() {
      if ($this->file) {
        if (!$this->error()) {
          if ($this->file['size'] > (1024000)) {
            return "File is too big";
          }
        } else {
          return $this->error();
        }
      }
      return true;
    }

    public function save() {
      move_uploaded_file($this->file['tmp_name'], CWD . "/public/uploads/" . $this->name());
      return true;
    }

    public function error() {
      return isset($this->$file) ? $this->$file['error'] : null;
    }

    public function name() {
      if (!isset($this->name)) {
        if ($this->file['tmp_name'] != "" && $this->file['name'] != "") {
          $extension = end(explode(".", strtolower($this->file['name'])));
          $this->name = basename($this->file['tmp_name']) . '.' . $extension;
        }
      }
      return $this->name;
    }
  }
?>