<?php

namespace Oblik\Outsource;

use Oblik\Variables\Manager;

class Variables
{
    public static function export(string $lang)
    {
        $handler = Manager::getHandler($lang);
        $data = null;

        if ($handler) {
            $data = $handler->data;
        }

        return $data;
    }

    public static function import(string $lang, array $data)
    {
        $handler = Manager::getHandler($lang);

        if ($handler && is_array($handler->data)) {
            $data = array_replace_recursive($handler->data, $data);
        }

        $handler->data = $data;
        $handler->write();
    }
}
