<?php

namespace Oblik\Outsource;

function sync($model)
{
    /**
     * Would be better to use the actual updated translation of the
     * page, instead of the Kirby language and assume they match.
     * @see https://github.com/getkirby/ideas/issues/396
     */
    $updatedLang = kirby()->languageCode();
    $synchronizer = new Synchronizer($model, $updatedLang);

    if ($data = $synchronizer->mark()) {
        $synchronizer->sync($data);
    }
}

return [
    'site.update:after' => function ($site) {
        sync($site);
    },
    'file.update:after' => function ($file) {
        sync($file);
    },
    'page.update:after' => function ($page) {
        sync($page);
    }
];
