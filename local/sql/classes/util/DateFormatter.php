<?php

namespace local_sql\util;

class DateFormatter {

    public static function format_time($timestamp, $wrap = true){
        $days = floor($timestamp / DAYSECS);
        if ($days > 0){
            return static::_format_days($days, $wrap);
        }

        $hours = floor($timestamp / HOURSECS);
        if ($hours > 0){
            return static::_format_hours($hours, $wrap);
        }

        $min = floor($timestamp / MINSECS);
        return static::_format_minutes($min, $wrap);
    }

    protected static function _format_days($days, $wrap = true){
        $result = $wrap ? static::_wrap_date($days) : $days;
        if ($days == 1){
            return $result.' day';
        }
        return $result.' days';
    }

    protected static function _format_hours($hours, $wrap = true){
        $result = $wrap ? static::_wrap_date($hours) : $hours;
        if ($hours == 1){
            return $result.' hour';
        }
        return $result.' hours';
    }

    protected static function _format_minutes($min, $wrap = true){
        $result = $wrap ? static::_wrap_date($min) : $min;
        if ($min == 1){
            return $result.' minute';
        }
        return $result.' minutes';
    }

    protected static function _wrap_date($date){
        return \html_writer::span($date, 'date-wrapper');
    }
}