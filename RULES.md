# Matching rules

A redirect matches a requested URL by its **source URL**. Redirect Manager resolves a redirect
only when a URL would otherwise 404, so a redirect never shadows a page that already exists.

Each redirect has a **match type** — `exact`, `prefix`, `wildcard`, or `pattern` — inferred from the
source syntax (or forced from the **Advanced** picker on the edit form). On the form you don't have to
memorize the syntax: open **"Pattern help"** to click tokens (`*`, `<name>`, `<name:regex>`) straight
into the last-focused URL field, and use the **"Test this redirect"** box to check a URL against your
redirect before saving (nothing is written). The match types are described below, from simplest to
most powerful.

## 1. Exact match

```
Source URL:       about-us
Destination URL:  about
```

`/about-us` → `/about`.

## 1b. Prefix — path starts with the source

Match any URL beneath a path. Match type: **prefix**.

```
Source URL:       blog
Destination URL:  news
```

`/blog`, `/blog/2024`, `/blog/2024/post` all → `/news`. `/blogger` does **not** match (it's not a
path segment boundary).

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

## 6. Regex — raw PCRE with `$1` backreferences

For full control, choose the **regex** match type and write a raw PCRE pattern. Numeric capture
groups are substituted into the destination as `$1`, `$2`, …

```
Source URL:       ^blog/(\d+)/(.+)$
Destination URL:  news/$2/$1
```

`/blog/2024/launch` → `/news/launch/2024`. Unknown backreferences (e.g. `$5` with no 5th group) are
left untouched. The pattern is matched as-is — add `^…$` yourself to anchor it.

On the edit form, selecting the **regex** type reveals a helper: click-to-insert tokens (`^`, `$`,
`(\d+)`, `(.+)`, `([^/]+)`), live validation that reports the capture-group count, and `$1`/`$2`
chips that drop a backreference into the destination. Use the **Test this redirect** box to preview
the result.

## Enabling, disabling and scheduling

- A redirect has a **status**: a disabled redirect is kept but never resolves. Toggle it on the edit
  form, or select redirects in the index and use the **Set status** bulk action.
- A redirect may have an optional **Start date** / **End date**. Outside that window it doesn't
  resolve. Either bound may be left empty for an open-ended window.

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
