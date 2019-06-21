<?php
namespace KirbyExporter;

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

  public function extractFieldData ($blueprint, $input) {
    if (!$this->isFieldEligible($blueprint)) {
      return null;
    }

    $fieldType = $blueprint['type'];
    $isFieldInstance = is_object($input);

    if ($fieldType === 'structure') {
      $data = [];
      $content = $isFieldInstance ? $input->yaml() : $input;

      foreach ($content as $index => $entry) {
        $childData = [];

        foreach ($blueprint['fields'] as $fieldName => $fieldBlueprint) {
          $fieldKey = strtolower($fieldName);

          if (isset($entry[$fieldKey])) {
            $fieldValue = $entry[$fieldKey];
            $extractedValue = $this->extractFieldData($fieldBlueprint, $fieldValue);

            if (!empty($extractedValue)) {
              $childData[$fieldName] = $extractedValue;
            }
          }
        }

        if (!empty($childData)) {
          $data[$index] = $childData;
        }
      }
    } else {
      $data = $input;
      $parseYaml = $blueprint['exporter']['yaml'] ?? null;

      if ($isFieldInstance) {
        if ($parseYaml) {
          $data = $data->yaml();
        } else {
          $data = $data->value();
        }
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
    }

    return $data;
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

  public function extractEntity ($entity) {
    $data = [];
    $content = $entity->content($this->settings['language']);
    $fieldBlueprints = $this->processBlueprints($entity->blueprint()->fields());

    foreach ($fieldBlueprints as $fieldName => $fieldBlueprint) {
      $field = $content->$fieldName();
      $fieldData = $this->extractFieldData(
        $fieldBlueprint,
        $field
      );

      if (!empty($fieldData)) {
        $data[$fieldName] = $fieldData;
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
