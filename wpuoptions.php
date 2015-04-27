<?php

/*
Plugin Name: WPU Options
Plugin URI: http://github.com/Darklg/WPUtilities
Version: 4.16
Description: Friendly interface for website options
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUOptions {

    private $options = array();

    private $default_box = array(
        'default' => array(
            'name' => ''
        )
    );

    private $default_tab = array(
        'default' => array(
            'name' => 'Site options'
        )
    );

    /**
     * Init plugin
     */
    function __construct() {
        $this->hooks();
        if (is_admin()) {
            $this->set_options();
            $this->admin_hooks();
        }
    }

    function load_plugin_textdomain() {
        load_plugin_textdomain('wpuoptions', false, dirname(plugin_basename(__FILE__)) . '/lang/');
    }

    /**
     * Set Options
     */
    private function set_options() {
        $this->options = array(
            'plugin_publicname' => __('Site options', 'wpuoptions') ,
            'plugin_name' => 'WPU Options',
            'plugin_version' => '4.13',
            'plugin_userlevel' => 'manage_categories',
            'plugin_menutype' => 'admin.php',
            'plugin_pageslug' => 'wpuoptions-settings',
            'plugin_dir' => str_replace(ABSPATH, (site_url() . '/') , dirname(__FILE__)) ,
            'plugin_basename' => str_replace(ABSPATH . 'wp-content/plugins/', '', __FILE__)
        );

        $this->main_url = $this->options['plugin_menutype'] . '?page=' . $this->options['plugin_pageslug'];
    }

    /**
     * Hooks
     */

    private function hooks() {
        add_action('plugins_loaded', array(&$this,
            'load_plugin_textdomain'
        ));
        add_action('init', array(&$this,
            'set_fields'
        ));
        add_action('init', array(&$this,
            'default_values'
        ));
    }

    /**
     * Set fields values
     */
    public function set_fields() {
        $this->fields = apply_filters('wpu_options_fields', array());
        $this->boxes = apply_filters('wpu_options_boxes', $this->default_box);
        $this->tabs = apply_filters('wpu_options_tabs', $this->default_tab);
    }

    /**
     * Check that every option is defined, to avoid non autoloading options
     */
    public function default_values() {
        foreach ($this->fields as $id => $option) {
            $opt = get_option($id);
            if ($opt === false) {
                update_option($id, '');
            }
        }
    }

    /**
     * Set admin hooks
     */
    private function admin_hooks() {
        add_action('admin_menu', array(&$this,
            'admin_menu'
        ));
        add_action('admin_bar_menu', array(&$this,
            'add_toolbar_menu_items'
        ) , 100);
        add_filter("plugin_action_links_" . plugin_basename(__FILE__) , array(&$this,
            'settings_link'
        ));
        add_action('admin_enqueue_scripts', array(&$this,
            'add_assets_js'
        ));
        add_action('admin_print_styles', array(&$this,
            'add_assets_css'
        ));
    }

    /**
     * Set admin menu
     */
    function admin_menu() {
        add_menu_page($this->options['plugin_name'] . ' Settings', $this->options['plugin_publicname'], $this->options['plugin_userlevel'], $this->options['plugin_pageslug'], array(&$this,
            'admin_settings'
        ) , '', 3);
        add_submenu_page($this->options['plugin_pageslug'], __('Import', 'wpuoptions') , __('Import', 'wpuoptions') , $this->options['plugin_userlevel'], $this->options['plugin_pageslug'] . '-import', array(&$this,
            'admin_import_page'
        ));
        add_submenu_page($this->options['plugin_pageslug'], __('Export', 'wpuoptions') , __('Export', 'wpuoptions') , $this->options['plugin_userlevel'], $this->options['plugin_pageslug'] . '-export', array(&$this,
            'admin_export_page'
        ));
    }

    /**
     * Settings link
     */
    function settings_link($links) {
        $settings_link = '<a href="' . admin_url($this->main_url) . '">' . __('Options', 'wpuoptions') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Add menu items to toolbar
     *
     * @param unknown $admin_bar
     */
    function add_toolbar_menu_items($admin_bar) {
        if (!current_user_can($this->options['plugin_userlevel'])) {
            return;
        }
        $admin_bar->add_menu(array(
            'id' => 'wpu-options-menubar-link',
            'title' => $this->options['plugin_publicname'],
            'href' => admin_url($this->main_url) ,
            'meta' => array(
                'title' => $this->options['plugin_publicname'],
            ) ,
        ));
    }

    /**
     * Enqueue JS
     */
    function add_assets_js() {
        if (isset($_GET['page']) && $_GET['page'] == $this->options['plugin_pageslug']) {
            wp_enqueue_media();
            wp_enqueue_script('wpuoptions_scripts', plugins_url('assets/events.js', __FILE__) , array(
                'jquery-ui-core',
                'jquery-ui-widget',
                'jquery-ui-mouse',
                'jquery-ui-slider',
                'jquery-ui-datepicker',
                'iris',
            ) , $this->options['plugin_version']);
        }
    }

    /**
     * Enqueue CSS
     */
    function add_assets_css() {
        if (isset($_GET['page']) && $_GET['page'] == $this->options['plugin_pageslug']) {
            wp_register_style('wpuoptions_style', plugins_url('assets/style.css', __FILE__) , array() , $this->options['plugin_version']);
            wp_enqueue_style('wpuoptions_style');
        }
    }

    /**
     * Set admin page
     */
    function admin_settings() {
        $content = '<div class="wrap">';
        $content.= '<div id="icon-tools" class="icon32"></div><h2>' . $this->options['plugin_publicname'] . '</h2>';
        if (!empty($this->fields)) {
            $content.= $this->admin_update();
            $content.= $this->admin_form();
        }
        else {
            $content.= '<p>' . __('No fields for the moment', 'wpuoptions') . '</p>';
        }
        $content.= '</div>';
        echo $content;
    }

    /**
     * Admin submenu export
     */
    function admin_export_page() {
        echo '<div class="wrap">';
        echo '<div id="icon-tools" class="icon32"></div><h2>' . __('Export', 'wpuoptions') . '</h2>';
        echo '<p>' . __("Click below to download a .json file containing all your website's options.", 'wpuoptions') . '</p>';
        echo '<p>' . $this->generate_export_url() . '</p>';
        echo '</div>';
    }

    /**
     * Admin submenu import
     */
    function admin_import_page() {
        echo '<div class="wrap">';
        echo '<div id="icon-tools" class="icon32"></div><h2>' . __('Import', 'wpuoptions') . '</h2>';

        if (isset($_FILES["wpu_import_options"])) {
            $import_options = $_FILES["wpu_import_options"]['tmp_name'];
            if (file_exists($import_options)) {
                $import_tmp = file_get_contents($import_options);
                $import = $this->import_options($import_tmp);
                if ($import) {
                    echo '<div class="updated"><p>' . __('The file has been successfully imported.', 'wpuoptions') . '</p></div>';
                }
                else {
                    echo '<div class="error"><p>' . __('The file has not been imported.', 'wpuoptions') . '</p></div>';
                }
            }
        }
        echo '<p>' . __("Upload a .json file (generated by WPU Options) to import your website's options.", 'wpuoptions') . '</p>';
        echo '<form action="" method="post" enctype="multipart/form-data"><div><input type="file" name="wpu_import_options" /> <button class="button button-primary" type="value">' . __('Import options file', 'wpuoptions') . '</button></div></form>';
        echo '</div>';
    }

    /**
     * Save new values
     *
     * @return unknown
     */
    private function admin_update() {
        $content = '';
        if (!isset($_POST['plugin_ok'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['wpuoptions-noncefield'], 'wpuoptions-nonceaction')) {
            $content.= '<p>' . __("Error in the form.", 'wpuoptions') . '</p>';
        }
        else {
            $languages = $this->get_languages();
            $updated_options = array();
            $errors = array();
            $testfields = array();
            foreach ($this->fields as $id => $field) {
                $testfields[$id] = $field;
                if (isset($field['lang']) && !empty($languages)) {
                    foreach ($languages as $lang => $name) {
                        $newfield = $field;
                        $newfield['label'] = '[' . $lang . '] ' . $newfield['label'];
                        $testfields[$lang . '___' . $id] = $newfield;
                    }
                }
            }

            foreach ($testfields as $id => $field) {
                $idf = $this->get_field_id($id);
                if (isset($_POST[$idf])) {
                    $field = $this->get_field_datas($id, $field);
                    $old_option = get_option($id);
                    $new_option = trim(stripslashes($_POST[$idf]));

                    $test_field = $this->test_field_value($field, $new_option);

                    $field_label = $field['label'];
                    if (isset($field['box']) && isset($this->boxes[$field['box']]['name'])) {
                        $field_label = '<em>' . $this->boxes[$field['box']]['name'] . '</em> - ' . $field['label'];
                    }

                    // Field is required and have been emptied
                    if ($new_option == '' && isset($field['required'])) {
                        $errors[] = sprintf(__('The field "%s" must not be empty', 'wpuoptions') , $field_label);
                    }

                    // If test is ok OR the field is not required
                    elseif ($test_field || ($new_option == '' && !isset($field['required']))) {
                        if ($old_option != $new_option) {
                            update_option($id, $new_option);
                            $updated_options[] = sprintf(__('The field "%s" has been updated.', 'wpuoptions') , $field_label);
                        }
                    }
                    else {
                        $errors[] = sprintf(__('The field "%s" has not been updated, because it\'s not valid.', 'wpuoptions') , $field_label);
                    }
                }
            }
            if (!empty($updated_options)) {
                $content.= '<div class="updated"><p><strong>' . __('Success!', 'wpuoptions') . '</strong><br />' . implode('<br />', $updated_options) . '</p></div>';
            }
            if (!empty($errors)) {
                $content.= '<div class="error"><p><strong>' . __('Fail!', 'wpuoptions') . '</strong><br />' . implode('<br />', $errors) . '</p></div>';
            }
        }
        return $content;
    }

    /**
     * Returns admin form
     *
     * @return unknown
     */
    private function admin_form() {

        $current_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $this->tabs) ? $_GET['tab'] : 'default';

        $content = '<form action="" method="post" class="wpu-options-form">';

        if (count($this->tabs) > 1) {
            $content.= '<div id="icon-themes" class="icon32"><br></div>';
            $content.= '<h2 class="nav-tab-wrapper">';
            foreach ($this->tabs as $idtab => $tab) {
                $current_class = ($current_tab == $idtab ? 'nav-tab-active' : '');
                $tab_url = '';
                if ($idtab != 'default') {
                    $tab_url = '&tab=' . $idtab;
                }
                $content.= '<a class="nav-tab ' . $current_class . '" href="' . admin_url($this->main_url . $tab_url) . '">' . $tab['name'] . '</a>';
            }
            $content.= '</h2><br />';
        }

        foreach ($this->boxes as $idbox => $box) {
            $box_tab = isset($box['tab']) ? $box['tab'] : 'default';
            if ($box_tab != $current_tab) {
                continue;
            }
            $content_tmp = '';
            foreach ($this->fields as $id => $field) {
                if ((isset($field['box']) && $field['box'] == $idbox) || ($idbox == 'default' && !isset($field['box']))) {
                    $content_tmp.= $this->admin_field($id, $field);
                }
            }
            if (!empty($content_tmp)) {
                $content.= '<div class="wpu-options-form__box">';

                // Adding box name if available
                if (empty($box['name'])) {
                    $box['name'] = ucfirst($idbox);
                }
                $content.= '<h3 id="box-' . $idbox . '" class="wpu-options-form__title">' . $box['name'] . '</h3>';
                $content.= '<table id="wpu-options-form__table">' . $content_tmp . '</table>';
                $content.= '</div>';
            }
        }
        $content.= '<ul><li><input class="button button-primary" name="plugin_ok" value="' . __('Update', 'wpuoptions') . '" type="submit" /></li></ul>';
        $content.= wp_nonce_field('wpuoptions-nonceaction', 'wpuoptions-noncefield', 1, 0);
        $content.= '</form>';
        return $content;
    }

    /**
     * Return an admin field
     *
     * @param unknown $id
     * @param unknown $field (optional)
     * @return unknown
     */
    private function admin_field($id, $field = array()) {
        $languages = $this->get_languages();
        $fields_versions = array();

        if (empty($languages) || !isset($field['lang'])) {
            $fields_versions[] = array(
                'id' => $id,
                'field' => $field,
                'prefix_label' => '',
                'prefix_opt' => '',
            );
        }
        else {
            foreach ($languages as $idlang => $lang) {
                $fields_versions[] = array(
                    'id' => $id,
                    'field' => $field,
                    'prefix_label' => '[' . $idlang . '] ',
                    'prefix_opt' => $idlang . '___',
                    'lang' => $lang,
                );
            }
        }
        $content = '';
        $upload_dir = wp_upload_dir();
        foreach ($fields_versions as $field_version) {
            $idf = $this->get_field_id($field_version['prefix_opt'] . $field_version['id']);
            $field = $this->get_field_datas($field_version['id'], $field_version['field']);
            $idname = ' id="' . $idf . '" name="' . $idf . '" ';
            $originalvalue = get_option($field_version['prefix_opt'] . $field_version['id']);
            if ($originalvalue === false && isset($field['default_value']) && $this->test_field_value($field, $field['default_value'])) {
                $originalvalue = $field['default_value'];
                update_option($field_version['prefix_opt'] . $field_version['id'], $field['default_value']);
            }
            $value = htmlspecialchars($originalvalue, ENT_QUOTES, "UTF-8");

            $content.= '<tr class="wpu-options-box">';
            $content.= '<td class="td-label"><label for="' . $idf . '">' . $field_version['prefix_label'] . $field['label'] . ' : </label></td>';
            $content.= '<td>';
            switch ($field['type']) {
                case 'editor':
                    ob_start();
                    wp_editor($originalvalue, $idf, array(
                        'textarea_rows' => 7
                    ));
                    $content_editor = ob_get_clean();
                    if (!empty($originalvalue)) {
                        $content.= '<div class="wpuoptions-view-editor-switch">';
                        $content.= '<div class="original-view"><div class="original">' . apply_filters('the_content', $originalvalue) . '</div><a class="edit-link button button-small" href="#" role="button">' . __('Edit this text', 'wpuoptions') . '</a>' . '</div>';
                        $content.= '<div class="editor-view">' . $content_editor . '<a class="edit-link button button-small" href="#" role="button">' . __('Cancel edition', 'wpuoptions') . '</a>' . '</div>';
                        $content.= '</div>';
                    }
                    else {
                        $content.= $content_editor;
                    }
                break;
                case 'file':
                case 'media':
                    $btn_label = __('Add a picture', 'wpuoptions');
                    $btn_edit_label = __('Change this picture', 'wpuoptions');
                    $btn_confirm_delete = __('Do you really want to remove this image ?', 'wpuoptions');
                    if ($field['type'] == 'file') {
                        $btn_label = __('Add a file', 'wpuoptions');
                        $btn_edit_label = __('Change this file', 'wpuoptions');
                        $btn_confirm_delete = __('Do you really want to remove this file ?', 'wpuoptions');
                    }
                    $btn_label_display = $btn_label;
                    $content_preview = '';
                    if (is_numeric($value)) {
                        $image = wp_get_attachment_image_src($value, 'big');
                        if (isset($image[0])) {
                            $content_preview = '<div class="wpu-options-upload-preview"><span class="x">&times;</span><img src="' . $image[0] . '?v=' . time() . '" alt="" /></div>';
                        }
                        else {
                            $file = wp_get_attachment_url($value);
                            $file = str_replace($upload_dir['baseurl'], '', $file);
                            $content_preview = '<div class="wpu-options-upload-preview"><span class="x">&times;</span><div class="wpu-options-upload-preview--file">' . $file . '</div></div>';
                        }
                        $btn_label_display = $btn_edit_label;
                    }

                    $content.= '<div data-removethis="' . $upload_dir['baseurl'] . '" data-type="' . $field['type'] . '" data-confirm="' . $btn_confirm_delete . '" data-defaultlabel="' . esc_attr($btn_label) . '" data-label="' . esc_attr($btn_edit_label) . '" id="preview-' . $idf . '">' . $content_preview . '</div>' . '<a href="#" data-for="' . $idf . '" class="button button-small wpuoptions_add_media">' . $btn_label_display . '</a>' . '<input class="hidden-value" type="hidden" ' . $idname . ' value="' . $value . '" />';
                break;
                case 'category':
                    $content.= wp_dropdown_categories(array(
                        'name' => $idf,
                        'selected' => $value,
                        'echo' => 0,
                        'hide_if_empty' => 1
                    ));
                break;
                case 'page':
                    $content.= wp_dropdown_pages(array(
                        'name' => $idf,
                        'selected' => $value,
                        'echo' => 0,
                    ));
                break;
                case 'post':
                    $field_post_type = isset($field['post_type']) ? $field['post_type'] : 'post';
                    $wpq_post_type = new WP_Query(array(
                        'posts_per_page' => - 1,
                        'post_type' => $field_post_type,
                        'orderby' => 'name',
                        'order' => 'ASC'
                    ));
                    $content.= '<select ' . $idname . '"><option value="" disabled selected style="display:none;">' . sprintf(__('Select a %s', 'wpuoptions') , $field_post_type) . '</option>';
                    while ($wpq_post_type->have_posts()) {
                        $wpq_post_type->the_post();
                        $key = get_the_ID();
                        $content.= '<option value="' . htmlentities($key) . '" ' . selected($key, $value, 0) . '>';
                        $content.= get_the_title();
                        $content.= '</option>';
                    }
                    wp_reset_postdata();
                    $content.= '</select>';
                break;
                case 'select':
                    $content.= '<select ' . $idname . '"><option value="" disabled selected style="display:none;">' . __('Select a value', 'wpuoptions') . '</option>';
                    foreach ($field['datas'] as $key => $var) {
                        $content.= '<option value="' . htmlentities($key) . '" ' . selected($key, $value, 0) . '>' . htmlentities($var) . '</option>';
                    }
                    $content.= '</select>';
                break;
                case 'radio':
                    foreach ($field['datas'] as $key => $var) {
                        $content.= '<label class="label-radio"><input type="radio" name="' . $idf . '" value="' . htmlentities($key) . '"  ' . checked($key, $value, 0) . '/> ' . htmlentities($var) . '</label>';
                    }
                break;
                case 'textarea':
                    $content.= '<textarea ' . $idname . ' rows="5" cols="30">' . $value . '</textarea>';
                break;

                    /* Multiple cases */
                case 'color':
                case 'date':
                case 'email':
                case 'number':
                case 'url':
                    $content.= '<input type="' . $field['type'] . '" ' . $idname . ' value="' . $value . '" />';
                break;
                default:
                    $content.= '<input type="text" ' . $idname . ' value="' . $value . '" />';
            }
            $content.= '</td>';
            $content.= '</tr>';
        }
        return $content;
    }

    /**
     * Getting all datas for a field, with default values for undefined params
     *
     * @param int     $id
     * @param unknown $field
     * @return unknown
     */
    private function get_field_datas($id, $field) {

        $default_values = array(
            'box' => 'default',
            'label' => $id,
            'type' => 'text',
            'test' => '',
            'datas' => array(
                __('No', 'wpuoptions') ,
                __('Yes', 'wpuoptions')
            )
        );
        foreach ($default_values as $name => $value) {
            if (empty($field[$name]) || !isset($field[$name])) {
                $field[$name] = $value;
            }
        }

        return $field;
    }

    /**
     * Generate export URL
     *
     * @return unknown
     */
    private function generate_export_url() {
        $languages = $this->get_languages();

        $site_url = str_replace(array(
            'http://',
            'https://'
        ) , '', site_url());
        $sanitized_site_url = sanitize_title_with_dashes($site_url);
        $filename = 'export-' . date_i18n('Y-m-d-his') . '-' . $sanitized_site_url . '.json';

        $options = array();

        // Array of fields:values
        foreach ($this->fields as $id => $field) {
            $opt_field = $this->get_field_datas($id, $field);

            // If this field has i18n
            if (isset($opt_field['lang']) && !empty($languages)) {
                foreach ($languages as $lang => $name) {
                    $options[$lang . '___' . $id] = get_option($lang . '___' . $id);
                }
            }
            $options[$id] = get_option($id);
        }
        $base64 = 'data:application/json;base64,' . base64_encode(json_encode($options));

        return '<a class="button button-primary" href="' . $base64 . '" download="' . $filename . '">' . __('Export options', 'wpuoptions') . '</a>';
    }

    /**
     * Import json into options
     *
     * @param string  $json
     * @return unknown
     */
    private function import_options($json) {
        $return = false;
        $options = json_decode($json);
        if (is_object($options)) {
            foreach ($options as $id => $value) {
                update_option($id, $value);
            }
            $return = true;
        }
        return $return;
    }

    /**
     * Validate a field value
     *
     * @param string  $field
     * @param unknown $value
     * @return boolean
     */
    private function test_field_value($field, $value) {
        $return = true;
        switch ($field['test']) {
            case 'email':
                if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    $return = false;
                }
            break;
            case 'category':
            case 'page':
                if (!ctype_digit($value)) {
                    $return = false;
                }
            break;
            case 'radio':
            case 'select':
                if (!array_key_exists($value, $field['datas'])) {
                    $return = false;
                }
            break;
            case 'url':
                if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                    $return = false;
                }
            break;
            default:
        }
        return $return;
}

