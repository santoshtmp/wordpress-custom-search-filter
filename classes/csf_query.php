<?php

/**
 * =========================================
 * Plugin Name: CSF - Custom Search Filter library
 * Description: A plugin for search filter to generate form and query the form, used full for developer. 
 * Version: 1.3
 * =======================================
 */

/**
 * Reference : 
 * https://developer.wordpress.org/reference/hooks/pre_get_posts/
 * https://developer.wordpress.org/reference/hooks/posts_join/
 * https://developer.wordpress.org/reference/hooks/posts_where/
 * https://developer.wordpress.org/reference/hooks/posts_groupby/
 * https://developer.wordpress.org/reference/hooks/posts_orderby/
 * 
 * https://www.lab21.gr/blog/extend-the-where-clause-in-wordpress-wp_query/ 
 */


namespace csf_search_filter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

//class to hande SF Query
class CSF_Query
{

    public function __construct()
    {
        add_action('pre_get_posts', [$this, 'csf_search_filter_query'], 101);
        add_filter('posts_where', [$this, 'where_post_case'], 10, 2);
        add_filter('posts_join', [$this, 'join_post_case']);
        add_filter('posts_groupby', [$this, 'groupby_post_case']);
        add_filter('posts_orderby', [$this, 'edit_posts_orderby']);
    }

    /**
     * 
     */
    public function csf_search_filter_query($query)
    {
        if (!is_admin() && $query->is_main_query()) {

            // $posts_page_id = get_option('page_for_posts');
            $default_post_page = (is_home() && ! is_front_page());
            if (is_archive() || is_tax() || $default_post_page) {
                $queried_object = get_queried_object();
                // archive post list page
                $query_post_type =  isset($query->query_vars['post_type']) ? $query->query_vars['post_type'] : '';
                $query_post_type = ($default_post_page) ? 'post' : $query_post_type;
                // isset($queried_object->name) ? $queried_object->name : '';
                // taxo list  page
                $query_taxonomy = isset($queried_object->taxonomy) ? $queried_object->taxonomy : ''; // isset($query->tax_query->queries[0]['taxonomy']) ? $query->tax_query->queries[0]['taxonomy'] : '';
                if (
                    ($query_post_type && is_post_type_archive($query_post_type)) ||
                    ($query_taxonomy  && $query->is_tax($query_taxonomy)) ||
                    $default_post_page
                ) {
                    $search_fields = \csf_search_filter\CSF_Fields::set_search_fields();
                    $setting_key = [];
                    foreach ($search_fields as $key => $settings) {
                        // 
                        if (isset($settings['is_main_query'])) {
                            if (!$settings['is_main_query'] || $settings['is_main_query'] != "true") {
                                continue;
                            }
                        } else {
                            continue;
                        }
                        // 
                        if (isset($settings['post_type']) && $query_post_type) {
                            if ($settings['post_type'] == $query_post_type) {
                                $setting_key[] = $key;
                                // break;
                            }
                        }
                        // 
                        if (isset($settings['taxonomies']) && $query_taxonomy) {
                            $taxonomies = explode(',', $settings['taxonomies']);
                            foreach ($taxonomies as $taxonomies_key => $value) {
                                if ($value == $query_taxonomy) {
                                    $setting_key[] = $key;
                                    // break;
                                }
                            }
                        }
                    }
                    // 
                    foreach ($setting_key as $key => $csf_uniqu_key) {
                        $fields_settings = (isset($search_fields[$csf_uniqu_key])) ? $search_fields[$csf_uniqu_key] : '';
                        if ($fields_settings && $csf_uniqu_key) {
                            $this->csf_query($fields_settings, $query);
                        }
                    }

                    return true;
                }
            }
        }
    }


