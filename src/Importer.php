<?php
namespace KirbyOutsource;

use yaml;

class Importer {
  private $language = null;
  private $defaultLanguage = null;

  function __construct ($language = null) {
    $this->language = $language;
    $this->defaultLanguage = kirby()->defaultLanguage()->code();
  }

  public function parseContent ($content, $fieldBlueprints) {
    $data = [];

    foreach ($fieldBlueprints as $key => $blueprint) {
      $field = $content->$key();

      if ($blueprint['type'] === 'structure') {
        $data[$key] = $field->yaml();
      } else {
        $data[$key] = $field->value();
      }
    }

    return $data;
  }

  public function update ($object, $data) {
    $fieldBlueprints = $object->blueprint()->fields();
    $translatedContent = $this->parseContent($object->content($this->language), $fieldBlueprints);

    // If https://forum.getkirby.com/t/page-update-copies-fields-on-non-default-languages/13367
    // is resolved, it would be enough to only merge structure fields.
    $mergedData = array_replace_recursive($translatedContent, $data);
    relog($translatedContent, $data, $mergedData);

    foreach ($mergedData as $key => $value) {
      $blueprint = $fieldBlueprints[$key] ?? null;
      $shouldUnset = false;

      if ($blueprint) {
        if ($blueprint['type'] === 'structure') {
          $mergedData[$key] = yaml::encode($value);
        }

        if (($blueprint['translate'] ?? true) === false) {
          $shouldUnset = true;
        }
      } else {
        $shouldUnset = true;
      }

      if ($shouldUnset) {
        unset($mergedData[$key]);
      }
    }

    $object->writeContent($mergedData, $this->language);
  }

  public function import ($data) {
    $site = site();

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
