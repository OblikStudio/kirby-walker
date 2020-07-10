<?php

namespace Oblik\Outsource\Util;

use Oblik\Outsource\Walker\Marker;
use Oblik\Outsource\Walker\Synchronizer;

use Kirby\Cms\ModelWithContent;

class Updater
{
    public static function update(ModelWithContent $model)
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

    public static function getHooks()
    {
        return [
            'site.update:after' => function ($newSite) {
                Updater::update($newSite);
            },
            'file.update:after' => function ($newFile) {
                Updater::update($newFile);
            },
            'page.update:after' => function ($newPage) {
                Updater::update($newPage);
            }
        ];
    }
}
