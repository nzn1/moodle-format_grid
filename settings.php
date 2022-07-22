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
}
$ADMIN->add('format_grid', $page);
