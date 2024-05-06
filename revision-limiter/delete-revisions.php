<?php
/*
Plugin Name: Revision Limiter
Description: Limits the number of revisions for posts and pages.
Version:     1.2
Author:      Jerel Layog
Author URI:  https://jerel.dev
*/

function limit_revisions( $num, $post ) {
  $revision_limit = get_option( 'revision_limit', 20 );
  return $revision_limit;
}
add_filter( 'wp_revisions_to_keep', 'limit_revisions', 10, 2 );

function delete_old_revisions( $post_id ) {
  $revisions = wp_get_post_revisions( $post_id, array( 'order' => 'ASC' ) );
  $revision_limit = get_option( 'revision_limit', 20 );
  if ( count( $revisions ) > $revision_limit ) {
    $delete = count( $revisions ) - $revision_limit;
    $revisions_to_delete = array_slice( $revisions, 0, $delete );
    foreach ( $revisions_to_delete as $revision ) {
      wp_delete_post_revision( $revision->ID );
    }
  }
}

function limit_revisions_settings() {
  add_options_page( 'Revision Limiter Settings', 'Revision Limiter', 'manage_options', 'limit-revisions', 'limit_revisions_settings_page' );
}
add_action( 'admin_menu', 'limit_revisions_settings' );

function limit_revisions_register_settings() {
  register_setting( 'limit_revisions_settings_group', 'revision_limit', 'intval' );
  add_settings_section( 'limit_revisions_section', 'Revision Limit', 'limit_revisions_section_callback', 'limit_revisions' );
  add_settings_field( 'revision_limit', 'Revision Limit', 'revision_limit_callback', 'limit_revisions', 'limit_revisions_section' );
}
add_action( 'admin_init', 'limit_revisions_register_settings' );

function limit_revisions_settings_page() {
  if (isset($_POST['delete_revisions'])) {
    delete_all_revisions();
    echo '<div id="message" class="updated notice is-dismissible"><p>All revisions exceeding the limit have been deleted.</p></div>';
  }
  ?>
  <div class="wrap">
    <h2>Revision Limiter Settings</h2>
    <form method="post" action="options.php">
      <?php settings_fields( 'limit_revisions_settings_group' ); ?>
      <?php do_settings_sections( 'limit_revisions' ); ?>
      <?php submit_button(); ?>
    </form>
    <form method="post">
      <input type="submit" name="delete_revisions" value="Delete Revisions" class="button" onclick="return confirm('Are you sure? This operation cannot be undone.');" />
    </form>
  </div>
  <?php
}

function limit_revisions_section_callback() {
  echo '<p>Set the maximum number of revisions to keep for posts and pages.</p>';
}

function revision_limit_callback() {
  $revision_limit = get_option( 'revision_limit', 20 );
  echo '<input type="number" name="revision_limit" value="' . esc_attr( $revision_limit ) . '" />';
}

// Function to delete all revisions exceeding the limit
function delete_all_revisions() {
  $args = array(
    'post_type' => array('post', 'page'),
    'posts_per_page' => -1,
  );
  $query = new WP_Query($args);
  while ($query->have_posts()) {
    $query->the_post();
    delete_old_revisions(get_the_ID());
  }
  wp_reset_postdata();
}
?>
