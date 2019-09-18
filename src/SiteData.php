<?php

namespace Oblik\Outsource;

use Exception;

trait SiteData
{
    public function processModel($model, $data, string $type)
    { }

    public function processVariables($data, string $driver)
    { }

    public function process($data = [])
    {
        $result = [];
        $site = site();

        if (!empty($data['site'])) {
            $result['site'] = $this->processModel($site, $data['site'], 'site');
        }

        if (!empty($data['pages'])) {
            $result['pages'] = [];

            foreach ($data['pages'] as $id => $pageData) {
                if ($page = $site->page($id)) {
                    $result['pages'][$id] = $this->processModel($page, $pageData, 'page');
                }
            }
        }

        if (!empty($data['files'])) {
            $result['files'] = [];

            foreach ($data['files'] as $id => $fileData) {
                if ($file = $site->file($id)) {
                    $result['files'][$id] = $this->processModel($file, $fileData, 'file');
                }
            }
        }

        if (!empty($data['variables'])) {
            $result['variables'] = [];

            if ($driver = $this->settings['variables'] ?? null) {
                $result['variables'] = $this->processVariables($data['variables'], $driver);
            } else {
                throw new Exception('No variables driver provided');
            }
        }

        return $result;
    }
}
