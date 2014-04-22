<?php
  // =================
  // = Sanitize HTML =
  // =================
  function html_safe($input) {
    return htmlentities(str_replace('\\', '', $input), ENT_QUOTES, 'UTF-8');
  }

  function safe_echo($input) {
    echo html_safe($input);
  }

  // ================================
  // = Protect from Forgery attacks =
  // ================================

  function include_csrf_inputs() {
    // Generate the tokens
    $now  = time();
    $csrf = sha1($_SERVER["REMOTE_ADDR"] . $_SERVER["HTTP_USER_AGENT"] . $_SERVER["REQUEST_URI"] . $now . Settings::get('csrf_key'));
    $key  = base64_encode($_SERVER["REQUEST_URI"]);
    $time = base64_encode($now);

    echo '<input type="hidden" name="CSRF" value="'      . $csrf . '">';
    echo '<input type="hidden" name="CSRF_KEY" value="'  . $key  . '">';
    echo '<input type="hidden" name="CSRF_TIME" value="' . $time . '">';
  }

  function protect_from_forgery() {
    if (empty($_POST)) return;

    // CSRF check
    $key  = base64_decode($_POST["CSRF_KEY"]);
    $time = base64_decode($_POST["CSRF_TIME"]);
    $csrf = sha1($_SERVER["REMOTE_ADDR"] . $_SERVER["HTTP_USER_AGENT"] . $key . $time . Settings::get('csrf_key'));
    // Compare token
    if ($_POST["CSRF"] != $csrf) {
      header("HTTP/1.0 403 Forbidden");
      die("Request forbidden!");
    }
    // Check expiry
    $now = time();
    if ($time < ($now - 60 * 10)) {
      header("HTTP/1.0 403 Forbidden");
      die("Request expired!");
    }
  }
?>