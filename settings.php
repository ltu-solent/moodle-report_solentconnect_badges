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
 * Settings for SolentConnect Badges report
 *
 * @package    report_solentconnect_badges
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\lang_string;
use core\url;

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new admin_externalpage(
    'reportsolentconnect_badges/index',
    get_string('badgelist', 'report_solentconnect_badges'),
    new url("/report/solentconnect_badges/index.php")
));

$settings = new admin_settingpage('report_solentconnect_badges_settings', get_string('pluginname', 'report_solentconnect_badges'));
if ($ADMIN->fulltree) {
    // Enter a comma separated list of course IDs to watch for badge awards.
    $settings->add(
        new admin_setting_configtext(
            'report_solentconnect_badges/courseids',
            new lang_string('courseids', 'report_solentconnect_badges'),
            new lang_string('courseids_desc', 'report_solentconnect_badges'),
            ''
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'report_solentconnect_badges/accreditationcode',
            new lang_string('accreditationcode', 'report_solentconnect_badges'),
            new lang_string('accreditationcode_desc', 'report_solentconnect_badges'),
            ''
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'report_solentconnect_badges/accreditationinstitutioncompanycode',
            new lang_string('accreditationinstitutioncompanycode', 'report_solentconnect_badges'),
            new lang_string('accreditationinstitutioncompanycode_desc', 'report_solentconnect_badges'),
            ''
        )
    );
    $settings->add(
        new admin_setting_heading(
            'report_solentconnect_badges/ftp',
            new lang_string('ftpsettings', 'report_solentconnect_badges'),
            new lang_string('ftpsettings_desc', 'report_solentconnect_badges')
        )
    );

    // Enable FTP.
    $settings->add(
        new admin_setting_configcheckbox(
            'report_solentconnect_badges/ftp_enabled',
            new lang_string('ftp_enabled', 'report_solentconnect_badges'),
            new lang_string('ftp_enabled_desc', 'report_solentconnect_badges'),
            0
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'report_solentconnect_badges/ftp_debug',
            new lang_string('ftp_debug', 'report_solentconnect_badges'),
            new lang_string('ftp_debug_desc', 'report_solentconnect_badges'),
            0
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'report_solentconnect_badges/ftp_host',
            new lang_string('ftp_host', 'report_solentconnect_badges'),
            new lang_string('ftp_host_desc', 'report_solentconnect_badges'),
            ''
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'report_solentconnect_badges/ftp_user',
            new lang_string('ftp_user', 'report_solentconnect_badges'),
            new lang_string('ftp_user_desc', 'report_solentconnect_badges'),
            ''
        )
    );
    $settings->add(
        new admin_setting_configpasswordunmask(
            'report_solentconnect_badges/ftp_pass',
            new lang_string('ftp_pass', 'report_solentconnect_badges'),
            new lang_string('ftp_pass_desc', 'report_solentconnect_badges'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'report_solentconnect_badges/ftp_port',
            new lang_string('ftp_port', 'report_solentconnect_badges'),
            new lang_string('ftp_port_desc', 'report_solentconnect_badges'),
            '22'
        )
    );
}
