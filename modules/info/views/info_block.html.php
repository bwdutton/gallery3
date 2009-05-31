<?php defined("SYSPATH") or die("No direct script access.") ?>
<table class="gMetadata">
  <tbody>
    <tr>
      <th><?= t("Title:") ?></th>
      <td><?= p::clean($item->title) ?></td>
    </tr>
    <? if ($item->description): ?>
    <tr>
      <th><?= t("Description:") ?></th>
      <td><?= p::clean($item->description) ?></td>
    </tr>
    <? endif ?>
    <? if ($item->id != 1): ?>
    <tr>
      <th><?= t("Name:") ?></th>
      <td><?= p::clean($item->name) ?></td>
    </tr>
    <? endif ?>
    <? if ($item->captured): ?>
    <tr>
      <th><?= t("Captured:") ?></th>
      <td><?= date("M j, Y H:i:s", $item->captured)?></td>
    </tr>
    <? endif ?>
    <? if ($item->owner): ?>
    <tr>
      <th><?= t("Owner:") ?></th>
      <td><a href="#"><?= p::clean($item->owner->name) ?></a></td>
    </tr>
    <? endif ?>
  </tbody>
</table>
