<?php

namespace KirbyOutsource;

use Yaml;
use KirbyOutsource\Walker;
use KirbyOutsource\Formatter;

class Importer {
  private $settings = [];

  function __construct ($settings = []) {
    $this->settings = $settings;
    $this->decodeWalker = new Walker($this->settings, ['KirbyOutsource\Formatter', 'decode']);
  }

  public function merge (&$a, &$b, $blueprint) {
    $type = $blueprint['type'] ?? null;

    if ($type === 'structure') {
      $structurePrints = $this->decodeWalker->processBlueprints($blueprint['fields']);

      foreach ($a as $index => $currentEntry) {
        $inputEntry = $b[$index] ?? null;

        if ($inputEntry) {
          foreach ($currentEntry as $key => $currentValue) {
            $inputValue = $inputEntry[$key] ?? null;

            if ($inputValue) {
              $currentEntry[$key] = $this->merge($currentValue, $inputValue, $structurePrints);
            }
          }
        }
      }
    }

    return $b;
  }

  public function update ($model, $data) {
    $currentData = $this->decodeWalker->walk($model);
    $prints = $this->decodeWalker->processBlueprints($model->blueprint()->fields());
    $mergedData = $this->merge($currentData, $data, $prints);

    $model->update($mergedData, $this->settings['language']);

    relog($currentData, $data, $mergedData);
    // $fieldBlueprints = $object->blueprint()->fields();
    // $translatedContent = $this->parseContent($object->content($this->language), $fieldBlueprints);

    // // If https://forum.getkirby.com/t/page-update-copies-fields-on-non-default-languages/13367
    // // is resolved, it would be enough to only merge structure fields.
    // $mergedData = array_replace_recursive($translatedContent, $data);
    // relog($translatedContent, $data, $mergedData);

    // foreach ($mergedData as $key => $value) {
    //   $blueprint = $fieldBlueprints[$key] ?? null;
    //   $shouldUnset = false;

    //   if ($blueprint) {
    //     if ($blueprint['type'] === 'structure') {
    //       $mergedData[$key] = Yaml::encode($value);
    //     }

    //     if (($blueprint['translate'] ?? true) === false) {
    //       $shouldUnset = true;
    //     }
    //   } else {
    //     $shouldUnset = true;
    //   }

    //   if ($shouldUnset) {
    //     unset($mergedData[$key]);
    //   }
    // }

    // $object->writeContent($mergedData, $this->language);
  }

  public function import ($data) {
    $site = site();

    if (!empty($data['site'])) {
      $this->update($site, $data['site']);
    }

    // if (!empty($data['pages'])) {
    //   foreach ($data['pages'] as $id => $pageData) {
    //     $page = $site->page($id);

    //     if ($page) {
    //       $this->update($page, $pageData);
    //     }
    //   }
    // }

    // if (!empty($data['files'])) {
    //   foreach ($data['files'] as $id => $fileData) {
    //     $file = $site->file($id);

    //     if ($file) {
    //       $this->update($file, $fileData);
    //     }
    //   }
    // }

    // if (!empty($data['variables'])) {
    //   Variables::update($this->language, $data['variables']);
    // }

    return true;
  }
}
