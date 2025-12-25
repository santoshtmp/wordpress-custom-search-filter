<?php

/**
 * =========================================
 * Plugin Name: CSF - Custom Search Filter library
 * Description: A plugin for search filter to generate form and query the form, used full for developer. 
 * Version: 1.3
 * =======================================
 */

namespace csf_search_filter;

use WP_Query;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// class to handle SF data
class CSF_Data
{

    /**
     * @param string $post_type
     * @param string  $meta_key => filter_term_key
     * @param string  $metadata_reference
     * @param bool  $dynamic_filter_item
     * @param array  $all_post_ids
     * @param string  $item_orderby
     * @return array $filter_items / $meta_info
     */
    public static function get_csf_metadata_items(
        $post_type,
        $meta_key,
        $metadata_reference = '',
        $dynamic_filter_item = false,
        $all_post_ids = [],
        $item_orderby = 'ASC'
    ) {
        if (!$post_type || !$meta_key) {
            return;
        }

        if (explode(",", $metadata_reference)[0] == 'asc_desc_sort_by') {
            return CSF_Data::get_asc_desc_sort_filter_items();
        }

        $seperate_meta_key =  explode("|", $meta_key);
        $seperate_metadata_reference =  explode("|", $metadata_reference);
        $meta_info = [];
        foreach ($seperate_meta_key as $key => $each_meta_key) {
            $each_metadata_reference = isset($seperate_metadata_reference[$key]) ? $seperate_metadata_reference[$key] : '';
            // 
            if ($all_post_ids && is_array($all_post_ids)) {
                foreach ($all_post_ids as $key => $post_id) {
                    $meta_info = self::check_post_meta_info($post_id,  $each_meta_key, $each_metadata_reference, $meta_info, false);
                }
            } else {
                $post_args = [
                    'post_type' => $post_type,
                    'posts_per_page' => -1,
                    'post_status' => array('publish'),
                ];
                if ($dynamic_filter_item) {
                    global $wp_query;
                    $wp_query_vars = $wp_query->query_vars;
                    $post_args['meta_query'] = isset($wp_query_vars['meta_query']) ? $wp_query_vars['meta_query'] : [];
                    $post_args['tax_query'] = isset($wp_query_vars['tax_query']) ? $wp_query_vars['tax_query'] : [];
                }
                // get all post from given post type and retrive given meta-key values
                // $posts = get_posts($post_args);
                $post_query = new WP_Query($post_args);
                if ($post_query->have_posts()) {
                    while ($post_query->have_posts()) {
                        $post_query->the_post();
                        $post_id = get_the_ID();
                        // foreach ($posts as $key => $post) {
                        // $post_id = $post->ID;
                        $meta_info = self::check_post_meta_info($post_id,  $each_meta_key, $each_metadata_reference, $meta_info, false);
                    }
                    wp_reset_postdata();
                }
            }
        }

        if ($item_orderby == 'ASC') {
            array_multisort(array_column($meta_info, 'name'), SORT_ASC, $meta_info);;
        } else if ($item_orderby == 'DESC') {
            array_multisort(array_column($meta_info, 'name'), SORT_DESC, $meta_info);
        } else {
            $meta_info = array_values($meta_info);
        }

        return $meta_info;
    }


    // 
    public static function check_post_meta_info($post_id, $meta_key, $metadata_reference, $meta_info = [], $return_field_term_id = false)
    {
        if (!$post_id || !$meta_key) {
            return;
        }
        $post_meta_value = true;
        $index = 0;

        while ($post_meta_value) {
            $meta_key_new = str_replace('{array}', $index, $meta_key);
            $meta_value = get_post_meta($post_id, $meta_key_new, true);
            if ($meta_value) {
                if (is_array($meta_value)) {
                    foreach ($meta_value as $key => $meta_val) {
                        $meta_info = self::check_add_meta_info($meta_info, $meta_val, $metadata_reference, $return_field_term_id);
                    }
                } else {
                    $meta_info = self::check_add_meta_info($meta_info, $meta_value, $metadata_reference, $return_field_term_id);
                }
            }
            $index = $index + 1;
            if (!str_contains($meta_key, '{array}') || ! $meta_value) {
                $post_meta_value = false;
            }
        }
        return $meta_info;
    }



