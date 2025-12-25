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

/**
 * class to handle search_filter_form 
 */
class CSF_Form
{
    public static $form_ids = [];
    /**
     * search filter form output
     * @param array $search_form[]
     *              $search_form['form_class'] = "search-filter-form";
     *              $search_form['post_type'] = 'post_type';
     *              $search_form['data_url'] = 'action_url ;
     *              $search_form['filter_name'] = 'post_type;' default=post_type or as defined in \SF_Fields\set_search_fields $resource_filter_name var
     */
    public static function the_search_filter_form($search_form = [])
    {
        global $wp;
        // get search filter form args
        $filter_name = (isset($search_form['filter_name'])) ? $search_form['filter_name'] : '';
        $post_type = (isset($search_form['post_type'])) ? $search_form['post_type'] : '';
        $form_class = (isset($search_form['form_class'])) ? $search_form['form_class'] : "search-filter-form";
        $data_url = (isset($search_form['data_url'])) ? $search_form['data_url'] : '';
        $all_post_ids = (isset($search_form['all_post_ids'])) ? $search_form['all_post_ids'] : [];
        // 
        $data_url = ($data_url) ?: home_url(add_query_arg(array(), $wp->request));
        $data_url = ($data_url) ? $data_url : home_url($post_type);
        // 
        if (!$filter_name) {
            echo "filter name is required";
            return;
        }
        if (!$post_type) {
            echo "post type is required";
            return;
        }
        // get filter fields and settings
        $search_fields = \csf_search_filter\CSF_Fields::set_search_fields();
        $fields_settings = (isset($search_fields[$filter_name])) ? $search_fields[$filter_name] : '';
        if (!$fields_settings) {
            echo "<div style='display:none;'>search filter fields/settings are not set/defind</div>";
            return;
        }
        $search_filter_title = (isset($fields_settings['search_filter_title'])) ? $fields_settings['search_filter_title'] : '';
        $display_count = (isset($fields_settings['display_count'])) ? $fields_settings['display_count'] : 0;
        $display_count_selected = (isset($fields_settings['display_count_selected'])) ? $fields_settings['display_count_selected'] : 1;
        $result_filter_area = (isset($fields_settings['result_filter_area'])) ? $fields_settings['result_filter_area'] : '';
        $dynamic_filter_item = (isset($fields_settings['dynamic_filter_item'])) ? $fields_settings['dynamic_filter_item'] : false;
        $update_url = (isset($fields_settings['update_url'])) ? $fields_settings['update_url'] : false;
        $show_result_info = (isset($fields_settings['show_result_info'])) ? $fields_settings['show_result_info'] : 0;
        $fields_actions = isset($fields_settings['fields_actions']) ? $fields_settings['fields_actions'] : '';
        $fields = isset($fields_settings['fields']) ? $fields_settings['fields'] : [];
        $taxonomies = (isset($fields_settings['taxonomies'])) ? $fields_settings['taxonomies'] : '';
        // 
        if (is_tax()) {
            if (!$taxonomies) {
                echo "<div style='display:none;'>taxonomy not definied in search fields</div>";
                return;
            }
            $taxonomies = explode(',', $taxonomies);
            $queried_object = get_queried_object();
            if (!in_array($queried_object->taxonomy, $taxonomies)) {
                echo "<div style='display:none;'>current taxonomy not definied in search fields</div>";
                return;
            }
        }
        if (!is_archive()) {
            $default_post_page = (is_home() && !is_front_page());
            // $is_main_query = (isset($fields_settings['is_main_query'])) ? $fields_settings['is_main_query'] : true;
            $search_form_data_url = (isset($search_form['data_url'])) ? $search_form['data_url'] : '';
            if (!$search_form_data_url && !$default_post_page) {
                echo "<div style='display:none;'>don't have data_url</div>";
                return;
            }
        }
        // 
        $filter_name_short = str_replace([' '], '-', strtolower($filter_name));
        $form_id = 'csf-filter-' . $filter_name_short;
        $result_area_id = "csf-result-area-";
        if ($result_filter_area) {
            $result_area_id .= str_replace([' '], '-', strtolower(trim($result_filter_area)));
        } else {
            $result_area_id .= $filter_name_short;
        }
        self::$form_ids[] = $form_id; ?>
        <div class="search-form-wrapper <?php echo $filter_name_short; ?>">
            <?php if ($search_filter_title) { ?>
                <h2 class='search-filter-title font-semibold text-sm m-4 mt-0'>
                    <?php echo esc_attr($search_filter_title); ?>
                </h2>
            <?php } ?>
            <form id="<?php echo esc_attr($form_id); ?>" action="<?php echo esc_attr($data_url); ?>" method="GET"
                class='<?php echo esc_attr($form_class); ?>' data-url="<?php echo esc_attr($data_url); ?>" role="search"
                result-area-id="<?php echo esc_attr($result_area_id) ?>">
                <?php
                $hook_filter_name = 'search_filter_form_' . str_replace([' ', '-'], '_', strtolower($filter_name)) . '_' . $post_type;
                if (has_filter($hook_filter_name)) {
                    $args = [
                        'form_id' => $form_id,
                        'post_type' => $post_type,
                        'display_count' => $display_count,
                        'dynamic_filter_item' => $dynamic_filter_item,
                        'all_post_ids' => $all_post_ids,
                        'display_count_selected' => $display_count_selected,
                    ];
                    $value = apply_filters($hook_filter_name, $fields, $args);
                    $has_search_text_get_value = isset($value['has_search_text_get_value']) ? $value['has_search_text_get_value'] : '';
                    $has_drop_down_get_value = isset($value['has_drop_down_get_value']) ? $value['has_drop_down_get_value'] : '';
                    $has_checkbox_get_value = isset($value['has_checkbox_get_value']) ? $value['has_checkbox_get_value'] : '';
                    $has_radio_get_value = isset($value['has_radio_get_value']) ? $value['has_radio_get_value'] : '';
                } else {
                    // setup csf_nonce
                    // wp_nonce_field('csf_nonce', '_csf_nonce', true, true);
                    // check and display each search filter field
                    $has_search_text_get_value = $has_drop_down_get_value = $has_checkbox_get_value = $has_radio_get_value = '';
                    foreach ($fields as $key => $field) {
                        // 
                        $hidden_field = (isset($field['hidden_field'])) ? $field['hidden_field'] : false;

                        // search_field_type
                        $search_field_type = (isset($field['search_field_type'])) ? $field['search_field_type'] : 'dropdown';
                        if ($search_field_type === 'search_text') {
                            $has_search_text_get_value = self::search_text_field($field);
                            continue;
                        }
                        // filter_term_type
                        $filter_term_type = (isset($field['filter_term_type'])) ? $field['filter_term_type'] : '';
                        if (!$filter_term_type) {
                            echo "search filter field term_type is not set/defind";
                            continue;
                        }
                        $filter_title = (isset($field['display_name'])) ? $field['display_name'] : '';
                        if (!$filter_title) {
                            echo "filter display name is not set or defined.";
                            break;
                            // if there is no name then go to next field
                        }
                        $field_name = self::get_search_field_name($filter_title);
                        $metadata_reference = (isset($field['metadata_reference'])) ? $field['metadata_reference'] : '';
                        $filter_term_key = (isset($field['filter_term_key'])) ? $field['filter_term_key'] : '';
                        $filter_items = (isset($field['filter_items'])) ? $field['filter_items'] : '';
                        if (!$filter_items || !is_array($filter_items)) {
                            $filter_items = [];
                            // check filter_term_type for taxonomy and metadara
                            if (!$filter_term_key) {
                                echo "search filter field term_key is not set/defind";
                                continue;
                            }
                            if ($filter_term_type === 'taxonomy') {
                                $filter_items = get_terms(['taxonomy' => $filter_term_key, 'hide_empty' => true]);
                            }
                            if ($filter_term_type === 'metadata') {
                                $item_orderby = (isset($field['item_orderby'])) ? $field['item_orderby'] : 'ASC';
                                $filter_items = \csf_search_filter\CSF_Data::get_csf_metadata_items($post_type, $filter_term_key, $metadata_reference, $dynamic_filter_item, $all_post_ids, $item_orderby);
                            }
                        }
                        if ($metadata_reference == 'past_upcoming_date_compare' && $display_count) {
                            $filter_items = CSF_Data::get_past_upcoming_date_compare_count($post_type, $filter_items, $filter_term_key);
                        }

                        // check search_field_type
                        if ($search_field_type == 'dropdown') {
                            $drop_down_get_value = \csf_search_filter\CSF_Form::dropdown_field($filter_title, $field_name, $filter_items, $display_count, $hidden_field);
                            if (!$has_drop_down_get_value) {
                                $has_drop_down_get_value = $drop_down_get_value;
                            }
                        }
                        if ($search_field_type == 'checkbox') {
                            $checkbox_get_value = \csf_search_filter\CSF_Form::checkbox_field($form_id, $filter_title, $field_name, $filter_items, $display_count, $display_count_selected, $hidden_field);
                            if (!$has_checkbox_get_value) {
                                $has_checkbox_get_value = $checkbox_get_value;
                            }
                        }
                        if ($search_field_type == 'radio') {
                            $radio_always_active = (isset($field['radio_always_active'])) ? $field['radio_always_active'] : false;
                            $radio_get_value = \csf_search_filter\CSF_Form::radio_field($form_id, $filter_title, $field_name, $filter_items, $display_count, $radio_always_active, $hidden_field);
                            if (!$has_radio_get_value) {
                                $has_radio_get_value = $radio_get_value;
                            }
                        }
                        // 
                    }
                    // 
                }
                if ($fields_actions) {

                    $auto_submit = isset($fields_actions['auto_submit']) ? $fields_actions['auto_submit'] : true;
                    if ($auto_submit) {
                        \csf_search_filter\CSF_Enqueue::csf_search_js(self::$form_ids, $filter_name, $dynamic_filter_item, $update_url);
                    }
                    // 
                    $submit_btn_show = isset($fields_actions['submit_btn_show']) ? $fields_actions['submit_btn_show'] : false;
                    $reset_btn_show = isset($fields_actions['reset_btn_show']) ? $fields_actions['reset_btn_show'] : false;
                    $submit_display_name = isset($fields_actions['submit_display_name']) ? $fields_actions['submit_display_name'] : 'Search';
                    $reset_display_name = isset($fields_actions['reset_display_name']) ? $fields_actions['reset_display_name'] : 'Reset';

                    if ($submit_btn_show == 'true' || $reset_btn_show == 'true') {
                        echo '<div class="filter-action-btns">';
                        if ($submit_btn_show == 'true') {
                ?>
                            <div class="filter-submit-btn ">
                                <button type="submit"><?php echo $submit_display_name; ?></button>
                            </div>
                        <?php
                        }
                        if ($reset_btn_show == 'true') {
                            $reset_display = 'display: none;';
                            if (
                                $has_search_text_get_value ||
                                $has_drop_down_get_value ||
                                $has_checkbox_get_value ||
                                $has_radio_get_value
                            ) {
                                $reset_display = '';
                            }
                        ?>
                            <div class="filter-reset-btn ">
                                <a href="<?php echo esc_attr($data_url); ?>"
                                    style="<?php echo $reset_display; ?>"><?php echo $reset_display_name; ?></a>
                            </div>
                <?php
                        }
                        echo '</div>';
                    }
                }
                ?>
            </form>
            <?php
            global $csf_result_info;
            $csf_result_info['filter_text'] = false;
            $csf_result_info['filter_other'] = false;
            if ($show_result_info == '1') {
                $csf_result_info['filter_text'] = true;
                $csf_result_info['filter_other'] = false;
            } else if ($show_result_info == '2') {
                $csf_result_info['filter_text'] = true;
                $csf_result_info['filter_other'] = true;
            }

            echo self::csf_get_result_info($csf_result_info);
            ?>
        </div>
    <?php
    }

