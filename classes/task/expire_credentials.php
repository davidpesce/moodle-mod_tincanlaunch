<?php

namespace mod_tincanlaunch\task;
require_once(dirname(dirname(dirname(__FILE__))).'/lib.php');
defined('MOODLE_INTERNAL') || die();

class expire_credentials extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens
        return get_string('expirecredentials', 'mod_tincanlaunch');
    }

    public function execute() {
        global $DB;
        $records = $DB->get_records_select('tincanlaunch_credentials',"expiry > 0 AND expiry < ".time());

        if ($records != false){
            foreach ($records as $record) {
                if ($this->tincanlaunch_delete_creds_watershed($record->tincanlaunchid, $record->credentialid)) {
                    $DB->delete_records('tincanlaunch_credentials', ['credentialid' => $record->credentialid]);
                };
            }
        }
    }

/**
 * Used with Watershed integration to fetch credentials from the LRS.
 * This process is not part of the xAPI specification or the Tin Can launch spec.
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @param int $tincanlaunchid instance id for LRS settings
 * @param int $credentialid credential id to delete
 * @return Bool success
 */
function tincanlaunch_delete_creds_watershed($tincanlaunchid, $credentialid)
{
    global $CFG;

    $tincanlaunchsettings = tincanlaunch_settings($tincanlaunchid);

    // Create a new Watershed activity provider
    $auth = array(
        "method" => "BASIC",
        "username" => $tincanlaunchsettings['tincanlaunchwatershedlogin'],
        "password" => $tincanlaunchsettings['tincanlaunchwatershedpass']
    );

    $explodedEndpoint = explode ('/', $tincanlaunchsettings['tincanlaunchlrsendpoint']);
    $wsServer = $explodedEndpoint[0].'//'.$explodedEndpoint[2];
    $orgId = $explodedEndpoint[5];

    $wsclient = new \WatershedClient\Watershed($wsServer, $auth);

    $response = $wsclient->deleteActivityProvider($credentialid, $orgId);
    if ($response["success"]) {
        echo("Deleted credential id {$credentialid} on organization id {$orgId}");
        return true;
    } 
    else {
        echo("Failed to delete credential id {$credentialid} on organization id {$orgId}");
        echo ('<pre>');
        var_dump($response);
        echo ('</pre>');
        return false;
    }
}
} 