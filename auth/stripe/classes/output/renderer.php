<?php

namespace auth_stripe\output;

use auth_stripe\core;
use auth_stripe\model\product;

require_once($CFG->libdir.'/formslib.php');

class renderer extends \plugin_renderer_base {

    /**
     * Render the login signup form into a nice template for the theme.
     *
     * @param \auth_stripe\form\signup_form|string $form
     *
     * @return string
     */
    public function render_signup_form($form){
        if (is_string($form)){
            $products = product::get_all_by_page(core::MAIN_PAGE);
            $product_cards = product::render_product_cards($products);

            $context = stripe_get_layout_context($form, $product_cards);
            return $this->output->render_from_template('auth_stripe/payment_layout', $context);
        }

        return $this->output->render($form);
    }
}