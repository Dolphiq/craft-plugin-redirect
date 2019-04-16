## Using the Redirect plugin

You can use the Redirect plugin to redirect simple routes but also use it for 
more advanced route matches.

### Static Routes
These are used for simple A -> B redirects. You may use absolute URLs or
relative URLs in either the source or the destination.

### Dynamic Routes
Dynamic routes are routes built using regular expressions. As such, you must be
very careful when you create them. Know your escape characters (such as `/`) as 
they are required in the source URL but not the destination URL.

Create matching groups in your RegEx and reference them in the destionation the
same way you would in `preg_replace`: $1, $2, ..., $7, etc

---
 
See some examples below.

### Simple static match
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

### More advanced dynamic redirect with a parameter:
Source URL:
```
^category\/(.+)\/overview.php$
```
Destination URL:
```
overview/category/$1/index.html
```

### Multiple parameters mixed
Source URL:
```
^cars\/(.+)\/(.+)/(.+)\/index.html$
```
Destination URL:
```
overview/cars/$1/colors/$3
```

### Dynamic redirects without a parameter
Source URL:
```
^some-deprecated-page\/*
```
Destination URL:
```
my-new-page
```
