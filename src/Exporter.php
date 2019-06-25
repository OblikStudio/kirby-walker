<?php
namespace KirbyExporter;

class Exporter {
  public $settings = [
    'language' => null,
    'variables' => true,
    'blueprints' => [],
    'fields' => [],
    'fieldPredicate' => null,
    'filters' => []
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

  public function extractEntity ($entity, $fieldBlueprints = null) {
    $data = null;
    $language = $this->settings['language'] ?? null;
    $content = $entity->content($language);

    if (!$fieldBlueprints) {
      $fieldBlueprints = $this->processBlueprints(
        $entity->blueprint()->fields()
      );
    }

    foreach ($content->fields() as $key => $field) {
      $blueprint = $fieldBlueprints[$key] ?? null;

      if ($blueprint) {
        $fieldData = $this->extractField($blueprint, $field);

        if ($fieldData !== null) {
          $data[$key] = $fieldData;
        }
      }
    }

    return $data;
  }

  // Extracts all content of a Page. Can be used by its own in case you need
  // to export a single page.
  public function extractPageContent ($page) {
    $data = $this->extractEntity($page);
    $files = [];
    $filesFilter = $this->settings['filters']['files'] ?? null;

    foreach ($page->files() as $file) {
      if (is_callable($filesFilter) && $filesFilter($file) === false) {
        // The file has been filtered out.
        continue;
      }

      $fileId = $file->id();
      $fileData = $this->extractEntity($file);

      if (!empty($fileData)) {
        $files[$fileId] = $fileData;
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

    $siteData = $this->extractPageContent(site());
    $files = array_replace($files, $siteData['files']);
    $pagesFilter = $this->settings['filters']['pages'] ?? null;

    foreach (site()->index() as $page) {
      if (is_callable($pagesFilter) && $pagesFilter($page) === false) {
        // The page has been filtered out.
        continue;
      }

      $pageId = $page->id();
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
