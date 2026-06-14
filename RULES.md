# Matching rules

A redirect matches a requested URL by its **source URL**. Redirect Manager resolves a redirect
only when a URL would otherwise 404, so a redirect never shadows a page that already exists.

Five kinds of source pattern are supported, from simplest to most powerful.

## 1. Exact match

```
Source URL:       about-us
Destination URL:  about
```

`/about-us` → `/about`.

## 2. Redirect to another (sub)domain

```
Source URL:       shop
Destination URL:  https://store.example.com/catalog
```

Any destination containing `://` is treated as an absolute URL. Relative destinations are
resolved against the current site's base URL.

## 3. Named parameter — `<name>`

Matches a single path segment and substitutes it into the destination.

```
Source URL:       category/<catname>/overview.php
Destination URL:  overview/category/<catname>
```

`/category/books/overview.php` → `/overview/category/books`.

You don't have to use every captured parameter in the destination:

```
Source URL:       cars/<brand>/<unused>/<color>/index.html
Destination URL:  overview/cars/<brand>/colors/<color>
```

## 4. Constrained parameter — `<name:regex>`

Add a regular expression to control what a parameter matches.

```
Source URL:       item/<id:\d+>
Destination URL:  products/<id>
```

`/item/42` → `/products/42`, but `/item/abc` does **not** match (only digits).

Use `.+` to capture across multiple segments:

```
Source URL:       wholepath/<rest:.+>
Destination URL:  otherpath/<rest>
```

`/wholepath/this/is/long` → `/otherpath/this/is/long`.

## 5. Wildcard — `*`

A shorthand that matches across path segments and is substituted into the matching `*` in the
destination, in order.

```
Source URL:       docs/*
Destination URL:  help/*
```

`/docs/getting-started` → `/help/getting-started`.

## Query-string parameters

Any `<name>` placeholder left in the destination that wasn't filled by the source match is taken
from the request's query string:

```
Source URL:       books/detail
Destination URL:  book-detail/<bookId>/index.html
```

`/books/detail?bookId=124` → `/book-detail/124/index.html`.

The original query string is otherwise preserved and appended to the destination.

## Fragments and numeric sources

- A `#fragment` in a source URL is ignored when matching (fragments aren't sent to the server).
- A purely numeric source (e.g. `12`) is matched as a path segment.

---

← Back to the [README](README.md).
