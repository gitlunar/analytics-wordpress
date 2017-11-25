;
analytics.track(
  <?php echo '"' . esc_js( $event ) . '"' ?>
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
