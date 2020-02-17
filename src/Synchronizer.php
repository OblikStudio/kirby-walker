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

        if ($data = $marker->walk($this->model)) {
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
                $syncer->settings[BP_LANGUAGE] = $lang;

                if ($data = $syncer->walk($this->model, [], $input)) {
                    $this->model->update($data, $lang);
                }
            }
        }
    }
}
