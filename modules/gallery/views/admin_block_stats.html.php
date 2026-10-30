<?php defined("SYSPATH") or die("No direct script access.") ?>
<ul>
  <li>
    <?= t("Version: %version", ["version" => gallery::version_string()]) ?>
  </li>
  <li>
    <?= t("Albums: %count", ["count" => $album_count]) ?>
  </li>
  <li>
    <?= t("Photos: %count", ["count" => $photo_count]) ?>
  </li>
</ul>
