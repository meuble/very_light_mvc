<?php
  class BaseModel {
    protected static $connection;

    protected $data = array();
    protected $errors = array();

    function __construct($array) {
      $this->data = $array;
      $id = isset($array['id']) ? $array['id'] : null;
      $this->setAttribute('id', $id);

      foreach ($this::$attributes_declaration as $attribute => $declaration) {
        $attribute_value = isset($array[$attribute]) ? $array[$attribute] : null;
        $this->setAttribute($attribute, $attribute_value);
      }
    }

    public function getAttribute($attribute) {
      return $this->data[$attribute];
    }

    public function getAttributes() {
      return $this->data;
    }

    public function setAttribute($attribute, $value) {
      if (get_class($value) != 'UploadedFile'
        && $this::$attributes_declaration[$attribute]
        && $this::$attributes_declaration[$attribute]['type'] == 'file') {
        $value = new UploadedFile($value);
      }
      if (get_class($value) != 'DateTime'
        && $this::$attributes_declaration[$attribute]
        && $this::$attributes_declaration[$attribute]['type'] == 'datetime') {
        $value = new DateTime($value);
      }
      $this->data[$attribute] = $value;
      $this->$attribute = $value;
      return $this;
    }

    public static function filterAttributes($params) {
      $filteredAttributes = array();
      $className = get_called_class();
      foreach ($className::$attributes_declaration as $attribute => $declaration) {
        if ($className::$attributes_declaration[$attribute]
          && $className::$attributes_declaration[$attribute]['type'] == 'file') {
          if ($params[$attribute] && $params[$attribute]['error'] == UPLOAD_ERR_OK) {
            $filteredAttributes[$attribute] = new UploadedFile($params[$attribute]);
          }
        } elseif ($className::$attributes_declaration[$attribute]
          && $className::$attributes_declaration[$attribute]['type'] == 'datetime'
          && $params[$attribute . '_day'] && $params[$attribute . '_month'] && $params[$attribute . '_year']) {
          $pattern = $params[$attribute . '_day'] . '-' . $params[$attribute . '_month'] . '-' . $params[$attribute . '_year'] . " " . $params[$attribute . '_hours'] . ':' . $params[$attribute . '_minutes'];
          $d = DateTime::createFromFormat('j-n-Y H:i', $pattern);
          $filteredAttributes[$attribute] = ($d->format('j-n-Y H:i') == $pattern) ? $d : null;
        } else {
          $filteredAttributes[$attribute] = $params[$attribute];
        }

      }
      return $filteredAttributes;
    }

    public static function for_csv($instances) {
      $data = array();
      $first = $instances[0];
      if ($first != NULL) {
        $className = get_called_class();
        $first = new $className();
      }
      $data[] = array_keys($instances[0]->getAttributes());

      foreach ($instances as $u) {
        $values = array_values($u->getAttributes());
        foreach ($values as $i => $v) {
          if (get_class($v) == "DateTime") {
            $v = $v->format('Y-m-d H:i:s');
          }
          if (get_class($v) == "UploadedFile") {
            $v = $v->name();
          }
          $values[$i] = $v;
        }
        $data[] = $values;
      }
      return $data;
    }

    public static function setConnection($connection) {
      self::$connection = $connection;
    }

    public static function getConnection() {
      return self::$connection;
    }

    public static function getTableName() {
      $className = get_called_class();
      if (isset($className::$tableName))
        return $className::$tableName;
      return strtolower($className) . 's';
    }

    private static function load ($data) {
      $className = get_called_class();
      $instance = new $className();
      foreach ($data AS $key => $value) {
        $instance->setAttribute($key, $value);
      }
      return $instance;
    }

    public function is_new_record() {
      $id = $this->getAttribute('id');
      return strlen($id) == 0;
      unset($id);
    }

    // ===========
    // = Queries =
    // ===========
    public static function query($sql) {
      $tuples = self::getConnection()->query($sql);
      if (!$tuples) {
        throw new \Exception(sprintf('Unable to execute SQL statement. %s => '.$sql, self::getConnection()->error));
      }

      $rows = array();
      while ($row = $tuples->fetch_assoc()) {
        $rows[] = $row;
      }

      $tuples->close();

      return $rows;
    }

    public static function count($conditions = array()) {
      $sql = "SELECT count(id) as count FROM " . static::$table_name;
      if (isset($conditions['where']))
        $sql .= ' WHERE ' . $conditions['where'];
      if (isset($options['group']))
        $query .= ' GROUP BY ' . $conditions['group'];

      $count = self::query($sql);
      return $count[0] ? $count[0]["count"] : 0;
    }

    public static function find($id, $conditions = array()) {
      if (isset($conditions['where'])) {
        $conditions['where'] = 'id=' . self::getConnection()->real_escape_string($id) . ' AND ' . $conditions['where'];
      } else {
        $conditions['where'] = 'id=' . self::getConnection()->real_escape_string($id);
      }
      return static::first($conditions);
    }

    public static function first($conditions = array()) {
      $conditions['limit'] = 1;
      $row = self::query(static::build_select_query($conditions));
      return isset($row) ? new static($row[0]) : null;
    }

    public static function findAll($conditions = array()) {
      $rows = self::query(static::build_select_query($conditions));

      foreach ($rows as &$row) {
        $row = new static($row);
      }

      return $rows;
    }

    protected static function build_select_query($options = array()) {
      $fields = isset($options['select']) ? $options['select'] : '*';
      $query = 'SELECT ' . $fields . ' FROM ' . static::$table_name;

      if (isset($options['where']))
        $query .= ' WHERE ' . $options['where'];
      if (isset($options['order']))
        $query .= ' ORDER BY ' . $options['order'];
      if (isset($options['limit']))
        $query .= ' LIMIT ' . $options['limit'];
      if (isset($options['group']))
        $query .= ' GROUP BY ' . $options['group'];

      return $query;
    }

    // ========
    // = I18n =
    // ========
    public function field_i18n($field) {
      return $this::$attributes_declaration[$field]['i18n'];
    }

    // =============
    // = Callbacks =
    // =============
    private function execute_before_validation_callbacks() {
      if (method_exists($this, 'before_validate')) {
        $this->before_validate();
      }
    }

    private function execute_before_create_callbacks() {
      if (method_exists($this, 'before_create')) {
        $this->before_create();
      }
    }

    private function execute_before_save_callbacks() {
      if (method_exists($this, 'before_save')) {
        $this->before_save();
      }
    }

    private function execute_after_create_callbacks() {
      if (method_exists($this, 'after_create')) {
        $this->after_create();
      }
    }

    private function execute_after_save_callbacks() {
      if (method_exists($this, 'after_save')) {
        $this->after_save();
      }
    }

    // ===============================
    // = Update, save, record errors =
    // ===============================
    public function update_attributes($new_values) {
      foreach ($this::$attributes_declaration as $attribute => $declaration) {
        if (isset($new_values[$attribute])) {
          $this->setAttribute($attribute, $new_values[$attribute]);
        }
      }
    }

    public function save() {
      $this->execute_before_validation_callbacks();
      // Did not validate
      if (!$this->validate()) {
        return false;
      }
      // Is new record
      elseif ($this->is_new_record()) {
        
        $this->execute_before_create_callbacks();
        $this->execute_before_save_callbacks();
        $success = $this->create_record();
        $this->execute_after_create_callbacks();
        $this->execute_after_save_callbacks();
        return $success;
      }
      // Is persisted record
      else {
        $this->execute_before_save_callbacks();
        $success = $this->update_record();
        $this->execute_after_save_callbacks();
        return $success;
      }
    }

    public function is_valid() {
      return sizeof($this->errors) == 0;
    }

    public function errors_flash_list() {
      if (sizeof($this->errors) == 0)
        return '';

      $list = '<ul class="errors_list">';
      foreach ($this->errors as $field => $errors) {
        foreach ($errors as $error) {
          $list .= '<li>'. $this->field_i18n($field) .' '. $error .'</li>';
        }
      }
      $list .= '</ul>';
      return $list;
    }

    public function error_for($field) {
      if (isset($this->errors[$field]))
        return true;
      return false;
    }

    private function error_message_for($field, $error_type, $expected_value = null) {
      $attribute_declaration = $this::$attributes_declaration[$field];
      if (isset($attribute_declaration['validates_i18n']) && isset($attribute_declaration['validates_i18n'][$error_type])) {
        return $attribute_declaration['validates_i18n'][$error_type];
      } else {
        switch ($error_type) {
          case 'presence':
            return 'doit être rempli(e)';
            break;
          case 'uniqueness':
            return 'est déjà pris(e)';
            break;
          case 'format':
            return 'n\'est pas valide';
            break;
          case 'inclusion':
            return 'n\'est pas une valeur autorisée';
            break;
          case 'greater_than':
            return 'doit être supérieur(e) à '. $expected_value;
            break;
          case 'acceptance':
            return 'doit être accepté(e)';
            break;
        }
      }

      unset($attribute_declaration);
    }

    protected function add_error($field, $error_type, $expected_value = null) {
      if (!isset($this->errors[$field]))
        $this->errors[$field] = array();

      $message = $this->error_message_for($field, $error_type, $expected_value);
      array_push($this->errors[$field], $message);
      unset($message);
    }

    private function validate() {
      foreach ($this::$attributes_declaration as $attribute => $declaration) {
        if (!isset($declaration['validates'])) continue;

        foreach ($declaration['validates'] as $validation => $rule) {
          $function_name = 'validate_'.$validation.'_of';
          $this->$function_name($attribute, $rule);
        }
      }

      if (sizeof($this->errors) == 0)
        return true;
      else
        return false;
    }

    private function validate_file_size_of($field, $rule) {
      $value = $this->getAttribute($field);
      if (!$value->validate()) {
        $this->add_error($field, 'file_size');
      }
    }

    private function validate_presence_of($field, $rule) {
      $value = $this->getAttribute($field);
      if (get_class($value) == "DateTime") {
        $value = $value->format('Y-m-d H:i:s');
      }
      if (strlen($value) < 1)
        $this->add_error($field, 'presence');
    }

    private function validate_uniqueness_of($field, $rule) {
      if (sizeof($this->getAttribute($field)) < 1)
        return;

      if ($this->is_new_record()) {
        $count = self::count(array('where' => $field.'='.self::getConnection()->real_escape_string($this->getAttribute($field)).''));
      } else {
        $count = self::count(array('where' => 'id<>? AND '.$field.'=?'));
      }

      if ($count > 0) $this->add_error($field, 'uniqueness');
    }

    private function validate_inclusion_of($field, $authorized_values) {
      $current_value = $this->getAttribute($field);

      if (strlen($current_value) == 0)
        return;

      foreach ($authorized_values as $value) {
        if ($current_value == $value)
          return;
      }
      $this->add_error($field, 'inclusion');

      unset($current_value);
    }

    private function validate_greater_than_of($field, $min_value) {
      $current_value = $this->getAttribute($field);
      if (intval($current_value) <= $min_value)
        $this->add_error($field, 'greater_than', $min_value);
      unset($current_value);
    }

    private function validate_format_of($field, $regexp) {
      $current_value = $this->getAttribute($field);
      if (strlen($current_value) > 0 && preg_match($regexp, $current_value) != 1)
        $this->add_error($field, 'format');
      unset($current_value);
    }

    private function validate_acceptance_of($field, $rule) {
      if ($this->getAttribute($field) != '1')
        $this->add_error($field, 'acceptance');
    }

    // =========================
    // = Create/update helpers =
    // =========================
    private function map_field_type($field_definition) {
      switch ($field_definition['type']) {
        case 'integer':
          return 'i';
        case 'boolean':
          return 'i';
        case 'string':
          return 's';
        case 'text':
          return 's';
        case 'datetime':
          return 's';
        case 'file':
          return 's';
      }
    }

    protected function map_field_value($value) {
      if (get_class($value) == "DateTime") {
        return $value->format('Y-m-d H:i:s');
      }
      if (get_class($value) == "UploadedFile") {
        return $value->name();
      }
      return $value;
    }

    private function map_field_placeholder($field_name) {
      return '?';
    }

    private function create_record() {
      $this->setAttribute('created_at', date("Y-m-d H:i:s"));
      $bindings_params = $fields_names = $values = $fields_placeholders = $fields_types = array();
      foreach ($this::$attributes_declaration as $attribute => $declaration) {
        $value = $this->getAttribute($attribute);
        if (!$value == null) {
          $fields_placeholders[] = "?";
          $values[] = $this->map_field_value($value);
          $fields_types[] = $this->map_field_type($declaration);
          $fields_names[] = $attribute;
        }
      }

      foreach($values as $key => $val) {
        $bindings_params[$key] = &$values[$key];
      }
      array_unshift($bindings_params, join('', $fields_types));
      $stmt = self::getConnection()->prepare('INSERT INTO ' . $this::$table_name . '(' . join(',', $fields_names) . ') VALUES(' . join(',', $fields_placeholders) . ')');
      call_user_func_array(array($stmt, 'bind_param'), $bindings_params);
      $stmt->execute();
      if ($stmt->affected_rows < 0) {
        throw new \Exception(sprintf('Something went wrong with this SQL statement. %s', $stmt->error));
      }

      if (intval($stmt->insert_id) > 0) {
        $this->setAttribute('id', $stmt->insert_id);
        return true;
      } else {
        return false;
      }
    }

    private function update_record() {
      $bindings_params = $fields_names = $values = $fields_placeholders = $fields_types = array();
      foreach ($this::$attributes_declaration as $attribute => $declaration) {
        $value = $this->getAttribute($attribute);
        if (!$value == null) {
          $fields_placeholders[] = "?";
          $values[] = $this->map_field_value($value);
          $fields_types[] = $this->map_field_type($declaration);
          $fields_names[] = $attribute . '=?';;
        }
      }

      array_push($values, intval($this->id));
      array_push($fields_types, 'i');
      foreach($values as $key => $val) {
        $bindings_params[$key] = &$values[$key];
      }

      array_unshift($bindings_params, join('', $fields_types));

      $stmt = self::getConnection()->prepare('UPDATE ' . $this::$table_name . ' SET ' . join(',', $fields_names) . ' WHERE id=?;');
      if ($stmt === false) {
        throw new \Exception(sprintf('Something went wrong with this SQL statement. %s', self::getConnection()->error));
      }

      call_user_func_array(array($stmt, 'bind_param'), $bindings_params);
      $stmt->execute();
      if ($stmt->affected_rows < 0) {
        throw new \Exception(sprintf('Something went wrong with this SQL statement. %s', $stmt->error));
      }

      if (intval($stmt->insert_id) > 0) {
        $this->setAttribute('id', $stmt->insert_id);
        return true;
      } else {
        return false;
      }
    }

    // ===========
    // = Destroy =
    // ===========
    public function destroy() {
      $stmt = self::getConnection()->prepare('DELETE from ' . $this::$table_name . ' WHERE id=?;');
      if ($stmt === false) {
        throw new \Exception(sprintf('Something went wrong with this SQL statement. %s', self::getConnection()->error));
      }

      $values = array('i', $this->id);
      $bindings_params = array();
      foreach($values as $key => $val) {
        $bindings_params[$key] = &$values[$key];
      }
      call_user_func_array(array($stmt, 'bind_param'), $bindings_params);
      $stmt->execute();

      if ($stmt->affected_rows < 0) {
        throw new \Exception(sprintf('Something went wrong with this SQL statement. %s', $stmt->error));
      }

      if ($stmt->affected_rows == 1)
        return true;
      else
        return false;
    }

  }
?>