    /**
     * Reference : 
     * https://developer.wordpress.org/reference/hooks/pre_get_posts/
     * https://developer.wordpress.org/reference/hooks/posts_join/
     * https://developer.wordpress.org/reference/hooks/posts_where/
     * https://developer.wordpress.org/reference/hooks/posts_groupby/
     * https://developer.wordpress.org/reference/hooks/posts_orderby/
     * 
     * https://www.lab21.gr/blog/extend-the-where-clause-in-wordpress-wp_query/ 
     */
    public function csf_query($fields_settings, $query, $set_post_type = false)
    {
        // // Verify _csf_nonce
        // if (!isset($_GET['_csf_nonce']) || !wp_verify_nonce($_GET['_csf_nonce'], 'csf_nonce')) {
        //     return '';
        // }
        global $csf_result_info;
        $_GET_search_text = '';
        $tax_query =  [];
        $meta_query = [];
        $sort_order_by = [];

        // 
        $query->set('post_status', ['publish']);
        // post per page 
        $posts_per_page = (isset($fields_settings['posts_per_page'])) ? $fields_settings['posts_per_page'] : '';
        if ($posts_per_page) {
            $query->set('posts_per_page', $posts_per_page);
        }
        // 
        $post_type = (isset($fields_settings['post_type'])) ? $fields_settings['post_type'] : 'page';
        if ($set_post_type) {
            $query->set('post_type', $post_type);
        }
        // 
        $default_asc_desc_sort_by = (isset($fields_settings['default_asc_desc_sort_by'])) ? $fields_settings['default_asc_desc_sort_by'] : [];
        // 
        $field_relation = (isset($fields_settings['field_relation'])) ? $fields_settings['field_relation'] : 'AND';
        // search fields
        $fields = isset($fields_settings['fields']) ? $fields_settings['fields'] : [];
        // 
        if ($fields) {
            foreach ($fields as $key =>  $field) {
                // search_field_type => 'dropdown' or 'checkbox' or 'search_text'
                $search_field_type = (isset($field['search_field_type'])) ? $field['search_field_type'] : 'dropdown';
                if ($search_field_type === 'search_text') {
                    $_GET_search_text = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
                    if ($_GET_search_text) {
                        $csf_result_info['search_text'] = $_GET_search_text;
                        // other free_search
                        $free_search = (isset($fields_settings['free_search'])) ? $fields_settings['free_search'] : false;
                        if ($free_search && ($field_relation == "OR")) {
                            // Also perform free text search in below defined tax key
                            if ($_GET_search_text) {
                                $post_taxonomies = $fields_settings['free_search']['post_taxonomies'];
                                foreach ($post_taxonomies as $post_taxonomy) {
                                    $term_ids = [];
                                    $terms =  $this->get_taxonomy_with_name_like($post_taxonomy, $_GET_search_text);
                                    if ($terms) {
                                        foreach ($terms as $key => $value) {
                                            if (!in_array($value->term_id, $term_ids)) {
                                                $term_ids[] = $value->term_id;
                                            }
                                        }
                                    }
                                    if ($term_ids) {
                                        $term_ids = implode(',', $term_ids);
                                        $tax_query[] = array(
                                            'taxonomy' => $post_taxonomy,
                                            'field' => 'id',
                                            'terms' => $term_ids,
                                        );
                                    }
                                }
                            }
                            // Also perform free text search in below defined meta key value
                            $meta_keys = $fields_settings['free_search']['meta_keys'];
                            foreach ($meta_keys as $meta_key) {
                                $meta_query[] = array(
                                    'key' => $meta_key,
                                    'value' => $_GET_search_text,
                                    'compare' => 'LIKE',
                                );
                            }
                        }
                    }
                    continue;
                }
                // filter_term_type => 'taxonomy' or 'metadata'
                $filter_term_type = (isset($field['filter_term_type'])) ? $field['filter_term_type'] : '';
                if (! $filter_term_type) {
                    echo "search filter field term_type is not set/defind";
                    continue;
                }

                // field display name
                $filter_title = (isset($field['display_name'])) ? $field['display_name'] : '';
                if (!$filter_title) {
                    break;
                    // if there is no name then go to next field
                }

                // get name and its value
                $field_name = \csf_search_filter\CSF_Form::get_search_field_name($filter_title);
                $_GET_field_name_val = isset($_GET[$field_name]) ? $_GET[$field_name] : '';
                if ($_GET_field_name_val) {
                    $csf_result_info['other_filter'][$filter_title] = $_GET_field_name_val;
                }
                $meta_query_compare = 'LIKE';
                $meta_query_type = '';

                // filter_term_key => 'taxonomy_key' or 'metadata_key'; [if meta_key is in array: example metakey_{array}_metakey]
                $all_filter_term_key = (isset($field['filter_term_key'])) ? $field['filter_term_key'] : '';
                if (! $all_filter_term_key) {
                    echo "search filter field term_key is not set/defind";
                    continue;
                }
                // metadata_reference => 'taxonomy,taxonomy_key' or 'post'; only apply to filter_term_key metadata_key
                $all_metadata_reference = (isset($field['metadata_reference'])) ? $field['metadata_reference'] : '';
                // 
                $all_filter_term_key =  explode("|", $all_filter_term_key);
                $all_metadata_reference =  explode("|", $all_metadata_reference);
                foreach ($all_filter_term_key as $key => $filter_term_key) {

                    $metadata_reference = isset($all_metadata_reference[$key]) ? $all_metadata_reference[$key] : '';
                    if ($metadata_reference) {
                        $metadata_reference = explode(',', $metadata_reference);
                        // for metadata ref taxonomy
                        if ($metadata_reference[0] == 'taxonomy' && isset($metadata_reference[1])) {
                            // check if the taxonomy is associalted with current post type
                            $taxonomy = $metadata_reference[1];
                            $taxonomies = get_object_taxonomies($post_type);
                            if (in_array($taxonomy, $taxonomies)) {
                                $filter_term_type = 'taxonomy';
                                $filter_term_key = $taxonomy;
                            } else {
                                // else if third paramater is defined as 'slug' then escape; which don't need term_id because it will perform meta query on accepted value
                                $query_by_slug = isset($metadata_reference[2]) ? $metadata_reference[2] : '';
                                if ($query_by_slug != 'slug') {
                                    // else find the taxonomy and get term id according to search_field_type
                                    if ($search_field_type == 'checkbox') {
                                        if ($_GET_field_name_val && is_array($_GET_field_name_val)) {
                                            $_GET_field_name_val_temp = [];
                                            foreach ($_GET_field_name_val as $key => $_GET_term_slug) {
                                                $current_term = get_term_by('slug', $_GET_term_slug, $taxonomy);
                                                if ($current_term) {
                                                    $_GET_field_name_val_temp[] = $current_term->term_id;
                                                }
                                            }
                                            $_GET_field_name_val = $_GET_field_name_val_temp;
                                            unset($_GET_field_name_val_temp);
                                        }
                                    }
                                    if ($search_field_type == 'dropdown' || $search_field_type == 'radio') {
                                        if ($search_field_type == 'radio') {
                                            $radio_always_active = (isset($field['radio_always_active'])) ? $field['radio_always_active'] : false;
                                            if ($radio_always_active && ! $_GET_field_name_val) {
                                                $dynamic_filter_item = (isset($field['dynamic_filter_item'])) ? $field['dynamic_filter_item'] : false;
                                                $radio_metadata_reference = (isset($field['metadata_reference'])) ? $field['metadata_reference'] : '';
                                                $item_orderby = (isset($field['item_orderby'])) ? $field['item_orderby'] : '';
                                                $filter_items = \csf_search_filter\CSF_Data::get_csf_metadata_items($post_type, $filter_term_key, $radio_metadata_reference, $dynamic_filter_item, $all_post_ids = [], $item_orderby);
                                                foreach ($filter_items  as $key => $value) {
                                                    $_GET_field_name_val =  $value['slug'];
                                                    break;
                                                }
                                            }
                                        }

                                        $current_term = get_term_by('slug', $_GET_field_name_val, $taxonomy);
                                        if ($current_term) {
                                            $_GET_field_name_val = $current_term->term_id;
                                        }
                                    }
                                }
                            }
                        }
                        // for metadata ref past upcoming date compare
                        if ($metadata_reference[0] == 'past_upcoming_date_compare') {
                            $meta_query_type = 'DATE';
                            if (is_array($_GET_field_name_val)) {
                                $meta_query_compare = [];
                                foreach ($_GET_field_name_val as $key => $value) {
                                    if ($value == 'past') {
                                        $meta_query_compare[$key] = '<';
                                    }
                                    if ($value == 'upcoming') {
                                        $meta_query_compare[$key] = '>=';
                                    }
                                }
                            } else {
                                if ($_GET_field_name_val == 'past') {
                                    $meta_query_compare = '<';
                                }
                                if ($_GET_field_name_val == 'upcoming') {
                                    $meta_query_compare = '>=';
                                }
                            }
                        }
                        // for metadata reference asc desc sort by
                        if ($metadata_reference[0] == 'asc_desc_sort_by') {
                            $sort_order_by['orderby'] = $filter_term_key;
                            if (isset($metadata_reference[1])) {
                                $sort_order_by['meta_key'] = $metadata_reference[1];
                            }
                        }
                    }
                    // taxonomy field data filter_term_type
                    if ($filter_term_type == 'taxonomy') {
                        if ($search_field_type == 'checkbox') {
                            if ($_GET_field_name_val && is_array($_GET_field_name_val)) {
                                foreach ($_GET_field_name_val as $key => $_GET_term_slug) {
                                    $tax_query[] = self::tax_filter_query($filter_term_key, $_GET_term_slug);
                                }
                            }
                        }
                        if ($search_field_type == 'dropdown') {
                            if ($_GET_field_name_val) {
                                $tax_query[] = self::tax_filter_query($filter_term_key, $_GET_field_name_val);
                            }
                        }
                    }
                    // metadata field data filter_term_type
                    if ($filter_term_type === 'metadata') {
                        if ($search_field_type == 'checkbox') {
                            if ($_GET_field_name_val && is_array($_GET_field_name_val)) {
                                foreach ($_GET_field_name_val as $key => $_GET_meta_value) {
                                    if ($_GET_meta_value == "ASC" || $_GET_meta_value == "DESC") {
                                        $sort_order_by['order'] = $_GET_meta_value;
                                    } else {
                                        $query_compare = is_array($meta_query_compare) ? $meta_query_compare[$key] : $meta_query_compare;
                                        $meta_query[] = self::meta_filter_query($filter_term_key,  $_GET_meta_value, $query_compare, $meta_query_type);
                                    }
                                }
                            }
                        }
                        if ($search_field_type == 'dropdown' || $search_field_type == 'radio') {
                            if ($_GET_field_name_val) {
                                if ($_GET_field_name_val == "ASC" || $_GET_field_name_val == "DESC") {
                                    $sort_order_by['order'] = $_GET_field_name_val;
                                } else {
                                    $meta_query[] = self::meta_filter_query($filter_term_key, $_GET_field_name_val, $meta_query_compare, $meta_query_type);
                                }
                            }
                        }
                    }
                }
            }
        }
        // filter query
        if ($field_relation == "AND") {
            if ($_GET_search_text) {
                $query->set('s', $_GET_search_text);
            }
            if ($tax_query) {
                $tax_query['relation'] = $field_relation;
                $query->set('tax_query', $tax_query);
            }
            if ($meta_query) {
                $meta_query['relation'] = $field_relation;
                $query->set('meta_query', $meta_query);
            }
        } else {
            // set csf custom_search_post
            if ($_GET_search_text) {
                $custom_search_post['search'] = $_GET_search_text;
            }
            if ($tax_query) {
                $custom_search_post['tax_query'] = $tax_query;
            }
            if ($meta_query) {
                $custom_search_post['meta_query'] = $meta_query;
            }
            $custom_search_post['relation'] =  $field_relation;
            $custom_search_post['sort_order_by'] =  $sort_order_by;
            $query->set('csf_posts', $custom_search_post);
        }
        // filter sort by
        if (isset($sort_order_by['order'])) {
            $query->set('order', $sort_order_by['order']);
            $query->set('orderby', $sort_order_by['orderby']);
            if (isset($sort_order_by['meta_key'])) {
                $query->set('meta_key', $sort_order_by['meta_key']);
            }
        } else {
            // default filter sort by
            if (
                isset($default_asc_desc_sort_by['order']) &&
                isset($default_asc_desc_sort_by['orderby'])
            ) {
                $query->set('order', $default_asc_desc_sort_by['order']);
                $query->set('orderby', $default_asc_desc_sort_by['orderby']);
                if (isset($default_asc_desc_sort_by['meta_key'])) {
                    $query->set('meta_key', $default_asc_desc_sort_by['meta_key']);
                }
            } else {
                $query->set('orderby', "menu_order");
            }
        }

        return;
    }

