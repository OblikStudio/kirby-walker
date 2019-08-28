<?php

namespace Oblik\Outsource;

function markModel($model, $lang)
{

    $marker = new Marker();
    $markedData = $marker->walk($model);
    $model = $model->update($markedData, $lang);
    return $markedData;
}

function syncModel($model, $data, $targetLang)
{
    $syncer = new Syncer();
    foreach ($model->translations() as $translation) {
        $lang = $translation->code();

        if ($lang !== $targetLang) {
            $syncer->settings['language'] = $lang;

            if ($syncedData = $syncer->walk($model, $data)) {
                $model->update($syncedData, $lang);
            }
        }
    }
}

function synchronizeModel($model)
{
    /**
     * Would be better to use the actual updated translation of the
     * page, instead of the Kirby language and assume they match.
     * @see https://github.com/getkirby/ideas/issues/396
     */
    $updatedLang = kirby()->languageCode();

    // Add mark structure entries by adding specified sync IDs
    $data = markModel($model, $updatedLang);

    // After the updated translation had its entries marked with IDs,
    // synchronize all other translations with it.
    syncModel($model, $data, $updatedLang);
}

return [
    'site.update:after' => function ($site) {
        synchronizeModel($site);
    },
    'file.update:after' => function ($file) {
        synchronizeModel($file);
    },
    'page.update:after' => function ($page) {
        synchronizeModel($page);
    }
];
