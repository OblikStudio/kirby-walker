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

    foreach ($blueprint as $key => $fieldBlueprint) {
      $fieldData = null;
      $sourceFieldData = $source[$key] ?? null;
      $destFieldData = $dest[$key] ?? null;
      $fieldType = $fieldBlueprint['type'] ?? null;

      if ($sourceFieldData && $destFieldData) {
        if ($fieldType === 'structure' && is_array($destFieldData)) {
          $structureFieldsBlueprints = $this->decodeWalker->processBlueprints($fieldBlueprint['fields']);

          foreach ($sourceFieldData as $index => $sourceEntry) {
            $destEntry = $destFieldData[$index] ?? null; // id maps go here

            if ($destEntry) {
              $fieldData[] = $this->merge($destEntry, $sourceEntry, $structureFieldsBlueprints);
            }
          }

          foreach ($destFieldData as $index => $destEntry) {
            if (!isset($fieldData[$index])) {
              $fieldData[$index] = $destEntry;
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
      } else if ($sourceFieldData !== null) {
        $fieldData = $sourceFieldData;
      } else if ($destFieldData !== null) {
        // Prevent any old data from getting lost.
        $fieldData = $destFieldData;
      }

      if ($fieldData !== null) {
        $data[$key] = $fieldData;
      }
    }

    return $data;
  }

  public function update ($model, $data) {
    // Holds the current (decoded) model values for the given translation
    // (language specified in Walker settings). If there's no value for a
    // certain field or no content at all, Kirby pulls from the default txt.
    $currentData = $this->decodeWalker->walk($model);

    // Uses the Walker to create blueprints with the same settings.
    $fieldsBlueprint = $this->decodeWalker->processBlueprints(
      $model->blueprint()->fields()
    );

    $mergedData = $this->merge($currentData, $data, $fieldsBlueprint);
    $model->writeContent($mergedData, $this->settings['language']);
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
