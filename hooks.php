<?php

namespace Oblik\Outsource;

use Oblik\Outsource\Walker\Marker;
use Oblik\Outsource\Walker\Synchronizer;

function update($model)
{
    $marker = new Marker();
    $synchronizer = new Synchronizer();

    /**
     * Would be better to use the actual updated translation of the
     * page, instead of the Kirby language and assume they match.
     * @see https://github.com/getkirby/ideas/issues/396
     */
    $mutatedLang = kirby()->languageCode();

    if ($markedData = $marker->walkModel($model, $mutatedLang)) {
        $model = $model->update($markedData, $mutatedLang);

        foreach ($model->translations() as $translation) {
            $lang = $translation->code();

            if ($lang !== $mutatedLang) {
                if ($syncedData = $synchronizer->walkModel($model, $lang, $markedData)) {
                    $model = $model->update($syncedData, $lang);
                }
            }
        }
    }
}

return [
    'site.update:after' => function ($site) {
        update($site);
    },
    'file.update:after' => function ($file) {
        update($file);
    },
    'page.update:after' => function ($page) {
        update($page);
    }
];
