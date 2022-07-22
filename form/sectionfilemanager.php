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
 * Grid format.
 *
 * @package    format_grid
 * @copyright  &copy; 2022-onwards G J Barnard.
 * @author     G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot."/lib/form/filemanager.php");

class MoodleQuickForm_sectionfilemanager extends MoodleQuickForm_filemanager implements templatable {

    private static $options = array(
        'maxfiles' => 1,
        'subdirs' => 0,
        'accepted_types' => array('gif', 'jpe', 'jpeg', 'jpg', 'png', 'webp'),
        'return_types' => FILE_INTERNAL
    );

    /**
     * Constructor
     *
     * @param string $elementName (optional) name of the filemanager
     * @param string $elementLabel (optional) filemanager label
     * @param array $attributes (optional) Either a typical HTML attribute string
     *              or an associative array
     */
    public function __construct($elementName=null, $elementLabel=null, $attributes=null) {
        parent::__construct($elementName, $elementLabel, $attributes, self::$options);
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event Name of event
     * @param mixed $arg event arguments
     * @param object $caller calling object
     * @return bool
     */
    public function onQuickFormEvent($event, $arg, &$caller) {
        $result = parent::onQuickFormEvent($event, $arg, $caller);
        switch ($event) {
            case 'createElement':
                $this->init();
                break;
        }
        return $result;
    }
    
    private function init() {
        $course = $this->getAttribute('course');
        $sectionid = $this->getAttribute('sectionid');

        $coursecontext = context_course::instance($course->id);
        $fmd = file_prepare_standard_filemanager($course, 'sectionimage', self::$options, $coursecontext, 'format_grid', 'sectionimage', $sectionid);
    }

        /**
     * Check that all files have the allowed type.
     *
     * @param int $value Draft item id with the uploaded files.
     * @return string|null Validation error message or null.
     */
    public function validateSubmitValue($value) {
        $failure = parent::validateSubmitValue($value);
        if (!$failure) {
            $course = $this->getAttribute('course');
            $coursecontext = context_course::instance($course->id);
            $sectionid = $this->getAttribute('sectionid');
            $indata = new stdClass();
            $indata->sectionimage_filemanager = $value;
            // The file manager deals with the files table when the image is deleted.
            $outdata = file_postupdate_standard_filemanager($indata, 'sectionimage', self::$options, $coursecontext, 'format_grid', 'sectionimage', $sectionid);
            global $DB;
            if ($outdata->sectionimage == '1') {
                // We have file(s).
                $fs = get_file_storage();
                $files = $fs->get_area_files($coursecontext->id, 'format_grid', 'sectionimage', $sectionid);
                foreach ($files as $file) {
                    if (!$file->is_directory()) {
                        $filename = $file->get_filename();
                        $contenthash = $file->get_contenthash();
                        $sectionimage = $DB->get_record_select(
                            'format_grid_image', 
                            'courseid = ? AND sectionid = ? AND '.$DB->sql_compare_text('image') . ' = ?',
                            array($course->id, $sectionid, $filename)
                        );
                        if ($sectionimage) {
                            if (($contenthash !== $sectionimage->contenthash) || ($filename !== $sectionimage->image)) {
                                $conditionsarray = array('courseid' => $course->id, 'sectionid' => $sectionid, 'image' => $filename);
                                $DB->set_field('format_grid_image', 'contenthash', $contenthash, $conditionsarray);
                            }
                        } else {
                            $newimagecontainer = new \stdClass();
                            $newimagecontainer->sectionid = $sectionid;
                            $newimagecontainer->courseid = $course->id;
                            $newimagecontainer->image = $filename;
                            $newimagecontainer->contenthash = $contenthash;
                            $newid = $DB->insert_record('format_grid_image', $newimagecontainer, true);
                        }
                    }
                }
                // Note: Not done the case whereby 'a' file is removed - needed?
            } else {
                // No files.
                $DB->delete_records('format_grid_image', array('courseid' => $course->id, 'sectionid' => $sectionid));
            }
        }

        return $failure;
    }
}
