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

namespace report_solentconnect_badges\table;

use core\lang_string;
use core\output\action_menu;
use core\output\action_menu\link_secondary;
use core\output\checkbox_toggleall;
use core\output\pix_icon;
use core\url;
use core_table\sql_table;
use stdClass;
use user_filter_text;

/**
 * Class issuedbadges
 *
 * @package    report_solentconnect_badges
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class issuedbadges_table extends sql_table {
    /**
     * Export fields
     *
     * @var array
     */
    private array $fields = [];

    /**
     * Issied Badges constructor
     *
     * @param string $uniqueid
     * @param stdClass $filter
     * @param string $downloadformat
     */
    public function __construct(string $uniqueid, stdClass $filter, string $downloadformat = '') {
        global $DB, $OUTPUT;
        parent::__construct($uniqueid);
        require_capability('report/solentconnect_badges:manage', \core\context\system::instance());
        $this->set_attribute('id', 'report_solentconnect_badges_table');

        $this->downloadable = true;
        $sheetname = clean_filename('issued_badges-' . date('Y-m-d-H-i-s'));
        $this->is_downloading($downloadformat, $sheetname, get_string('issuedbadges', 'report_solentconnect_badges'));
        $this->show_download_buttons_at([TABLE_P_BOTTOM]);

        $this->fields = \report_solentconnect_badges\helper::get_csv_field_definitions();
        $columns = [];
        if (!$this->is_downloading()) {
            $mastercheckbox = new checkbox_toggleall(
                $uniqueid,
                true,
                [
                    'id' => 'select-all-badges',
                    'name' => 'select-all-badges',
                    'label' => get_string('selectall'),
                    'labelclasses' => 'sr-only',
                    'classes' => 'm-1',
                    'checked' => false,
                ]
            );
            $columns['select'] = $OUTPUT->render($mastercheckbox);
            $this->useridfield = 'userid';
            $columns['fullname'] = new lang_string('recipient', 'report_solentconnect_badges');
        }
        foreach ($this->fields as $key => $notused) {
            $columns[strtolower($key)] = $key;
        }
        if (!$this->is_downloading()) {
            $columns['timeexported'] = new lang_string('timeexported', 'report_solentconnect_badges');
            $columns['actions'] = new lang_string('actions');
        }
        $this->define_columns(array_keys($columns));
        $this->define_headers(array_values($columns));
        $urlparams = (array) $filter;
        $this->define_baseurl(new url('/report/solentconnect_badges/index.php', $urlparams));

        $userfieldsapi = \core_user\fields::for_name();
        $userfieldssql = $userfieldsapi->get_sql('u', false, '', '', false)->selects;
        $select = "bi.id biid, u.idnumber, u.email, bi.userid, bi.dateissued, bi.dateexpire, b.name, b.id bid,
            scb.id scbid, scb.timeexported, {$userfieldssql}";
        $from = "{report_solentconnect_badges} scb
            JOIN {badge_issued} bi ON bi.id = scb.badgeissuedid
            JOIN {badge} b ON b.id = bi.badgeid
            JOIN {user} u ON u.id = bi.userid
            JOIN {course} c ON c.id = b.courseid";

        $params = [];
        $wheres = [];
        if (isset($filter->showqueuedonly) && $filter->showqueuedonly) {
            $wheres[] = 'scb.timeexported = 0';
        }
        if (isset($filter->exporteddate_sdt) && $filter->exporteddate_sdt > 0) {
            $wheres[] = 'scb.timeexported >= :exporteddate_sdt';
            $params['exporteddate_sdt'] = $filter->exporteddate_sdt;
        }
        if (isset($filter->exporteddate_edt) && $filter->exporteddate_edt > 0) {
            if (!isset($filter->exporteddate_sdt) || $filter->exporteddate_sdt == 0) {
                $wheres[] = 'scb.timeexported > 0';
            }
            $wheres[] = 'scb.timeexported <= :exporteddate_edt';
            $params['exporteddate_edt'] = $filter->exporteddate_edt;
        }
        if (isset($filter->badgeissued_sdt) && $filter->badgeissued_sdt > 0) {
            $wheres[] = 'bi.dateissued >= :badgeissued_sdt';
            $params['badgeissued_sdt'] = $filter->badgeissued_sdt;
        }
        if (isset($filter->badgeissued_edt) && $filter->badgeissued_edt > 0) {
            $wheres[] = 'bi.dateissued <= :badgeissued_edt';
            $params['badgeissued_edt'] = $filter->badgeissued_edt;
        }
        if (isset($filter->badgeexpire_sdt) && $filter->badgeexpire_sdt > 0) {
            $wheres[] = 'bi.dateexpire >= :badgeexpire_sdt';
            $params['badgeexpire_sdt'] = $filter->badgeexpire_sdt;
        }
        if (isset($filter->badgeexpire_edt) && $filter->badgeexpire_edt > 0) {
            $wheres[] = 'bi.dateexpire <= :badgeexpire_edt';
            $params['badgeexpire_edt'] = $filter->badgeexpire_edt;
        }
        if (isset($filter->recipient) && !empty($filter->recipient)) {
            $recipient = new user_filter_text(
                'recipient',
                new lang_string('recipient', 'report_solentconnect_badges'),
                false,
                $DB->sql_fullname()
            );
            [$recipsql, $recipparams] = $recipient->get_sql_filter([
                'operator' => $filter->recipient_op,
                'value' => $filter->recipient,
            ]);
            $wheres[] = $recipsql;
            $params = array_merge($params, $recipparams);
        }
        if (isset($filter->badge) && !empty($filter->badge)) {
            $wheres[] = 'bi.badgeid = :badge';
            $params['badge'] = $filter->badge;
        }

        if (count($wheres) > 0) {
            $where = implode(' AND ', $wheres);
        } else {
            $where = '1=1';
        }
        $this->set_sql($select, $from, $where, $params);

        $this->collapsible(false);
        $this->no_sorting('accreditationcode');
        $this->no_sorting('accreditationimportaction');
        $this->no_sorting('accreditationinstitutioncompanycode');
        $this->no_sorting('actions');
        $this->no_sorting('linetype');
        $this->no_sorting('select');
    }

    /**
     * Accreditation code
     *
     * @param stdClass $row
     * @return string
     */
    public function col_accreditationcode($row): string {
        return $this->fields['AccreditationCode']['value'];
    }
    /**
     * Badge name
     *
     * @param stdClass $row
     * @return string
     */
    public function col_accreditationcomments($row): string {
        return s($row->name);
    }

    /**
     * Accreditation Expiry Date
     *
     * @param stdClass $row
     * @return string
     */
    public function col_accreditationexpirydate($row): string {
        return date($this->fields['AccreditationExpiryDate']['format'], $row->dateexpire);
    }

    /**
     * Import action
     *
     * @param stdClass $row
     * @return string
     */
    public function col_accreditationimportaction($row): string {
        return $this->fields['AccreditationImportAction']['value'];
    }

    /**
     * Company code
     *
     * @param stdClass $row
     * @return string
     */
    public function col_accreditationinstitutioncompanycode($row): string {
        return $this->fields['AccreditationInstitutionCompanyCode']['value'];
    }

    /**
     * Accreditation Issued Date
     *
     * @param stdClass $row
     * @return string
     */
    public function col_accreditationissueddate($row): string {
        return date($this->fields['AccreditationIssuedDate']['format'], $row->dateissued);
    }

    /**
     * Accreditation Year Issued Date
     *
     * @param stdClass $row
     * @return string
     */
    public function col_accreditationyearissued($row): string {
        return date($this->fields['AccreditationYearIssued']['format'], $row->dateissued);
    }

    /**
     * Actions if not downloading
     *
     * @param stdClass $row The current row object.
     * @return string Action links html
     */
    public function col_actions($row): string {
        global $OUTPUT;
        $actions = [];
        $actions['delete'] = new link_secondary(
            new url(
                '/report/solentconnect_badges/index.php',
                [
                    'action' => 'delete',
                    'scbid' => $row->scbid,
                    'sesskey' => sesskey(),
                ]
            ),
            new pix_icon('t/delete', get_string('delete')),
            get_string('delete', 'report_solentconnect_badges'),
            ['class' => 'text-danger']
        );
        $actions['requeue'] = new link_secondary(
            new url(
                '/report/solentconnect_badges/index.php',
                [
                    'action' => 'requeue',
                    'scbid' => $row->scbid,
                    'sesskey' => sesskey(),
                ]
            ),
            new pix_icon('t/reload', get_string('requeue', 'report_solentconnect_badges')),
            get_string('requeue', 'report_solentconnect_badges'),
        );
        $actions['markexported'] = new link_secondary(
            new url(
                '/report/solentconnect_badges/index.php',
                [
                    'action' => 'markexported',
                    'scbid' => $row->scbid,
                    'sesskey' => sesskey(),
                ]
            ),
            new pix_icon('i/export', get_string('markexported', 'report_solentconnect_badges')),
            get_string('markexported', 'report_solentconnect_badges'),
        );
        $actionmenu = new action_menu($actions);
        $actionmenu->set_menu_trigger(get_string('actions'));
        // This ensures the menu opens without the table showing scrollbars.
        $actionmenu->set_boundary('window');
        return $OUTPUT->render($actionmenu);
    }

    /**
     * Employee ID
     *
     * @param stdClass $row
     * @return string
     */
    public function col_employeeid($row): string {
        return $row->idnumber;
    }

    /**
     * LineType
     *
     * @param stdClass $row
     * @return string
     */
    public function col_linetype($row): string {
        return $this->fields['LineType']['value'];
    }

    /**
     * Select checkbox column
     *
     * @param stdClass $row
     * @return string
     */
    public function col_select($row): string {
        global $OUTPUT;
        $name = 'badges' . $row->biid;
        $checkbox = new checkbox_toggleall(
            $this->uniqueid,
            false,
            [
                'classes' => 'badgecheckbox m-1',
                'id' => $name,
                'name' => $name,
                'checked' => false,
                'label' => get_string('selectitem', 'report_solentconnect_badges', $row),
                'labelclasses' => 'accesshide',
                'value' => $row->scbid,
            ]
        );
        return $OUTPUT->render($checkbox);
    }

    /**
     * Time exported
     *
     * @param stdClass $row
     * @return string
     */
    public function col_timeexported($row): string {
        if ($row->timeexported > 0) {
            return date('Y-m-d H:i:s', $row->timeexported);
        }
        return '-';
    }

    /**
     * Download table
     *
     * @return void
     */
    public function download() {
        unset($this->columns['select']);
        unset($this->columns['actions']);
        unset($this->columns['timeexported']);
        \core\session\manager::write_close();
        $this->out(0, false);
        exit();
    }

    /**
     * Wrap up the HTML for the table, including bulk action menu if applicable.
     */
    public function wrap_html_finish(): void {
        global $OUTPUT;
        $data = new stdClass();
        $data->showbulkactions = true;
        if ($data->showbulkactions) {
            $data->id = 'badgesbulkactions';
            $data->attributes = [
                [
                    'name' => 'data-action',
                    'value' => 'toggle',
                ],
                [
                    'name' => 'data-togglegroup',
                    'value' => $this->uniqueid,
                ],
                [
                    'name' => 'data-toggle',
                    'value' => 'action',
                ],
                [
                    'name' => 'disabled',
                    'value' => true,
                ],
            ];
            $data->actions = [
                [
                    'name' => get_string('deleteselected', 'report_solentconnect_badges'),
                    'value' => '#deleteselected',
                ],
                [
                    'name' => get_string('markexported', 'report_solentconnect_badges'),
                    'value' => '#markexported',
                ],
                [
                    'name' => get_string('requeueforexport', 'report_solentconnect_badges'),
                    'value' => '#requeueforexport',
                ],
            ];

            echo $OUTPUT->render_from_template('report_solentconnect_badges/bulk_actions_menu', $data);
        }
    }
}
