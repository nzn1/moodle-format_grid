<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Grid Format - A topics based format that uses a grid of user selectable images to popup a
 *               light box of the section.
 *
 * @package    format_grid
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  &copy; 2013 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://about.me/gjbarnard} and
 *                           {@link http://moodle.org/user/profile.php?id=442195}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use format_grid\admin_setting_information;
use format_grid\admin_setting_markdown;

require_once($CFG->dirroot . '/course/format/grid/lib.php'); // For format_grid static constants.

$settings = null;
$ADMIN->add('formatsettings', new admin_category('format_grid', get_string('pluginname', 'format_grid')));

// Information.
$page = new admin_settingpage('format_grid_information',
    get_string('information', 'format_grid'));

if ($ADMIN->fulltree) {
    $page->add(new admin_setting_heading('format_grid_information', '',
        format_text(get_string('informationsettingsdesc', 'format_grid'), FORMAT_MARKDOWN)));

    // Information.
    $page->add(new admin_setting_information('format_grid/formatinformation', '', '', 400));

    // Support.md.
    $page->add(new admin_setting_markdown('format_grid/formatsupport', '', '', 'Support.md'));
}
$ADMIN->add('format_grid', $page);

// Settings.
$page = new admin_settingpage('format_grid_settings',
    get_string('settings', 'format_grid'));
if ($ADMIN->fulltree) {
    $page->add(new admin_setting_heading('format_grid_settings', '',
        format_text(get_string('settingssettingsdesc', 'format_grid'), FORMAT_MARKDOWN)));

    // Resize method - 1 = scale, 2 = crop.
    $name = 'format_grid/defaultimageresizemethod';
    $title = get_string('defaultimageresizemethod', 'format_grid');
    $description = get_string('defaultimageresizemethod_desc', 'format_grid');
    $default = 1; // Scale.
    $choices = array(
        1 => new lang_string('scale', 'format_grid'),
        2 => new lang_string('crop', 'format_grid')
    );
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('format_grid::update_displayed_images_callback');
    $page->add($setting);

    // Displayed image file type - 1 = original, 2 = webp.
    $name = 'format_grid/defaultdisplayedimagefiletype';
    $title = get_string('defaultdisplayedimagefiletype', 'format_grid');
    $description = get_string('defaultdisplayedimagefiletype_desc', 'format_grid');
    $default = 1; // Original.
    $choices = array(
        1 => new lang_string('original', 'format_grid'),
        2 => new lang_string('webp', 'format_grid')
    );
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('format_grid::update_displayed_images_callback');
    $page->add($setting);
}
$ADMIN->add('format_grid', $page);
