<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2009 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */

class user_event_Core {
  static function admin_menu($menu, $theme) {
    $user_group_menu = Menu::factory("link")
      ->id("users_groups")
      ->label(t("Users/Groups"))
      ->url(url::site("admin/users"));
    $identity_menu = $menu->get("identity_menu");
    if (empty($identity_menu)) {
      $menu->add_after("appearance_menu", $user_group_menu);
    }else {
      $identity_menu->append($user_group_menu);
    }

    return $menu;
  }
}