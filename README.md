# Devenia Replace Media

**Replace images and files while keeping the same URL.** Works with Elementor and Gutenberg.

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

## The Problem

You uploaded an image. It's used in 47 places across your site. Now you need to replace it with a better version.

WordPress gives you two bad options:
1. Delete the old image, upload new one, manually update all 47 places
2. Upload with same filename and pray the cache clears

## The Solution

This plugin adds a "Replace File" button to every image in your media library. Click it, upload the new file, done. Same URL, new file, automatic cache busting.

## Features

- Replace any file in media library (images, PDFs, etc.)
- Keeps the same URL - no broken links
- Works in Media Library, Elementor gallery editor, anywhere WordPress media is used
- Preserves captions, alt text, and all metadata
- Automatic cache busting so visitors see the new file immediately
- Regenerates all thumbnail sizes

## Installation

1. Download from [Releases](https://github.com/bjornfix/devenia-replace-media/releases)
2. Upload via WordPress Admin → Plugins → Add New → Upload Plugin
3. Activate the plugin

That's it. No settings to configure.

## How to Use

1. Go to Media Library
2. Click on any image
3. Click "Replace File" button
4. Upload your new file
5. Done - all instances across your site now show the new image

The button also appears in Elementor's gallery editor when editing an image.

## How It Works

When you replace a file:
1. The new file overwrites the old one on disk
2. All thumbnail sizes are regenerated
3. A version parameter is added to URLs (`?v=timestamp`)
4. Browser caches are bypassed, visitors see the new file immediately

## Requirements

- WordPress 5.0+
- PHP 7.4+

## Changelog

### 1.7
- Works in Elementor gallery editor
- Better button styling

### 1.6
- Automatic cache busting
- Thumbnail regeneration

### 1.0
- Initial release

## License

GPL-2.0+

## Author

[Devenia](https://devenia.com) - We've been doing SEO and web development since 1993.

## Links

- [Plugin Page](https://devenia.com/plugins/replace-media/)
