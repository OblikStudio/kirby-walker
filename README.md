# Outsource

Plugin that allows you to walk, export, and import all site data according to specified blueprints. It is created with the intention of being used as a dependency for other plugins. Features:

- Flexible configurability in blueprints, _config.php_, or the API itself
- Serialize content on a per-field basis; convert kirbytags their XML representation, parse JSON and YAML, convert Markdown to HTML, etc.
- Export serialized site data for easy consumation by another API
- Import data and deserialize it so Kirby can use it
- Export/import language variables via [kirby-variables](https://github.com/OblikStudio/kirby-variables)
- Add IDs to structure entries on page update so consuming APIs can identify them and to avoid incorrectly merging content when importing data
- Synchronize structures in multi-lang sites so manipulating entries in one language applies the same changes in others
- Built-in support for [the Kirby Editor](https://github.com/getkirby/editor)

For example, this plugin is used as a dependency for [kirby-memsource](https://github.com/OblikStudio/kirby-memsource). It is used to export all site data and after it's translated in [Memsource](https://www.memsource.com/) - import it back.

## Installation

With [Composer](http://packagist.org/packages/oblik/kirby-outsource):

```
composer require oblik/kirby-outsource
```

## Usage

Class synopsis:

- [Walker](src/Walker.php) iterates over a Kirby Model's fields and recurses in structures
- [Serializers](src/Serializer) convert data from one format to another and back
- [Formatter](src/Formatter.php) uses Serializers and applies value transformations according to blueprint
- [Exporter](src/Exporter.php) extends Walker and uses Formatter to export serialized site data
- [Importer](src/Importer.php) extends Walker and uses Formatter to serialize current site data, merge it with some input data, deserialize it, and save it

Check the other classes [here](src/).

### Field Settings

These alter how field types are processed by the various plugin classes:

```php
[
    // Whether the plugin should ignore this field.
    'ignore' => false,

    // Array with serializers that must be applied when the value is exported.
    'serialize' => [
        'markdown' => true, // converts Markdown to HTML
        'kirbytags' => true // then converts kirbytags to HTML
    ],

    // Array with serializers that must be applied when the value is imported.
    // If omitted, the values in `serialize` will be used in reverse order.
    'deserialize' => [
        // same as `serialize`
    ],

    // Export settings.
    'export' => [
        'filter' => [
            // Array of value keys to filter
            'keys' => [],

            // Whether the specified keys above are a whitelist or a blacklist.
            'inclusive' => true,

            // Whether numeric keys should be allowed to exist either way.
            // Keep in mind that structure entries have numeric keys.
            'numeric' => true,

            // Whether this filter should be applied recursively.
            'recursive' => true
        ]
    ],

    // Key name for IDs when synchronizing structures.
    // Recommended: `id`
    'sync' => null
]
```

You can specify these settings in your blueprints. For example, the `pages` field is stored as YAML. Let's say you want to use the YAML serializer to parse the field value. You can do that in the blueprint under the `outsource` property:

```yml
fields:
  articles:
    type: pages
    outsource:
      serialize:
        yaml: true
```

If you don't want to explicitly set that for each `pages` field in the blueprint, you can set it in the global `fields` plugin setting in your _config.php_:

```php
return [
    'oblik.outsource.fields' => [
        'pages' => [
            'serialize' => [
                'yaml' => true
            ]
        ]
    ]  
];
```

...or, when used directly, in the Walker instance options:

```php
$exporter = new \Oblik\Outsource\Exporter([
    'fields' => [
        'pages' => [
            'serialize' => [
                'yaml' => true
            ]
        ]
    ]
]);
```

Settings specified in the YAML blueprint file will always have the highest priority. Settings in _config.php_ are intended for defaults and stuff that would otherwise be constantly repeated in blueprint files. Check the defaults [here](index.php).

### Artificial Fields

Since everything is processed according to the blueprints, you can't process a field outside of them. One such field is the `title`, which all pages have by default, even if it isn't in the blueprint. You can add the following in _config.php_:

```php
return [
    'oblik.outsource.blueprint' => [
        'title' => [
            'type' => 'text'
        ]
    ]  
];
```

...and all Models will be processed as if they had:

```yml
fields:
  title:
    type: text
```

**Note:** This is the default value for the `blueprint` plugin option, so you don't have to explicitly set it.

### Walker

The core class of the plugin is the Walker which processes a Model's fields and recurses in structure entries. You can optionally provide an `$input` array with the same form as the walked Model. In the Importer, for example, that is used to merge the current content with the provided one and then save it.

#### Exporting

```php
$exporter = new Oblik\Outsource\Exporter([
    // Translation which should be used when walking the Model.
    'language' => 'en',

    // Fields that should artificially be added to each blueprint.
    'blueprint' => [
        'title' => [
            'type' => 'text'
        ]
    ],

    // Configuration for each field type.
    'fields' => [
        'textarea' => [
            'serialize' => [
                'markdown' => true
            ]
        ]
    ]
]);
$data = $exporter->export(site());
```

Resulting data will be in the form of:

```php
[
    'site' => [
        // fields from site.yml
    ],
    'pages' => [
        'page' => [
            // page fields
        ],
        'page/child' => [
            // child page fields
        ]
    ],
    'files' => [
        'page/logo.svg' => [
            // file fields
        ]
    ],
    'variables' => [
        // variables array
    ]
]
```

#### Importing

```php
$importer = new Oblik\Outsource\Importer([
    'language' => 'bg'
]);
$importer->process([
    'site' => [
        'title' => 'New Title'
    ],
    'pages' => [
        'my/page' => [
            'title' => 'My Page',
            'text' => 'Hello World!'
        ]
    ]
]);
```

Importer has the same settings as Exporter and uses the same input data
structure as the one coming out from Exporter. The returned data from `process()` is an array with the changed values due to the import, for example:

```php
[
    'site' => [
        'title' => [
            '$old' => 'Title',
            '$new' => 'New Title'
        ]
    ],
    'pages' => [
        'my/page' => [
            'title' => [
                '$old' => 'Page',
                '$new' => 'My Page'
            ],
            'text' => [
                '$old' => null, // if there was no value
                '$new' => 'Hello World!'
            ]
        ]
    ]
]
```

#### Structure Synchronization

Synchronization of structures consists of two parts:

1. Adding IDs to structure entries so they can be uniquely identified
2. Adding/deleting/removing entries in all languages whenever a change in any language has been made

To have that working, you must mark the structure as a synced structure:

```yml
fields:
  items:
    type: structure
    outsource:
      sync: id # `id` is the field name used to store the ID
```

When you save that structure in the panel, you'd get:

```yml
- 
  text: foo
  id: cmrc
- 
  text: bar
  id: djzw
```

Then, in a translation, if you reorder those entries like this:

```yml
- 
  text: translated bar
  id: djzw
- 
  text: translated foo
  id: cmrc
```

...the values in the default language would be reordered in the same way:

```yml
- 
  text: bar
  id: djzw
- 
  text: foo
  id: cmrc
```
