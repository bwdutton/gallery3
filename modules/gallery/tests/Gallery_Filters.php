<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2013 Bharat Mediratta
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
class PhpCodeFilterIterator extends FilterIterator {
  public function accept(): bool {
    $path_name = $this->getInnerIterator()->getPathName();
    return str_ends_with($path_name, ".php");
  }
}

class GalleryCodeFilterIterator extends FilterIterator {
  public function accept(): bool {
    // Skip anything that we didn't write
    $path_name = $this->getInnerIterator()->getPathName();
    $file_name = $this->getInnerIterator()->getFileName();
    return !(
      $file_name == "." ||
      $file_name == ".." ||
      str_contains($path_name, DOCROOT . ".git") ||
      str_contains($path_name, DOCROOT . "test") ||
      str_contains($path_name, DOCROOT . "var") ||
      str_contains($path_name, MODPATH . "forge") ||
      str_contains($path_name, MODPATH . "gallery/views/kohana_error_page.php") ||
      str_contains($path_name, MODPATH . "gallery/views/kohana_profiler.php") ||
      str_contains($path_name, MODPATH . "gallery_unit_test/vendor") ||
      str_contains($path_name, MODPATH . "gallery_unit_test/views/kohana_error_page.php") ||
      str_contains($path_name, MODPATH . "gallery_unit_test/views/kohana_unit_test_cli.php") ||
      str_contains($path_name, MODPATH . "unit_test") ||
      str_contains($path_name, MODPATH . "exif/lib") ||
      str_contains($path_name, MODPATH . "user/lib/PasswordHash") ||
      str_contains($path_name, SYSPATH) ||
      str_contains($path_name, MODPATH . "gallery/libraries/HTMLPurifier") ||
      str_contains($path_name, MODPATH . "gallery/vendor/joomla") ||
      str_contains($path_name, MODPATH . "organize/vendor/ext") ||
      str_contains($path_name, DOCROOT . "lib") ||
      str_contains($path_name, DOCROOT . "themes/admin_wind/css/themeroller") ||
      str_contains($path_name, DOCROOT . "themes/wind/css/themeroller") ||
      str_ends_with($path_name, "~"));
  }
}
