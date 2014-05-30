First+Third Fields
=========

Wordpress Custom Fields Plugin

## Usage

Install to plugin directory.

Create a new directory under `wp-content` called `fields`. This will store all the yaml files.

## Creating a field

Create a new .yaml file in the `fields` directory. Use the following as a template:

```yaml
id: test_inputs
title: Test inputs
post_type: post
inputs:
  - type: text
    name: your_name
    label: Name
    placeholder: Your name
    required: true
```

#### Parameters

| Param      | Use                                             |
| :--------- | :---------------------------------------------- |
| id*        | ID (slug) of the field set                      |
| title*     | Title of the field set                          |
| post_type* | Post type this field should be used on          |
| inputs*    | Array of inputs to show. See inputs chart below |

#### Inputs

###### text

| Param | Value |
| :-- | :-- |
| type* | `text` |
| name* | Name of the input element (id and slug). |
| label* | Label used to describe the element. |
| placeholder | Placeholder text used in text input. |
| required | `true` or `false`. Will error if value is not set when true. |

###### password

| Param | Value |
| :-- | :-- |
| type* | `password` |
| name* | Name of the input element (id and slug). |
| label* | Label used to describe the element. |
| placeholder | Placeholder text used in text input. |
| required | `true` or `false`. Will error if value is not set when true. |

###### media

| Param | Value |
| :-- | :-- |
| type* | `media` |
| name* | Name of the input element (id and slug). |
| label* | Label used to describe the element. |
| placeholder | Placeholder text used in text input. |
| description | Additional text that may be displayed. |
| required | `true` or `false`. Will error if value is not set when true. |

###### select

| Param | Value |
| :-- | :-- |
| type* | `select` |
| name* | Name of the select element (id and slug). |
| label* | Label used to describe the element. |
| multiple | `true` or `false`. If true select will allow multiple items to be selected. |
| required | `true` or `false`. Will error if value is not set when true. |
| options* | Array of title/value pairs. See example "Option param usage" below. |

###### checkbox

| Param | Value |
| :-- | :-- |
| type* | `checkbox` |
| name* | Name of the checkbox (id and slug). |
| label* | Label used to describe the element. |
| required | `true` or `false`. Will error if value is not set when true. |
| options* | Array of title/value pairs. See example "Option param usage" below. |

###### radio

| Param | Value |
| :-- | :-- |
| type* | `radio` |
| name* | Name of the radio input set (id and slug). |
| label* | Label used to describe the element. |
| required | `true` or `false`. Will error if value is not set when true. |
| options* | Array of title/value pairs. See example "Option param usage" below |

###### textarea

| Param | Value |
| :-- | :-- |
| type* | `textarea` |
| name* | Name of the input element (id and slug). |
| label* | Label used to describe the element. |
| wysiwyg | `true` or `false`. True will render the wordpress WYSIWYG editor. |
| placeholder | Placeholder text used in textarea. |
| required | `true` or `false`. Will error if value is not set when true. |

**Option param usage:**

```yaml
options:
  - title: Kids
    value: kids
  - title: Teens
    value: teens
  - title: Adults
    value: adults
```

\* = required

## Configuring config path

In your plugin or functions.php

```php
add_action('init', 'test_fields');
function test_fields() {
  do_action('ft_fields_path', '/your/path/here/');
}
```

## Using meta

All field metadata is stored in the wordpress database along with any other custom fields that other plugins might generate. There's two ways to get data back:

### Wordpress way

The following will return all post meta for a given post.

```php
$fields = get_post_meta($id);
```

### ftFields way (easier)

ftFields includes a built in method to get only metadata set by the plugin. In your plugin you can do:

```php
ftFields::get_meta($id, $field);
```

Or you can add the following snippet to your `functions.php` to expose a helper for your templates.

```php
function get_custom_fields($id, $field) {
  if(class_exists('ftFields')) {
    return ftFields::get_meta($id, $field);
  } else {
    return null;
  }
}
```

The `$field` argument is the name you gave the element in the config. Here's how it could look in your template:

```html
<img src="<?php echo get_custom_fields(get_the_ID(), 'cat_pic'); ?>"/>
```