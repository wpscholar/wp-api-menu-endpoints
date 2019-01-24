# WordPress REST API Menu Endpoints

This repository is meant to serve as an initial implementation of the WordPress REST API menu endpoints with the intent of eventually merging into WordPress core. 

Discussion via issues and pull requests are welcome.

[View the API documentation](https://documenter.getpostman.com/view/530620/RztfxY8j)

## What It Does

Provides a collection of endpoints that allow full management of menus in WordPress. 

Don't believe me? Try out my [Vue prototype](https://github.com/wpscholar/wp-menu-ui-vue-prototype) that aims to replicate the menus page from the WordPress admin. 

## How to Use It

Add to your project via [Composer](https://getcomposer.org/):

```
$ composer wpscholar/wp-rest-menu-endpoints
```

Make sure you have added the Composer autoloader to your project:  

```php
<?php

require __DIR__ . '/vendor/autoload.php';
```

All endpoints will be automatically registered and will work out of the box. Check out the [API documentation](https://documenter.getpostman.com/view/530620/RztfxY8j) to see how the API works. If you have a question, just open an issue.
