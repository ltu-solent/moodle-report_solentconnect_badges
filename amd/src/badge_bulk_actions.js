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
 * Bulk actions on the badges for SolentConnect
 *
 * @module     report_solentconnect_badges/badge_bulk_actions
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getList} from 'core/normalise';
import Notification from 'core/notification';
import {prefetchStrings} from 'core/prefetch';
import {get_string as getString} from 'core/str';
import Ajax from 'core/ajax';
import Pending from 'core/pending';
import {add as addToast} from 'core/toast';

const selectors = {
    activeRows: 'input.badgecheckbox:checked',
    bulkActions: '#badgesbulkactions',
    table: '#report_solentconnect_badges_table'
};

export const init = () => {
    const table = document.querySelector(selectors.table);
    const bulkActions = document.querySelector(selectors.bulkActions);

    if (!table || !bulkActions) {
        return;
    }
    prefetchStrings('report_solentconnect_badges', [
        'confirmdelete',
        'deletewarning'
    ]);

    prefetchStrings('core', [
        'delete',
    ]);

    registerEventListeners();
};

const registerEventListeners = () => {
    document.querySelector(selectors.bulkActions)?.addEventListener('change', e => {
        const action = e.target;
        if (action.value.indexOf('#') !== -1) {
            e.preventDefault();
            if (action.value == '#deleteselected') {
                deleteBadgesConfirm();
            }
            if (action.value == '#markexported') {
                markBadgesExported();
            }
            if (action.value == '#requeueforexport') {
                requeueBadgesForExport();
            }
        }
    });
};

const deleteBadgesConfirm = () => {
    const table = document.querySelector(selectors.table);
    const activeRows = getList(table.querySelectorAll(selectors.activeRows));
    const scbids = activeRows.map(row => row.value);
    if (scbids.length === 0) {
        return;
    }
    // Proceed with the deletion confirmation logic here.
    Notification.saveCancelPromise(
        getString('confirmdelete', 'report_solentconnect_badges'),
        getString('deletewarning', 'report_solentconnect_badges', {count: scbids.length}),
        getString('delete', 'core'),
    ).then(() => {
        // Handle the confirmation result here.
        return deleteBadges(scbids);
    }).catch(() => {
        // User cancelled, do nothing.
    });
};

/**
 * Deletes the specified SolentConnect badges.
 *
 * @param {Number[]} scbids SolentConnect BadgeID
 */
async function deleteBadges(scbids) {
    const request = {
        methodname: 'report_solentconnect_badges_delete_badges',
        args: {
            scbids: scbids,
        },
    };
    const pendingPromise = new Pending('report_solentconnect_badges/deleting_badges');
    try {
        await Ajax.call([request])[0];
        addToast(getString('deletedsuccessfully', 'report_solentconnect_badges', {count: scbids.length}), 'success');
        window.location.reload(); // Refresh the page to reflect the changes.
    } catch (error) {
        Notification.exception(error);
    }
    pendingPromise.resolve();
}

/**
 * Marks the selected SolentConnect badges as exported.
 *
 * @returns {Promise<void>}
 */
async function markBadgesExported() {
    const table = document.querySelector(selectors.table);
    const activeRows = getList(table.querySelectorAll(selectors.activeRows));
    const scbids = activeRows.map(row => row.value);
    if (scbids.length === 0) {
        return;
    }
    const request = {
        methodname: 'report_solentconnect_badges_update_badges',
        args: {
            scbids: scbids,
            timeexported: Math.floor(Date.now() / 1000)
        },
    };
    const pendingPromise = new Pending('report_solentconnect_badges/marking_exported');
    try {
        await Ajax.call([request])[0];
        addToast(getString('markedexportedsuccessfully', 'report_solentconnect_badges', {count: scbids.length}), 'success');

        window.location.reload(); // Refresh the page to reflect the changes.
    } catch (error) {
        Notification.exception(error);
    }
    pendingPromise.resolve();
}

/**
 * Requeues the selected SolentConnect badges for export by setting their exported time to 0.
 *
 * @returns {Promise<void>}
 */
async function requeueBadgesForExport() {
    const table = document.querySelector(selectors.table);
    const activeRows = getList(table.querySelectorAll(selectors.activeRows));
    const scbids = activeRows.map(row => row.value);
    if (scbids.length === 0) {
        return;
    }
    const request = {
        methodname: 'report_solentconnect_badges_update_badges',
        args: {
            scbids: scbids,
            timeexported: 0
        },
    };
    const pendingPromise = new Pending('report_solentconnect_badges/requeueing_export');
    try {
        await Ajax.call([request])[0];
        addToast(getString('requeuedsuccessfully', 'report_solentconnect_badges', {count: scbids.length}), 'success');
        window.location.reload(); // Refresh the page to reflect the changes.
    } catch (error) {
        Notification.exception(error);
    }
    pendingPromise.resolve();
}
