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

  public function merge ($dest, $source, $blueprint) {
    $data = null;

    foreach ($source as $key => $sourceFieldData) {
      $fieldData = null;
      $destFieldData = $dest[$key] ?? null;
      $fieldBlueprint = $blueprint[$key] ?? null;

      if ($fieldBlueprint) {
        $fieldType = $fieldBlueprint['type'] ?? null;

        if ($fieldType === 'structure' && is_array($destFieldData)) {
          $structureFieldsBlueprints = $this->decodeWalker->processBlueprints($fieldBlueprint['fields']);

          foreach ($sourceFieldData as $index => $sourceEntry) {
            $destEntry = $destFieldData[$index] ?? null; // id maps go here

            if ($destEntry) {
              $fieldData[] = $this->merge($destEntry, $sourceEntry, $structureFieldsBlueprints);
            }
          }
        } else {
          // custom merges here
          if (is_array($sourceFieldData) && is_array($destFieldData)) {
            $fieldData = array_replace_recursive($destFieldData, $sourceFieldData);
          } else {
            $fieldData = $sourceFieldData;
          }
        }
      }

      $data[$key] = $fieldData;
    }

    return $data;
  }

  public function update ($model, $data) {
    $currentData = $this->decodeWalker->walk($model);
    $prints = $this->decodeWalker->processBlueprints($model->blueprint()->fields());
    $mergedData = $this->merge($currentData, $data, $prints);

    $model->update($mergedData, $this->settings['language']);
    relog($currentData, $data, $mergedData);
  }

  public function import ($data) {
    $site = site();

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
