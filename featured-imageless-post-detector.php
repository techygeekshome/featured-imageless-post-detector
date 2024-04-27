<?php
/*
Plugin Name: Featured Imageless Post Detector
Description: Checks for posts without a featured image.
Version: 1.0
Author: TechyGeeksHome
Author URI: https://techygeekshome.info
*/

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

    // Get the total number of posts without featured images
    $total_posts = $posts->found_posts;

    // Output the posts without featured images
    echo '<div class="wrap">';
    echo '<h1>Posts without Featured Image</h1>';

    // Output the post count
    echo '<p>Total posts without featured images: ' . $total_posts . '</p>';

    // Output the filter dropdowns
    echo '<form method="get" id="fipd-filter-form">';
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
        'total'   => ceil($total_posts / $per_page),
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
        echo '<th>' . esc_html__('Post Categories', 'text-domain') . '</th>';
        echo '<th>' . esc_html__('Post Tags', 'text-domain') . '</th>';
        echo '<th>' . esc_html__('Post Comments Count', 'text-domain') . '</th>';
        echo '<th>' . esc_html__('Edit', 'text-domain') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        while ($posts->have_posts()) {
            $posts->the_post();
            echo '<tr>';
            echo '<td>' . get_the_title() . '</td>';
            echo '<td>' . esc_html(get_post_status()) . '</td>';
            echo '<td>' . get_the_author() . '</td>';
            echo '<td>' . get_the_date() . '</td>';
            echo '<td>' . get_the_category_list(', ') . '</td>';
            echo '<td>' . get_the_tag_list('', ', ', '') . '</td>';
            echo '<td>' . get_comments_number() . '</td>';
            echo '<td><a href="' . get_edit_post_link() . '">' . esc_html__('Edit', 'text-domain') . '</a></td>';
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

// Enqueue scripts and styles
function fipd_enqueue_scripts() {
    wp_enqueue_script('jquery');
}
add_action('admin_enqueue_scripts', 'fipd_enqueue_scripts');

// JavaScript for form submission
function fipd_filter_posts_with_ajax() {
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#fipd-filter-form').submit(function(e) {
        e.preventDefault(); // Prevent the default form submission

        var form = $(this);
        var data = form.serialize(); // Serialize form data

        $.ajax({
            type: form.attr('method'),
            url: form.attr('action'),
            data: data,
            success: function(response) {
                // Replace the content of the container with the filtered posts
                $('#fipd-posts-container').html($(response).find('#fipd-posts-container').html());
            }
        });
    });
});
</script>
<?php
}
add_action('admin_footer', 'fipd_filter_posts_with_ajax');
