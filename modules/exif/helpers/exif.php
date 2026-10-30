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

/**
 * This is the API for handling exif data.
 */
class exif_Core {

  protected static $exif_keys;

  public static function extract($item) {
    $keys = [];
    // Only try to extract EXIF from photos
    if ($item->is_photo() && $item->mime_type == "image/jpeg") {
      $data = [];
      require_once(MODPATH . "exif/lib/exif.php");
      $exif_raw = read_exif_data_raw($item->file_path(), false);
      if (isset($exif_raw['ValidEXIFData'])) {
        foreach(self::_keys() as $field => $exifvar) {
          if (isset($exif_raw[$exifvar[0]][$exifvar[1]])) {
            $value = $exif_raw[$exifvar[0]][$exifvar[1]];
            $value = encoding::convert_to_utf8($value);
            $keys[$field] = Input::clean($value);

            if ($field == "DateTime") {
              $time = strtotime($value);
              if ($time > 0) {
                $item->captured = $time;
              }
            } else if ($field == "Caption" && !$item->description) {
              $item->description = $value;
            }
          }
        }
      }

      $size = getimagesize($item->file_path(), $info);
      if (is_array($info) && !empty($info["APP13"])) {
        $iptc = iptcparse($info["APP13"]);
        foreach (["Keywords" => "2#025", "Caption" => "2#120"] as $keyword => $iptc_key) {
          if (!empty($iptc[$iptc_key])) {
            $value = implode(" ", $iptc[$iptc_key]);
            $value = encoding::convert_to_utf8($value);
            $keys[$keyword] = Input::clean($value);

            if ($keyword == "Caption" && !$item->description) {
              $item->description = $value;
            }
          }
        }
      }
    }
    $item->save();

    $record = ORM::factory("exif_record")->where("item_id", "=", $item->id)->find();
    if (!$record->loaded()) {
      $record->item_id = $item->id;
    }
    $record->data = serialize($keys);
    $record->key_count = count($keys);
    $record->dirty = 0;
    $record->save();
  }

  public static function get($item) {
    $exif = [];
    $record = ORM::factory("exif_record")
      ->where("item_id", "=", $item->id)
      ->find();
    if (!$record->loaded()) {
      return [];
    }

    $definitions = self::_keys();
    $keys = unserialize($record->data);
    foreach ($keys as $key => $value) {
      $exif[] = ["caption" => $definitions[$key][2], "value" => $value];
    }

    return $exif;
  }

  private static function _keys() {
    self::$exif_keys ??= [
      "Make"            => ["IFD0",   "Make",              t("Camera Maker"),     ],
      "Model"           => ["IFD0",   "Model",             t("Camera Model"),     ],
      "Aperture"        => ["SubIFD", "FNumber",           t("Aperture"),         ],
      "ColorSpace"      => ["SubIFD", "ColorSpace",        t("Color Space"),      ],
      "ExposureBias"    => ["SubIFD", "ExposureBiasValue", t("Exposure Value"),   ],
      "ExposureProgram" => ["SubIFD", "ExposureProgram",   t("Exposure Program"), ],
      "ExposureTime"    => ["SubIFD", "ExposureTime",      t("Exposure Time"),    ],
      "Flash"           => ["SubIFD", "Flash",             t("Flash"),            ],
      "FocalLength"     => ["SubIFD", "FocalLength",       t("Focal Length"),     ],
      "ISO"             => ["SubIFD", "ISOSpeedRatings",   t("ISO"),              ],
      "MeteringMode"    => ["SubIFD", "MeteringMode",      t("Metering Mode"),    ],
      "DateTime"        => ["SubIFD", "DateTimeOriginal",  t("Date/Time"),        ],
      "Copyright"       => ["IFD0",   "Copyright",         t("Copyright"),        ],
      "ImageType"       => ["IFD0",   "ImageType",         t("Image Type"),       ],
      "Orientation"     => ["IFD0",   "Orientation",       t("Orientation"),      ],
      "ResolutionUnit"  => ["IFD0",   "ResolutionUnit",    t("Resolution Unit"),  ],
      "xResolution"     => ["IFD0",   "xResolution",       t("X Resolution"),     ],
      "yResolution"     => ["IFD0",   "yResolution",       t("Y Resolution"),     ],
      "Compression"     => ["IFD1",   "Compression",       t("Compression"),      ],
      "BrightnessValue" => ["SubIFD", "BrightnessValue",   t("Brightness Value"), ],
      "Contrast"        => ["SubIFD", "Contrast",          t("Contrast"),         ],
      "ExposureMode"    => ["SubIFD", "ExposureMode",      t("Exposure Mode"),    ],
      "FlashEnergy"     => ["SubIFD", "FlashEnergy",       t("Flash Energy"),     ],
      "Saturation"      => ["SubIFD", "Saturation",        t("Saturation"),       ],
      "SceneType"       => ["SubIFD", "SceneType",         t("Scene Type"),       ],
      "Sharpness"       => ["SubIFD", "Sharpness",         t("Sharpness"),        ],
      "SubjectDistance" => ["SubIFD", "SubjectDistance",   t("Subject Distance"), ],
      "Caption"         => ["IPTC",   "Caption",           t("Caption"),          ],
      "Keywords"        => ["IPTC",   "Keywords",          t("Keywords"),         ]
    ];
    return self::$exif_keys;
  }

  public static function stats() {
    $missing_exif = db::build()
      ->select("items.id")
      ->from("items")
      ->join("exif_records", "items.id", "exif_records.item_id", "left")
      ->where("type", "=", "photo")
      ->and_open()
      ->where("exif_records.item_id", "IS", null)
      ->or_where("exif_records.dirty", "=", 1)
      ->close()
      ->execute()
      ->count();

    $total_items = ORM::factory("item")->where("type", "=", "photo")->count_all();
    if (!$total_items) {
      return [0, 0, 0];
    }
    return [$missing_exif, $total_items,
                 round(100 * (($total_items - $missing_exif) / $total_items))];
  }

  public static function check_index() {
    [$remaining] = exif::stats();
    if ($remaining) {
      site_status::warning(
        t('Your Exif index needs to be updated.  <a href="%url" class="g-dialog-link">Fix this now</a>',
          ["url" => html::mark_clean(url::site("admin/maintenance/start/exif_task::update_index?csrf=__CSRF__"))]),
        "exif_index_out_of_date");
    }
  }
}
