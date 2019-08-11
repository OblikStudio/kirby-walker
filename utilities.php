<?php

namespace Oblik\Outsource;

function merge_by_key(array $data, array $input, callable $callback, $key = 'id') {
    foreach ($input as $inputValue) {
        $inputId = $inputValue[$key] ?? null;

        if ($inputId !== null) {
            foreach ($data as &$dataValue) {
                $dataId = $dataValue[$key] ?? null;

                if ($inputId === $dataId) {
                    $dataValue = $callback($dataValue, $inputValue);
                }
            }
        }
    }

    return $data;
}
