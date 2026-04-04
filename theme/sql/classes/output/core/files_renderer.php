<?php

namespace theme_sql\output\core;

use form_filemanager;

defined('MOODLE_INTERNAL') || die();

/**
 * Rendering of files viewer related widgets.
 * @package   core
 * @subpackage file
 * @copyright 2010 Dongsheng Cai
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */

/**
 * File browser render
 *
 * @copyright 2010 Dongsheng Cai
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class files_renderer extends \core_files_renderer {

    /**
     * Prints the file manager and initializes all necessary libraries
     *
     * <pre>
     * $fm = new form_filemanager($options);
     * $output = get_renderer('core', 'files');
     * echo $output->render($fm);
     * </pre>
     *
     * @param form_filemanager $fm File manager to render
     * @return string HTML fragment
     */
    public function render_form_filemanager($fm) {
        $html = $this->fm_print_generallayout($fm);
        $module = array(
            'name'=>'form_filemanager',
            'fullpath'=>'/lib/form/filemanager.js',
            'requires' => array('moodle-core-notification-dialogue', 'core_filepicker', 'base', 'io-base', 'node', 'json', 'core_dndupload', 'panel', 'resize-plugin', 'dd-plugin'),
            'strings' => array(
                array('error', 'moodle'), array('info', 'moodle'), array('confirmdeletefile', 'repository'),
                array('draftareanofiles', 'repository'), array('entername', 'repository'), array('enternewname', 'repository'),
                array('invalidjson', 'repository'), array('popupblockeddownload', 'repository'),
                array('unknownoriginal', 'repository'), array('confirmdeletefolder', 'repository'),
                array('confirmdeletefilewithhref', 'repository'), array('confirmrenamefolder', 'repository'),
                array('confirmrenamefile', 'repository'), array('newfolder', 'repository'), array('edit', 'moodle'),
                array('originalextensionchange', 'repository'), array('originalextensionremove', 'repository'),
                array('aliaseschange', 'repository'), ['nofilesselected', 'repository'],
                ['confirmdeleteselectedfile', 'repository'], ['selectall', 'moodle'], ['deselectall', 'moodle'],
                ['selectallornone', 'form'],
            )
        );
        if ($this->page->requires->should_create_one_time_item_now('core_file_managertemplate')) {
            $this->page->requires->js_init_call('M.form_filemanager.set_templates',
                array($this->filemanager_js_templates()), true, $module);
        }
        $this->page->requires->js_call_amd('core/checkbox-toggleall', 'init');
        $this->page->requires->js_init_call('M.form_filemanager.init', array($fm->options), true, $module);

        // non javascript file manager
        $html .= '<noscript>';
        $html .= "<div><object type='text/html' data='".$fm->get_nonjsurl()."' height='160' width='600' style='border:1px solid #000'></object></div>";
        $html .= '</noscript>';


        return $html;
    }

    /**
     * Returns html for displaying one file manager
     *
     * @param form_filemanager $fm
     * @return string
     */
    protected function fm_print_generallayout($fm) {
        $context = [
            'client_id' => $fm->options->client_id,
            'helpicon' => $this->help_icon('setmainfile', 'repository'),
            'restrictions' => $this->fm_print_restrictions($fm)
        ];
        return $this->render_from_template('theme_sql/core/filemanager_page_generallayout', $context);
    }

    /**
     * FileManager JS template for window with file information/actions.
     *
     */
    protected function fm_js_template_fileselectlayout() {
        $context = [
            'helpicon' => $this->help_icon('setmainfile', 'repository'),
            'licensehelpicon' => $this->create_license_help_icon_context(),
            'columns' => true
        ];
        return $this->render_from_template('core/filemanager_fileselect', $context);
    }

    /**
     * Displays restrictions for the file manager
     *
     * @param form_filemanager $fm
     * @return string
     */
    protected function fm_print_restrictions($fm) {
        $maxbytes = display_size($fm->options->maxbytes, 0);
        $strparam = (object) array('size' => $maxbytes, 'attachments' => $fm->options->maxfiles,
                                   'areasize' => display_size($fm->options->areamaxbytes, 0));
        $hasmaxfiles = !empty($fm->options->maxfiles) && $fm->options->maxfiles > 0;
        $hasarealimit = !empty($fm->options->areamaxbytes) && $fm->options->areamaxbytes != -1;
        if ($hasmaxfiles && $hasarealimit) {
            $maxsize = get_string('maxsizeandattachmentsandareasize', 'moodle', $strparam);
        } else {
            $maxsize = get_string('maxfilesize', 'theme_sql', $maxbytes);
        }

        return '<span>'. $maxsize . '</span>';
    }

    /**
     * Template for FilePicker with general layout (not QuickUpload).
     *
     *
     * @return string
     */
    protected function fp_js_template_generallayout(){
        return $this->render_from_template('core/filemanager_modal_generallayout', []);
    }
}
