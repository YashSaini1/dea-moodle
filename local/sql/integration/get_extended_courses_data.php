<?php

require_once '../../../config.php';

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename=extended_courses_data.json');

global $DB;

$sql = "
    SELECT
        c.id AS course_id,
        c.fullname AS course_name,
        u.id AS user_id,
        CONCAT(u.firstname, ' ', u.lastname) AS user_name,
        MIN(e.timecreated) AS extension_time
    FROM
        {sql_extended} e
    JOIN
        {user} u ON e.userid = u.id
    JOIN
        {course_sections} cs ON e.sectionid = cs.id
    JOIN
        {course} c ON cs.course = c.id
    GROUP BY
        c.id, c.fullname, u.id, u.firstname, u.lastname
    ORDER BY
        c.fullname, user_name;
";

$recordset = $DB->get_recordset_sql($sql);

$courses = [];
if ($recordset->valid()) {
    foreach ($recordset as $row) {
        if (!isset($courses[$row->course_id])) {
            $courses[$row->course_id] = [
                'id' => (int)$row->course_id,
                'name' => $row->course_name,
                'users' => [],
            ];
        }

        $courses[$row->course_id]['users'][] = [
            'id' => (int)$row->user_id,
            'name' => $row->user_name,
            'extension_time' => (int)$row->extension_time,
        ];
    }
}
$recordset->close();

$output_data = array_values($courses);

$json_data = json_encode($output_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo $json_data;
exit;