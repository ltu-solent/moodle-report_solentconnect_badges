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

namespace report_solentconnect_badges\form;

use core\lang_string;
use moodleform;
use user_filter_date;
use user_filter_simpleselect;
use user_filter_text;


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/filters/lib.php');

/**
 * Class badge_filter
 *
 * @package    report_solentconnect_badges
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badge_filter extends moodleform {
    /**
     * Filter badges
     *
     * @return void
     */
    public function definition() {
        global $DB;
        $mform = $this->_form;

        // Add your form elements here.
        $mform->addElement('checkbox', 'showqueuedonly', get_string('showqueuedonly', 'report_solentconnect_badges'));
        $mform->addHelpButton('showqueuedonly', 'showqueuedonly', 'report_solentconnect_badges');
        $mform->setDefault('showqueuedonly', 1);
        $mform->hideIf('exporteddate_grp', 'showqueuedonly', 'checked');
        $badgechoices = \report_solentconnect_badges\helper::get_badges_menu();
        $filters = [
            'exporteddate' => new user_filter_date(
                'exporteddate',
                new lang_string('exporteddate', 'report_solentconnect_badges'),
                false,
                'scb.timeexported'
            ),
            'badgeissued' => new user_filter_date(
                'badgeissued',
                new lang_string('badgeissued', 'report_solentconnect_badges'),
                false,
                'bi.dateissued'
            ),
            'badgeexpire' => new user_filter_date(
                'badgeexpire',
                new lang_string('badgeexpire', 'report_solentconnect_badges'),
                false,
                'bi.dateexpire'
            ),
            'recipient' => new user_filter_text(
                'recipient',
                new lang_string('recipient', 'report_solentconnect_badges'),
                false,
                $DB->sql_fullname()
            ),
            'badge' => new user_filter_simpleselect(
                'badge',
                new lang_string('badge', 'report_solentconnect_badges'),
                false,
                'bid',
                $badgechoices
            ),
        ];

        foreach ($filters as $filter) {
            $filter->setupForm($mform);
        }

        $this->add_action_buttons(false, get_string('search', 'report_solentconnect_badges'));
    }
}