    // 
    public static function tax_filter_query($taxonomy, $terms_slug)
    {
        return [
            'taxonomy' => $taxonomy,
            'field' => 'slug',
            'terms' => $terms_slug,
        ];
    }


    // 
    public static function meta_filter_query($meta_key, $meta_value, $compare = 'LIKE', $type = '')
    {
        $meta_query =  [
            'key' => $meta_key,
            'value' => $meta_value,
            'compare' => $compare,
        ];
        if ($type == 'DATE') {
            $meta_query['value'] = date('Y-m-d');
            $meta_query['type'] = $type;
        }
        return $meta_query;
    }

    /**
     * custom_search_post for where case
     */
    // add_filter('posts_where', 'where_post_case', 10, 2);
    function where_post_case($where, $wp_query)
    {
        // global $wp_query; // var_dump($wp_query->request);
        global $wpdb;
        if ($custom_search_post = $wp_query->get('csf_posts')) {
            $custom_where = [];
            $relation = (isset($custom_search_post['relation'])) ? $custom_search_post['relation'] : 'OR';
            $search = (isset($custom_search_post['search'])) ? $custom_search_post['search'] : '';
            $meta_query = (isset($custom_search_post['meta_query'])) ? $custom_search_post['meta_query'] : '';
            $tax_query = (isset($custom_search_post['tax_query'])) ? $custom_search_post['tax_query'] : '';
            if ($search) {
                $custom_where[] =  ' ( ' . $wpdb->posts . '.post_title LIKE \'%' . $wpdb->esc_like($search) . '%\' OR ' . $wpdb->posts . '.post_excerpt LIKE \'%' . $wpdb->esc_like($search) . '%\' OR ' . $wpdb->posts . '.post_content LIKE \'%' . $wpdb->esc_like($search) . '%\' ) ';
            }
            if ($meta_query && is_array($meta_query)) {
                foreach ($meta_query as $key => $query) {
                    if (!is_array($query)) {
                        continue;
                    }
                    $meta_key = $query['key'];
                    $meta_value = $query['value'];
                    $compare = $query['compare'];
                    $type = $query['type'];
                    if (is_array($meta_value) || is_array($compare)) {
                        continue;
                    }

                    if ($compare === 'LIKE') {
                        $meta_value_compare = $compare . " '%$meta_value%' ";
                    } else {
                        $meta_value_compare = $compare . " '$meta_value' ";
                    }

                    if ($type === 'DATE') {
                        $custom_where[] = " ($wpdb->postmeta.meta_key = '$meta_key' AND  CAST($wpdb->postmeta.meta_value AS DATE) $meta_value_compare) ";
                    } else {
                        $custom_where[] = " ($wpdb->postmeta.meta_key = '$meta_key' AND $wpdb->postmeta.meta_value $meta_value_compare) ";
                    }
                }
            }
            if ($tax_query && is_array($tax_query)) {
                foreach ($tax_query as $key => $query) {
                    if (!is_array($query)) {
                        continue;
                    }
                    $taxonomy = $query['taxonomy'];
                    $terms = $query['terms'];
                    $field = $query['field'];
                    if ($field === 'id') {
                        $custom_where[] = " $wpdb->term_relationships.term_taxonomy_id IN ($terms) ";
                    } else {
                        $current_term = get_term_by($field, $terms, $taxonomy);
                        if ($current_term) {
                            $current_term_id = $current_term->term_id;
                            $custom_where[] = " $wpdb->term_relationships.term_taxonomy_id IN ($current_term_id) ";
                        }
                    }
                }
            }

            if ($custom_where) {
                $where .= ' AND (' . implode($relation, $custom_where) . ') ';
            }
        }

        // https://www.advancedcustomfields.com/resources/query-posts-custom-fields/
        if (str_contains($where, "{array}")) {
            // Replace '=' before {array} with 'LIKE' dynamically
            $where = preg_replace("/= '(.*?)_{array}(.*?)'/", "LIKE '$1_%$2'", $where);
            // Replace {array} with '%'
            $where = str_replace('{array}', '%', $where);
        }

        return $where;
    }


