<?php

namespace dolphiq\redirect\events;

use yii\base\Event;

class RedirectEvent extends Event
{
    /**
     * @var string The URI requested
     */
    public $uri;
}
