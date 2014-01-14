Shoestrap-Updater
=================

Manage updates using EDD Licencing.

This plugin makes managing updates for plugins and themes easier.

If you're using [EDD Licencing](https://easydigitaldownloads.com/extensions/software-licensing/) on your site to sell WordPress plugins and themes, then you probably have to add a class and write a lot of code to get your updates out there.
This plugin attempts to make this process easier, and allow you to have a plugin that will manage ALL updates for your plugins and themes.

It already contains the `EDD_SL_Plugin_Updater` and `EDD_SL_Theme_Updater.php` classes, so you don't have to include them in your plugins and themes.
It also has a Wrapper Class for them `Shoestrap_Updater` and functions that actually read some additional header info from your products and automatically populate the licencing fields with the appropriate data.

## Implementation

### Allow Automatic Updates for your plugins:

Usually this is how what plugin's header would look like:
```php
<?php
/*
Plugin Name: My Plugin
Plugin URI: http://example.com
Description: A description here
Version: 1.0
Author: Your Name
Author URI:  http://example.com
*/
```

To allow the updater to detect and manage this plugin, you'd have to add this line in there:
`Software Licensing: true`
So your Headers will look like this:

```php
<?php
/*
Plugin Name: My Plugin
Plugin URI: http://example.com
Description: A description here
Version: 1.0
Author: Your Name
Author URI:  http://example.com
Software Licensing: true
*/
```

This will create a new field in the dashboard where users will be able to enter their own licence key and activate it.

The extra headers available are:
```
Software Licensing:
Software Licensing URL:
Software Licensing Description:
```

If you want to force a specific licence key, then you can specify it in the Software Licensing field like this:

`Software Licensing: a0abaffea901e14bf9b20eada692f9fe`

The `Software Licensing URL` header allows you to use your own Remote API URL.

Example:
`Software Licensing URL: http://example.com`

The `Software Licensing Description` header allows you to add some additional info in the form that users will use to enter their license keys.

Example:
`Software Licensing Description: This is a sample description.`

Nothing more is needed... plugins and themes will automatically be picked up and updated.

### Contibuting:

We colcome all contributions from everyone. This plugin is not yet fully functional, but the principal is solid. Any improvements or suggestions you might have are more than welcomed!