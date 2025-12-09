<?php
/**
 * Plugin Name: Devenia Replace Media
 * Plugin URI: https://devenia.com/plugins/replace-media/
 * Description: Replace media files while keeping the same URL. Works in Media Library, Elementor gallery editor, and anywhere WordPress media is used. Preserves captions, alt text, and all metadata. Includes automatic cache busting.
 * Version: 1.6
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Author: Devenia
 * Author URI: https://devenia.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: devenia-replace-media
 *
 * @package Devenia_Replace_Media
 */

/*
Devenia Replace Media is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Devenia Replace Media is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Devenia Replace Media. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Cache busting: append version to image URLs
add_filter('wp_get_attachment_url', 'devenia_cache_bust_url', 10, 2);
add_filter('wp_get_attachment_image_src', 'devenia_cache_bust_src', 10, 4);

function devenia_cache_bust_url($url, $attachment_id) {
    $version = get_post_meta($attachment_id, '_devenia_replaced', true);
    if ($version) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'v=' . $version;
    }
    return $url;
}

function devenia_cache_bust_src($image, $attachment_id, $size, $icon) {
    if ($image && is_array($image)) {
        $version = get_post_meta($attachment_id, '_devenia_replaced', true);
        if ($version) {
            $image[0] .= (strpos($image[0], '?') === false ? '?' : '&') . 'v=' . $version;
        }
    }
    return $image;
}

// Add Replace button to media modal and Elementor gallery editor
add_action('admin_footer', 'devenia_replace_media_scripts');
add_action('elementor/editor/footer', 'devenia_replace_media_scripts');

function devenia_replace_media_scripts() {
    $base_url = admin_url('upload.php?page=devenia-replace-media&attachment_id=');
    ?>
    <script>
    jQuery(document).ready(function($) {
        var baseUrl = '<?php echo esc_js($base_url); ?>';

        function addReplaceButton() {
            if ($('.devenia-replace-btn').length) return;

            var attachmentId = null;

            // Method 1: Elementor gallery editor - get ID from Edit Image link
            var editLink = $('a[href*="action=edit"][href*="image-editor"]');
            if (editLink.length) {
                var match = editLink.attr('href').match(/post=(\d+)/);
                if (match) attachmentId = match[1];
            }

            // Method 2: Standard WP media modal
            if (!attachmentId && typeof wp !== 'undefined' && wp.media && wp.media.frame) {
                var selection = wp.media.frame.state().get('selection');
                if (selection && selection.first()) {
                    attachmentId = selection.first().get('id');
                }
            }

            if (!attachmentId) return;

            var btn = $('<a class="devenia-replace-btn" href="' + baseUrl + attachmentId + '" target="_blank" style="display:inline-block;margin:8px 0;padding:6px 12px;background:#2271b1;color:#fff;text-decoration:none;border-radius:3px;font-size:13px;">Replace File</a>');

            if (editLink.length) {
                editLink.after($('<br>')).after(btn);
            } else {
                var actionsEl = $('.attachment-actions, .attachment-info .actions');
                if (actionsEl.length) actionsEl.append(btn);
            }
        }

        var observer = new MutationObserver(function() {
            if (!$('.devenia-replace-btn').length) {
                var hasEditLink = $('a[href*="action=edit"][href*="image-editor"]').length;
                var hasDetails = $('.attachment-details').length;
                if (hasEditLink || hasDetails) {
                    setTimeout(addReplaceButton, 100);
                }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });

        $(document).on('click', '.attachment, .elementor-control-gallery-add, .elementor-control-gallery-thumbnails', function() {
            setTimeout(addReplaceButton, 300);
        });
    });
    </script>
    <?php
}

// Add "Replace File" link in Media Library list view
add_filter('media_row_actions', 'devenia_replace_media_row_action', 10, 2);

function devenia_replace_media_row_action($actions, $post) {
    $url = admin_url('upload.php?page=devenia-replace-media&attachment_id=' . $post->ID);
    $actions['replace'] = '<a href="' . esc_url($url) . '">Replace File</a>';
    return $actions;
}

// Add admin page (hidden from menu)
add_action('admin_menu', 'devenia_replace_media_menu');

function devenia_replace_media_menu() {
    add_submenu_page(null, 'Replace Media', 'Replace Media', 'upload_files', 'devenia-replace-media', 'devenia_replace_media_page');
}

// The replacement page
function devenia_replace_media_page() {
    $attachment_id = isset($_GET['attachment_id']) ? intval($_GET['attachment_id']) : 0;

    if (!$attachment_id || !($attachment = get_post($attachment_id)) || $attachment->post_type !== 'attachment') {
        wp_die('Invalid attachment.');
    }

    // Handle upload
    if (isset($_POST['devenia_replace_submit']) && check_admin_referer('devenia_replace_' . $attachment_id)) {
        $result = devenia_do_replace($attachment_id);
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>File replaced! You can close this tab and refresh the gallery.</p></div>';
            echo '<script>if(window.opener) { window.opener.location.reload(); }</script>';
        }
    }

    $current_url = wp_get_attachment_url($attachment_id);
    ?>
    <div class="wrap">
        <h1>Replace Media File</h1>
        <p><strong><?php echo esc_html(basename($current_url)); ?></strong></p>
        <?php echo wp_get_attachment_image($attachment_id, 'medium'); ?>
        <p>URL stays the same: <code><?php echo esc_html($current_url); ?></code></p>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('devenia_replace_' . $attachment_id); ?>
            <p><input type="file" name="replacement_file" required></p>
            <p>
                <input type="submit" name="devenia_replace_submit" class="button button-primary" value="Replace File">
                <button type="button" class="button" onclick="window.close()">Cancel</button>
            </p>
        </form>
    </div>
    <?php
}

// Do the actual replacement
function devenia_do_replace($attachment_id) {
    if (empty($_FILES['replacement_file']['tmp_name'])) {
        return new WP_Error('no_file', 'No file uploaded.');
    }

    $file = $_FILES['replacement_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return new WP_Error('upload_error', 'Upload failed.');
    }

    $current_file = get_attached_file($attachment_id);
    if (!$current_file) {
        return new WP_Error('no_current', 'Could not find current file.');
    }

    // Initialize WP Filesystem
    require_once ABSPATH . 'wp-admin/includes/file.php';
    WP_Filesystem();
    global $wp_filesystem;

    // Delete old thumbnails
    $metadata = wp_get_attachment_metadata($attachment_id);
    $current_dir = dirname($current_file);
    if (!empty($metadata['sizes'])) {
        foreach ($metadata['sizes'] as $sizeinfo) {
            $wp_filesystem->delete( $current_dir . '/' . $sizeinfo['file'] );
        }
    }

    // Delete original and copy new file

    $wp_filesystem->delete( $current_file );

    // Copy uploaded file to destination using WP Filesystem
    if ( ! $wp_filesystem->copy( $file['tmp_name'], $current_file, true, FS_CHMOD_FILE ) ) {
        return new WP_Error( 'move_failed', 'Failed to save file.' );
    }
    // Clean up temp file
    $wp_filesystem->delete( $file['tmp_name'] );

    // Update mime type
    $filetype = wp_check_filetype($current_file);
    if ($filetype['type']) {
        wp_update_post(['ID' => $attachment_id, 'post_mime_type' => $filetype['type']]);
    }

    // Regenerate thumbnails
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $current_file));
    clean_post_cache($attachment_id);

    // Save timestamp for cache busting
    update_post_meta($attachment_id, '_devenia_replaced', time());

    return true;
}
