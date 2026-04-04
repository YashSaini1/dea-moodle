<?php

namespace qtype_sqlrunner\form;

require_once($CFG->libdir.'/formslib.php');

class dataset_manager_form extends \moodleform {

    const TABLES_REGEXP = "/CREATE\s+TABLE\s*[`'".'"'."]?(\w+)[`'".'"'."]?/";

    const DEFAULT_REPEATED = 3;

    const REPEAT_NUMBER_FIELD = 'repeated_datasets';

    protected $_fileoptions;

    protected $_files_tables;

    protected $_ctx;

    protected $_repeats = 0;

    /**
     * @inheritDoc
     */
    protected function definition(){
        $mform = $this->_form;
        $dataset = $this->_customdata['dataset_rec'];

        $this->_ctx = \context_system::instance();
        $this->_fileoptions = array('subdirs' => false, 'maxfiles' => 1, 'maxbytes' => -1, 'accepted_types' => '.sql');

        $this->_repeats = static::DEFAULT_REPEATED;
        if (!empty($dataset) && !empty($dataset->id)){
            $this->_repeats = 1;
        }
        $mform->addElement('hidden', 'id', $dataset ? $dataset->id : 0);
        $mform->setType('id', PARAM_INT);

        $this->_add_multiple_fields_fields($mform);

        // Insert the attachment section to allow file uploads.
        $this->add_action_buttons(true, get_string('savechanges'));
    }

    public function set_data($data){
        $repeated_count = optional_param(static::REPEAT_NUMBER_FIELD, 0, PARAM_INT);
        $repeated_count += $this->_repeats;
        $field_name = 'dataset';

        for ($i = 0; $i < $repeated_count; $i++){
            $temp_field = $field_name.'['.$i.']';
            $draftid = file_get_submitted_draft_itemid($temp_field);
            $itemid = $data->id;
            file_prepare_draft_area($draftid, $this->_ctx->id, SQL_RUNNER, 'dataset', $itemid, $this->_fileoptions);
            $data->$temp_field = $draftid; // File manager needs this (and we need it when saving).
        }
        parent::set_data($data);
    }

    /**
     * {@inheritDoc}
     */
    public function validation($data, $files){
        $errors = parent::validation($data, $files);
        foreach ($data['dataset'] as $key => $draftid){
            $tables_field = "dataset_tables[$key]";
            $files_tables = $this->_parse_uploaded_files($draftid);

            $inputted_tables = trim($data['dataset_tables'][$key]);
            if (empty($inputted_tables) && empty($files_tables)){
                continue;
            }
            $inputted_tables = explode(',', $inputted_tables);

            $checked_inputted_tables = $missing_file = $duplicated_tables = $existed_tables = [];
            foreach ($inputted_tables as $it){
                $it = trim($it);
                // check only non-duplicated inputted tables
                if (!empty($checked_inputted_tables[$it])){
                    continue;
                }
                $checked_inputted_tables[$it] = true;

                // check if this table already exists in datasets
                if (check_dataset_table_exists($it, $data['id'])){
                    $existed_tables[] = $it;
                }

                if (empty($files_tables[$it])){
                    $missing_file[] = $it;
                    continue;
                }

                // make an error message if in files there are duplicated tables, because we cannot create it
                if ($files_tables[$it] > 1){
                    $duplicated_tables[] = $it;
                }
                unset($files_tables[$it]);
            }

            $errors[$tables_field] = '';
            if (!empty($duplicated_tables)){
                $errors[$tables_field] = sqlrunner_str('error_duplicated_tables', implode(',', $duplicated_tables))."</br>";
            }
            if (!empty($missing_file)){
                $errors[$tables_field] .= sqlrunner_str('error_missed_file_tables', implode(',', $missing_file))."</br>";
            }
            if (!empty($files_tables)){
                $errors[$tables_field] .= sqlrunner_str('error_missed_inputted_tables', implode(',', array_keys($files_tables)))."</br>";
            }
            if (!empty($existed_tables)){
                $errors[$tables_field] = sqlrunner_str('error_existed_table', implode(',', $existed_tables))."</br>";
            }
            if (empty($errors[$tables_field])){
                unset($errors[$tables_field]);
            }
        }

        return $errors;
    }

