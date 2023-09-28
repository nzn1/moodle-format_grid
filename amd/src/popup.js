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
 * @param {boolean} showcompletion Show completion is on.
 */
export const init = (showcompletion) => {
    log.debug('Grid popup JS init');
    if (registered) {
        log.debug('Grid popup JS init already registered');
        return;
    }
    // Listen for toggled manual completion states of activities.
    document.addEventListener(CourseEvents.manualCompletionToggled, () => {
        mctFired = true;
    });
    registered = true;

    // To pass the current section when using keyboard control.
    var currentsection = null;

    jQuery('#gridPopup').on('show.bs.modal', function(event) {
        var section = currentsection;
        if (section === null) {
            var trigger = jQuery(event.relatedTarget);
            section = trigger.data('section');
        }

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
        if (currentsection !== null) {
            currentsection = null;
        }
        jQuery('.gridcarousel-item').removeClass('active');
        if (showcompletion && mctFired) {
            mctFired = false;
            window.location.reload();
        }
    });

    jQuery(".grid-section .grid-modal").on('keydown', function (event) {
        if ((event.which == 13) || (event.which == 27)) {
            event.preventDefault();
            var trigger = jQuery(event.currentTarget);
            currentsection = trigger.data('section');
            jQuery('#gridPopup').modal('show');
        }
    });

    jQuery("#gridPopup").on('keydown', function(event) {
        if (event.which == 39) {
            event.preventDefault();
            jQuery('#gridPopupCarouselRight').trigger('click');
        }

        else if (event.which == 37) {
            event.preventDefault();
            jQuery('#gridPopupCarouselLeft').trigger('click');
        }
    });
};
