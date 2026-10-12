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

namespace report_solentconnect_badges;

use stdClass;

/**
 * Class observers
 *
 * @package    report_solentconnect_badges
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observers {
    /**
     * Add record to report_solentconnect_badges table for processing if it belongs to a watched course.
     *
     * @param \core\event\badge_awarded $event
     * @return void
     */
    public static function badge_issued(\core\event\badge_awarded $event) {
        global $DB;
        $issuedbadge = $DB->get_record('badge_issued', ['id' => $event->other['badgeissuedid']]);
        $badge = $DB->get_record('badge', ['id' => $issuedbadge->badgeid]);
        $courseids = get_config('report_solentconnect_badges', 'courseids');
        $courseids = array_map('trim', explode(',', $courseids));
        if (in_array($badge->courseid, $courseids)) {
            $record = new stdClass();
            $record->badgeissuedid = $event->other['badgeissuedid'];
            $record->timetransferred = 0;
            if (!$DB->record_exists('report_solentconnect_badges', ['badgeissuedid' => $event->other['badgeissuedid']])) {
                $DB->insert_record('report_solentconnect_badges', $record);
            }
        }
    }
}
