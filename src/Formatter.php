<?php

namespace KirbyOutsource;

use KirbyOutsource\Walker;
use KirbyOutsource\KirbytagParser;

class Formatter {
  public $settings = [];

  function __construct ($settings = []) {
    $this->settings = array_replace($this->settings, $settings);
  }

  public function decode ($entity) {
    $walker = new Walker($this->settings, function ($field, $blueprint) {
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
    });

    return $walker->walk($entity);
  }
}
