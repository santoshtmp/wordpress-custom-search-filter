<?php
/**
 * =========================================
 * Plugin Name: CSF - Custom Search Filter library
 * Description: A plugin for search filter to generate form and query the form, used full for developer.
 * Version: 1.3
 * =======================================
 */

/**
 * search filter result default template
 */
if (get_query_var('paged')) {
    $paged = get_query_var('paged');
} elseif (get_query_var('page')) {
    $paged = get_query_var('page');
} else {
    $paged = 1;
}
//  echo do_shortcode('[csf_searchfilter filter_name="map_filter" post_type="project" data_url="http://site_domain_name.local/resource/" ]');
//  echo do_shortcode('[csf_searchfilter filter_name="map_filter" result_show="true"]');

if ($csf_query->have_posts()) {
?>
    <div class="post-card-wrapper">
        <?php
        while ($csf_query->have_posts()) {
            $csf_query->the_post();
            $id = get_the_ID();
        ?>
            <div class="post-card">
                <div class="title">
                    <a href="<?php echo get_permalink($id); ?>"><?php echo get_the_title($id); ?></a>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
<?php
} else {
?>
    <div>
        <p>No content found at the moment, please try to search with other keyword. </p>
    </div>
<?php
}

echo '<div class="navigation pagination " role="navigation">';
echo '<div class="nav-links">';
echo paginate_links(['total' => $csf_query->max_num_pages, 'current' => $paged]);
echo '</div>';
echo '</div>';
?>