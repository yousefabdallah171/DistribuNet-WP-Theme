<div class="wrapper-top-cart">
	<div class="overlay-dropdown-menu-right"></div>
	<div class="dropdown-menu-right">
	    <div class="widget_shopping_cart_heading">
	        <h3><i class="fa fa-cart"></i> <?php esc_html_e('My Cart', 'guido'); ?></h3>
	    </div>
	    <div class="widget_shopping_cart_content_wrapper">
	    	<div class="widget_shopping_cart_content">
	            <?php woocommerce_mini_cart(); ?>
	        </div>
	    </div>
	</div>
</div>