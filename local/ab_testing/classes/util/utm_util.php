<?php

namespace local_ab_testing\util;

class utm_util {

    /**
     * Remove all parameters from the url and get called file
     *
     * TODO: This may not be suitable in some situations, so you need to add new function parameter for new logic
     *
     * @param $url
     *
     * @return mixed
     */
    public static function clean_url($url){
        static $paths = [];

        if (!array_key_exists($url, $paths)){
            $url_params = parse_url($url);
//            $regex = '/((utm_\w+=\w*)&?)+/';

//            $url_query = $url_params['query'];

            // hard-coded id values
//            if (strpos($url_query, 'id') !== false && !empty($url_query)){
//                $url_query = preg_replace($regex, '', $url_params['query']);
//                $url_query = trim($url_query, '&');
//                $url_query = '?'.$url_query;
//            } else {
//                $url_query = '';
//            }
            $paths[$url] = $url_params['path'];
        }

        return $paths[$url];
    }

    public static function add_utm_to_url($url, $utm_query){
        $url_params = parse_url($url);
        if (!empty($url_params['query'])){
            $utm_query = $url_params['query'].'&'.$utm_query;
        }
        return $url_params['path'].'?'.$utm_query;
    }
}