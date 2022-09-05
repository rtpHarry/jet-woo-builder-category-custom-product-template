# Introduction
Apply a custom JetWooBuilder product template at the WooCommerce category 
level.

Adds new configuration fields to the WooCommerce Product Category admin
pages to select a category wide product single template.

<img width="812" alt="image" src="https://user-images.githubusercontent.com/1038062/182481658-727e05a9-ba95-48e0-8c62-a404e1450090.png">

# Usage
Install the plugin and activate it.

Go to WooCommerce Products > Categories and you will find new options to
select a Jet Woo Builder single product template to apply to all products
in that category.

Use the Priority field if you have products in multiple categories and 
need to decide which template should take priority.

Note: If you set a custom template on the individual product page then that
will override the category level template selection.

# Download
Download and contribute issues at:

https://github.com/rtpHarry/jet-woo-builder-category-custom-product-template

# Known Issues
The sorting on the admin columns only works if the meta key exists on that 
term. This means that if the term already existed before the plugin was
activated, and you haven't edited the term since then, when you use the
sort options, these terms will disappear from view. I plan to implement
an activating hook in an upcoming version to fix this.

# Changelog
1.2.0 - 6 September 2022
  - Bugfix - 500 error in some scenarios because global $product was
    not yet defined
  - Bugfix - Incorrect reference to self const in php code
  - Bugfix - Wrong version number in jsdocs comments

1.1.0 - 8 August 2022
  - Improve wording of field labels to clarify that it is the product 
    template being assigned.
  - Implement admin column in the manage screen for product template
  - Implement admin column in the manage screen for template priority
  - Make the new admin columns sortable

1.0.1 - 3 August 2022
  - Bugfix - Only attempt to modify template on product single pages

1.0.0 - 2 August 2022
  - Initial release

# Licence
This plugin is licenced under GPL 3, and is free to use on personal and 
commercial projects.

# Author
Built by Matthew Harris of runthings.dev, copyright 2022.

https://runthings.dev/
