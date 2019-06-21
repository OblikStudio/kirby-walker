<?php
namespace KirbyExporter;

// loop over content instead of blueprint
// remove page [content, files], use class attributes to push into

class Exporter {
  private $settings = [
    'language' => null,
    'page' => null,
    'variables' => true,
    'blueprints' => [],
    'fields' => [],
    'fieldPredicate' => null
  ];

  function __construct ($settings = []) {
    $this->settings = array_replace($this->settings, $settings);
  }

  public function isFieldEligible ($blueprint) {
    $ignored = $blueprint['exporter']['ignore'] ?? false;
    $predicate = $this->settings['fieldPredicate'] ?? null;

    if ($ignored) {
      return false;
    }

    if (is_callable($predicate)) {
      return $predicate($blueprint);
    }

    return true;
  }

  // Extracts the data from a Structure, treating entries as content entities
  // like Page or File.
  private function extractStructure ($structure, $blueprint) {
    $data = null;
    $fieldBlueprints = $this->processBlueprints($blueprint['fields']);

    foreach ($structure as $entry) {
      $childData = $this->extractEntity($entry, $fieldBlueprints);

      if (!empty($childData)) {
        $data[] = $childData;
      }
    }

    return $data;
  }

  // Extracts the value of a content entity. Note that it handles Structure
  // fields, but not structures themselves.
  private function extractValue ($field, $blueprint) {
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

  // Extracts ModelWithContent fields based on eligibility and type.
  public function extractField ($blueprint, $input) {
    if ($this->isFieldEligible($blueprint)) {
      if ($blueprint['type'] === 'structure') {
        return $this->extractStructure($input->toStructure(), $blueprint);
      } else {
        return $this->extractValue($input, $blueprint);
      }
    }

    return null;
  }

  public function processBlueprints ($prints) {
    $fields = $this->settings['fields'];
    $blueprints = $this->settings['blueprints'];
    $prints = array_merge_recursive($prints, $blueprints);

    foreach ($prints as $key => $value) {
      $fieldType = $value['type'] ?? null;
      $fieldData = $fields[$fieldType] ?? null;

      if ($fieldData) {
        $prints[$key] = array_merge_recursive($prints[$key], $fieldData);
      }
    }

    return $prints;
  }

  public function extractEntity ($entity, $fieldBlueprints = null) {
    $data = [];
    $content = $entity->content($this->settings['language']);

    if (!$fieldBlueprints) {
      $fieldBlueprints = $entity->blueprint()->fields();
      $fieldBlueprints = $this->processBlueprints($fieldBlueprints);
    }

    foreach ($fieldBlueprints as $fieldName => $fieldBlueprint) {
      $field = $content->$fieldName();

      if ($field->value() !== null) {
        $fieldData = $this->extractField($fieldBlueprint, $field);

        if ($fieldData !== null) {
          $data[$fieldName] = $fieldData;
        }
      }
    }

    return $data;
  }

  public function extractPageContent ($page) {
    $data = $this->extractEntity($page);
    $files = [];

    foreach ($page->files() as $file) {
      $fileData = $this->extractEntity($file);

      if (!empty($fileData)) {
        $files[$file->id()] = $fileData;
      }
    }

    return [
      'content' => $data,
      'files' => $files
    ];
  }

  public function export () {
    $data = [];

    $pages = [];
    $files = [];
    $filterPage = $this->settings['page'];

    $siteData = $this->extractPageContent(site());
    $files = array_replace($files, $siteData['files']);

    foreach (site()->index() as $page) {
      $pageId = $page->id();

      if ($filterPage && strpos($pageId, $filterPage) === false) {
        continue;
      }

      $pageData = $this->extractPageContent($page);

      if (!empty($pageData['content'])) {
        $pages[$pageId] = $pageData['content'];
      }

      $files = array_replace($files, $pageData['files']);
    }

    $data['site'] = $siteData['content'];
    $data['pages'] = $pages;
    $data['files'] = $files;

    if ($this->settings['variables']) {
      $variables = Variables::get($this->settings['language']);

      if (!empty($variables)) {
        $data['variables'] = $variables;
      }
    }

    return $data;
  }
}
