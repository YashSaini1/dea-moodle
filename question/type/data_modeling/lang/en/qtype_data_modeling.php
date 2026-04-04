<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'qtype_data_modeling', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    qtype
 * @subpackage data_modeling
 * @copyright  2023 Alexey Kazlovsky <sat.lesha.kazlovsky@gmail.com>
 */

$string['pleaseenterananswer'] = 'Please enter an answer.';
$string['pluginname'] = 'Data Modeling';
$string['pluginname_help'] = 'Data Modeling question with code and table editor.';
$string['pluginname_link'] = 'question/type/data_modeling';
$string['pluginnameadding'] = 'Adding a Data Modeling question';
$string['pluginnameediting'] = 'Editing a Data Modeling question';
$string['pluginnamesummary'] = 'Data Modeling question with code and table editor.';
$string['privacy:metadata'] = 'Data Modeling question type plugin allows question authors to set default options as user preferences.';
$string['video'] = 'Video';
$string['video_url'] = 'Video URL';

$string['see_solution_text'] = 'See solution data';

$string['default_code'] = 'Table dim_customers as cust{
  customer_id int
  customer_name varchar
  age int
  gender varchar
  email_address varchar
  monthly_income int
  occupation varchar
  city varchar
  state varchar
  home_owner varchar
  marital_status varchar
  family_size int
  Indexes {
    (customer_id) [pk]
  }
}


Table dim_products as prod{
  product_id int
  seller_id int
  product_name varchar
  sub_category varchar
  category varchar
  price int
  Indexes {
    (product_id) [pk]
  }
}


Table dim_sellers as sell{
  seller_id int
  seller_name varchar
  seller_description varchar
  business_category varchar
  business_age int
  membership_type varchar
  Indexes {
    (seller_id) [pk]
  }
}


Table fact_transactions as trans{
  order_id int
  transaction_id int
  transaction_date date
  product_id int
  seller_id int
  customer_id int
  quantity_purchased int
  order_value int
  payment_method varchar
  Indexes {
    (order_id) [pk]
  }
}


Ref: trans.customer_id > cust.customer_id
Ref: trans.seller_id > sell.seller_id
Ref: trans.product_id > prod.product_id
Ref: prod.seller_id > sell.seller_id';