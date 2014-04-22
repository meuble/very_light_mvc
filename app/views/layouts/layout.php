<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <link href="/public/stylesheets/normalize.css" rel="stylesheet" type="text/css">
  <link href="/public/stylesheets/main.css" rel="stylesheet" type="text/css">
  <link href="/public/stylesheets/app.css" rel="stylesheet" type="text/css">
  <script type="text/javascript" src="/public/javascripts/app.js"></script>
  <script type="text/javascript" src="/public/javascripts/placeholder.js"></script>
</head>
<body>
  <script type="text/javascript" charset="utf-8">
  AppSettings = {
    appId: "<?php echo Settings::get('app_id') ?>",
    permissions: "<?php echo Settings::get('permissions') ?>",
    channelUrl: "",
    postLoginUrl: "",
    namespace: "<?php echo Settings::get('namespace') ?>",
    pageUrl: "http://facebook.com/" + "<?php echo Settings::get('page_name') ?>",
    pageId: "<?php echo Settings::get('page_id') ?>",
    rootUrl: "<?php echo Settings::get('root_url') ?>",
  };
  </script>

  <script type="text/javascript" charset="utf-8">
    if (window.top == window && !isMobileDevice()) {
      window.top.location = '<?php echo($this->redirectUrlInFacebook()); ?>';
    }
  </script>
  
  <div id="fb-root"></div>
  <div id="wrapper">


    <?php if(!isset($contest) || $contest->id == NULL) : ?>
      <?php if ($placeholder->id == NULL) :?>
        <p>Aucun concours disponible.</p>
      <?php else: ?>
        <img src="<?php echo $placeholder->picture_for('banner') ?>"  alt="banner" class="banner"/>
        <p><?php echo($placeholder->text); ?></p>
      <?php endif ?>
    <?php else: ?>
      <?php if ($contest->banner->name() != NULL) {?>
        <img src="<?php echo $contest->picture_for('banner') ?>"  alt="banner" class="banner"/>
      <?php } ?>
      <?php echo $content; ?>
    <?php endif ?>
  </div>
  <div id="footer">
    <p class="center">
      <?php if ($contest->rules_url) {?>
        <a href="<?php echo $contest->rules_url ?>" target="_blank">Règlement</a>
      <?php } ?>
      <?php if ($contest->rules_url && $contest->cgu_url) {?>
       &nbsp;-&nbsp;
      <?php } ?>
      <?php if ($contest->cgu_url) {?>
        <a href="<?php echo $contest->cgu_url ?>" target="_blank">CGU</a>
      <?php } ?>
    </p>
  </div>
</body>
</html>
