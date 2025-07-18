<?php
/**
 * Checkout Form
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php
wc_print_notices();

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout
if ( ! $checkout->enable_signup && ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
	echo apply_filters( 'woocommerce_checkout_must_be_logged_in_message', esc_html__( 'You must be logged in to checkout.', 'guido' ) );
	return;
}
?>
<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
<div class="row">
	<div class="col-lg-8 col-12">
		<div class="details-check">
		<?php if ( $checkout->get_checkout_fields() ) : ?>

			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

			<div id="customer_details">
				<?php do_action( 'woocommerce_checkout_billing' ); ?>

				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</div>

			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
			
		<?php endif; ?>
		</div>
	</div>
	<div class="col-lg-4 col-12">
		<div class="details-review box-white-inner">
			<div class="order-review">
				<h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'guido' ); ?></h3>
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>

				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
			</div>
		</div>	
	</div>
</div>
</form>
<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>