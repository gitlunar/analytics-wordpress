;
analytics.page(
  <?php echo '"' . esc_js( $category ) . '"' ?>
  <?php
    if ( ! empty( $name ) )
      echo ', "' . esc_js( $name ) . '"'
  ?>
  <?php
    if ( ! empty( $properties ) ) {
      echo ', ' . json_encode( Segment_Analytics_WordPress::esc_js_deep( $properties ) );
    }
    else {
      echo ', {}';
    }
  ?>
  <?php
    if ( ! empty( $options ) ) {
      echo ', ' . json_encode( Segment_Analytics_WordPress::esc_js_deep( $options ) );
    }
  ?>
);