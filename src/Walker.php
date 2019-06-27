<?php

namespace KirbyOutsource;

class Walker {
  public $settings = [
    'language' => null,
    'predicate' => null,
    'blueprints' => [],
    'fields' => []
  ];
  public $callback = null;

  function __construct ($settings = [], $callback) {
    $this->settings = array_replace($this->settings, $settings);
    $this->callback = $callback;
  }

  public function processBlueprints ($prints) {
    $blueprints = $this->settings['blueprints'];
    $fields = $this->settings['fields'];

    $prints = array_merge_recursive($prints, $blueprints);
    $prints = array_change_key_case($prints, CASE_LOWER);

    foreach ($prints as $key => $value) {
      $fieldType = $value['type'] ?? null;
      $fieldData = $fields[$fieldType] ?? null;

      if ($fieldData) {
        $prints[$key] = array_merge_recursive($prints[$key], $fieldData);
      }
    }

    return $prints;
  }

  private function walkStructure ($structure, $blueprint) {
    $data = null;
    $fieldBlueprints = $this->processBlueprints($blueprint['fields']);

    foreach ($structure as $entry) {
      $childData = $this->walkEntity($entry, $fieldBlueprints);

      if (!empty($childData)) {
        $data[] = $childData;
      }
    }

    return $data;
  }

  public function walkField ($blueprint, $input) {
    if (
      is_callable($this->settings['predicate']) &&
      $this->settings['predicate']($blueprint, $input) === false
    ) {
      return null;
    }

    if ($blueprint['type'] === 'structure') {
      return $this->walkStructure($input->toStructure(), $blueprint);
    } else {
      return ($this->callback)($input, $blueprint);
    }

    return null;
  }

  public function walkEntity ($entity, $fieldBlueprints = null) {
    $data = null;
    $content = $entity->content($this->settings['language']);

    if (!$fieldBlueprints) {
      $fieldBlueprints = $this->processBlueprints(
        $entity->blueprint()->fields()
      );
    }

    foreach ($content->fields() as $key => $field) {
      $blueprint = $fieldBlueprints[$key] ?? null;

      if ($blueprint) {
        $fieldData = $this->walkField($blueprint, $field);

        if ($fieldData !== null) {
          $data[$key] = $fieldData;
        }
      }
    }

    return $data;
  }

  public function walk ($entity) {
    return $this->walkEntity($entity);
  }
}
