<?php
// This file is part of the mod_coursecertificate plugin for Moodle - http://moodle.org/
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
 * mod_diary steps definitions.
 *
 * @package     mod_diary
 * @category    test
 * @copyright   2023 Giorgio Riva
 * @copyright   AL Rachels (drachels@drachels.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Steps definitions for mod_diary.
 *
 * @package     mod_diary
 * @category    test
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_diary extends behat_base {
    /**
     * Waits a while, for debugging.
     *
     * @param int $seconds How long to wait.
     *
     * @When I wait :seconds second(s)
     */
    public function wait($seconds) {
        sleep($seconds);
    }

    /**
     * Opens the notification preferences page.
     *
     * @When I open diary notification preferences
     */
    public function i_open_diary_notification_preferences() {
        $this->getSession()->visit($this->locate_path('/message/notificationpreferences.php'));
    }

    /**
     * Configures completion metric requirements for diary activities.
     *
     * Required columns: diary, metric, operator, value, penalty.
     * Operator accepts >=, <=, atleast, atmost.
     *
     * @Given /^the following diary metric requirements are configured:$/
     */
    public function the_following_diary_metric_requirements_are_configured($table) {
        global $DB;

        $rows = $table->getHash();
        $bydiary = [];

        foreach ($rows as $row) {
            if (!isset($row['diary'], $row['metric'], $row['operator'], $row['value'], $row['penalty'])) {
                throw new \Exception('Required columns: diary, metric, operator, value, penalty.');
            }

            $diaryname = trim((string)$row['diary']);
            $metric = trim((string)$row['metric']);
            $operatorraw = strtolower(trim((string)$row['operator']));
            $value = (float)$row['value'];
            $penalty = (int)$row['penalty'];

            if ($operatorraw === '<=' || $operatorraw === 'atmost') {
                $operator = 1;
            } else if ($operatorraw === '>=' || $operatorraw === 'atleast') {
                $operator = 0;
            } else {
                throw new \Exception('Unsupported operator: ' . $operatorraw . ' (expected >=, <=, atleast, atmost).');
            }

            if (!isset($bydiary[$diaryname])) {
                $bydiary[$diaryname] = [];
            }

            $bydiary[$diaryname][$metric] = [
                'operator' => $operator,
                'value' => $value,
                'penalty' => max($penalty, 0),
            ];
        }

        foreach ($bydiary as $diaryname => $requirements) {
            $diary = $DB->get_record('diary', ['name' => $diaryname], 'id', MUST_EXIST);
            set_config('metricrequirements_' . (int)$diary->id, json_encode($requirements), 'mod_diary');
        }
    }
}
