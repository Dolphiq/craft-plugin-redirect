## Using the Redirect plugin

You can use the Redirect plugin to redirect simple routes but also use it for more advanced route matches. See some examples below.

### Simple redirect exact match
Source URL:
```
oldpage/wont/work/anymore
```
Destination URL:
```
newpage/will/work/again
```

### Simple redirect to an other (sub)domain
Source URL:
```
oldpage/wont/work/anymore
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
cars/<brand>/<dontusepart>/<color>/index.html
```
Destination URL:
```
overview/cars/<brand>/colors/<color>
```
*note: it is not required to use all the source parameters in the destination URL

### Replace a uri parameter in the source string to a new path

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

After the redirect, the url will look like:
```
book-detail/124/index.html
```

### Replace a long path with unknown amount of segments for an other url

Source URL:
```
wholepath/<options:.+>
```
Destination URL:
```
otherpath/index.html?cat=<a>&subcat=<b>
```

Example: the original url looks like:
```
wholepath/this/is/a/long/path/with/params?a=1&b=2&c=4
```

After the redirect, the url will look like:
```
/otherpath/index.html?cat=1&subcat=2
```

[Click here](README.md) for the main readme.
