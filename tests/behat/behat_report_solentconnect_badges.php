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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Gherkin\Node\TableNode;

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat steps in plugin SolentConnect Badges
 *
 * @package    report_solentconnect_badges
 * @category   test
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_report_solentconnect_badges extends behat_base {
    /**
     * Convert page names to URLs for steps like 'When I am on the "[page name]" page'.
     *
     * Recognised page names are:
     * | report index | the list of all reports. |
     *
     * @param string $page name of the page, with the component name removed e.g. 'Admin notification'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch (strtolower($page)) {
            case 'report index':
                return new moodle_url('/report/solentconnect_badges/index.php');
            default:
                throw new Exception('Unrecognised report page type "' . $page . '."');
        }
    }

    /**
     * Step for setting complex settings
     *
     * @param TableNode $table
     * @return void
     * @Given /^the following report solentconnect badges config values are set:$/
     */
    public function the_following_report_solentconnect_badges_config_values_are_set(TableNode $table) {
        global $DB;
        $configvalues = $table->getRowsHash();
        if (!$configvalues) {
            return;
        }
        foreach ($configvalues as $name => $value) {
            if ($name == 'courseids') {
                $courseidnumbers = array_map('trim', explode(',', $value));
                [$insql, $inparams] = $DB->get_in_or_equal($courseidnumbers, SQL_PARAMS_NAMED);
                $courseids = $DB->get_fieldset_select('course', 'id', "shortname $insql", $inparams);
                $value = implode(',', $courseids);
            }
            set_config($name, $value, 'report_solentconnect_badges');
        }
    }
}
