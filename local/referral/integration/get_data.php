<?php

require_once '../../../config.php';

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename=referral_data.json');

$sql = "
    SELECT
        ro.userid AS referrer_id,
        CONCAT(u.firstname, ' ', u.lastname) AS referrer_name,
        ROUND(ro.balance, 2) AS referrer_balance,
        COALESCE(
            (SELECT JSON_ARRAYAGG(
                JSON_OBJECT(
                    'id', u2.id,
                    'name', CONCAT(u2.firstname, ' ', u2.lastname),
                    'first_bonus', ri.is_bonus_allow,
                    'registration_time', u2.timecreated,
                    'payment_time', IF(
                        ri.is_bonus_allow = 0,
                        (
                            SELECT rp.timecreated
                            FROM {referral_payment} rp
                            WHERE rp.userid = u2.id
                            ORDER BY rp.timecreated ASC
                            LIMIT 1
                        ),
                        NULL
                    )
                )
            )
            FROM {referral_invited} ri
            JOIN {user} u2 ON ri.userid = u2.id
            WHERE ri.ownerid = ro.id),
          JSON_ARRAY()
        ) AS referrals,
        (SELECT COUNT(*) FROM {referral_invited} ri2 WHERE ri2.ownerid = ro.id) AS referral_count
    FROM
        {referral_owner} ro
    JOIN
        {user} u ON ro.userid = u.id
    WHERE
        EXISTS (SELECT 1 FROM {referral_invited} ri3 WHERE ri3.ownerid = ro.id)
    GROUP BY
        ro.id, u.firstname, u.lastname, ro.balance
    ORDER BY
        referral_count DESC, ro.id;
";

$results = $DB->get_records_sql($sql);

$referrers = [];
if ($results) {
    foreach ($results as $row) {
        $referrer = [
            'id'      => (int)$row->referrer_id,
            'name'    => $row->referrer_name,
            'balance' => $row->referrer_balance,
            'referrals' => json_decode($row->referrals),
        ];
        $referrers[] = $referrer;
    }
}

$json_data = json_encode($referrers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo $json_data;
exit;
