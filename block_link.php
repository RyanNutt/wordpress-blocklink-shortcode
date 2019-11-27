<?php
add_shortcode('block_link', 'block_link');
function block_link( $atts ) {
  global $post;
  $atts = shortcode_atts( [
      'id' => '',
      'slug' => '',
      'url' => ''
          ], $atts );

  /* If the ID wasn't passed we have to do a little more work
   * to figure it out
   */
  if ( empty( $atts[ 'id' ] ) ) {
    if ( ! empty( $atts[ 'slug' ] ) ) {
      $test_post = get_posts( [
          'name' => $atts[ 'slug' ],
          'post_status' => 'publish',
          'numberposts' => 1,
          'post_type' => ['post', 'page']
              ] );
      $atts[ 'id' ] = ! empty( $test_post ) ? $test_post[ 0 ]->ID : 0;
    }
    else if ( ! empty( $atts[ 'url' ] ) ) {
      $atts[ 'id' ] = url_to_postid( $atts[ 'url' ] );
    }
  }

  /* If we can't find the post, just don't show anything except maybe some
   * comments if we're in debug mode.
   */
  if ( empty( $atts[ 'id' ] ) ) { return 'id is empty';
    return defined( 'WP_DEBUG' ) && true === WP_DEBUG ? '<-- Post not found for block link -->' : '';
  }

  /* Time to load the post and build the layout */
  $link_post = get_post( $atts[ 'id' ] );

  if ( empty( $link_post ) ) { die('no post by id');
    return defined( 'WP_DEBUG' ) && true === WP_DEBUG ? '<-- Could not find post by ID for block link -->' : '';
  }

  /* Start with the image if it exists */
  $image_url = get_the_post_thumbnail( $link_post, 'thumbnail' );

  if ( empty( $image_url ) ) {
    // Fallback to Yoast meta fields
    $yoast_url = get_metadata( $link_post->post_type, $link_post->ID, '_yoast_wpseo_opengraph-image', true );
    if ( empty( $yoast_url ) ) {
      // Try Twitter
      $yoast_url = get_metadata( $link_post->post_type, $link_post->ID, '_yoast_wpseo_twitter-image', true );
    }
    if ( ! empty( $yoast_url ) ) {
      $attachment_id = attachment_url_to_postid( $yoast_url );
      if ( $attachment_id ) {
        $yoast_url = wp_get_attachment_image( $attachment_id, 'thumbnail' );
        $image_url = $yoast_url;
      }
    }
  }

  /* Excerpt is next */
  $excerpt = has_excerpt( $link_post->ID ) ? get_the_excerpt( $link_post ) : '';

  if ( empty( $excerpt ) ) {
    $excerpt = get_metadata( $link_post->post_type, $link_post->ID, '_yoast_wpseo_metadesc', true );
  }
  if ( empty( $excerpt ) ) {
    $excerpt = get_metadata( $link_post->post_type, $link_post->ID, '_yoast_wpseo_opengraph-description', true );
  }
  if ( empty( $excerpt ) ) {
    $excerpt = get_metadata( $link_post->post_type, $link_post->ID, '_yoast_wpseo_twitter-description', true );
  }
  if ( empty( $excerpt ) ) {
    /* Just make sure it's a string */
    $excerpt = '';
  }

  /* And now, it's time to build the HTML */
  $permalink = get_permalink( $link_post );
  $html = '<div class="block-link">';
  if ( ! empty( $image_url ) ) {
    $html .= '<a href="' . $permalink . '">';
    $html .= '<div class="link-image">' . $image_url . '</div>';
    $html .= '</a>';
  }
  $html .= '<div class="link-text">';
  $html .= '<a href="' . $permalink . '">';
  $html .= '<div class="link-hdr">' . get_the_title( $link_post ) . '</div>';
  $html .= '<div class="link-excerpt">' . $excerpt . '</div>';
  $html .= '</a>';
  $html .= '</div>'; // .link-text
  $html .= '</div>'; // .block-link
  $html .= '</a>';

  return $html;
}
