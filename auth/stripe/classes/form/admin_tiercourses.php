<?php

namespace auth_stripe\form;

class admin_tiercourses extends \moodleform
{

    /**
     * @inheritDoc
     */
    protected function definition()
    {
        global $DB, $COURSE;
        $mform = $this->_form;

        if(!empty($this->_customdata['categories']) && !empty($this->_customdata['products'])){
            foreach($this->_customdata['categories'] as $key=>$category){

                if(empty($category['categoryname'])) continue ; // if course id==1

                $mform->addElement('header', 'category_name_'.$key, $category['categoryname']);
                $products = array();
                foreach($this->_customdata['products'] as $product) {
                    $products[] = $mform->createElement('html', '<div class="col-lg-2">'.$product['name'].'</div>');
                }
                $mform->addGroup($products, 'category_products_name_'.$key, '', null, false);

                foreach($category['courses'] as $course) {
                    $optionattrs = ['class'=>'col-lg-2'];
                    $courses = array();
                    foreach($this->_customdata['products'] as $product) {
                        $courses[] = $mform->createElement('checkbox', 'course_'.$course['id'].'_product_'.$product['tier'], '', '', $optionattrs);
                    }
                    $mform->addGroup($courses, 'course_product', $course['fullname']);
                }
            }
        }

        $buttonarray    = array();
        $classarray     = array('class' => 'form-submit');
        $buttonarray[]  = &$mform->createElement('submit', 'saveandreturn', get_string('savechangesandreturn'), $classarray);
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function output_html($data, $query = '') {
        if (is_array($data) || empty($data)) {
            $data = $this->normalise_data($data);
        }

        $return = '<div class="group"><input type="hidden" name="' .
            $this->get_full_name() . '[' . self::DURING . ']" value="0" />';
        foreach (self::times() as $timemask => $namestring) {
            $id = $this->get_id(). '_' . $timemask;
            $state = '';
            if ($data & $timemask) {
                $state = 'checked="checked" ';
            }
            if ($timemask == self::DURING && !is_null($this->duringstate)) {
                $state = 'disabled="disabled" ';
                if ($this->duringstate) {
                    $state .= 'checked="checked" ';
                }
            }
            $return .= '<span><input type="checkbox" name="' .
                $this->get_full_name() . '[' . $timemask . ']" value="1" id="' . $id .
                '" ' . $state . '/> <label for="' . $id . '">' .
                $namestring . "</label></span>\n";
        }
        $return .= "</div>\n";

        return format_admin_setting($this, $this->visiblename, $return,
            $this->description, true, '', get_string('everythingon', 'quiz'), $query);
    }


}