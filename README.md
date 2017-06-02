# Redirect plugin for Craft CMS 3.x

Craft plugin that provides an easy way to enter and mentain 301 and 302 redirects for your Craft 3 powered website.

This is particularly useful if you are migrating pages from an old website and want to avoid dead links and want to keep the page ranks for the SEO. But also use full if you are making (big) changes in the site (url) structure.

## Installation

To Redirect, follow these steps:

1. Install with Composer via `composer require dolphiq/redirect` from your project folder
2. Install plugin in the Craft Control Panel under Settings > Plugins
3. The redirect plugin will be visible in the settings view on in the plugins section

Redirect plugin works on Craft 3.x.

## Redirect plugin


## Using the Redirect plugin

You can use the Redirect plugin to redirect simple routes but also use it for more advanced route matches. See some examples below.

### Simple redirect exact match
Source URL:
```
oldpage/dont/work/anymore
```
Destination URL:
```
newpage/will/work/again
```

### Simple redirect to an other (sub)domain
Source URL:
```
oldpage/dont/work/anymore
```
Destination URL:
```
https://www.newwebsite.com/newpage/will/work/again
```

### More advanced redirect with a parameter
Source URL:
```
category/<catname>/overview.php
```
Destination URL:
```
overview/category/<catname>/index.html
```

### Multiple parameters mixed
Source URL:
```
/cars/<brand>/<dontusepart>/<color>/index.html
```
Destination URL:
```
overview/cars/<brand>/colors/<color>
```
*note: it is not required to use all the source parameters in the destination URL

### Replace a uri parameter in de source string to a new path

Source URL:
```
books/detail
```
Destination URL:
```
book-detail/<bookId>/index.html
```

Example: the original url looks like:
```
books/detail?bookId=124
```

After the redirect, the url look likes
```
book-detail/124/index.html
```

### Contributors & Developers
Johan Zandstra - johan@dolphiq.nl
Brought to you by [Dolphiq](https://dolphiq.nl)
