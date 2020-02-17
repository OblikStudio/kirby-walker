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
        $data = $marker->walkModel($this->model, $this->lang);

        if ($data) {
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
                $data = $syncer->walkModel($this->model, $lang, $input);

                if ($data) {
                    $this->model->update($data, $lang);
                }
            }
        }
    }
}
