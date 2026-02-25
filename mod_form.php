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
 * The main tincanlaunch configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_tincanlaunch_mod_form extends moodleform_mod {
    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {

        $cfgtincanlaunch = get_config('tincanlaunch');

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('tincanlaunchname', 'tincanlaunch'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'tincanlaunchname', 'tincanlaunch');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        $mform->addElement('header', 'packageheading', get_string('tincanpackagetitle', 'tincanlaunch'));

        // Content type selector.
        $typeoptions = [
            0 => get_string('tincanlaunchtype_zip', 'tincanlaunch'),
            1 => get_string('tincanlaunchtype_external', 'tincanlaunch'),
        ];
        $mform->addElement('select', 'tincanlaunchtype', get_string('tincanlaunchtype', 'tincanlaunch'), $typeoptions);
        $mform->addHelpButton('tincanlaunchtype', 'tincanlaunchtype', 'tincanlaunch');
        $mform->setDefault('tincanlaunchtype', 1);

        // Package upload — shown only for "Zip package".
        $filemanageroptions = [];
        $filemanageroptions['accepted_types'] = ['.zip'];
        $filemanageroptions['maxbytes'] = 0;
        $filemanageroptions['maxfiles'] = 1;
        $filemanageroptions['subdirs'] = 0;

        $mform->addElement('filemanager', 'packagefile', get_string('tincanpackage', 'tincanlaunch'), null, $filemanageroptions);
        $mform->addHelpButton('packagefile', 'tincanpackage', 'tincanlaunch');
        $mform->hideIf('packagefile', 'tincanlaunchtype', 'eq', 1);

        // Launch URL — shown only for "External URL".
        $mform->addElement('text', 'tincanlaunchurl', get_string('tincanlaunchurl', 'tincanlaunch'), ['size' => '64']);
        $mform->setType('tincanlaunchurl', PARAM_TEXT);
        $mform->addRule('tincanlaunchurl', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanlaunchurl', 'tincanlaunchurl', 'tincanlaunch');
        $mform->setDefault('tincanlaunchurl', 'https://example.com/example-activity/index.html');
        $mform->hideIf('tincanlaunchurl', 'tincanlaunchtype', 'eq', 0);

        // Activity ID — shown only for "External URL".
        $mform->addElement('text', 'tincanactivityid', get_string('tincanactivityid', 'tincanlaunch'), ['size' => '64']);
        $mform->setType('tincanactivityid', PARAM_TEXT);
        $mform->addRule('tincanactivityid', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanactivityid', 'tincanactivityid', 'tincanlaunch');
        $mform->setDefault('tincanactivityid', 'https://example.com/example-activity');
        $mform->hideIf('tincanactivityid', 'tincanlaunchtype', 'eq', 0);

        // Start advanced settings.
        $mform->addElement('header', 'lrsheading', get_string('lrsheading', 'tincanlaunch'));

        $mform->addElement('static', 'description', get_string('lrsdefaults', 'tincanlaunch'), get_string(
            'lrssettingdescription',
            'tincanlaunch'
        ));

        // Override default LRS settings.
        $mform->addElement('advcheckbox', 'overridedefaults', get_string('overridedefaults', 'tincanlaunch'));
        $mform->addHelpButton('overridedefaults', 'overridedefaults', 'tincanlaunch');

        // Add LRS endpoint.
        $mform->addElement(
            'text',
            'tincanlaunchlrsendpoint',
            get_string('tincanlaunchlrsendpoint', 'tincanlaunch'),
            ['size' => '64']
        );
        $mform->setType('tincanlaunchlrsendpoint', PARAM_TEXT);
        $mform->addRule('tincanlaunchlrsendpoint', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanlaunchlrsendpoint', 'tincanlaunchlrsendpoint', 'tincanlaunch');
        $mform->setDefault('tincanlaunchlrsendpoint', $cfgtincanlaunch->tincanlaunchlrsendpoint);
        $mform->disabledIf('tincanlaunchlrsendpoint', 'overridedefaults');

        // Add LRS Authentication.
        $authoptions = [
            1 => get_string('tincanlaunchlrsauthentication_option_0', 'tincanlaunch'),
            2 => get_string('tincanlaunchlrsauthentication_option_1', 'tincanlaunch'),
            0 => get_string('tincanlaunchlrsauthentication_option_2', 'tincanlaunch'),
        ];
        $mform->addElement(
            'select',
            'tincanlaunchlrsauthentication',
            get_string('tincanlaunchlrsauthentication', 'tincanlaunch'),
            $authoptions
        );
        $mform->disabledIf('tincanlaunchlrsauthentication', 'overridedefaults');
        $mform->addHelpButton('tincanlaunchlrsauthentication', 'tincanlaunchlrsauthentication', 'tincanlaunch');
        $mform->getElement('tincanlaunchlrsauthentication')->setSelected($cfgtincanlaunch->tincanlaunchlrsauthentication);

        $mform->addElement(
            'static',
            'description',
            get_string('tincanlaunchlrsauthentication_watershedhelp_label', 'tincanlaunch'),
            get_string('tincanlaunchlrsauthentication_watershedhelp', 'tincanlaunch')
        );

        // Add basic authorisation login.
        $mform->addElement(
            'text',
            'tincanlaunchlrslogin',
            get_string('tincanlaunchlrslogin', 'tincanlaunch'),
            ['size' => '64']
        );
        $mform->setType('tincanlaunchlrslogin', PARAM_TEXT);
        $mform->addRule('tincanlaunchlrslogin', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanlaunchlrslogin', 'tincanlaunchlrslogin', 'tincanlaunch');
        $mform->setDefault('tincanlaunchlrslogin', $cfgtincanlaunch->tincanlaunchlrslogin);
        $mform->disabledIf('tincanlaunchlrslogin', 'overridedefaults');

        // Add basic authorisation pass.
        $mform->addElement(
            'password',
            'tincanlaunchlrspass',
            get_string('tincanlaunchlrspass', 'tincanlaunch'),
            ['size' => '64']
        );
        $mform->setType('tincanlaunchlrspass', PARAM_TEXT);
        $mform->addRule('tincanlaunchlrspass', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanlaunchlrspass', 'tincanlaunchlrspass', 'tincanlaunch');
        $mform->setDefault('tincanlaunchlrspass', $cfgtincanlaunch->tincanlaunchlrspass);
        $mform->disabledIf('tincanlaunchlrspass', 'overridedefaults');

        // Duration.
        $mform->addElement(
            'text',
            'tincanlaunchlrsduration',
            get_string('tincanlaunchlrsduration', 'tincanlaunch'),
            ['size' => '64']
        );
        $mform->setType('tincanlaunchlrsduration', PARAM_TEXT);
        $mform->addRule('tincanlaunchlrsduration', get_string('maximumchars', '', 5), 'maxlength', 5, 'client');
        $mform->addHelpButton('tincanlaunchlrsduration', 'tincanlaunchlrsduration', 'tincanlaunch');
        $mform->setDefault('tincanlaunchlrsduration', $cfgtincanlaunch->tincanlaunchlrsduration);
        $mform->disabledIf('tincanlaunchlrsduration', 'overridedefaults');

        // Actor account homePage.
        $mform->addElement(
            'text',
            'tincanlaunchcustomacchp',
            get_string('tincanlaunchcustomacchp', 'tincanlaunch'),
            ['size' => '64']
        );
        $mform->setType('tincanlaunchcustomacchp', PARAM_TEXT);
        $mform->addRule('tincanlaunchcustomacchp', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanlaunchcustomacchp', 'tincanlaunchcustomacchp', 'tincanlaunch');
        $mform->setDefault('tincanlaunchcustomacchp', $cfgtincanlaunch->tincanlaunchcustomacchp);
        $mform->disabledIf('tincanlaunchcustomacchp', 'overridedefaults');

        // Don't use email.
        $mform->addElement('advcheckbox', 'tincanlaunchuseactoremail', get_string('tincanlaunchuseactoremail', 'tincanlaunch'));
        $mform->addHelpButton('tincanlaunchuseactoremail', 'tincanlaunchuseactoremail', 'tincanlaunch');
        $mform->setDefault('tincanlaunchuseactoremail', $cfgtincanlaunch->tincanlaunchuseactoremail);
        $mform->disabledIf('tincanlaunchuseactoremail', 'overridedefaults');
        // End advanced settings.

        // Apearance settings.
        $mform->addElement('header', 'appearanceheading', get_string('appearanceheading', 'tincanlaunch'));

        // Simplified launch.
        $mform->addElement('advcheckbox', 'tincansimplelaunchnav', get_string('tincansimplelaunchnav', 'tincanlaunch'));
        $mform->setDefault('tincansimplelaunchnav', 0);
        $mform->addHelpButton('tincansimplelaunchnav', 'tincansimplelaunchnav', 'tincanlaunch');

        // Allow multiple registrations.
        $mform->addElement('advcheckbox', 'tincanmultipleregs', get_string('tincanmultipleregs', 'tincanlaunch'));
        $mform->setDefault('tincanmultipleregs', 1);
        $mform->hideIf('tincanmultipleregs', 'tincansimplelaunchnav', 'checked');
        $mform->addHelpButton('tincanmultipleregs', 'tincanmultipleregs', 'tincanlaunch');

        // Grade settings.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();
        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Display module-specific activity completion rules.
     * Part of the API defined by moodleform_mod
     * @return array Array of string IDs of added items, empty array if none
     */
    public function add_completion_rules() {
        $mform =& $this->_form;
        $suffix = $this->get_suffix();

        $items = [];

        $completionverbenabled = 'completionverbenabled' . $suffix;
        $tincanverbid = 'tincanverbid' . $suffix;
        $completionverbgroup = 'completionverbgroup' . $suffix;

        $verbgroup = [];

        // Add completion form based on the xAPI verb.
        $verbgroup[] = $mform->createElement(
            'advcheckbox',
            $completionverbenabled,
            null,
            get_string('completionverb', 'tincanlaunch')
        );
        $verbgroup[] = $mform->createElement('text', $tincanverbid, null, ['size' => '64']);
        $mform->setType($tincanverbid, PARAM_TEXT);
        $mform->disabledIf($tincanverbid, $completionverbenabled);

        $mform->addGroup(
            $verbgroup,
            $completionverbgroup,
            get_string('completionverbgroup', 'tincanlaunch'),
            [' '],
            false
        );
        $mform->addGroupRule($completionverbgroup, [$tincanverbid => [
            [get_string('maximumchars', '', 255), 'maxlength', 255, 'client']]]);
        $mform->addHelpButton($completionverbgroup, 'completionverbgroup', 'tincanlaunch');

        $items[] = $completionverbgroup;

        // Add completion form item based on the above verb expiring after a period of time (days).
        $completionexpiryenabled = 'completionexpiryenabled' . $suffix;
        $tincanexpiry = 'tincanexpiry' . $suffix;
        $completionexpirygroup = 'completionexpirygroup' . $suffix;

        $expirygroup = [];
        $expirygroup[] = $mform->createElement(
            'advcheckbox',
            $completionexpiryenabled,
            null,
            get_string('completionexpiry', 'tincanlaunch')
        );

        $expirygroup[] = $mform->createElement('text', $tincanexpiry, null, ['size' => '63']);
        $mform->setType($tincanexpiry, PARAM_TEXT);
        $mform->disabledIf($tincanexpiry, $completionexpiryenabled);
        $mform->addGroup(
            $expirygroup,
            $completionexpirygroup,
            get_string('completionexpirygroup', 'tincanlaunch'),
            [' '],
            false
        );
        $mform->addGroupRule($completionexpirygroup, [$tincanexpiry => [
            [get_string('maximumchars', '', 10), 'maxlength', 10, 'client']]]);
        $mform->addHelpButton($completionexpirygroup, 'completionexpirygroup', 'tincanlaunch');
        $mform->disabledIf($completionexpirygroup, $completionverbenabled);

        $items[] = $completionexpirygroup;

        return $items;
    }

    /**
     * Determines if completion is enabled for this module.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();
        if (!empty($data['completionverbenabled' . $suffix]) && !empty($data['tincanverbid' . $suffix])) {
            return true;
        }
        if (!empty($data['completionexpiryenabled' . $suffix]) && !empty($data['tincanexpiry' . $suffix])) {
            return true;
        }
        return false;
    }

    /**
     * Any data processing needed before the form is displayed
     * (needed to set up draft areas for editor and filemanager elements)
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        global $DB;

        // Determine if default lrs settings were overriden.
        if (!empty($defaultvalues['overridedefaults'])) {
            if ($defaultvalues['overridedefaults'] == '1') {
                // Retrieve activity lrs settings from DB.
                $conditions = ['tincanlaunchid' => $defaultvalues['instance']];
                $fields = '*';
                $strictness = IGNORE_MISSING;
                $tincanlaunchlrs = $DB->get_record('tincanlaunch_lrs', $conditions, $fields, $strictness);
                $defaultvalues['tincanlaunchlrsendpoint'] = $tincanlaunchlrs->lrsendpoint;
                $defaultvalues['tincanlaunchlrsauthentication'] = $tincanlaunchlrs->lrsauthentication;
                $defaultvalues['tincanlaunchcustomacchp'] = $tincanlaunchlrs->customacchp;
                $defaultvalues['tincanlaunchuseactoremail'] = $tincanlaunchlrs->useactoremail;
                $defaultvalues['tincanlaunchlrsduration'] = $tincanlaunchlrs->lrsduration;
                $defaultvalues['tincanlaunchlrslogin'] = $tincanlaunchlrs->lrslogin;
                $defaultvalues['tincanlaunchlrspass'] = $tincanlaunchlrs->lrspass;
            }
        }

        $draftitemid = file_get_submitted_draft_itemid('packagefile');
        file_prepare_draft_area(
            $draftitemid,
            $this->context->id,
            'mod_tincanlaunch',
            'package',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
        $defaultvalues['packagefile'] = $draftitemid;

        // Auto-detect content type when editing: if a package file exists, default to Zip package (0).
        if (!empty($defaultvalues['instance'])) {
            $fs = get_file_storage();
            $files = $fs->get_area_files(
                $this->context->id,
                'mod_tincanlaunch',
                'package',
                0,
                'id',
                false
            );
            if (!empty($files)) {
                $defaultvalues['tincanlaunchtype'] = 0;
            }
        }

        // This is needed to persist the default values (after the initial activity creation).
        $suffix = $this->get_suffix();
        if (!empty($defaultvalues['tincanverbid'])) {
            $defaultvalues['completionverbenabled' . $suffix] = 1;
        } else {
            $defaultvalues['tincanverbid' . $suffix] = 'http://adlnet.gov/expapi/verbs/completed';
        }
        if (!empty($defaultvalues['tincanexpiry'])) {
            $defaultvalues['completionexpiryenabled' . $suffix] = 1;
        } else {
            $defaultvalues['tincanexpiry' . $suffix] = 365;
        }
    }

    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked.
            $suffix = $this->get_suffix();
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            $verbenabled = 'completionverbenabled' . $suffix;
            $expiryenabled = 'completionexpiryenabled' . $suffix;
            if (empty($data->$verbenabled) || !$autocompletion) {
                $data->tincanverbid = '';
            }
            if (empty($data->$expiryenabled) || !$autocompletion) {
                $data->tincanexpiry = '';
            }
        }

        // If simplified launch is enabled, we must disable multiple registrations.
        if ($data->tincansimplelaunchnav == 1) {
            $data->tincanmultipleregs = 0;
        }
    }

    /**
     * Perform minimal validation on the settings form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);

        $tincanlaunchtype = isset($data['tincanlaunchtype']) ? (int) $data['tincanlaunchtype'] : 1;

        if ($tincanlaunchtype === 1) {
            // External URL mode: require launch URL and activity ID.
            if (empty($data['tincanlaunchurl'])) {
                $errors['tincanlaunchurl'] = get_string('errorlaunchurlempty', 'tincanlaunch');
            }
            if (empty($data['tincanactivityid'])) {
                $errors['tincanactivityid'] = get_string('erroractivityidempty', 'tincanlaunch');
            }
        }

        if ($tincanlaunchtype === 0 && !empty($data['packagefile'])) {
            $draftitemid = file_get_submitted_draft_itemid('packagefile');

            file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'mod_tincanlaunch',
                'packagefilecheck',
                null,
                ['subdirs' => 0, 'maxfiles' => 1]
            );

            // Get file from users draft area.
            $usercontext = context_user::instance($USER->id);
            $fs = get_file_storage();
            $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);

            if (count($files) >= 1) {
                $file = reset($files);
                // Validate this TinCan package.
                $errors = array_merge($errors, tincanlaunch_validate_package($file));
            }
        }
        return $errors;
    }
}