    /**
     * custom_search_post for join case
     */
    // add_filter('posts_join', 'join_post_case');
    function join_post_case($join)
    {
        global $wp_query, $wpdb;

        if ($custom_search_post = $wp_query->get('csf_posts')) {
            $meta_query = (isset($custom_search_post['meta_query'])) ? $custom_search_post['meta_query'] : '';
            $tax_query = (isset($custom_search_post['tax_query'])) ? $custom_search_post['tax_query'] : [];
            if ($meta_query && is_array($meta_query)) {
                $join .= " LEFT JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id ";
            }
            if ($tax_query && is_array($tax_query)) {
                $join .= " LEFT JOIN $wpdb->term_relationships ON $wpdb->posts.ID = $wpdb->term_relationships.object_id ";
                // $relation = (isset($custom_search_post['relation'])) ? $custom_search_post['relation'] : 'OR';
                // if ($relation == 'AND' && count($tax_query) > 1) {
                //     $join .= " LEFT JOIN $wpdb->term_relationships AS tt ON $wpdb->posts.ID = $wpdb->term_relationships.object_id ";
                // }
            }
        }
        return $join;
    }


    /**
     * custom_search_post for groupby case
     */
    // add_filter('posts_groupby', 'groupby_post_case');
    function groupby_post_case($groupby)
    {
        global $wp_query, $wpdb;

        if ($custom_search_post = $wp_query->get('csf_posts')) {
            $meta_query = (isset($custom_search_post['meta_query'])) ? $custom_search_post['meta_query'] : '';
            $tax_query = (isset($custom_search_post['tax_query'])) ? $custom_search_post['tax_query'] : '';
            if (($meta_query && is_array($meta_query)) || ($tax_query && is_array($tax_query))) {
                $groupby .= " $wpdb->posts.ID ";
            }
        }
        return $groupby;
    }