    protected function _parse_uploaded_files($draftid){
        $files_tables = [];
        $files = $this->get_uploaded_files($draftid);
        foreach ($files as $file){
            // Do not store file content, move it directly into the parse function
            $file_tables = $this->_get_all_file_tables($file->get_content());
            foreach ($file_tables as $ft){
                $ft = trim($ft);
                $files_tables[$ft] = !empty($files_tables[$ft]) ? ++$files_tables[$ft] : 1;
            }
            $this->_files_tables[$draftid] = $file_tables;
        }
        return $files_tables;
    }

    public function get_uploaded_files($draftid){
        global $USER;
        $fs = get_file_storage();
        $ctx = \context_user::instance($USER->id);
        return $fs->get_directory_files($ctx->id, 'user', 'draft', $draftid, '/', false);
    }

    protected function _get_all_file_tables($sql_file_content){
        $result = [];
        preg_match_all(static::TABLES_REGEXP, $sql_file_content, $result);
        return $result[1];
    }

    ///////////////// Custom functions
    protected function _add_multiple_fields_fields(&$mform){
        $repeatedoptions = array();
        $repeted = $this->_get_multiple_fields($mform, $repeatedoptions);
        $this->repeat_elements($repeted, $this->_repeats, $repeatedoptions, static::REPEAT_NUMBER_FIELD,
            'addfield', $this->_repeats < 2 ? 0 : 3, $this->_get_more_choices_string(), true);
    }

    /**
     * Language string to use for 'Add {no} more {whatever we call answers}'.
     */
    protected function _get_more_choices_string(){
        return get_string('addmorechoiceblanks', 'question');
    }

    /**
     * @param \MoodleQuickForm $mform
     * @param                  $repeatedoptions
     *
     * @return array
     */
    protected function _get_multiple_fields($mform, &$repeatedoptions){
        $groupelements = array();
        $groupelements[] = $mform->createElement('filemanager', 'dataset', sqlrunner_str('form_dataset_name'), null, $this->_fileoptions);
        $groupelements[] = $mform->createElement('text', 'dataset_tables', sqlrunner_str('form_dataset_tables_name'));

        $repeatedoptions['dataset_tables']['type'] = PARAM_TEXT;
        $repeatedoptions['dataset']['helpbutton'] = ['form_dataset', SQL_RUNNER];
        $repeatedoptions['dataset_tables']['helpbutton'] = ['form_dataset_tables', SQL_RUNNER];
        return $groupelements;
    }

    public function get_files_tables(){
        return $this->_files_tables;
    }

