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
 * JS module for the course homepage.
 *
 * @module      core_course/view
 * @copyright   2021 Jun Pataleta <jun@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import jQuery from 'jquery';
import log from 'core/log';

/**
 * Whether the event listener has already been registered for this module.
 *
 * @type {boolean}
 */
let registered = false;

/**
 * Function to intialise and register event listeners for this module.
 *
 * @param {array} sectionnumbers Show completion is on.
 */
export const init = (sectionnumbers) => {
    log.debug('Grid thegrid JS init');
    if (registered) {
        log.debug('Grid thegrid JS init already registered');
        return;
    } else {
        log.debug('Grid thegrid sectionnumbers ' + sectionnumbers);
    }
    registered = true;

    var currentsection = -1;
    var endsection = sectionnumbers.length - 1;

    jQuery(document).on('keydown', function(event) {
        if (event.which == 37) {
            // Left.
            event.preventDefault();
            if ((currentsection == -1) || (currentsection == 0)) {
                if (currentsection == 0) {
                    jQuery('#section-' + sectionnumbers[currentsection]).removeClass('grid-current-section');
                }
                currentsection = endsection;
                jQuery('#section-' + sectionnumbers[currentsection]).addClass('grid-current-section');
            } else {
                jQuery('#section-' + sectionnumbers[currentsection]).removeClass('grid-current-section');
                currentsection = currentsection - 1;
                jQuery('#section-' + sectionnumbers[currentsection]).addClass('grid-current-section');
            }
            log.debug("Left: " + sectionnumbers[currentsection]);
        } else if (event.which == 39) {
            // Right.
            event.preventDefault();
            if ((currentsection == -1) || (currentsection == endsection)) {
                if (currentsection == endsection) {
                    jQuery('#section-' + sectionnumbers[currentsection]).removeClass('grid-current-section');
                }
                currentsection = 0;
                jQuery('#section-' + sectionnumbers[currentsection]).addClass('grid-current-section');
            } else {
                jQuery('#section-' + sectionnumbers[currentsection]).removeClass('grid-current-section');
                currentsection = currentsection + 1;
                jQuery('#section-' + sectionnumbers[currentsection]).addClass('grid-current-section');
            }
            log.debug("Right: " + sectionnumbers[currentsection]);
        } else if ((event.which == 13) || (event.which == 27)) {
            event.preventDefault();
            //var trigger = jQuery(event.currentTarget);
            //currentsection = trigger.data('section');
            jQuery('#gridPopup').modal('show');
        }
    });
};
