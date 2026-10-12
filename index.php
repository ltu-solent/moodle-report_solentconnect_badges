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
 * Display SolentConnect Badges report
 *
 * @package    report_solentconnect_badges
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$action = optional_param('action', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

admin_externalpage_setup('reportsolentconnect_badges/index', '', null, '', ['pagelayout' => 'report']);

$filterform = new \report_solentconnect_badges\form\badge_filter();
$formdata = null;
$table = null;
if ($formdata = $filterform->get_data()) {
    $table = new \report_solentconnect_badges\table\issuedbadges_table('issuedbadges', $formdata, $download);
} else {
    $formdata = new stdClass();

    $formdata->exporteddate_sdt = optional_param('exporteddate_sdt', 0, PARAM_INT);
    $formdata->exporteddate_edt = optional_param('exporteddate_edt', 0, PARAM_INT);
    if ($formdata->exporteddate_sdt > 0 || $formdata->exporteddate_edt > 0) {
        $formdata->showqueuedonly = 0;
    } else {
        $formdata->showqueuedonly = 1;
    }
    $formdata->badgeissued_sdt = optional_param('badgeissued_sdt', 0, PARAM_INT);
    $formdata->badgeissued_edt = optional_param('badgeissued_edt', 0, PARAM_INT);
    $formdata->badgeexpire_sdt = optional_param('badgeexpire_sdt', 0, PARAM_INT);
    $formdata->badgeexpire_edt = optional_param('badgeexpire_edt', 0, PARAM_INT);
    $formdata->recipient = optional_param('recipient', '', PARAM_TEXT);
    $formdata->recipient_op = optional_param('recipient_op', 'contains', PARAM_ALPHA);
    $formdata->badge = optional_param('badge', 0, PARAM_INT);
    $filterform->set_data($formdata);
    $table = new \report_solentconnect_badges\table\issuedbadges_table('issuedbadges', $formdata, $download);
}

if ($table->is_downloading()) {
    $table->download();
    exit;
}

if ($confirm = optional_param('confirm', 0, PARAM_BOOL)) {
    $scbid = required_param('scbid', PARAM_INT);
    if (!$DB->record_exists('report_solentconnect_badges', ['id' => $scbid])) {
        throw new \core\exception\moodle_exception('invalidrecord', 'report_solentconnect_badges');
    }
    if ($confirm && confirm_sesskey() && in_array($action, ['delete', 'requeue', 'markexported'])) {
        $message = '';
        if ($action === 'delete') {
            $message = get_string('solentconnectbadgedeleted', 'report_solentconnect_badges');
            $DB->delete_records('report_solentconnect_badges', ['id' => $scbid]);
        } else if ($action === 'requeue') {
            $message = get_string('requeuedsuccessfully', 'report_solentconnect_badges', (object)['count' => 1]);
            $DB->update_record('report_solentconnect_badges', ['id' => $scbid, 'timeexported' => 0]);
        } else if ($action === 'markexported') {
            $message = get_string('markedexportedsuccessfully', 'report_solentconnect_badges', (object)['count' => 1]);
            $DB->update_record('report_solentconnect_badges', ['id' => $scbid, 'timeexported' => time()]);
        }
        redirect(
            new url('/report/solentconnect_badges/index.php'),
            $message,
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}



echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_solentconnect_badges'));

if (in_array($action, ['delete', 'requeue', 'markexported'])) {
    $scbid = required_param('scbid', PARAM_INT);
    $confirmurl = new url(
        '/report/solentconnect_badges/index.php',
        [
            'action' => $action,
            'scbid' => $scbid,
            'confirm' => 1,
            'sesskey' => sesskey(),
        ]
    );
    echo $OUTPUT->confirm(
        get_string('confirm' . $action, 'report_solentconnect_badges'),
        $confirmurl,
        new url('/report/solentconnect_badges/index.php')
    );
    echo $OUTPUT->footer();
    exit; // Ensure no further output is sent after the confirmation page.
}


$filterform->display();

$table->out(50, true);
$PAGE->requires->js_call_amd('report_solentconnect_badges/badge_bulk_actions', 'init');


echo $OUTPUT->footer();
