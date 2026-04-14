# My API Plugin

A local Moodle plugin that provides external API endpoints for retrieving course data.

## Overview

This plugin exposes web service functions to retrieve full course data including sections and modules through the Moodle external API.

## Features

- Get complete course structure (sections and modules)
- Returns module URLs and visibility status
- Compatible with course formats including Flexsections

## Installation

1. Extract the plugin to `local/myapi/`
2. Visit Site administration > Notifications
3. Install the plugin

## Web Services

### get_course_full_data

**Description:** Retrieves full course data including all sections and modules

**Parameters:**

- `courseid` (int) - ID of the course

**Returns:** Array of sections with their modules

**Example Response:**

```json
[
  {
    "id": 1,
    "name": "Section 1",
    "section": 0,
    "modules": [
      {
        "id": 1,
        "name": "Module Name",
        "modname": "lesson",
        "url": "http://example.com/mod/lesson/view.php?id=1",
        "visible": 1
      }
    ]
  }
]
```

## Requirements

- Moodle 4.1 or later

## License

GNU General Public License v3.0 or later - See COPYING.txt in Moodle root
