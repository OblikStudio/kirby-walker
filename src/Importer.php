<?php
namespace KirbyExporter;

use yaml;

class Importer {
  private $language = null;
  private $defaultLanguage = null;

  function __construct ($language) {
    $this->language = $language;
    $this->defaultLanguage = kirby()->defaultLanguage()->code();
  }

  public static function clean ($data) {
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $data[$key] = static::clean($value);
      }
    }

    return array_filter($data);
  }

  public function update ($object, $data) {
    $defaultData = [];
    $defaultContent = $object->content($this->defaultLanguage);
    $fieldBlueprints = $object->blueprint()->fields();

    // Get default language data to obtain translate:false fields; also parse
    // structure fields to arrays.
    foreach ($fieldBlueprints as $key => $blueprint) {
      $field = $defaultContent->$key();

      if ($blueprint['type'] === 'structure') {
        $defaultData[$key] = $field->yaml();
      } else {
        $defaultData[$key] = $field->value();
      }
    }

    // If https://forum.getkirby.com/t/page-update-copies-fields-on-non-default-languages/13367
    // is resolved, it would be enough to only merge structure fields.
    $mergedData = array_replace_recursive($defaultData, $data);

    // Encode all arrays back to YAML.
    foreach ($mergedData as $key => $value) {
      if (
        isset($fieldBlueprints[$key]) &&
        $fieldBlueprints[$key]['type'] === 'structure'
      ) {
        $mergedData[$key] = yaml::encode($value);
      }
    }

    $object->update($mergedData, $this->language);
  }

  public function import ($data) {
    $site = site();
    $data = static::clean($data);

    array_walk_recursive($data, function (&$value) {
      $value = KirbytagParser::parse($value);
    });

    if (!empty($data['site'])) {
      $this->update($site, $data['site']);
    }

    if (!empty($data['pages'])) {
      foreach ($data['pages'] as $id => $pageData) {
        $page = $site->page($id);

        if ($page) {
          $this->update($page, $pageData);
        }
      }
    }

    if (!empty($data['files'])) {
      foreach ($data['files'] as $id => $fileData) {
        $file = $site->file($id);

        if ($file) {
          $this->update($file, $fileData);
        }
      }
    }

    if (!empty($data['variables'])) {
      Variables::update($this->language, $data['variables']);
    }

    return true;
  }
}
