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
 * External functions and service declaration for SolentConnect Badges
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/external/description}
 *
 * @package    report_solentconnect_badges
 * @category   webservice
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'report_solentconnect_badges_update_badges' => [
        'classname'   => report_solentconnect_badges\external\update_badges::class,
        'description' => 'Update SolentConnect exported time.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'report_solentconnect_badges_delete_badges' => [
        'classname'   => report_solentconnect_badges\external\delete_badges::class,
        'description' => 'Delete SolentConnect badges.',
        'type'        => 'write',
        'ajax'        => true,
    ],
];

$services = [
];
