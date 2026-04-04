<?php

namespace qtype_pythonrunner\traits;

trait python_sandbox_trait {

    use python_trait;

    /**
     * A list of available sandboxes. Keys are the externally known sandbox names
     * as they appear in the exported questions, values are the associated
     * class names. File names are the same as the class names with the
     * leading qtype_pythonrunner and all underscores removed.
     * @return array
     */
    public static function available_sandboxes(){
        return array(
            'jobesandbox'   => 'qtype_pythonrunner_jobesandbox',
            'pythonsandbox' => 'qtype_pythonrunner_pythonsandbox',
        );
    }
}