/**
 * Optain an admin field id
 *
 * @param string  $id
 * @return string
 */
private function get_field_id($id) {
    return 'wpu_admin_id_' . $id;
}

/**
 * Obtain a list of languages
 *
 * @return array
 */
private function get_languages() {
    global $q_config, $polylang;
    $languages = array();

    // Obtaining from Qtranslate
    if (isset($q_config['enabled_languages'])) {
        foreach ($q_config['enabled_languages'] as $lang) {
            if (!in_array($lang, $languages) && isset($q_config['language_name'][$lang])) {
                $languages[$lang] = $q_config['language_name'][$lang];
            }
        }
    }

    // Obtaining from Polylang
    if (function_exists('pll_the_languages') && is_object($polylang)) {
        $poly_langs = $polylang->model->get_languages_list();
        foreach ($poly_langs as $lang) {
            $languages[$lang->slug] = $lang->name;
        }
    }
    return $languages;
}
}

$WPUOptions = new WPUOptions();

/* ----------------------------------------------------------
  Utilities
---------------------------------------------------------- */

/**
 * Get an option value with l18n
 *
 * @param string  $name
 * @return string
 */
function wputh_l18n_get_option($name, $lang = false) {
    global $q_config;

    $option = get_option($name);

    /* Define lang */

    if ($lang === false) {
        if (isset($q_config['language'])) {
            $lang = $q_config['language'];
        }
    }

    /* Get meta value */

    if (isset($q_config['language'])) {
        $option_l18n = get_option($lang . '___' . $name);
        if (!empty($option_l18n)) {
            $option = $option_l18n;
        }
    }

    /* Use default language value */
    $default_language = '';
    if (isset($q_config['language'])) {
        $default_language = $q_config['enabled_languages'][0];
    }
    $default_language = apply_filters('wputh_l18n_get_option__defaultlang', $default_language);

    $use_default = apply_filters('wputh_l18n_get_option__usedefaultlang', true);
    if (empty($option) && $use_default && $lang != $default_language) {
        return wputh_l18n_get_option($name, $default_language);
    }

    return $option;
}

