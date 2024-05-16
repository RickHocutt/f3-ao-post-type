<?php
/*
Plugin Name: F3 AO Custom Post Type
Description: Adds a custom post type to create AO pages.
Version: 1.0
Author: Rick Hocutt "Clickbait"
*/

function create_custom_post_type() {
  register_post_type('f3-ao-pages',
      array(
          'labels' => array(
              'name' => __('F3 AO'),
              'singular_name' => __('F3 AO'),
              'all_items' => __('Locations (AOs)'), /* the all items menu item */
              'add_new' => __('Add an AO', 'add new'), /* The add new menu item */
              'add_new_item' => __('Add a New AO'), /* Add New Display Title */
              'edit' => __( 'Edit' ), /* Edit Dialog */
              'edit_item' => __('Edit AO'), /* Edit Display Title */
              'new_item' => __('New AO'), /* New Display Title */
              'view_item' => __('View AO'), /* View Display Title */
              'search_items' => __('Search AOs'), /* Search Custom Type Title */
              'not_found' =>  __('Nothing found in the AO Database.'), /* This displays if there are no entries yet */
              'not_found_in_trash' => __('Nothing found in Trash'), /* This displays if there is nothing in the trash */
          ),
          'public' => true,
          'has_archive' => 'ao', /* you can rename the slug here */
          'rewrite' => array('slug' => 'ao'),
          'exclude_from_search' => false,
          'menu_position' => 5, /* this is what order you want it to appear in on the left hand side menu */
          'menu_icon' => 'dashicons-star-filled', /* the icon for the custom post type menu. uses built-in dashicons (CSS class name) */
          'description' => __( 'F3 Area of Operations' ), /* Custom Type Description */

          'supports' => array('title', 'editor', 'thumbnail', 'trackbacks', 'custom-fields'),
      )
  );
}

add_action('init', 'create_custom_post_type');

// Add custom fields for getting the correct data from the Google Sheet for this AO

function add_custom_fields_meta_box() {
    add_meta_box(
        'custom_fields_meta_box', // $id
        'Additional Fields', // $title
        'show_custom_fields_meta_box', // $callback
        'f3-ao-pages', // $screen
        'normal', // $context
        'high' // $priority
    );
}
add_action('add_meta_boxes', 'add_custom_fields_meta_box');

function show_custom_fields_meta_box() {
    global $post;
    $meta = get_post_meta($post->ID, 'custom_fields', true);
if (!is_array($meta)) {
    $meta = array(
        'ao_website' => ''
    );
} ?>

    <input type="hidden" name="custom_meta_box_nonce" value="<?php echo wp_create_nonce(basename(__FILE__)); ?>">

    <p>
        <label for="custom_fields[ao_website]">AO Website (as entered in Google Sheet):</label>
        <input type="text" name="custom_fields[ao_website]" id="custom_fields[ao_website]" class="regular-text" value="<?php echo isset($meta['ao_website']) ? esc_attr($meta['ao_website']) : ''; ?>">
    </p>
    <!-- Add more fields as needed -->
<?php }


function save_custom_fields_meta($post_id) {
    if (!isset($_POST['custom_meta_box_nonce']) || !wp_verify_nonce($_POST['custom_meta_box_nonce'], basename(__FILE__)))
        return $post_id;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return $post_id;
    if ('page' === $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id))
            return $post_id;
    } elseif (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }

    $old = get_post_meta($post_id, 'custom_fields', true);
    $new = (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) ? $_POST['custom_fields'] : [];

    if (!empty($new) && $new !== $old) {
        update_post_meta($post_id, 'custom_fields', $new);
    } elseif (empty($new) && $old) {
        delete_post_meta($post_id, 'custom_fields', $old);
    }
}

add_action('save_post', 'save_custom_fields_meta');


// Add entry for storing the Google Sheet data, Shown as a menu in the Settings section of the Dashboard page

add_action('admin_menu', 'add_my_custom_menu');

function add_my_custom_menu() {
    // This page will be under "Settings"
    add_options_page('Custom Settings', 'Custom Settings', 'manage_options', 'my-custom-settings', 'my_custom_settings_page');
}

function my_custom_settings_page() {
    ?>
    <div class="wrap">
        <h1>Custom Settings</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('my-custom-settings-group');
                do_settings_sections('my-custom-settings-group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Google Sheets API Key</th>
                    <td><input type="text" name="google_sheet_api_key" value="<?php echo esc_attr(get_option('google_sheet_api_key')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">AO Schedule Google Sheet ID</th>
                    <td><input type="text" name="ao_schedule_google_sheet_id" value="<?php echo esc_attr(get_option('ao_schedule_google_sheet_id')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'my_custom_settings');

function my_custom_settings() {
    register_setting('my-custom-settings-group', 'ao_schedule_google_sheet_id', 'sanitize_text_field');
}
