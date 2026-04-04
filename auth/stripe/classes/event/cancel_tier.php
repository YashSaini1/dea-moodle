<?php

namespace auth_stripe\event;

use auth_stripe\model\user_tier;

class cancel_tier extends \core\event\base {

    public user_tier $user_tier;

    public static function create_by_tier($user_tier, $eventdata = null){
        $eventdata['userid'] = $user_tier->userid;
        $eventdata['context'] = \context_system::instance();

        /** @var static $event */
        $event = static::create($eventdata);
        $event->user_tier = $user_tier;
        return $event;
    }

    /**
     * @inheritDoc
     */
    protected function init(){
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = static::LEVEL_OTHER;
    }
}