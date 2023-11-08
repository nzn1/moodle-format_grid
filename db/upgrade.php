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
 * Grid Format.
 *
 * @package    format_grid
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  &copy; 2012 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://about.me/gjbarnard} and
 *                           {@link http://moodle.org/user/profile.php?id=442195}
 * @author     Based on code originally written by Paul Krix and Julian Ridden.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_format_grid_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2022072200) {
        // Define table format_grid_image to be created.
        $table = new xmldb_table('format_grid_image');
        $somethingbroke = false;

        // Has the script been executed already and broken?
        if ($dbman->table_exists($table)) {
            $somethingbroke = true;
            $dbman->drop_table($table);
        }

        // Adding fields to table format_grid_image.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('image', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('displayedimagestate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table format_grid_image.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table format_grid_image.
        $table->add_index('section', XMLDB_INDEX_UNIQUE, ['sectionid']);
        $table->add_index('course', XMLDB_INDEX_NOTUNIQUE, ['courseid']);

        // Create table for format_grid_image.
        $dbman->create_table($table);

        $lock = true;
        if (!defined('BEHAT_SITE_RUNNING')) {
            $lockfactory = \core\lock\lock_config::get_lock_factory('format_grid');
            $lock = $lockfactory->get_lock('gridupgradelock2022072200', 5);
        }
        if ($lock) {
            try {
                $oldtable = new xmldb_table('format_grid_icon');
                if ($dbman->table_exists($oldtable)) {
                    // Upgrade from old images.
                    $oldimages = $DB->get_records('format_grid_icon');
                    if (!empty($oldimages)) {
                        if ($dbman->table_exists($oldtable)) {
                            // Move images.
                            $DB->execute('
                            INSERT INTO {format_grid_image} (sectionid, courseid, image, displayedimagestate)
                            SELECT sectionid, courseid, image, 0
                            FROM {format_grid_icon}
                            WHERE courseid IN ( SELECT id FROM {course} )
                            ');
                            $courses = $DB->get_records_sql('SELECT DISTINCT courseid FROM {format_grid_image}');
                            foreach ($courses as $course) {
                                $task = new \format_grid\task\upgrade_single_course();
                                $task->set_custom_data([
                                    'course_id' => $course->courseid,
                                ]);
                                \core\task\manager::queue_adhoc_task($task, true);
                            }
                        }
                    }
                }

                // Delete 'format_grid_icon' and 'format_grid_summary' tables....
                $dbman->drop_table($oldtable);
                $oldsummarytable = new xmldb_table('format_grid_summary');
                $dbman->drop_table($oldsummarytable);

                if (!defined('BEHAT_SITE_RUNNING')) {
                    $lock->release();
                }
            } catch (\Exception $e) {
                if (!defined('BEHAT_SITE_RUNNING')) {
                    $lock->release();
                }
                throw $e;
            }
        } else {
            throw new moodle_exception('cannotgetupgradelock', 'format_grid', '', 'Cannot get upgrade lock');
        }

        // Grid savepoint reached.
        upgrade_plugin_savepoint(true, 2022072200, 'format', 'grid');
    }

    if ($oldversion < 2022112605) {
        $records = $DB->get_records('course_format_options',
            [
                'format' => 'grid',
                'name' => 'numsections',
            ], '', 'id'
        );

        $records = array_keys($records);
        foreach ($records as $id) {
            $DB->set_field('course_format_options', 'name', 'gnumsections', ['id' => $id]);
        }

        // Grid savepoint reached.
        upgrade_plugin_savepoint(true, 2022112605, 'format', 'grid');
    }

    // Automatic 'Purge all caches'....
    purge_all_caches();

    return true;
}
