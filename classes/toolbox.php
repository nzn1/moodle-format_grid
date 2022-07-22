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
 * @copyright  &copy; 2021-onwards G J Barnard based upon work done by Marina Glancy.
 * @author     G J Barnard - {@link http://about.me/gjbarnard} and
 *                           {@link http://moodle.org/user/profile.php?id=442195}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_grid;

/**
 * The format's toolbox.
 *
 * @copyright  &copy; 2021-onwards G J Barnard.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class toolbox {
    /**
     * This is a lonely object.
     */
    private function __construct() {
    }

    /**
     * Gets the toolbox singleton.
     *
     * @return toolbox The toolbox instance.
     */
    public static function get_instance() {
        if (!is_object(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Gets the grid image entries for the given course.
     * @param int $courseid The course id to use.
     * @returns bool|array The records or false if the course id is 0 or the request failed.
     */
    public static function get_images($courseid) {
        global $DB;

        if (!$courseid) {
            return false;
        }

        if (!$sectionimagecontainers = $DB->get_records('format_grid_icon', array('courseid' => $courseid), '',
                'sectionid, image, displayedimageindex, updatedisplayedimage, alttext')) {
            $sectionimagecontainers = false;
        }
        return $sectionimagecontainers;
    }

    /**
     * Gets the grid image entry for the given course and section.  If an entry cannot be found then one is created
     * and returned.  If the course id is set to the default then it is updated to the one supplied as the value
     * will be accurate.
     * @param int $courseid The course id to use.
     * @param int $sectionid The section id to use.
     * @returns bool|array The record or false if the course id is 0 or section id is 0 or the request failed.
     */
    public static function get_image($courseid, $sectionid) {
        global $DB;

        if ((!$courseid) || (!$sectionid)) {
            return false;
        }

        // Only allow this code to be executed once at the same time for the given section id (the id is unique).
        $lockfactory = \core\lock\lock_config::get_lock_factory('format_grid');
        if ($lock = $lockfactory->get_lock('sectionid'.$sectionid, 5)) {
            if (!$sectionimage = $DB->get_record('format_grid_icon', array('sectionid' => $sectionid),
                'courseid, sectionid, image, displayedimageindex, updatedisplayedimage, alttext')) {
                $newimagecontainer = new \stdClass();
                $newimagecontainer->sectionid = $sectionid;
                $newimagecontainer->courseid = $courseid;
                $newimagecontainer->displayedimageindex = 0;
                $newimagecontainer->updatedisplayedimage = 0;

                if (!$newimagecontainer->id = $DB->insert_record('format_grid_icon', $newimagecontainer, true)) {
                    $lock->release();
                    throw new \moodle_exception('invalidiconrecordid', 'format_grid', '', get_string('invalidiconrecordid', 'format_grid'));
                }
                $sectionimage = $newimagecontainer;
            } else if ($sectionimage->courseid == 1) { // 1 is the default and is the 'site' course so cannot be the Grid format.
                // Note: Using a double equals in the test and not a triple as the latter does not work for some reason.
                /* Course id is the default and needs to be set correctly.  This can happen with data created by versions prior to
                13/7/2012. */
                $DB->set_field('format_grid_icon', 'courseid', $courseid, array('sectionid' => $sectionid));
                $sectionimage->courseid = $courseid;
            }
            $lock->release();
        } else {
            throw new \moodle_exception('cannotgetimagelock', 'format_grid', '', get_string('cannotgetimagelock', 'format_grid'));
        }
        return $sectionimage;
    }

    public static function delete_displayed_images($courseformat) {
        $sectionimages = self::get_images($courseformat->get_courseid());

        if (is_array($sectionimages)) {
            global $DB;
            $contextid = \format_grid::get_contextid($courseformat);
            $fs = get_file_storage();
            $gridimagepath = self::get_image_path();
            $t = $DB->start_delegated_transaction();

            foreach ($sectionimages as $sectionimage) {
                // Delete the displayed image.
                self::delete_displayed_image($contextid, $sectionimage, $gridimagepath, $fs);
            }
            $t->allow_commit();
        }
    }

    public static function delete_displayed_image($contextid, $sectionimage, $gridimagepath, $fs) {
        global $DB;

        if ($file = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, $gridimagepath,
            $sectionimage->displayedimageindex . '_' . $sectionimage->image)) {
            $file->delete();
            $DB->set_field('format_grid_icon', 'displayedimageindex', 0, array('sectionid' => $sectionimage->sectionid));
        }
        if ($file = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, $gridimagepath,
            $sectionimage->displayedimageindex . '_' . $sectionimage->image.'.webp')) {
            $file->delete();
            $DB->set_field('format_grid_icon', 'displayedimageindex', 0, array('sectionid' => $sectionimage->sectionid));
        }
    }

    /**
     * Set up the displayed image.
     * @param array $sectionimage Section information from its row in the 'format_grid_icon' table.
     * @param int $contextid The context id to which the image relates.
     * @param int $courseid The course id to which the image relates.
     * @param array $settings The course settings to apply.
     * @param string $mime The mime type if already known.
     * @return array The updated $sectionimage data.
     */
    public static function setup_displayed_image($sectionimage, $contextid, $courseid, $settings, $mime = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/repository/lib.php');
        require_once($CFG->libdir . '/gdlib.php');

        // Set up the displayed image:...
        $fs = get_file_storage();
        if ($imagecontainerpathfile = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, '/',
                $sectionimage->newimage)) {
            $gridimagepath = self::get_image_path();
            $convertsuccess = true;
            if (!$mime) {
                $mime = $imagecontainerpathfile->get_mimetype();
            }

            $displayedimageinfo = self::get_displayed_image_container_properties($settings);
            $tmproot = make_temp_directory('gridformatdisplayedimagecontainer');
            $tmpfilepath = $tmproot . '/' . $imagecontainerpathfile->get_contenthash();
            $imagecontainerpathfile->copy_content_to($tmpfilepath);

            if ($settings['imageresizemethod'] == 1) {
                $crop = false;
            } else {
                $crop = true;
            }
            $iswebp = (get_config('format_grid', 'defaultdisplayedimagefiletype') == 2);
            if ($iswebp) { // WebP.
                $newmime = 'image/webp';
            } else {
                $newmime = $mime;
            }
            $debugdata = array(
                'itemid' => $imagecontainerpathfile->get_itemid(),
                'filename' => $imagecontainerpathfile->get_filename(),
                'sectionimage_sectionid' => $sectionimage->sectionid,
                'sectionimage_image' => $sectionimage->image,
                'sectionimage_displayedimageindex' => $sectionimage->displayedimageindex,
                'sectionimage_newimage' => $sectionimage->newimage
            );
            $data = self::generate_image($tmpfilepath, $displayedimageinfo['width'], $displayedimageinfo['height'], $crop, $newmime, $debugdata);
            if (!empty($data)) {
                // Updated image.
                $sectionimage->displayedimageindex++;
                $created = time();
                $displayedimagefilerecord = array(
                    'contextid' => $contextid,
                    'component' => 'course',
                    'filearea' => 'section',
                    'itemid' => $sectionimage->sectionid,
                    'filepath' => $gridimagepath,
                    'filename' => $sectionimage->displayedimageindex . '_' . $sectionimage->newimage,
                    'timecreated' => $created,
                    'timemodified' => $created,
                    'mimetype' => $mime);

                self::remove_existing_new_displayed_image($displayedimagefilerecord, $fs);

                if ($iswebp) { // WebP.
                    // Displayed image is a webp image from the original, so change a few things.
                    $displayedimagefilerecord['filename'] = $sectionimage->displayedimageindex . '_' . $sectionimage->newimage.'.webp';
                    $displayedimagefilerecord['mimetype'] = $newmime;
                }
                $fs->create_file_from_string($displayedimagefilerecord, $data);
            } else {
                $convertsuccess = false;
            }
            unlink($tmpfilepath);

            if ($convertsuccess == true) {
                // Now safe to delete old file(s) if they exist.
                if ($oldfile = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, $gridimagepath,
                        ($sectionimage->displayedimageindex - 1) . '_' . $sectionimage->image)) {
                    $oldfile->delete();
                }
                if ($oldfile = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, $gridimagepath,
                        ($sectionimage->displayedimageindex - 1) . '_' . $sectionimage->image.'.webp')) {
                    $oldfile->delete();
                }
                $DB->set_field('format_grid_icon', 'displayedimageindex', $sectionimage->displayedimageindex,
                    array('sectionid' => $sectionimage->sectionid));
                if ($sectionimage->updatedisplayedimage == 1) {
                    $DB->set_field('format_grid_icon', 'updatedisplayedimage', 0,
                        array('sectionid' => $sectionimage->sectionid));
                    $sectionimage->updatedisplayedimage = 0;
                }
            } else {
                print_error('cannotconvertuploadedimagetodisplayedimage', 'format_grid',
                    $CFG->wwwroot."/course/view.php?id=".$courseid,
                    'SI: '.var_export($sectionimage, true).', DII: '.var_export($displayedimageinfo, true));
            }
        } else {
            $DB->set_field('format_grid_icon', 'image', null, array('sectionid' => $sectionimage->sectionid));
        }

        return $sectionimage;  // So that the caller can know the new value of displayedimageindex.
    }

    protected static function remove_existing_new_displayed_image($displayedimagefilerecord, $fs) {
        // Can happen if previously updating the section name did not delete the displayed image.
        if ($fs->file_exists($displayedimagefilerecord['contextid'], $displayedimagefilerecord['component'],
            $displayedimagefilerecord['filearea'], $displayedimagefilerecord['itemid'],
            $displayedimagefilerecord['filepath'], $displayedimagefilerecord['filename'])) {
            /* This can happen with previous CONTRIB-4099 versions where it was possible for the backup file to
               have the 'gridimage' files too.  Therefore without this, then 'create_file_from_string' below will
               baulk as the file already exists.   Unfortunately has to be here as the restore mechanism restores
               the grid format data for the database and then the files.  And the Grid code is called at the 'data'
               stage. */
            if ($oldfile = $fs->get_file($displayedimagefilerecord['contextid'], $displayedimagefilerecord['component'],
                $displayedimagefilerecord['filearea'], $displayedimagefilerecord['itemid'],
                $displayedimagefilerecord['filepath'], $displayedimagefilerecord['filename'])) {
                // Delete old file.
                $oldfile->delete();
            }
        }
        // WebP version.
        if ($fs->file_exists($displayedimagefilerecord['contextid'], $displayedimagefilerecord['component'],
            $displayedimagefilerecord['filearea'], $displayedimagefilerecord['itemid'],
            $displayedimagefilerecord['filepath'], $displayedimagefilerecord['filename'].'.webp')) {
            /* This can happen with previous CONTRIB-4099 versions where it was possible for the backup file to
               have the 'gridimage' files too.  Therefore without this, then 'create_file_from_string' below will
               baulk as the file already exists.   Unfortunately has to be here as the restore mechanism restores
               the grid format data for the database and then the files.  And the Grid code is called at the 'data'
               stage. */
            if ($oldfile = $fs->get_file($displayedimagefilerecord['contextid'], $displayedimagefilerecord['component'],
                $displayedimagefilerecord['filearea'], $displayedimagefilerecord['itemid'],
                $displayedimagefilerecord['filepath'], $displayedimagefilerecord['filename'].'.webp')) {
                // Delete old file.
                $oldfile->delete();
            }
        }
    }

    public static function output_section_image($section, $sectionname, $sectionimage, $contextid, $thissection, $gridimagepath, $output, $iswebp) {
        $content = '';
        $alttext = isset($sectionimage->alttext) ? $sectionimage->alttext : '';

        if (is_object($sectionimage) && ($sectionimage->displayedimageindex > 0)) {
            $filename = $sectionimage->displayedimageindex . '_' . $sectionimage->image;
            if ($iswebp) {
                $filename .= '.webp';
            }
            $imgurl = \moodle_url::make_pluginfile_url(
                $contextid, 'course', 'section', $thissection->id, $gridimagepath,
                $filename
            );
            $content = \html_writer::empty_tag('img', array(
                'src' => $imgurl,
                'alt' => $alttext,
                'role' => 'img',
                'aria-label' => $sectionname));
        } else if ($section == 0) {
            $imgurl = $output->image_url('info', 'format_grid');
            $content = \html_writer::empty_tag('img', array(
                'src' => $imgurl,
                'alt' => $alttext,
                'class' => 'info',
                'role' => 'img',
                'aria-label' => $sectionname));
        }
        return $content;
    }

    public static function delete_image($sectionid, $contextid, $courseid) {
        $sectionimage = self::get_image($courseid, $sectionid);
        if ($sectionimage) {
            global $DB;
            if (!empty($sectionimage->image)) {
                $fs = get_file_storage();

                // Delete the image.
                if ($file = $fs->get_file($contextid, 'course', 'section', $sectionid, '/', $sectionimage->image)) {
                    $file->delete();
                    $DB->set_field('format_grid_icon', 'image', null, array('sectionid' => $sectionimage->sectionid));
                    // Delete the displayed image(s).
                    $gridimagepath = self::get_image_path();
                    if ($file = $fs->get_file($contextid, 'course', 'section', $sectionid, $gridimagepath,
                            $sectionimage->displayedimageindex . '_' . $sectionimage->image)) {
                        $file->delete();
                    }
                    if ($file = $fs->get_file($contextid, 'course', 'section', $sectionid, $gridimagepath,
                            $sectionimage->displayedimageindex . '_' . $sectionimage->image.'.webp')) {
                        $file->delete();
                    }
                }
            }
            $DB->delete_records("format_grid_icon", array('courseid' => $courseid,
                'sectionid' => $sectionimage->sectionid));
        }
    }

    public static function delete_images($courseid) {
        $sectionimages = self::get_images($courseid);

        if (is_array($sectionimages)) {
            global $CFG, $DB;

            require_once($CFG->dirroot . '/course/format/lib.php'); // For 'course_get_format()'.
            $courseformat = course_get_format($courseid);

            $contextid = \format_grid::get_contextid($courseformat);
            $fs = get_file_storage();
            $gridimagepath = self::get_image_path();

            foreach ($sectionimages as $sectionimage) {
                // Delete the image if there is one.
                if (!empty($sectionimage->image)) {
                    if ($file = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, '/',
                            $sectionimage->image)) {
                        $file->delete();
                        // Delete the displayed image(s).
                        if ($file = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, $gridimagepath,
                                $sectionimage->displayedimageindex . '_' . $sectionimage->image)) {
                            $file->delete();
                        }
                        if ($file = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, $gridimagepath,
                                $sectionimage->displayedimageindex . '_' . $sectionimage->image.'.webp')) {
                            $file->delete();
                        }
                    }
                }
            }
            $DB->delete_records("format_grid_icon", array('courseid' => $courseid));
        }
    }

    /**
     * Returns the RGB for the given hex.
     *
     * @param string $hex
     * @return array
     */
    public static function hex2rgb($hex) {
        if ($hex[0] == '#') {
            $hex = substr($hex, 1);
        }
        if (strlen($hex) == 3) {
            $r = substr($hex, 0, 1);
            $r .= $r;
            $g = substr($hex, 1, 1);
            $g .= $g;
            $b = substr($hex, 2, 1);
            $b .= $b;
        } else {
            $r = substr($hex, 0, 2);
            $g = substr($hex, 2, 2);
            $b = substr($hex, 4, 2);
        }
        return array('r' => hexdec($r), 'g' => hexdec($g), 'b' => hexdec($b));
    }
}
