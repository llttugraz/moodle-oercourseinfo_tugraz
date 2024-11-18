### moodle-oercourseinfo_tugraz

Subplugin for [local_oer](https://github.com/llttugraz/moodle-local_oer) plugin.<br>
*As there are dependencies that are not publicly accesible yet, this source code is intended as example, how to implement this subplugin type for the* `local_oer` *plugin.*

## Table of Contents


- [Installation](#installation)
- [Requirements](#requirements)
- [Features](#features)
- [Configuration](#configuration)
- [Usage](#usage)
- [Dependencies](#dependencies)
- [API Documentation](#api-documentation)
- [Subplugins](#subplugins)
- [Privacy](#privacy)
- [Known Issues](#known-issues)
- [License](#license)
- [Contributors](#contributors)

## Installation

There are 3 options on how to install this subplugin.

1. Download the subplugin and extract the files.
2. Move the extracted folder to your `moodle/local/oer/metadata` directory.
3. Log in as an admin in Moodle and navigate to `Site Administration` or `Dashboard`.
4. Follow the on-screen instructions to complete the installation.

or

1. Download the subplugin.
2. Log in as admin in Moodle and navigate to `Site Administration > Plugins > Install plugins`.
3. Add zip directory to drag and drop field.
4. Follow the on-screen instructions to complete the installation.

or

1. Open `.../moodle/local/oer/metadata` in a terminal.
2. Add this subplugin in terminal with `git clone <ssh link> <folder name>`.
3. Log in as admin and navigate to `Site Administration` or `Dashboard`.
4. Follow the on-screen instructions to complete the installation.

with the third option there are also the git functions available.


## Requirements

- Supported Moodle Version: 4.1 - 4.5
- Supported PHP Version:    7.4 - 8.3
- Supported Databases:      MariaDB, PostgreSQL
- Supported Moodle Themes:  Boost

This plugin has only been tested under the above system requirements against the specified versions.
However, it may work under other system requirements and versions as well.

## Features

As there are dependencies that are not publicly accesible yet, this source code is intended as example, how to implement this subplugin type for the [local_oer](https://github.com/llttugraz/moodle-local_oer) plugin.

In the TeachCenter (the Moodle instance used by Graz University of Technology) Moodle courses can be linked to several external courses from TUGRAZonline (CAMPUSonline instance - the campus management software). This is used to synchronise groups, teachers and students to the moodle courses.

This subplugin gathers the information about the linked courses and adds them to the metadata information for the [local_oer](https://github.com/llttugraz/moodle-local_oer). The metadata about the courses is added to the file metadata.  


## Configuration

No settings of this subplugin available.

## Usage

See description of main plugin [local_oer](https://github.com/llttugraz/moodle-local_oer).

## Dependencies

* [local_oer](https://github.com/llttugraz/moodle-local_oer) v2.3.0+
* local_coursesync - not yet publicly accessible
* coursesync_lectures - not yet publicly accessible
* coursesync_courseid - not yet publicly accessible
* local_tugrazonlinewebservice - not yet publicly accessible

## API Documentation

No API.

## Subplugins

No subplugins.

## Privacy

No personal data are stored.

## Accessibility Status

No accessibility status yet. TODO.

## License

This plugin is licensed under the [GNU GPL v3](http://www.gnu.org/licenses).

## Contributors

- **Ortner, Christian** - Developer - [GitHub](https://github.com/chriso123)
