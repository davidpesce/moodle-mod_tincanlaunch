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
 * Unit tests for mod_tincanlaunch lib.php.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_tincanlaunch;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/tincanlaunch/lib.php');
require_once($CFG->dirroot . '/mod/tincanlaunch/locallib.php');

/**
 * Unit tests for mod_tincanlaunch lib.php functions.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_tincanlaunch
 */
final class lib_test extends \advanced_testcase {
    /** @var \stdClass Test course. */
    protected \stdClass $course;

    /** @var \stdClass Test student. */
    protected \stdClass $student;

    /** @var \stdClass Test teacher. */
    protected \stdClass $teacher;

    /**
     * Set up test fixtures.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
    }

    /**
     * Test tincanlaunch_supports returns correct values.
     */
    public function test_supports(): void {
        $this->assertTrue(tincanlaunch_supports(FEATURE_MOD_INTRO));
        $this->assertTrue(tincanlaunch_supports(FEATURE_SHOW_DESCRIPTION));
        $this->assertTrue(tincanlaunch_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
        $this->assertTrue(tincanlaunch_supports(FEATURE_COMPLETION_HAS_RULES));
        $this->assertTrue(tincanlaunch_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertEquals(MOD_PURPOSE_CONTENT, tincanlaunch_supports(FEATURE_MOD_PURPOSE));
        $this->assertNull(tincanlaunch_supports(FEATURE_GRADE_HAS_GRADE));
    }

    /**
     * Test creating a tincanlaunch instance.
     */
    public function test_add_instance(): void {
        global $DB;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance(['course' => $this->course->id]);

        $this->assertNotEmpty($instance->id);
        $this->assertEquals($this->course->id, $instance->course);

        $record = $DB->get_record('tincanlaunch', ['id' => $instance->id]);
        $this->assertNotFalse($record);
        $this->assertEquals('https://example.com/xapi-activity/index.html', $record->tincanlaunchurl);
        $this->assertEquals('https://example.com/xapi-activity', $record->tincanactivityid);
    }

    /**
     * Test creating a tincanlaunch instance with LRS override.
     */
    public function test_add_instance_with_lrs_override(): void {
        global $DB;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_tincanlaunch');
        $instance = $generator->create_instance([
            'course' => $this->course->id,
            'overridedefaults' => 1,
            'tincanlaunchlrsendpoint' => 'https://custom-lrs.example.com/endpoint/',
            'tincanlaunchlrslogin' => 'customkey',
            'tincanlaunchlrspass' => 'customsecret',
        ]);

        $lrsrecord = $DB->get_record('tincanlaunch_lrs', ['tincanlaunchid' => $instance->id]);
        $this->assertNotFalse($lrsrecord);
        $this->assertEquals('https://custom-lrs.example.com/endpoint/', $lrsrecord->lrsendpoint);
        $this->assertEquals('customkey', $lrsrecord->lrslogin);
        $this->assertEquals('customsecret', $lrsrecord->lrspass);
    }

    /**
     * Test updating a tincanlaunch instance.
     */
    public function test_update_instance(): void {
        global $DB;

        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $this->course->id,
            'name' => 'Original Name',
        ]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id);

        $updatedata = new \stdClass();
        $updatedata->instance = $instance->id;
        $updatedata->coursemodule = $cm->id;
        $updatedata->name = 'Updated Name';
        $updatedata->tincanlaunchurl = $instance->tincanlaunchurl;
        $updatedata->tincanactivityid = $instance->tincanactivityid;
        $updatedata->tincanverbid = $instance->tincanverbid;
        $updatedata->tincanexpiry = $instance->tincanexpiry;
        $updatedata->overridedefaults = 0;
        $updatedata->tincanmultipleregs = $instance->tincanmultipleregs;
        $updatedata->tincansimplelaunchnav = $instance->tincansimplelaunchnav;
        $updatedata->tincanlaunchlrsendpoint = 'https://lrs.example.com/endpoint/';
        $updatedata->tincanlaunchlrsauthentication = 1;
        $updatedata->tincanlaunchlrslogin = 'testkey';
        $updatedata->tincanlaunchlrspass = 'testsecret';
        $updatedata->tincanlaunchlrsduration = 9000;
        $updatedata->tincanlaunchcustomacchp = '';
        $updatedata->tincanlaunchuseactoremail = 1;

        $result = tincanlaunch_update_instance($updatedata);
        $this->assertTrue($result);

        $updated = $DB->get_record('tincanlaunch', ['id' => $instance->id]);
        $this->assertEquals('Updated Name', $updated->name);
        $this->assertNotEmpty($updated->timemodified);
    }

