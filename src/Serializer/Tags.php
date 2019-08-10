<?php

namespace Oblik\Outsource\Serializer;

use Kirby\Toolkit\A;

class Tags
{
    public static function decode($input, $options)
    {
        return $options['field']->split();
    }

    public static function encode($input, $options)
    {
        return A::join($input);
    }
}
