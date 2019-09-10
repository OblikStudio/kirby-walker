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
        $site = site();

        if (!empty($data['site'])) {
            $data['site'] = $this->processModel($site, $data['site'], 'site');
        }

        if (!empty($data['pages'])) {
            foreach ($data['pages'] as $id => $pageData) {
                if ($page = $site->page($id)) {
                    $data['pages'][$id] = $this->processModel($page, $pageData, 'page');
                }
            }
        }

        if (!empty($data['files'])) {
            foreach ($data['files'] as $id => $fileData) {
                if ($file = $site->file($id)) {
                    $data['files'][$id] = $this->processModel($file, $fileData, 'file');
                }
            }
        }

        if (!empty($data['variables'])) {
            if ($driver = $this->settings['variables'] ?? null) {
                $data['variables'] = $this->processVariables($data['variables'], $driver);
            } else {
                throw new Exception('No variables driver provided');
            }
        }

        return $data;
    }
}
