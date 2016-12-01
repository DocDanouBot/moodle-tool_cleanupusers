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
 * A scheduled task for tool_deprovisionuser cron.
 *
 * The Class archive_user_task is supposed to show the admin a page of users which will be archived and expectes a submit or cancel reaction.
 * @package    tool_deprovisionuser
 * @copyright  2016 N Herrmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_deprovisionuser\task;
use tool_deprovisionuser\db as this_db;
global $CFG;
require_once($CFG->dirroot.'/admin/tool/deprovisionuser/user_status_checker.php');

class archive_user_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('archive_user_task', 'tool_deprovisionuser');
    }

    /**
     * Runs the cron job - Makes a list of all Users who will be archived.
     *
     * Only supposed to execute Logic. Admin is supposed to see the last result of the Cron-Job. We need to save the data of users
     * in Databases to display the results of the last cronjob.
     *
     * @return ?
     */
    public function execute() {
        global $DB, $USER;
        $userstatuschecker = new user_status_checker();
        $archivearray = $userstatuschecker->get_to_suspend_for_cron();

        foreach($archivearray as $user){
            if (!is_siteadmin($user) and $user->suspended != 1 and $USER->id != $user->id) {
                $user->suspended = 1;
                // Force logout.
                $transaction = $DB->start_delegated_transaction();
                // TODO inserts not a binary but \x31 for true
//                $DB->insert_record_raw('tool_deprovisionuser', array('id' => $user->id, 'archived' => true), true, false, true);
                $transaction->allow_commit();

                \core\session\manager::kill_user_sessions($user->id);
                user_update_user($user, false);
            } else {
                // senseful exception
            }
        }
        return true;
    }
}