    /**
     * custom_search_post for orderby case
     */
    // add_filter('posts_orderby', 'edit_posts_orderby');
    function edit_posts_orderby($posts_orderby)
    {
        global $wp_query, $wpdb;

        if ($custom_search_post = $wp_query->get('csf_posts')) {
            $search = (isset($custom_search_post['search'])) ? $custom_search_post['search'] : '';
            // $sort_order_by = (isset($custom_search_post['sort_order_by'])) ? $custom_search_post['sort_order_by'] : '';
            // if (isset($sort_order_by['order']) && isset($sort_order_by['orderby'])) {
            //     $posts_orderby .=  ', ' . $wpdb->posts . '.post_title ' . $sort_order_by['order'];
            // } else 
            if ($search) {
                $posts_orderby .=  ', ' . $wpdb->posts . '.post_title LIKE \'%' . $wpdb->esc_like($search) . '%\' DESC ';
            }
        }
        return $posts_orderby;
    }

    /**
     * @param string $taxonomy
     * @param string $search_text
     * @return array
     */
    function get_taxonomy_with_name_like($taxonomy, $search_text)
    {
        global $wpdb;
        $search_term = '%' . $wpdb->esc_like($search_text) . '%';
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.name, t.term_id, t.slug
                FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                WHERE tt.taxonomy = %s
                AND t.name LIKE %s",
                $taxonomy,
                $search_term
            )
        );
        return $results;
    }

    // SF_Query class end
}

class CSF_Args
{
    // Property to hold an array of data
    private $args = array();

    // Method to set a value in the array
    public function set($key, $value)
    {
        $this->args[$key] = $value;
    }

    // Method to get a value from the array
    public function get($key)
    {
        // Check if the key exists in the array
        if (isset($this->args[$key])) {
            return $this->args[$key];
        } else {
            return null; // Return null if key does not exist
        }
    }
    public function getAll()
    {
        return $this->args;
    }

    // Method to check if a key exists
    public function has($key)
    {
        return isset($this->args[$key]);
    }
}
