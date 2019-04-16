# Migration Guide for Migrating from Dolphiq
## Overview
While this plugin is a direct fork of Dolphiq, it retains very little of its originally source code. This has two
implications:
1. Our data is stored similarly (Yay!)
2. Redirects work completely differently (Boo!)

Fortunately, Venveo Redirect provides a migration to help out with this.

### Pre-requisites:

- Dolphiq redirect installed, enabled, and updated to no further than 1.0.20. **THIS IS IMPORTANT.**

### Steps
1. Install Venveo redirect either through composer or the plugin store
    
   `composer require venveo/craft-redirect`

    The installation process will automatically detect if Dolphiq redirect is installed, apply the proper migrations to
    bring it up to the required version, apply the new migrations for Venveo Redirect, and disable Dolphiq redirect.

2. Update your dynamic redirects
    
    You'll need to update all of your redirects with dynamic patterns to RegEx and set the type to "Dynamic (RegEx)"
   
3. You may now safely remove dolphiq Redirect (Don't "Uninstall"!)
    
    `composer remove dolphiq/redirect`
    
