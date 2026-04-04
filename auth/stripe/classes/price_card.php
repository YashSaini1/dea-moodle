<?php

namespace auth_stripe;

use auth_stripe\model\price;
use auth_stripe\model\price\price_description;
use auth_stripe\model\product;
use auth_stripe\util\price_display_util;
use auth_stripe\util\util;

/**
 * Class for render product prices
 *
 * @package     auth_stripe
 * @copyright   2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class price_card {

    public product $product;
    public price $price;

    public ?price $second_price;
    public int $dependency_price;
    public string $displayed_price;

    public function __construct(product $product, price $price){
        $this->product = $product;
        $this->price = $price;
        $this->second_price = $this->price->get_price_dependency();
        $this->displayed_price = price_display_util::format_displayed_price($this->price, $this->second_price);
        if (!empty($this->second_price)){
            $this->dependency_price = $this->second_price->price * $this->second_price->max_times;
        }
    }

    public function get_payment_data(){
        $data = [
            'plan_name'  => $this->price->plan_name,
            'tier'       => $this->product->tier,
            'price_info' => $this->displayed_price,
            'priceid'    => $this->price->id,
            'period'     => $this->price->period,
            'plan_info'  => $this->get_plan_info(),
            'price_sum'  => price_display_util::format_price_sum($this->price, $this->second_price),
            'is_allow_coupon'  => $this->price->is_allow_coupon,
        ];
        return $data;
    }

    public function get_plan_info(): string{
        if ($this->product->sql_page == core::MAIN_PAGE){
            $template = 'premium';
            if ($this->price->period == core::PERIOD_YEAR){
                $template .= '_annual';
            }

            // self needs to render default premium during AB testing
            return self::_get_card($template, ['period' => util::format_premium_period($this->price)]);
        }

        // we not process state, where first and second prices has a period
        $is_coaching = $this->product->is_coaching_page();
        if (!empty($this->second_price)){
            $second_period = $this->second_price->period;
            $second_max_times = $this->second_price->max_times;

            $deposit_object = [
                'first_deposit' => price_display_util::format_price($this->price->price),
                'period_price'  => price_display_util::format_price($this->second_price->price),
                'period'        => $second_period,
                'period_times'  => $second_max_times,
                'final_times'   => $second_max_times + 1,
                'period_many'   => ($second_max_times > 1) ? $second_period.'s' : $second_period,
                'final_price'   => price_display_util::format_price($this->price->price + $this->dependency_price),
            ];

            $template_context = [
                'deposit_info' => (object)$deposit_object,
                'nextdate'     => util::format_date(strtotime('+1 '.$second_period)),
            ];

            $result = static::_get_card('deposit_payment', $template_context);

            if ($this->product->sql_page == core::SPECIAL_PREMIUM_PAGE){
                // Temporary solution, price description should be used for all prices
                return $result.$this->_get_price_description();
            }

            if ($this->product->sql_page == core::SECOND_COACHING_PAGE){
                return $result.$this->_get_price_description().static::_get_card('coaching_additional', []);
            }

            if (!$is_coaching){
                return $result;
            }

            return $result.static::_get_card('coaching_additional', []);
        }

        if ($this->price->period == core::PERIOD_ONE_TIME){
            $result = static::_get_card('one_time', []);
            if ($this->product->sql_page == core::SECOND_COACHING_PAGE){
                return $result.$this->_get_price_description().static::_get_card('coaching_additional', []);
            }
            if ($this->product->sql_page == core::SPECIAL_PREMIUM_PAGE){
                // Temporary solution, price description should be used for all prices
                return $result.$this->_get_price_description();
            }
            return $result;
        }

        if (core::is_period_price($this->price->period)){
            $result = static::_get_card('split_payment', [
                'nextdate' => util::format_date(strtotime('+1 '.$this->price->period)),
                'display_billed_twice' => $this->price->max_times == 2
            ]);
            if ($this->product->sql_page == core::SECOND_COACHING_PAGE){
                return $result.$this->_get_price_description().static::_get_card('coaching_additional', []);
            }
            if ($this->product->sql_page == core::SPECIAL_PREMIUM_PAGE){
                // Temporary solution, price description should be used for all prices
                return $result.$this->_get_price_description();
            }
            return $result;
        }

        return '';
    }

    protected function _get_price_description(){
        $price_description = price_description::get_by_price($this->price);
        if (empty($price_description)){
            return '';
        }

        $description_str = explode('<br>', strip_tags($price_description->info, '<br><strong><em><span><s>'));
        $bullets_data = [];
        foreach ($description_str as $key => $value){
            $value = trim($value);
            if (!empty($value)){
                $bullets_data[] = ['option_value' => $value];
            }
        }
        return static::_get_card('bullets', [
            'has_info'       => !empty($bullets_data),
            'bullet_options' => $bullets_data,
        ]);
    }

    protected static function _get_card($template, $context = []){
        global $OUTPUT;
        return $OUTPUT->render_from_template('auth_stripe/cards/'.$template, $context);
    }
}