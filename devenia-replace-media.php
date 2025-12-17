<?php
/**
 * Plugin Name: Devenia Replace Media
 * Plugin URI: https://devenia.com/plugins/replace-media/
 * Description: Replace media files while keeping the same URL. Works in Media Library, Elementor gallery editor, and anywhere WordPress media is used. Preserves captions, alt text, and all metadata. Includes automatic cache busting.
 * Version: 1.7.3
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

// Enqueue scripts for Replace button in media modal and Elementor gallery editor
add_action( 'admin_enqueue_scripts', 'devenia_replace_media_enqueue_scripts' );
add_action( 'elementor/editor/before_enqueue_scripts', 'devenia_replace_media_enqueue_scripts' );

/**
 * Enqueue the Replace Media scripts properly using wp_add_inline_script.
 */
function devenia_replace_media_enqueue_scripts() {
    // Only load on pages where media modal might be used.
    $screen = get_current_screen();
    $allowed_screens = array( 'upload', 'post', 'page', 'elementor' );

    // For Elementor editor, screen might not be set the same way.
    $is_elementor = did_action( 'elementor/editor/before_enqueue_scripts' );

    if ( ! $is_elementor && $screen && ! in_array( $screen->base, $allowed_screens, true ) && $screen->base !== 'toplevel_page_elementor' ) {
        // Still allow on any admin page that might use media modal.
        if ( ! wp_script_is( 'media-views', 'enqueued' ) && ! $is_elementor ) {
            // Don't load on pages without media functionality, but be permissive.
        }
    }

    // Register a dummy script handle to attach inline script to.
    wp_register_script(
        'devenia-replace-media',
        false, // No external file.
        array( 'jquery' ),
        '1.7.3',
        true // In footer.
    );
    wp_enqueue_script( 'devenia-replace-media' );

    // Build the nonce for AJAX-style security (attachment ID will be added client-side).
    $nonce = wp_create_nonce( 'devenia_replace_media_access' );
    $base_url = admin_url( 'upload.php?page=devenia-replace-media&_wpnonce=' . $nonce . '&attachment_id=' );

    $inline_script = '
    jQuery(document).ready(function($) {
        var baseUrl = ' . wp_json_encode( $base_url ) . ';

        function addReplaceButton() {
            if ($(".devenia-replace-btn").length) return;

            var attachmentId = null;

            // Method 1: Elementor gallery editor - get ID from Edit Image link
            var editLink = $("a[href*=\"action=edit\"][href*=\"image-editor\"]");
            if (editLink.length) {
                var match = editLink.attr("href").match(/post=(\d+)/);
                if (match) attachmentId = match[1];
            }

            // Method 2: Standard WP media modal
            if (!attachmentId && typeof wp !== "undefined" && wp.media && wp.media.frame) {
                var selection = wp.media.frame.state().get("selection");
                if (selection && selection.first()) {
                    attachmentId = selection.first().get("id");
                }
            }

            if (!attachmentId) return;

            var btn = $("<a class=\"devenia-replace-btn\" href=\"" + baseUrl + attachmentId + "\" target=\"_blank\" style=\"display:inline-block;margin:8px 0;padding:6px 12px;background:#2271b1;color:#fff;text-decoration:none;border-radius:3px;font-size:13px;\">Replace File</a>");

            if (editLink.length) {
                editLink.after($("<br>")).after(btn);
            } else {
                var actionsEl = $(".attachment-actions, .attachment-info .actions");
                if (actionsEl.length) actionsEl.append(btn);
            }
        }

        var observer = new MutationObserver(function() {
            if (!$(".devenia-replace-btn").length) {
                var hasEditLink = $("a[href*=\"action=edit\"][href*=\"image-editor\"]").length;
                var hasDetails = $(".attachment-details").length;
                if (hasEditLink || hasDetails) {
                    setTimeout(addReplaceButton, 100);
                }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });

        $(document).on("click", ".attachment, .elementor-control-gallery-add, .elementor-control-gallery-thumbnails", function() {
            setTimeout(addReplaceButton, 300);
        });
    });
    ';

    wp_add_inline_script( 'devenia-replace-media', $inline_script );
}

// Add "Replace File" link in Media Library list view
add_filter( 'media_row_actions', 'devenia_replace_media_row_action', 10, 2 );

/**
 * Add Replace File action to media row actions.
 *
 * @param array   $actions Existing actions.
 * @param WP_Post $post    The attachment post object.
 * @return array Modified actions.
 */
function devenia_replace_media_row_action( $actions, $post ) {
    $nonce = wp_create_nonce( 'devenia_replace_media_access' );
    $url   = admin_url( 'upload.php?page=devenia-replace-media&_wpnonce=' . $nonce . '&attachment_id=' . $post->ID );
    $actions['replace'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Replace File', 'devenia-replace-media' ) . '</a>';
    return $actions;
}

// Add admin page (hidden from menu)
add_action('admin_menu', 'devenia_replace_media_menu');

function devenia_replace_media_menu() {
    add_submenu_page(null, 'Replace Media', 'Replace Media', 'upload_files', 'devenia-replace-media', 'devenia_replace_media_page');
}

/**
 * The replacement page.
 */
function devenia_replace_media_page() {
    // Verify nonce from GET request.
    $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'devenia_replace_media_access' ) ) {
        wp_die( esc_html__( 'Security check failed. Please try again from the Media Library.', 'devenia-replace-media' ) );
    }

    // Sanitize and validate attachment ID.
    $attachment_id = isset( $_GET['attachment_id'] ) ? absint( $_GET['attachment_id'] ) : 0;

    if ( ! $attachment_id ) {
        wp_die( esc_html__( 'Invalid attachment ID.', 'devenia-replace-media' ) );
    }

    $attachment = get_post( $attachment_id );
    if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
        wp_die( esc_html__( 'Invalid attachment.', 'devenia-replace-media' ) );
    }

    // Check user capability.
    if ( ! current_user_can( 'upload_files' ) ) {
        wp_die( esc_html__( 'You do not have permission to replace media files.', 'devenia-replace-media' ) );
    }

    $success_message = '';
    $error_message   = '';

    // Handle upload.
    if ( isset( $_POST['devenia_replace_submit'] ) && check_admin_referer( 'devenia_replace_' . $attachment_id ) ) {
        $result = devenia_do_replace( $attachment_id );
        if ( is_wp_error( $result ) ) {
            $error_message = $result->get_error_message();
        } else {
            $success_message = __( 'File replaced! You can close this tab and refresh the gallery.', 'devenia-replace-media' );
            // Enqueue inline script to refresh opener.
            add_action( 'admin_footer', 'devenia_replace_media_success_script' );
        }
    }

    $current_url = wp_get_attachment_url( $attachment_id );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Replace Media File', 'devenia-replace-media' ); ?></h1>

        <?php if ( $error_message ) : ?>
            <div class="notice notice-error"><p><?php echo esc_html( $error_message ); ?></p></div>
        <?php endif; ?>

        <?php if ( $success_message ) : ?>
            <div class="notice notice-success"><p><?php echo esc_html( $success_message ); ?></p></div>
        <?php endif; ?>

        <p><strong><?php echo esc_html( basename( $current_url ) ); ?></strong></p>
        <?php echo wp_get_attachment_image( $attachment_id, 'medium' ); ?>
        <p><?php esc_html_e( 'URL stays the same:', 'devenia-replace-media' ); ?> <code><?php echo esc_html( $current_url ); ?></code></p>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'devenia_replace_' . $attachment_id ); ?>
            <p><input type="file" name="replacement_file" required></p>
            <p>
                <input type="submit" name="devenia_replace_submit" class="button button-primary" value="<?php esc_attr_e( 'Replace File', 'devenia-replace-media' ); ?>">
                <button type="button" class="button" onclick="window.close()"><?php esc_html_e( 'Cancel', 'devenia-replace-media' ); ?></button>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Output success script to refresh opener window.
 */