    /**
     * Test deleting a tincanlaunch instance.
     */
    public function test_delete_instance(): void {
        global $DB;

        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $this->course->id,
            'overridedefaults' => 1,
            'tincanlaunchlrsendpoint' => 'https://lrs.example.com/endpoint/',
            'tincanlaunchlrsauthentication' => 1,
            'tincanlaunchlrslogin' => 'key',
            'tincanlaunchlrspass' => 'secret',
            'tincanlaunchlrsduration' => 9000,
            'tincanlaunchcustomacchp' => '',
            'tincanlaunchuseactoremail' => 1,
        ]);

        // Verify records exist.
        $this->assertTrue($DB->record_exists('tincanlaunch', ['id' => $instance->id]));
        $this->assertTrue($DB->record_exists('tincanlaunch_lrs', ['tincanlaunchid' => $instance->id]));

        // Delete the instance.
        $result = tincanlaunch_delete_instance($instance->id);
        $this->assertTrue($result);

        // Verify records are removed.
        $this->assertFalse($DB->record_exists('tincanlaunch', ['id' => $instance->id]));
        $this->assertFalse($DB->record_exists('tincanlaunch_lrs', ['tincanlaunchid' => $instance->id]));
    }

    /**
     * Test deleting a non-existent instance returns false.
     */
    public function test_delete_instance_nonexistent(): void {
        $result = tincanlaunch_delete_instance(99999);
        $this->assertFalse($result);
    }

    /**
     * Test get_coursemodule_info returns correct data.
     */
    public function test_get_coursemodule_info(): void {
        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $this->course->id,
            'name' => 'Test Activity',
            'showdescription' => 1,
            'intro' => 'Test intro text',
            'introformat' => FORMAT_HTML,
        ]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id);
        $info = tincanlaunch_get_coursemodule_info($cm);

        $this->assertInstanceOf(\cached_cm_info::class, $info);
        $this->assertEquals('Test Activity', $info->name);
    }

    /**
     * Test get_coursemodule_info with completion tracking populates customdata.
     */
    public function test_get_coursemodule_info_with_completion(): void {
        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $this->course->id,
            'name' => 'Completion Test',
            'tincanverbid' => 'http://adlnet.gov/expapi/verbs/completed',
            'tincanexpiry' => 30,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id);
        $info = tincanlaunch_get_coursemodule_info($cm);

        $this->assertIsArray($info->customdata);
        $this->assertArrayHasKey('customcompletionrules', $info->customdata);
    }

    /**
     * Test get_coursemodule_info returns false for invalid ID.
     */
    public function test_get_coursemodule_info_invalid(): void {
        $cm = new \stdClass();
        $cm->instance = 99999;
        $cm->showdescription = 0;
        $cm->completion = COMPLETION_TRACKING_NONE;

        $result = tincanlaunch_get_coursemodule_info($cm);
        $this->assertFalse($result);
    }

    /**
     * Test build_lrs_settings constructs correct object.
     */
    public function test_build_lrs_settings(): void {
        $tincanlaunch = new \stdClass();
        $tincanlaunch->instance = 1;
        $tincanlaunch->tincanlaunchlrsendpoint = 'https://lrs.example.com/endpoint/';
        $tincanlaunch->tincanlaunchlrsauthentication = 1;
        $tincanlaunch->tincanlaunchlrslogin = 'mykey';
        $tincanlaunch->tincanlaunchlrspass = 'mysecret';
        $tincanlaunch->tincanlaunchlrsduration = 9000;
        $tincanlaunch->tincanlaunchcustomacchp = 'https://example.com';
        $tincanlaunch->tincanlaunchuseactoremail = 1;

        $result = tincanlaunch_build_lrs_settings($tincanlaunch);

        $this->assertEquals('https://lrs.example.com/endpoint/', $result->lrsendpoint);
        $this->assertEquals(1, $result->lrsauthentication);
        $this->assertEquals('mykey', $result->lrslogin);
        $this->assertEquals('mysecret', $result->lrspass);
        $this->assertEquals(9000, $result->lrsduration);
        $this->assertEquals('https://example.com', $result->customacchp);
        $this->assertEquals(1, $result->useactoremail);
    }

    /**
     * Test tincanlaunch_getactor with email identification.
     */
    public function test_getactor_email(): void {
        // Set up global LRS settings.
        set_config('tincanlaunchlrsendpoint', 'https://lrs.example.com/endpoint/', 'tincanlaunch');
        set_config('tincanlaunchlrsauthentication', 1, 'tincanlaunch');
        set_config('tincanlaunchlrslogin', 'key', 'tincanlaunch');
        set_config('tincanlaunchlrspass', 'secret', 'tincanlaunch');
        set_config('tincanlaunchlrsduration', 9000, 'tincanlaunch');
        set_config('tincanlaunchcustomacchp', '', 'tincanlaunch');
        set_config('tincanlaunchuseactoremail', 1, 'tincanlaunch');

        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $this->course->id,
        ]);

        $this->setUser($this->student);

        $actor = tincanlaunch_getactor($instance->id);

        $this->assertInstanceOf(\TinCan\Agent::class, $actor);
        $this->assertEquals('mailto:' . $this->student->email, $actor->getMbox());
        $this->assertEquals(fullname($this->student), $actor->getName());
    }

    /**
     * Test tincanlaunch_getactor with custom account homepage (idnumber identification).
     */
    public function test_getactor_account(): void {
        global $DB;

        // Give student an idnumber.
        $this->student->idnumber = 'STUDENT001';
        $DB->update_record('user', $this->student);

        set_config('tincanlaunchlrsendpoint', 'https://lrs.example.com/endpoint/', 'tincanlaunch');
        set_config('tincanlaunchlrsauthentication', 1, 'tincanlaunch');
        set_config('tincanlaunchlrslogin', 'key', 'tincanlaunch');
        set_config('tincanlaunchlrspass', 'secret', 'tincanlaunch');
        set_config('tincanlaunchlrsduration', 9000, 'tincanlaunch');
        set_config('tincanlaunchcustomacchp', 'https://myinstitution.example.com', 'tincanlaunch');
        set_config('tincanlaunchuseactoremail', 1, 'tincanlaunch');

        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $this->course->id,
        ]);

        $this->setUser($this->student);

        // Reset settings cache.
        global $tincanlaunchsettings;
        $tincanlaunchsettings = null;

        $actor = tincanlaunch_getactor($instance->id);

        $this->assertInstanceOf(\TinCan\Agent::class, $actor);
        $account = $actor->getAccount();
        $this->assertNotNull($account);
        $this->assertEquals('https://myinstitution.example.com', $account->getHomePage());
        $this->assertEquals('STUDENT001', $account->getName());
    }

    /**
     * Test tincanlaunch_getactor with explicit user parameter.
     */
    public function test_getactor_with_user(): void {
        set_config('tincanlaunchlrsendpoint', 'https://lrs.example.com/endpoint/', 'tincanlaunch');
        set_config('tincanlaunchlrsauthentication', 1, 'tincanlaunch');
        set_config('tincanlaunchlrslogin', 'key', 'tincanlaunch');
        set_config('tincanlaunchlrspass', 'secret', 'tincanlaunch');
        set_config('tincanlaunchlrsduration', 9000, 'tincanlaunch');
        set_config('tincanlaunchcustomacchp', '', 'tincanlaunch');
        set_config('tincanlaunchuseactoremail', 1, 'tincanlaunch');

        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $this->course->id,
        ]);

        $actor = tincanlaunch_getactor($instance->id, $this->teacher);

        $this->assertInstanceOf(\TinCan\Agent::class, $actor);
        $this->assertEquals(fullname($this->teacher), $actor->getName());
    }

    /**
     * Test use_global_lrs_settings with no override.
     */
    public function test_use_global_lrs_settings_default(): void {
        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $this->course->id,
            'overridedefaults' => 0,
        ]);

        $result = tincanlaunch_use_global_lrs_settings($instance->id);
        $this->assertTrue($result);
    }

    /**
     * Test use_global_lrs_settings with override enabled.
     */
    public function test_use_global_lrs_settings_override(): void {
        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $this->course->id,
            'overridedefaults' => 1,
            'tincanlaunchlrsendpoint' => 'https://custom.example.com/endpoint/',
            'tincanlaunchlrsauthentication' => 1,
            'tincanlaunchlrslogin' => 'key',
            'tincanlaunchlrspass' => 'secret',
            'tincanlaunchlrsduration' => 9000,
            'tincanlaunchcustomacchp' => '',
            'tincanlaunchuseactoremail' => 1,
        ]);

        $result = tincanlaunch_use_global_lrs_settings($instance->id);
        $this->assertFalse($result);
    }

    /**
     * Test tincanlaunch_get_file_areas returns expected areas.
     */
    public function test_get_file_areas(): void {
        $areas = tincanlaunch_get_file_areas(null, null, null);
        $this->assertIsArray($areas);
        $this->assertArrayHasKey('content', $areas);
        $this->assertArrayHasKey('package', $areas);
    }

    /**
     * Test tincanlaunch_validate_package with no valid tincan.xml.
     */
    public function test_validate_package_no_manifest(): void {
        global $CFG;

        // Create a temporary zip file without tincan.xml.
        $zippath = $CFG->tempdir . '/test_no_manifest.zip';
        $zip = new \ZipArchive();
        $zip->open($zippath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('index.html', '<html><body>Test</body></html>');
        $zip->close();

        // Store it in Moodle file storage.
        $fs = get_file_storage();
        $filerecord = [
            'contextid' => \context_system::instance()->id,
            'component' => 'phpunit',
            'filearea' => 'test',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'test_no_manifest.zip',
        ];
        $file = $fs->create_file_from_pathname($filerecord, $zippath);

        $errors = tincanlaunch_validate_package($file);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('packagefile', $errors);

        // Clean up.
        unlink($zippath);
    }

    /**
     * Test tincanlaunch_validate_package with valid tincan.xml.
     */
    public function test_validate_package_valid(): void {
        global $CFG;

        // Create a valid zip file with tincan.xml.
        $zippath = $CFG->tempdir . '/test_valid_package.zip';
        $zip = new \ZipArchive();
        $zip->open($zippath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $tincanxml = '<?xml version="1.0" encoding="utf-8" ?>'
            . '<tincan xmlns="http://projecttincan.com/tincan.xsd">'
            . '<activities><activity id="https://example.com/activity">'
            . '<name>Test</name><launch>index.html</launch>'
            . '</activity></activities></tincan>';
        $zip->addFromString('tincan.xml', $tincanxml);
        $zip->addFromString('index.html', '<html><body>Test</body></html>');
        $zip->close();

        $fs = get_file_storage();
        $filerecord = [
            'contextid' => \context_system::instance()->id,
            'component' => 'phpunit',
            'filearea' => 'test',
            'itemid' => 1,
            'filepath' => '/',
            'filename' => 'test_valid_package.zip',
        ];
        $file = $fs->create_file_from_pathname($filerecord, $zippath);

        $errors = tincanlaunch_validate_package($file);

        $this->assertEmpty($errors);

        // Clean up.
        unlink($zippath);
    }

    /**
     * Test tincanlaunch_get_moodle_language with simple language code.
     */
    public function test_get_moodle_language_simple(): void {
        // The default language is 'en'.
        $lang = tincanlaunch_get_moodle_language();
        $this->assertEquals('en', $lang);
    }

    /**
     * Test the registration key default and configurable behaviour.
     */
    public function test_registration_key(): void {
        $this->resetAfterTest();
        $default = 'http://tincanapi.co.uk/stateapikeys/registrations';

        // Default value is returned when no config is set.
        $this->assertEquals($default, tincanlaunch_get_registration_key());

        // Custom value is returned when config is set.
        set_config('tincanlaunchregistrationkey', 'https://example.com/custom/key', 'tincanlaunch');
        $this->assertEquals('https://example.com/custom/key', tincanlaunch_get_registration_key());

        // Falls back to default when config is empty.
        set_config('tincanlaunchregistrationkey', '', 'tincanlaunch');
        $this->assertEquals($default, tincanlaunch_get_registration_key());
    }

    /**
     * Test tincanlaunch_myjson_encode unescapes slashes.
     */
    public function test_myjson_encode(): void {
        $data = ['url' => 'https://example.com/path/to/resource'];
        $encoded = tincanlaunch_myjson_encode($data);

        $this->assertStringContainsString('https://example.com/path/to/resource', $encoded);
        $this->assertStringNotContainsString('\\/', $encoded);
    }

    /**
     * Test tincanlaunch_user_outline returns expected structure.
     */
    public function test_user_outline(): void {
        $result = tincanlaunch_user_outline();

        $this->assertIsObject($result);
        $this->assertObjectHasProperty('time', $result);
        $this->assertObjectHasProperty('info', $result);
        $this->assertEquals(0, $result->time);
        $this->assertEquals('', $result->info);
    }

    /**
     * Test tincanlaunch_print_recent_activity returns false.
     */
    public function test_print_recent_activity(): void {
        $result = tincanlaunch_print_recent_activity();
        $this->assertFalse($result);
    }

    /**
     * Test tincanlaunch_get_extra_capabilities returns empty array.
     */
    public function test_get_extra_capabilities(): void {
        $result = tincanlaunch_get_extra_capabilities();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test tincanlaunch_build_actor_map with email (mbox) identification.
     */
    public function test_build_actor_map_email(): void {
        $settings = [
            'tincanlaunchcustomacchp' => '',
            'tincanlaunchuseactoremail' => 1,
        ];

        $user1 = (object) ['id' => 10, 'idnumber' => '', 'email' => 'alice@example.com', 'username' => 'alice'];
        $user2 = (object) ['id' => 20, 'idnumber' => '', 'email' => 'bob@example.com', 'username' => 'bob'];

        $map = tincanlaunch_build_actor_map([$user1, $user2], $settings);

        $this->assertArrayHasKey('mailto:alice@example.com', $map);
        $this->assertEquals(10, $map['mailto:alice@example.com']);
        $this->assertArrayHasKey('mailto:bob@example.com', $map);
        $this->assertEquals(20, $map['mailto:bob@example.com']);
    }

    /**
     * Test tincanlaunch_build_actor_map with custom account homepage (idnumber).
     */
    public function test_build_actor_map_account(): void {
        $settings = [
            'tincanlaunchcustomacchp' => 'https://myinstitution.example.com',
            'tincanlaunchuseactoremail' => 1,
        ];

        $user1 = (object) ['id' => 10, 'idnumber' => 'STU001', 'email' => 'alice@example.com', 'username' => 'alice'];
        $user2 = (object) ['id' => 20, 'idnumber' => 'STU002', 'email' => 'bob@example.com', 'username' => 'bob'];

        $map = tincanlaunch_build_actor_map([$user1, $user2], $settings);

        $this->assertArrayHasKey('https://myinstitution.example.com|STU001', $map);
        $this->assertEquals(10, $map['https://myinstitution.example.com|STU001']);
        $this->assertArrayHasKey('https://myinstitution.example.com|STU002', $map);
        $this->assertEquals(20, $map['https://myinstitution.example.com|STU002']);
    }

    /**
     * Test tincanlaunch_build_actor_map fallback to wwwroot + username.
     */
    public function test_build_actor_map_fallback(): void {
        global $CFG;

        $settings = [
            'tincanlaunchcustomacchp' => '',
            'tincanlaunchuseactoremail' => 0,
        ];

        $user1 = (object) ['id' => 10, 'idnumber' => '', 'email' => '', 'username' => 'alice'];

        $map = tincanlaunch_build_actor_map([$user1], $settings);

        $expectedkey = $CFG->wwwroot . '|alice';
        $this->assertArrayHasKey($expectedkey, $map);
        $this->assertEquals(10, $map[$expectedkey]);
    }

    /**
     * Test tincanlaunch_build_actor_map with mixed identification strategies.
     */
    public function test_build_actor_map_mixed(): void {
        $settings = [
            'tincanlaunchcustomacchp' => 'https://myinstitution.example.com',
            'tincanlaunchuseactoremail' => 1,
        ];

        // User1 has idnumber — should use account-based.
        $user1 = (object) ['id' => 10, 'idnumber' => 'STU001', 'email' => 'alice@example.com', 'username' => 'alice'];
        // User2 has no idnumber but has email — should use mbox.
        $user2 = (object) ['id' => 20, 'idnumber' => '', 'email' => 'bob@example.com', 'username' => 'bob'];

        $map = tincanlaunch_build_actor_map([$user1, $user2], $settings);

        $this->assertArrayHasKey('https://myinstitution.example.com|STU001', $map);
        $this->assertEquals(10, $map['https://myinstitution.example.com|STU001']);
        $this->assertArrayHasKey('mailto:bob@example.com', $map);
        $this->assertEquals(20, $map['mailto:bob@example.com']);
    }

    /**
     * Test tincanlaunch_match_statement_to_user with mbox actor.
     */
    public function test_match_statement_to_user_mbox(): void {
        $actormap = ['mailto:alice@example.com' => 10, 'mailto:bob@example.com' => 20];

        // Suppress TinCanPHP deprecation notices (third-party library).
        $olderror = error_reporting(E_ALL & ~E_DEPRECATED);
        $statement = new \TinCan\Statement([
            'actor' => [
                'mbox' => 'mailto:alice@example.com',
                'objectType' => 'Agent',
            ],
            'verb' => ['id' => 'http://adlnet.gov/expapi/verbs/completed'],
            'object' => ['id' => 'https://example.com/activity', 'objectType' => 'Activity'],
        ]);

        $result = tincanlaunch_match_statement_to_user($statement, $actormap);
        error_reporting($olderror);
        $this->assertEquals(10, $result);
    }

    /**
     * Test tincanlaunch_match_statement_to_user with account actor.
     */
    public function test_match_statement_to_user_account(): void {
        $actormap = ['https://myinstitution.example.com|STU001' => 10];

        // Suppress TinCanPHP deprecation notices (third-party library).
        $olderror = error_reporting(E_ALL & ~E_DEPRECATED);
        $statement = new \TinCan\Statement([
            'actor' => [
                'account' => [
                    'homePage' => 'https://myinstitution.example.com',
                    'name' => 'STU001',
                ],
                'objectType' => 'Agent',
            ],
            'verb' => ['id' => 'http://adlnet.gov/expapi/verbs/completed'],
            'object' => ['id' => 'https://example.com/activity', 'objectType' => 'Activity'],
        ]);

        $result = tincanlaunch_match_statement_to_user($statement, $actormap);
        error_reporting($olderror);
        $this->assertEquals(10, $result);
    }

    /**
     * Test tincanlaunch_match_statement_to_user returns null for unknown actor.
     */
    public function test_match_statement_to_user_unknown(): void {
        $actormap = ['mailto:alice@example.com' => 10];

        // Suppress TinCanPHP deprecation notices (third-party library).
        $olderror = error_reporting(E_ALL & ~E_DEPRECATED);
        $statement = new \TinCan\Statement([
            'actor' => [
                'mbox' => 'mailto:unknown@example.com',
                'objectType' => 'Agent',
            ],
            'verb' => ['id' => 'http://adlnet.gov/expapi/verbs/completed'],
            'object' => ['id' => 'https://example.com/activity', 'objectType' => 'Activity'],
        ]);

        $result = tincanlaunch_match_statement_to_user($statement, $actormap);
        error_reporting($olderror);
        $this->assertNull($result);
    }
}
