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
        global $CFG, $DB;
        if (!get_config('report_solentconnect_badges', 'ftp_enabled')) {
            return;
        }
        // Has ftp been set up? If not, nothing to do.
        $ftphost = get_config('report_solentconnect_badges', 'ftp_host');
        $ftpuser = get_config('report_solentconnect_badges', 'ftp_user');
        $ftppass = get_config('report_solentconnect_badges', 'ftp_pass');
        $ftpport = get_config('report_solentconnect_badges', 'ftp_port') ?? 22;
        $ftpdebug = get_config('report_solentconnect_badges', 'ftp_debug');
        $companycode = get_config('report_solentconnect_badges', 'accreditationinstitutioncompanycode');
        $accreditationcode = get_config('report_solentconnect_badges', 'accreditationcode');
        if (empty($ftphost) || empty($ftpuser) || empty($ftppass) || empty($companycode) || empty($accreditationcode)) {
            mtrace('FTP configuration is incomplete.');
            return;
        }
        if (!class_exists('phpseclib4\Net\SFTP')) {
            require_once($CFG->dirroot . '/report/solentconnect_badges/vendor/autoload.php');
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
            [],
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
            foreach ($fields as $key => $field) {
                if ($field['type'] == 'date') {
                    $formatted = date($field['format'], $record->{$field['field']});
                    $row[$key] = $formatted;
                } else if (isset($field['value'])) {
                    // Hardcoded value.
                    $row[$key] = $field['value'];
                } else {
                    $row[$key] = $record->{$field['field']} ?? '';
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
        // Transfer the CSV file to SolentConnect via SFTP (phpseclib).
        if ($ftpdebug) {
            define('NET_SSH2_LOGGING', \phpseclib4\Net\SSH2::LOG_SIMPLE);
        }
        $sftpconn = new \phpseclib4\Net\SFTP($ftphost, $ftpport);
        $errors = [];
        $logs = [];
        if (!$sftpconn) {
            mtrace('Failed to initialize SFTP connection to SolentConnect.');
            return;
        }
        try {
            if (!$sftpconn->login($ftpuser, $ftppass)) {
                mtrace('Failed to connect to SolentConnect SFTP server.');
                $errors = $sftpconn->getErrors();
                if ($ftpdebug) {
                    $logs = $sftpconn->getLog();
                }
                $this->handleerrors($errors, $logs);
                return;
            }
        } catch (\Exception $e) {
            mtrace('Exception occurred while connecting to SolentConnect SFTP server: ' . $e->getMessage());
            $this->handleerrors([$e->getMessage()], $ftpdebug ? $sftpconn->getLog() : []);
            return;
        }

        $remotefile = basename($csvfile) . '.csv';
        $sftpconn->put($remotefile, $csvfile, \phpseclib4\Net\SFTP::SOURCE_LOCAL_FILE);
        // Delete the temporary CSV file.
        unlink($csvfile);
        if (!$sftpconn->file_exists($remotefile)) {
            mtrace('Failed to transfer CSV file to SolentConnect via SFTP.');
            $errors = $sftpconn->getErrors();
            if ($ftpdebug) {
                $logs = $sftpconn->getLog();
            }
            $this->handleerrors($errors, $logs);
            return;
        }

        mtrace("Transferred $count badges to SolentConnect in the file $remotefile.");
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
    }

    /**
     * Output error and debug info
     *
     * @param array $errors
     * @param array $logs
     * @return void
     */
    private function handleerrors(array $errors, array $logs) {
        if (!empty($errors)) {
            foreach ($errors as $error) {
                mtrace("SFTP error: $error");
            }
        }
        if (!empty($logs)) {
            foreach ($logs as $entry) {
                mtrace("SFTP log entry: $entry");
            }
        }
    }
}
