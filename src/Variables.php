<?php
namespace KirbyOutsource;

use F;
use yaml;

class Variables {
  const EXTENSION = '.yml';

  public static function getFilePath ($filename) {
    $path = kirby()->root('languages');
    $folder = option('oblik.easyvars.folder');

    if (!empty($folder)) {
      $path .= DS . $folder;
    }

    return $path . DS . $filename . self::EXTENSION;
  }

  public static function get ($language) {
    $filePath = static::getFilePath($language);

    if (file_exists($filePath)) {
      return yaml::decode(F::read($filePath));
    } else {
      return null;
    }
  }

  public static function update ($language, $data) {
    $currentData = static::get($language);

    if (is_array($currentData)) {
      $data = array_replace_recursive($currentData, $data);
    }

    $filePath = static::getFilePath($language);
    $encodedData = yaml::encode($data);

    file_put_contents($filePath, $encodedData);
  }
}
