<?php

/**
 * =========================================
 * Plugin Name: CSF - Custom Search Filter library
 * Description: A plugin for search filter to generate form and query the form, used full for developer. 
 * Version: 1.3
 * =======================================
 */

namespace csf_search_filter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class CSF_Enqueue
{
    // 
    public static function csf_search_js(
        $form_id = ['csf-filter-form'],
        $filter_name = '',
        $dynamic_filter_item = false,
        $update_url = false
    ) {
        $js_file_path_url = csf_path_url . 'js/csf-search-filter.js';
        wp_enqueue_script(
            'csf-filter',
            $js_file_path_url,
            array('jquery'),
            filemtime(get_stylesheet_directory($js_file_path_url)),
            array(
                'in_footer' => true,
                'strategy' => 'defer'
            )
        );
        wp_localize_script('csf-filter', 'csf_obj', [
            'form_ids' => wp_json_encode($form_id),
            'filter_name' => $filter_name,
            'dynamic_filter_item' => $dynamic_filter_item,
            'update_url' => $update_url
        ]);
    }

    // 
    public static function csf_admin_setting_js()
    {
        // ace-editor
        wp_enqueue_script(
            'ace-editor-csf',
            'https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ace.js',
            array('jquery'),
            '1.0',
            array(
                'in_footer' => true,
                'strategy' => 'defer'
            )
        );
        wp_enqueue_script(
            'ace-ext-beautify-csf',
            'https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ext-beautify.js',
            array('jquery'),
            '1.0',
            array(
                'in_footer' => true,
                'strategy' => 'defer'
            )
        );
        // csf js

        $js_file_path_url = csf_path_url . 'js/csf_admin_settings.js';
        wp_enqueue_script(
            'csf_admin_settings',
            $js_file_path_url,
            array('jquery'),
            filemtime(get_stylesheet_directory($js_file_path_url)),
            array(
                'in_footer' => true,
                'strategy' => 'defer'
            )
        );


        $default_search_fields = \csf_search_filter\CSF_Fields::set_search_fields();
        wp_localize_script('csf_admin_settings', 'csf_obj', array(
            'default_search_fields' => ($default_search_fields) ? wp_json_encode($default_search_fields) : '',
        ));
    }
}
