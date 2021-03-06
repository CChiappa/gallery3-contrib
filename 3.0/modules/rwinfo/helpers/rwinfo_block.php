<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2012 Bharat Mediratta
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
class rwinfo_block_Core {
  static function get_site_list() {
    return array("metadata" => t("rWInfo"));
  }

  static function get($block_id, $theme) {
    $block = "";
    switch ($block_id) {
    case "metadata":
      if ($theme->item()) {
        // rWatcher Edit: Don't display on root album.
        if ($theme->item->id == 1) {
          return "";
        }
        // End rWatcher Edit

        $block = new Block();
        $block->css_id = "g-metadata";
		
        // rWatcher Edit:  Add Movie Info Option
        //$block->title = $theme->item()->is_album() ? t("Album info") : t("Photo info");
        $block_title = "";
        if ($theme->item->is_album()) {
          $block_title = t("Album Info");
        } else if ($theme->item->is_movie()) {
          $block_title = t("Movie Info");
        } else {
          $block_title = t("Photo Info");
        }
        $block->title = $block_title;
        // End rWatcher Edit
		
        // rWatcher Edit:  File Name change.
        $block->content = new View("rwinfo_block.html");
		
        if ($theme->item->title && module::get_var("rwinfo", "show_title")) {
          $info["title"] = array(
            "label" => t("Title:"),
            "value" => html::purify($theme->item->title)
          );
        }
        if ($theme->item->description && module::get_var("rwinfo", "show_description")) {
          $info["description"] = array(
            "label" => t("Description:"),
            "value" => nl2br(html::purify($theme->item->description))
          );
        }
        if (!$theme->item->is_album() && module::get_var("rwinfo", "show_name")) {
          $info["file_name"] = array(
            "label" => t("File name:"),
            "value" => html::clean($theme->item->name)
          );
        }

        // rWatcher Edit: 
        if (!$theme->item->is_album()) {
          // Calculate file size.
          $filesize_unit = array("B","kB","MB","GB","TB","PB","EB","ZB","YB");
          $item_filesize = filesize($theme->item->file_path());
          $unit_counter = 0;
          while ($item_filesize >= 1024) {
            $item_filesize = $item_filesize / 1024;
            $unit_counter++;
          }
          $item_filesize = number_format($item_filesize, 2) . " " . $filesize_unit[$unit_counter];
          $info["file_size"] = array(
            "label" => t("File size:"),
            "value" => $item_filesize
          );

          // Display photo/video resolution.
          $info["file_resolution"] = array(
            "label" => t("Resolution:"),
            "value" => $theme->item->width . " x " . $theme->item->height . " " . t("pixels")
          );
        }

        //if ($theme->item->captured && module::get_var("rwinfo", "show_captured")) {
        //  $info["captured"] = array(
        //    "label" => t("Captured:"),
        //    "value" => gallery::date_time($theme->item->captured)
        //  );
        //}
        if ($theme->item->is_album() && $theme->item->created && module::get_var("rwinfo", "show_captured")) {
          $info["captured"] = array(
            "label" => t("Date:"),
            "value" => gallery::date($theme->item->created)
          );
        }
        if (!$theme->item->is_album() && $theme->item->created && module::get_var("rwinfo", "show_captured")) {
          $info["captured"] = array(
            "label" => t("Date:"),
            "value" => gallery::date_time($theme->item->captured)
          );
        }
        // End rWatcher Edit

        if ($theme->item->owner && module::get_var("rwinfo", "show_owner")) {
          $display_name = $theme->item->owner->display_name();

          // rWatcher Edit:  Display profile instead of web site, if viewable.
          $str_owner_url = $theme->item->owner->url;
          if (rwinfo_block_Core::_can_view_profile_pages(identity::lookup_user($theme->item->owner->id))) {
            $str_owner_url = user_profile::url($theme->item->owner->id);
          }
          // rWatcher End Edit

          if ($str_owner_url) { //rW Edit $str_owner_url.
            $info["owner"] = array(
              "label" => t("Owner:"),
              "value" => "<a href=\"{$str_owner_url}\">" .
                         html::clean($display_name) . "</a>"  //rW Edit $str_owner_url.
            );
          } else {
            $info["owner"] = array(
              "label" => t("Owner:"),
              "value" => html::clean($display_name)
            );
          }
        }
        $block->content->metadata = $info;

        module::event("info_block_get_metadata", $block, $theme->item);
      }
      break;
    }
    return $block;
  }

  // This came from modules/gallery/controllers/user_profile.php.
  static private function _can_view_profile_pages($user) {
    if (!$user->loaded()) {
      return false;
    }

    if ($user->id == identity::active_user()->id) {
      // You can always view your own profile
      return true;
    }

    switch (module::get_var("gallery", "show_user_profiles_to")) {
    case "admin_users":
      return identity::active_user()->admin;

    case "registered_users":
      return !identity::active_user()->guest;

    case "everybody":
      return true;

    default:
      // Fail in private mode on an invalid setting
      return false;
    }
  }
}