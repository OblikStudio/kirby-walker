<?php
namespace KirbyExporter;

use yaml;

class Importer {
  private $language = null;

  function __construct ($language) {
    $this->language = $language;
  }

  public static function clean ($data) {
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $data[$key] = static::clean($value);
      }
    }

    return array_filter($data);
  }

  public static function revertKirbytagXML ($data) {
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $data[$key] = static::revertKirbytagXML($value);
      } else if (is_string($value)) {
        $data[$key] = KirbytagXML::revert($value);
      }
    }

    return $data;
  }

  public function updatePage ($page, $data) {
    // Clean the input data so that empty strings won't overwrite the non-
    // empty default language values later.
    $data = static::clean($data);

    if (empty($data)) {
      return; // nothing to update
    }

    $fieldBlueprints = $page->blueprint()->fields();
    $defaultData = [];

    foreach ($fieldBlueprints as $key => $blueprint) {
      $field = $page->$key();

      if ($blueprint['type'] === 'structure') {
        $defaultData[$key] = $field->yaml();
      } else {
        $defaultData[$key] = $field->value();
      }
    }

    $mergedData = array_replace_recursive($defaultData, $data);

    // Encode all arrays back to YAML because that's how Kirby stores
    // them. If they are not pased, an empty value will be saved.
    foreach ($mergedData as $key => $value) {
      if (
        isset($fieldBlueprints[$key]) &&
        $fieldBlueprints[$key]['type'] === 'structure'
      ) {
        $mergedData[$key] = yaml::encode($value);
      }
    }

    // static::revertKirbytagXML($mergedData)
    $page->update($mergedData, $this->language);
  }

  public function importPages ($data) {
    foreach ($data as $pageId => $value) {
      $page = null;

      if ($pageId === '$site') {
        $page = site();
      } else {
        $page = site()->children()->find($pageId);
      }

      if ($page) {
        $this->updatePage($page, $value, $this->lang);
      }
    }
  }

  public function importVariables ($data) {
    $dir = kirby()->roots()->languages();

    if (!is_dir($dir)) {
      mkdir($dir);
    }

    $file = $dir . DS . $this->lang . '.yml';
    $encoded = Yaml::encode($data);

    file_put_contents($file, $encoded);
  }

  public function import ($data) {
    $data = static::clean($data);

    $this->defaultLang = kirby()->defaultLanguage()->code();

    if (isset($data['site'])) {
      $this->updatePage(site(), $data['site']);
    }

    // if (isset($data['pages'])) {
    //   $this->importPages($data['pages']);
    // }

    // if (isset($data['variables'])) {
    //   $this->importVariables($data['variables']);
    // }
  }
}
