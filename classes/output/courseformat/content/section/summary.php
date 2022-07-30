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
 * Grid Format - A topics based format that uses a grid of user selectable images to link to a single section page.
 * Contains the default section summary (used for multipage format).
 *
 * @package    format_grid
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  &copy; 2022 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://about.me/gjbarnard} and
 *                           {@link http://moodle.org/user/profile.php?id=442195}
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @author     Based on code originally written by Paul Krix and Julian Ridden.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_grid\output\courseformat\content\section;

use core_courseformat\output\local\content\section\summary as summary_base;
use core_courseformat\base as course_format;
use section_info;

use context_course;
use stdClass;

/**
 * Base class to render a course section summary.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class summary extends summary_base {

    /** @var section_info the course section class - core is 'private' */
    private $thesection;

    /**
     * Constructor.
     *
     * @param course_format $format the course format
     * @param section_info $section the section info
     */
    public function __construct(course_format $format, section_info $section) {
        parent::__construct($format, $section);
        $this->thesection = $section;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {

        $section = $this->thesection;

        $data = new stdClass();

        if ($section->uservisible || $section->visible) {
            $data->summarytext = $this->singlepagesummaryimage().$this->format_summary_text();
        }
        return $data;
    }

    /**
     * Generate html for a section summary text
     *
     * @return string HTML to output.
     */
    public function format_summary_text(): string {
        $section = $this->thesection;
        $context = context_course::instance($section->course);
        $summarytext = file_rewrite_pluginfile_urls($section->summary, 'pluginfile.php',
            $context->id, 'course', 'section', $section->id);

        $options = new stdClass();
        $options->noclean = true;
        $options->overflowdiv = true;
        return format_text($summarytext, $section->summaryformat, $options);
    }
    
    protected function singlepagesummaryimage(): string {
        global $DB;
        $o = '';

        if (true) {
            //error_log('singlepagesummaryimage - '.print_r($this->thesection->course, true));
            //error_log('singlepagesummaryimage - '.print_r($this->thesection->section, true));
            //error_log('singlepagesummaryimage - '.print_r($this->thesection->id, true));

            $courseid = $this->thesection->course;
            $sectionid = $this->thesection->id;
            $coursesectionimage = $DB->get_record('format_grid_image', array('courseid' => $courseid, 'sectionid' => $sectionid));
            if (!empty($coursesectionimage)) {
                $fs = get_file_storage();
                $coursecontext = \context_course::instance($courseid);
                if (empty($coursesectionimage->displayedimagestate)) {
                    $lockfactory = \core\lock\lock_config::get_lock_factory('format_grid');
                    $toolbox = \format_grid\toolbox::get_instance();
                    if ($lock = $lockfactory->get_lock('sectionid'.$sectionid, 5)) {
                        $files = $fs->get_area_files($coursecontext->id, 'format_grid', 'sectionimage', $coursesectionimage->sectionid);
                        foreach ($files as $file) {
                            if (!$file->is_directory()) {
                                try {
                                    $coursesectionimage = $toolbox->setup_displayed_image($coursesectionimage, $file, $courseid, $coursesectionimage->sectionid, $this->format);
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
                if ($coursesectionimage->displayedimagestate >= 1) {
                    // Yes.
                    $filename = $coursesectionimage->image;
                    $iswebp = (get_config('format_grid', 'defaultdisplayedimagefiletype') == 2);

                    if ($iswebp) {
                        $filename = $filename.'.webp';
                    }
                    $image = \moodle_url::make_pluginfile_url(
                        $coursecontext->id, 'format_grid', 'displayedsectionimage', $sectionid, '/'.$coursesectionimage->displayedimagestate.'/', $filename
                    );
                    $o .= $image->out();
                }
            }
            error_log('singlepagesummaryimage - '.print_r($coursesectionimage, true));
        }

        return $o;
    }
}
