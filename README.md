## Overview

ExpressionEngine is a PHP blogging platform created in 2003, and like WordPress or Drupal, can be used to build complex, custom web sites.

This repository provides updates and support for version 1.6.

Although the application continued development on to 2.x, version 1.6 hit a sweet spot of providing essential and extensible functionality.

- Dynamic paths
- Templating engine
- Extensions can be used to alter core functionality using hooks
- Modules are used to add functionality to the frontend or control panel
- Plugins allow for short snippets in templates to produce or alter output

---

## Installation

Follow [instructions in the documentation](http://alt.dysproseum.com/EECore1.6.0/docs/#install_docs)

The web server can be configured to run either from the document root (ex. http://example.com/) or under a directory path (ex. http://example.com/ee/)

Clone the repository on your web server:

`git clone https://github.com/dysproseum/ExpressionEngine1.6 ee`

Ensure the following paths are writable:
- `touch system/config.php`
- `chmod 666 system/config.php`
- `chmod -R 777 images`
- `chmod -R 777 system/cache`

Database connection
- Have connection details handy
- You may need to create the database
- Recommended: create a new MySQL user for accessing ExpressionEngine

Navigate to the `/install.php` file and enter your site details.

When installation completes, you can log into the control panel at http://example.com/system/

## Post-install configuration

If your web server does not have `mod_rewrite` enabled for dynamic paths, you can try enabling the option to "Force URL query strings" under System Preferences => Output and Debugging Preferences

To manage template changes in version control, enable the "Allow Templates to be Saved As Files" option under Templates => Global Template Preferences

## Restoration

ExpressionEngine was originally written to run on PHP 4.1, but many methods were deprecated in the 5.3 release of PHP.

Starting with a clean `EECore1.6.0.zip` containing the source code, the following updates were performed to get ExpressionEngine running on PHP 7.4:

- Update `ereg` calls to use `preg_match`
- Update `eregi` calls to use `preg_match("/^pattern$/i")`
- Additional `preg_match` updates to fix invalid delimiter
  - Delimiter is required in most cases; must be non-alphanumeric and not a backslash.
- Refactor `preg_match("/^pattern$/e")` calls to use `preg_match_callback("/^pattern$/", "my_callback", $str)`
- Convert `mysql` calls to use `mysqli` methods

MySQL 3.x was also in use at the time, and there are a few updates needed for compatibility with MySQL 5.x:

- Too big precision 14 specified for 'column_name'. Maximum is 6
  - Make database schema updates in `install.php` to set valid column definitions
- No default value for column
  - These are found where insert queries specify `VALUES()` but do not include all columns
  - Set missing default values in `install.php`
- No integer for auto increment column
  - These are found when inserting like `INSERT INTO table (id, value) VALUES ('', 'value');`
  - Replace empty string with `0`

Once these updates were made, the install was able to complete. A user can now log in and post content.

## References

Demo site: http://alt.dysproseum.com/EECore1.6.0/
