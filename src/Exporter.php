<?php

namespace KirbyOutsource;

use KirbyOutsource\Formatter;
use KirbyOutsource\Variables;

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
    $this->settings['predicate'] = function ($blueprint, $input) {
      $ignored = $blueprint['exporter']['ignore'] ?? false;
      $predicate = $this->settings['fieldPredicate'] ?? null;

      if ($ignored) {
        return false;
      }

      if (is_callable($predicate)) {
        return $predicate($blueprint);
      }

      return true;
    };

    $this->formatter = new Formatter($this->settings);
  }

  // Extracts all content of a Page. Can be used by its own in case you need
  // to export a single page.
  public function extractPageContent ($page) {
    $data = $this->formatter->decode($page);
    $files = [];
    $filesFilter = $this->settings['filters']['files'] ?? null;

    foreach ($page->files() as $file) {
      if (is_callable($filesFilter) && $filesFilter($file) === false) {
        // The file has been filtered out.
        continue;
      }

      $fileId = $file->id();
      $fileData = $this->formatter->decode($file);

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