/**
 * Get media details
 *
 * @param string  $option_name
 * @param string  $size
 * @return string
 */
function wpu_options_get_media($option_name, $size = 'thumbnail') {
    $default_attachment_details = array(
        'title' => '',
        'caption' => '',
        'alt' => '',
        'description' => '',
        'href' => '',
        'src' => '',
        'width' => 0,
        'height' => 0
    );

    $attachment_details = $default_attachment_details;

    $attachment_id = get_option($option_name);
    $attachment = get_post($attachment_id);

    if (isset($attachment->post_title)) {
        $attachment_details['title'] = trim($attachment->post_title);
        $attachment_details['caption'] = trim($attachment->post_excerpt);
        $attachment_details['description'] = $attachment->post_content;
        $attachment_details['href'] = get_permalink($attachment->ID);
        $attachment_details['src'] = $attachment->guid;
    }

    $image = wp_get_attachment_image_src($attachment_id, $size);
    if (isset($image[0])) {
        $attachment_details['src'] = $image[0];
        $attachment_details['width'] = $image[1];
        $attachment_details['height'] = $image[2];
    }
    else {
        $attachment_details = $default_attachment_details;
        $attachment_details['src'] = get_stylesheet_directory_uri() . '/images/options/' . $option_name . '.jpg';
    }

    return $attachment_details;
}
