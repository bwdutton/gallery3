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
class Exif_Test extends Gallery_Unit_Test_Case {
  public function exif_extract_test() {
    $photo = test::random_photo_unsaved()
      ->set_data_file(MODPATH . "exif/tests/data/image.jpg")
      ->save();

    $expected = [
      ["caption" => "Camera Maker", "value" => "Pentax Corporation"],
      ["caption" => "Camera Model", "value" => "PENTAX K10D"],
      ["caption" => "Aperture", "value" => "f/2.8"],
      ["caption" => "Color Space", "value" => "Uncalibrated"],
      ["caption" => "Exposure Value", "value" => "4294.67 EV"],
      ["caption" => "Exposure Program", "value" => "Program"],
      ["caption" => "Exposure Time", "value" => "1/60 sec"],
      ["caption" => "Flash", "value" => "No Flash"],
      ["caption" => "Focal Length", "value" => "50 mm"],
      ["caption" => "ISO", "value" => "100"],
      ["caption" => "Metering Mode", "value" => "Pattern"],
      ["caption" => "Date/Time", "value" => "2008:03:17 17:41:25"],
      ["caption" => "Copyright", "value" => "(C) 2008 -  T. Almdal"],
      ["caption" => "Orientation", "value" => "1: Normal (0 deg)"],
      ["caption" => "Resolution Unit", "value" => "Inch"],
      ["caption" => "X Resolution", "value" => "240 dots per ResolutionUnit"],
      ["caption" => "Y Resolution", "value" => "240 dots per ResolutionUnit"],
      ["caption" => "Brightness Value", "value" => "0"],
      ["caption" => "Scene Type", "value" => "0"],
      ["caption" => "Subject Distance", "value" => "0"],
    ];
    $this->assert_equal($expected, exif::get($photo));
  }
}