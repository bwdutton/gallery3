<?php defined("SYSPATH") or die("No direct script access.") ?>
<ul>
  <li>
    <?= t("Host name: %host_name", ["host_name" => php_uname("n")]) ?>
  </li>
  <li>
    <?= t("Operating system: %os %version", ["os" => php_uname("s"), "version" => php_uname("r")]) ?>
  </li>
  <li>
    <?= t("Apache: %apache_version", ["apache_version" => function_exists("apache_get_version") ? apache_get_version() : t("Unknown")]) ?>
  </li>
  <li>
    <?= t("PHP: %php_version", ["php_version" => phpversion()]) ?>
  </li>
  <li>
    <?= t("MySQL: %mysql_version", ["mysql_version" => Database::instance()->query("SELECT version() as v")->current()->v]) ?>
  </li>
  <li>
    <?= t("Server load: %load_average", ["load_average" => implode(" ", sys_getloadavg())]) ?>
  </li>
  <li>
    <?= t("Graphics toolkit: %toolkit", ["toolkit" => module::get_var("gallery", "graphics_toolkit")]) ?>
  </li>
</ul>