    // get input field name
    public static function get_search_field_name($display_name)
    {
        $field_name = "csf_" . str_replace([' ', '-'], '_', strtolower($display_name));
        return $field_name;
    }

    // form free search text area
    public static function search_text_field($field)
    {
        global $csf_result_info;
        $search_name = isset($field['search_name']) ? $field['search_name'] : 'search';
        $search = isset($_GET[$search_name]) ? $_GET[$search_name] : '';
        $csf_result_info['search_text'] = $search; ?>
        <div class="filter-block search-box">
            <div class="input-wrapper relative" filter-action='free-search'>
                <input type="text" id="search_input" class='search-input w-full' maxlength="100"
                    name="<?php echo $search_name; ?>" value="<?php echo esc_attr($search); ?>"
                    placeholder="<?php echo esc_attr($field['placeholder']); ?>">
                <span class='search-icon top-1/2 absolute right-4 '>

                    <?php
                    if (function_exists('useSVG')) {
                        echo useSVG('search-icon');
                    }
                    ?>
                </span>
            </div>
        </div>
    <?php
        return $search;
    }

    // form dropdown_field fild
    public static function dropdown_field($filter_title, $name, $filter_items, $show_count = 0, $hidden_field = false)
    {
        $display_none = ($hidden_field) ? "style='display:none;'" : "";
        $select_dropdown = isset($_GET[$name]) ? $_GET[$name] : '';
    ?>
        <div class="filter-block dropdown-field <?php echo ($select_dropdown) ? 'active' : ''; ?>" <?php echo $display_none; ?>>
            <select name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>"
                data-type="<?php echo esc_attr($name); ?>">
                <option value="">
                    <?php echo esc_attr($filter_title); ?>
                </option>
                <?php
                foreach ($filter_items as $each_key => $value) {
                    $value = (array) $value;
                    $search_drop_select = '';
                    if (isset($value['slug'])) {
                        $search_drop_select = ($select_dropdown == $value['slug']) ? 'selected' : '';
                    }
                    $unique_filter_item_name = $name . '-' . $value['slug']

                ?>
                    <option class="filter-item" value="<?php echo (isset($value['slug'])) ? $value['slug'] : ''; ?>"
                        item-type="<?php echo esc_attr($unique_filter_item_name); ?>"
                        option-id="<?php echo (isset($value['term_id'])) ? $value['term_id'] : ''; ?>"
                        parent-id="<?php echo (isset($value['parent'])) ? $value['parent'] : ''; ?>" <?php echo $search_drop_select; ?>>
                        <?php echo (isset($value['name'])) ? ucfirst($value['name']) : '';
                        if ($show_count) {
                            echo (isset($value['count'])) ? '(' . $value['count'] . ')' : '';
                        }
                        ?>
                    </option>
                <?php
                }
                ?>
            </select>
        </div>
    <?php
        return $select_dropdown;
    }

