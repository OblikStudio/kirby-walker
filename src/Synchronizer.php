<?php

namespace Oblik\Outsource;

class Synchronizer
{
    public $model;
    public $lang;

    public function __construct($model, $lang)
    {
        $this->model = $model;
        $this->lang = $lang;
    }

    public function mark()
    {
        $marker = new Marker();
        $content = $this->model->content($this->lang);
        $fields = $this->model->blueprint()->fields();
        $blueprint = $marker->processBlueprint($fields);

        if ($data = $marker->walk($content, $blueprint)) {
            $this->model = $this->model->update($data, $this->lang);
        }

        return $data;
    }

    public function sync(array $input)
    {
        $syncer = new Syncer();

        foreach ($this->model->translations() as $translation) {
            $lang = $translation->code();

            if ($lang !== $this->lang) {
                $content = $this->model->content($lang);
                $fields = $this->model->blueprint()->fields();
                $blueprint = $syncer->processBlueprint($fields);

                if ($data = $syncer->walk($content, $blueprint, $input)) {
                    $this->model->update($data, $lang);
                }
            }
        }
    }
}