    /**
     * @param array $meta_info
     * @param string $meta_val
     * @param string $metadata_reference
     * @return array
     */
    public static function check_add_meta_info($meta_info, $meta_val, $metadata_reference = '', $return_field_term_id = false)
    {
        $meta_val_parent =  $meta_val_term_id = '';
        $meta_val_slug = $meta_val_name = $meta_val;
        if ($metadata_reference) {
            $reference = self::check_metadata_reference($meta_val, $metadata_reference);
            if ($reference) {
                $meta_val_term_id = $reference['meta_val_term_id'];
                $meta_val_name = $reference['meta_val_name'];
                $meta_val_slug = $reference['meta_val_slug'];
                $meta_val_parent = $reference['meta_val_parent'];
            }
        }
        // 
        if ($return_field_term_id) {
            $meta_info[$meta_val] = $meta_val_term_id;
        } else {
            $metadata_reference = explode(',', $metadata_reference);
            if ($metadata_reference[0] == 'taxonomy' && !$meta_val_term_id) {
                return $meta_info;
            }
            if (isset($meta_info[$meta_val])) {
                if (array_key_exists($meta_val, $meta_info)) {
                    $meta_info[$meta_val]['count'] = $meta_info[$meta_val]['count'] + 1;
                }
            } else {
                $meta_info[$meta_val]['value'] = $meta_val;
                $meta_info[$meta_val]['slug'] = $meta_val_slug;
                $meta_info[$meta_val]['name'] = $meta_val_name;
                $meta_info[$meta_val]['term_id'] = $meta_val_term_id;
                $meta_info[$meta_val]['parent'] = $meta_val_parent;
                $meta_info[$meta_val]['count'] = 1;
            }
        }
        return $meta_info;
    }


    /**
     *  @return array [
     *    'meta_val_term_id' => $meta_val_term_id,
     *    'meta_val_name' => $meta_val_name,
     *    'meta_val_slug' => $meta_val_slug,
     *    'meta_val_parent' => $meta_val_parent,
     *  ];
     */
    public static function check_metadata_reference($meta_val, $metadata_reference)
    {
        $meta_val_parent =  $meta_val_term_id = $meta_val_name = $meta_val_slug = $meta_val;
        $metadata_reference = explode(',', $metadata_reference);
        if (isset($metadata_reference[0])) {
            if ($metadata_reference[0] == 'taxonomy') {
                $current_term = '';
                if (intval($meta_val)) {
                    $current_term = get_term($meta_val);
                } else {
                    if (isset($metadata_reference[1])) {
                        $current_term = get_term_by('slug', $meta_val, $metadata_reference[1]);
                    }
                }
                if ($current_term && isset($current_term->slug)) {
                    $meta_val_name = $current_term->name;
                    $meta_val_slug = $current_term->slug;
                    $meta_val_parent = $current_term->parent;
                    $meta_val_term_id = $current_term->term_id;
                } else {
                    return '';
                }
            } else if ($metadata_reference[0] == 'post') {
                $meta_val_name = get_the_title($meta_val);
            } else {
                if (function_exists($metadata_reference[0])) {
                    $data = $metadata_reference[0]();
                    $meta_val_name =  (isset($data[$meta_val])) ? $data[$meta_val] : $meta_val_name;
                }
            }
        }
        return [
            'meta_val_term_id' => $meta_val_term_id,
            'meta_val_name' => $meta_val_name,
            'meta_val_slug' => $meta_val_slug,
            'meta_val_parent' => $meta_val_parent,
        ];
    }

    /**
     * get count data for past_upcoming_date_compare_count metadata_reference
     */
    public static function get_past_upcoming_date_compare_count($post_type, $filter_items, $filter_term_key)
    {
        foreach ($filter_items as $key => $items) {
            $meta_query_compare = '';
            $posts_count = 0;
            if ($items['slug'] == 'past') {
                $meta_query_compare = '<';
            }
            if ($items['slug'] == 'upcoming') {
                $meta_query_compare = '>=';
            }
            if ($meta_query_compare) {
                $meta_query = CSF_Query::meta_filter_query($filter_term_key, $items['slug'], $meta_query_compare, 'DATE');
                $post_args = [
                    'post_type' => $post_type,
                    'posts_per_page' => -1,
                    'post_status' => array('publish'),
                    'meta_query' => [
                        $meta_query
                    ]
                ];
                $posts_count = 0;
                // $posts_count = count(get_posts($post_args));
                $post_query = new WP_Query($post_args);
                if ($post_query->have_posts()) {
                    $posts_count = $post_query->found_posts;
                    wp_reset_postdata();
                }
            }
            $filter_items[$key]['count'] = $posts_count;
        }
        return $filter_items;
    }

    /**
     *  asc desc sort filter_items 
     * */
    public static function get_asc_desc_sort_filter_items()
    {
        return [
            [
                'slug' => 'ASC',
                'name' => 'ASC'
            ],
            [
                'slug' => 'DESC',
                'name' => 'DESC'
            ],
        ];
    }

    // SF_Data class end

}