    // form checkbox_field field


    public static function checkbox_field($form_id, $filter_title, $field_name, $filter_items, $show_count = 0, $display_count_selected = 0, $hidden_field = false)
    {

        $active_filter = isset($_GET['active_filter']) ? $_GET['active_filter'] : '';
        $select_checkbox = isset($_GET[$field_name]) ? $_GET[$field_name] : [];
        $active_filter = ($active_filter == $field_name) ? 'active' : '';
        $filter_active_class = ($select_checkbox) ? 'active' : $active_filter;
        if (!$filter_items) {
            return $select_checkbox;
        }
        $display_none = ($hidden_field) ? "style='display:none;'" : "";

    ?>
        <div class="filter-block checkbox-field <?php echo esc_attr($filter_active_class); ?>" <?php echo $display_none; ?>>
            <div class="filter-title ">
                <div class='title flex gap-x-1.5 items-center'>
                    <?php
                    echo esc_attr($filter_title);
                    if ($select_checkbox && $display_count_selected) {
                    ?>
                        <span class="count-selected">
                            <?php echo esc_attr(count($select_checkbox)); ?>
                        </span>
                    <?php
                    } ?>
                </div>
            </div>
            <div class="filter-item-list-wrapper ">
                <div class="filter-item-list">
                    <?php
                    foreach ($filter_items as $each_key => $value) {
                        $value = (array) $value;
                        $checkbox_checked = '';
                        if (is_array($select_checkbox) && in_array($value['slug'], $select_checkbox)) {
                            $checkbox_checked = 'checked';
                        }
                        $unique_filter_item_name = $field_name . '-' . str_replace([' '], '-', $value['slug']);
                    ?>
                        <div class="filter-item filter-checkbox-wrapper"
                            item-type="<?php echo esc_attr($unique_filter_item_name); ?>">

                            <label class='filter-input-label gap-x-1.5'
                                for="<?php echo esc_attr($form_id . '-' . $unique_filter_item_name); ?>">

                                <div class='filter-input-wrapper custom-checkbox'>
                                    <input type="checkbox" name="<?php echo esc_attr($field_name) . '[]'; ?>"
                                        id="<?php echo esc_attr($form_id . '-' . $unique_filter_item_name); ?>"
                                        value="<?php echo $value['slug']; ?>" <?php echo $checkbox_checked; ?>>
                                    <span class='checkmark'></span>
                                </div>
                                <div class="label-wrapper">
                                    <span class="label-name"><?php echo ucfirst($value['name']); ?></span>
                                    <?php
                                    if ($show_count && isset($value['count'])) {
                                    ?>
                                        <span class="label-count">(<?php echo $value['count']; ?>)</span>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </label>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    <?php
        return $select_checkbox;
    }

    // form radio_field field
    public static function radio_field($form_id, $filter_title, $field_name, $filter_items, $show_count = 0, $radio_always_active = false, $hidden_field = false)
    {
        $active_filter = isset($_GET['active_filter']) ? $_GET['active_filter'] : '';
        $select_radio = isset($_GET[$field_name]) ? $_GET[$field_name] : '';
        $active_filter = ($active_filter == $field_name) ? 'active' : '';
        $filter_active_class = ($select_radio) ? 'active' : $active_filter;
        if (!$filter_items) {
            return $select_radio;
        }
        $display_none = ($hidden_field) ? "style='display:none;'" : "";


    ?>
        <div class="filter-block radio-field <?php echo esc_attr($filter_active_class); ?>" <?php echo $display_none; ?>>
            <div class="filter-title ">
                <div class='title flex gap-x-1.5 items-center'>
                    <?php echo esc_attr($filter_title); ?>
                </div>
            </div>
            <div class="filter-item-list-wrapper ">
                <div class="filter-item-list-wrapper">
                    <?php
                    foreach ($filter_items as $each_key => $value) {
                        $value = (array) $value;
                        $radio_checked = '';
                        if (isset($value['slug'])) {
                            $radio_checked = ($select_radio == $value['slug']) ? 'checked' : '';
                        }
                        if ($radio_always_active && $each_key === 0 && !$radio_checked) {
                            $radio_checked = 'checked';
                        }
                        $unique_filter_item_name = $field_name . '-' . $value['slug']
                    ?>
                        <div class="filter-item filter-radio-wrapper <?php echo $radio_checked; ?> "
                            item-type="<?php echo esc_attr($unique_filter_item_name); ?>">

                            <label class='filter-input-label gap-x-1.5'
                                for="<?php echo esc_attr($form_id . '-' . $unique_filter_item_name); ?>">

                                <div class='filter-input-wrapper custom-radio'>
                                    <input type="radio" name="<?php echo esc_attr($field_name); ?>"
                                        id="<?php echo esc_attr($form_id . '-' . $unique_filter_item_name); ?>"
                                        value="<?php echo $value['slug']; ?>" <?php echo $radio_checked; ?>>
                                    <span class='checkmark'></span>
                                </div>
                                <div class="label-wrapper">
                                    <span class="label-name"><?php echo ucfirst($value['name']); ?></span>
                                    <?php
                                    if ($show_count && isset($value['count'])) {
                                    ?>
                                        <span class="label-count">(<?php echo $value['count']; ?>)</span>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </label>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
        return $select_radio;
    }

    /**
     * 
     */
    public static function csf_get_result_info($csf_result_info)
    {
        $filter_text = isset($csf_result_info['filter_text']) ? boolval($csf_result_info['filter_text']) : false;
        $filter_other = isset($csf_result_info['filter_other']) ? boolval($csf_result_info['filter_other']) : false;

        if (!$filter_text && !$filter_other) {
            return '';
        }
        ob_start();
        echo "<div id='csf-get-result-info' class = 'filter-result-info'>";
        if (isset($csf_result_info['search_text']) && $csf_result_info['search_text'] && $filter_text) {
        ?>
            <p class="filter-text">
                Search results for
                <span class="filter-text-value">
                    <?php echo $csf_result_info['search_text']; ?>
                </span>
            </p>
            <?php
        }
        if (isset($csf_result_info['other_filter']) && $csf_result_info['other_filter'] && $filter_other) {
            foreach ($csf_result_info['other_filter'] as $other_filter_name => $other_filter_values) {
            ?>
                <div class="search-filter-values">
                    <div class="filter-title">
                        <?php echo $other_filter_name; ?>
                    </div>
                    <div class="filter-value">
                        <?php
                        if (is_string($other_filter_values)) {
                            echo "<span>" . $other_filter_values . "</span>";
                        } else if (is_array($other_filter_values)) {
                            foreach ($other_filter_values as $key => $value) {
                                echo "<span>" . $value . "</span>";
                            }
                        } ?>
                    </div>
                </div>
<?php
            }
        }
        echo "</div>";
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    // SF_Form class end
}
