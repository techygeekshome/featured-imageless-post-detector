<?php
/*
Plugin Name: Featured Imageless Post Detector
Description: Checks for posts without a featured image and displays statistics on the Dashboard.
Version: 1.0.0
Author: TechyGeeksHome
Author URI: https://techygeekshome.info
License: GPLv3 or later
*/

// Security Checks
if ( ! defined( 'ABSPATH' ) ) 
	{
		die;
	}

// Enqueue styles
function fipd_enqueue_css() {
    // Enqueue CSS
    wp_enqueue_style('fipd-style', plugins_url('css/fipd-styles.css', __FILE__));
}
// Add the CSS Styling
add_action('admin_enqueue_scripts', 'fipd_enqueue_css');

// Hook into admin menu
add_action('admin_menu', 'fipd_add_menu');

function fipd_add_menu() {
    add_submenu_page('edit.php', 'Posts without Featured Image', 'No Featured Image', 'manage_options', 'posts_without_featured_image', 'fipd_display_posts_without_featured_image');
}

// Function to display posts without featured image
function fipd_display_posts_without_featured_image() {
    // Define the number of posts per page
    $per_page = 30;

    // Define the pagination parameters
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

    // Get the selected post status filter
    $selected_status = isset($_GET['post_status']) ? sanitize_text_field($_GET['post_status']) : 'any';

    // Define the list of post statuses
    $post_statuses = array(
        'any'     => __('Any', 'text-domain'),
        'publish' => __('Published', 'text-domain'),
        'draft'   => __('Draft', 'text-domain'),
        'private' => __('Private', 'text-domain'),
    );

    $args = array(
        'post_type'      => 'post',
        'meta_query'     => array(
            array(
                'key'     => '_thumbnail_id',
                'compare' => 'NOT EXISTS'
            )
        ),
        'post_status'    => $selected_status, // Apply the selected post status filter
        'posts_per_page' => $per_page,
        'paged'          => $paged
    );

    $posts = new WP_Query($args);

    // Output the posts without featured images
    echo '<div class="wrap">';
    echo '<h1>Posts without Featured Image</h1>';

    // Output the filter dropdowns
    echo '<form method="get" id="fipd-filter-form">';
    echo '<input type="hidden" name="page" value="posts_without_featured_image">';
    echo '<label for="post_status">' . __('Sort by Post Status:', 'text-domain') . '</label>';
    echo '<select name="post_status" id="post_status">';
    foreach ($post_statuses as $status => $label) {
        printf('<option value="%s" %s>%s</option>', esc_attr($status), selected($selected_status, $status, false), esc_html($label));
    }
    echo '</select>';
    echo '<input type="submit" class="button" value="' . esc_attr__('Filter', 'text-domain') . '">';
    echo '</form>';

    // Output the pagination links
    echo '<div class="tablenav">';
    echo '<div class="tablenav-pages">';
 	echo paginate_links(array(
    'base'    => add_query_arg('paged', '%#%'),
    'format'  => '',
    'prev_text' => __('&laquo;'),
    'next_text' => __('&raquo;'),
    'total'   => ceil($posts->found_posts / $per_page),
    'current' => $paged
));
    echo '</div>';
    echo '</div>';

    // Output the table of posts without featured images
    echo '<div id="fipd-posts-container">';
    if ($posts->have_posts()) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('Post Title', 'text-domain') . '</th>';
        echo '<th>' . esc_html__('Post Status', 'text-domain') . '</th>';
        echo '<th>' . esc_html__('Post Author', 'text-domain') . '</th>';
        echo '<th>' . esc_html__('Post Date', 'text-domain') . '</th>';
        echo '<th>' . esc_html__('Edit', 'text-domain') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while ($posts->have_posts()) {
            $posts->the_post();
            echo '<tr>';
            echo '<td>' . esc_html(get_the_title()) . '</td>';
            echo '<td>' . esc_html(get_post_status()) . '</td>';
            echo '<td>' . esc_html(get_the_author()) . '</td>';
            echo '<td>' . esc_html(get_the_date()) . '</td>';
            echo '<td><a href="' . esc_url(get_edit_post_link()) . '">' . esc_html__('Edit', 'text-domain') . '</a></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>' . esc_html__('No posts without featured images found.', 'text-domain') . '</p>';
    }
    echo '</div>';

    echo '</div>';
    wp_reset_postdata();
}

// Add custom dashboard widget
add_action('wp_dashboard_setup', 'fipd_add_dashboard_widget');

function fipd_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'fipd_dashboard_widget', // Widget ID
        'Posts without Featured Image', // Widget title
        'fipd_display_dashboard_widget' // Callback function
    );
}

// Callback function to display content in the dashboard widget
function fipd_display_dashboard_widget() {
    // Define the query to get posts without featured images
    $args = array(
        'post_type'      => 'post',
        'meta_query'     => array(
            array(
                'key'     => '_thumbnail_id',
                'compare' => 'NOT EXISTS'
            )
        ),
        'posts_per_page' => -1, // Get all posts without pagination
    );

    $posts = new WP_Query($args);

    // Output the total number of posts without featured images
    $count = $posts->found_posts;
    ?>
    <div class="fipd-dashboard-widget">
        <div class="fipd-widget-header">Featured Image Stats</div>
        <div class="fipd-widget-content">
            <div class="fipd-count"><?php echo esc_html($count); ?></div>
            <div class="fipd-text"><?php esc_html_e('Total posts without featured images', 'text-domain'); ?></div>
        </div>
    </div>
    <?php
    // Reset post data
    wp_reset_postdata();
}
