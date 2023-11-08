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

namespace format_grid\task;

/**
 * Class upgrade_single_course
 *
 * @package    format_grid
 * @copyright  2023 Jay Oswad <jayoswald@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upgrade_single_course extends \core\task\adhoc_task {

    public function get_name() {
        return "Course Format Grid Image Migration - 3.9 to 4.1";
    }

    public function execute() {
        $params = $this->get_custom_data();
        $courseid = $params->courseid;
        $this->upgrade_course($courseid);
    }

    private function upgrade_course($currentcourseid) {
        global $DB;
        $newimagecoursearray = $DB->get_records_sql(
            'SELECT sectionid, courseid, image, displayedimagestate FROM {format_grid_image} WHERE courseid = ?',
            [$currentcourseid]
        );
        $fs = get_file_storage();
        $coursecontext = \context_course::instance($currentcourseid);
        $files = $fs->get_area_files($coursecontext->id, 'course', 'section');
        foreach ($files as $file) {
            if (!$file->is_directory()) {
                if ($file->get_filepath() == '/gridimage/') {
                    $file->delete();
                } else {
                    $filename = $file->get_filename();
                    $filesectionid = $file->get_itemid();
                    // Ensure we know about this section.
                    if (array_key_exists($filesectionid, $newimagecoursearray)) {
                        $gridimage = $newimagecoursearray[$filesectionid];
                        // Ensure the correct file.
                        if (($gridimage) && ($gridimage->image == $filename)) {
                            $filerecord = new \stdClass();
                            $filerecord->contextid = $coursecontext->id;
                            $filerecord->component = 'format_grid';
                            $filerecord->filearea = 'sectionimage';
                            $filerecord->itemid = $filesectionid;
                            $filerecord->filepath = '/';
                            $filerecord->filename = $filename;
                            $thefile = false;
                            // Check to see if the file is already there.
                            $thefile = $fs->get_file(
                                $filerecord->contextid,
                                $filerecord->component,
                                $filerecord->filearea,
                                $filerecord->itemid,
                                $filerecord->filepath,
                                $filerecord->filename);
                            if ($thefile === false) {
                                $thefile = $fs->create_file_from_storedfile($filerecord, $file);
                            }
                            if ($thefile !== false) {
                                $DB->set_field('format_grid_image', 'contenthash',
                                    $thefile->get_contenthash(), ['sectionid' => $filesectionid]);
                                // Don't delete the section file in case used in the summary.
                            }
                        }
                    }
                }
            }
        }
    }
}
