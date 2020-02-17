<?php

namespace Oblik\Outsource;

class Model {
    protected $site;
    protected $pages;
    protected $files;
    protected $variables;

    public function __construct($data = [])
    {
        $this->site = $data['site'] ?? [];
        $this->pages = $data['pages'] ?? [];
        $this->files = $data['files'] ?? [];
        $this->variables = $data['variables'] ?? [];
    }

    public function __call($name, $arguments)
    {
        return $this->$name ?? null;
    }

    public function setSite(array $data)
    {
        $this->site = $data;
    }

    public function addPage(string $key, array $data)
    {
        $this->pages[$key] = $data;
    }

    public function addFile(string $key, array $data)
    {
        $this->files[$key] = $data;
    }

    public function setVariables(array $data)
    {
        $this->variables = $data;
    }

    public function toArray()
    {
        $result = null;

        foreach (['site', 'pages', 'files', 'variables'] as $prop) {
            if (!empty($this->$prop)) {
                $result[$prop] = $this->$prop;
            }
        }

        return $result;
    }
}
