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
 * JS module for the grid.
 *
 * @module      format_grid/thegrid
 * @copyright   &copy; 2023-onwards G J Barnard.
 * @author      G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as CourseEvents from 'core_course/events';
import jQuery from 'jquery';
import log from 'core/log';

/**
 * Whether the event listener has already been registered for this module.
 *
 * @type {boolean}
 */
let registered = false;

/**
 * If the manualCompletionToggled event has fired.
 *
 * @type {boolean}
 */
let mctFired = false;

/**
 * Function to intialise and register event listeners for this module.
 *
 * @param {array} sectionnumbers Show completion is on.
 * @param {boolean} showcompletion Show completion is on.
 */
export const init = (sectionnumbers, showcompletion) => {
    log.debug('Grid thegrid JS init');
    if (registered) {
        log.debug('Grid thegrid JS init already registered');
        return;
    } else {
        log.debug('Grid thegrid sectionnumbers ' + sectionnumbers);
    }
    // Listen for toggled manual completion states of activities.
    document.addEventListener(CourseEvents.manualCompletionToggled, () => {
        mctFired = true;
    });
    registered = true;

    // Grid current section.
    var currentsection = -1;
    var endsection = sectionnumbers.length - 1;

    // Modal.
    var currentmodalsection = null;
    var modalshown = false;

    jQuery('#gridPopup').on('show.bs.modal', function(event) {
        var section = currentmodalsection;
        if (section === null) {
            var trigger = jQuery(event.relatedTarget);
            section = trigger.data('section');
        }
        currentsection = section - 1;

        var gml = jQuery('#gridPopupLabel');
        var triggersectionname = jQuery('#gridpopupsection-' + section).data('sectiontitle');
        gml.text(triggersectionname);

        var modal = jQuery(this);
        modal.find('#gridpopupsection-' + section).addClass('active');

        jQuery('#gridPopupCarousel').on('slid.bs.carousel', function() {
            var sno = jQuery('.gridcarousel-item.active').data('sectiontitle');
            gml.text(sno);
        });
    });

    jQuery('#gridPopup').on('hidden.bs.modal', function() {
        if (currentmodalsection !== null) {
            currentmodalsection = null;
        }
        jQuery('.gridcarousel-item').removeClass('active');
        if (showcompletion && mctFired) {
            mctFired = false;
            window.location.reload();
        }
        modalshown = false;
    });

    jQuery(".grid-section .grid-modal").on('keydown', function (event) {
        if ((event.which == 13) || (event.which == 27)) {
            event.preventDefault();
            var trigger = jQuery(event.currentTarget);
            currentmodalsection = trigger.data('section');
            modalshown = true;
            jQuery('#gridPopup').modal('show');
        }
    });

    jQuery("#gridPopup").on('keydown', function(event) {
        if (event.which == 37) {
            // Left.
            event.preventDefault();
            jQuery('#gridPopupCarouselLeft').trigger('click');
        }
        else if (event.which == 39) {
            // Right.
            event.preventDefault();
            jQuery('#gridPopupCarouselRight').trigger('click');
        }
    });

    jQuery(document).on('keydown', function (event) {
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
            if ((currentsection !== -1) && (!modalshown)) {
                currentmodalsection = sectionnumbers[currentsection];
            }
            modalshown = true;
            jQuery('#gridPopup').modal('show');
        }
    });
};