    /**
     * Method to add a repeating group of elements to a form.
     *
     * @param array  $elementobjs      Array of elements or groups of elements that are to be repeated
     * @param int    $repeats          no of times to repeat elements initially
     * @param array  $options          a nested array. The first array key is the element name.
     *                                 the second array key is the type of option to set, and depend on that option,
     *                                 the value takes different forms.
     *                                 'default'    - default value to set. Can include '{no}' which is replaced by the repeat number.
     *                                 'type'       - PARAM_* type.
     *                                 'helpbutton' - array containing the helpbutton params.
     *                                 'disabledif' - array containing the disabledIf() arguments after the element name.
     *                                 'rule'       - array containing the addRule arguments after the element name.
     *                                 'expanded'   - whether this section of the form should be expanded by default. (Name be a header element.)
     *                                 'advanced'   - whether this element is hidden by 'Show more ...'.
     * @param string $repeathiddenname name for hidden element storing no of repeats in this form
     * @param string $addfieldsname    name for button to add more fields
     * @param int    $addfieldsno      how many fields to add at a time
     * @param string $addstring        name of button, {no} is replaced by no of blanks that will be added.
     * @param bool   $addbuttoninside  if true, don't call closeHeaderBefore($addfieldsname). Default false.
     * @param string $deletebuttonname if specified, treats the no-submit button with this name as a "delete element" button
     *                                 in each of the elements
     *
     * @return int no of repeats of element in this page
     */
    public function repeat_elements($elementobjs, $repeats, $options, $repeathiddenname,
        $addfieldsname, $addfieldsno = 5, $addstring = null, $addbuttoninside = false,
        $deletebuttonname = ''){
        if ($addstring === null){
            $addstring = get_string('addfields', 'form', $addfieldsno);
        } else {
            $addstring = str_ireplace('{no}', $addfieldsno, $addstring);
        }
        $repeats = $this->optional_param($repeathiddenname, $repeats, PARAM_INT);
        $addfields = $this->optional_param($addfieldsname, '', PARAM_TEXT);
        $oldrepeats = $repeats;
        if (!empty($addfields)){
            $repeats += $addfieldsno;
        }
        $mform =& $this->_form;
        $mform->registerNoSubmitButton($addfieldsname);
        $mform->addElement('hidden', $repeathiddenname, $repeats);
        $mform->setType($repeathiddenname, PARAM_INT);
        //value not to be overridden by submitted value
        $mform->setConstants(array($repeathiddenname => $repeats));
        $namecloned = array();
        $no = 1;
        for ($i = 0; $i < $repeats; $i++){
            if ($deletebuttonname){
                $mform->registerNoSubmitButton($deletebuttonname."[$i]");
                $isdeleted = $this->optional_param($deletebuttonname."[$i]", false, PARAM_RAW) ||
                    $this->optional_param($deletebuttonname."-hidden[$i]", false, PARAM_RAW);
                if ($isdeleted){
                    $mform->addElement('hidden', $deletebuttonname."-hidden[$i]", 1);
                    $mform->setType($deletebuttonname."-hidden[$i]", PARAM_INT);
                    continue;
                }
            }
            foreach ($elementobjs as $elementobj){
                $elementclone = fullclone($elementobj);
                $this->repeat_elements_fix_clone($i, $elementclone, $namecloned);

                if ($elementclone instanceof \HTML_QuickForm_group && !$elementclone->_appendName){
                    foreach ($elementclone->getElements() as $el){
                        $this->repeat_elements_fix_clone($i, $el, $namecloned);
                    }
                    $elementclone->setLabel(str_replace('{no}', $no, $elementclone->getLabel()));
                } else {
                    if ($elementobj instanceof \HTML_QuickForm_submit && $elementobj->getName() == $deletebuttonname){
                        // Mark the "Delete" button as no-submit.
                        $onclick = $elementclone->getAttribute('onclick');
                        $skip = 'skipClientValidation = true;';
                        $onclick = ($onclick !== null) ? $skip.' '.$onclick : $skip;
                        $elementclone->updateAttributes(['data-skip-validation' => 1, 'data-no-submit' => 1, 'onclick' => $onclick]);
                    }
                }

                // Mark newly created elements, so they know not to look for any submitted data.
                if ($i >= $oldrepeats){
                    $mform->note_new_repeat($elementclone->getName());
                }

                $mform->addElement($elementclone);
                $no++;
            }
        }
        for ($i = 0; $i < $repeats; $i++){
            foreach ($options as $elementname => $elementoptions){
                $pos = strpos($elementname, '[');
                if ($pos !== false){
                    $realelementname = substr($elementname, 0, $pos)."[$i]";
                    $realelementname .= substr($elementname, $pos);
                } else {
                    $realelementname = $elementname."[$i]";
                }
                foreach ($elementoptions as $option => $params){

                    switch ($option){
                        case 'default' :
                            $mform->setDefault($realelementname, str_replace('{no}', $i + 1, $params));
                            break;
                        case 'helpbutton' :
                            $params = array_merge(array($realelementname), $params);
                            call_user_func_array(array(&$mform, 'addHelpButton'), $params);
                            break;
                        case 'disabledif' :
                        case 'hideif' :
                            $pos = strpos($params[0], '[');
                            $ending = '';
                            if ($pos !== false){
                                $ending = substr($params[0], $pos);
                                $params[0] = substr($params[0], 0, $pos);
                            }
                            foreach ($namecloned as $num => $name){
                                if ($params[0] == $name){
                                    $params[0] = $params[0]."[$i]".$ending;
                                    break;
                                }
                            }
                            $params = array_merge(array($realelementname), $params);
                            $function = ($option === 'disabledif') ? 'disabledIf' : 'hideIf';
                            call_user_func_array(array(&$mform, $function), $params);
                            break;
                        case 'rule' :
                            if (is_string($params)){
                                $params = array(null, $params, null, 'client');
                            }
                            $params = array_merge(array($realelementname), $params);
                            call_user_func_array(array(&$mform, 'addRule'), $params);
                            break;

                        case 'type':
                            $mform->setType($realelementname, $params);
                            break;

                        case 'expanded':
                            $mform->setExpanded($realelementname, $params);
                            break;

                        case 'advanced' :
                            $mform->setAdvanced($realelementname, $params);
                            break;
                    }
                }
            }
        }

        // add or not 'add more' button
        if ($addfieldsno > 0){
            $mform->addElement('submit', $addfieldsname, $addstring, [], false);
        }

        if (!$addbuttoninside){
            $mform->closeHeaderBefore($addfieldsname);
        }

        return $repeats;
    }
}