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

/**
 * Class helper
 *
 * @package    report_solentconnect_badges
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * CSV field definitions from SQL
     *
     * @return array The CSV field definitions.
     */
    public static function get_csv_field_definitions(): array {
        $companycode = get_config('report_solentconnect_badges', 'accreditationinstitutioncompanycode');
        $fields = [
            'LineType' => [
                'value' => 'A',
                'type' => 'string',
            ],
            'EmployeeId' => [
                'field' => 'idnumber',
                'type' => 'string',
            ],
            'Email' => [
                'field' => 'email',
                'type' => 'string',
            ],
            'AccreditationCode' => [
                'value' => get_config('report_solentconnect_badges', 'accreditationcode'),
                'type' => 'string',
            ],
            'AccreditationIssuedDate' => [
                'field' => 'dateissued',
                'type' => 'date',
                'format' => 'd/m/Y',
            ],
            'AccreditationExpiryDate' => [
                'field' => 'dateexpire',
                'type' => 'date',
                'format' => 'd/m/Y',
            ],
            'AccreditationYearIssued' => [
                'field' => 'dateissued',
                'type' => 'date',
                'format' => 'Y',
            ],
            'AccreditationInstitutionCompanyCode' => [
                'value' => $companycode,
                'type' => 'string',
            ],
            'AccreditationComments' => [
                'field' => 'name',
                'type' => 'string',
            ],
            'AccreditationImportAction' => [
                'value' => 'A',
                'type' => 'string',
            ],

        ];
        return $fields;
    }

    /**
     * List of distinct badge names that have been queued.
     *
     * @return array
     */
    public static function get_badges_menu(): array {
        global $DB;

        $badges = $DB->get_records_sql_menu(
            "SELECT b.id bid, b.name
                FROM {report_solentconnect_badges} scb
                JOIN {badge_issued} bi ON bi.id = scb.badgeissuedid
                JOIN {badge} b ON b.id = bi.badgeid
                GROUP BY b.id
                ORDER BY b.name ASC"
        );
        return $badges;
    }
}
