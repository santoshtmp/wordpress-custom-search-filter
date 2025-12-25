<?php


/**
 * =========================================
 * Plugin Name: CSF - Custom Search Filter library
 * Description: A plugin for search filter to generate form and query the form, used full for developer. 
 * Version: 1.3
 * =======================================
 * echo do_shortcode('[csf_searchfilter filter_name = "filter_name" post_type = "post_type" ]');
 */

namespace csf_search_filter;

use WP_Query;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class CSF_shortcode
{
    public function __construct()
    {
        add_shortcode('csf_searchfilter', [$this, 'display_shortcode_csf_searchfilter']);
        add_shortcode('csf_get_result_info', [$this, 'display_shortcode_csf_get_result_info']);
    }

    // 
    public function display_shortcode_csf_searchfilter($atts)
    {
        // Define default attributes
        $atts = shortcode_atts(
            array(
                'filter_name' => "",
                'form_id' => "",
                'form_class' => "",
                'post_type' => "",
                'data_url' => "",
                'result_show' => "false",
            ),
            $atts
        );
        $filter_name = $atts['filter_name'];
        $form_id = $atts['form_id'];
        $form_class = $atts['form_class'];
        $post_type = $atts['post_type'];
        $data_url = $atts['data_url'];
        $result_show = $atts['result_show'];
        // 
        if (!$filter_name) {
            return "csf_searchfilter filter_name is required";
        }
        if ($filter_name = 'default' && $post_type = 'default') {
            $filter_name = $post_type = get_post_type();
        }
        if (is_search()) {
            $filter_name = $post_type = 'search_page';
            $data_url = home_url('/');
        }
        // 
        $search_form = [
            "filter_name" => $filter_name,
            'form_id' => $form_id,
            'form_class' => $form_class,
            'post_type' => $post_type,
            'data_url' => $data_url,
        ];

        // 
        if ($result_show === 'true') {
            $search_fields = \csf_search_filter\CSF_Fields::set_search_fields();
            $fields_settings = (isset($search_fields[$filter_name])) ? $search_fields[$filter_name] : '';
            $this->display_shortcode_csf_searchfilter_result($filter_name, $post_type, $fields_settings);
            return '';
        }
        ob_start();
        \csf_search_filter\CSF_Form::the_search_filter_form($search_form);
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    // 
    public function display_shortcode_csf_searchfilter_result($filter_name, $post_type, $fields_settings)
    {
        global  $csf_query;

        $result_filter_area = (isset($fields_settings['result_filter_area'])) ? $fields_settings['result_filter_area'] : '';
        $filter_name_short = str_replace([' '], '-', strtolower($filter_name));
        $result_area_id = "csf-result-area-";
        if ($result_filter_area) {
            $result_area_id .= str_replace([' '], '-', strtolower(trim($result_filter_area)));
        } else {
            $result_area_id .= $filter_name_short;
        }

        if ($filter_name == get_post_type()) {
            global $wp_query;
            if (! isset($wp_query)) {
                return false;
            }
            $csf_query = $wp_query;
        } else {
            $csf_query = new \csf_search_filter\CSF_Query();
            $query_args = new CSF_Args();
            $csf_query->csf_query($fields_settings, $query_args, true);
            $csf_query = new WP_Query($query_args->getAll());
        }
        echo '<div id="' . $result_area_id . '" class="csf-search-result" data-region="csf-search-filter-result">  shortcode="csf_searchfilter_result_show" ';
        $hook_action_name = 'search_filter_result_' . str_replace([' ', '-'], '_', strtolower($filter_name)) . '_' . $post_type;
        if (has_filter($hook_action_name)) {
            apply_filters($hook_action_name, $csf_query);
        } else {
            $template_path = csf_dir . 'includes/csf-result-loop-template.php';
            if ($template_path) {
                $template_path = ltrim($template_path, '/');
                if (file_exists($template_path)) {
                    include $template_path;
                } else {
                    // $load_template = get_template_part($template_path);
                    echo "invalid search filter result template.";
                }
            }
        }
        echo "</div> ";
        wp_reset_postdata();
    }

    // 
    public function display_shortcode_csf_get_result_info($atts)
    {
        global $csf_result_info;

        // Define default attributes
        $atts = shortcode_atts(
            array(
                'filter_text' => true,
                'filter_other' => false
            ),
            $atts
        );
        $csf_result_info['filter_text'] = $atts['filter_text'];
        $csf_result_info['filter_other'] = $atts['filter_other'];

        echo \csf_search_filter\CSF_Form::csf_get_result_info($csf_result_info);
    }

    // class end
}
