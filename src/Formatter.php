<?php

namespace KirbyOutsource;

use KirbyOutsource\KirbytagParser;

class Formatter {
  public static function decode ($field, $blueprint) {
    $parseYaml = $blueprint['exporter']['yaml'] ?? null;

    if ($parseYaml) {
      $data = $field->yaml();
    } else {
      $data = $field->value();
    }

    if (is_array($data)) {
      $whitelist = null;

      if (is_array($parseYaml)) {
        $whitelist = $parseYaml;
      }

      foreach ($data as $key => $value) {
        if ($whitelist && !in_array($key, $whitelist)) {
          unset($data[$key]);
        } else {
          $data[$key] = KirbytagParser::toXML($value);
        }
      }
    } else {
      $data = KirbytagParser::toXML($data);
    }

    return $data;
  }
}
