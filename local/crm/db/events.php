<?php

$observers = array(
    array(
        'eventname' => '\core\event\user_created',
        'callback' => '\local_crm\event\observer::observer',
    ),
);
