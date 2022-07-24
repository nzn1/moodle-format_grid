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
 * Contains the default content output class.
 *
 * @package   format_grid
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_grid\output\courseformat;

use core_courseformat\output\local\content as content_base;

/**
 * Base class to render a course content.
 *
 * @package   format_grid
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {

    /**
     * @var bool Grid format does not add section after each topic.
     *
     * The responsible for the buttons is core_courseformat\output\local\content\section.
     */
    protected $hasaddsection = false;

    public function get_template_name(\renderer_base $renderer): string {
        return 'format_grid/local/content';
    }

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        global $DB;
        $format = $this->format;

        // Most formats uses section 0 as a separate section so we remove from the list.
        $sections = $this->export_sections($output);
        $initialsection = '';
        if (!empty($sections)) {
            $initialsection = array_shift($sections);
        }

        $data = (object)[
            'title' => $format->page_title(), // This method should be in the course_format class.
            'initialsection' => $initialsection,
            'sections' => $sections,
            'format' => $format->get_format(),
            'sectionreturn' => 0,
        ];

        // The single section format has extra navigation.
        $singlesection = $this->format->get_section_number();
        if ($singlesection) {
            // if (!$PAGE->theme->usescourseindex) {
                $sectionnavigation = new $this->sectionnavigationclass($format, $singlesection);
                $data->sectionnavigation = $sectionnavigation->export_for_template($output);

                $sectionselector = new $this->sectionselectorclass($format, $sectionnavigation);
                $data->sectionselector = $sectionselector->export_for_template($output);
            // }
            $data->hasnavigation = true;
            $data->singlesection = array_shift($data->sections);
            $data->sectionreturn = $singlesection;
        } else {
            //error_log(print_r($sections, true));
            error_log(print_r($format->get_format_options(), true));
            foreach ($sections as $section) {
                $sectionclass = new \stdClass();
                $sectionclass->id = $section->id;
                error_log($section->id.print_r($format->get_format_options($sectionclass), true));
            }

            if (!empty($sections)) {
                $course = $format->get_course();
                $toolbox = \format_grid\toolbox::get_instance();
                $coursesectionimages = $DB->get_records('format_grid_image', array('courseid' => $course->id));
                error_log($course->id.print_r($coursesectionimages, true));
                if (!empty($coursesectionimages)) {
                    $fs = get_file_storage();
                    $coursecontext = \context_course::instance($course->id);
                    foreach ($coursesectionimages as $coursesectionimage) {
                        if (empty($coursesectionimage->displayedimagestate)) {
                            $lockfactory = \core\lock\lock_config::get_lock_factory('format_grid');
                            if ($lock = $lockfactory->get_lock('sectionid'.$coursesectionimage->sectionid, 5)) {
                                $files = $fs->get_area_files($coursecontext->id, 'format_grid', 'sectionimage', $coursesectionimage->sectionid);
                                foreach ($files as $file) {
                                    if (!$file->is_directory()) {
                                        error_log('f '.$coursesectionimage->sectionid.' - '.print_r($file->get_filename(), true));
                                        try {
                                            $toolbox->setup_displayed_image($coursesectionimage, $file, $course->id, $coursesectionimage->sectionid);
                                        } catch (\Exception $e) {
                                            $lock->release();
                                            throw $e;
                                        }
                                    }
                                }
                                $lock->release();
                            } else {
                                throw new \moodle_exception('cannotgetimagelock', 'format_grid', '',
                                    get_string('cannotgetmanagesectionimagelock', 'format_grid'));
                            }
                        }
                    }
                }
            }
        }

        if ($this->hasaddsection) {
            $addsection = new $this->addsectionclass($format);
            $data->numsections = $addsection->export_for_template($output);
        }

        return $data;
    }
}
