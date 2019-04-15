# Migration Guide for Migrating from Dolphiq
## Overview
While this plugin is a direct fork of Dolphiq, it retains very little of its originally source code. This has two
implications:
1. Our data is stored similarly (Yay!)
2. Redirects work completely differently (Boo!)

We'll tackle each of the following challenges in this guide:
1. Handling handles changing handles
2. Installing Venveo craft-redirect (AKA: vredirect)
3. Fixing dynamic redirects

## 1. Handling handles changing handles
Craft keeps track of what migrations you've run and what plugins you have installed by using a plugin handle. Because
this plugin is a fork and can hypothetically exist alongside dolphiq redirect.
### Step 1
Remove Dolphiq redirect, but do **not** uninstall it. Simply:
`composer remove dolphiq/redirect`

Install Venveo redirect:
`composer require venveo/craft-redirect`

Manually update database references:
- Update the `craft_plugins` table and change the plugin entry with the handle "redirect" to "vredirect"
```sql
UPDATE craft_plugins SET handle = 'vredirect' WHERE handle = 'redirect' LIMIT 1;
```

### Step 2
Open the admin CP of your environment and you should be prompted to run CMS updates. Run those.

### Step 3
TODO
