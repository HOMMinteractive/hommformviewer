# HOMM Form Viewer for Craft CMS

HOMM Form Viewer for contact form requests

![Screenshot](resources/img/plugin-logo.svg)

## Requirements

This plugin requires Craft CMS 5.x and PHP 8.2+.

For the Craft CMS 4.x plugin version, see 2.x tags.
For the Craft CMS 3.x plugin version, see 1.x tags.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require homm/hommformviewer

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for HOMM Form Viewer.

## HOMM Form Viewer Overview

With this plugin you can send forms per email and view them through the control panel.
Specially this plugin does not send attachments through email, but saves it at a specified location and sends instead a link to the user.

## Using HOMM Form Viewer

Example form submission:

```twig
<form action="{{ url('hommformviewer/submit') }}" method="post" enctype="multipart/form-data">
    {{ csrfInput() }} {# pass `async: true` if you use static site caching #}
    {{ hiddenInput('formId', entry.formId) }}
    {{ hiddenInput('receivers', entry.receivers|hash) }}
    {{ hiddenInput('subject', entry.subject|hash) }}

    <label for="name">Name</label>
    <input type="text" name="name" id="name">

    <label for="email">Email</label>
    <input type="email" name="email" id="email">

    {# Optional: a field name which contains the reply address #}
    {{ hiddenInput('replyto', 'email'|hash) }}

    {# Optional: a confirmation text which will be sent to the email address provided by "replyto" #}
    <textarea name="confirmation" style="display: none;">{{ entry.confirmation }}</textarea>
</form>
```

After submitting a form, you can view, search and export them in the control panel.

## HOMM Form Viewer Roadmap

Some things to do, and ideas for potential features:

* You'll let us know...

Brought to you by [HOMM interactive](https://github.com/HOMMinteractive)