function devenia_replace_media_success_script() {
    wp_print_inline_script_tag( 'if(window.opener) { window.opener.location.reload(); }' );
}

/**
 * Do the actual file replacement.
 *
 * @param int $attachment_id The attachment ID to replace.
 * @return true|WP_Error True on success, WP_Error on failure.
 */
function devenia_do_replace( $attachment_id ) {
    // Verify nonce.
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'devenia_replace_' . $attachment_id ) ) {
        return new WP_Error( 'nonce_failed', __( 'Security check failed.', 'devenia-replace-media' ) );
    }

    // Check user capability.
    if ( ! current_user_can( 'upload_files' ) ) {
        return new WP_Error( 'no_permission', __( 'You do not have permission to replace files.', 'devenia-replace-media' ) );
    }

    // Validate file upload exists.
    if ( ! isset( $_FILES['replacement_file'] ) ) {
        return new WP_Error( 'no_file', __( 'No file uploaded.', 'devenia-replace-media' ) );
    }

    // Access the file array - $_FILES cannot be sanitized with standard functions.
    // We validate the upload error code, file type, and use WP functions for handling.
    $uploaded_file = array(
        'name'     => isset( $_FILES['replacement_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['replacement_file']['name'] ) ) : '',
        'type'     => isset( $_FILES['replacement_file']['type'] ) ? sanitize_mime_type( wp_unslash( $_FILES['replacement_file']['type'] ) ) : '',
        'tmp_name' => isset( $_FILES['replacement_file']['tmp_name'] ) ? $_FILES['replacement_file']['tmp_name'] : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is a server path, validated via is_uploaded_file().
        'error'    => isset( $_FILES['replacement_file']['error'] ) ? absint( $_FILES['replacement_file']['error'] ) : UPLOAD_ERR_NO_FILE,
        'size'     => isset( $_FILES['replacement_file']['size'] ) ? absint( $_FILES['replacement_file']['size'] ) : 0,
    );

    // Check for upload errors.
    if ( UPLOAD_ERR_OK !== $uploaded_file['error'] ) {
        return new WP_Error( 'upload_error', __( 'Upload failed.', 'devenia-replace-media' ) );
    }

    // Validate the file was actually uploaded (security check against path traversal).
    if ( empty( $uploaded_file['tmp_name'] ) || ! is_uploaded_file( $uploaded_file['tmp_name'] ) ) {
        return new WP_Error( 'invalid_upload', __( 'Invalid file upload.', 'devenia-replace-media' ) );
    }

    // Validate file type using WordPress functions.
    $filetype = wp_check_filetype( $uploaded_file['name'] );
    if ( ! $filetype['type'] ) {
        return new WP_Error( 'invalid_type', __( 'File type not allowed.', 'devenia-replace-media' ) );
    }

    $current_file = get_attached_file( $attachment_id );
    if ( ! $current_file ) {
        return new WP_Error( 'no_current', __( 'Could not find current file.', 'devenia-replace-media' ) );
    }

    // Load WP Filesystem - required for file operations.
    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    WP_Filesystem();
    global $wp_filesystem;

    // Delete old thumbnails.
    $metadata    = wp_get_attachment_metadata( $attachment_id );
    $current_dir = dirname( $current_file );
    if ( ! empty( $metadata['sizes'] ) ) {
        foreach ( $metadata['sizes'] as $sizeinfo ) {
            $wp_filesystem->delete( $current_dir . '/' . $sizeinfo['file'] );
        }
    }

    // Delete original file.
    $wp_filesystem->delete( $current_file );

    // Copy uploaded file to destination using WP Filesystem.
    if ( ! $wp_filesystem->copy( $uploaded_file['tmp_name'], $current_file, true, FS_CHMOD_FILE ) ) {
        return new WP_Error( 'move_failed', __( 'Failed to save file.', 'devenia-replace-media' ) );
    }

    // Update mime type.
    $new_filetype = wp_check_filetype( $current_file );
    if ( $new_filetype['type'] ) {
        wp_update_post(
            array(
                'ID'             => $attachment_id,
                'post_mime_type' => $new_filetype['type'],
            )
        );
    }

    // Regenerate thumbnails - requires image.php for wp_generate_attachment_metadata().
    if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }
    wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $current_file ) );
    clean_post_cache( $attachment_id );

    // Save timestamp for cache busting
    update_post_meta($attachment_id, '_devenia_replaced', time());

    return true;
}
