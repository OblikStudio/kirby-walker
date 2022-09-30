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

#### Importing
