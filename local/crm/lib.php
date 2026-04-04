<?php

function add_plus(string $phone): string {
    if (empty($phone))
        return '';

    $phone = preg_replace('/\s+/', '', $phone);

    if (strpos($phone, '+') !== 0)
        return '+' . $phone;

    return $phone;
}
