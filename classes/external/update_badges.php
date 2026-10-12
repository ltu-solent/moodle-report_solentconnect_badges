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

namespace report_solentconnect_badges\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_value;
use core_external\external_description;

/**
 * Class update_badges
 *
 * @package    report_solentconnect_badges
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_badges extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'scbids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'SolentConnect badge ID'),
                'List of SolentConnect badge IDs to mark as exported'
            ),
            'timeexported' => new external_value(PARAM_INT, 'Timestamp when the badge was exported'),
        ]);
    }

    /**
     * Execute update
     *
     * @param array $scbids
     * @param int $timeexported
     * @return void
     */
    public static function execute(array $scbids, int $timeexported) {
        global $DB;
        require_capability('report/solentconnect_badges:manage', \core\context\system::instance());
        $params = self::validate_parameters(
            self::execute_parameters(),
            ['scbids' => $scbids, 'timeexported' => $timeexported]
        );
        [$insql, $inparams] = $DB->get_in_or_equal($params['scbids'], SQL_PARAMS_NAMED);
        $DB->execute(
            "UPDATE {report_solentconnect_badges} SET timeexported = :timeexported WHERE id $insql",
            array_merge(
                ['timeexported' => $params['timeexported']],
                $inparams
            )
        );
    }

    /**
     * Returns result method value
     *
     * @return null
     */
    public static function execute_returns(): ?external_description {
        return null;
    }
}
