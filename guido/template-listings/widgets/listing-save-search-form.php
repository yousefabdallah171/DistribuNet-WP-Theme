<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !empty($args['before_widget']) ) {
	echo wp_kses_post( $args['before_widget'] );
}

if ( ! empty( $instance['title'] ) ) {
	echo wp_kses_post( $args['before_title'] );
	echo esc_attr( $instance['title'] );
	echo wp_kses_post( $args['after_title'] );
}

$email_frequency_default = WP_Listings_Directory_Saved_Search::get_email_frequency();

?>

<form method="get" action="" class="saved-search-form">
	<div class="form-group">
	    <label for="<?php echo esc_attr( $args['widget_id'] ); ?>_title"><?php esc_html_e('Title', 'guido'); ?></label>

	    <input type="text" name="name" class="form-control" id="<?php echo esc_attr( $args['widget_id'] ); ?>_title">
	</div><!-- /.form-group -->

	<div class="form-group space-20">
	    <label for="<?php echo esc_attr( $args['widget_id'] ); ?>_email_frequency"><?php esc_html_e('Email Frequency', 'guido'); ?></label>
	    <select name="email_frequency" class="form-control" id="<?php echo esc_attr( $args['widget_id'] ); ?>_email_frequency">
	        <?php if ( !empty($email_frequency_default) ) { ?>
	            <?php foreach ($email_frequency_default as $key => $value) {
	                if ( !empty($value['label']) && !empty($value['days']) ) {
	            ?>
	                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_attr($value['label']); ?></option>

	                <?php } ?>
	            <?php } ?>
	        <?php } ?>
	    </select>
	</div><!-- /.form-group -->

	<?php
		do_action('wp-listings-directory-add-saved-search-form');

		wp_nonce_field('wp-listings-directory-add-saved-search-nonce', 'nonce');
	?>
	<?php if ( ! empty( $instance['button_text'] ) ) : ?>
		<div class="form-group">
			<button class="button btn btn-theme btn-block"><?php echo esc_attr( $instance['button_text'] ); ?></button>
		</div><!-- /.form-group -->
	<?php endif; ?>
</form>

<?php
if ( !empty($args['after_widget']) ) {
	echo wp_kses_post( $args['after_widget'] );
}
?>

