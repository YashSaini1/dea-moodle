<?php

function gen_safe_string($length): string {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_len = strlen($characters);
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $random_i = random_int(0, $characters_len - 1);
        $token .= $characters[$random_i];
    }
    return $token;
}
