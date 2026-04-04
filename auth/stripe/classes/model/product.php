<?php

namespace auth_stripe\model;

use auth_stripe\core;
use auth_stripe\core\stripe_database;
use auth_stripe\price_card;
use local_sql\core\model\base_object;

/**
 * Moodle product entity
 *
 * @package     auth_stripe
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class product extends base_object {

    const PAGES = [
        core::MAIN_PAGE,
        core::COACHING_PAGE,
        core::SECOND_COACHING_PAGE,
        core::SPECIAL_PREMIUM_PAGE,
    ];

    static protected string $table = stripe_database::TABLE_PRODUCT;

    public $id;
    public $name;

    /**
     * @var string stripe product id
     */
    public $productid;
    public $tier;
    public $sql_page;

    public static function create_from_form($fromform, $tier){
        $tname = trim($fromform->{'tier_'.$tier.'_name'});
        if (empty($tname)){
            return false;
        }

        $products = product::get_all_sorted_by_tier();
        $product = $products[$tier] ?? null;
        if (empty($product)){
            $product = new static();
        } else {
            $product = clone $product;
        }

        $product->tier = $tier;
        $product->name = $tname;
        $product->sql_page = static::PAGES[$fromform->{'tier_'.$tier.'_page'}];

        return $product;
    }

    /**
     * @param int $tier
     *
     * @return product|null
     */
    public static function get_by_tier($tier): ?product{
        static $products = [];

        if (!array_key_exists($tier, $products)){
            global $DB;
            $record = $DB->get_record(static::table(), ['tier' => $tier]);
            $products[$tier] = static::_create_from_record($record);
        }

        return $products[$tier];
    }

    /**
     * @param string|array $page
     *
     * @return product[]
     */
    public static function get_all_by_page($page){
        static $saved = [];

        $key = $page;
        if (is_array($page)){
            $key = implode('_', $page);
        }

        if (!array_key_exists($key, $saved)){
            $products = static::get_all(['sql_page' => $page]);
            // at the current moment, we work only with one product in one page
            $saved[$key] = $products;
        }
        return $saved[$key];
    }

    public static function get_by_page(string $page){
        $products = static::get_all_by_page($page);
        return end($products);
    }

    /**
     * @return product[]
     */
    public static function get_all_sorted_by_tier(){
        static $products = null;
        if (is_null($products)){
            $records = static::get_records();
            foreach ($records as $record){
                $product = static::_create_from_record($record);
                $products[$product->tier] = $product;
            }
        }
        return $products;
    }

    /**
     * @param product[] $products
     */
    public static function render_product_cards($products){
        $template_data = [];
        foreach ($products as $product){
            $prices = price::get_product_prices($product->id);
            $template_data = static::render_prices($product, $prices, $template_data);
        }

        return $template_data;
    }

    /**
     * @param product $product
     * @param price[] $prices
     *
     * @return array
     */
    public static function render_prices($product, $prices, $template_data = []){
        foreach ($prices as $price){
            if (!$price->enabled){
                continue;
            }

            if (empty($price->dependency)){
                $price_card = new price_card($product, $price);
                $template_data[] = $price_card->get_payment_data();
            }
        }
        return $template_data;
    }

    public function check_page($page){
        return $this->sql_page == $page;
    }

    public function check_page_many($pages){
        return in_array($this->sql_page, $pages);
    }

    public function is_coaching_page(){
        return core::is_coaching_page($this->sql_page);
    }

    public function is_premium_page(){
        return core::is_premium_page($this->sql_page);
    }
}