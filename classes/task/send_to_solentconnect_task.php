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

namespace report_solentconnect_badges\task;

use core\task\scheduled_task;

/**
 * Class send_to_solentconnect_task
 *
 * @package    report_solentconnect_badges
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_to_solentconnect_task extends scheduled_task {
    /**
     * Returns the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendtosolentconnecttask', 'report_solentconnect_badges');
    }

    /**
     * Executes the task.
     */
    public function execute() {
        global $DB;
        if (!get_config('report_solentconnect_badges', 'ftp_enabled')) {
            return;
        }
        // Has ftp been set up? If not, nothing to do.
        $ftphost = get_config('report_solentconnect_badges', 'ftp_host');
        $ftpuser = get_config('report_solentconnect_badges', 'ftp_user');
        $ftppass = get_config('report_solentconnect_badges', 'ftp_pass');
        $companycode = get_config('report_solentconnect_badges', 'accreditationinstitutioncompanycode');
        $accreditationcode = get_config('report_solentconnect_badges', 'accreditationcode');
        if (empty($ftphost) || empty($ftpuser) || empty($ftppass) || empty($companycode) || empty($accreditationcode)) {
            mtrace('FTP configuration is incomplete.');
            return;
        }
        // Check SSH2 SFTP is installed on the server.
        if (!function_exists('ssh2_connect')) {
            mtrace('SSH2 SFTP is not installed.');
            return;
        }
        // Are there any records not yet transferred to SolentConnect? Max at 500.
        $records = $DB->get_records_sql(
            "SELECT bi.id, u.idnumber, u.email, bi.dateissued, bi.dateexpire, b.name, scb.id scbid
            FROM {report_solentconnect_badges} scb
            JOIN {badge_issued} bi ON bi.id = scb.badgeissuedid
            JOIN {badge} b ON b.id = bi.badgeid
            JOIN {user} u ON u.id = bi.userid
            JOIN {course} c ON c.id = b.courseid
            WHERE scb.timeexported = 0",
            0,
            500
        );
        if (empty($records)) {
            mtrace('No badges to transfer to SolentConnect.');
            return;
        }
        $fields = \report_solentconnect_badges\helper::get_csv_field_definitions();
        // Convert the records to csv file.
        $csvfile = tempnam(sys_get_temp_dir(), 'solentconnect_badges_');
        $fp = fopen($csvfile, 'w');
        fputcsv(
            stream: $fp,
            fields: array_keys($fields),
            escape: '\\'
        );
        $count = 0;
        $inparams = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($fields as $field) {
                if ($field['type'] == 'date') {
                    $formatted = date($field['format'], $record->{$field['field']});
                    $row[$field['field']] = $formatted;
                } else if (isset($field['value'])) {
                    // Hardcoded value.
                    $row[$field['field']] = $field['value'];
                } else {
                    $row[$field['field']] = $record->{$field['field']} ?? '';
                }
            }
            if (!empty($row)) {
                fputcsv(
                    stream: $fp,
                    fields: $row,
                    escape: '\\'
                );
                $count++;
                $inparams[] = $record->scbid;
            }
        }
        fclose($fp);
        // Transfer the CSV file to SolentConnect via SFTP (SSH2).
        $sftpconn = \ssh2_connect($ftphost);
        if ($sftpconn && \ssh2_auth_password($sftpconn, $ftpuser, $ftppass)) {
            $sftp = \ssh2_sftp($sftpconn);
            $remotefile = "ssh2.sftp://$sftp/" . basename($csvfile) . '.csv';
            if (\ssh2_scp_send($sftpconn, $csvfile, $remotefile)) {
                mtrace("Transferred $count badges to SolentConnect.");
                // Mark the records as exported.
                if (!empty($inparams)) {
                    [$insql, $inparams] = $DB->get_in_or_equal($inparams, SQL_PARAMS_NAMED);
                    $DB->execute(
                        "UPDATE {report_solentconnect_badges} SET timeexported = :timeexported WHERE id $insql",
                        array_merge(
                            ['timeexported' => time()],
                            $inparams
                        )
                    );
                }
            } else {
                mtrace('Failed to transfer CSV file to SolentConnect via SFTP.');
            }
        } else {
            mtrace('Failed to connect to SolentConnect SFTP server.');
        }
        // Delete the temporary CSV file.
        unlink($csvfile);
    }
}
