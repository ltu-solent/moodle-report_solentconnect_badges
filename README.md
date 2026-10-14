# SolentConnect Badges

This plugin captures Badge Awarded events on a list of specified courses (settings: courseids). When the event is triggered a badge from the course is listed in the `report_solentconnect_badges` table.

There are two options for exporting the Badges to SolentConnect: Manual CSV download and FTP transfer.

An entry is considered "exported" when there is a time-stamp in the `timeexported` field in the database. If not exported, then the entry will have a zero.

## Manual CSV download

By default all non-exported entries will be listed (this is the `showqueuedonly` checkbox).

The administrator can change the filters to show any combination:

- Badges exported between dates (if the `showqueuedonly` field is unchecked)
- Badges issued between dates
- Badges expiring between dates
- Badges issued to a user
- Specified badge from drop-down of available badges listed in the `report_solentconnect_badges` table.

A Download option will be made available on the page when there are entries to show. The CSV will include all the entries even if the table is paged.

### Actions

Each row in the results has the following actions:

- Delete (note this only deletes the entry in the `report_solentconnect_badges` table, not the actual Badge).
- Mark exported - this will set the current time in the `timeexported` field.
- Mark for export - this will reset any time in the `timeexported` field to zero.

There is also a Bulk action menu to perform the same actions available on a row basis.

### Manual workflow

1. Filter for all "showqueuedonly"
2. Download the CSV
3. Bulk "Mark exported" (you will need to do this for each page, if more than one page).
    - There is a risk between Downloading and marking as Exported that new badges would be awarded
4. Upload CSV to SolentConnect


## SFTP transfer

For SFTP this uses [phpseclib](https://phpseclib.com/docs/ssh2/sftp), and this is included in the vendor folder and autoloaded by the task, if it's running.

If you want to use SFTP, then set the `ftp_host`, `ftp_user`, `ftp_pass` and `ftp_enabled` fields according to your SFTP server.

A task runs once a day and constructs a CSV of all Queued items (up to 500 items). If the FTP is successful all exported entries will be marked as exported with the timestamp in `timeexported`.

If there are any remaining Queued items they will be collected on the next run.

If you're not using SFTP, consider disabling the task.