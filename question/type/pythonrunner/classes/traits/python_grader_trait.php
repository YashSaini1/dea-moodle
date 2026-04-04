<?php

namespace qtype_pythonrunner\traits;

trait python_grader_trait {

    use python_trait;

    /**
     * A list of available sandboxes. Keys are the externally known sandbox names
     * as they appear in the exported questions, values are the associated
     * class names. File names are the same as the class names with the
     * leading qtype_pythonrunner and all underscores removed.
     * @return array
     */
    public static function available_graders() {
        return array(
            'EqualityGrader'       => 'qtype_sqlrunner_equality_grader',
            'PythonEqualityGrader' => 'qtype_pythonrunner_python_equality_grader',
            'SqlEqualityGrader'    => 'qtype_sqlrunner_sql_equality_grader',
        );
    }
}