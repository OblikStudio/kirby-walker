# Walker

Plugin that allows you to walk, export, and import all site data according to specified blueprints. It is created with the intention of being used as a dependency for other plugins. Features:

-   Serialize content on a per-field basis (parse YAML in structures, turn comma-separated tags fields to arrays, etc.)
-   Convert KirbyTags to XML and back (for consumption by another API that doesn't understand KirbyTag syntax)
-   Convert Markdown to HTML and back (for APIs that don't work with Markdown)
-   Export entire site, page, and file models
-   Import content using the same schema as the exports
-   Traverse blocks and structures according to their `id` fields (if they have such)
-   Easy extensibility of the PHP classes for implementing custom behavior
-   Support for the now deprecated [Kirby Editor](https://github.com/getkirby/editor)

## Installation

With Composer from [oblik/kirby-walker on Packagist](http://packagist.org/packages/oblik/kirby-walker):

```
composer require oblik/kirby-walker
```

## Usage

#### Exporting

You can use the `Exporter` class to export content:

```php
use Oblik\Walker\Walker\Exporter;

$exporter = new Exporter();
$result = $exporter->walk(page('home'));
echo json_encode($result, JSON_PRETTY_PRINT);
```

```json
{
    "title": "Home",
    "headline": "Welcome to Kirby's Starterkit",
    "subheadline": "A fully documented example project"
}
```

#### Importing

You can use the `Importer` class to merge input content with the current model content.

```php
use Oblik\Walker\Walker\Importer;

$importer = new Importer();
$model = page('home');
$result = $importer->walk($model, [
	'input' => [
		'headline' => 'Updated headline!'
	]
]);
echo json_encode($data, JSON_PRETTY_PRINT);
```

```json
{
    "title": "Home",
    "headline": "Updated headline!",
    "subheadline": "A fully documented example project"
}
```

After merging the data, you can use the resulting array to apply the changes to the model:

```php
kirby()->impersonate('kirby');
$model->update($result);
```

## Extending

You can easily extend the base classes to add custom behaviors. For example, you could return the character lengths of each field like so:

```php
use Oblik\Walker\Walker\Walker;

class CustomWalker extends Walker
{
	protected function walkText(string $text, $context)
	{
		return strlen($text);
	}
}

$walker = new CustomWalker();
$data = $walker->walk(page('home'));
echo json_encode($data, JSON_PRETTY_PRINT);
```

```json
{
    "title": 4,
    "headline": 29,
    "subheadline": 34
}
```

You could also use values from each field's blueprint:

```php
class CustomWalker extends Walker
{
	protected function walkField(Field $field, $context)
	{
		return $context['blueprint']['label'] ?? 'NONE';
	}
}
```

```json
{
    "title": "NONE",
    "headline": "Headline",
    "gap": "Gap",
    "subheadline": "Subheadline"
}
```

The cool part is that parsing YAML for structure fields and JSON for layout and blocks fields is already handled. Your custom logic will work in all nested fields automatically.

### Separate logic for field types

You can have different logic for different field types by adding a `walkField{{ field type }}` method. For example, you can change the behavior for just the `gap` fields by adding `walkFieldGap`:

```php
class CustomWalker extends Walker
{
	protected function walkFieldGap($field, $context)
	{
		return 'Mind the gap!';
	}
}
```

```json
{
    "title": "Home",
    "headline": "Welcome to Kirby's Starterkit",
    "gap": "Mind the gap!",
    "subheadline": "A fully documented example project"
}
```
