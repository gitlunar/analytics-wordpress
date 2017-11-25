
;
jQuery( document ).ready( function( $ ) {
	$.post( "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
		{
			action : 'segment_unset_cookie',
			key    : '<?php echo esc_js( $http_event ); ?>'
		},
		console.log );
});