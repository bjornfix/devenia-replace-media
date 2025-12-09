=== Devenia Replace Media ===
Contributors: basicus
Donate link: https://devenia.com/
Tags: replace media, replace image, media library, elementor, cache busting
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.6
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replace media files while keeping the same URL. Works everywhere: Media Library, Elementor, and more.

For more information, visit [Devenia Replace Media](https://devenia.com/plugins/replace-media/).

== Description ==

**Devenia Replace Media** lets you replace any media file in WordPress while keeping the original URL intact. Perfect for updating images without breaking links or losing SEO value.

= Key Features =

* **Keep Your URLs** - Replace the file, keep the URL. No broken links, no lost SEO.
* **Works Everywhere** - Media Library list view, Elementor gallery editor, and standard WordPress media modals.
* **Preserves Metadata** - Captions, alt text, titles, and descriptions stay intact.
* **Automatic Cache Busting** - Browsers automatically fetch the new file. No manual cache clearing needed.
* **Regenerates Thumbnails** - All image sizes are automatically regenerated after replacement.
* **Simple Interface** - Just click "Replace File", upload your new file, done.

= Where It Works =

* **Media Library** - "Replace File" link appears in the list view actions
* **Elementor Gallery Editor** - Blue "Replace File" button in attachment details
* **WordPress Media Modal** - Works in the standard media selector used by Gutenberg and Classic Editor

= Use Cases =

* Update product images without changing URLs
* Fix typos in PDFs or documents
* Replace outdated screenshots
* Update seasonal images across your site
* Swap placeholder images for final versions

= Why Cache Busting Matters =

When you replace an image, browsers may show the old cached version. This plugin automatically appends a version parameter to URLs after replacement, forcing browsers to fetch the new file. Your visitors always see the latest version.

== Installation ==

1. Upload the `devenia-replace-media` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's it! Look for "Replace File" links in your Media Library

== Frequently Asked Questions ==

= Will this break my existing links? =

No. The file URL stays exactly the same. Only the file content changes.

= Does it work with CDNs? =

Yes. The cache-busting version parameter ensures CDNs serve the new file.

= What file types can I replace? =

Any file type that WordPress allows in the Media Library: images (JPG, PNG, GIF, WebP), documents (PDF, DOC), audio, video, and more.

= Can I replace an image with a different file type? =

Yes. For example, you can replace a JPG with a PNG. The URL path stays the same, but the MIME type is updated.

= Does it preserve my captions and alt text? =

Yes. All attachment metadata (title, caption, alt text, description) is preserved.

= I replaced an image but still see the old one. What do I do? =

Try a hard refresh (Ctrl+Shift+R or Cmd+Shift+R). The plugin adds cache-busting parameters, but your browser may have a very aggressive cache. For images replaced before installing this plugin, the cache-busting won't apply.

== Screenshots ==

1. Replace File link in Media Library list view
2. Replace File button in Elementor gallery editor
3. Simple upload interface

== Changelog ==

= 1.6 =
* Uses WordPress Filesystem API for all file operations
* Improved security and WordPress.org compatibility

= 1.5 =
* Added automatic cache busting - URLs now include version parameter after replacement
* Browsers automatically fetch new files without manual cache clearing

= 1.4 =
* Added support for Elementor gallery editor
* Uses `elementor/editor/footer` hook for proper script loading

= 1.3 =
* Changed script loading method for better compatibility

= 1.2 =
* Improved attachment ID detection in media modals

= 1.1 =
* Added JavaScript injection for media modal support

= 1.0 =
* Initial release
* Replace files from Media Library list view
* Preserve metadata and regenerate thumbnails

== Upgrade Notice ==

= 1.6 =
Uses WordPress Filesystem API for improved security and WordPress.org compatibility.

= 1.5 =
Adds automatic cache busting. After replacing an image, visitors will automatically see the new version without clearing their browser cache.
