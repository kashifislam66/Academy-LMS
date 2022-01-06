<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (file_exists("application/aws-module/aws-autoloader.php")) {
    include APPPATH . 'aws-module/aws-autoloader.php';
}

class Crud_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        /*cache control*/
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    public function get_categories($param1 = "")
    {
        if ($param1 != "") {
            $this->db->where('id', $param1);
        }
        $this->db->where('parent', 0);
        return $this->db->get('category');
    }

    public function get_category_details_by_id($id)
    {
        return $this->db->get_where('category', array('id' => $id));
    }

    public function get_category_id($slug = "")
    {
        $category_details = $this->db->get_where('category', array('slug' => $slug))->row_array();
        return $category_details['id'];
    }

    public function add_category()
    {
        $data['code']   = html_escape($this->input->post('code'));
        $data['name']   = html_escape($this->input->post('name'));
        $data['parent'] = html_escape($this->input->post('parent'));
        $data['slug']   = slugify(html_escape($this->input->post('name')));

        // CHECK IF THE CATEGORY NAME ALREADY EXISTS
        $this->db->where('name', $data['name']);
        $this->db->or_where('slug', $data['slug']);
        $previous_data = $this->db->get('category')->num_rows();

        if ($previous_data == 0) {
            // Font awesome class adding
            if ($_POST['font_awesome_class'] != "") {
                $data['font_awesome_class'] = html_escape($this->input->post('font_awesome_class'));
            } else {
                $data['font_awesome_class'] = 'fas fa-chess';
            }

            if ($this->input->post('parent') == 0) {
                // category thumbnail adding
                if (!file_exists('uploads/thumbnails/category_thumbnails')) {
                    mkdir('uploads/thumbnails/category_thumbnails', 0777, true);
                }
                if ($_FILES['category_thumbnail']['name'] == "") {
                    $data['thumbnail'] = 'category-thumbnail.png';
                } else {
                    $data['thumbnail'] = md5(rand(10000000, 20000000)) . '.jpg';
                    move_uploaded_file($_FILES['category_thumbnail']['tmp_name'], 'uploads/thumbnails/category_thumbnails/' . $data['thumbnail']);
                }
            }
            $data['date_added'] = strtotime(date('D, d-M-Y'));
            $this->db->insert('category', $data);
            return true;
        }

        return false;
    }

    public function edit_category($param1)
    {
        $data['name']   = html_escape($this->input->post('name'));
        $data['parent'] = html_escape($this->input->post('parent'));
        $data['slug']   = slugify(html_escape($this->input->post('name')));

        // CHECK IF THE CATEGORY NAME ALREADY EXISTS
        $this->db->where('name', $data['name']);
        $this->db->or_where('slug', $data['slug']);
        $previous_data = $this->db->get('category')->result_array();

        $checker = true;
        foreach ($previous_data as $row) {
            if ($row['id'] != $param1) {
                $checker = false;
                break;
            }
        }

        if ($checker) {
            // Font awesome class adding
            if ($_POST['font_awesome_class'] != "") {
                $data['font_awesome_class'] = html_escape($this->input->post('font_awesome_class'));
            } else {
                $data['font_awesome_class'] = 'fas fa-chess';
            }

            if ($this->input->post('parent') == 0) {
                // category thumbnail adding
                if (!file_exists('uploads/thumbnails/category_thumbnails')) {
                    mkdir('uploads/thumbnails/category_thumbnails', 0777, true);
                }
                if ($_FILES['category_thumbnail']['name'] != "") {
                    $data['thumbnail'] = md5(rand(10000000, 20000000)) . '.jpg';
                    move_uploaded_file($_FILES['category_thumbnail']['tmp_name'], 'uploads/thumbnails/category_thumbnails/' . $data['thumbnail']);
                }
            }
            $data['last_modified'] = strtotime(date('D, d-M-Y'));
            $this->db->where('id', $param1);
            $this->db->update('category', $data);

            return true;
        }
        return false;
    }

    public function delete_category($category_id)
    {
        $this->db->where('id', $category_id);
        $this->db->delete('category');
    }

    public function get_sub_categories($parent_id = "")
    {
        return $this->db->get_where('category', array('parent' => $parent_id))->result_array();
    }

    public function enrol_history($course_id = "")
    {
        if ($course_id > 0) {
            return $this->db->get_where('enrol', array('course_id' => $course_id));
        } else {
            return $this->db->get('enrol');
        }
    }

    public function enrol_history_by_user_id($user_id = "")
    {
        return $this->db->get_where('enrol', array('user_id' => $user_id));
    }

    public function enrol_history_by_company_id($course_status = '' , $cr_user_id = 0)
    {
        $user_id = $this->session->userdata('user_id');
        $where = [];
        $where['company_id'] = $user_id;
        if(!empty($course_status)){
            $where['course_status'] = $course_status;
        }
        if(!empty($cr_user_id)){
            $where['user_id'] = $cr_user_id;
        }
        return $query = $this->db
            ->select("enrol.user_id,enrol.course_id,enrol.course_status,enrol.enrol_last_date,users.*")
            ->from ("enrol")
            ->join('users', 'enrol.user_id = users.id')
            ->where($where)
            ->get();
        // return $this->db->get_where('enrol', array('user_id' => $user_id));
    }

    

    public function all_enrolled_student()
    {
        $this->db->select('user_id');
        $this->db->distinct('user_id');
        return $this->db->get('enrol');
    }

    public function enrol_history_by_date_range($timestamp_start = "", $timestamp_end = "")
    {
        $this->db->order_by('date_added', 'desc');
        $this->db->where('date_added >=', $timestamp_start);
        $this->db->where('date_added <=', $timestamp_end);
        return $this->db->get('enrol');
    }

    public function enrol_request_by_date_range()
    {
        $this->db->order_by('dated_request', 'desc');
        $this->db->where('company_id', $this->session->userdata('user_id'));
        return $this->db->get('enrolment_request');
    }

    

    public function get_revenue_by_user_type($timestamp_start = "", $timestamp_end = "", $revenue_type = "")
    {
        $course_ids = array();
        $courses    = array();
        $admin_details = $this->user_model->get_admin_details()->row_array();
        if ($revenue_type == 'admin_revenue') {
            $this->db->where('date_added >=', $timestamp_start);
            $this->db->where('date_added <=', $timestamp_end);
        } elseif ($revenue_type == 'instructor_revenue') {

            $this->db->where('user_id !=', $admin_details['id']);
            $this->db->select('id');
            $courses = $this->db->get('course')->result_array();
            foreach ($courses as $course) {
                if (!in_array($course['id'], $course_ids)) {
                    array_push($course_ids, $course['id']);
                }
            }
            if (sizeof($course_ids)) {
                $this->db->where_in('course_id', $course_ids);
            } else {
                return array();
            }
        }

        $this->db->order_by('date_added', 'desc');
        return $this->db->get('payment')->result_array();
    }

    public function get_instructor_revenue($user_id = "", $timestamp_start = "", $timestamp_end = "")
    {
        $course_ids = array();
        $courses    = array();

        $multi_instructor_course_ids = $this->multi_instructor_course_ids_for_an_instructor($user_id);

        if ($user_id > 0) {
            $this->db->where('user_id', $user_id);
        } else {
            $this->db->where('user_id', $this->session->userdata('user_id'));
        }

        if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
            $this->db->or_where_in('id', $multi_instructor_course_ids);
        }

        $this->db->select('id');
        $courses = $this->db->get('course')->result_array();
        foreach ($courses as $course) {
            if (!in_array($course['id'], $course_ids)) {
                array_push($course_ids, $course['id']);
            }
        }
        if (sizeof($course_ids)) {
            $this->db->where_in('course_id', $course_ids);
        } else {
            return array();
        }

        // CHECK IF THE DATE RANGE IS SELECTED
        if (!empty($timestamp_start) && !empty($timestamp_end)) {
            $this->db->where('date_added >=', $timestamp_start);
            $this->db->where('date_added <=', $timestamp_end);
        }

        $this->db->order_by('date_added', 'desc');
        return $this->db->get('payment')->result_array();
    }

    public function delete_payment_history($param1)
    {
        $this->db->where('id', $param1);
        $this->db->delete('payment');
    }
    public function delete_enrol_history($param1)
    {
        $this->db->where('id', $param1);
        $this->db->delete('enrol');
    }

    public function purchase_history($user_id)
    {
        if ($user_id > 0) {
            return $this->db->get_where('payment', array('user_id' => $user_id));
        } else {
            return $this->db->get('payment');
        }
    }

    public function get_payment_details_by_id($payment_id = "")
    {
        return $this->db->get_where('payment', array('id' => $payment_id))->row_array();
    }

    public function update_payout_status($payout_id = "", $payment_type = "")
    {
        $updater = array(
            'status' => 1,
            'payment_type' => $payment_type,
            'last_modified' => strtotime(date('D, d-M-Y'))
        );
        $this->db->where('id', $payout_id);
        $this->db->update('payout', $updater);
    }

    public function update_system_settings()
    {
        $data['value'] = html_escape($this->input->post('system_name'));
        $this->db->where('key', 'system_name');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('system_title'));
        $this->db->where('key', 'system_title');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('author'));
        $this->db->where('key', 'author');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('slogan'));
        $this->db->where('key', 'slogan');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('language'));
        $this->db->where('key', 'language');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('text_align'));
        $this->db->where('key', 'text_align');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('system_email'));
        $this->db->where('key', 'system_email');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('address'));
        $this->db->where('key', 'address');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('phone'));
        $this->db->where('key', 'phone');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('youtube_api_key'));
        $this->db->where('key', 'youtube_api_key');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('vimeo_api_key'));
        $this->db->where('key', 'vimeo_api_key');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('purchase_code'));
        $this->db->where('key', 'purchase_code');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('footer_text'));
        $this->db->where('key', 'footer_text');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('footer_link'));
        $this->db->where('key', 'footer_link');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('website_keywords'));
        $this->db->where('key', 'website_keywords');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('website_description'));
        $this->db->where('key', 'website_description');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('student_email_verification'));
        $this->db->where('key', 'student_email_verification');
        $this->db->update('settings', $data);
    }

    public function update_smtp_settings()
    {
        $data['value'] = html_escape($this->input->post('protocol'));
        $this->db->where('key', 'protocol');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('smtp_host'));
        $this->db->where('key', 'smtp_host');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('smtp_port'));
        $this->db->where('key', 'smtp_port');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('smtp_user'));
        $this->db->where('key', 'smtp_user');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('smtp_pass'));
        $this->db->where('key', 'smtp_pass');
        $this->db->update('settings', $data);
    }

    public function update_social_login_settings()
    {
        $data['value'] = html_escape($this->input->post('fb_social_login'));
        $this->db->where('key', 'fb_social_login');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('fb_app_id'));
        $this->db->where('key', 'fb_app_id');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('fb_app_secret'));
        $this->db->where('key', 'fb_app_secret');
        $this->db->update('settings', $data);
    }

    public function update_paypal_settings()
    {
        // update paypal keys
        $paypal_info = array();
        $paypal['active'] = $this->input->post('paypal_active');
        $paypal['mode'] = $this->input->post('paypal_mode');
        $paypal['sandbox_client_id'] = $this->input->post('sandbox_client_id');
        $paypal['sandbox_secret_key'] = $this->input->post('sandbox_secret_key');

        $paypal['production_client_id'] = $this->input->post('production_client_id');
        $paypal['production_secret_key'] = $this->input->post('production_secret_key');

        array_push($paypal_info, $paypal);

        $data['value']    =   json_encode($paypal_info);
        $this->db->where('key', 'paypal');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('paypal_currency'));
        $this->db->where('key', 'paypal_currency');
        $this->db->update('settings', $data);
    }

    public function update_stripe_settings()
    {
        // update stripe keys
        $stripe_info = array();

        $stripe['active'] = $this->input->post('stripe_active');
        $stripe['testmode'] = $this->input->post('testmode');
        $stripe['public_key'] = $this->input->post('public_key');
        $stripe['secret_key'] = $this->input->post('secret_key');
        $stripe['public_live_key'] = $this->input->post('public_live_key');
        $stripe['secret_live_key'] = $this->input->post('secret_live_key');

        array_push($stripe_info, $stripe);

        $data['value']    =   json_encode($stripe_info);
        $this->db->where('key', 'stripe_keys');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('stripe_currency'));
        $this->db->where('key', 'stripe_currency');
        $this->db->update('settings', $data);
    }

    public function update_razorpay_settings() {
        // update razorpay keys
        $paytm_info = array();
        $razorpay['active'] = htmlspecialchars($this->input->post('razorpay_active'));
        $razorpay['key'] = htmlspecialchars($this->input->post('key'));
        $razorpay['secret_key'] = htmlspecialchars($this->input->post('secret_key'));
        $razorpay['theme_color'] = htmlspecialchars($this->input->post('theme_color'));

        array_push($paytm_info, $razorpay);

        $data['value']    =   json_encode($paytm_info);
        $this->db->where('key', 'razorpay_keys');
        $this->db->update('settings', $data);

        $data['value'] = htmlspecialchars($this->input->post('razorpay_currency'));
        $this->db->where('key', 'razorpay_currency');
        $this->db->update('settings', $data);
    }

    public function update_system_currency()
    {
        $data['value'] = html_escape($this->input->post('system_currency'));
        $this->db->where('key', 'system_currency');
        $this->db->update('settings', $data);

        $data['value'] = html_escape($this->input->post('currency_position'));
        $this->db->where('key', 'currency_position');
        $this->db->update('settings', $data);
    }

    public function update_instructor_settings()
    {
        if (isset($_POST['allow_instructor'])) {
            $data['value'] = html_escape($this->input->post('allow_instructor'));
            $this->db->where('key', 'allow_instructor');
            $this->db->update('settings', $data);
        }

        if (isset($_POST['instructor_revenue'])) {
            $data['value'] = html_escape($this->input->post('instructor_revenue'));
            $this->db->where('key', 'instructor_revenue');
            $this->db->update('settings', $data);
        }

        if (isset($_POST['instructor_application_note'])) {
            $data['value'] = html_escape($this->input->post('instructor_application_note'));
            $this->db->where('key', 'instructor_application_note');
            $this->db->update('settings', $data);
        }
    }

    public function get_lessons($type = "", $id = "")
    {
        $this->db->order_by("order", "asc");
        if ($type == "course") {
            return $this->db->get_where('lesson', array('course_id' => $id));
        } elseif ($type == "section") {
            return $this->db->get_where('lesson', array('section_id' => $id));
        } elseif ($type == "lesson") {
            return $this->db->get_where('lesson', array('id' => $id));
        } else {
            return $this->db->get('lesson');
        }
    }

    public function add_course($param1 = "")
    {
        $outcomes = $this->trim_and_return_json($this->input->post('outcomes'));
        $requirements = $this->trim_and_return_json($this->input->post('requirements'));

        $data['course_type'] = html_escape($this->input->post('course_type'));
        $data['title'] = html_escape($this->input->post('title'));
        $data['short_description'] = html_escape($this->input->post('short_description'));
        $data['description']   = $this->input->post('description');
        $data['future_course'] = $this->input->post('future_course');
        $data['outcomes'] = $outcomes;
        $data['language'] = $this->input->post('language_made_in');
        $data['sub_category_id'] = $this->input->post('sub_category_id');
        $category_details = $this->get_category_details_by_id($this->input->post('sub_category_id'))->row_array();
        $data['category_id'] = $category_details['parent'];
        $data['requirements'] = $requirements;
        $data['price'] = $this->input->post('price');
        $data['discount_flag'] = $this->input->post('discount_flag');
        $data['discounted_price'] = $this->input->post('discounted_price');
        $data['level'] = $this->input->post('level');
        $data['is_free_course'] = $this->input->post('is_free_course');
        $data['video_url'] = html_escape($this->input->post('course_overview_url'));

        if ($this->input->post('course_overview_url') != "") {
            $data['course_overview_provider'] = html_escape($this->input->post('course_overview_provider'));
        } else {
            $data['course_overview_provider'] = "";
        }

        $data['date_added'] = strtotime(date('D, d-M-Y'));
        $data['section'] = json_encode(array());
        $data['is_top_course'] = $this->input->post('is_top_course');
        $data['user_id'] = $this->session->userdata('user_id');
        $data['creator'] = $this->session->userdata('user_id');
        $data['meta_description'] = $this->input->post('meta_description');
        $data['meta_keywords'] = $this->input->post('meta_keywords');
        $admin_details = $this->user_model->get_admin_details()->row_array();
        if ($admin_details['id'] == $data['user_id']) {
            $data['is_admin'] = 1;
        } else {
            $data['is_admin'] = 0;
        }
        if ($param1 == "save_to_draft") {
            $data['status'] = 'draft';
        } else {
            if ($this->session->userdata('admin_login')) {
                $data['status'] = 'active';
            } else {
                $data['status'] = 'pending';
            }
        }
        $this->db->insert('course', $data);

        $course_id = $this->db->insert_id();
        // Create folder if does not exist
        if (!file_exists('uploads/thumbnails/course_thumbnails')) {
            mkdir('uploads/thumbnails/course_thumbnails', 0777, true);
        }

        // Upload different number of images according to activated theme. Data is taking from the config.json file
        $course_media_files = themeConfiguration(get_frontend_settings('theme'), 'course_media_files');
        foreach ($course_media_files as $course_media => $size) {
            if ($_FILES[$course_media]['name'] != "") {
                move_uploaded_file($_FILES[$course_media]['tmp_name'], 'uploads/thumbnails/course_thumbnails/' . $course_media . '_' . get_frontend_settings('theme') . '_' . $course_id . '.jpg');
            }
        }

        if ($data['status'] == 'approved') {
            $this->session->set_flashdata('flash_message', get_phrase('course_added_successfully'));
        } elseif ($data['status'] == 'pending') {
            $this->session->set_flashdata('flash_message', get_phrase('course_added_successfully') . '. ' . get_phrase('please_wait_untill_Admin_approves_it'));
        } elseif ($data['status'] == 'draft') {
            $this->session->set_flashdata('flash_message', get_phrase('your_course_has_been_added_to_draft'));
        }

        $this->session->set_flashdata('flash_message', get_phrase('course_has_been_added_successfully'));
        return $course_id;
    }

    function add_shortcut_course($param1 = "")
    {
        $data['course_type'] = html_escape($this->input->post('course_type'));
        $data['title'] = html_escape($this->input->post('title'));
        $data['outcomes'] = '[]';
        $data['language'] = $this->input->post('language_made_in');
        $data['sub_category_id'] = $this->input->post('sub_category_id');
        $category_details = $this->get_category_details_by_id($this->input->post('sub_category_id'))->row_array();
        $data['category_id'] = $category_details['parent'];

        $data['requirements'] = '[]';
        $data['price'] = $this->input->post('price');
        $data['discount_flag'] = $this->input->post('discount_flag');
        $data['discounted_price'] = $this->input->post('discounted_price');
        $data['level'] = $this->input->post('level');
        $data['is_free_course'] = $this->input->post('is_free_course');

        $data['date_added'] = strtotime(date('D, d-M-Y'));
        $data['section'] = json_encode(array());

        $data['user_id'] = $this->session->userdata('user_id');

        $admin_details = $this->user_model->get_admin_details()->row_array();
        if ($admin_details['id'] == $data['user_id']) {
            $data['is_admin'] = 1;
        } else {
            $data['is_admin'] = 0;
        }
        if ($param1 == "save_to_draft") {
            $data['status'] = 'draft';
        } else {
            if ($this->session->userdata('admin_login')) {
                $data['status'] = 'active';
            } else {
                $data['status'] = 'pending';
            }
        }
        if ($data['is_free_course'] == 1 || $data['is_free_course'] != 1 && $data['price'] > 0 && $data['discount_flag'] != 1 || $data['discount_flag'] == 1 && $data['discounted_price'] > 0) {
            $this->db->insert('course', $data);

            $this->session->set_flashdata('flash_message', get_phrase('course_has_been_added_successfully'));

            $response['status'] = 1;
            return json_encode($response);
        } else {
            $response['status'] = 0;
            $response['message'] = get_phrase('please_fill_up_the_price_field');
            return json_encode($response);
        }
    }

    function trim_and_return_json($untrimmed_array)
    {
        $trimmed_array = array();
        if (sizeof($untrimmed_array) > 0) {
            foreach ($untrimmed_array as $row) {
                if ($row != "") {
                    array_push($trimmed_array, $row);
                }
            }
        }
        return json_encode($trimmed_array);
    }

    public function update_course($course_id, $type = "")
    {
        $course_details = $this->get_course_by_id($course_id)->row_array();

        $outcomes = $this->trim_and_return_json($this->input->post('outcomes'));
        $requirements = $this->trim_and_return_json($this->input->post('requirements'));
        $data['title'] = $this->input->post('title');
        $data['short_description'] = $this->input->post('short_description');
        $data['description'] = $this->input->post('description');
        $data['outcomes'] = $outcomes;
        $data['language'] = $this->input->post('language_made_in');
        $data['future_course'] = $this->input->post('future_course');
        $data['sub_category_id'] = $this->input->post('sub_category_id');
        $category_details = $this->get_category_details_by_id($this->input->post('sub_category_id'))->row_array();
        $data['category_id'] = $category_details['parent'];
        $data['requirements'] = $requirements;
        $data['is_free_course'] = $this->input->post('is_free_course');
        $data['price'] = $this->input->post('price');
        $data['discount_flag'] = $this->input->post('discount_flag');
        $data['discounted_price'] = $this->input->post('discounted_price');
        $data['level'] = $this->input->post('level');
        $data['video_url'] = $this->input->post('course_overview_url');
        if ($this->input->post('course_overview_url') != "") {
            $data['course_overview_provider'] = $this->input->post('course_overview_provider');
        } else {
            $data['course_overview_provider'] = "";
        }

        $data['meta_description'] = $this->input->post('meta_description');
        $data['meta_keywords'] = $this->input->post('meta_keywords');
        $data['last_modified'] = strtotime(date('D, d-M-Y'));

        if ($this->input->post('is_top_course') != 1) {
            $data['is_top_course'] = 0;
        } else {
            $data['is_top_course'] = 1;
        }


        if ($type == "save_to_draft") {
            $data['status'] = 'draft';
        } else {
            if ($this->session->userdata('admin_login')) {
                $data['status'] = 'active';
            } else {
                $data['status'] = $course_details['status'];
            }
        }

        // MULTI INSTRUCTOR PART STARTS
        if (isset($_POST['new_instructors']) && !empty($_POST['new_instructors'])) {
            $existing_instructors = explode(',', $course_details['user_id']);
            foreach ($_POST['new_instructors'] as $instructor) {
                if (!in_array($instructor, $existing_instructors)) {
                    array_push($existing_instructors, $instructor);
                }
            }
            $data['user_id'] = implode(",", $existing_instructors);
            $data['multi_instructor'] = 1;
        }
        // MULTI INSTRUCTOR PART ENDS

        $this->db->where('id', $course_id);
        $this->db->update('course', $data);

        // Upload different number of images according to activated theme. Data is taking from the config.json file
        $course_media_files = themeConfiguration(get_frontend_settings('theme'), 'course_media_files');
        foreach ($course_media_files as $course_media => $size) {
            if ($_FILES[$course_media]['name'] != "") {
                move_uploaded_file($_FILES[$course_media]['tmp_name'], 'uploads/thumbnails/course_thumbnails/' . $course_media . '_' . get_frontend_settings('theme') . '_' . $course_id . '.jpg');
            }
        }

        if ($data['status'] == 'active') {
            $this->session->set_flashdata('flash_message', get_phrase('course_updated_successfully'));
        } elseif ($data['status'] == 'pending') {
            $this->session->set_flashdata('flash_message', get_phrase('course_updated_successfully') . '. ' . get_phrase('please_wait_untill_Admin_approves_it'));
        } elseif ($data['status'] == 'draft') {
            $this->session->set_flashdata('flash_message', get_phrase('your_course_has_been_added_to_draft'));
        }
    }

    public function change_course_status($status = "", $course_id = "")
    {
        if ($status == 'active') {
            if ($this->session->userdata('admin_login') != true) {
                redirect(site_url('login'), 'refresh');
            }
        }
        $updater = array(
            'status' => $status
        );
        $this->db->where('id', $course_id);
        $this->db->update('course', $updater);
    }

    public function get_course_thumbnail_url($course_id, $type = 'course_thumbnail')
    {
       
        // Course media placeholder is coming from the theme config file. Which has all the placehoder for different images. Choose like course type.
        $course_media_placeholders = themeConfiguration(get_frontend_settings('theme'), 'course_media_placeholders');
        // if (file_exists('uploads/thumbnails/course_thumbnails/'.$type.'_'.get_frontend_settings('theme').'_'.$course_id.'.jpg')){
        //     return base_url().'uploads/thumbnails/course_thumbnails/'.$type.'_'.get_frontend_settings('theme').'_'.$course_id.'.jpg';
        // } elseif(file_exists('uploads/thumbnails/course_thumbnails/'.$course_id.'.jpg')){
        //     return base_url().'uploads/thumbnails/course_thumbnails/'.$course_id.'.jpg';
        // } else{
        //     return $course_media_placeholders[$type.'_placeholder'];
        // }
       
        if (file_exists('uploads/thumbnails/course_thumbnails/' . $type . '_' . get_frontend_settings('theme') . '_' . $course_id . '.jpg')) {
            return base_url() . 'uploads/thumbnails/course_thumbnails/' . $type . '_' . get_frontend_settings('theme') . '_' . $course_id . '.jpg';
        } else {
            return base_url() . $course_media_placeholders[$type . '_placeholder'];
        }
    }
    public function get_lesson_thumbnail_url($lesson_id)
    {

        if (file_exists('uploads/thumbnails/lesson_thumbnails/' . $lesson_id . '.jpg'))
            return base_url() . 'uploads/thumbnails/lesson_thumbnails/' . $lesson_id . '.jpg';
        else
            return base_url() . 'uploads/thumbnails/thumbnail.png';
    }

    public function get_my_courses_by_category_id($category_id)
    {
        $this->db->select('course_id');
        $course_lists_by_enrol = $this->db->get_where('enrol', array('user_id' => $this->session->userdata('user_id')))->result_array();
        $course_ids = array();
        foreach ($course_lists_by_enrol as $row) {
            if (!in_array($row['course_id'], $course_ids)) {
                array_push($course_ids, $row['course_id']);
            }
        }
        $this->db->where_in('id', $course_ids);
        $this->db->where('category_id', $category_id);
        return $this->db->get('course');
    }

    public function get_my_courses_by_search_string($search_string)
    {
        $this->db->select('course_id');
        $course_lists_by_enrol = $this->db->get_where('enrol', array('user_id' => $this->session->userdata('user_id')))->result_array();
        $course_ids = array();
        foreach ($course_lists_by_enrol as $row) {
            if (!in_array($row['course_id'], $course_ids)) {
                array_push($course_ids, $row['course_id']);
            }
        }
        $this->db->where_in('id', $course_ids);
        $this->db->like('title', $search_string);
        return $this->db->get('course');
    }

    public function get_courses_by_search_string($search_string)
    {
        $this->db->like('title', $search_string);
        $this->db->where('status', 'active');
        return $this->db->get('course');
    }


    public function get_course_by_id($course_id = "")
    {
        return $this->db->get_where('course', array('id' => $course_id));
    }

    public function get_course_by_api_id($course_id = "")
    {
        return $this->db->get_where('course', array('api_id' => $course_id));
    }

    public function get_cat_by_api_id($name)
    {
        return $this->db->get_where('category', array('name' => $name));
     
    }

    function add_category_api($db_data){
        $sql = $this->db->insert('category',$db_data);
        return $this->db->insert_id();
    }
    // course add
    function add_course_api($db_data){
        $sql = $this->db->insert('course',$db_data);
        return $this->db->insert_id();
    }

    // lesson add
    function add_lesson_api($db_data){
        $sql = $this->db->insert('lesson',$db_data);
        return $this->db->insert_id();
    }

    // section add
    function add_section_api($db_data){
        $sql = $this->db->insert('section',$db_data);
        return $this->db->insert_id();
    }

    public function delete_course($course_id = "")
    {
        $course_type = $this->get_course_by_id($course_id)->row('course_type');

        $this->db->where('id', $course_id);
        $this->db->delete('course');

        if ($course_type == 'general') {
            // DELETE ALL THE LESSONS OF THIS COURSE FROM LESSON TABLE
            $lesson_checker = array('course_id' => $course_id);
            $this->db->delete('lesson', $lesson_checker);

            // DELETE ALL THE section OF THIS COURSE FROM section TABLE
            $this->db->where('course_id', $course_id);
            $this->db->delete('section');
        } elseif ($course_type == 'scorm') {
            $this->load->model('addons/scorm_model');
            $scorm_query = $this->scorm_model->get_scorm_curriculum_by_course_id($course_id);

            $this->db->where('course_id', $course_id);
            $this->db->delete('scorm_curriculum');

            if ($scorm_query->num_rows() > 0) {
                //deleted previews course directory
                $this->scorm_model->deleteDir('uploads/scorm/courses/' . $scorm_query->row('identifier'));
            }
        }
    }

    function get_top_categories($limit = "10", $category_column = "category_id"){
        $query = $this->db
            ->select($category_column.", count(*) AS course_number",false)
            ->from ("course")
            ->group_by($category_column)
            ->order_by("course_number","DESC")
            ->where('status', 'active')
            ->limit($limit)
            ->get();
        return $query->result_array();
    }

    public function get_top_courses()
    {
        if (addon_status('scorm_course')) {
            return $this->db->get_where('course', array('is_top_course' => 1, 'status' => 'active'));
        } else {
            return $this->db->get_where('course', array('is_top_course' => 1, 'status' => 'active', 'course_type' => 'general'));
        }
    }

    public function get_default_category_id()
    {
        $categories = $this->get_categories()->result_array();
        foreach ($categories as $category) {
            return $category['id'];
        }
    }

    public function get_courses_by_user_id($param1 = "")
    {
        $multi_instructor_course_ids = $this->multi_instructor_course_ids_for_an_instructor($param1);

        $this->db->where('status', 'draft');
        $this->db->where('user_id', $param1);
        if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
            $this->db->or_where_in('id', $multi_instructor_course_ids);
        }
        $courses['draft'] = $this->db->get('course');

        $this->db->where('status', 'pending');
        $this->db->where('user_id', $param1);
        if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
            $this->db->or_where_in('id', $multi_instructor_course_ids);
        }
        $courses['pending'] = $this->db->get('course');

        $this->db->where('status', 'active');
        $this->db->where('user_id', $param1);
        if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
            $this->db->or_where_in('id', $multi_instructor_course_ids);
        }
        $courses['active'] = $this->db->get('course');

        return $courses;
    }

    public function get_status_wise_courses($status = "")
    {
        if (addon_status('scorm_course')) {
            if ($status != "") {
                $courses = $this->db->get_where('course', array('status' => $status));
            } else {
                $courses['draft'] = $this->db->get_where('course', array('status' => 'draft'));
                $courses['pending'] = $this->db->get_where('course', array('status' => 'pending'));
                $courses['active'] = $this->db->get_where('course', array('status' => 'active'));
            }
        } else {
            if ($status != "") {
                $courses = $this->db->get_where('course', array('status' => $status, 'course_type' => 'general'));
            } else {
                $courses['draft'] = $this->db->get_where('course', array('status' => 'draft', 'course_type' => 'general'));
                $courses['pending'] = $this->db->get_where('course', array('status' => 'pending', 'course_type' => 'general'));
                $courses['active'] = $this->db->get_where('course', array('status' => 'active', 'course_type' => 'general'));
            }
        }
        return $courses;
    }
    

    public function get_status_wise_courses_for_instructor($status = "")
    {
        $multi_instructor_course_ids = $this->multi_instructor_course_ids_for_an_instructor($this->session->userdata('user_id'));

        if ($status != "") {
            $this->db->where('status', $status);
            $this->db->where('user_id', $this->session->userdata('user_id'));
            if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
                $this->db->or_where_in('id', $multi_instructor_course_ids);
            }
            $courses = $this->db->get('course');
        } else {
            $this->db->where('status', 'draft');
            $this->db->where('user_id', $this->session->userdata('user_id'));
            if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
                $this->db->or_where_in('id', $multi_instructor_course_ids);
            }
            $courses['draft'] = $this->db->get('course');

            $this->db->where('status', 'draft');
            $this->db->where('user_id', $this->session->userdata('user_id'));
            if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
                $this->db->or_where_in('id', $multi_instructor_course_ids);
            }
            $courses['pending'] = $this->db->get('course');

            $this->db->where('status', 'draft');
            $this->db->where('user_id', $this->session->userdata('user_id'));
            if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
                $this->db->or_where_in('id', $multi_instructor_course_ids);
            }
            $courses['active'] = $this->db->get('course');
        }
        return $courses;
    }

    public function get_default_sub_category_id($default_cateegory_id)
    {
        $sub_categories = $this->get_sub_categories($default_cateegory_id);
        foreach ($sub_categories as $sub_category) {
            return $sub_category['id'];
        }
    }

    public function get_instructor_wise_courses($instructor_id = "", $return_as = "")
    {
        // GET COURSE IDS FOR MULTI INSTRUCTOR
        $multi_instructor_course_ids = $this->multi_instructor_course_ids_for_an_instructor($instructor_id);

        $this->db->where('user_id', $instructor_id);

        if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
            $this->db->or_where_in('id', $multi_instructor_course_ids);
        }

        $courses = $this->db->get('course');
        if ($return_as == 'simple_array') {
            $array = array();
            foreach ($courses->result_array() as $course) {
                if (!in_array($course['id'], $array)) {
                    array_push($array, $course['id']);
                }
            }
            return $array;
        } else {
            return $courses;
        }
    }

    public function get_instructor_wise_payment_history($instructor_id = "")
    {
        $courses = $this->get_instructor_wise_courses($instructor_id, 'simple_array');
        if (sizeof($courses) > 0) {
            $this->db->where_in('course_id', $courses);
            return $this->db->get('payment')->result_array();
        } else {
            return array();
        }
    }

    public function add_section($course_id)
    {
        $data['title'] = html_escape($this->input->post('title'));
        $data['course_id'] = $course_id;
        $this->db->insert('section', $data);
        $section_id = $this->db->insert_id();

        $course_details = $this->get_course_by_id($course_id)->row_array();
        $previous_sections = json_decode($course_details['section']);

        if (sizeof($previous_sections) > 0) {
            array_push($previous_sections, $section_id);
            $updater['section'] = json_encode($previous_sections);
            $this->db->where('id', $course_id);
            $this->db->update('course', $updater);
        } else {
            $previous_sections = array();
            array_push($previous_sections, $section_id);
            $updater['section'] = json_encode($previous_sections);
            $this->db->where('id', $course_id);
            $this->db->update('course', $updater);
        }
    }

    public function edit_section($section_id)
    {
        $data['title'] = $this->input->post('title');
        $this->db->where('id', $section_id);
        $this->db->update('section', $data);
    }

    public function delete_section($course_id, $section_id)
    {
        $this->db->where('id', $section_id);
        $this->db->delete('section');

        $this->db->where('section_id', $section_id);
        $this->db->delete('lesson');



        $course_details = $this->get_course_by_id($course_id)->row_array();
        $previous_sections = json_decode($course_details['section']);

        if (sizeof($previous_sections) > 0) {
            $new_section = array();
            for ($i = 0; $i < sizeof($previous_sections); $i++) {
                if ($previous_sections[$i] != $section_id) {
                    array_push($new_section, $previous_sections[$i]);
                }
            }
            $updater['section'] = json_encode($new_section);
            $this->db->where('id', $course_id);
            $this->db->update('course', $updater);
        }
    }

    public function get_section($type_by, $id)
    {
        $this->db->order_by("order", "asc");
        if ($type_by == 'course') {
            return $this->db->get_where('section', array('course_id' => $id));
        } elseif ($type_by == 'section') {
            return $this->db->get_where('section', array('id' => $id));
        }
    }

    public function serialize_section($course_id, $serialization)
    {
        $updater = array(
            'section' => $serialization
        );
        $this->db->where('id', $course_id);
        $this->db->update('course', $updater);
    }

    public function add_lesson()
    {

        $data['course_id'] = html_escape($this->input->post('course_id'));
        $data['title'] = html_escape($this->input->post('title'));
        $data['section_id'] = html_escape($this->input->post('section_id'));

        $lesson_type_array = explode('-', $this->input->post('lesson_type'));

        $lesson_type = $lesson_type_array[0];
        $data['lesson_type'] = $lesson_type;

        $attachment_type = $lesson_type_array[1];
        $data['attachment_type'] = $attachment_type;

        if ($lesson_type == 'video') {
            // This portion is for web application's video lesson
            $lesson_provider = $this->input->post('lesson_provider');
            if ($lesson_provider == 'youtube' || $lesson_provider == 'vimeo') {
                if ($this->input->post('video_url') == "" || $this->input->post('duration') == "") {
                    $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_url_and_duration'));
                    redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                }
                $data['video_url'] = html_escape($this->input->post('video_url'));

                $duration_formatter = explode(':', $this->input->post('duration'));
                $hour = sprintf('%02d', $duration_formatter[0]);
                $min = sprintf('%02d', $duration_formatter[1]);
                $sec = sprintf('%02d', $duration_formatter[2]);
                $data['duration'] = $hour . ':' . $min . ':' . $sec;

                $video_details = $this->video_model->getVideoDetails($data['video_url']);
                $data['video_type'] = $video_details['provider'];
            } elseif ($lesson_provider == 'html5') {
                if ($this->input->post('html5_video_url') == "" || $this->input->post('html5_duration') == "") {
                    $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_url_and_duration'));
                    redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                }
                $data['video_url'] = html_escape($this->input->post('html5_video_url'));
                $duration_formatter = explode(':', $this->input->post('html5_duration'));
                $hour = sprintf('%02d', $duration_formatter[0]);
                $min = sprintf('%02d', $duration_formatter[1]);
                $sec = sprintf('%02d', $duration_formatter[2]);
                $data['duration'] = $hour . ':' . $min . ':' . $sec;
                $data['video_type'] = 'html5';
            } elseif ($lesson_provider == 'google_drive') {
                if ($this->input->post('google_drive_video_url') == "" || $this->input->post('google_drive_video_duration') == "") {
                    $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_url_and_duration'));
                    redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                }
                $data['video_url'] = html_escape($this->input->post('google_drive_video_url'));
                $duration_formatter = explode(':', $this->input->post('google_drive_video_duration'));
                $hour = sprintf('%02d', $duration_formatter[0]);
                $min = sprintf('%02d', $duration_formatter[1]);
                $sec = sprintf('%02d', $duration_formatter[2]);
                $data['duration'] = $hour . ':' . $min . ':' . $sec;
                $data['video_type'] = 'google_drive';
            } else {
                $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_provider'));
                redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
            }

            // This portion is for mobile application video lessons
            if ($this->input->post('html5_video_url_for_mobile_application') == "" || $this->input->post('html5_duration_for_mobile_application') == "") {
                $mobile_app_lesson_url = "https://www.html5rocks.com/en/tutorials/video/basics/devstories.webm";
                $mobile_app_lesson_duration = "00:01:10";
            } else {
                $mobile_app_lesson_url = $this->input->post('html5_video_url_for_mobile_application');
                $mobile_app_lesson_duration = $this->input->post('html5_duration_for_mobile_application');
            }
            $duration_for_mobile_application_formatter = explode(':', $mobile_app_lesson_duration);
            $hour = sprintf('%02d', $duration_for_mobile_application_formatter[0]);
            $min  = sprintf('%02d', $duration_for_mobile_application_formatter[1]);
            $sec  = sprintf('%02d', $duration_for_mobile_application_formatter[2]);
            $data['duration_for_mobile_application'] = $hour . ':' . $min . ':' . $sec;
            $data['video_type_for_mobile_application'] = 'html5';
            $data['video_url_for_mobile_application'] = $mobile_app_lesson_url;
        } elseif ($lesson_type == "s3") {
            // SET MAXIMUM EXECUTION TIME 600
            ini_set('max_execution_time', '600');

            $fileName           = $_FILES['video_file_for_amazon_s3']['name'];
            $tmp                = explode('.', $fileName);
            $fileExtension      = strtoupper(end($tmp));

            $video_extensions = ['WEBM', 'MP4'];
            if (!in_array($fileExtension, $video_extensions)) {
                $this->session->set_flashdata('error_message', get_phrase('please_select_valid_video_file'));
                redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
            }

            if ($this->input->post('amazon_s3_duration') == "") {
                $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_duration'));
                redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
            }

            $upload_loaction = get_settings('video_upload_location');
            $access_key = get_settings('amazon_s3_access_key');
            $secret_key = get_settings('amazon_s3_secret_key');
            $bucket = get_settings('amazon_s3_bucket_name');
            $region = get_settings('amazon_s3_region_name');

            $s3config = array(
                'region'  => $region,
                'version' => 'latest',
                'credentials' => [
                    'key'    => $access_key, //Put key here
                    'secret' => $secret_key // Put Secret here
                ]
            );


            $tmpfile = $_FILES['video_file_for_amazon_s3'];

            $s3 = new Aws\S3\S3Client($s3config);
            $key = str_replace(".", "-" . rand(1, 9999) . ".", $tmpfile['name']);

            $result = $s3->putObject([
                'Bucket' => $bucket,
                'Key'    => $key,
                'SourceFile' => $tmpfile['tmp_name'],
                'ACL'   => 'public-read'
            ]);

            $data['video_url'] = $result['ObjectURL'];
            $data['video_type'] = 'amazon';
            $data['lesson_type'] = 'video';
            $data['attachment_type'] = 'file';

            $duration_formatter = explode(':', $this->input->post('amazon_s3_duration'));
            $hour = sprintf('%02d', $duration_formatter[0]);
            $min = sprintf('%02d', $duration_formatter[1]);
            $sec = sprintf('%02d', $duration_formatter[2]);
            $data['duration'] = $hour . ':' . $min . ':' . $sec;

            $data['duration_for_mobile_application'] = $hour . ':' . $min . ':' . $sec;
            $data['video_type_for_mobile_application'] = "html5";
            $data['video_url_for_mobile_application'] = $result['ObjectURL'];
        } elseif ($lesson_type == "system") {
            // SET MAXIMUM EXECUTION TIME 600
            ini_set('max_execution_time', '600');

            $fileName           = $_FILES['system_video_file']['name'];

            // CHECKING IF THE FILE IS AVAILABLE AND FILE SIZE IS VALID
            if (array_key_exists('system_video_file', $_FILES)) {
                if ($_FILES['system_video_file']['error'] !== UPLOAD_ERR_OK) {
                    $error_code = $_FILES['system_video_file']['error'];
                    $this->session->set_flashdata('error_message', phpFileUploadErrors($error_code));
                    redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                }
            } else {
                $this->session->set_flashdata('error_message', get_phrase('please_select_valid_video_file'));
                redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
            };

            $tmp                = explode('.', $fileName);
            $fileExtension      = strtoupper(end($tmp));

            $video_extensions = ['WEBM', 'MP4'];

            if (!in_array($fileExtension, $video_extensions)) {
                $this->session->set_flashdata('error_message', get_phrase('please_select_valid_video_file'));
                redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
            }

            // custom random name of the video file
            $uploadable_video_file    =  md5(uniqid(rand(), true)) . '.' . strtolower($fileExtension);

            if ($this->input->post('system_video_file_duration') == "") {
                $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_duration'));
                redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
            }



            $tmp_video_file = $_FILES['system_video_file']['tmp_name'];

            if (!file_exists('uploads/lesson_files/videos')) {
                mkdir('uploads/lesson_files/videos', 0777, true);
            }
            $video_file_path = 'uploads/lesson_files/videos/' . $uploadable_video_file;
            move_uploaded_file($tmp_video_file, $video_file_path);
            $data['video_url'] = site_url($video_file_path);
            $data['video_type'] = 'system';
            $data['lesson_type'] = 'video';
            $data['attachment_type'] = 'file';

            $duration_formatter = explode(':', $this->input->post('system_video_file_duration'));
            $hour = sprintf('%02d', $duration_formatter[0]);
            $min = sprintf('%02d', $duration_formatter[1]);
            $sec = sprintf('%02d', $duration_formatter[2]);
            $data['duration'] = $hour . ':' . $min . ':' . $sec;

            $data['duration_for_mobile_application'] = $hour . ':' . $min . ':' . $sec;
            $data['video_type_for_mobile_application'] = "html5";
            $data['video_url_for_mobile_application'] = site_url($video_file_path);
        }elseif($lesson_type == 'text' && $attachment_type == 'description'){
            $data['attachment'] = htmlspecialchars($this->input->post('text_description'));
        } else {
            if ($attachment_type == 'iframe') {
                if (empty($this->input->post('iframe_source'))) {
                    $this->session->set_flashdata('error_message', get_phrase('invalid_source'));
                    redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                }
                $data['attachment'] = $this->input->post('iframe_source');
            } else {
                if ($_FILES['attachment']['name'] == "") {
                    $this->session->set_flashdata('error_message', get_phrase('invalid_attachment'));
                    redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                } else {
                    $fileName           = $_FILES['attachment']['name'];
                    $tmp                = explode('.', $fileName);
                    $fileExtension      = end($tmp);
                    $uploadable_file    =  md5(uniqid(rand(), true)) . '.' . $fileExtension;
                    $data['attachment'] = $uploadable_file;

                    if (!file_exists('uploads/lesson_files')) {
                        mkdir('uploads/lesson_files', 0777, true);
                    }
                    move_uploaded_file($_FILES['attachment']['tmp_name'], 'uploads/lesson_files/' . $uploadable_file);
                }
            }
        }

        $data['date_added'] = strtotime(date('D, d-M-Y'));
        $data['summary'] = htmlspecialchars($this->input->post('summary'));
        $data['is_free'] = htmlspecialchars($this->input->post('free_lesson'));


        $this->db->insert('lesson', $data);
        $inserted_id = $this->db->insert_id();

        if ($_FILES['thumbnail']['name'] != "") {
            if (!file_exists('uploads/thumbnails/lesson_thumbnails')) {
                mkdir('uploads/thumbnails/lesson_thumbnails', 0777, true);
            }
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], 'uploads/thumbnails/lesson_thumbnails/' . $inserted_id . '.jpg');
        }
    }

    public function edit_lesson($lesson_id)
    {

        $previous_data = $this->db->get_where('lesson', array('id' => $lesson_id))->row_array();

        $data['course_id'] = html_escape($this->input->post('course_id'));
        $data['title'] = html_escape($this->input->post('title'));
        $data['section_id'] = html_escape($this->input->post('section_id'));

        $lesson_type_array = explode('-', $this->input->post('lesson_type'));

        $lesson_type = $lesson_type_array[0];
        $data['lesson_type'] = $lesson_type;

        $attachment_type = $lesson_type_array[1];
        $data['attachment_type'] = $attachment_type;

        if ($lesson_type == 'video') {
            $lesson_provider = $this->input->post('lesson_provider');
            if ($lesson_provider == 'youtube' || $lesson_provider == 'vimeo') {
                if ($this->input->post('video_url') == "" || $this->input->post('duration') == "") {
                    $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_url_and_duration'));
                    redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                }
                $data['video_url'] = html_escape($this->input->post('video_url'));

                $duration_formatter = explode(':', $this->input->post('duration'));
                $hour = sprintf('%02d', $duration_formatter[0]);
                $min = sprintf('%02d', $duration_formatter[1]);
                $sec = sprintf('%02d', $duration_formatter[2]);
                $data['duration'] = $hour . ':' . $min . ':' . $sec;

                $video_details = $this->video_model->getVideoDetails($data['video_url']);
                $data['video_type'] = $video_details['provider'];
            } elseif ($lesson_provider == 'html5') {
                if ($this->input->post('html5_video_url') == "" || $this->input->post('html5_duration') == "") {
                    $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_url_and_duration'));
                    redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                }
                $data['video_url'] = html_escape($this->input->post('html5_video_url'));

                $duration_formatter = explode(':', $this->input->post('html5_duration'));
                $hour = sprintf('%02d', $duration_formatter[0]);
                $min = sprintf('%02d', $duration_formatter[1]);
                $sec = sprintf('%02d', $duration_formatter[2]);
                $data['duration'] = $hour . ':' . $min . ':' . $sec;
                $data['video_type'] = 'html5';

                if ($_FILES['thumbnail']['name'] != "") {
                    if (!file_exists('uploads/thumbnails/lesson_thumbnails')) {
                        mkdir('uploads/thumbnails/lesson_thumbnails', 0777, true);
                    }
                    move_uploaded_file($_FILES['thumbnail']['tmp_name'], 'uploads/thumbnails/lesson_thumbnails/' . $lesson_id . '.jpg');
                }
            } elseif ($lesson_provider == 'google_drive') {
                if ($this->input->post('google_drive_video_url') == "" || $this->input->post('google_drive_video_duration') == "") {
                    $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_url_and_duration'));
                    redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                }
                $data['video_url'] = html_escape($this->input->post('google_drive_video_url'));
                $duration_formatter = explode(':', $this->input->post('google_drive_video_duration'));
                $hour = sprintf('%02d', $duration_formatter[0]);
                $min = sprintf('%02d', $duration_formatter[1]);
                $sec = sprintf('%02d', $duration_formatter[2]);
                $data['duration'] = $hour . ':' . $min . ':' . $sec;
                $data['video_type'] = 'google_drive';
            } else {
                $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_provider'));
                redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
            }
            $data['attachment'] = "";

            // This portion is for mobile application video lessons
            if ($this->input->post('html5_video_url_for_mobile_application') == "" || $this->input->post('html5_duration_for_mobile_application') == "") {
                $mobile_app_lesson_url = "https://www.html5rocks.com/en/tutorials/video/basics/devstories.webm";
                $mobile_app_lesson_duration = "00:01:10";
            } else {
                $mobile_app_lesson_url = $this->input->post('html5_video_url_for_mobile_application');
                $mobile_app_lesson_duration = $this->input->post('html5_duration_for_mobile_application');
            }
            $duration_for_mobile_application_formatter = explode(':', $mobile_app_lesson_duration);
            $hour = sprintf('%02d', $duration_for_mobile_application_formatter[0]);
            $min  = sprintf('%02d', $duration_for_mobile_application_formatter[1]);
            $sec  = sprintf('%02d', $duration_for_mobile_application_formatter[2]);
            $data['duration_for_mobile_application'] = $hour . ':' . $min . ':' . $sec;
            $data['video_type_for_mobile_application'] = 'html5';
            $data['video_url_for_mobile_application'] = $mobile_app_lesson_url;
        } elseif ($lesson_type == "s3") {
            // SET MAXIMUM EXECUTION TIME 600
            ini_set('max_execution_time', '600');

            if (isset($_FILES['video_file_for_amazon_s3']) && !empty($_FILES['video_file_for_amazon_s3']['name'])) {
                $fileName           = $_FILES['video_file_for_amazon_s3']['name'];
                $tmp                = explode('.', $fileName);
                $fileExtension      = strtoupper(end($tmp));

                $video_extensions = ['WEBM', 'MP4'];
                if (!in_array($fileExtension, $video_extensions)) {
                    $this->session->set_flashdata('error_message', get_phrase('please_select_valid_video_file'));
                    redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                }

                $upload_loaction = get_settings('video_upload_location');
                $access_key = get_settings('amazon_s3_access_key');
                $secret_key = get_settings('amazon_s3_secret_key');
                $bucket = get_settings('amazon_s3_bucket_name');
                $region = get_settings('amazon_s3_region_name');

                $s3config = array(
                    'region'  => $region,
                    'version' => 'latest',
                    'credentials' => [
                        'key'    => $access_key, //Put key here
                        'secret' => $secret_key // Put Secret here
                    ]
                );


                $tmpfile = $_FILES['video_file_for_amazon_s3'];

                $s3 = new Aws\S3\S3Client($s3config);
                $key = str_replace(".", "-" . rand(1, 9999) . ".", preg_replace('/\s+/', '', $tmpfile['name']));

                $result = $s3->putObject([
                    'Bucket' => $bucket,
                    'Key'    => $key,
                    'SourceFile' => $tmpfile['tmp_name'],
                    'ACL'   => 'public-read'
                ]);

                $data['video_url'] = $result['ObjectURL'];
                $data['video_url_for_mobile_application'] = $result['ObjectURL'];
            }

            $data['video_type'] = 'amazon';
            $data['lesson_type'] = 'video';
            $data['attachment_type'] = 'file';


            if ($this->input->post('amazon_s3_duration') == "") {
                $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_duration'));
                redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
            }

            $duration_formatter = explode(':', $this->input->post('amazon_s3_duration'));
            $hour = sprintf('%02d', $duration_formatter[0]);
            $min = sprintf('%02d', $duration_formatter[1]);
            $sec = sprintf('%02d', $duration_formatter[2]);
            $data['duration'] = $hour . ':' . $min . ':' . $sec;

            $data['duration_for_mobile_application'] = $hour . ':' . $min . ':' . $sec;
            $data['video_type_for_mobile_application'] = "html5";
        } elseif ($lesson_type == "system") {
            // SET MAXIMUM EXECUTION TIME 600
            ini_set('max_execution_time', '600');

            if (isset($_FILES['system_video_file']) && !empty($_FILES['system_video_file']['name'])) {
                //delete previews video
                $previews_video_url = $this->db->get_where('lesson', array('id' => $lesson_id))->row('video_url');
                $video_file = explode('/', $previews_video_url);
                unlink('uploads/lesson_files/videos/' . end($video_file));
                //end delete previews video

                $fileName           = $_FILES['system_video_file']['name'];

                // CHECKING IF THE FILE IS AVAILABLE AND FILE SIZE IS VALID
                if (array_key_exists('system_video_file', $_FILES)) {
                    if ($_FILES['system_video_file']['error'] !== UPLOAD_ERR_OK) {
                        $error_code = $_FILES['system_video_file']['error'];
                        $this->session->set_flashdata('error_message', phpFileUploadErrors($error_code));
                        redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                    }
                } else {
                    $this->session->set_flashdata('error_message', get_phrase('please_select_valid_video_file'));
                    redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                };

                $tmp                = explode('.', $fileName);
                $fileExtension      = strtoupper(end($tmp));

                $video_extensions = ['WEBM', 'MP4'];
                if (!in_array($fileExtension, $video_extensions)) {
                    $this->session->set_flashdata('error_message', get_phrase('please_select_valid_video_file'));
                    redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                }

                // custom random name of the video file
                $uploadable_video_file    =  md5(uniqid(rand(), true)) . '.' . strtolower($fileExtension);


                $tmp_video_file = $_FILES['system_video_file']['tmp_name'];

                if (!file_exists('uploads/lesson_files/videos')) {
                    mkdir('uploads/lesson_files/videos', 0777, true);
                }
                $video_file_path = 'uploads/lesson_files/videos/' . $uploadable_video_file;
                move_uploaded_file($tmp_video_file, $video_file_path);

                $data['video_url'] = site_url($video_file_path);
                $data['video_url_for_mobile_application'] = site_url($video_file_path);
            }

            $data['video_type'] = 'system';
            $data['lesson_type'] = 'video';
            $data['attachment_type'] = 'file';


            if ($this->input->post('system_video_file_duration') == "") {
                $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_duration'));
                redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
            }

            $duration_formatter = explode(':', $this->input->post('system_video_file_duration'));
            $hour = sprintf('%02d', $duration_formatter[0]);
            $min = sprintf('%02d', $duration_formatter[1]);
            $sec = sprintf('%02d', $duration_formatter[2]);
            $data['duration'] = $hour . ':' . $min . ':' . $sec;

            $data['duration_for_mobile_application'] = $hour . ':' . $min . ':' . $sec;
            $data['video_type_for_mobile_application'] = "html5";

        }elseif($lesson_type == 'text' && $attachment_type == 'description'){
            $data['attachment'] = htmlspecialchars($this->input->post('text_description'));
        } else {
            if ($attachment_type == 'iframe') {
                if (empty($this->input->post('iframe_source'))) {
                    $this->session->set_flashdata('error_message', get_phrase('invalid_source'));
                    redirect(site_url(strtolower($this->session->userdata('role')) . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                }
                $data['attachment'] = $this->input->post('iframe_source');
            } else {
                if ($_FILES['attachment']['name'] != "") {
                    // unlinking previous attachments
                    if ($previous_data['attachment'] != "") {
                        unlink('uploads/lesson_files/' . $previous_data['attachment']);
                    }

                    $fileName           = $_FILES['attachment']['name'];
                    $tmp                = explode('.', $fileName);
                    $fileExtension      = end($tmp);
                    $uploadable_file    =  md5(uniqid(rand(), true)) . '.' . $fileExtension;
                    $data['attachment'] = $uploadable_file;
                    $data['video_type'] = "";
                    $data['duration'] = "";
                    $data['video_url'] = "";
                    $data['duration_for_mobile_application'] = "";
                    $data['video_type_for_mobile_application'] = '';
                    $data['video_url_for_mobile_application'] = "";
                    if (!file_exists('uploads/lesson_files')) {
                        mkdir('uploads/lesson_files', 0777, true);
                    }
                    move_uploaded_file($_FILES['attachment']['tmp_name'], 'uploads/lesson_files/' . $uploadable_file);
                }
            }
        }

        $data['last_modified'] = strtotime(date('D, d-M-Y'));
        $data['summary'] = htmlspecialchars($this->input->post('summary'));
        $data['is_free'] = htmlspecialchars($this->input->post('free_lesson'));

        $this->db->where('id', $lesson_id);
        $this->db->update('lesson', $data);
    }

    public function delete_lesson($lesson_id)
    {
        $this->db->where('id', $lesson_id);
        $this->db->delete('lesson');
    }

    public function update_frontend_settings()
    {
        $data['value'] = html_escape($this->input->post('banner_title'));
        $this->db->where('key', 'banner_title');
        $this->db->update('frontend_settings', $data);

        $data['value'] = html_escape($this->input->post('banner_sub_title'));
        $this->db->where('key', 'banner_sub_title');
        $this->db->update('frontend_settings', $data);

        $data['value'] = html_escape($this->input->post('cookie_status'));
        $this->db->where('key', 'cookie_status');
        $this->db->update('frontend_settings', $data);

        $data['value'] = $this->input->post('cookie_note');
        $this->db->where('key', 'cookie_note');
        $this->db->update('frontend_settings', $data);

        $data['value'] = $this->input->post('cookie_policy');
        $this->db->where('key', 'cookie_policy');
        $this->db->update('frontend_settings', $data);



        $data['value'] = $this->input->post('facebook');
        $this->db->where('key', 'facebook');
        $this->db->update('frontend_settings', $data);

        $data['value'] = $this->input->post('twitter');
        $this->db->where('key', 'twitter');
        $this->db->update('frontend_settings', $data);

        $data['value'] = $this->input->post('linkedin');
        $this->db->where('key', 'linkedin');
        $this->db->update('frontend_settings', $data);


        $data['value'] = $this->input->post('about_us');
        $this->db->where('key', 'about_us');
        $this->db->update('frontend_settings', $data);

        $data['value'] = $this->input->post('terms_and_condition');
        $this->db->where('key', 'terms_and_condition');
        $this->db->update('frontend_settings', $data);

        $data['value'] = $this->input->post('privacy_policy');
        $this->db->where('key', 'privacy_policy');
        $this->db->update('frontend_settings', $data);

        $data['value'] = $this->input->post('refund_policy');
        $this->db->where('key', 'refund_policy');
        $this->db->update('frontend_settings', $data);
    }

    public function update_recaptcha_settings()
    {
        $data['value'] = html_escape($this->input->post('recaptcha_status'));
        $this->db->where('key', 'recaptcha_status');
        $this->db->update('frontend_settings', $data);

        $data['value'] = html_escape($this->input->post('recaptcha_sitekey'));
        $this->db->where('key', 'recaptcha_sitekey');
        $this->db->update('frontend_settings', $data);

        $data['value'] = html_escape($this->input->post('recaptcha_secretkey'));
        $this->db->where('key', 'recaptcha_secretkey');
        $this->db->update('frontend_settings', $data);
    }

    public function update_frontend_banner()
    {
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['name'] != "") {
            unlink('uploads/system/' . get_frontend_settings('banner_image'));
            $data['value'] = md5(rand(1000, 100000)) . '.jpg';
            $this->db->where('key', 'banner_image');
            $this->db->update('frontend_settings', $data);
            move_uploaded_file($_FILES['banner_image']['tmp_name'], 'uploads/system/' . $data['value']);
        }
    }

    public function update_light_logo()
    {
        if (isset($_FILES['light_logo']) && $_FILES['light_logo']['name'] != "") {
            unlink('uploads/system/' . get_frontend_settings('light_logo'));
            $data['value'] = md5(rand(1000, 100000)) . '.png';
            $this->db->where('key', 'light_logo');
            $this->db->update('frontend_settings', $data);
            move_uploaded_file($_FILES['light_logo']['tmp_name'], 'uploads/system/' . $data['value']);
        }
    }

    public function update_dark_logo()
    {
        if (isset($_FILES['dark_logo']) && $_FILES['dark_logo']['name'] != "") {
            unlink('uploads/system/' . get_frontend_settings('dark_logo'));
            $data['value'] = md5(rand(1000, 100000)) . '.png';
            $this->db->where('key', 'dark_logo');
            $this->db->update('frontend_settings', $data);
            move_uploaded_file($_FILES['dark_logo']['tmp_name'], 'uploads/system/' . $data['value']);
        }
    }

    public function update_small_logo()
    {
        if (isset($_FILES['small_logo']) && $_FILES['small_logo']['name'] != "") {
            unlink('uploads/system/' . get_frontend_settings('small_logo'));
            $data['value'] = md5(rand(1000, 100000)) . '.png';
            $this->db->where('key', 'small_logo');
            $this->db->update('frontend_settings', $data);
            move_uploaded_file($_FILES['small_logo']['tmp_name'], 'uploads/system/' . $data['value']);
        }
    }

    public function update_favicon()
    {
        if (isset($_FILES['favicon']) && $_FILES['favicon']['name'] != "") {
            unlink('uploads/system/' . get_frontend_settings('favicon'));
            $data['value'] = md5(rand(1000, 100000)) . '.png';
            $this->db->where('key', 'favicon');
            $this->db->update('frontend_settings', $data);
            move_uploaded_file($_FILES['favicon']['tmp_name'], 'uploads/system/' . $data['value']);
        }
        //move_uploaded_file($_FILES['favicon']['tmp_name'], 'uploads/system/favicon.png');
    }

    public function handleWishList($course_id)
    {
        $wishlists = array();
        $user_details = $this->user_model->get_user($this->session->userdata('user_id'))->row_array();
        if ($user_details['wishlist'] == "") {
            array_push($wishlists, $course_id);
        } else {
            $wishlists = json_decode($user_details['wishlist']);
            if (in_array($course_id, $wishlists)) {
                $container = array();
                foreach ($wishlists as $key) {
                    if ($key != $course_id) {
                        array_push($container, $key);
                    }
                }
                $wishlists = $container;
                // $key = array_search($course_id, $wishlists);
                // unset($wishlists[$key]);
            } else {
                array_push($wishlists, $course_id);
            }
        }

        $updater['wishlist'] = json_encode($wishlists);
        $this->db->where('id', $this->session->userdata('user_id'));
        $this->db->update('users', $updater);
    }

    public function is_added_to_wishlist($course_id = "")
    {
        if ($this->session->userdata('user_login') == 1) {
            $wishlists = array();
            $user_details = $this->user_model->get_user($this->session->userdata('user_id'))->row_array();
            $wishlists = json_decode($user_details['wishlist']);
            if (in_array($course_id, $wishlists)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getWishLists($user_id = "")
    {
        if ($user_id == "") {
            $user_id = $this->session->userdata('user_id');
        }
        $user_details = $this->user_model->get_user($user_id)->row_array();
        return json_decode($user_details['wishlist']);
    }

    public function get_latest_10_course()
    {
        return  $query = $this->db
            ->select("course.*")
            ->from ("course")
            ->join('rating', 'rating.ratable_id = course.id')
            ->order_by("course.id", "desc")
            ->limit('10')
            ->where('rating', 5)
            ->where('status', 'active')
            ->get()->result_array();
            // print_r($query); die();
        // if (addon_status('scorm_course')) {
        //     $this->db->where('course_type', 'general');
        // }
        // $this->db->order_by("id", "desc");
        // $this->db->limit('10');
        // $this->db->where('status', 'active');
        // return $this->db->get('course')->result_array();
    }

    public function get_future_courses()
    {
        if (addon_status('scorm_course')) {
            $this->db->where('course_type', 'general');
        }
        $this->db->order_by("id", "desc");
        $this->db->limit('10');
        $this->db->where('status', 'active');
        $this->db->where('future_course', '1');
        return $this->db->get('course')->result_array();
    }

    public function enrol_student($user_id)
    {
        $purchased_courses = $this->session->userdata('cart_items');
        foreach ($purchased_courses as $purchased_course) {
            $data['user_id'] = $user_id;
            $data['course_id'] = $purchased_course;
            $data['date_added'] = strtotime(date('D, d-M-Y'));
            $this->db->insert('enrol', $data);
        }
    }
    public function enrol_a_student_manually()
    {
        $data['course_id'] = $this->input->post('course_id');
        $data['user_id']   = $this->input->post('user_id');
        if ($this->db->get_where('enrol', $data)->num_rows() > 0) {
            $this->session->set_flashdata('error_message', get_phrase('student_has_already_been_enrolled_to_this_course'));
        } else {
            $get_login = $this->api_model->login_go1();
            $get_login_decode = json_decode($get_login);
   
        if(isset($get_login_decode->access_token)) {
            $course_details = $this->get_course_by_id($data['course_id'])->row_array();
            $user_data= $this->user_model->get_user($data['user_id'])->row_array();
            $enrol_add =   $this->api_model->enrol_Add($get_login_decode->access_token,$user_data['go1_id'],$course_details['api_id']);
            $enrol_add_decode = json_decode($enrol_add);
            // print_r($enrol_add_decode); die();
            $data['enrol_go1_id'] = $enrol_add_decode->id;
        }
            $data['enrol_last_date'] = strtotime($this->input->post('enrol_last_date'));
            $data['date_added'] = strtotime(date('D, d-M-Y'));
            $this->db->insert('enrol', $data);
            $this->email_model->send_email_course_assign_to_student_manually($data['user_id'],$data['course_id']);
            $this->session->set_flashdata('flash_message', get_phrase('student_has_been_enrolled_to_that_course'));
        }
    }

    public function enrol_a_student_by_request($id)
    {
        $this->db->where('id', $id);
        $resut =  $this->db->get('enrolment_request')->row_array();

        $data['course_id'] = $resut['course_id'];
        $data['user_id']   = $resut['user_id'];
        if ($this->db->get_where('enrol', $data)->num_rows() > 0) {
            $this->session->set_flashdata('error_message', get_phrase('student_has_already_been_enrolled_to_this_course'));
        } else {
            $get_login = $this->api_model->login_go1();
            $get_login_decode = json_decode($get_login);
   
        if(isset($get_login_decode->access_token)) {
            $course_details = $this->get_course_by_id($data['course_id'])->row_array();
            $user_data= $this->user_model->get_user($data['user_id'])->row_array();
            $enrol_add =   $this->api_model->enrol_Add($get_login_decode->access_token,$user_data['go1_id'],$course_details['api_id']);
            $enrol_add_decode = json_decode($enrol_add);
            // print_r($enrol_add_decode); die();
            $data['enrol_go1_id'] = $enrol_add_decode->id;
        }
            $data['date_added'] = strtotime(date('D, d-M-Y'));
            $this->db->insert('enrol', $data);
            $status = ['status'=>1];
            $checker = array('id' => $id);
            $this->db->where($checker);
            $this->db->update('enrolment_request', $status);
            $this->email_model->send_email_req_accept_user_enrolment($data['user_id'],$data['course_id']);
            $this->session->set_flashdata('flash_message', get_phrase('student_has_been_enrolled_to_that_course'));
        }
    }

    public function shortcut_enrol_a_student_manually()
    {
        $data['course_id'] = $this->input->post('course_id');
        $user_id = $this->input->post('user_id');
       
        foreach($user_id as $user) {    
            $data['user_id'] = $user;
            
                if ($this->db->get_where('enrol', $data)->num_rows() < 1) {
                   
                    $get_login = $this->api_model->login_go1();
                    $get_login_decode = json_decode($get_login);
        
                        if(isset($get_login_decode->access_token)) {
                            $course_details = $this->get_course_by_id($data['course_id'])->row_array();
                            $user_data= $this->user_model->get_user($data['user_id'])->row_array();
                            $enrol_add =   $this->api_model->enrol_Add($get_login_decode->access_token,$user_data['go1_id'],$course_details['api_id']);
                            $enrol_add_decode = json_decode($enrol_add);
                        //  print_r($enrol_add_decode); die();
                            $data['enrol_go1_id'] = $enrol_add_decode->id;
                        }
                    $data['enrol_last_date'] = strtotime($this->input->post('enrol_last_date'));
                    $data['date_added'] = strtotime(date('D, d-M-Y'));
                    // print_r($data['user_id']); exit;
                    $this->db->insert('enrol', $data);
                    $this->session->set_flashdata('flash_message', get_phrase('student_has_been_enrolled_to_that_course'));
                }
               
            }
           
            $this->email_model->send_email_shortcut_enrol_a_student_manually($user_id, $data['course_id']);
            $response['status'] = 1;
            return json_encode($response); 
    }

    public function enrol_to_free_course($course_id = "", $user_id = "")
    {
        $course_details = $this->get_course_by_id($course_id)->row_array();
        if ($course_details['is_free_course'] == 1) {
            $data['course_id'] = $course_id;
            $data['user_id']   = $user_id;
            if ($this->db->get_where('enrol', $data)->num_rows() > 0) {
                $this->session->set_flashdata('error_message', get_phrase('student_has_already_been_enrolled_to_this_course'));
            } else {
                $get_login = $this->api_model->login_go1();
                $get_login_decode = json_decode($get_login);
   
                if(isset($get_login_decode->access_token)) {
                    $course_details = $this->get_course_by_id($data['course_id'])->row_array();
                    $user_data= $this->user_model->get_user($data['user_id'])->row_array();
                    $enrol_add =   $this->api_model->enrol_Add($get_login_decode->access_token,$user_data['go1_id'],$course_details['api_id']);
                    $enrol_add_decode = json_decode($enrol_add);
                    // print_r($enrol_add_decode); die();
                    $data['enrol_go1_id'] = $enrol_add_decode->id;
                }

                $data['date_added'] = strtotime(date('D, d-M-Y'));
                $data['enrol_last_date'] = strtotime($this->input->post('enrol_last_date'));
                $this->db->insert('enrol', $data);
                $this->session->set_flashdata('flash_message', get_phrase('successfully_enrolled'));
            }
        } else {
            $this->session->set_flashdata('error_message', get_phrase('this_course_is_not_free_at_all'));
            redirect(site_url('home/course/' . slugify($course_details['title']) . '/' . $course_id), 'refresh');
        }
    }
    public function company_user_enrolment($course_id = "", $user_id = "" , $company_id = "")
    {
        $course_details = $this->get_course_by_id($course_id)->row_array();
        
            $data['course_id'] = $course_id;
            $data['user_id']   = $user_id;
            if($company_id != "") {
            $data['company_id']   = $company_id;
            } else {
                $data['company_id']   = 1; 
            }
            
            if ($this->db->get_where('enrolment_request', $data)->num_rows() > 0) {
                $this->session->set_flashdata('error_message', get_phrase('student_has_already_sent_request_to_enrolled_this_course'));
            } else {
                $data['dated_request'] = strtotime(date('D, d-M-Y'));
                $this->db->insert('enrolment_request', $data);
                $this->email_model->send_email_company_user_enrolment($data['user_id'],$data['course_id'], $data['company_id']);
                $this->session->set_flashdata('flash_message', get_phrase('successfully__sent_enrolled_request'));
            }
       
    }
    public function course_purchase($user_id, $method, $amount_paid, $param1 = "", $param2 = "")
    {
        $purchased_courses = $this->session->userdata('cart_items');
        $applied_coupon = $this->session->userdata('applied_coupon');

        foreach ($purchased_courses as $purchased_course) {

            if ($method == 'stripe') {
                //param1 transaction_id, param2 session_id for stripe
                $data['transaction_id'] = $param1;
                $data['session_id'] = $param2;
            }

            if ($method == 'razorpay') {
                //param1 payment keys
                $data['transaction_id'] = $param1;
            }

            $data['user_id'] = $user_id;
            $data['payment_type'] = $method;
            $data['course_id'] = $purchased_course;
            $course_details = $this->get_course_by_id($purchased_course)->row_array();

            if ($course_details['discount_flag'] == 1) {
                $data['amount'] = $course_details['discounted_price'];
            } else {
                $data['amount'] = $course_details['price'];
            }

            // CHECK IF USER HAS APPLIED ANY COUPON CODE
            if ($applied_coupon) {
                $coupon_details = $this->get_coupon_details_by_code($applied_coupon)->row_array();
                $discount = ($data['amount'] * $coupon_details['discount_percentage']) / 100;
                $data['amount'] = $data['amount'] - $discount;
                $data['coupon'] = $applied_coupon;
            }

            if (get_user_role('role_id', $course_details['creator']) == 1) {
                $data['admin_revenue'] = $data['amount'];
                $data['instructor_revenue'] = 0;
                $data['instructor_payment_status'] = 1;
            } else {
                if (get_settings('allow_instructor') == 1) {
                    $instructor_revenue_percentage = get_settings('instructor_revenue');
                    $data['instructor_revenue'] = ceil(($data['amount'] * $instructor_revenue_percentage) / 100);
                    $data['admin_revenue'] = $data['amount'] - $data['instructor_revenue'];
                } else {
                    $data['instructor_revenue'] = 0;
                    $data['admin_revenue'] = $data['amount'];
                }
                $data['instructor_payment_status'] = 0;
            }
            $data['date_added'] = strtotime(date('D, d-M-Y'));
            $this->db->insert('payment', $data);
        }
    }

    public function get_default_lesson($section_id)
    {
        $this->db->order_by('order', "asc");
        $this->db->limit(1);
        $this->db->where('section_id', $section_id);
        return $this->db->get('lesson');
    }

    public function get_courses_by_wishlists()
    {
        $wishlists = $this->getWishLists();
        if (sizeof($wishlists) > 0) {
            $this->db->where_in('id', $wishlists);
            return $this->db->get('course')->result_array();
        } else {
            return array();
        }
    }


    public function get_courses_of_wishlists_by_search_string($search_string)
    {
        $wishlists = $this->getWishLists();
        if (sizeof($wishlists) > 0) {
            $this->db->where_in('id', $wishlists);
            $this->db->like('title', $search_string);
            return $this->db->get('course')->result_array();
        } else {
            return array();
        }
    }

    public function get_total_duration_of_lesson_by_course_id($course_id)
    {
        $total_duration = 0;
        $lessons = $this->crud_model->get_lessons('course', $course_id)->result_array();
        foreach ($lessons as $lesson) {
            if ($lesson['lesson_type'] != "other" && $lesson['lesson_type'] != "text") {
                if($lesson['duration'] != "" ) {
                $time_array = explode(':', $lesson['duration']);
                $hour_to_seconds = $time_array[0] * 60 * 60;
                $minute_to_seconds = $time_array[1] * 60;
                $seconds = $time_array[2];
                $total_duration += $hour_to_seconds + $minute_to_seconds + $seconds;
                }
            }
        }
        // return gmdate("H:i:s", $total_duration).' '.get_phrase('hours');
        $hours = floor($total_duration / 3600);
        $minutes = floor(($total_duration % 3600) / 60);
        $seconds = $total_duration % 60;
        if( sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds) == "00:00:00") {
            return "";
        } else {
          return   sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds) . ' ' . get_phrase('hours');
        }
    }

    public function get_total_duration_of_lesson_by_section_id($section_id)
    {
        $total_duration = 0;
        $lessons = $this->crud_model->get_lessons('section', $section_id)->result_array();
        foreach ($lessons as $lesson) {
            if ($lesson['lesson_type'] != "other" && $lesson['lesson_type'] != "text") {
                if($lesson['duration'] != "" ) {
                $time_array = explode(':', $lesson['duration']);
                $hour_to_seconds = $time_array[0] * 60 * 60;
                $minute_to_seconds = $time_array[1] * 60;
                $seconds = $time_array[2];
                $total_duration += $hour_to_seconds + $minute_to_seconds + $seconds;
                }
            }
        }
        //return gmdate("H:i:s", $total_duration).' '.get_phrase('hours');
        $hours = floor($total_duration / 3600);
        $minutes = floor(($total_duration % 3600) / 60);
        $seconds = $total_duration % 60;
        if( sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds) == "00:00:00") {
            return "";
        } else {
          return   sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds) . ' ' . get_phrase('hours');
        }
        // return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds) . ' ' . get_phrase('hours');
    }

    public function rate($data)
    {
        if ($this->db->get_where('rating', array('user_id' => $data['user_id'], 'ratable_id' => $data['ratable_id'], 'ratable_type' => $data['ratable_type']))->num_rows() == 0) {
            $this->db->insert('rating', $data);
        } else {
            $checker = array('user_id' => $data['user_id'], 'ratable_id' => $data['ratable_id'], 'ratable_type' => $data['ratable_type']);
            $this->db->where($checker);
            $this->db->update('rating', $data);
        }
    }

    public function get_user_specific_rating($ratable_type = "", $ratable_id = "")
    {
        $reviews = $this->db->get_where('rating', array('ratable_type' => $ratable_type, 'user_id' => $this->session->userdata('user_id'), 'ratable_id' => $ratable_id));
        if($reviews->num_rows() > 0){
            return $reviews->row_array();
        }else{
            return array('rating' => 0);
        }
    }

    public function get_ratings($ratable_type = "", $ratable_id = "", $is_sum = false)
    {
        if ($is_sum) {
            $this->db->select_sum('rating');
            return $this->db->get_where('rating', array('ratable_type' => $ratable_type, 'ratable_id' => $ratable_id));
        } else {
            return $this->db->get_where('rating', array('ratable_type' => $ratable_type, 'ratable_id' => $ratable_id));
        }
    }

    public function get_instructor_wise_course_ratings($instructor_id = "", $ratable_type = "", $is_sum = false)
    {
        $course_ids = $this->get_instructor_wise_courses($instructor_id, 'simple_array');
        if ($is_sum) {
            $this->db->where('ratable_type', $ratable_type);
            $this->db->where_in('ratable_id', $course_ids);
            $this->db->select_sum('rating');
            return $this->db->get('rating');
        } else {
            $this->db->where('ratable_type', $ratable_type);
            $this->db->where_in('ratable_id', $course_ids);
            return $this->db->get('rating');
        }
    }
    public function get_percentage_of_specific_rating($rating = "", $ratable_type = "", $ratable_id = "")
    {
        $number_of_user_rated = $this->db->get_where('rating', array(
            'ratable_type' => $ratable_type,
            'ratable_id'   => $ratable_id
        ))->num_rows();

        $number_of_user_rated_the_specific_rating = $this->db->get_where('rating', array(
            'ratable_type' => $ratable_type,
            'ratable_id'   => $ratable_id,
            'rating'       => $rating
        ))->num_rows();

        //return $number_of_user_rated.' '.$number_of_user_rated_the_specific_rating;
        if ($number_of_user_rated_the_specific_rating > 0) {
            $percentage = ($number_of_user_rated_the_specific_rating / $number_of_user_rated) * 100;
        } else {
            $percentage = 0;
        }
        return floor($percentage);
    }

    ////////private message//////
    function send_new_private_message()
    {
        $message    = $this->input->post('message');
        $timestamp  = strtotime(date("Y-m-d H:i:s"));

        $receiver   = $this->input->post('receiver');
        $sender     = $this->session->userdata('user_id');

        //check if the thread between those 2 users exists, if not create new thread
        $num1 = $this->db->get_where('message_thread', array('sender' => $sender, 'receiver' => $receiver))->num_rows();
        $num2 = $this->db->get_where('message_thread', array('sender' => $receiver, 'receiver' => $sender))->num_rows();
        if ($num1 == 0 && $num2 == 0) {
            $message_thread_code                        = substr(md5(rand(100000000, 20000000000)), 0, 15);
            $data_message_thread['message_thread_code'] = $message_thread_code;
            $data_message_thread['sender']              = $sender;
            $data_message_thread['receiver']            = $receiver;
            $this->db->insert('message_thread', $data_message_thread);
        }
        if ($num1 > 0)
            $message_thread_code = $this->db->get_where('message_thread', array('sender' => $sender, 'receiver' => $receiver))->row()->message_thread_code;
        if ($num2 > 0)
            $message_thread_code = $this->db->get_where('message_thread', array('sender' => $receiver, 'receiver' => $sender))->row()->message_thread_code;


        $data_message['message_thread_code']    = $message_thread_code;
        $data_message['message']                = $message;
        $data_message['sender']                 = $sender;
        $data_message['timestamp']              = $timestamp;
        $this->db->insert('message', $data_message);

        return $message_thread_code;
    }

    function send_reply_message($message_thread_code)
    {
        $message    = html_escape($this->input->post('message'));
        $timestamp  = strtotime(date("Y-m-d H:i:s"));
        $sender     = $this->session->userdata('user_id');

        $data_message['message_thread_code']    = $message_thread_code;
        $data_message['message']                = $message;
        $data_message['sender']                 = $sender;
        $data_message['timestamp']              = $timestamp;
        $this->db->insert('message', $data_message);
    }

    function mark_thread_messages_read($message_thread_code)
    {
        // mark read only the oponnent messages of this thread, not currently logged in user's sent messages
        $current_user = $this->session->userdata('user_id');
        $this->db->where('sender !=', $current_user);
        $this->db->where('message_thread_code', $message_thread_code);
        $this->db->update('message', array('read_status' => 1));
    }

    function count_unread_message_of_thread($message_thread_code)
    {
        $unread_message_counter = 0;
        $current_user = $this->session->userdata('user_id');
        $messages = $this->db->get_where('message', array('message_thread_code' => $message_thread_code))->result_array();
        foreach ($messages as $row) {
            if ($row['sender'] != $current_user && $row['read_status'] == '0')
                $unread_message_counter++;
        }
        return $unread_message_counter;
    }

    public function get_last_message_by_message_thread_code($message_thread_code)
    {
        $this->db->order_by('message_id', 'desc');
        $this->db->limit(1);
        $this->db->where(array('message_thread_code' => $message_thread_code));
        return $this->db->get('message');
    }

    function curl_request($code = '')
    {

        $product_code = $code;

        $personal_token = "FkA9UyDiQT0YiKwYLK3ghyFNRVV9SeUn";
        $url = "https://api.envato.com/v3/market/author/sale?code=" . $product_code;
        $curl = curl_init($url);

        //setting the header for the rest of the api
        $bearer   = 'bearer ' . $personal_token;
        $header   = array();
        $header[] = 'Content-length: 0';
        $header[] = 'Content-type: application/json; charset=utf-8';
        $header[] = 'Authorization: ' . $bearer;

        $verify_url = 'https://api.envato.com/v1/market/private/user/verify-purchase:' . $product_code . '.json';
        $ch_verify = curl_init($verify_url . '?code=' . $product_code);

        curl_setopt($ch_verify, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch_verify, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch_verify, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch_verify, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch_verify, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

        $cinit_verify_data = curl_exec($ch_verify);
        curl_close($ch_verify);

        $response = json_decode($cinit_verify_data, true);

        if (count($response['verify-purchase']) > 0) {
            return true;
        } else {
            return false;
        }
    }


    // version 1.3
    function get_currencies()
    {
        return $this->db->get('currency')->result_array();
    }

    function get_paypal_supported_currencies()
    {
        $this->db->where('paypal_supported', 1);
        return $this->db->get('currency')->result_array();
    }

    function get_stripe_supported_currencies()
    {
        $this->db->where('stripe_supported', 1);
        return $this->db->get('currency')->result_array();
    }

    // version 1.4
    function filter_course($selected_category_id = "", $selected_price = "", $selected_level = "", $selected_language = "", $selected_rating = "")
    {
        // echo $selected_category_id.' '.$selected_price.' '.$selected_level.' '.$selected_language.' '.$selected_rating;
// die();
        $course_ids = array();
        if ($selected_category_id != "all") {
            $category_details = $this->get_category_details_by_id($selected_category_id)->row_array();

            if ($category_details['parent'] > 0) {
                
            //   $sub =   explode(',', $category_details['sub_category_id']);
            $search="FIND_IN_SET ('$selected_category_id',sub_category_id)";
             $this->db->where($search);
                // $this->db->where_in('sub_category_id', $selected_category_id);
            } else {
                $this->db->where('category_id', $selected_category_id);
            }
        }

        if ($selected_price != "all") {
            if ($selected_price == "paid") {
                $this->db->where('is_free_course', null);
            } elseif ($selected_price == "free") {
                $this->db->where('is_free_course', 1);
            }
        }

        if ($selected_level != "all") {
            $this->db->where('level', $selected_level);
        }

        if ($selected_language != "all") {
            $this->db->where('language', $selected_language);
        }
        $this->db->where('status', 'active');
        $courses = $this->db->get('course')->result_array();

        foreach ($courses as $course) {
            if ($selected_rating != "all") {
                $total_rating =  $this->get_ratings('course', $course['id'], true)->row()->rating;
                $number_of_ratings = $this->get_ratings('course', $course['id'])->num_rows();
                if ($number_of_ratings > 0) {
                    $average_ceil_rating = ceil($total_rating / $number_of_ratings);
                    if ($average_ceil_rating == $selected_rating) {
                        array_push($course_ids, $course['id']);
                    }
                }
            } else {
                array_push($course_ids, $course['id']);
            }
        }

        if (count($course_ids) > 0) {
            if (!addon_status('scorm_course')) {
                $this->db->where('course_type', 'general');
            }
            return  $course_ids;
           
        } else {
            return array();
        }
    }

    

    public function get_courses($category_id = "", $sub_category_id = "", $instructor_id = 0)
    {
        if ($category_id > 0 && $sub_category_id > 0 && $instructor_id > 0) {

            $multi_instructor_course_ids = $this->multi_instructor_course_ids_for_an_instructor($instructor_id);
            $this->db->where('category_id', $category_id);
            $this->db->where('sub_category_id', $sub_category_id);
            $this->db->where('user_id', $instructor_id);

            if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
                $this->db->or_where_in('id', $multi_instructor_course_ids);
            }

            return $this->db->get('course');
        } elseif ($category_id > 0 && $sub_category_id > 0 && $instructor_id == 0) {
            return $this->db->get_where('course', array('category_id' => $category_id, 'sub_category_id' => $sub_category_id));
        } else {
            return $this->db->get('course');
        }
    }

    public function filter_course_for_backend($category_id, $instructor_id, $price, $status)
    {
        // MULTI INSTRUCTOR COURSE IDS
        $multi_instructor_course_ids = array();
        if ($instructor_id != "all") {
            $multi_instructor_course_ids = $this->multi_instructor_course_ids_for_an_instructor($instructor_id);
        }

        if ($category_id != "all") {
            $this->db->where('sub_category_id', $category_id);
        }

        if ($price != "all") {
            if ($price == "paid") {
                $this->db->where('is_free_course', null);
            } elseif ($price == "free") {
                $this->db->where('is_free_course', 1);
            }
        }

        if ($instructor_id != "all") {
            $this->db->where('user_id', $instructor_id);
            if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
                $this->db->or_where_in('id', $multi_instructor_course_ids);
            }
        }

        if ($status != "all") {
            $this->db->where('status', $status);
        }
        return $this->db->get('course')->result_array();
    }

    public function sort_section($section_json)
    {
        $sections = json_decode($section_json);
        foreach ($sections as $key => $value) {
            $updater = array(
                'order' => $key + 1
            );
            $this->db->where('id', $value);
            $this->db->update('section', $updater);
        }
    }

    public function sort_lesson($lesson_json)
    {
        $lessons = json_decode($lesson_json);
        foreach ($lessons as $key => $value) {
            $updater = array(
                'order' => $key + 1
            );
            $this->db->where('id', $value);
            $this->db->update('lesson', $updater);
        }
    }
    public function sort_question($question_json)
    {
        $questions = json_decode($question_json);
        foreach ($questions as $key => $value) {
            $updater = array(
                'order' => $key + 1
            );
            $this->db->where('id', $value);
            $this->db->update('question', $updater);
        }
    }

    public function get_free_and_paid_courses($price_status = "", $instructor_id = "")
    {
        // MULTI INSTRUCTOR COURSE IDS
        $multi_instructor_course_ids = array();
        if ($instructor_id > 0) {
            $multi_instructor_course_ids = $this->multi_instructor_course_ids_for_an_instructor($instructor_id);
        }

        if (!addon_status('scorm_course')) {
            $this->db->where('course_type', 'general');
        }
        $this->db->where('status', 'active');
        if ($price_status == 'free') {
            $this->db->where('is_free_course', 1);
        } else {
            $this->db->where('is_free_course', null);
        }

        if ($instructor_id > 0) {
            $this->db->where('user_id', $instructor_id);
            if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
                $this->db->or_where_in('id', $multi_instructor_course_ids);
            }
        }
        return $this->db->get('course');
    }

    // Adding quiz functionalities
    public function add_quiz($course_id = "")
    {
        $data['course_id'] = $course_id;
        $data['title'] = html_escape($this->input->post('title'));
        $data['section_id'] = html_escape($this->input->post('section_id'));

        $data['lesson_type'] = 'quiz';
        $data['duration'] = '00:00:00';
        $data['date_added'] = strtotime(date('D, d-M-Y'));
        $data['summary'] = html_escape($this->input->post('summary'));
        $this->db->insert('lesson', $data);
    }

    // updating quiz functionalities
    public function edit_quiz($lesson_id = "")
    {
        $data['title'] = html_escape($this->input->post('title'));
        $data['section_id'] = html_escape($this->input->post('section_id'));
        $data['last_modified'] = strtotime(date('D, d-M-Y'));
        $data['summary'] = html_escape($this->input->post('summary'));
        $this->db->where('id', $lesson_id);
        $this->db->update('lesson', $data);
    }

    // Get quiz questions
    public function get_quiz_questions($quiz_id)
    {
        $this->db->order_by("order", "asc");
        $this->db->where('quiz_id', $quiz_id);
        return $this->db->get('question');
    }

    public function get_quiz_question_by_id($question_id)
    {
        $this->db->order_by("order", "asc");
        $this->db->where('id', $question_id);
        return $this->db->get('question');
    }

    // Add Quiz Questions
    public function add_quiz_questions($quiz_id)
    {
        $question_type = $this->input->post('question_type');
        if ($question_type == 'mcq') {
            $response = $this->add_multiple_choice_question($quiz_id);
            return $response;
        }
    }

    public function update_quiz_questions($question_id)
    {
        $question_type = $this->input->post('question_type');
        if ($question_type == 'mcq') {
            $response = $this->update_multiple_choice_question($question_id);
            return $response;
        }
    }
    // multiple_choice_question crud functions
    function add_multiple_choice_question($quiz_id)
    {
        if (sizeof($this->input->post('options')) != $this->input->post('number_of_options')) {
            return false;
        }
        foreach ($this->input->post('options') as $option) {
            if ($option == "") {
                return false;
            }
        }
        if (sizeof($this->input->post('correct_answers')) == 0) {
            $correct_answers = [""];
        } else {
            $correct_answers = $this->input->post('correct_answers');
        }
        $data['quiz_id']            = $quiz_id;
        $data['title']              = html_escape($this->input->post('title'));
        $data['number_of_options']  = html_escape($this->input->post('number_of_options'));
        $data['type']               = 'multiple_choice';
        $data['options']            = json_encode($this->input->post('options'));
        $data['correct_answers']    = json_encode($correct_answers);
        $this->db->insert('question', $data);
        return true;
    }
    // update multiple choice question
    function update_multiple_choice_question($question_id)
    {
        if (sizeof($this->input->post('options')) != $this->input->post('number_of_options')) {
            return false;
        }
        foreach ($this->input->post('options') as $option) {
            if ($option == "") {
                return false;
            }
        }

        if (sizeof($this->input->post('correct_answers')) == 0) {
            $correct_answers = [""];
        } else {
            $correct_answers = $this->input->post('correct_answers');
        }

        $data['title']              = html_escape($this->input->post('title'));
        $data['number_of_options']  = html_escape($this->input->post('number_of_options'));
        $data['type']               = 'multiple_choice';
        $data['options']            = json_encode($this->input->post('options'));
        $data['correct_answers']    = json_encode($correct_answers);
        $this->db->where('id', $question_id);
        $this->db->update('question', $data);
        return true;
    }

    function delete_quiz_question($question_id)
    {
        $this->db->where('id', $question_id);
        $this->db->delete('question');
        return true;
    }

    function get_application_details()
    {
        $purchase_code = get_settings('purchase_code');
        $returnable_array = array(
            'purchase_code_status' => get_phrase('not_found'),
            'support_expiry_date'  => get_phrase('not_found'),
            'customer_name'        => get_phrase('not_found')
        );

        $personal_token = "gC0J1ZpY53kRpynNe4g2rWT5s4MW56Zg";
        $url = "https://api.envato.com/v3/market/author/sale?code=" . $purchase_code;
        $curl = curl_init($url);

        //setting the header for the rest of the api
        $bearer   = 'bearer ' . $personal_token;
        $header   = array();
        $header[] = 'Content-length: 0';
        $header[] = 'Content-type: application/json; charset=utf-8';
        $header[] = 'Authorization: ' . $bearer;

        $verify_url = 'https://api.envato.com/v1/market/private/user/verify-purchase:' . $purchase_code . '.json';
        $ch_verify = curl_init($verify_url . '?code=' . $purchase_code);

        curl_setopt($ch_verify, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch_verify, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch_verify, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch_verify, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch_verify, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

        $cinit_verify_data = curl_exec($ch_verify);
        curl_close($ch_verify);

        $response = json_decode($cinit_verify_data, true);

        if (count($response['verify-purchase']) > 0) {

            //print_r($response);
            $item_name         = $response['verify-purchase']['item_name'];
            $purchase_time       = $response['verify-purchase']['created_at'];
            $customer         = $response['verify-purchase']['buyer'];
            $licence_type       = $response['verify-purchase']['licence'];
            $support_until      = $response['verify-purchase']['supported_until'];
            $customer         = $response['verify-purchase']['buyer'];

            $purchase_date      = date("d M, Y", strtotime($purchase_time));

            $todays_timestamp     = strtotime(date("d M, Y"));
            $support_expiry_timestamp = strtotime($support_until);

            $support_expiry_date  = date("d M, Y", $support_expiry_timestamp);

            if ($todays_timestamp > $support_expiry_timestamp)
                $support_status    = get_phrase('expired');
            else
                $support_status    = get_phrase('valid');

            $returnable_array = array(
                'purchase_code_status' => $support_status,
                'support_expiry_date'  => $support_expiry_date,
                'customer_name'        => $customer
            );
        } else {
            $returnable_array = array(
                'purchase_code_status' => 'invalid',
                'support_expiry_date'  => 'invalid',
                'customer_name'        => 'invalid'
            );
        }

        return $returnable_array;
    }

    // Version 2.2 codes

    // This function is responsible for retreving all the language file from language folder
    function get_all_languages()
    {
        $language_files = array();
        $all_files = $this->get_list_of_language_files();
        foreach ($all_files as $file) {
            $info = pathinfo($file);
            if (isset($info['extension']) && strtolower($info['extension']) == 'json') {
                $file_name = explode('.json', $info['basename']);
                array_push($language_files, $file_name[0]);
            }
        }
        return $language_files;
    }

    // This function is responsible for showing all the installed themes
    function get_installed_themes($dir = APPPATH . '/views/frontend')
    {
        $result = array();
        $cdir = $files = preg_grep('/^([^.])/', scandir($dir));
        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    array_push($result, $value);
                }
            }
        }
        return $result;
    }
    // This function is responsible for showing all the uninstalled themes inside themes folder
    function get_uninstalled_themes($dir = 'themes')
    {
        $result = array();
        $cdir = $files = preg_grep('/^([^.])/', scandir($dir));
        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".", "..", ".DS_Store"))) {
                array_push($result, $value);
            }
        }
        return $result;
    }
    // This function is responsible for retreving all the language file from language folder
    function get_list_of_language_files($dir = APPPATH . '/language', &$results = array())
    {
        $files = scandir($dir);
        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[] = $path;
            } else if ($value != "." && $value != "..") {
                $this->get_list_of_directories_and_files($path, $results);
                $results[] = $path;
            }
        }
        return $results;
    }

    // This function is responsible for retreving all the files and folder
    function get_list_of_directories_and_files($dir = APPPATH, &$results = array())
    {
        $files = scandir($dir);
        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[] = $path;
            } else if ($value != "." && $value != "..") {
                $this->get_list_of_directories_and_files($path, $results);
                $results[] = $path;
            }
        }
        return $results;
    }

    function remove_files_and_folders($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir")
                        $this->remove_files_and_folders($dir . "/" . $object);
                    else unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    function get_category_wise_courses($category_id = "")
    {
        $category_details = $this->get_category_details_by_id($category_id)->row_array();

        if ($category_details['parent'] > 0) {
            $this->db->where('sub_category_id', $category_id);
        } else {
            $this->db->where('category_id', $category_id);
        }
        $this->db->where('status', 'active');
        return $this->db->get('course');
    }

    function activate_theme($theme_to_active)
    {
        $data['value'] = $theme_to_active;
        $this->db->where('key', 'theme');
        $this->db->update('frontend_settings', $data);
    }

    // code of mark this lesson as completed
    function save_course_progress()
    {
        $lesson_id = $this->input->post('lesson_id');
        $progress = $this->input->post('progress');
        $user_id   = $this->session->userdata('user_id');
        $user_details  = $this->user_model->get_all_user($user_id)->row_array();
        $watch_history = $user_details['watch_history'];
        $watch_history_array = array();
        if ($watch_history == '') {
            array_push($watch_history_array, array('lesson_id' => $lesson_id, 'progress' => $progress));
        } else {
            $founder = false;
            $watch_history_array = json_decode($watch_history, true);
            for ($i = 0; $i < count($watch_history_array); $i++) {
                $watch_history_for_each_lesson = $watch_history_array[$i];
                if ($watch_history_for_each_lesson['lesson_id'] == $lesson_id) {
                    $watch_history_for_each_lesson['progress'] = $progress;
                    $watch_history_array[$i]['progress'] = $progress;
                    $founder = true;
                }
            }
            if (!$founder) {
                array_push($watch_history_array, array('lesson_id' => $lesson_id, 'progress' => $progress));
            }
        }
        $data['watch_history'] = json_encode($watch_history_array);
        $this->db->where('id', $user_id);
        $this->db->update('users', $data);

        // CHECK IF THE USER IS ELIGIBLE FOR CERTIFICATE
        if (addon_status('certificate')) {
            $this->load->model('addons/Certificate_model', 'certificate_model');
            $this->certificate_model->check_certificate_eligibility("lesson", $lesson_id, $user_id);
        }

        return $progress;
    }



    //FOR MOBILE
    function enrol_to_free_course_mobile($course_id = "", $user_id = "")
    {
        $data['course_id'] = $course_id;
        $data['user_id']   = $user_id;
        $data['date_added'] = strtotime(date('D, d-M-Y'));
        if ($this->db->get_where('course', array('id' => $course_id))->row('is_free_course') == 1) :
            $this->db->insert('enrol', $data);
        endif;
    }

    function check_course_enrolled($course_id = "", $user_id = "")
    {
        return $this->db->get_where('enrol', array('course_id' => $course_id, 'user_id' => $user_id))->num_rows();
    }
    function check_course_enrolled_user($course_id = "", $user_id = "")
    {
        return $this->db->get_where('enrol', array('course_id' => $course_id, 'user_id' => $user_id))->row_array();
    }


    // GET PAYOUTS
    public function get_payouts($id = "", $type = "")
    {
        $this->db->order_by('id', 'DESC');
        if ($id > 0 && $type == 'user') {
            $this->db->where('user_id', $id);
        } elseif ($id > 0 && $type == 'payout') {
            $this->db->where('id', $id);
        }
        return $this->db->get('payout');
    }

    // GET COMPLETED PAYOUTS BY DATE RANGE
    public function get_completed_payouts_by_date_range($timestamp_start = "", $timestamp_end = "")
    {
        $this->db->order_by('id', 'DESC');
        $this->db->where('date_added >=', $timestamp_start);
        $this->db->where('date_added <=', $timestamp_end);
        $this->db->where('status', 1);
        return $this->db->get('payout');
    }

    // GET PENDING PAYOUTS BY DATE RANGE
    public function get_pending_payouts()
    {
        $this->db->order_by('id', 'DESC');
        $this->db->where('status', 0);
        return $this->db->get('payout');
    }

    // GET TOTAL PAYOUT AMOUNT OF AN INSTRUCTOR
    public function get_total_payout_amount($id = "")
    {
        $checker = array(
            'user_id' => $id,
            'status'  => 1
        );
        $this->db->order_by('id', 'DESC');
        $payouts = $this->db->get_where('payout', $checker)->result_array();
        $total_amount = 0;
        foreach ($payouts as $payout) {
            $total_amount = $total_amount + $payout['amount'];
        }
        return $total_amount;
    }

    // GET TOTAL REVENUE AMOUNT OF AN INSTRUCTOR
    public function get_total_revenue($id = "")
    {
        $revenues = $this->get_instructor_revenue($id);
        $total_amount = 0;
        foreach ($revenues as $key => $revenue) {
            $total_amount = $total_amount + $revenue['instructor_revenue'];
        }
        return $total_amount;
    }

    // GET TOTAL PENDING AMOUNT OF AN INSTRUCTOR
    public function get_total_pending_amount($id = "")
    {
        $total_revenue = $this->get_total_revenue($id);
        $total_payouts = $this->get_total_payout_amount($id);
        $total_pending_amount = $total_revenue - $total_payouts;
        return $total_pending_amount;
    }

    // GET REQUESTED WITHDRAWAL AMOUNT OF AN INSTRUCTOR
    public function get_requested_withdrawal_amount($id = "")
    {
        $requested_withdrawal_amount = 0;
        $checker = array(
            'user_id' => $id,
            'status' => 0
        );
        $payouts = $this->db->get_where('payout', $checker);
        if ($payouts->num_rows() > 0) {
            $payouts = $payouts->row_array();
            $requested_withdrawal_amount = $payouts['amount'];
        }
        return $requested_withdrawal_amount;
    }

    // GET REQUESTED WITHDRAWALS OF AN INSTRUCTOR
    public function get_requested_withdrawals($id = "")
    {
        $requested_withdrawal_amount = 0;
        $checker = array(
            'user_id' => $id,
            'status' => 0
        );
        $payouts = $this->db->get_where('payout', $checker);

        return $payouts;
    }

    // ADD NEW WITHDRAWAL REQUEST
    public function add_withdrawal_request()
    {
        $user_id = $this->session->userdata('user_id');
        $total_pending_amount = $this->get_total_pending_amount($user_id);

        $requested_withdrawal_amount = $this->input->post('withdrawal_amount');
        if ($total_pending_amount > 0 && $total_pending_amount >= $requested_withdrawal_amount) {
            $data['amount']     = $requested_withdrawal_amount;
            $data['user_id']    = $this->session->userdata('user_id');
            $data['date_added'] = strtotime(date('D, d M Y'));
            $data['status']     = 0;
            $this->db->insert('payout', $data);
            $this->session->set_flashdata('flash_message', get_phrase('withdrawal_requested'));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('invalid_withdrawal_amount'));
        }
    }

    // DELETE WITHDRAWAL REQUESTS
    public function delete_withdrawal_request()
    {
        $checker = array(
            'user_id' => $this->session->userdata('user_id'),
            'status' => 0
        );
        $requested_withdrawal = $this->db->get_where('payout', $checker);
        if ($requested_withdrawal->num_rows() > 0) {
            $this->db->where($checker);
            $this->db->delete('payout');
            $this->session->set_flashdata('flash_message', get_phrase('withdrawal_deleted'));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('withdrawal_not_found'));
        }
    }

    // get instructor wise total enrolment. this function return the number of enrolment for a single instructor
    public function instructor_wise_enrolment($instructor_id)
    {
        $course_ids = $this->crud_model->get_instructor_wise_courses($instructor_id, 'simple_array');
        if (!count($course_ids) > 0) {
            return false;
        }
        $this->db->select('user_id');
        $this->db->where_in('course_id', $course_ids);
        return $this->db->get('enrol');
    }

    public function check_duplicate_payment_for_stripe($transaction_id = "", $stripe_session_id = "", $user_id = "")
    {
        if ($user_id == "") {
            $user_id = $this->session->userdata('user_id');
        }

        $query = $this->db->get_where('payment', array('user_id' => $user_id, 'transaction_id' => $transaction_id, 'session_id' => $stripe_session_id));
        if ($query->num_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function get_course_by_course_type($type = "")
    {
        if ($type != "") {
            $this->db->where('course_type', $type);
        }
        return $this->db->get('course');
    }

    public function check_recaptcha()
    {
        if (isset($_POST["g-recaptcha-response"])) {
            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $data = array(
                'secret' => get_frontend_settings('recaptcha_secretkey'),
                'response' => $_POST["g-recaptcha-response"]
            );
            $query = http_build_query($data);
            $options = array(
                'http' => array(
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                        "Content-Length: " . strlen($query) . "\r\n" .
                        "User-Agent:MyAgent/1.0\r\n",
                    'method' => 'POST',
                    'content' => $query
                )
            );
            $context  = stream_context_create($options);
            $verify = file_get_contents($url, false, $context);
            $captcha_success = json_decode($verify);
            if ($captcha_success->success == false) {
                return false;
            } else if ($captcha_success->success == true) {
                return true;
            }
        } else {
            return false;
        }
    }

    function get_course_by_user($user_id = "", $course_type = "")
    {
        $multi_instructor_course_ids = $this->multi_instructor_course_ids_for_an_instructor($user_id);
        if ($course_type != "") {
            $this->db->where('course_type', $course_type);
        }
        $this->db->where('user_id', $user_id);

        if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
            $this->db->or_where_in('id', $multi_instructor_course_ids);
        }

        return $this->db->get('course');
    }

    public function multi_instructor_course_ids_for_an_instructor($instructor_id)
    {
        $course_ids = array();
        $multi_instructor_courses = $this->db->get_where('course', array('multi_instructor' => 1))->result_array();
        foreach ($multi_instructor_courses as $key => $multi_instructor_course) {
            $exploded_user_ids = explode(',', $multi_instructor_course['user_id']);
            if (in_array($instructor_id, $exploded_user_ids)) {
                array_push($course_ids, $multi_instructor_course['id']);
            }
        }
        return $course_ids;
    }

    /** COUPONS FUNCTIONS */
    public function get_coupons($id = null)
    {
        if ($id > 0) {
            $this->db->where('id', $id);
        }
        return $this->db->get('coupons');
    }

    public function get_coupon_details_by_code($code)
    {
        $this->db->where('code', $code);
        return $this->db->get('coupons');
    }

    public function add_coupon()
    {
        if (isset($_POST['code']) && !empty($_POST['code']) && isset($_POST['discount_percentage']) && !empty($_POST['discount_percentage']) && isset($_POST['expiry_date']) && !empty($_POST['expiry_date'])) {
            $data['code'] = $this->input->post('code');
            $data['discount_percentage'] = $this->input->post('discount_percentage') > 0 ? $this->input->post('discount_percentage') : 0;
            $data['expiry_date'] = strtotime($this->input->post('expiry_date'));
            $data['created_at'] = strtotime(date('D, d-M-Y'));

            $availability = $this->db->get_where('coupons', ['code' => $data['code']])->num_rows();
            if ($availability) {
                return false;
            } else {
                $this->db->insert('coupons', $data);
                return true;
            }
        } else {
            return false;
        }
    }
    public function edit_coupon($coupon_id)
    {
        if (isset($_POST['code']) && !empty($_POST['code']) && isset($_POST['discount_percentage']) && !empty($_POST['discount_percentage']) && isset($_POST['expiry_date']) && !empty($_POST['expiry_date'])) {
            $data['code'] = $this->input->post('code');
            $data['discount_percentage'] = $this->input->post('discount_percentage') > 0 ? $this->input->post('discount_percentage') : 0;
            $data['expiry_date'] = strtotime($this->input->post('expiry_date'));
            $data['created_at'] = strtotime(date('D, d-M-Y'));

            $this->db->where('id !=', $coupon_id);
            $this->db->where('code', $data['code']);
            $availability = $this->db->get('coupons')->num_rows();
            if ($availability) {
                return false;
            } else {
                $this->db->where('id', $coupon_id);
                $this->db->update('coupons', $data);
                return true;
            }
        } else {
            return false;
        }
    }

    public function delete_coupon($coupon_id)
    {
        $this->db->where('id', $coupon_id);
        $this->db->delete('coupons');
        return true;
    }

    // CHECK IF THE COUPON CODE IS VALID
    public function check_coupon_validity($coupon_code)
    {
        $this->db->where('code', $coupon_code);
        $result = $this->db->get('coupons');
        if ($result->num_rows() > 0) {
            $result = $result->row_array();
            if ($result['expiry_date'] >= strtotime(date('D, d-M-Y'))) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // GET DISCOUNTED PRICE AFTER APPLYING COUPON
    public function get_discounted_price_after_applying_coupon($coupon_code)
    {
        $total_price  = 0;
        foreach ($this->session->userdata('cart_items') as $cart_item) {
            $course_details = $this->crud_model->get_course_by_id($cart_item)->row_array();
            if ($course_details['discount_flag'] == 1) {
                $total_price += $course_details['discounted_price'];
            } else {
                $total_price  += $course_details['price'];
            }
        }

        if ($this->check_coupon_validity($coupon_code)) {
            $coupon_details = $this->get_coupon_details_by_code($coupon_code)->row_array();
            $discounted_price = ($total_price * $coupon_details['discount_percentage']) / 100;
            $total_price = $total_price - $discounted_price;
        } else {
            return $total_price;
        }

        return $total_price > 0 ? $total_price : 0;
    }

    function get_free_lessons($lesson_id = ""){
        if($lesson_id != ""){
            $this->db->where('id', $lesson_id);
        }
        $this->db->where('is_free', 1);
        return $this->db->get('lesson');
    }

    function update_watch_history($course_id = "", $lesson_id = ""){
        $user_id = $this->session->userdata('user_id');
        $query = $this->db->get_where('watch_histories', array('course_id' => $course_id, 'student_id' => $user_id));

        if($course_id != "" && $lesson_id != ""){
            if($query->num_rows() > 0){
                $this->db->where('watch_history_id', $query->row('watch_history_id'));
                $this->db->update('watch_histories', array('watching_lesson_id' => $lesson_id, 'date_updated' => time()));
            }else{
                $data['course_id'] = $course_id;
                $data['student_id'] = $user_id;
                $data['watching_lesson_id'] = $lesson_id;
                $data['date_added'] = time();
                $this->db->insert('watch_histories', $data);
            }
            return $lesson_id;
        }elseif($query->num_rows() > 0){
            return $query->row('watching_lesson_id');
        }
    }

    function get_top_instructor($limit = 10){
        $query = $this->db
            ->select("creator, count(*) AS enrol_number",false)
            ->from ("enrol")
            ->join('course', 'course.id = enrol.course_id')
            ->group_by('creator')
            ->order_by("creator","DESC")
            ->limit($limit)
            ->get();
        return $query->result_array();
    }

    function get_active_course_by_category_id($category_id = "", $category_id_type = "category_id"){
        $search="FIND_IN_SET ('$category_id',$category_id_type)";
        $this->db->where($search);
        $this->db->where('status', 'active');
        return $this->db->get('course');
    }
    function get_active_course($course_id = ""){

        if($course_id > 0){
            $this->db->where('id', $course_id = "");
        }
        $this->db->where('status', 'active');
        return $this->db->get('course');
    }

    
    
function go1Array() {
        
 $array = array('16615483','16536554','1895648','7041135','13404565','7816983','13671573','10275297','2083670','13402446','2645470','1895724','13401865','1895718','21961666','8528237','7710533','13401868','13401847','5540534','15696020','13402430','27712146','10651500','13401790','10654781',
 '5515000','16708031','13404560','13406698','13402351','4335494','13401748','13402457','13401767','13404581','1895690','13401750','13401820','13402363','13402414','1895730','13402412','13401862','13401859','13402410','16098862','13401854','13402416','10651621','13401809','5307860','10654616','10654532','2872008','10654688','1947557','5307309','5393318','3511433','10622213','10883506','12935640','2562376','5654134','2872291','2470935','9139872','8509570','16824364','4198346','2872351','16619747','22064390','7710730','2471111','21905342','13398056','9154715','24800257','16892751','5432214','10647771','3510295','10646910','9154722','4800049','16999218','22064715','11011994','9139967','7848833','2645853','7329305','10523431','10647080','13758590','6024600','4266314','13659916','6083970','9275278','13398054','10284921','2470934','20730923','13406638','10647148','11825021','14054865','13436241','9139915','13398028','11822319','10647256','13486726','3510324','13406623','13436652','16739320','10647405','6084219','13527780','10626260','13398058','10647468','9198648','11213334','13398021','13406642','3509943','10647616','10647558','13406641','23156616','13406617','13406635','13401049','13398043','13398013','13406625','14924081','13406627','14602296','13398052','13406628','23156629','13406616','13398039','6381770','13398027','13406622','13406624','10695930','13398026','13398030','13406615','13406633','13398047','13406619','13398009','4335293','2470950','13401052','13406626','6022363','8381889','11168002','2872105','2644669','6021823','17198737','2647027','11012256','9139865','10626464','14771557','13437808','8436885','13758592','16824095','6084383','13148963','11023111','2470938','25179507','13758587','17055724','6682733','14602278','13744955','3510329','4335025','4197754','13404588','4335438','7455006','14602260','21795090','10943756','5393698','3510302','4198175','6635868','2026313','12852875','7444024','12071304','24800272','7458659','2043062','2471075','13374173','5514796','13401787','9735150','7051610','10643808','25556068','13672229','12246324','13758593','13401801','29086332','8540343','2049185','11011625','13401788','4335058','7869692','5993943','24181678','2872069','5413548','10362404','4198564','10389473','5414889','4800013','6019339','6638309','13402408','4196945','9477485','2471073','11823290','4335415','5499923','6085177','5307614','4773538','5994799','2471106','6638199','4761527','2471140','4787739','11011880','10369330','5392012','12853311','8515631','10620120','8176965','2471087','2471033','12880851','4335704','5671154','2301883','5393433','8553705','10385680','9140051','2471077','6638255','2301545','8571175','8571178','13401811','5540696','10389166','13404573','5413141','10389365','6578855','6638415','8571189','21667252','4198970','13401879','8571180','7459349','23640868','11168833','5385297','11822430','5983571','8540341','3051892','13401830','4787705','6144535','8571171','8571194','8571186','27798811','11150522','13401825','8571165','22056752','5754879','8571158','5215655','8571193','13401849','9477521','9139946','8571157','8571160','19477400','2471052','8571190','12693094','13404593','2301750','4198066','11150426','4335498','2471081','11823372','8571153','3511164','2871635','10213061','5307437','9275174','6580269','5280029','24936697','4198366','22063631','9275490','17198663','2644977','24963579','4787727','9275367','12848627','27797665','2048693','13890054','26965136','13402434','34785304','21162350','2471030','27585565','4787749','2048963','8788005','4773339','23640574','5415779','8539046','13401842','5391451','10679565','9139888','13401845','11823375','4761558','9139867','22478582','8579536','2645120','2647143','3108624','13401869','19477373','28436930','19472029','13402438','7829482','13401883','10815475','2471079','17047722','7709064','20716307','23105332','11965167','19474782','5385711','7851453','10231479','13401775','13033007','11150507','11823505','5515721','22803347','5383890','2471021','2049006','13402406','13402449','12853723','13402384','13402372','5393537','13402375','13401758','18506696','17198788','2301873','5385179','13402440','13402397','8658502','9477523','13672710','9139994','4370866','13402400','8570373','13402387','11823522','15194788','10632693','11757918','7478850','2471031','9477525','13034098','26963711','4198762','2470932','6022552','21173876','16785250','8148191','13401827','7830269','10679546','5346554','13402342','30954135','11741345','13401852','3830311','12692982','2301534','13437531','5383792','26132835','5414659','13401784','13402901','13401781','23603867','5536512','11150822','11150611','10232586','13401779','11823654','8382269','13404570','6380963','13401828','13402349','8579535','8366972','13402431','13402344','2471145','9477527','5499190','23182671','24799426','11150644','10671578','13404591','11823634','13401798','13402367','13401773','13401770','13401753','19477439','7329552','3511949','2645384','10501605','13401890','13402445','24829484','14939781','19477364','5517009','11150800','11863359','15192176','10695722','11823701','9477529','6021302','19477389','2471042','4800255','2471100','3966834','7766120','5415486','4198701','5423565','11168565','5682117','13215274','9477531','25174917','15696924','9139950','4198128','2471023','2048912','5995346','20383610','19477356','23910784','9477533','11150862','11823711','13402391','11150965','12692984','5515267','10091669','10921386','4335459','11859731','9432542','2471136','11150900','4326440','13033010','7869839','28514086','9477534','13401834','3511267','11150936','11150985','9477538','20433776','5440815','22918310','4335089','2301844','13402451','3511272','19714015','22803212','7092224','12692986','11823825','4198022','13671018','5393740','8505830','2471091','15698371','12869971','19477349','2643612','19713994','11823827','22190628','9477539','3800915','19477407','5418327','5417834','23724196','22666624','9477542','10231468','5783157','2644778','13616151','11741059','10231519','8579534','13402389','7837051','13401836','11859803','2301572','17198776','9477544','3510334','14768800','23602963','8570375','19477462','9477546','3881386','12692987','13408465','8142998','9477547','13033013','5995758','5399086','8579533','11859897','13425189','4198507','5499154','13033023','5399382','13437520','9477549','10213961','8540336','9477550','13437527','17198797','11823923','13033021','11859966','26447661','9070673','13402378','3614561','9341025','27669441','3621553','6022526','2645013','13033026','9477553','13033017','6381467','4263354','13402382','13437521','5540233','9477554','12750134','5391499','12692993','4354613','23110974','13437530','9477555','8571212','5666889','11823924','14055778','11860021','11860072','3511423','11824170','27582394','12929339','9477556','9477557','5372519','9477561','5346556','12692994','24799761','9477559','23606260','11824160','9477563','13401886','12692995','20528567','6022893','12692996','6718692','11860128','4198774','13404585','22311531','9341026','11860185','7457343','5383273','19477452','5265680','9477564','13437533','9477565','27586389','12693001','11860243','13033030','13437721','9477567','11824389','14602285','11860311','13402356','7815036','13437661','27878410','13401873','8141964','5784420','9477568','13437532','5394209','4370749','22532579','35054288','13437523','11861271','26257963','9477569','13401745','13437524','13402426','9477572','21943620','13402423','11861326','8579531','11824401','11863189','2471132','9477577','9477579','13402359','3511157','13033054','9477581','9477576','5984015','8579532','13437772','3511357','9477587','11861447','17198705','20568116','9477598','8579528','26996067','9477584','11861494','11863249','2471080','11863145','8579530','8568466','11824598','14053908','9477593','13033033','19477415','9477589','2301753','8579525','13437519','13033035','3511536','13408461','9477595','13408471','11639767','13437522','9477600','13033059','12693009','9477605','8579527','13033038','13408473','8579526','10695997','11824600','12693007','9477606','8571211','9477603','9477607','10520072','9477609','11824671','13437529','4335067','22305809','13033040','12800265','11824665','2049064','30390907','9477613','12693011','9477615','12693012','26447981','6653738','5781648','17431825','7837607','4198298','12693020','19486940','2644145','12693013','9477624','9477628','24829827','9477616','9477618','5984216','9477635','9477626','13033043','12693024','9477631','12034152','5784567','9477640','9477633','13907417','17980737','8541120','9477638','13033046','20545677','13424967','25487557','9477643','12694064','6653398','12693028','13033049','3510141','4737888','5984738','6653849','13033064','17198806','13033051','3511495','8661950','13033058','7822638','23641225','10152640','12021957','2047760','5539527','10632302','26225524','9813974','13319251','5560587','12693033','8540641','13425211','10622568','18918426','13424972','7851627','7677071','3510315','3828554','24783302','13188128','4325615','11395917','8571206','2644939','6081021','10671293','26998415','8571208','2645152','10696204','21491081','11196537','8571210','5742064','11084819','12693034','5378311','2301565','18108303','11900328','17430155','5742922','5373047','11022004','7850376','11022033','17198684','16314193','2301889','12693035','26997619','3958011','24926919','26195864','25200453','7769034','12693037','2989383','2645426','12693040','12693044','8571207','2643963','8141772','13425206','24839800','17334681','11149767','19464875','12693045','8142461','11149752','11569942','21795444','8563186','8514462','26997800','10240912','4773301','12693055','13319332','11084812','12693057','27883867','12693058','10696257','4197145','5461039','5782917','22067138','4462386','5782642','12693062','25118632','10501677','14768811','12693059','24744978','10622699','3509952','13319253','10621715','10696058','22148726','19573398','11084923','12693064','13425220','7841999','11740564','13425199','19581088','24799912','24799486','26998149','12693067','5539176','11639919','8568464','6023988','13977832','12693075','5346722','12751219','11174442','12693084','12693085','12693071','3882836','12693091','5541187','25243862','11639865','10696303','5784260','12693105','13005207','13425299','4737935','12693098','11084962','2471134','5784838','5499948','10501676','29429155','7870121','11745281','13319379','22803631','11022162','11149866','5665806','11172968','11085373','12693107','2644555','19080086','13424910','12693126','36197277','3872178','11084948','12693112','12693110','4354343','8568463','13319267','12693116','3625342','2872161','21231511','22873536','4197745','2301531','12693115','12693119','13425186','13425044','12693128','18918349','30391085','13267275','12693117','3511149','11132221','20434220','22802949','11084987','11022177','13425177','2872260','6401274','11085350','4335169','12693121','12693132','10625785','12693153','12693123','4198207','3957759','10621246','19477486','28577732','7789584','12800526','12693135','12693179','12693158','12693148','16937040','4326378','11149930','22803114','13425306','5499899','10632896','11193280','13424977','9994461','12693139','12693152','22153898','8568461','9279233','12693170','8568460','24838457','2049483','2647163','12693146','8568462','12693162','6093350','9815102','13319254','13690491','17377996','11022203','12693173','11021421','3882811','12693161','12693159','5383398','4787767','5578080','13425184','25114609','11166935','20424410','13981079','12693166','13304785','10283771','8568458','5383429','7849857','19477446','13425161','13426709','6243936','13426832','3510285','17336181','11168709','4198497','12071236','11022495','10284262','9460475','9875315','10284280','9139951','13425202','2301747','18253090','10284294','13425170','10284361','3114512','19477422','13005212','8512828','11741830','11149964','19477428','5586277','13425102','11022247','8014526','9154541','11149986','12387040','3608761','11484455','11022544','3966872','9139916','5346122','4335156','5307258','13425312','11173260','28455145','6022385','11022595','13425165','11741140','24830875','13319255','6380448','11822863','11022577','10407786','13319257','11483723','6381720','13424915','5784505','8515439','2471032','11150029','8538748','13005220','13427037','12388324','13425172','13425193','11150054','13424988','11022672','11022619','11150262','13319276','13005225','9154710','6023200','24799324','32005706','13425154','13425213','12607765','11022638','13319261','12205612','11168623','3510537','11022735','22274109','13427933','9279329','11900229','12877500','13428100','10642845','13425231','2646860','13319269','13319271','6076599','13425128','16355913','13425006','5286975','13425125','13319501','13425228','13005235','5418659','28395461','7453018','36114308','13319285','2471051','13425021','11900251','13427847','13424955','3882661','10670600','13425315','5419155','13425010','24928616','10642915','5214917','13425223','9154712','13005258','5495787','13424958','9154714','13425040','13319290','13425328','9154723','13425309','13425322','9154728','13424983','9154726','13753381','4339824','6076991','11570172','13425334','13425003','13005270','13848676','13424918','10671408','13425175','6655047','23605441','11173476','13424895','13425132','13425241','13425023','2994083','21779572','13425235','5655681','8570369','27183367','13428016','11484697','13430305','13426235','6084733','13430428','3606067','8540442','13005276','3510356','13005280','2645589','11744618','11632875','15799905','11166989','11173708','2301700','3966525','13005289','18099267','3509739','11484948','3966770','6380576','16290979','9139931','15932700','8563183','11570253','26447813','13005296','11741541','8581308','3966718','22803427','22646286','8570381','22803439','5754055','4800245','13005310','11194631','7837291','8563185','28436948','8628804','3510345','16619633','7851520','5414084','13005316','9853660','4198584','11485065','4787760','17428424','2644027','11131701','8437314','4462311','13893184','13005317','17671672','4196904','10498219','13005320','7270105','4334037','5514638','11485216','3519581','13005423','13005330','5499939','12022058','33737298','13005410','11196229','11173887','2301668','11822850','11485462','3966515','11486212','13005414','13005336','15459007','11485309',
 '13005406','11174091','5983250','11570165','11900245','11900246','8563178','4335382','12773045','5286835','4773631','24832702','8536948','8017540','11485638','10187178','7051753','5558715','11570254','5781241','12937271','11822852','3511229','11485863','8147480','3829566','2301770','20446889','3511466','24799437','11195902','5307288','11485921','23153100','5302243','5279912','3512305','6020750','11570256','3617952','5213384','11486031','18099559','12844126','15068487','11900243','9140053','20728663','11486145','11570217','11569203','33604211','8563177','14768808','13939208','4198079','6024570','5515787','20426625','11822859','6000516','19959860','5399829','14038487','11167112','8437893','5384023','8563176','23607525','5647728','6093493','6093817','3829350','8563173','20416016','7459042','8563165','4773389','32710875','19493708','8563169','13517490','13858436','11900261','2301810','13436193','3922962','21161106','13752011','8488772','6719225','7041318','7850990','8171477','11012174','11570203','3114574','11569225','10885280','8658581','30381592','24800277','4354360','11174375','9279102','11900228','16288514','8570376','27427486','20424999','11570252','9139947','14237138','16289743','13751698','15699466','20837920','6077210','27337443','3510055','11570229','8667868','5459096','9139949','24187667','10885493','20739914','10212554','8547382','10408101','5391763','8437536','11570241','17198831','31924079','8567966','10408113','2646336','11570173','3511419','6682687','24828215','19052613','5307459','5460882','3872050','33319865','3510544','6242060','4198986','11758506','16356943','13150058','2471125','5383100','10622805','11570209','24799668','16938062','11131756','13887319','20523800','8567259','2470947','9139895','2301686','11570167','12929298','9815106','6653007','14761275','12965796','8570380','24181704','11900226','11900242','11822965','5311991','3635776','9139917','28077351','2301532','8436692','10231677','8335424','15068488','11011811','11900219','2471131','2471139','5754234','15068489','2646198','6400462','5499838','11570210','11569405','8437088','11570200','2645819','11729858','10626677','22888637','9121946','9139919','12966340','11900252','2646462','13939240','12966592','13690221','13436221','4335026','9817220','11569291','7220469','14527451','11900225','11570194','11570195','9139995','11822854','11570212','24800170','11569347','6687118','5307186','4335435','24800021','11662506','13486683','11900227','6085831','5753952','11168763','10544331','12094612','11900083','2301553','5515444','22669168','13747300','8170889','13886979','11570196','11900148','11569430','11569377','13889260','29176067','11131780','11900240','7838093','11569448','12965879','3509957','12965028','5414480','8007876','5380382','13041447','11570197','5665450','9879079','13523685','13436203','13436225','11900220','6021149','22803406','14941051','15018207','8581307','8579602','18528654','5215420','11633032','11167141','13889499','10545595','13486687','7849064','3510310','20423848','8577891','24822591','22066355','12966412','22668023','4737884','5671798','13436237','10622406','3510822','12071135','11131797','3830397','12965910','11570199','7457105','11740860','14346904','13436224','12965944','8666669','9201183','6472622','26533562','17981754','13267856','3882310','11742031','11900262','6861096','2644624','11763724','9853047','20846223','11570192','8570377','8506869','11537346','9815107','8437761','14602274','3966691','7874277','11570193','28763083','5219482','11570184','24799444','11900078','16626657','11131838','11538374','11570213','20422371','11900231','5397299','8437958','28143009','19941105','26417206','6017274','10230992','15701946','13486721','22464018','9815108','11900221','10648505','15635297','27427777','9856975','22319346','3872262','7872507','11570171','5496640','7873345','17056103','29145024','11632970','24840357','11570216','5754704','13939258','6076780','11570239','11167429','2526443','11570168','9274675','23738626','7848659','8539742','21427668','11167169','4335519','22802973','18515822','6021476','3511382','11900157','16087009','11570228','7159850','24664455','7478973','8540020','11900249','12937266','24800148','16356926','11483923','6093765','11167269','3872092','8575270','2647279','11059831','11570220','25182973','10185917','2471074','10282596','13436199','3511451','11900265','22064954','5483680','2301782','11570218','11570204','11900222','6400663','11570205','11167245','11822803','11900142','4335442','12033965','9815111','11570245','11570202','14052956','7869009','2471086','3510154','15158299','11537394','9787844','3966494','7847489','12656420','12844850','2471092','8656057','8008672','11131857','11570208','13371326','11570226','2645745','21491557','7090343','11538544','11900155','17198752','6396172','8514888','11570170','11900153','9920570','16989276','11900263','9815112','7870106','11900128','11900235','15068490','20422050','8570374','11570233','7839936','11570246','20424731','5433610','4335681','13586376','11570250','13269051','11937728','9879080','3509920','11900141','8654751','8007272','11131896','11538474','10544461','28077288','5460826','8548250','11570191','12655893','6683790','3512317','11570255','10646266','8539985','11900147','30390898','8575268','11570248','12685228','8573818','2301650','11197136','5307627','11822881','7092365','2301566','11900145','10231631','3519586','11900136','11900080','11570186','11538720','6020840','11570251','12684796','12685363','2471150','11538634','8569358','8547383','10523433','11570224','11900264','10650038','8556425','11570244','11900139','5585658','28161206','6085383','14052560','18439669','8612470','13269244','4354634','8575269','5996071','3543174','5379118','13036976','12648291','4737891','6081651','8538632','27887047','11768540','11822865','12842559','3829493','11900140','6422401','11570232','11822857','11131920','11570185','11900143','30390970','21313493','11570174','8570368','3510049','11570183','8017399','4198800','9815114','8009228','11131950','11900255','11570180','11218644','4370879','11570201','6657798','11131929','11570188','30344110','3510563','11570190','3830196','8666497','14770704','11570178','11570175','10544747','9815116','25165116','22144843','5970237','11780808','11900216','11900093','11900134','2992973','10921773','8708820','14770855','11570227','11900097','6399956','4335693','3510510','11570235','11570234','6423166','8581306','11570237','15469432','6085668','11900082','11900109','28577588','11570238','26143318','2301670','16275412','5313381','13304915','11900092','7102780','3603054','8146760','4335452','4761587','5372729','36132756','5650823','8575267','8574117','8688566','11900101','14460035','5784164','15068492','16753873','11900100','8709133','7879924','5342167','2471138','6861674','12777049','13752017','11822862','11218743','4800244','2301780','5307668','6423251','18916688','22158024','11131964','3108629','10523454','11768260','11900112','10679162','3622257','10886645','7800742','6024314','10523455','14770304','8570372','6083300','13889190','27427590','8550847','5307918','11194975','11900114','6703045','6401278','8539223','16709490','9815118','32535912','11900117','8578628','11900104','10125945','10240911','4761546','2646396','10544949','13486688','11900089','5460709','24929229','11900118','14500598','15068495','8540479','8575266','11900124','11900088','3830473','12907380','3511439','11131983','11900121','3872115','6389302','5307416','12937278','11822867','8582099','11663295','10667207','11900111','15068494','4370432','8570367','8575264','14770705','7839658','10238039','10650046','3872106','10229998','20434364','8570365','8567257','8570363','2645519','11012690','9879085','11900085','8489821','8567250','3510150','3511427','11131996','10786194','25114387','5743091','10229267','8569346','3511249','8569941','17198763','8575262','10545236','2823195','8575263','11132002','30988427','14052009','7846833','15696038','8714813','4370578','8570366','8581305','3509994','19476547','8575261','2646813','11132038','6421519','13030771','5421127','22455325','5296851','4787683','8570364','8575260','8575255','13522916','8575531','2646713','3510143','9856916','8573816','6083722','11132012','8575258','8575259','10885151','4197775','9879088','8575256','2644197','9853824','11218724','11218811','9280316','11682831','4774177','30391336','8575257','8575254','11827838','10136968','18456149','11173081','4787748','35430798','2992702','4201642','2645682','21891097','3510570','6422538','7792488','11132103','11132087','13889711','23916486','11132053','15068498','25870871','13751719','19585255','5515474','21491519','11132067','5754705','11219084','6020968','11822869','30350439','2301607','2301645','8559777','6021834','12060492','11219009','13406917','8562435','11741900','30423336','8575530','11763396','11219097','14770266','11132122','13892926','8538306','8538971','3966301','11212743','10643515','9879091','11132109','10885960','8578627','11822873','13406990','24839994','11132137','7455532','11212058','5784541','11219066','3618507','10643396','30436219','11219035','9879093','7849748','11822887','11822870','17670915','11219126','9815122','10620314','14925778','9879095','5391977','10621530','4197760','19653302','8540145','8581304','11822874','4787735','5313638','8017073','6023939','10650582','6379840','2301561','8569359','6704727','2676815','8579874','8123585','14770709','7812092','13888642','4323821','5310222','5995278','15068500','4335773','4738024','13308793','3519583','11856326','11132153','3829452','5393540','11132167','2471066','2301714','22533417','7457619','3872351','22455683','5654358','14770714','11132191','24799602','9879097','13757228','11219270','2301888','8569345','11742441','26964735','10229964','16098865','13975231','4800037','8569353','8569361','8567249','13067428','24827093','10650297','10240906','11132201','5514985','5431049','10154903','9816003','26995412','17606122','4787741','10679214','13486707','13413752','6682316','11763486','14768805','36248998','8569360','24799848',
 '5371859','2301784','13857462','22463815','18902621','8547384','2646484','2647601','6381146','3512339','8578626','4324986','9724621','20822632','8581302','9879103','8015048','19473842','6871774','5296799','12750873','5654841','3511278','8569342','15697472','13662913','3510581','5540508','24799354','8577895','4335398','6400193','10238029','11195789','15199116','11219189','8569348','36129111','22803120','11219153','8578625','22157115','11635286','8569352','11763481','31164692','8540556','8581301','2643708','13690339','25853643','7841377','11219256','11219217','12774685','14770277','7845681','25250367','8569355','2643852','11173155','8581285','11740717','11822882','24799680','8569341','8574116','14762379','4326327','8450465','8562420','5346003','8581299','14770715','11856396','5540523','9879106','13486714','8541195','8569350','8572638','11856261','11539721','9879397','8562417','7873195','7358991','13436240','14770721','13087755','8389588','8562416','33604244','6093402','12880782','4787709','35544391','13436200','9713209','14770729','10523437','17331935','10136767','25420025','8508097','10621984','20429833','8581300','11822877','6084924','15068503','13486698','13486711','11822893','7829238','9879110','13486696','5307419','11046776','25121283','9274918','7118288','8581298','13436232','11768977','3512352','3510367','11822875','25245793','2471122','18902640','8578624','13436233','13486690','10125939','8581295','13486697','13486719','11822889','9275421','13486694','13486701','13752295','2642715','10622965','10912478','8690749','8562412','9815123','13486702','24800242','10650417','8562409','9201189','8581297','9815127','13015086','36218719','8366471','13436195','35200607','18070275','22067705','6020284','9080050','8562410','8562411','5308466','8562414','8581657','8540735','2471152','8578620','11763483','11221088','5432251','5658965','8578623','30391150','10231449','8581283','13486704','2646666','11822851','15068505','11663300','8582098','11822779','11763493','11186653','22307535','12648131','18505806','8578621','13486699','3829730','8578622','7086810','15068504','13436197','13436219','2647003','13308801','8581294','9007277','8547385','10650480','5781981','8581286','9815129','13436208','36128109','9879113','11663304','8581288','13486693','13436230','8547396','14770269','2645644','5515372','11822890','5371980','8578619','11186679','10622657','14501069','10137863','8575529','5345968','8581292','5309989','22214111','12205376','7828955','5671934','8581296','14770287','14084211','5292234','24840190','11822883','28577833','6382052','3957991','6417654','11023010','9279876','2301811','8578618','8581291','8581290','8147091','14770725','3599886','11822772','5433850','8581284','3510387','13756716','11763484','8581287','9815130','8581275','13436218','2643305','28948665','9879115','6082011','17861854','10231510','10523445','14770730','14345399','22398317','8581278','8581280','13436213','10643638','10125889','15068535','2301562','8573815','2301765','9405347','3598973','8514788','10679296','11511302','28507791','2632357','8577889','8581273','5391141','20422849','8581281','8581277','11763490','10678953','29176861','4354967','8575523','5307497','11763485','9460856','9879114','8581279','8581272','13939262','14770732','11822775','11827847','13893011','8581282','8581274','5419638','13436057','8581270','11822885','8581276','14125356','10622184','13887563','10523434','30380740','11761468','15380682','15068507','5658843','14770309','4737982','31675480','31241061','6078654','26995069','9879119','12094112','22188485','10236078','11822805','8515092','5216252','2644388','4325341','10238015','9139875','5647891','9856918','5307359','11173173','9790595','14770272','14770279','7829132','8547386','21613282','8126335','3511369','16836645','10897398','8575528','11822895','15068508','9879123','3510462','5514564','11899298','2301704','11663306','23647021','5514628','11012490','11856262','2301571','5301375','9815132','20423196','4339815','9879128','12072428','8574115','18522935','2301887','14770313','5514885','8124518','13857517','11221657','12648134','11763498','30987957','5751524','11167976','8539092','14602273','24823926','3509767','14770291','11763496','5461138','15068528','8547387','11763495','24832235','11171838','8567283','2646580','21174270','3615531','3510377','11822776','11827834','8489246','5391844','3510808','13308808','13892530','9879131','14770299','5392227','14501072','15068509','3872221','2301672','8681588','8690106','20715687','7690540','9879134','8573814','8579873','19493394','9815133','24838639','5301009','8574113','8007691','8575527','8149582','3510467','10644255','5312741','18902626','14770294','14770283','15068527','18917049','13067429','11013202','6422035','15068510','11663307','18902650','3966931','22886620','8572636','3512345','13746532','8574114','13939328','2643776','11784169','5540451','5383570','5782368','30390697','8567258','18918602','9139958','8148306','24840069','25244710','14501073','12380815','18918906','9878984','8567293','18902636','6021301','24799369','2301777','14770305','6020488','5660519','11221652','5268462','15068529','26143192','8540728','12094275','3510117','4335502','6081187','11763399','22778455','2471045','12908035','22337351','2301866','20533659','8547388','10646049','3872085','11167901','15068512','9879136','8575525','8558179','13587097','11822802','11663309','2301793','8571133','12072242','20424031','8574111','6021183','10498162','11013395','10646122','2471053','23153123','11763397','5393912','3882760','9879003','8547389','11822897','8539701','6024382','5784879','9879143','8574112','3510011','11822898','10622732','9879139','8575524','14770303','2646741','15068521','20421583','13308814','10667958','9329702','11173360','11167869','8569940','8544933','3510427','13104903','15068515','2301812','4243120','8690369','4354465','4325168','10670877','4761572','6380079','13756701','11856264','11634758','11221655','9878978','10162914','8536572','6085514','11193106','34776030','36173492','12071978','8538287','7992138','11822808','14501078','10811911','8381122','6400760','5540094','10622537','15068522','4325343','9878988','9815135','5996763','5215112','22803004','5783399','9879006','11192765','3511310','11856268','11763400','11634780','13890614','9878994','9878993','9477984','6352905','13697356','28397591','11763403','7837746','14501076','9139955','9878986','8575526','8559512','13308822','3615599','7845323','11186654','24799752','13406957','9878997','6053296','11822782','5280292','5419641','16892757','5743685','11012312','5782495','8301284','9878999','9815139','11763407','11822827','29401751','10696367','6077692','5743365','15068539','4354327','9879000','13308839','6023878','3510486','6022691','11173390','24773667','9879022','8569917','3510068','5307522','11822790','5432482','2471027','15068526','7849956','9879025','11663310','8545276','2646295','3965904','7848433','11822784','15068523','9879007','10238023','8573813','13067430','13890572','8567292','2301578','13407036','9879001','20846523','5653903','17054415','13697364','11856265','11763449','22779116','13747292','11168387','9879009','9879011','20425575','2049136','13217575','22803451','9879014','9879008','8577897','6082546','10884286','3830253','22276082','15068531','9879015','8567289','18916378','6932785','8547390','6084497','11763452','4737904','9879019','8547397','13857820','2644814','13744946','15068540','13308827','3510139','9851865','2837651','3966853','11856269','12648135','6652040','9815140','27879205','11856270','24800015','15068545','15068533','12095306','10941041','11763415','17657355','9139933','8566284','19797941','13939337','11822795','10498169','12929245','11513586','25258606','11663312','19043347','19049852','9201187','8547391','13939338','10265137','8381601','8569939','8569937','8570440','8579872','8567254','8544934','9856919','3609380','11763413','11173415','4773986','2471093','15068547','13407146','2301544','8582096','13726370','17322713','8433001','12093963','13406860','6653303','8613277','17660804','6083121','3830228','14501082','10919762','15068534','15068543','5751538
 ','9139960','6702707','8547392','16785103','5395538','25861229','22851078','15068542','10520471','24173137','5214691','8567286','8569489','8572633','13752012','10886906','11856273','5982613','13041538','5986098','2301568','11663313','3509936','13939300','8381410','22545708','2470962','8569700','8019965','11735485','27630072','5516397','13890444','11827884','27194341','36139397','20434148','5658327','13308832','3511261','25421138','11221659','5985057','15068549','18919287','5312602','8579871','8017342','14730420','13306790','11827837','3830325','11763418','3948168','4198151','4354428','10231173','8573810','8544936','14602282','11741676','11763426','11763428','11763437','7050177','36139412','10153362','9815146','8547394','3511216','11822785','6399553','8573808','8547404','8547405','18515697','11221663','7845809','2471036','11168143','15068551','10241477','11663316','11763431','11212891','15068560','15068579','15068580','2301768','2301530','11663317','8582095','13939209','13744981','3511364','12744859','11856279','11763433','11763443','7844060','24800038','10137859','9815145','3511300','11173437','11168303','15068576','11168452','15068569','12880482','13887060','8582093','5307358','11740437','5498363','11046828','22803055','5753892','15068561','15068578','13518112','11663318','8547395','8547393','11822797','5461271','36139404','9139944','15068552','17398038','9815158','8383059','8544935','8577890','13316663','6078792','4198794','15068568','9815157','4365077','16626701','8569933','7874549','3509726','11856277','11012543','11822796','15068557','15068563','15068570','15068555','18916387','2301594','9856921','11643450','11763440','11763445','32734224','15068564','4355080','8579870','8008008','13067433','11856280','14501085','11168246','15068556','15068559','15068566','9815170','8547413','8150938','3967043','6085073','19474810','11168349','13889537','9815149','9815153','8581674','8538301','8547398','11856274','7839128','36139406','15068573','15068574','16392024','16626706','8544937','8547408','3510456','4472954','5515535','11763410','11856281','5391576','22803191','36139408','12881525','12880963','2301647','7763616','7757975','8547399','13758586','10671144','11763446','11634690','11822798','11221653','36139409','12478899','8573805','8579869','8547412','8559516','8547400','13308914','11013364','7049985','35549101','2301638','8573807','8544940','8547410','13308836','11763455','14501086','12309331','5874556','9815152','5666711','8580033','8536637','8579868','18919073','8547402','13968517','11856282','12028805','36139405','36139410','8906587','8537765','8547401','8547409','11822800','3830451','4367315','4370929','8546584','24664067','8547406','8547411','11763448','12648136','18916389','4335697','8140305','11764323','11763411','11856293','11173497','10136768','3882126','9139854','13893217','11663319','8572632','3610248','11822811','7795207','3830079','11171802','14768798','8555647','8569930','8579867','8544939','8544938','8577892','23911119','11856302','11091339','11827852','7845385','14501087','10766714','8539351','8544946','3966550','10523452','24830275','11856284','11744671','13408289','30390769','15901026','9201194','8547407','2645327','7881030','11856287','11763454','11763459','11763467','22826605','23740904','4197919','5577285','5299162','4325243','8573806','8544944','9030401','23607396','13308920','6084587','6417163','15161078','22803281','4198828','13891019','5751835','8615432','2301783','8554295','8144732','20720038','11764317','11856291','11856315','11763466','11764304','11038628','2301543','11663322','11763461','11763463','11173525','11173575','11221661','13889240','28742924','8544947','13726339','6080559','11856285','5386402','9275346','20714863','18919283','24347505','8544941','8544942','5307301','4334923','11634649','11856290','4370950','5302005','30390788','5307546','13067437','2301529','9815161','23974791','13308844','8146577','11856297','11856309','6418985','14501088','5751575','2301773','2301870','8579484','3510589','5296506','8582092','7040946','11173733','25104153','9139893','9818367','13408286','17054515','23606004','11764315','9841959','5371239','16084326','16626620','2301905','11663320','8572627','8559518','23153084','11643582','11856296','24799988','5391883','11757528','14501089','10265341','16626691','11663324','10231081','11764320','8539133','8550014','8572628','13858442','16626629','9856923','13758631','11764311','11856319','11764333','5461182','11663327','8569928','11663328','13744980','11173551','11764309','10114617','11169109','9815162','8539059','3510666','3511335','2646885','11856321','11856306','11856312','8559732','13757225','24926410','10501631','11764325','6421668','7828415','5782820','9274888','2301767','8538686','8582091','2647595','11856316','11046949','9267093','5540591','22803202','14501091','2471135','4350807','13421171','8582088','3635046','11764314','11822812','6416633','7797712','2301563','8582089','10501606','11764313','11764338','11764319','22802990','24799263','14501092','12070473','8579141','3966570','2646236','11539951','11764341','11764346','10499084','11764327','11822813','5983510','5783424','8562309','28216568','8582087','21505517','21506704','11856323','10498200','11764328','12929560','5384908','34776205','14501098','11511285','8569924','8559520','3595043','11633327','11764326','5460962','7785039','3881941','7764635','28765225','8559519','13308924','13744951','6021559','20871717','16786189','11764342','11764369','11764345','11972518','36155298','25540378','9727080','5314234','14940950','26964013','2301533','3511460','3510934','11764360','11764335','11856324','11764332','11173607','3829663','2470943','20425201','30498562','11764351','11764354','11764331','2783019','25189215','8550480','5433650','15462476','13087758','20423568','5653586','10284678','16392096','8572626','13969627','12507552','11764361','3882337','14501095','2301636','9856924','13307105','11764363','11764355','11764366','11822818','11221660','9680514','5414192','5784453','4272091','8569921','13308909','3510719','28436867','11540587','22803155','14501094','17398040','21251104','13308876','13749284','3511208','5515176','11764362','11764364','11822815','5384811','5461168','7813115','13435318','8562406','11663329','3510125','11221707','10499496','27331122','11221709','11190754','14239085','7445652','13887117','10265431','11212651','14501101','9815166','2301771','8556571','8570489','8559521','8006925','2647091','10501622','6082668','17198724','13888728','2301658','9726890','10695825','3510003','6083859','11173632','5540397','5422111','4737948','11186492','13890132','29942499','8537970','8569920','14132628','3612455','6024528','13304274','11822823','11822816','9267140','2994016','2471082','15699125','9815169','4354774','10211316','8572620','8559522','3073731','13758633','11822837','14501100','13892589','9139881','4354785','19502339','5314268','5514687','11643477','9461018','22156990','13067439','6082318','11822839','11013464','11047004','11743913','5742890','14501105','8571136','8559526','8559524','7867083','4334942','3511455','24927137','11822820','11896441','14501099','5743939','2301814','9815174','5346396','8559529','3509906','13858565','6078271','12330256','11173657','11221664','7092066','10546751','2301586','8577893','2645312','3509996','5307646','5498942','9461106','6436024','13523273','14038489','8572623','8572621','13972862','14138297','24842667','10823252','6400013','12648137','13408295','8568060','8569913','8577896','11663330','11822830','9687135','25121379','2471049','2471101','11757508','5667858','8537873','14941321','30390766','10275459','17198676','11663339','8581672','9856925','24072426','10667798','21499726','4473480','11221675','6400183','8357299','2476575','2782938','2301815','9815244','2301779','8541199','11663332','11822833','11221668','22802784','14501102','7153095','8559528','3510519','11822842','6082775','11972138','11175155','9139877','5585836','8579683','11663333','13559561','8559527','8559534','33259617','9267073','11221682','6401764','6416367','2471034','8711719','11663334','11663335','4370876','11663340','13149931','36101891','8559542','3511957','5515016','12929111','8540430','9856926','8559536','8559539','14123382','3607729','11221670','10618910','11040109','11511452','9702309','11663342','6380083','8537392','8569915','8572618','8559540','3509913','11827863','11822848','25243261','24799322','2471078','4130944','16287965','8657996','20433909','8579483','9754498','8572619','8572616','8559551','3509972','12378809','4773883','14501108','2301745','11663344','8559547','8559555','11822836','11173779','5539853','17670940','14501112','11172066','9815175','9815177','8581658','8563751','8562405','8569909','27068724','7845006','8171725','14501109','9139923','11663350','8615805','8880907','11822838','11173693','11173761','21318655','4737931','22803218','33472942','8559549','12744045','6682416','25200544','12309332','9280575','2301816','8361223','8559543','8559544','8009569','13744952','8014946','24842993','5645059','6416700','14501110','6381498','11196795','6379738','11663345','8579482','9201258','8572617','8559541','8559546','14501113','14501115','5657095','2301766','10231348','33469331','11221700','11974385','16937744','11169089','17398059','5214808','11663356','8569900','9856927','8559563','11822849','28470948','11221678','11221665','17398054','7956461','8570488','9856928','8572612','8559560','8559557','2646870','24927214','11827850','11173859','21708707','7710871','12309334','11663347','8581673','9856931','8572614','3509760','6422481','14039169','5782886','2994126','14501114','11168760','36241480','2301637','11663351','8569904','8569910','8579680','8572601','8559559','13745799','3589080','11737064','11221669','11221672','4737881','5296272','5781762','12648138','14501121','9815180','11663353','9154522','8567237','7869446','34776075','2471035','11168746','2301554','11663352','5672037','9858267','14295972','13067443','11173825','25106890','17664824','2301813','11663358','11663366','8554296',
 '8577894','8572610','8572608','10523441','3631901','11827851','5993025','14501116','11172139','10285010','2301711','10212758','8569899','9754671','24831111','11173919','10464509','5391914','9139913','20717172','17398055','9815178','9815182','20002520','8581661','8571204','8569898','8570476','8559733','3510480','11974556','11174134','11173805','21500847','14501117','12851288','12309333','8571130','8540346','8572604','4335224','10187506','7847351','22850834','11172155','14501118','13889112','13345665','14730620','11175145','11972139','5393359','5986238','12648140','11214920','15189631','11663363','9754838','9727081','21491166','5499629','11221679','5279884','5391137','36132471','17198767','5301323','12101510','9815191','9815181','2301772','11663368','18917037','4272102','9856936','8572607','13067449','3510032','6093445','6421563','24822804','9755685','2471088','9815187','31067833','8536826','8572602','8148902','13744942','6020661','5783335','11024328','2301910','9994454','11663362','8576814','8562431','8569895','28453132','10162399','3510676','10910067','19044944','11827868','9686484','36173493','12648143','12648144','5658794','32935280','13406933','13406862','11663380','8550483','24346380','8541211','6703637','9723833','8176250','13692677','9275471','11186689','11174217','11174260','11827853','9686936','6416436','9815192','22160218','11663388','11663384','8579480','9856932','4335710','27282699','11174248','6085259','27391161','25181941','36169773','11172104','16286640','9815190','11663381','8561211','8556572','18918671','13757290','6093370','11221680','5583763','22155513','5461071','22803540','7270553','12095075','11663370','8545278','8569894','8569884','9880363','9727082','12908340','3511443','4334980','11174052','11174001','12309335','14761117','11663373','11663374','8579479','8581666','11663404','8569893','30415160','6416786','7050094','3829612','24799844','10546931','5783171','16315578','11663383','8539523','8556576','8569891','8569885','8570481','7846529','13744962','11221705','11174114','22353584','25859597','7451800','9818368','11663377','8579476','8550489','16824166','12777782','9856939','8149917','11174163','13085166','11085828','12648148','11512051','9815193','8571152','11663393','6657167','8562311','8569889','9790341','8567230','9754854','9856940','14128942','2647193','13971012','24800194','11172210','13888559','2301657','11663389','11663392','8579472','8579473','11663405','8569879','8175716','3510340','30381070','8681366','6688706','8579475','7797843','2301903','10231743','8569881','30362425','11223450','7843320','5398747','2470949','11024097','11511635','14771291','4366206','8579474','11663394','8579140','8570468','20898325','11974607','11047108','5944636','17862309','5753717','9275216','2301775','8579471','8562442','11663409','8569882','30391201','9856941','4131066','13939264','7872312','11175148','14540953','11511901','20423022','14602257','12744482','11974716','11084257','11172175','13087756','2823755','5314289','11663406','8556578','11827866','12415843','19474828','11023213','5371700','7092159','18903990','12963575','14275943','13972224','11827843','11827857','11827862','11221710','11172234','11048364','4370995','2301602','8539001','9754858','9856943','7840092','2471108','11511580','15702094','9815194','9841949','6024716','6080737','7765728','5982989','17054422','5314320','8556579','9856942','23983418','24173341','9275137','2301896','6380612','13408287','24261964','9815196','16291486','13067456','3625609','25120918','5995748','9280587','8550683','9754872','8008236','13939276','13405803','13744941','11643499','11221685','5269959','14771388','3172699','11221684','9139929','12648157','13887583','6382909','9856945','8537736','2645070','13857817','10501633','14466969','9815198','9815200','4370853','8579682','9794797','9787715','9754919','14730406','5292219','11047218','25246360','36273352','5372824','5432583','11048870','2301791','12063773','8576813','27203472','11782407','3510630','2647571','11679617','11643539','11974739','17869308','13087760','5576355','13893490','2301542','8561206','9856947','9856949','8554078','8169004','3511178','11827860','7846688','13232369','12648151','12648153','13886617','11535599','13888375','9139894','4355063','2301776','8554298','5655571','8570475','20693284','24843115','11221711','11221696','22803387','12648171','10912500','9815210','8545277','6653218','11175172','25107331','25183187','14245808','5981594','12648165','10534007','12648174','11511698','13889080','4354554','2301781','9815209','8567282','8550482','2301899','13060977','3634823','11221702','11856833','11974759','24799799','5540540','12648164','12309344','13886645','12070335','9815201','13435349','8570487','9856951','13939266','11221695','11974788','7839275','5432322','17669525','3881276','2471117','11153045','12309339','12309340','11169057','2301728','8579139','9754939','8559734','13756710','8691005','21490888','13988450','7798669','12648168','17327823','11511800','27645208','13061225','2645568','6021403','11047266','25200678','22802872','24690981','9842207','30350923','10230625','17377605','9754931','13744982','13067463','25240985','12929584','24172736','36200425','7823697','9815211','9815213','5314298','9787600','9856954','12937275','3606550','5301800','6418416','10617488','2471068','12309347','9815206','8540323','8550510','8379549','8579679','13744978','13500544','11783217','11175149','5459011','14339685','5386232','19831650','2471043','20714391','8538953','7955064','10136152','4266664','3510041','11221703','10501672','11190774','9139871','12309345','13889346','9815230','2301790','2301715','8554299','8562423','8547456','28763985','8570467','9727084','2645178','11827855','11047307','6399555','6206315','11024307','14771389','9856956','9856957','11740294','13577207','11643565','11186652','11221681','7845535','11174081','13889357','12309351','2471097','14281946','9818369','23114345','2301564','9856960','5307599','11636286','11635988','11047342','12929102','9753732','13408059','6379749','9754962','9856961','9033574','11175151','2476569','22678095','12309349','5667025','8571148','8550484','28763415','9856963','9856958','13746313','28836997','23156605','3829707','23365063','22803250','3872067','11169240','10521932','19569209','13886640','19801720','8571139','9790536','9727087','8149522','3510550','7992186','11221699','11175169','11899527','6399993','8785797','2471058','12309371','4339768','20537148','4203899','9815216','30391191','8559737','14130765','24924069','11827856','10921582','3882366','12309352','13408407','8579138','9856962','14762398','13970282','6022763','7764970','12414944','5431149','5381301','12578468','11211808','15634669','12309373','2301560','12064194','9815231','20422640','8550485','9856966','9856964','36342648','11190815','14392113','3882636','12309374','2301829','16315919','4370809','9815234','10206239','6380704','13692185','10523439','24827427','27199854','11974823','5983126','12632861','30391241','8454382','4325456','10944012','5383120','10621342','12309356','14771390','10522585','10230731','12778366','18919133','10501608','11974858','12929140','13987823','20715119','9139959','12309354','12309357','3882349','5754156','4276819','11195154','16312730','9790435','10886992','11186665','25178664','2993448','12309366','12309363','12309362','13087757','5654690','12063520','8562308','23307791','9727085','2647640','13858136','8169872','25180343','9139883','13888810','9815218','2301769','17054561','8562379','8555649','18917046','13692699','13308820','12937284','5536213','36211305','7451198','17672622','24799828','10617613','14771391','5671611','9815217','9815224','12752108','2301898','19717057','9790383','8570474','9031524','3510597','8147293','11637249','32241434','11175152','6421849','27375010','5379767','5279808','5393408','6401618','10618420','22318570','2993607','11169197','5650517','9815222','6637727','9201253','8569801','8550487','9815235','8579681','13060751','14005532','6085594','11974891','9114821','8488259','5782732','14771394','5311811','2301862','2301884','13407040','8581669','8550495','8540730','8167752','7811744','7847118','5393476','12908080','8454509','9815227','6381610','8562404','8562307','20425771','8563333','8559738','21526959','2646602','24927726','10777213','11974997','5461256','4198000','9815226','9815232','9815237','10192774','7872948','8150106','5280309','4737865','30988718','2476576','4198539','5743732','19382877','2301808','8579137','2301755','4800237','5494623','5373192','10617995','17327856','6380962','9815219','8571593','4110741','19726540','8570473','5307212','21885700','11186673','25105479','14346986','7090564','6861779','9815238','2301689','9818370','21790938','2301708','8554300','8559740','4335689','23591977','28436968','5274673','11152680','2301778','10240916','13407029','11758567','8550486','9047760','8579136','9727088','2048840','3510708','14730409','36149435','11975012','11975082','5265675','4737874','22803013','17327832','10285037','10284815','19384005','2301585','8554301','8550490','3510556','4335505','2646533','13752315','11175162','26807265','7841042','5578292','10619544','11169274','10238008','9815239','13435747','17051473','8570454','8539689','6638788','8554079','13751712','11047381','24838731','5993220','10679373','11174614','4354857','2301733','13408712','9815225','9815240','8311442','9855841','7796482','5654273','9994637','13858133','5307223','10523471','5499555','10792065','36124027','4277324','11214869','9815241','10238026','8550488','8451170','8559739','24928411','5277417','5400355','11148154','11085702','13893438','11169347','10811947','15461838','9154678','8576812','8581668','8571132','24423477','8575682','8559751','9723835','13061480','13060759','11972141','11975110','24822679','5392507','5950467','5385214','14771303','14771398','13408292','21787970','16290041','13407026','10231390','8546585','8555651','8579678','9939960','11636079','11975096','11047407','17850559','5265837','22533445','11048809','11535827','8569813','8554302','8562444',
 '20257515','8570427','16937047','11972142','10498210','11975170','13206758','21944761','24800217','2994165','25543656','11213373','11173399','9280599','11197289','12751860','5310117','8488298','8559744','8559752','3598857','11898296','6401674','6400712','5417073','25199701','36155306','11039786','14771399','11169259','6654438','17377609','8570466','2647307','7090713','5461228','2471133','20706087','7300588','29946944','11173559','5654359','13407110','2301684','8570448','8576811','5346183','8139690','13396669','7160033','13747294','21264038','11539819','11975290','11975315','6052698','5269536','36128383','12607079','11223217','12101597','20001947','8554303','8555650','8550492','8541192','8562401','5498442','11186692','11975123','22424889','6399834','10621408','13889524','5782148','12572130','4339775','10137862','2301555','2301809','8571147','8570486','8570470','10209522','15697826','2301685','8559742','14730413','13752015','24928123','24982273','5393482','7847710','11174075','5296382','8148601','8554304','8550494','8550509','8570482','12873124','13858432','3613747','14131479','11975267','11975412','11175195','24799244','6422299','7794282','16355811','5371133','3873073','10439910','14427958','11211691','11174660','11169216','4110912','8576810','8550508','6652105','23015503','5648007','8559743','21571428','11636187','11975433','11175157','11831186','12853039','11211944','13889994','11148490','9139912','14771295','10632931','8571145','8554305','8554307','8550491','8550493','9727089','8555652','7867274','14762383','14730434','11783218','24830465','26806822','6242120','22802851','9140006','9139954','18905794','9280622','11213437','8308128','8575797','8554308','6636658','8550500','8579135','6395826','9841892','8559746','8509763','14730441','24181693','24928287','19846159','12092530','11175158','11175164','11175166','11190768','22802707','13887107','12872371','13408401','8580032','8571146','8555654','7992297','11975461','11975485','5983387','8787641','36228931','25096379','22803164','4324938','4354415','2301701','8562436','6637453','8579134','8570485','14296046','6021542','24927001','12093502','22599013','2782862','13889662','10153266','10212054','13407034','10228233','8567281','8554310','8550496','8579133','8460037','3510819','14130770','3626612','4335217','24925404','12092455','12093064','22065187','6418624','22167034','4198769','13890154','13890211','14281947','14771298','8554309','8550498','8550501','16277584','8559748','8554082','13746533','5498895','12389265','11175189','12092488','5515631','11175199','29175031','11085746','11174792','17327846','13887025','5654570','8576809','8555657','8536959','8566769','9723836','14602276','13698737','3510794','8515576','10501620','11636242','12092471','12092632','12093130','12093721','25852733','32392557','22802891','6858139','36138423','10679262','11212854','13886973','18916757','6384957','16133848','8581662','8571134','8550499','8556228','8510386','8554083','10523438','6081837','11636142','12092656','12092792','12093247','12093265','12093448','12093561','12093612','12093968','12093990','12094010','11190772','7839418','11212105','11038662','11252968','13890136','13890058','8562432','6380925','8016024','13060763','5514918','12092743','12092874','12093038','12093096','12093350','12093375','14347008','6958039','24800090','14768801','17327867','23543785','13408300','8562305','8579132','8570443','8570431','22765647','9727096','4370601','8559750','8554084','8554093','28756493','9260050','14137336','11972144','12093481','12093699','12093750','12093818','11047432','6421494','10910111','8581660','8561329','15799903','9939805','13939243','13316750','11144787','11091534','22668116','12092706','12093005','12093289','12093322','12093848','11190762','25860502','3107900','14540932','11152961','2301890','2301703','8576808','6656585','10231345','10209240','8541028','10206723','8562332','11106791','2647610','11186656','30192062','12093212','5282515','5296726','29996414','11085696','16168420','11254956','9139977','14771309','8390560','2301646','9818371','8149150','8576804','8576807','8550506','20791523','8554086','8554097','3503678','5492883','24925482','11047506','4761624','5983790','24067475','22274544','8550502','8550503','2301720','10206769','8554087','8554089','11091298','11190775','5399249','25245060','11173850','11148460','11212810','26073361','19042097','8576806','8571143','8545279','8575665','6638614','9841886','8559756','8554092','3510575','14730823','8139605','6021048','22803579','3881516','11174521','13890050','14771313','14771307','8531028','8933656','2301807','10238034','8570458','8550507','2301787','8579131','8575681','8554091','8125164','18515806','14769353','11898297','25184808','13085038','16356620','6427302','10136772','5460930','12960288','13887513','8530950','8125245','8580031','6395874','27612803','8559755','8554099','8554090','8554098','8554094','10210991','8150598','6082937','6082429','11190769','11186682','12929243','6401438','6400496','12688523','7755344','2301902','2301691','9818372','9687650','8571131','8580029','8545280','8570469','13757137','8017126','12076120','11175176','11047562','27617190','7847774','11049528','11212432','13890867','13407997','8552915','8579130','8575680','9280638','8554100','8554101','8554105','13060767','3509816','13160944','10264825','10499093','6424227','7653411','17672049','11174763','11049226','14771343','30390849','9880421','8562302','8540725','8579128','5314376','12073058','19712569','8562399','2301774','8554104','9723837','11175198','22255016','11049184','14771316','14771323','14771319','8567228','8538694','8579129','8575679','20425989','5346229','8554102','7845689','23910476','34776140','5265887','12379115','11082701','15504941','11152272','11038737','5312684','2301785','8683694','8545282','6639234','8562303','8570438','8575678','5651088','8436911','2645353','32522942','10501670','11186671','11190812','11047628','10813648','25121495','25200374','3872337','9139953','11581124','11085902','13911264','13889248','14771328','8122961','8567270','8568867','8554272','8570445','12022302','6689400','18096982','11175193','11186669','11186691','11047670','5461079','6418496','5651125','11148249','2471129','11174721','13889552','9727093','6395469','8567279','8567271','8545287','8537762','10208316','20842469','8579125','9727094','8567231','8008610','12937270','11175178','11190770','11831192','10626695','6427107','10106013','12929138','9139976','10386210','10912498','14771334','8690439','4205203','8571592','8545281','8550285','8579127','6703393','22919009','14322834','3605717','24830717','11633876','11175171','11190778','25246969','10792930','11048799','11048852','14771349','2301558','2301556','20533293','8569686','8567276','8567300','20781424','2646424','14762377','13745804','2647699','30362203','11190845','6416868','11212391','13886651','13891810','14771340','10809518','10633458','2301574','6652370','4370808','8575677','8575674','8460711','13746537','5307229','28395991','10501613','21658477','11783221','11175186','11186668','11175173','13988873','25190592','31256205','10812085','30391029','13407114','6379715','2301789','8545286','8579126','9818373','8562402','12094441','9727097','4354682','5307578','8148810','3627397','9070774','11175197','11186680','11898301','5441885','10789968','3882061','11085784','11038921','14771346','10160771','24347598','13406861','8579123','8579124','36229703','9280680','13060774','8168074','36161693','11175185','11190783','25113082','26393555','7050057','11742769','22598832','3872277','11148534','11085761','11048880','6994209','5668695','8378868','2301742','8564183','6655149','8555156','8570477','9727095','8144187','11729747','8148556','3511186','10498170','31714666','24402112','11175188','12898901','11039803','11047751','5539971','24823104','3872346','13891526','7222152','11038822','14771354','13889531','8710781','10230452','30391696','8580973','8540744','8575676','12937290','11091324','11972145','11175187','11190779','14394210','5412750','11599998','9487978','10910119','11535225','5782796','5671478','5342022','8567273','8540349','8579121','8579122','8562396','13757229','13857889','4773730','11190776','25184664','6417436','36128379','9279186','25118820','8015537','13891553','11049240','5672518','6395656','24962525','5668021','8146440','24346204','24347297','8563787','9280659','11739802','13060776','22665003','3510755','3606095','5102266','12329499','11175174','11190795','7763839','10625771','8788665','6241994','13094633','16087635','5579007','13891522','11173697','20537558','24102419','10523453','11540967','10501625','11972146','11190800','23740835','22803368','25402422','13888378','11173621','2301673','16286058','10154996','8665950','20790802','8568042','24347539','8575675','26673115','10204251','7881358','8008072','12929118','6242333','22334743','11221877','22905373','14771358','13890100','11534928','14771355','11048007','10812974','6380216','12873487','9342794','2301845','8570480','8575660','8575673','33319859','8562395','8550144','13746310','8178685','3511038','11176943','29106305','11186685','11175182','11831201','11047842','5280547','6401073','25199470','3872292','11212141','2471084','17312349','11174739','11581206','13013581','11039150','10812009','10812211','2301536','4276889','12779291','16392052','13406886','8545285','10209317','8575653','10209292','8575672','30390994','8562403','10206783','10696103','3510849','36178709','11783222','11633439','11190780','11190784','5497511','22975679','6416909','7787944','34618042','2470936','12853040','11085804','11212832','13889122','5720948','10812764','6381818','18254700','6335991','8575668','8575669','8575655','8575671','9819031','8006763','14602283','10501643','11634026','13035618','5540481','25200139','24799732','3872503','5586061','11536929','11038962','14771381','11048205','9140007','10809622','6395369','16392032','13408630','6380036','8567622','8575667','8575663','8583424','9727103','9841919','9280675','22915456','9727101','11734260','3510419','7992614','11633941',
 '11186655','11190814','7794631','25117783','24800122','11086358','14771362','14771375','11047977','9139862','4370434','10212386','9752792','8570452','8575664','8575670','8575656','8575654','8575661','8575662','8575659','9727102','9727100','8537342','23645095','8655407','3613447','13499629','5515990','7991221','11190790','11190802','11190756','21707824','5295840','25102674','6421680','5751698','3872465','11085850','11048891','14771361','13893913','5667287','13407134','9727120','8540659','6652275','6380933','8575666','8575658','13060782','13697381','13316578','8138530','5515091','10501681','23911231','11190786','11047904','6401000','12797334','36262670','7839750','11148320','14275400','14771360','14771363','14771372','14771369','14771380','14771373','10812992','8378575','2301551','2301746','13406866','2301891','8564632','8545288','8539897','10946186','14602270','14730418','3632450','21173881','11783223','11831441','13233012','6422957','36155307','7846435','22802932','10621009','10922040','11212452','15462283','11174831','11085875','14771371','10809638','18434374','4201688','8567269','10209459','8538489','9939806','9723838','2647685','3635600','36107057','29175354','11190797','5383980','22803320','14713955','2993410','11173720','2993870','13890682','11212783','14771385','22457388','5754432','23011886','20533982','13217336','11214771','8568864','28756710','13408408','9841888','8126195','14714283','13758629','11186658','11898305','11831446','30988934','6242217','7049257','6401149','5440638','25179832','11174813','11173827','10933312','14771382','11173656','5754017','8380373','2301591','9818421','9818386','8168097','8562434','8562438','10209491','10209381','4131111','27885027','8570435','10206744','9280732','13316918','3512367','13758635','13776172','11759679','11832764','11190805','25113814','12929100','25184158','14241390','3872471','14540608','13889104','13890716','2712003','13407790','13406971','8567235','8561216','10150074','5657307','9727104','14730436','14323141','11642750','11190757','11048066','29975686','6400557','10627855','7846555','22802699','5440603','5576454','5782496','13888852','10812920','5782317','13407116','12479340','8541414','8540990','8544732','9723842','8148031','8120851','8125514','13757142','6020883','11190808','11190764','11190759','11831452','21711187','3872412','13407139','23015282','21250549','6382472','8568872','8580030','8570428','16824105','9280701','3511199','13756704','13060807','10883345','10670435','7992259','11633504','11972147','11759674','11190765','11047960','6423175','7051742','24822868','6417732','2471141','14274564','14459984','13891801','10139468','13610520','6667460','10230860','12851240','8562394','14730424','13060789','13060803','13030731','11611412','11186660','11175192','11190766','11831525','7768785','6416959','12929122','5270784','22803595','2471151','11148429','10813565','8561328','4354726','8570429','9810482','8172071','4334978','10523432','13060792','3510162','7846041','11048114','6418726','5400486','12938180','11221138','11254561','10812721','2471146','2301796','13407020','10207091','2301639','30390950','10240899','14762391','22157303','10936252','11186659','12688631','11186667','11186687','5532803','6419178','7840707','11212764','5740577','11048068','10812171','11013290','13407107','15799895','8570080','8583422','24347666','9818389','6682547','2645036','28703872','6021242','11175190','11175200','13085145','5294112','6422807','10125870','5742954','17327879','10812594','10812501','13408298','30390982','8554274','8571590','8561327','8562421','8540434','2301897','28743417','5307330','3511287','3510727','7881191','8765659','11190811','11898308','11048187','11048284','5280155','21707953','5268579','14039118','5279773','7840489','13093721','9140004','11533894','17313356','13892947','10812640','10812808','10812543','11481560','2301549','8583423','6937805','14730438','13858434','13405815','3510528','22664210','11642818','12688554','36213738','11175194','11175191','25251361','9854327','24823027','10620919','10989038','13888552','13408411','9818392','4339707','6395553','8219475','8570444','9727106','7986211','13857521','21173873','13216787','8170685','11783224','11186664','11048248','10626816','10628812','12649064','11173870','13887210','8389729','36190745','2301677','9702421','8570460','8564181','8456033','9841891','22354984','8177015','14730431','3967037','12937260','27343023','11783225','36138624','24800104','12062222','5540095','5430520','11086446','11148515','36120032','13887805','13887199','13893969','11173759','5654367','2301619','2301893','8567298','22067273','28755322','8144894','8149782','8171014','14274811','22810496','10501640','6053568','11972149','25246268','3829683','3882174','10679243','11086384','5782241','14540933','11092547','5655120','2301721','9727112','27118911','8382712','4339812','13407112','8561326','27554436','8583421','7813611','15469306','14322836','13692666','14136308','5498316','11048302','11855669','25182527','4787721','4198777','35860603','14713959','11212357','13893983','13889414','10813523','11255221','5667439','13407926','9818422','4325393','13406871','8581603','8561320','14038669','9939807','9727117','4335276','8513485','10946349','13041215','3627632','8765548','13745634','11186661','11048397','9278891','5948832','13518784','28143144','11760306','13341788','11192471','10235850','12479495','8554275','8554283','11014034','20432065','12072986','10213343','9727110','9795141','3509894','3619832','3620547','2647625','21491099','11972151','11855658','6420939','12030789','6417821','12929130','22803259','4787753','2471063','10672380','11148371','11223329','13888389','13886728','10813539','20427082','13407541','24659818','8142780','8013820','3966162','14730429','13857468','6093665','7991599','25798010','11972148','11048642','11039905','21708565','7092275','5277402','6242400','5386066','6400075','10672378','17398060','2782931','13407130','8366817','24969124','2301655','4325382','10209419','8554287','30391275','7866942','11346479','3511401','10524752','11048449','12472394','5295850','5268114','5996962','29965798','11148590','10932136','6205962','12908088','11252932','12476041','13887567','10492803','11740337','13890587','2301738','2301739','9874611','8567296','8554277','8564171','8561317','8561319','8554286','14226422','8544557','8138884','8014823','13756706','13060811','10501609','21658401','10941013','11855676','5499799','10822692','5267915','25175588','13085168','13085171','18431383','11175786','3881653','11148680','5754668','2301575','9727121','9470467','12632848','4325176','13407138','8567294','8554279','8554280','8554281','18919292','23015646','9939818','9723843','2645712','13588042','8142621','11048533','11048575','11855871','36112930','5295702','11756842','7794007','3872129','11173893','13887692','31255692','13889497','2301665','16824133','8561323','8562428','8539554','24667192','9841896','7846709','2646515','3966885','24839221','26995231','10190576','11048671','11056964','7091011','17665383','5440734','22803232','2470933','14936130','2301598','9727119','15699229','13406916','5665697','2301601','8568863','8571588','28743006','28763817','8566774','8554294','8554288','8554290','27885096','6389969','8019538','3510699','13060815','13939212','7582587','10941781','5498258','11048613','11855895','11898314','11039771','23740789','9854032','22803175','6400070','22803677','20715367','35322691','11536515','11223403','10921865','11513306','11086475','11048103','4201749','13408293','9727111','11193888','22593079','18918010','13406997','8564177','4131221','24347748','8554293','36268230','10206825','28754501','8550145','13726367','13692225','6719122','11783230','11048705','11048738',
 '22311151',
 '24801251','21708073','6242563','24800085','29227876','2782972','9139866','11173809','15428264','4354732','4353491','18916514','36222363','8571195','8562503','8554292','10211356','24345657','12072674','10192686','8564650','9841893','5102726','8016915','14730437','13939217','12937296','3511972','3624533','24929626','10498180','6022777','9056394','11186675','11057173','11057295','11898317','25175232','22354171','22270257','27561791','14713977','11048141','11048189','13886624','14154267','10912502','31791078','20426810','9471081','18920261','4325316','2301559','8568870','8570442','8571203','9939809','24459109','9723848','4266990','10826446','3510407','10501657','10501671','6053438','11783229','27456125','11831923','11186677','11057319','11057364','4737854','5295795','19468246','5385649','5743112','11153080','11148643','10441642','12579257','11522597','2301582','2301901','13406864','9726904','23015746','6380505','10206902','8572952','5307483','14135781','18099220','11759681','11831913','11057575','5279906','5298736','17670343','7847507','22803145','6435344','22802798','3882581','13887889','13890076','11534702','6382352','12778029','17054542','13406995','13407001','4131115','10206843','14203341','3510018','3509776','8151320','3590303','11831927','11057440','9114937','36228956','23472675','6417353','5398547','5382867','2471124','12476043','12851300','11214224','11092481','9818395','8537365','8570439','10211390','10206793','9341028','8550942','3509749','7849326','8125926','14762402','13857519','24925640','11832066','11898319','11832073','6957935','2470940','11086512','12476042','11048158','36232334','5651048','11196915','18919136','8152399','10207115','8574659','10203378','24347343','2301751','10206871','9341033','2647586','8125311','8171227','8007364','3616673','22669412','11783231','11057486','11057541','11898326','24799203','6400387','17665631','6423278','5419876','7846931','11223286','13886939','13886664','11254931','13888501','2301653','13435330','8540584','8541181','12751611','10206941','8458198','10206956','11484044','9841894','9341037','13316641','13060826','22155453','10186804','7991532','11783233','11898335','11832081','5279857','10792122','9279038','5297371','22803289','12688247','10620189','11214442','11223364','13891740','12375239','11535440','36218874','5754876','6396382','24344582','6379942','8571586','2301667','8568521','9341032','2647114','13041217','36131788','26448082','11832158','11898323','11898329','11898341','5282583','5982069','27193912','12929284','10814644','5751506','13887151','36216277','36180917','5658807','16354764','9726881','9726882','9994469','19291092','8568064','6579202','6379792','24345494','9841898','9341031','8007396','11735003','13060847','5302202','21173891','11905804','6022685','11898330','10501673','6416509','8788436','22534432','13217712','20716753','11532956','10478707','10542302','16085136','10672403','12908058','11254668','11092455','36226999','36225739','13890105','9726916','22872999','13406983','34228245','13407156','8570461','8576665','8558178','18006046','30391229','10203492','24667226','20719249','9939812','18920244','9341038','10149102','13060834','13060855','11740164','4800113','6083417','13917462','11611295','11972153','11972154','36231769','11057647','11057664','11832143','10628516','6242482','12929310','4198979','11086546','31256700','12853163','10920631','11513264','17538454','36225896','29177133','8036223','2301651','4339801','10136826','9726886','23014773','8571569','2301865','8536543','8537445','8013870','7847974','33291828','13316671','3510780','13758637','36178712','7992534','5492944','6053736','11856579','11855926','11186693','11057752','11898343','11898351','24661394','12929288','5285573','12687478','5384026','5996491','5398004','24800049','13911645','12648826','11536478','10989544','10478894','11533236','13893768','9139890','36202754','2301705','13408414','9726893','10240915','23011795','6381614','20788323','12874971','8575463','9726885','8564743','2301681','5314151','9841899','14322838','36149208','11642788','24930154','11057825','10886333','7453760','6424008','10628341','19794332','5370569','28143096','7797481','25101425','11046107','10672386','13888897','13890727','6381060','7816537','4325462','30391286','13407152','10154150','9726891','9726902','4325259','10207021','8575250','8571584','8539744','8564747','8549458','9841901','18939176','13060842','13060850','8139091','6243511','31908297','36236061','28848694','11832386','11057719','11057782','11057849','11900041','24745375','24799219','10814098','13517501','8332698','5395398','5419364','5270448','17669552','11513384','11262118','12851102','11534891','13890009','13889998','9471104','9470466','23015198','2301593','23855707','8564648','8574581','8568866','33320458','14974727','8571198','6389664','8544559','10204222','14762382','13939330','13316659','13316590','13316602','13745796','3626998','29206193','13690422','11856175','11856190','11856093','11057800','11898374','6423626','22803069','22598571','23724152','11513913','7661373','11254633','5219501','4339866','13407379','13408307','10206984','8168354','14226554','10207044','10207067','8540339','8537909','6638550','23012226','8544563','31790400','9841921','13744964','8172708','13316658','36126205','11856187','11856372','11856645','11057964','11058192','11039604','11898384','10627279','5995702','12578873','11520297','11144307','2783063','13892132','13888056','13888044','5216290','4325483','10212114','2301662','31323565','13406981','6381363','8571200','18917053','4354753','8564753','23015026','9818396','10211606','31791048','8544560','5307185','11091531','11856341','11856352','11057868','11058140','11058272','33754476','36155305','10627650','27440964','19475529','5392190','11152084','11515197','25534550','13346833','11533268','11536805','11039760','17198694','11492217','13890167','5301083','5671606','31790940','9726883','20423352','13406895','8563497','6638495','8539983','8541103','8573615','2301748','9795142','13408305','2301707','8570426','9841907','8014673','8176347','14762378','14762421','14322839','11856098','11057907','11058162','11058224','9267110','11898377','11898380','11898346','11898350','11898360','14393391','22975220','5400434','5278003','12929291','12874188','10920893','10917933','9139934','12960263','13891584','13886888','10619381','35490687','36120083','2829650','2301539','8981528','9994465','8575465','9818402','8556015','8540432','9818401','12775045','20540341','8544561','9841902','5302246','5307275','13316596','14138575','14300565','8125713','21173888','7867688','10501651','10620435','10501688','22069041','11058682','11058708','11898378','11898353','11898372','25180117','13988925','7651877','25115510','10990906','11514230','11254976','35690442','13887145','13888557','11254709','13887329','13893367','11492397','11547019','11535483','13887985','36241356','8390304','2301861','8384810','6932373','13407142','13406911','13406959','8151327','2301908','9818398','9723851','11519294','13060902','13041243','11687641','5498758','11972155','5854290','5498832','11058330','11058746','11058963','11059349','11898344','11898355','10793997','10813170','28948816','5372515','5984751','22803565','2782993','11495369','13887110','11535144','2471029','36190693','2301876','2301788','13406869','10150396','9726894','4277424','10207005','8558204','8558251','2301892','2301656','9795144','8569388','8379093','13856122','18917807','8570434','18919970','9841905','9841903','9841909','8550146','11519207','8009313','14129599','13744960','13042579','13316637','13316584','10501658','11759682','11972158','13500734','11058432','11058514','11058539','11058882','11059326','6242095','27624614','5295772','5296213','36010062','13215121','22802805','22598104','7549550','3882345','11260436','36157214','11086557','11580450','11086591','12476045','13087736','13893494','26321741','5668260','15203208','2301867','8552642','8561214','15701355','27882975','9841910','9939816','9341040','9341043','2047712','8150324','14300587','13060861','13060866','13060889','12937282','13060879','8697072','13213228','9140910','11058579','11058857','11058923','11059048','11059193','11059378','11059642','11898366','6401660','4737897','10131568','24690522','7714781','11536705','10918601','10920632','4197801','12476049','14540934','10933858','13892195','12376511','11223011','26234105','5666223','19714735','16278572','8571580','10211474','31790524','10211592','9841906','8146950','5102031','14130787','13060873','24831348','10265399','36123477','5514716','11058395','36226525','11058645','11058995','11059251','25243616','27565823','5431640','5414064','7755886','5383889','36271942','28063123','11086527','11262755','25403110','11514023','11224164','13888960','33134622','5346623','8126060','2301885','9726913','9471066','9726898','9726899','9841913','9841908','9341056','27427341','3511508','13041218','36243448','10498196','11759686','6084095','12688746','11059411','11059488','11059597','11898369','5281952','9246107','12687365','12600056','2471153','10402631','13890881','13887831','13893492','13890174','12376395','5301246','5668269','6379927','2301623','13407032','11394979','24345286','8539214','9795146','10211519','9818417','8544562','8538467','8015777','11737492','14130757','13060883','13060937','13041226','13060917','13858149','8147792','6081755','11972159','5514867','11059555','11039522','5498538','6162204','12929301','25115872','17669931','5754730','5783186','9140038','15516956','13889979','13887933','8307686','30391685','31790441','14940511','13406913','8387098','13407027','8555149','8540689','8540777','18918556','7788287','10211438','5342385','8544564','9841917','9939822','9723856','14602269','13060894','3616796','13979019','5498321','9071097','13917392','11972160','12688691','24800008','12411764','7837886','13201465','22802907','12874126','15379678','11175768','11513458','25397963','11039996','11221486','13887864','9139856','10619163','11492695','5751410','11534662','11535418','13087787','5654888','2301683',
 '18906084','12754182','9471119','5311790','8168941','9818404','8571199','8571572','8571574','4243100','8149994','8540792','8540986','8566759','10204152','8544566','9841912','8544587','9341046','9341048','8006635','13939292','13757231','13060968','13317012','10501674','6093511','10885800','11038235','36108001','5515642','14345377','6401059','7768171','11742724','17672338','5282854','36261988','22457637','11223230','11223351','10990644','11223318','10990009','15696036','11220094','11219898','13889199','13888623','12804371','13887152','13892378','13888200','11492826','11493185','13889129','12376639','11534817','18433451','5301355','5657374','5560725','19653580','20424275','16824152','9726912','9726922','12753990','2301682','13407140','8152636','8570436','10211639','9726908','9841916','8544581','8544569','8544571','8544578','9341052','9723721','8144503','13041231','14762418','14130753','13060923','13041232','3598009','11637144','10501646','5514731','6086013','11972163','6421922','5293865','10101772','17669519','5420135','10990853','15379702','10920636','3882788','11224469','3882537','13890036','11493295','5657267','2301569','6381121','19432519','18905799','13408754','10212887','8567222','27427914','8537058','8564751','8544565','9841918','9939823','8544568','8544570','3511931','14087668','13041219','27884313','12963513','12321270','27334423','22669728','11783237','6402235','12029995','12901060','6242179','10106208','5380483','13085179','12881505','11536652','10988844','11514120','11040224','11222208','11493028','30210400','22160839','9201202','19726004','23972708','8562499','8571573','6637156','10211551','27519576','7952931','10211488','8570441','9841923','8544610','8544602','8544583','8544585','8544584','8544589','8544597','8544574','9723728','5514817','32150396','6697409','13236263','7766655','5269248','5993451','5986041','5781810','10478521','15379733','12607639','10619953','9139879','12476053','12476051','13893351','13893223','7328362','2301605','34209011','9726923','12773697','8564644','8575253','8562496','8578680','8541438','6667363','10231541','8538149','8564739','10203651','10211620','8544567','9841914','9939824','8544604','8544605','8544606','8544603','8544582','8544576','8544572','8544577','8544579','8550148','8550147','9341049','13060909','13060954','13368076','12937285','11972171','7797049','34776360','12999901','12448132','5277887','6423483','12929189','9853937','19472923','5423187','5371895','14245442','5751839','36180934','3882763','11221622','12476050','12476060','11221848','36230989','5560638','6389805','6396861','13406927','9726901','11484001','9818442','8564642','8581602','9818405','9795149','9994784','8540786','12071804','8564711','10232403','10203949','10203712','33312868','30390712','9854976','10204191','9939826','8544607','8544586','8544592','8544593','8544594','8544599','8550149','3509870','11741252','11782946','13722845','13723025','13041239','13316628','13939333','28398241','10884563','5295969','6416583','14055184','17863605','6242072','3882329','10933533','11262743','12656733','36114611','9139963','11493230','11493146','12376737','12380606','5215286','5655452','4325912','13407157','9726921','11193105','9818428','4325602','9471123','9818416','9818424','8567984','2301538','21250904','20434546','9841924','8544591','8544590','8544588','4267148','8006830','14275955','13745932','13316647','13030138','29629641','13498507','11039563','36169777','24799956','5279628','7789333','12929313','6416446','5423907','9853269','6399920','3873045','12476055','12476070','12804392','11533367','13888156','2470931','11493121','12378699','36190727','5665878','2301676','13408418','9726918','9726920','9471051','9726906','9471087','19707322','22152194','13342408','8564213','9818410','8563493','8457981','9471117','8541209','8566136','10204173','9841938','9841935','9723854','14322960','13060963','13776177','13316599','13060945','14027434','36181197','32394116','10498197','5515154','11150300','13113984','6242138','8333108','9139884','12960273','14540935','11223395','10990963','11513541','15583053','11214159','11214410','13888421','13889071','13889513','13888256','11263051','2783049','13890128','5560758','9818426','29584956','9471147','29585795','9795151','8540748','9795152','18916428','8564162','5342548','9841925','9841931','9341057','9341058','7851674','10523469','14762412','13041257','13060960','32372231','8168205','3510878','14322953','11643934','13917237','5514586','11783238','10501627','36212696','13085091','10131899','12929280','29642359','25859834','5431248','5380245','6421410','11222113','14540948','10990142','11145884','11513862','12476062','12476069','12476056','13888663','13888364','11492886','4353608','2301709','30390870','12875608','13435343','9726928','12753001','24962094','9471149','8563489','9795153','8564706','18917808','9795155','10186154','10204278','9939828','9841928','9795156','8550150','8550151','9341055','9341062','13060933','13041246','28436879','8016171','28436832','13858031','12797956','9199563','11759707','11038932','25114078','6401192','5995383','17672335','22534520','7872982','22826412','4199022','11152808','5782865','11580057','12476072','25399642','13887615','13889254','5784363','12380245','11535556','11515055','22330214','2301573','7814715','2301756','8121425','6381474','6382772','13408311','13407002','9154517','2301628','8564206','9818406','9818407','10211659','20421257','8540633','8562329','8564165','9939829','9841927','9841930','3966218','2647653','14322956','12937292','8016991','3616564','19875775','13753386','24828037','7992360','11783240','11783248','11972164','36104672','12688805','11039451','23098047','27624735','6401139','23738543','25202103','7092244','24799806','24800141','5996407','10216699','5385295','27440883','22802754','10917876','35850258','12476063','13559221','13890378','12379463','4339725','7824943','16324525','12102267','20827103','6380999','9795150','30868779','8561309','9841933','9341070','14275939','14762384','13758329','12963592','2645760','3609117','36253594','8178987','11759687','11783243','11783246','11972170','10788173','11639600','22803606','6418804','23741015','13011727','6401489','19714143','6871736','3882859','36200087','9139927','29204450','12874193','11568756','36180923','11213329','12476064','12476068','13889651','11535509','12380464','5666667','8147136','18006018','12777675','6688159','6379939','8569440','8567591','8562500','9818411','33998789','8536748','8540539','9753725','8300476','10203858','10203733','10203821','8558188','24515137','19042701','10203627','9841936','8550153','9341060','5307176','8176571','11739412','14762400','13041250','13858141','13059271','11091349','10668157','10671028','11643996','27440831','12030642','13011719','13516241','17672566','5399574','11760926','2476571','2471039','9139939','12571513','11520720','11536752','10920765','4198996','15583095','4198048','10990526','12476073','13893550','13887067','12379568','12379253','4131136','8514993','8126301','2301589','6395698','9726925','12101974','10212139','4277111','21230631','9818419','18902613','8562373','8563488','8564734','15708260','10203523','8576683','8570433','10204304','9795158','8550152','8014433','18940098','13041248','13041254','13041260','3606956','21174405','8691213','11640006','10670244','27292640','11643858','13085155','10792206','23739215','8338231','5384838','6421296','5380397','24800261','12029165','7796679','2471137','2470941','11040262','11223899','12476071','12578065','13886649','13892467','9470469','11757979','18918054','10212645','36103704','31061123','8570456','8563476','8563487','19650681','10203555','10204120','10203930','7824133','9939839','18920334','8550154','9341067','10946734','3511395','13041247','28436874','21173870','6682615','5499031','10942005','11783716','11972168','11783726','9275433','21941147','6418744','11434817','11519961','2471067','11040301','13892529','12377226','5754808','11176045','22318139','4370894','5658689','8302925','9471134','9471151','9994613','10213264','4131125','9818415','9818431','8570450','10204097','8584531','9939834','10192870','18916645','9795160','9795162','9795173','8168528','13406717','14126655','8151052','8168291','3510746','3631121','10945994','13577078','11253732','36299164','33729695','12929302','10792759','24799291','6416829','5269377','11968859','5265723','5992479','17664689','3882919','36114298','10920633','11570082','11522413','13893409','13892423','13890087','12379801','13083015','12380386','2630961','2301567','5668095','2301624','19041570','9471057','9341157','21070422','9818414','10212161','4367669','5342738','9818408','19652297','8540360','20545184','10203604','10203792','27150194','10214257','9939833','9939842','9939845','8550943','8550158','8428614','8550155','8550156','9341068','13041252','13316651','13717767','5498962','5497656','21491212','23156663','13500318','10502029','11783249','5461391','12018689','12066305','25175993','25098218','6418889','24799552','3883288','10019638','17327758','19468327','11144533','11166385','11513967','2782900','11219719','13889250','13893444','12379922','12379717','12379350','12380675','6380550','20554992','9818420','15699368','23965797','9471130','9470479','2301614','9471073','10896707','8564637','8563484','27886926','8564680','11757331','9939843','9795161','9795172','22156052','14762393','13041263','3589671','13217024','3510770','8172912','8007234','6022631','9267151','7992097','12688864','12712661','11972172','11972173','11660479','36195670','9680789','13238532','19714119','11754716','24800065','10813931','10912505','11262276','13893473','4198138','11533306','13888281','5743221','7663699','4110915','8458840','2301752','25246138','12869747','9470471','9471160','9818432','10212011','10211769','8566771','8566144','8564631','8561209','8580546','22873122','4351329','10203761','14974795','4353711','9795163','8550157','9723862','8550161','8550162','8550165','8550167','8167533','11780739','18838001','13041258','14602262','14295978','14762416','3607761','22144576',
 '4773975','30797918','11639491','11759692','24799377','25247221','33187777','12929127','14345386','10106562','8788201','5996144','7798977','36156325','11175853','11214424','13888980','13887215','13890092','11220077','11636481','8123632','2301535','16824159','34795825','13406925','9471111','9702422','9341086','9471155','12094787','9818436','9818437','10212774','10212925','6381557','15708271','9818445','9818440','2301754','8564634','8575252','8553364','8572974','8563463','8563481','8537209','8564653','30391255','16392142','9855248','8541114','8581601','8550945','9795164','9795169','8550169','8550172','8550166','6024049','14738473','13858020','3591356','14602295','5516559','10937734','5515082','28408721','11039867','24742150','12929144','13011716','5385044','12933007','25120791','21087205','31222273','11537020','11740726','2476573','11581160','13889217','13889990','11640389','36230992','13893460','8515234','7955387','8299561','8360782','2301615','18920412','15635525','12479707','9470475','9471080','9341072','9471060','9341162','10240914','9753721','10212674','10212704','10212755','10212956','27410477','2301690','8564622','8575240','8561290','8563473','8563475','8561210','8550286','8544710','8564688','13407039','23967555','8581600','24457584','9939848','9939863','9795168','4203368','9723860','8550164','8550170','7851353','13939232','13041268','13041269','13066986','13041262','13697383','13757289','14088133','13939334','5515251','15018064','5499070','11905252','6242291','13096573','8488513','5540358','7455214','36244556','2486176','4197792','11223824','11536966','11175879','11190924','10920637','17327893','10998008','11540589','13887277','26752999','12380771','36230965','12381421','11255408','7794322','5654983','2301877','8120332','2301652','31528982','9470473','9702424','9341077','9341073','11214691','10231567','9810943','10211852','2301854','8569819','8563456','8563458','8563470','8563477','12775286','8564732','8540781','10151870','8570437','21891568','5342707','8555120','9939857','9795166','9795165','8550946','9795175','9795177','8550171','8179835','8150459','11679457','11780931','13041277','14322844','13692663','8017494','14130749','13776101','5499541','10499094','19263274','11783251','11637109','29176490','22828707','7092445','5460707','25859725','5985929','5392346','5278057','36191389','10621494','29824557','13530674','12874146','11520605','10920641','11514075','2476568','14293259','13887211','26321784','36230994','2301850','2301786','6689084','6395610','22160608','13398965','9471077','9855979','10212222','8572460','8563461','8563468','8550287','8550289','27884296','8564728','8564724','8564735','8564730','8564709','8564681','18918787','8568522','27633912','18915531','9939853','8550944','9795176','8550174','8017033','7845534','13041288','13041264','14139265','14323091','3589657','5494700','24930777','9070854','5514665','24930288','11972178','9052870','11972177','6421053','13085005','24173464','12691526','11190042','11221839','11495499','11263009','9139952','14704387','13884983','34803949','13889658','13891063','13887226','13886612','13083725','9747999','12381092','2301570','2301706','2301674','2301737','6390161','13406865','9470477','9818441','10211906','18681425','2301613','14064881','2301617','5299264','8566962','8563459','8563469','8556022','8539902','8564716','8564670','8564672','8564661','2301894','2301687','16392105','18920320','8658121','9939865','8537837','9939869','8550948','9795178','9795180','8550173','35869027','20726267','14769348','27807356','11972184','11759693','12712618','11830159','36173496','12456061','10814957','5295781','13880044','22239061','13085144','22780144','5385436','2783067','2471085','12656785','10917868','13887683','13888271','11493553','11092507','36230995','4325625','18864656','9471049','9341090','9341088','9341079','11013508','9818433','9818438','9818439','10211796','18919580','9965115','10239684','8564614','8564618','8568063','8563450','8541135','14038492','8564720','8564654','9795181','8568519','4131005','9939873','9939851','9939854','8550951','9723861','8176803','2647055','10523457','13042585','24832454','23156673','11144903','29420720','11640172','4459476','10942356','11783260','12881849','12688925','7050204','11022916','6416409','5270904','5384751','13085252','7800015','5782366','2471062','9275469','15380675','14540937','10917966','11083051','13529354','11224328','11023160','9140000','5311693','7795721','7795903','4370807','6379851','9471105','9341094','9341096','9471142','9341084','9476721','9791059','10211973','9471063','9471052','2301648','8569430','8566765','8564628','8581586','8562490','8572976','18902607','10212189','28754395','9795185','8564691','8564682','8564662','8564675','9795191','9795195','9795198','9795202','16392127','16392152','10239672','9939866','18916765','8544331','6683823','13939218','13758330','13041291','13745616','14130866','13747290','21490945','11640200','6083601','11783252','36124850','11254621','5265404','25117657','22803135','19010657','7790389','10792250','11744312','5285438','22803337','25183454','13085016','5383212','5399476','2471116','10542190','10917870','14342592','11260418','36200348','11152460','14540936','11656544','11262823','13888621','13891705','13888063','10679085','29647661','8149717','2301671','9471061','9341095','9341116','5346696','4243055','15701497','8550291','8578679','12872951','8564719','8564687','9795184','9795193','9795186','9795197','18916425','10214889','9939867','9939868','22160077','8550949','8125050','14322949','14323088','13757222','13041270','13744959','8017652','13067005','11643957','27372853','11972179','11972193','11972219','23153162','5279926','25249063','6418561','15451563','7837974','5985751','13011778','5418255','2470937','10672383','14540942','10920647','10619035','3872430','11152788','11495359','17398075','13886660','13889049','13888272','13893503','5743489','13083734','13892236','19079477','19086818','18916681','27149538','18919594','10211927','13406856','9341103','27202822','8569438','8566142','8566129','8564615','8573535','8563448','8168693','28764759','10214293','8568572','8564699','9795182','9795192','9795188','9795194','9795196','9795201','8387641','8570055','2644742','5307615','11346480','8147601','13406730','30321110','14137061','3596741','5515781','29206484','10954192','11639422','11640087','11643974','10498205','11660436','24929727','25241985','10150203','12066232','5457441','22547570','25198092','6420920','5384906','2471002','3882665','35859604','11040335','10917890','11495669','13893534','13886893','11040363','17398092','26321985','11024144','13886814','13888985','11260756','6702415','4339854','4370827','19803358','12937467','13435279','13406999','10138423','9471065','9341100','9471056','9341115','9810944','9790630','4339858','10213000','10213066','10213365','19040835','9471126','8567227','8564619','8574126','8558205','36188591','8540623','28768013','8564715','8564722','8564659','8562378','8459028','28765040','8550947','9723863','5307527','14323092','14322963','18099331','13758230','14087357','14132604','13041280','30182266','3634484','11640118','12258871','11759694','11660408','11254618','22803504','14347016','7051625','21708377','10792506','5539658','22802881','22803310','5296190','5383748','10813669','5296264','5751461','2782960','2471083','3882586','5783033','12688847','11251995','11190320','11152916','11214189','3881007','2471102','11176034','11040321','14540940','13893167','13890033','5753820','13889174','2301871','7822451','4325631','22158812','13406873','9341097','9341106','11192889','9994753','22889420','4326508','10212424','10212314','10213030','2301595','14225506','20844771','8567981','8562489','8545865','8564713','8564686','9795199','18919585','7817955','8567317','9723869','6683878','9032575','13939285','13041282','13042524','3510396','13042528','8124811','36122630','11643923','11783254','11783256','11783259','13037967','5279671','10792593','14054616','8327925','7838538','8786959','5393640','5414647','17665294','10917874','11252136','23544301','22323249','14035453','14540938','11520446','10679121','10920700','11262996','36124026','11090935','9154752','4275650','2301764','2301537','2301620','6381908','20001388','19618177','18920289','18903886','9341111','9341108','9341158','10241465','10241468','21155270','10212442','10214496','6938560','9471139','22872645','8151705','8569781','8569439','8566858','8567992','8563070','8550288','9702427','8563328','19043157','9723866','8550953','10524552','14323097','14762381','13749587','14132616','28436859','8695598','3636215','11643903','10501630','10911210','10884418','11783258','11972180','11759712','11660444','5265333','14345448','7650610','2471070','20442922','15418763','19574007','14440631','11520538','11221069','10914253','11495623','14540943','13888992','13893448','24499559','13083732','25393394','4351690','6397258','4370666','2301610','2301583','6396909','18903852','13435368','8615878','8714510','9471109','9341121','9341133','9341145','9341148','9341151','9341155','12632821','9753730','10212029','10213307','10162799','2301692','16324862','9702426','8570453','8564616','8567969','8553451','6657254','10214912','8562326','12873946','10234717','10231575','8570049','8550962','9723698','8123193','36285115','8508862','14275283','18851616','13968504','13745793','14130783','8019810','3602944','36101871','11634390','13268164','30592542','11783690','11972189','11759713','6243524','25181416','21708389','27874368','15198222','10792314','5296477','12880355','5279743','5433998','5386174','10814430','13217787','5751680','3882956','15379713','11152377','13889010','24696617','13890184','13889988','13889717','13890414','13888170','13893140','8390852','5655499','4368953','29584141','10137866','9471089','9471088','9471124','9341113','9341125','9341128','9341119','9341135','9341142','9790687','10212352','10213284','13406929','2301900','13319617','8569768','8574568','8572477','8575251','8556016','8540663','8568595','2301820','27436488','19050077','8562319',
 '8581599','10214331','8550952','8550959','14275957','14275960','13717795','13041293','13041299','13042547','13042574','13042588','8180115','14274816','13042546','13030134','5532677','36254334','28208329','21490860','11643880','21490955','26023138','36123634','13491662','11783255','11759695','11759697','11660442','12688974','36128396','22851030','5461027','10792435','31114625','5996721','13000934','5743013','8005009','20058685','10917869','10920635','10920638','11152405','13888162','13889412','13890126','11047825','25398886','4339839','5666620','2301881','2301666','2301621','19795737','17378425','12479093','9471107','9471118','9471078','9341147','21238495','9874663','4339831','10212462','10212373','10212488','4354816','10214638','9471115','8576664','8575237','8563144','8549324','10215120','8566891','24513713','8550793','10213885','10215034','8563265','14609003','9723872','8171351','5307965','11963197','13396512','13042581','13042521','13858146','24792045','5532822','36107065','10938952','12629908','11759698','11660449','11660445','11660460','11972227','7090444','24799542','21708603','17670911','24800266','13004739','10792674','10125869','12371906','5371348','36238841','5431517','24743215','25121576','8388280','2471060','12874306','12881541','11151831','9139910','11049558','14713378','11533782','11091579','14540939','13890778','13911789','8388664','13886659','5666410','2301846','2301879','2301710','4205291','4131118','6382560','9471069','9341131','9341140','23918410','27454249','9855980','9880422','8566268','8575247','8562486','8572971','8563744','10214955','14038507','10214372','8564669','8312101','9702667','10214408','10215241','36319313','8550972','8550950','9723876','8013731','9033766','14300581','14132639','14130858','13041296','13042534','13042550','22804537','11637284','9055403','11783261','11972201','12689067','25094695','21087751','12929204','8355276','5295870','11792803','6400973','10103087','5442235','10125875','22803525','9140048','12578911','12687104','11176543','11214457','14281948','11224307','11083936','11040348','12848749','12804498','13888129','13893381','13886859','13889056','13890655','11176011','11092491','11023556','4272373','7825809','19043672','9471082','9471084','9341123','9471144','9341149','10212395','10214562','9702433','8569764','8577114','8566762','8562491','30390928','4354917','8563324','19045565','28692724','30391065','8433325','10192916','18902583','10214100','8540721','28766851','9723726','8142165','13042522','14300594','14602288','32744684','8016389','8453754','24827572','10501678','10501649','9140868','23153144','22667601','11759731','11660447','11972216','36128101','7797965','25250627','13987460','24690610','5270033','11170344','5441010','5371432','7757379','13268763','13132177','4198558','12689644','14346128','10917918','10920644','14275218','11152431','11495596','15516547','11220015','13888963','12851092','12851117','13887045','13890753','13888194','11493536','11744787','8459070','30390865','31791056','13854227','13406876','13406996','4277385','14223646','8387336','20526782','9478339','8435538','5309476','8566135','8581867','8581643','18916313','8546086','8568593','27613368','8570446','36260383','10215075','10214057','9855184','18918032','8550958','8550963','9723722','8014146','18851612','22156646','13717779','13858728','14137655','14130835','13041309','13042541','13042542','13042545','13692258','29064104','14132631','13971512','10523470','3591482','30054724','29207361','24833894','21491045','23346790','10499609','27502239','11783262','11972196','11972209','11254550','5297273','17665616','6401714','7838314','5397523','22802711','21945934','13085127','26806694','5265991','2782884','2782954','9139941','15418743','17328186','11176001','11152584','11152483','12881278','5743853','36229528','11495555','11153271','11092521','13891495','13890059','17327903','13892856','36269757','13083746','13083737','8216521','8382899','7826573','6637977','6382531','16393763','13406859','9702429','9471085','12102226','22153670','9810924','10212433','8515259','9699754','5309587','8569362','8567311','8561205','8556017','10213949','8568586','10215001','8562344','8562322','9702668','10213922','18918327','8550956','8550961','2301634','10874079','14300552','14762414','13757226','13717778','13858249','13041303','13041308','13042563','14323094','3607169','13857888','3625377','29206798','21491148','12205338','10669936','36179594','11972202','11972215','11759701','11972224','11972233','13906653','5280445','29429953','26923847','22803029','11660485','11438254','35443410','6418151','5982464','23472838','13030121','27565846','11083778','10672382','12578400','14540949','11255967','9139957','11262636','13887209','13887083','13888059','13886790','11092440','5743423','13888301','13083744','13893044','13889431','13083739','5668660','2301541','2301730','2301741','6395521','28077301','13407037','10215806','22888418','9810947','9753807','10212505','10212545','9753734','10241469','10241486','2301712','14974777','8569437','8561207','8568591','8568588','4354534','8562324','8562323','10214008','10192882','4272385','8536750','24459309','8550957','9723882','18919601','9723873','8013917','8008961','4335235','13717775','13717781','14132648','13044663','13044693','13042536','13042539','3512067','3616270','12937279','19477503','16937061','20909678','11759717','12689022','11827743','36157255','25201932','24799382','13241032','24799738','5422957','36128376','11791981','10917872','15428125','3882618','14540946','11176021',
 '9139971','11580543','15418804','13888039','2783053','13893055','13888360','13889922','13888315','13087804','11580696','17327931','8216777','11255205','36190730','13889647','25264207','13659353','4277095','2301880','6380982','19849610','12752050','9702442','9200070','10213522','9753731','10212566','10212521','10214594','10214531','12102052','8566138','8581597','8555139','8558518','8546122','8571104','27469486','24405578','8550970','8550955','8550971','8550965','8550974','9723720','8550966','4335024','11519617','13044690','13406711','13044601','13044626','13042531','13066962','13042559','13042577','30417490','13692222','13394403','13939267','7992504','34994238','36116068','21490912','12882666','11783263','36249691','12712863','11148201','36104864','11972229','11040037','5280302','11782190','26981944','13036498','14345443','15817175','5993469','5985759','10871089','3107783','5984731','25104829','14228600','8202232','4198389','3882550','25548124','10620004','12881540','14540944','11175907','10934156','11145936','15618501','14275214','11046771','11495645','12579444','13888145','13893517','13893956','19653992','13854231','22489680','8569386','8571110','8581173','8556234','8546121','8540114','6636417','8579639','8538592','8562317','8353734','23492914','18916774','9723713','8015563','13044683','13067036','13042553','13042741','13395244','13857884','32529392','5499378','5497723','36128103','4773739','11783269','11759715','11254674','14345397','17671511','24799776','12034609','12336205','10814897','5269555','6401557','5379923','27625102','25861398','10090950','5383970','2471026','2471142','2782925','9140013','10478806','9139975','3882246','12874579','15418744','11175934','11189939','11224013','11224052','11046237','14540945','15418742','14540954','11219688','14540955','13888397','13888214','13884979','13888639','13889059','13083749','2301792','8388915','4354441','2301600','9702445','12632849','27150317','6380739','6396130','2301726','10277726','8569436','8570451','8567226','8574580','8574125','8564136','8554700','8569783','8568565','18920420','4370367','18902652','8576663','8550967','8550969','8550964','28077336','8014313','14275453','22156205','13717771','14130775','13042556','13042557','23591613','13692278','14130851','14130819','3632054','3621515','4335275','14129247','4800092','5499688','5498348','7991463','29207094','33156189','24834061','15601733','11635857','11783264','11783265','11759722','11972236','11972344','11759721','36120863','34776584','14345450','17665199','13037644','7766303','25115698','24229944','19796407','19474961','11170006','5993555','29997975','22803043','12027928','4197969','2470959','14540951','11570011','11144792','3880934','11260667','11533751','11092116','11045099','13887102','17327913','14704359','13890287','2301843','6385266','13420783','5666549','8167678','8569428','8569431','8566302','8568044','8561204','8563060','8556018','8556019','8556037','8556039','27436169','6655814','4275235','10193387','10231701','8562339','30432822','7826110','11896559','8550973','8550960','14270381','13042554','13068102','8122128','28108146','24925753','20462401','11783272','11972204','11972205','11759708','11972237','11972223','11514759','11519186','5280392','14345379','25259246','13307867','12456042','13085139','36123331','36125549','11172156','8338298','5417718','5385398','25176806','35443203','2783035','9140014','12687057','12576703','12874177','12853165','11568791','11092021','11082904','11152450','31627940','14824452','11091909','11263039','11580360','13889212','13888464','13887116','13893293','13893162','13891722','13893372','13083762','13083776','5656436','8515147','6381427','19797143','28934765','12101830','5346243','4367687','8435792','2301618','8359290','36269162','8567314','8581596','8562526','8571108','8556046','8556020','8556023','8556027','8556032','8556034','8556035','8556036','8556042','8556043','6579352','18915608','27428056','28743212','8563756','8558180','18902683','8008861','8124915','8178765','13757288','13044618','13042566','32523413','11346483','32986260','14129270','27369504','23510708','13267437','11540903','11783268','11972212','5280310','5280388','10125866','11743101','5279679','13180955','36128334','27440921','5284166','36283932','5441499','7800191','9753096','4787731','11175918','11175929','11176587','11494453','6993967','11153286','12408516','13889027','12848751','13892771','13892741','15418772','13083757','13083754','13083765','4352987','2301642','4202199','4275941','18005851','14925788','23015816','9754071','7769558','18917316','2301872','9699755','8576729','8575467','8567323','8581588','8562393','24335357','4277292','8556024','8556029','8556030','8556040','8556044','36205341','14038491','28743447','8540358','8539317','21160759','10192982','18917727','18902734','6653483','9723707','9723703','8180180','13044701','14275944','14649707','13698684','7403956','5307489','3608251','8016073','13044621','13030727','21491015','25797611','11144897','11639536','36181223','11783285','11972208','11759756','8455693','19580220','25590001','12456058','12031827','10277721','10815211','13627128','21707777','19800318','22599194','5993692','7811946','14346736','5751732','2471095','2471044','10539012','15516550','36118219','10996317','10679192','11019912','11019253','11144670','22456206','12648754','11153207','2782963','13893464','10910102','13892507','14502646','11084480','13889397','8389902','9274957','13083727','13083751','5654637','5665776','8176375','5667788','6396840','30390879','14737330','10151399','5654593','21572047','9873408','9810953','9855981','10320850','9753736','24118765','2301635','27669936','5665727','8567589','8567225','8579462','8553737','8562488','8563722','8581170','8577519','9753741','8567964','14941236','27504357','8550464','10192947','19086804','8562315','5668651','8562321','8544318','8583850','9855196','9723701','9723702','28077366','7868673','14275286','13726314','13698694','14132620','12963612','3607313','3621672','5498843','11540120','11254617','11783266','11783274','11759726','11972222','11972430','11972221','7090794','7089981','25249992','14345368','7051793','5268559','36118829','5392062','13884885','5385460','23670524','8901148','24455744','14540952','14540960','11223966','11224000','10914256','11215409','11224295','11047849','11091800','11580236','13889386','11219923','13887078','11262546','13892454','13887898','2470957','18434640','35852719','13083780','13083760','13083756','12686344','2830888','5666321','4326676','2301584','13854233','14064716','13406993','18918333','13435752','9702440','8352749','18916330','6384731','8570422','8575249','20818553','8546127','8548172','8539435','13854276','28762681','10239682','21157350','8556537','10192961','18915369','8562333','8562331','18915540','8564142','6024446','2647294','13044653','5302280','14130011','14650174','13857808','13746539','13717777','13692680','14130813','14086668','13044615','13044697','12937291','12744616','13692197','8148708','14300571','13752019','3511990','8140455','13044628','13726372','8139969','13727231','14769355','20348702','11643195','11783289','11759809','11972207','11972214','11759734','11972234','9070585','36116870','14346927','17671852','14345425','12957666','9246437','5296691','5279570','20049370','12929239','33729555','25176398','5270293','5424016','5996404','5422057','5413357','25106126','34776601','5781889','6861838','3882830','14936127','11260831','17327769','12689447','12687487','12648737','12851320','11260691','14428798','14463205','11533979','11091883','11580303','14540950','11219945','13087740','13887138','13888323','13889278','10625944','13887860','13083774','13083763','13083729','13083781','13083770','10915888','36205615','4325298','5666479','13560731','23012636','20732855','9855983','9874664','9754114','15799889','9702438','20537894','18914348','6389610','10239695','8576724','8566960','8581583','8581587','8581590','8581595','8574579','8568062','8562392','8580859','9853800','8550083','8546123','24466275','28766530','8568577','9754407','10193122','27427424','8562314','8555134','8571105','21284664','9855186','18919015','34023883','8570051','8015873','10944614','8175935','14300569','13717732','13692692','14203392','14130808','14130831','12963510','13044670','14714291','13044676','36280046','14129255','14129278','35320416','27401469','13347659','10499085','5533583','7991993','11972240','12712910','36129113','23522948','24824075','12060260','5398132','8356846','11223953','9140057','12881552','12880675','12874156','11224042','25403519','10991237','11220040','14540965','13889934','13888179','13083779','13083764','8453451','5667798','8459201','4352124','2301540','14974783','10154440','9729570','9874622','20791300','16626714','9754070','8150193','10239669','8569435','8567306','8567308','8581594','8556539','8572975','8558184','10161225','8539516','8541107','6656616','8568568','8566886','8570083','10193021','10193137','8562342','8562337','18919574','10193162','9855247','10193752','8568574','9723704','8151199','11782647','14763954','18933039','13749394','13717783','14123324','14132610','13044649','13857815','13044710','13751703','21491527','12482399','27391281','10939600','11783278','11783280','11972220','12712961','16369193','26971517','13001330','7715337','12932365','12448129','10813742','11172725','5391651','5996595','13011732','5986289','5385668','7758550','5782719','5784219','5781296','9139990','29956205','12960275','12874598','12881117','12656721','12580510','14540961','11514711','11176578','11302176','11082962','3881876','11224245','11153238','11047098','11260909','2783041','13888906','13888928','13893800','11092525','13892606','13892936','13087798','14605932','13888871','5215571','4277330','4277336','36166793','15698939','13406867','13406994','36122409','10240901','10241467','9856372','14974749','4277147','9880423','8569434','8574657','8567979','8575245','8575248','8578678','8540591','8579282','29584540','36205338','29119025','6639738','14038499','8358715','10193041','31544732','9702654',
 '8546651','10193066','8544335','10231957','18916769','18919742','3510320','8178560','3509978','2048768','14275294','13726344','13692674','13044612','13044685','13067016','13697355','36286053','13717791','3634670','14129326','14129262','14129281','21491021','10886517','11091687','11643700','11783275','11972322','5496629','7091959','36179704','21711179','25251671','5284120','5994591','5383246','5400039','5386362','5382685','28628864','12449505','6967306','2471104','3873090','12853048','17327784','13158122','11175984','10679433','11494015','14281950','11224213','9139858','23085610','11091899','12960286','11084232','13892680','12851294','2470971','13087796','5313324','5296632','4370917','2301858','8492587','2301800','6396118','18965796','13559736','9702446','12388934','10285539','23130478','18918043','9856005','9855987','4370396','22922826','4275688','8570355','8567304','8567334','8567658','8566127','8582496','8581584','8567965','8575244','8571237','8550290','8548633','8540325','27798050','18916209','10193101','11193578','8562341','9855188','18916782','18916838','18916844','9723711','9723732','8172445','8176738','2646896','11783596','8176092','14738471','13939244','13698683','13044706','13066961','13068149','22586991','22805403','3606998','2644272','14129297','14129273','5514677','11144673','10501675','10498158','11091340','11639393','28543175','11783282','11972238','11972315','11660665','36123563','10908731','36220895','35765201','5282278','24799619','12932467','8352533','7799407','13236951','17670798','12448127','10102328','5412912','10090466','22802748','12929198','22803462','4199026','18525285','10622305','12874217','17198733','22275907','13529474','14540956','11049484','11175998','11260787','10912483','14282026','9139974','11146116','11533085','11091852','11533133','14281949','11219955','11219974','34427493','10995046','13893405','13887014','13892618','9140029','14540966','13888456','14645435','11255293','3881971','5311516','7816729','4275909','18920417','20420127','16740583','15708231','33820835','14940277','10151743','21571825','9790661','9753744','4370736','13849234','6380500','13435754','13854256','13854245','31892389','8567983','8568540','8561314','8563069','8545712','8547857','18918329','29698412','10193343','5343057','8666315','6380507','9723710','9723733','8006978','8179331','8168904','8169128','11769294','14275953','14275290','14649698','14738458','14021508','13758241','13044608','13044691','13059984','13068106','13068115','11346482','13697369','13697382','14129287','6020895','10667000','9077441','11634340','11759745','11759773','11972338','25798880','36118846','29997430','14039213','25244492','10814810','5296790','21708466','10125862','5296265','36171619','14535765','5992872','13085120','5383324','5754299','3882386','4198992','3882856','9140033','12908045','24173556','12648773','12579583','14649246','11176513','11224313','25534381','17398112','11149231','14540957','13893393','13892098','26286811','13893424','36190846','5654153','2301763','8123318','2301722','6395489','13435338','10155697','8667779','12020950','11014284','10240902','9994468','23953275','12837033','12019728','9790708','10320858','6395948','8148873','8569668','8575468','8580038','8567986','8572965','8559374','6579502','8546771','8541046','18915551','18920499','4334823','8175846','8150818','10944176','14275948','14738483','14132646','14130824','13068128','13068107','13692241','8140952','13059359','8169292','3609087','22144267','14602294','28502423','25747792','15312725','10501610','9055244','10942681','10941022','11783291','11783684','11759738','11759755','11759759','11972241','11972242','11972334','11660383','12713020','11254634','7764523','17670961','19798534','12033581','10809474','10275279','10813790','22975058','12929106','12938804','12925642','5398063','5743305','2471126','2471057','3873018','22140957','14540959','11514648','11554883','11176596','11152600','14269709','14428799','11259491','11040612','5649280','11540731','13887999','13889062','13889378','13891858','13888114','13887228','26294857','13886805','14900779','11260712','13888116','6932647','4370748','4201710','4325892','4205124','4131195','19719954','18967309','13854242','17054539','13407033','13406893','10285601','12102169','10231246','27149968','9472883','2301660','27149802','27724409','16286248','14940782','18914799','18915003','8569824','8576726','8568061','8568852','8572966','29191398','8568582','27555510','8568581','2301749','10193184','8568518','9702674','9855195','36138639','3511028','8121691','8181110','4334979','11346477','14268545','14714286','13939341','13717786','13717813','14122603','13044604','13044673','13066966','13067042','13059351','13068134','13068138','12937294','13068145','13974010','27287206','10940807','7992580','10911046','23912242','29167429','11783286','11759740','11759747','11759751','11972321','11599061','12714843','14346962','26982016','12955464','5277938','7837448','5995884','6416895','13881529','5419375','5391412','5296195','25177045','9278989','2783071','11522560','9139899','9139870','12908110','24188565','14540958','11519153','11045264','11224061','14282028','11224125','7839433','11515639','12853050','11580394','11580133','36168938','11259397','13889007','13888747','13087742','14540963','13893122','12874199','14645443','12853063','4351941','7799955','8178755','4370043','5657676','2301759','8356043','4354398','2301547','4275871','2301664','12851617','16392058','18903792','18005937','10285568','10211856','24514817','21156473','18903799','36273279','36132482','9855989','8204212','8312428','8313703','18915611','8576662','8576906','8566958','8575242','8562381','8564127','8572970','4277367','13560181','8578677','8548173','8550292','6637324','28706661','8540719','29192486','10193201','10193712','30390751','9855191','9855193','10314496','8176455','8150736','8178268','14275292','13717797','14135465','13044759','13044659','13066968','13067037','13059970','13059973','13068126','13068116','13692215','13697386','14274814','14123368','13757145','5497767','20453656','24923270','24661772','10941289','11783287','11759744','11972231','11972244','11972249','11972324','11972340','11972341','11254559','22802843','5277369','10113487','10814859','5293648','5268401','21707908','8787446','5418877','25121026','5781934','15379680','17327794','12338824','11092133','11493598','11493876','11494204','11487912','11023239','14282029','13368942','11224272','11091638','12881536','11580504','12853161','11023827','11061788','12803953','10540884','13887936','13893034','13893105','24753265','13890162','13889221','6389913','19652131','19653482','19629618','19042402','13854236','17378441','15799910','13407035','10162968','9476753','18919219','18919131','9810956','9874623','9874628','4277362','9755731','4354765','15342844','7823812','13854253','20833344','8570059','8569376','8570362','8567583','8574121','8568850','8575243','8562374','8540846','8541072','8540549','36323962','16315320','10193401','10193301','10193208','10193533','8716126','8544319','8541207','8544354','8570069','8015933','8177575','36280045','10946529','14275940','14275284','14300563','13717801','4335325','13044704','13067014','13067050','13059363','13060009','13062245','13068142','13068119','32984554','22651115','22804578','13698717','13698718','13697376','13692249','7868949','3590086','7867880','36102898','24670937','11165703','10886148','11091343','29176251','11783292','11783685','11759741','11972248','11972336','11972419','12713103','7798843','26925615','26807063','21708580','27472231','14347015','12453088','10815109','5207360','36118825','19833049','36171626','5393603','5996909','13181431','13085080','25199528','36264929','2471008','3882941','3882750','10620697','3882852','15516557','12656766','11519671','11152558','11176534','11091926','13717740','11581027','11046137','12853131','10997709','13888629','13887613','12804345','13893995','13893285','13888381','13891456','3882677','26294136','36118122','26276839','13890834','11255359','4495989','2301579','2301758','2301874','4354586','2301718','6398687','31790532','12906261','36132725','10285630','9702452','10234448','18917263','27312645','5286358','20845240','18918576','9874631','9790703','9753751','6381042','8712048','13854266','16753510','6396259','2301895','13854274','36214331','18916613','16626663','33309338','7800127','5312340','8576730','8566853','8566293','8566814','8574566','8574644','8568842','8575239','8571109','8572968','8571565','8578902','8540444','27427693','18915624','10193273','8562335','9702656','10215555','18919470','8177905','8169981','4335088','8172570','10523459','14270372','18515835','13717800','13717805','14129318','14130704','14132622','14139938','13011535','13059276','13059292','13059340','13067021','13059976','13060002','13062264','13068124','13068136','13068114','22664646','8019197','13011567','3611614','3624752','5307903','5514771','18442444','20899782','10941053','9072279','29219264','11783686','11783688','11972363','11972317','11972318','11972326','11972337','11972346','36129118','11972385','24799568','21707966','21085895','19714061','6400564','10811866','11759230','34776105','5269510','26807567','22803101','21708008','30964923','19474860','5268424','5434097','7846099','13117406','25200724','26806644','5743287','5751603','8388902','4198829','33859734','9140028','10620445','11045438','12908046','22275318','12874194','14055389','11045636','11493660','11493758','11223804','11224022','11260862','14704405','9139968','10998317','12853086','11580261','13888031','13893177','13893394','11540800','13886622','26288195','11083889','13892010','13889809','13888173','13888101','13888506','11519056','11255427','5309241','7757734','5311469','6390118','4277327','6636797','19724337','13435759','36145352','10150833','9201210','10215210','24344893','21157539','21157880','21163198','20543317','9819901','4205070','9753749','4243410','13854261','9699758','8570425','8576725','8566839','8566851','8567989','8564211','8564147','8572972','8561284','8561293',
 '8563066','8578674','8546152','8545710','8536615','8571103','27885780','8540709','8537554','8539063','10193326','10193286','10193218','8544332','36306739','10239678','9702467','9702657','10193673','8544355','18916653','20787840','9855198','9855203','36205344','16291956','9032796','13059345','14649724','13758335','14123319','14130802','14122594','13062300','13062304','13405825','13059272','13066969','13066972','13067004','13067034','13059981','13059990','13060000','13062269','13068129','13068148','13068153','13068109','33289525','8717640','13939231','13692208','13751724','3589419','3601547','14268102','13499046','10620328','10498173','10498176','11634259','10498204','14769356','19545418','21490932','11783729','11783733','11783734','11783290','11972377','11759766','11972250','11972320','11972351','11972423','11972392','11660384','11660389','11254568','5280405','28392460','21707739','25248908','6697342','12444152','12061636','10823357','36118831','10813876','23740281','14347018','13085172','5268987','5397826','5394101','12929293','25202064','2471071','4198097','11533181','9140017','29651631','15428290','12578654','12600826','11514852','11176557','11260728','11024193','11224320','11224290','14463206','14463210','11083014','19474365','11262070','11224368','11091917','17398104','2471121','12960267','11518871','11580189','11580853','11084003','11084378','12848773','13887994','35374670','26280672','11260886','13890506','13889417','13891708','13892539','13892546','11251941','11145744','11568719','13886934','13893514','13087789','13886848','5311975','8454421','2301632','7816124','8303752','8391099','26057960','16392086','13342213','12752350','9476682','26673938','21571623','9753755','4370232','9856375','8459038','8211292','36232548','36157184','21163993','8569773','8567462','8566842','8566816','8566823','8581655','8563022','8557208','8561249','8558181','8558189','8563068','8563718','8578673','8578675','8578676','8541460','8536802','8540839','27884068','2301661','10193425','12754089','8562334','28768295','8581638','8568514','22923266','10239843','9855201','9855199','9855212','8143442','8177268','11782494','14275959','14649710','13717739','13062292','8178436','13059300','13066975','13066978','13066999','13067046','13059572','13059997','13068157','13726576','13059289','3612442','3635986','13031641','5497865','21491031','24980260','10498159','11636385','9274454','5492026','11783725','11972362','11783701','11759764','11759768','11972342','11972380','11972390','11972397','36131674','36266338','7092017','5280159','5268271','14245874','14228617','17671080','13085078','5269304','11756927','10815079','5269889','12066278','23740427','17669822','12929575','36282239','36118853','5397562','5269633','5391317','5781727','6206547','6871758','10621105','12874572','12853077','12908059','13621039','12688629','12581280','12408411','11569967','11255947','11493825','11145232','11152532','13528642','12853044','10275499','11519594','11047130','11580418','11580974','11609531','13891335','13891405','13892565','11023785','13889353','26268370','12851118','13890510','13890696','13890617','13887896','13887672','13892885','13888427','13888193','14704433','13886984','13888569','11865083','13891034','31387654','36143532','4339803','5310076','8310107','36260440','2301606','6396048','13854251','18908030','15362222','9702449','24456344','18918493','18006060','9856374','27995887','2301725','10239690','22491361','8583848','8567586','8566141','8566299','8574124','8568509','8575238','8555123','8571563','8562547','8561396','8561937','8578671','8578672','8541318','8549968','8566883','18005729','10193462','9702671','8568517','9855218','9855219','9855233','18918029','8015671','8169544','14296048','13744977','13758265','14130844','13062271','13062285','13405804','13066983','13067027','13062248','13062258','13068133','13068154','13692246','13692195','13692189','30628415','8019748','3620306','10826588','6723244','36226568','21490964','11635162','9276342','11783728','11759794','11783692','11972355','11972375','11783708','11759777','11759783','11972328','11660385','11254671','11972405','11660386','12713305','11513825','36238845','27441991','5270727','5286792','36117203','5383828','36306024','22592215','5782640','3883302','4197813','9139904','12960278','22457078','12607608','11214973','10933703','11019881','11083124','11145052','11145131','11518654','33134587','10997217','14463207','13060612','14282031','12600013','11046449','11251850','13888989','13888033','13892957','13886880','26234101','13889232','13892675','13887913','13886923','13889225','13888053','4277382','4277573','8381403','7823484','6384647','14737322','16392072','27639267','13435284','14974808','20822445','9702456','9874625','9855995','9856006','9856379','9782365','13435740','9699760','26960858','9880431','18915630','9201227','8569631','8583849','8576716','8566133','8566296','8571128','8568513','8572467','8562371','8571217','8571240','8571559','8561307','8558552','8563058','8563705','8563707','8546804','8546153','8548176','27885154','8569778','10214435','8567327','10193443','8552152','18915013','18918796','10193504','9702662','9702677','10193731','10193775','4354660','9855225','9855223','9855204','9855216','8008902','14649725','14649715','13726383','14138986','14123322','13062279','13059285','13059295','13059298','13067024','13062251','13068139','36143004','13758596','14602287','8168395','8170533','8178511','14274812','11768820','5498848','21491062','11635784','10910857','11783687','11783689','11972358','11783699','11783703','11783709','11783717','11759776','11759781','11759788','11972384','11972394','12449268','12713455','34006629','7799250','14339674','14345363','14339710','12929132','5982844','10813835','10814994','7800541','5269561','5269758','19805100','36290953','5383653','5970902','5296157','7050974','5433430','36311291','25860626','7101329','2476570','10623356','10912489','9755610','12960287','12874489','18528344','12599804','11514799','11048334','11082824','14704400','13368974','14295456','15418754','13888152','11093456','13893472','13893480','13888796','13890518','13889688','13886788','13886798','13887966','13888286','29667470','8453472','4110909','2301907','4370941','4354711','2301697','8313416','8488096','8173077','8300845','6382270','6382268','4131063','22158181','17054567','32428301','13435290','13435734','9702450','8659805','12632847','10185122','10239671','9994470','26673863','13854268','9154527','4354806','18914357','12777528','17054462','9880455','14974766','12873276','13521033','8576722','8576661','8566803','8567973','8568052','8568847','8562388','8562389','8572957','8571556','8563663','8546132','8546133','8544709','8571102','27533966','28763363','9702659','9855258','9855227','18915554','9855208','9855211','9855253','20524633','4267317','8009087','8176422','2645092','13062298','13406718','13066993','13068159','13530317','8175604','13939233','3609884','13030725','5496914','11636598','27290233','5498362','11972353','11972356','11972371','11783704','11783707','11783711','11759786','11759792','9052884','36123648','11972387','11972458','11660390','12713741','11190860','11642943','6140397','24176975','29975610','27194629','10793426','5277425','12066330','5265862','5265918','5986112','22802920','5432051','5399248','5385356','13085011','7790648','27624666','34776632','5783247','6871631','12881554','12908082','15516959','14841528','13398410','12648813','12874171','11514628','11567334','11568570','11215378','11019729','11083178','11262409','14282032','13013316','11224261','12874178','12960260','11580034','13887135','13893234','12851105','13889351','13888096','13888128','26298952','14704403','29942684','8113714','14463222','36190736','36190734','26267941','5311840','8434373','5311575','5309006','5657278','5655561','5650161','5671957','8455827','4205085','2301822','30391311','14737402','11864379','18918046','9753757','2301612','8437451','14974662','9856383','9855991','36206932','2301848','23297431','9699766','9699762','9880424','8513536','2301548','26964633','8575469','8567246','8566957','8566834','8566845','8566270','8566812','8581818','8568844','8553029','8557210','8557187','8572967','8571550','8558554','8563848','8555932','8546087','8541124','8216767','8541033','8536666','6395944','10193549','10193514','10193625','9702461','9702463','9702466','9855256','8563346','8570104','9855230','9855213','18916298','8177771','7847234','2646994','11735182','10523436','14300592','14649701','14738460','13752297','13717743','13745802','13692687','14122590','12963560','13062274','13062286','32177736','13698685','14270387','14270383','14274813','14122607','5498817','5499060','26956514','11091313','9277084','23915331','11634453','10498206','11783691','11783720','11759774','11759790','11972421','11972433','36111302','11972402','12713813','12714659','11148056','36121417','35789437','11254515','5297451','6158290','22803638','7051783','10793361','9853444','12331710','10814001','5294137','10793251','5297424','5285030','36144721','19474977','5370577','7652760','5415364','22851055','23740499','5784786','11019937','11084324','9139846','12853069','12608735','12689611','12688059','12580004','12851313','36129200','10672880','11493949','15626661','11534119','11581244','11060423','11909515','13887954','13893309','13886662','13889334','13889095','13889437','13887564','13888182','26321710','15023264','14704408','14583896','13188192','13697721','11255378','36285061','6380602','2301875','6381445','31790773','8298753','18905967','13615047','13854307','13407024','13399102','29383829','8661840','27454125','9475900','9856009','9790645','9753760','22920941','13854271','9856385','9856380','30390962','26754418','15343531','2301830','8569765','8570038','8569757','8570361','8567329','8567267','8566835','8566837','8566827','8574660','8568914','8568843','8572476','8562511','8572959','8571554','8563726','8546149','8546125','8545447','27428138','8541023','8564263','10193662','10193575','10193596','10193683','10193696','19394884','9702678',
 '9702679','9702693','9855257','9855255','9855260','9855261','9855259','10193764','10193741','9855221','9855242','9855244','9855250','8014006','8007596','8008794','10826137','10523461','13939301','22660507','10524543','13406721','32632044','23510136','10501650','11636530','11636567','8795248','11643734','11783731','11783719',
 '11783722','11783695','11783715','11660391','11759779','11660427','11972429','11972428','11972393','11972396','11972401','11972412','5498283','12714371','33187880','14345464','13085165','19802021','12929196','30986199','25199966','12449499','11824206','5270181','20049647','12335268','12029773','34776225','36156320','8333320','5971455','33187677','29643397','5430207','5433547','5385272','5400411','5412546','17672435','5754214','4199007','11522403','36106345','22456845','12599901','12600092','12689008','12689347','12579705','36158254','11491038','11259450','10679317','11048408','10633418','11547535','12384916','11020592','12604897','10995899','35369325','11045045','13887279','13893902','13893924','13888556','13888579','12851111','13889376','13889399','13889245','13888168','12851289','26322436','26323206','34065653','5309083','5309809','7924973','5313703','4277545','9154671','36283901','36192269','4325481','4131042','18920229','19038804','14607984','35774069','10139570','8659720','12632846','10212218','27150077','18918337','9874630','9874632','9790646','9753787','10241475','5655560','9753813','19045921','4205614','8570066','8570089','8570350','8576728','8574118','8568508','8568055','8562384','8555125','8571231','8572963','8572964','8571551','8580963','8563366','8546129','8546151','8548178','8548182','8548189','8541202','28765809','19654059','2301869','18920428','30391112','10193635','10193647','10193691','10193700','9702469','9702680','18918331','9855234','8168788','10944272','11780082','13939203','13717749','13717765','3588739','14127612','18936861','5499639','8454570','28617915','24831603','12882656','11091711','11635194','10667435','11091683','5491875','11642984','5515719','13499288','29175632','11759798','11759801','11759802','11759805','11783721','11783710','11660398','11660400','11759784','11972452','11972411','11972415','11931428','36283946','5269425','24799315','22802726','25244087','13085125','14345404','14339667','12929105','7795679','5461262','12062082','11755363','5269488','10815177','13669716','22803303','12028369','23738938','36275902','5415076','5419915','5413052','5413518','5270875','5754097','7101065','3873105','5721373','36209622','36180891','12960280','12908039','12908112','22463936','25396934','12649219','12580899','12853140','14346827','11493723','10679051','11146085','14463209','11173003','14282038','12376232','13889194','13888161','13408648','13893353','13893857','13892961','13892985','13889891','11512341','13893036','13893262','13087773','12804326','11515158','9139973','31164185','5309904','4354814','7812639','8458635','7799289','13435900','15799885','15343534','27633473','14974787','10233638','18914815','18919588','9810957','9856012','9856027','9856044','9753762','9753767','9753781','33311835','9856002','9856004','9856007','9855990','9855992','4203974','2301868','2301823','10897277','14940409','8569752','8569760','8574574','8574653','8574120','8568046','8568054','8568056','8568841','8562516','8555126','8572960','8571555','8561237','8561240','8561288','8561298','8563661','8563065','8563745','8581260','8548181','8548186','8548191','8548197','8548198','6667633','8549969','18915639','28698711','6667520','8539326','8544742','15274589','15270885','6379906','8547851','18918448','9702665','22890040','7851564','10523462','14300555','14649718','14649729','14714285','13939239','14130793','14087888','13368188','13405810','12907479','14602290','13406731','3604047','36286118','10498161','10883706','10941020','9071236','10910957','9072683','11660457','11759803','11660411','23153185','11972425','11972427','11972443','11972455','11254560','36124852','36124877','11905318','13906145','5265136','24799999','17672363','13204413','27824139','17665210','5270838','12449496','10810688','8485525','5296201','5372930','8786508','5384809','5981981','22803088','7051657','22803620','7756432','31932983','3882873','11515002','11040787','12908068','16088222','17930974','12649184','12874140','12874211','11521238','11515234','11579335','11252099','10672398','10914246','10914262','14713961','14282620','11047657','12881511','11534067','11090491','12908071','11570387','15516565','13888976','13891345','13888154','13893067','13892874','13889785','13889563','8382116','28405717','8434863','5348763','2301904','8216049','4370991','2301695','2301859','8456004','6389978','4277357','4368341','4272335','7768701','19044279','10140321','12070982','22888251','21573792','18918041','9856008','9942524','9753766','36123670','9856001','13854302','9856011','4354874','9699761','10897278','8569559','8576659','8566810','8566322','8566819','8581637','8574658','8574119','8568506','8572472','8552916','8562517','8557192','8561245','8563619','8563064','8563850','8563749','8578086','8556110','8550915','8546130','8548177','8548194','8548199','8548201','8541475','8540436','8571101','27884386','14038504','28763589','8545445','8540578','27446662','10241470','9702661','9702682','9702688','2644424','11782175','11768912','8179064','14276146','13726328','14122598','13011537','13406709','22663318','12963521','3597274','3512076','22805128','11729570','13695937','33177387','24463316','22826419','12904732','11166692','11091528','27292875','28288990','11635814','11783723','11660394','11660401','11972451','11972447','36126248','36127990','11783909','36110207','27480542','7769465','28949024','26806866','17672562','25245996','19468216','22298534','12033369','10812094','10793671','10815139','5269541','36195673','20049994','6127513','23983264','5383340','12033470','5413835','11169247','7846261','29429984','7763400','2471144','5721090','31165459','10019541','33134529','12908123','23544050','12648029','12687954','12687146','13063617','11542268','11547151','11547234','11569590','11084297','11494273','11487803','11093016','7551007','10991331','12874491','11172874','14713958','12384911','11047552','12648658','10236403','13888912','12851066','13893377','13893083','13888539','13886997','26285008','13889863','13889708','13890210','12851095','11540548','28508497','11255400','11255466','13759794','5346888','5346316','7790320','4370861','5654745','5656483','8215623','2301860','8455441','8488176','6381669','36287525','36260367','6382078','4131140','4277352','2301659','6395427','2301609','20420850','13435755','15696623','27581551','13344691','23014814','23917829','23666623','26612115','20783122','20543770','18919189','9856014','9856018','9856020','9856022','9856024','9790691','9753769','9753785','23109350','9856382','9259851','2301795','4354812','10897285','11866282','8569681','8569382','8576658','8576660','8576905','8567580','8566806','8566286','8580035','8574578','8574652','8567977','8553725','8561243','8563667','8563062','8563713','8581162','8578904','8546126','8546134','8548179','8548183','8548184','8548185','8548190','8548195','8548235','8579281','31334287','27887756','27535835','8540732','8544730','2301693','8566880','18918793','10897467','9702666','9702691','18915228','18920249','8016351','5302209','8168695','10946079','14602272','14294862','13697380','13395085','22810829','13939346','28436905','13939336','3625222','12963237','3634389','14270380','23965684','36129116','11660396','11660412','11660424','11660432','11660435','11972418','36124847','11972409','11972414','35724271','20522445','6141118','14329279','13232112','20049132','7051394','25200791','12021052','10813958','23280200','5293986','5269487','5270857','17665135','25210070','5417510','36320574','17671856','5278010','8388809','7135330','3883324','2782917','2476574','36261609','36261559','9139909','10620520','12874561','12874564','12853042','12687182','12691568','36126354','14768812','11519398','11515617','11569953','11259843','11516161','3882902','2471028','14463208','11262582','11174928','11023538','11172982','12453559','14282037','14282480','11150371','11020700','12874164','11041251','11580846','10991551','15516962','13087750','13887633','13888130','13888149','13893158','13892897','13888361','26227314','13890116','26323307','11521439','13888487','22454892','8430101','13888226','11255498','5655232','2301856','8455298','2301803','9154664','19794715','14668626','26382371','18919413','9810959','9856010','9856015','9856019','9856033','9856046','13854273','10139648','5650664','18917332','24481759','22879379','31790552','9699764','10897373','18915017','8569770','8569800','8576643','8567461','8566950','8566808','8580037','8571233','8560827','8558196','8563706','8581165','8577518','8578139','8556098','8545171','8545794','8548210','8539948','8537701','8571100','6396554','28766948','8540739','2301837','15324710','19038385','19651627','18920422','10234066','30391050','8556770','8015490','8123409','6024342','11739971','14771549','13939302','22156364','21930574','13776111','13726357','13692231','13692250','13692193','13396917','13001075','8176679','14084099','9109874','10523460','13030735','5499679','23523674','16786472','12330877','5491687','11660458','36126235','10498202','11972416','7800392','14244721','14228084','7794927','7091143','5270382','5296360','10813699','17670927','8038645','23279782','13085026','27624719','24309002','36173953','36144747','5996926','7051749','5419196','10090209','13085096','10815307','36296352','27624707','6981041','5750456','2471069','25545058','9139966','11119540','12959390','12960270','12908052','22456683','12687538','12687800','13158082','12851307','12874181','11540856','11579040','11092106','9139978','13665134','36222452','12687898','12874505','12851077','13887164','13887167','26226991','13893783','13892820','13893096','36241185','13888349','13888418','12851301','13890118','13890170','13890188','13887917','13888188','11512907','11023769','13892816','4367690','2301839','7824406','4198643','5672016','8458105','8122272','9154646','6384708','4277602','4325447','2301644','2301825','6396607','18920408','19802406','18005914','14974799','10135842','9201208','12777199','9994691','23012343','9874633','9856028','9856032','9856036','8379209','4277302','9856040','2301640','11902435','7811908','10897289',
 '17378486','8215170','8569665','8569685','8576651','8566787','8566804','8566297','8582487','8574562','8567735','8572470','8563055','8561247','8563063','8580550','8546131','8546136','8545809','8545819','8548246','8548203','8548204','8548207','7790566','8540879','6657227','8537662','8537252','18919719','2301805','8575785','9702685','36143493','7794986','7816933','27582693','10233291','11519729','8169367','6024690','5302101','14294851','14294861','14738464','13726342','13726349','13717753','13717761','13011546','12937289','13692234','13697365','13746536','3608143','3616373','8170348','5497261','36255133','29335263','29210757','26943238','36226531','11660455','11660404','11660428','11660452','11972454','11932281','11513899','11254517','10494951','36298754','5267434','6161203','5296517','25118222','21708839','13085134','24799942','12472316','12444140','12413619','12331409','33729708','6242064','12062136','36217358','5284782','17672600','14535684','25421398','5419613','5400143','5421060','13085042','2782945','2476572','36261573','9139986','15380669','15380678','14841529','14936132','12687206','12687625','12476809','11092742','11252165','11023727','11172960','11172761','12408212','11172573','24399556','14282477','14293263','12874503','12908093','11581063','11519556','12848747','13890967','13887156','13888820','13888807','13888499','5753858','13889875','13889977','13892261','13892130','13887641','5782547','13890540','3872301','5309644','5310153','2301906','4354979','4355056','8182003','4370633','5655641','5670790','7797614','8383462','2301826','6396769','19652464','18005758','11866426','10186554','23014603','22592551','21217623','21163830','9873585','9856039','9790682','9753768','36118035','15727106','16098874','9476692','9699769','10897290','8569804','8570061','8569377','8569562','8570358','8576645','8576653','8576657','8575464','8566954','8566277','8582493','8576068','8568053','8568919','8563164','8571214','8571230','8561301','8559987','8580562','8577853','8555524','8546643','8545702','8548213','8544715','8539969','28754700','6639312','18005814','9686892','2301716','8576303','9702683','9702695','10320849','18918279','8141316','11736058','6682475','14767091','13939295','14738449','14738485','13749480','13698682','22157205','14129303','14129308','14123345','14129232','14129241','14129260','13745627','13368103','12329895','31866671','28936194','13698703','3510904','3511470','3617136','3632044','3618529','36195398','8171955','13758227','30321543','5497676','20387261','21885861','10941031','11091310','11091331','9262565','10668271','9277316','10910721','11643749','11660419','11972440','36226537','36104316','5296460','24094870','29975495','14228053','21707939','14345407','7051602','12456040','12029447','10815037','24799228','22776234','19829892','7789199','30543988','30543890','5282639','12929586','5430710','5396581','5418985','5432788','13204662','2471130','2471046','13727654','12874216','15516580','15517666','15379720','15380671','25546647','12687780','12648315','12581343','12874197','14768803','11515130','11570219','11570435','11092016','11084461','10672407','10679391','10921279','11145476','11145581','11145675','14713962','11019711','11568536','31500715','14713978','11821914','11259793','11047452','12384903','11215386','11580931','12691553','13889203','12376010','12851084','13888157','13887810','13893199','13893376','13893864','13893552','13894020','13887204','13888840','35858593','13892264','12851286','13888176','13888102','26299294','11252181','14348134','5309236','5295834','2301840','7800528','5560662','2301849','4325408','36262876','36247803','4130999','2301663','6396617','19041860','13435762','27706377','10137865','9201201','8689867','10897085','10214606','9994460','18914316','21219164','18919226','9856037','9856038','9753777','9753814','22915751','2301702','15204930','9753776','9880425','2301804','8569777','8570347','8567576','8566280','8582482','8582495','8575470','8574571','8573617','8568043','8564201','8564045','8563056','8561302','8563775','8563627','8580970','8548256','8548214','18918038','8206538','28763652','27621961','6652904','29373531','27882609','8544990','8564253','18919734','15363552','22921620','18920424','9702689','19650978','8563330','4277310','8567331','8140152','8180813','14294852','14300562','13939259','13726365','13717758','22156966','14129772','13011550','13011571','13405821','13405131','27269182','13692670','8179428','5491927','29260944','18187331','22828584','10501656','9140467','9277254','36125227','10498160','10910582','11660453','11660454','11660456','11660416','11660423','11660441','11972435','11972444','11972456','12873999','12874218','11827971','9275333','5282384','7092117','36298748','14346994','23740652','22975709','26925012','27261977','22850842','21708495','21708790','13241076','20048569','10728046','5270787','12472503','10793928','10150578','5277452','5279691','17670969','36169779','36144729','5994123','5371102','5422551','13202126','5430228','5430881','10091904','22335291','5743422','2783001','4198844','11040828','9140050','12874585','12874321','17315357','14841535','12600777','12853026','12408326','11215097','10922119','11516777','11145412','11147903','14428800','14463217','14282478','14293261','11092367','12686299','12881497','11521925','11262663','13158088','15428123','11540978','11568657','11521497','11251768','13087746','13888999','13887578','13887143','13893476','13893488','13888492','13888382','13888400','13889939','13889242','13891109','13892248','14628511','13888097','13893258','30344644','13758907','13891400','5309978','4129565','4370731','4370854','2301760','8186258','8509387','9154649','36288667','36308961','4325424','2301599','6396867','31670401','13855517','13669803','13435757','13435267','13435741','10137869','9733784','9474699','10240909','10211634','18919020','9810917','9753778','9753794','30390943','22886994','18902677','10152406','9874641','4325629','13184107','6396199','13854295','5312157','23107481','10897292','21215338','8569817','8570057','8576715','8576644','8576655','8576631','8567458','8579699','8582485','8581649','8574565','8564204','8564208','8563057','8563712','8559376','8580971','8578084','4354845','8548865','8540149','8537917','8546138','8298587','6396810','34017985','31344351','27628954','28699472','8545425','8567242','14492669','27244630','8567218','18919712','7822132','14608955','8178935','7103656','10523464','14300597','14738445','14714287','13776174','13726320','13698688','14129313','13695959','13697379','13939229','3512071','32518610','14738447','13030728','5533543','29211974','24926670','10265716','10942896','10941012','9277119','36292553','5499531','5516217','11660422','11660430','11254473','23156647','36226538','5279976','14339709','21708535','25246777','25242906','19804227','24800285','14329305','12929332','7452525','5270715','12444142','10811825','12332585','12333688','5277371','5269407','24177357','13085044','14346907','10109887','5372275','5372576','13202073','12929200','5399625','5269839','5268414','22454711','9139902','9140049','9747422','14841536','12874554','12874588','12874481','12874507','14841532','12608720','12687281','12649286','12687128','12656689','13158114','12853147','12853164','14606870','14460056','14301248','11541072','11547175','11579654','10672396','11083910','11019269','11516113','11488271','11150620','11151694','13087762','14463213','15626663','15626667','15626670','11215050','14760384','11262652','14282481','14282632','11047742','12908102','35370845','13887113','13887130','13893360','12851282','13889263','13889169','13889699','13892624','13887538','11048512','10934018','13888126','13888581','5784689','8390105','26234074','5297429','5295927','5301428','7957380','4354836','7795425','8177571','8302746','4326532','2301694','2301757','8214321','8480430','2301587','21964257','36158657','18255204','15336507','33320660','13435737','14225684','9476689','22442609','20825020','21156862','9810961','9874644','4277341','23111727','9474737','15697119','15194733','15363585','4201757','9699772','25695075','10284610','14940685','14490829','8570076','8570035','8570418','8570423','8570359','8570360','8576717','8576646','8567455','8575776','8580039','8582491','8574570','8574155','8574245','8571247','8568050','8572463','8553089','8552658','8562512','8562518','8562550','8562557','8561389','8561025','8558232','8561848','8563700','8563734','8581942','8583845','8581146','8580967','8578085','8580616','8578617','8578898','8546471','8546098','8540334','8540629','28742865','8537933','8538805','8540771','8564257','8583841','8558190','8563153','13345264','10523458','18515728','13939349','14738480','14139480','14123327','14123334','14129238','13368142','13368151','18097200','32526608','13692696','13695938','13695953','13697352','13692240','13697372','13692251','12963571','3612075','3634846','5316775','4572702','33045277','23510555','12797743','10498213','11634323','9280704','9275487','11660418','16371161','11907224','36126869','35908788','11905428','36310544','36313727','7799800','25200222','25248545','25243068','17665352','7089579','5287202','24800099','12456054','12448128','31352253','10125941','10125868','11755154','5269887','12066292','5267034','7816600','12797022','5279777','5279788','5293808','5269950','30543953','5441445','5422643','10868277','7838727','10136769','5269457','5430349','36273164','22522275','17672627','13330269','5754534','8388540','8390574','3881811','11040745','11041148','12960282','12881531','22455871','15516568','23711323','14813988','14936136','12605222','12687991','12649587','12688388','13087778','12853141','36118198','11519459','11569620','11569665','11569777','10914257','10620246','11516002','11521785','13368978','8382090','12689527','11262561','10912507','14276526','15428266','11047626','12647463','11215442','5743841','12853032','11090689','3882559','10265280','12848767','13887124','13888132','26226971','13889345','26234249','13891892','13890567','13890787','13888051','11512753','11512494','13892738','5784723',
 '13891911','11515725','9154537','7760922','4335788','5655732','5670722','8313527','8340482','4368954','36182599','4325589','2301596','2301627','2301806','2301824','6396449','31790882','16316262','18907160','33319590','14737395','13435739','14974737','10155383','8665589','9733785','10285057','22442367','22873758','18917334','18917336','20837659','9810909','9790658','9755051','18968061','24424931','4277153','18915139','6384989','13854306','5655876','9880433','9880427','8570074','8570357','8576721','8576652','8576654','8566334','8580034','8582480','8574564','8574573','8573622','8567971','8564198','8553701','8562528','8555162','8561305','8561275','8583843','8580966','8580968','8578903','8556113','8546090','8546091','8546124','8545837','8545694','8548252','8539185','8537689','6638690','10230492','28754724','7794539','2301838','8546142','15324714','6638030','8552154','9476700','9753789','31370498','10241484','11519570','4334934','8007323','22155932','14294856','14275383','14602266','14057638','13776180','13758338','13726337','13726311','13858241','13698691','14129293','14129468','14123376','13011556','4335001','13368079','31603411','24987899','13396315','33291472','21930648','3620668','5491963','15018062','20900433','10667585','9114474','36118588','10909620','27973052','11582477','11254601','11905331','27624285','14345384','26806666','25210648','13110344','13085052','13085055','13085195','36117859','15151453','7793693','12417106','10793786','5294339','13080585','13011730','17672599','11761978','17672663','5383459','10870890','13038365','12929567','36144727','23280301','36238836','2783012','2470954','3882272','11040770','10620586','10912487','29086358','12874125','15516572','15516577','22854858','24172964','14841550','12608634','12687597','12688227','12853130','12853171','11540923','11546881','11568487','11579771','11579175','11090833','11092783','11490542','11259546','11259756','10914258','10914243','11019422','10620335','11516064','11490404','11147657','11150731','11174994','14463219','8390401','15626675','11023694','11569699','9139857','14649990','14713965','14293264','2471127','14282488','12384909','11495089','11172901','14841575','14282626','13889191','13888951','13887220','13887862','12851303','13886786','13886802','13891926','13889895','13889680','13889727','13892419','13888073','13087775','13889676','13886755','5784302','9275249','9008885','5216617','5312096','5312328','5310302','5286100','2301909','7823004','7815799','5539101','2301794','5671206','6381714','7798628','9008869','4352287','4354349','4201698','2301821','6384852','12875314','33334490','13341696','13345596','13854303','9989158','20769555','21974893','21574165','18918164','9820058','9810911','9873586','9790632','9790686','4277124','22151224','20847444','18005799','18920345','20765434','9880428','10897067','18915020','18915648','8569655','8569810','8569679','8569373','8569374','8576647','8567245','8566952','8574654','8552171','8571223','8562553','8561278','8561285','8561286','8563601','8563795','8563618','8563650','8561571','8564044','8563693','8563728','8577516','8563332','8546137','8546111','8544992','8540489','8539953','8540057','8549325','27517735','8541083','27883339','8538829','18918934','12752729','8569167','8580163','9753798','8569663','36144812','30390809','28762895','8570087','10970059','4335282','8020040','8169650','8167935','11780619','8179545','14300590','14295369','13939331','13752302','13758220','14122617','14123330','14123359','14122587','12963580','13011564','13406710','13698699','13695976','13939343','36189322','3612736','13030136','5498786','36181190','29264055','33014847','20900308','21658438','21490987','11540393','10262387','36103585','5492793','12625880','11513960','11254456','7092202','36264925','24799305','24080396','22851012','14329307','7458953','12444158','10823291','12382298','8485979','5294095','11763017','10103245','7829921','5986505','5399956','12929322','26816211','10808211','5751717','9139948','9274767','10672376','9139852','10912508','12853085','12881507','12874347','12874398','12908085','12908116','12908125','12874485','15418797','15379706','15428117','12688561','12688914','12655121','12851342','36118218','11541355','11568614','11569645','11579380','11515829','8390678','11521812','7321831','13619081','14428802','11023145','11262624','11740978','14282483','14293213','11262668','12874284','13891017','13893399','13893433','13893542','13888341','13888398','13887065','13889084','13889099','13888825','13888828','26272793','13892320','13889873','13889421','26279456','13893229','11512707','11512324','13888084','36221622','13887942','8386950','11092141','36190749','13891772','4354887','4370451','4277177','2301857','2301717','2301552','2301736','6396494','6382641','13435763','18908036','18905807','15336487','15194585','13854279','13854285','13854298','13854487','13854495','9476696','12803100','9994456','21973711','9874635','9790636','9753790','2301863','12778679','27635358','4201729','18917325','10897071','4203882','36225777','13854502','9699777','2301633','10897379','14491473','8569694','8569699','8569368','8569587','8569363','8576904','8567564','8566946','8567262','8566229','8565914','8566134','8568614','8567963','8553702','8562376','8571235','8558559','8563694','8580554','8580964','8577517','8546145','8544998','8547846','8539941','28766264','8567241','34016558','27458272','27884163','8537017','8546139','8546140','19041243','15274605','15274591','15324774','8552155','8550845','18917990','15343516','18918283','8581630','10235645','7793859','36143499','30390778','36294977','36349966','8178822','8178872','7869182','11730068','13939296','14738444','13939332','13752310','13745630','20723680','36165943','24137300','13692200','13692255','13395439','13745620','13746308','14300576','13695930','8141126','31779199','3599501','3593099','23640738','22155725','5496780','23524397','25798249','36178751','10943041','27501425','12800668','24926250','11582819','11599083','11254467','11166181','5515013','36126202','12875946','11514014','11905359','13904594','5293832','5299020','28630513','27194062','13238806','13032657','7767514','7050140','12449481','12449479','10125867','12029531','23281006','5268225','5269922','12066243','22850984','25178167','26807432','5284700','36144712','30543975','5993225','8338597','8787136','5370447','5441665','5442422','7757819','7272051','8388746','11546825','12874138','12881535','17538368','14841539','14841562','12605179','12689460','11519226','11515802','11569811','11632361','10997688','11045399','11491306','10672389','10933139','11092936','11515942','11147790','12379460','15516591','7490015','14428803','14428805','11145415','11024049','9140002','19469670','25403233','14282628','14282634','12853062','12376088','11090553','12881508','14442610','35370571','11259688','15427437','12851088','12848771','13887134','12804294','13893392','13893403','13893428','13888598','26233844','10910071','13889843','13890159','13889409','13889800','13891503','13887631','36114337','13893934','17606082','33003269','2301581','4352550','4354743','2301853','5667595','36196441','4205325','6395606','31790545','31790561','19616980','4131146','18905958','15203924','13854489','10155961','9476698','12632843','10014380','24345049','24458832','23918784','18915840','21976445','21978913','18918485','18917881','9812113','9810912','9790693','9753816','8428959','21159962','15363555','27514401','4325101','18005930','9880435','10239680','11483810','8570063','8569380','8576720','8576650','8567567','8567448','8566921','8566755','8580961','8582481','8567975','8562522','8571224','8571242','8561274','8563779','8561313','8581909','8580969','8546135','8546144','8546096','8546097','8545709','8547896','8548254','8558588','28768209','31335620','27586111','28755630','28764506','8544737','8558233','36205297','8564260','8566878','14226186','8581629','8580446','7794760','8546092','8177861','11739531','11769364','14294869','14300561','14771550','14738468','22048250','13757291','13757293','13857882','14134835','14123338','14123342','13368131','13368137','32526600','13698697','13692223','13939224','13858243','10524296','14135135','13757151','8170792','5498105','5496860','5497643','29263286','36143865','21490900','9137358','9275450','27502059','36123647','10910638','11643041','27973174','11254622','11599543','11254522','12772133','11148097','11514185','11599233','5280667','25178362','14346956','26982073','21707766','13201873','13085115','13085136','5270851','10125865','10807902','5297376','5269788','17671528','11794090','36144732','5383554','5986213','5384552','17663566','17672701','5431462','5431865','5422373','25121099','36155161','7793459','5751765','2471128','2471004','5782104','9139922','9139868','10912775','9756021','29597685','30436754','12874578','12874493','15379556','17606065','14841547','14841548','12607351','12687750','13087782','12853144','36118194','14705858','11521020','11541018','11534454','10997851','10997891','11090788','11084401','11491181','11491412','11259809','11494310','11093609','14274561','11521767','6993160','11172567','14643624','11040839','14463221','15626680','7029574','11636039','14293203','12881547','12689074','12876003','12881549','35370342','12338694','12804269','12848759','13887777','13888148','13893885','13893015','13888593','13888599','13886993','13890143','13889236','13889243','13892562','13892578','13892582','13888054','26346221','13887218','11512623','13889000','13892892','11045149','31495747','8389925','13888659','14349123','5308298','5308857','2301631','2301576','7799711','5655801','5650952','4272381','2301864','4205115','30391371','19652035','19650544','14668632','18908110','27634841','28161629','14974793','13435744','12751849','10161819','9201217','9476725','10213282','20779466','21974692','18918533','9874638','9874658','9790655','9753786','8436311','18914052','10897084','18915844','27584557','8312281','10241472','9733815','9810913','12746307','9699774','4277349','9880437','9880434','10897053','10897294','10897061',
 '10284703','27580766','18915652','8569825','8569687','8570040','8570053','8570082','8570354','8576641','8576648','8567572','8567453','8575449','8566949','8566202','8566228','8580960','8580036','8576009','8574246','8574248','8574249','8574252','8567980','8573521','8572462','8564119','8563986','8555147','8555155','8561280','8561283','8558191','8583765','8578901','8546128','8546095','8545003','8545432','8545704','8539711','8549326','8548626','8547047','8579279','28763261','8538999','8566872','15274593','18005901','21158483','18920436','33310627','36157223','8580162','22003622','8557371','27461285','7868475','13749691','14138714','14275063','12963518','13011554','13405814','13405826','31598015','22811167','31622491','32516934','3611480','6905984','21930679','14738462','13030739','24831971','21658173','23523979','36163832','13726883','15018065','12883519','12355903','11091348','9261063','8795673','9277487','6081457','11254646','11254499','11254462','11254518','10498201','12874985','11514106','36126020','11905387','29428547','26924545','17670953','13085110','13084972','13084990','13084993','27624639','19468232','12880336','10620289','24800073','19468198','34417814','12451545','12451550','12454585','12333803','12335371','13011726','5296384','13085014','28948928','5286845','13268888','10102487','7829635','5985122','5986454','5986542','5396995','5418021','27442150','8387503','4198483','3873118','2470998','8388857','11083735','9139979','9139845','11093655','11119504','35374980','12874584','12874134','12874359','12908037','15428142','17499973','14841546','14841541','14841545','12649124','12655117','13158180','12338062','14608885','14346828','14536773','11519325','11541106','11566122','11568638','11518802','10998098','11092362','11090955','11090963','10672385','10672416','11048384','11515894','11151593','11521750','8390207','17538482','11172936','14713966','14282484','14282627','12376008','11020944','36222442','17398100','11579827','11150967','13888915','13889021','12851072','13888151','13893848','13893967','13893291','13886926','13888336','13889266','14628518','36113583','13889840','13890688','13887634','13887696','26281533','7827587','13888431','13888123','26322460','26324364','15626678','13888391','36190762','5298996','4354796','4354932','4354941','6380656','6397547','7814138','5656372','2301847','5850208','8429086','8357512','8359512','2301611','2301675','2301546','4130933','19727276','18920369','19050661','18903923','18005997','27917397','13854505','10152678','10152872','10137870','9733786','9201246','21164185','22153896','18917411','18917441','9874640','9790654','9790668','9790680','19039507','30563786','9790642','3117085','18005477','10241491','4370730','14039382','9699780','9476683','9880441','10238011','10897054','12872216','8569675','8570090','8576642','8576718','8576649','8567571','8567088','8566936','8567265','8574563','8574247','8574251','8557183','8564138','8571245','8561270','8560294','8560201','8562548','8564034','8563741','8563743','8580962','8580972','8578083','8578899','8578900','8556211','8555934','8546523','8546155','8546309','8545708','8545793','8548240','8547861','8548651','8548662','8540495','8540449','8540471','36214733','8538689','8537060','8539716','8579280','8540970','36347261','36205288','34024297','8536809','8538877','18920311','20740477','8562468','9755019','18919739','18918298','18919504','18914482','34229920','8540752','6389712','15343519','8557191','8576903','8584527','18918097','36144781','8549456','10524528','14294859','14296050','13939335','13939223','13939324','13753377','13857811','13368097','4335488','4335686','12329680','13695980','13939345','13696206','8019472','8035106','3615231','3625259','5499581','5498952','8452003','36253379','32765191','20522839','19175375','23524221','25891743','15018067','10265006','9140752','22069148','10909031','9114645','10910329','11254635','11254453','11254565','11254658','14489662','25175749','29975405','34778424','13085090','27473458','5297471','35733246','12456044','12451542','12446675','10822801','10823053','10811659','10794096','5983611','13011721','5279544','17670895','12571369','12929279','27477058','12867786','17672489','5277931','19474777','5424077','8338707','5384028','17670603','31222097','5394050','5394193','5421330','13037383','22354827','13626998','11755599','5754518','5742291','2471105','2471015','2471022','3881291','3882152','36260070','11534282','11040722','9139896','9746896','10541303','10672384','13672223','12908097','12874433','12881544','24242917','14841543','14841572','14841564','14841558','14841565','12600665','13180360','12874154','14301251','11543799','11517190','11045463','11092355','11091026','11491117','10672405','10996003','11494655','11145288','14463215','14463229','14760392','14293193','11093544','11020677','11554941','23625363','12881495','35370912','24241599','13888742','12804406','13887850','13893108','36241180','13888779','13888519','13887070','13889272','26227260','13887736','13887754','26322651','26321855','26298756','11512811','13887845','13889671','15428270','5216318','5295674','4277375','8300144','8302152','8179152','4370507','5650317','5658608','5668946','8389766','4174425','8354878','9008873','36291747','4199133','4174419','6334857','6395927','6395308','31790418','31790892','18005575','17054553','15343547','34918566','27581999','27586004','13854546','13435361','36100552','10153018','9201220','12776543','11012780','10214187','22916058','24346011','23107079','22873259','26611580','20819296','21974400','18918155','18917921','9755053','14223024','36294926','20822841','6397074','9790638','18920298','9699783','22914698','10897370','10897375','2301817','10897329','8358520','9755020','10897038','10897047','8569821','8570353','8583717','8576634','8576637','8576638','8576639','8576640','8576628','8576629','8567325','8566937','8566945','8566339','8566341','8566986','8566214','8566217','8566271','8566281','8574266','8572475','8552515','8563026','8556541','8557204','8557184','8557188','8570766','8562986','8561273','8560185','8563665','8558213','8561568','8564014','8563844','8564038','8564040','8564042','8559381','8549534','8578079','8546154','8546093','8547848','8547862','8544504','19795356','8558591','8541397','8537147','8536884','8539642','8550036','8549328','4370120','28743317','36205287','8537437','22592776','22440233','36225441','18918454','18915326','8576298','36252693','18914411','8538041','10897072','31546705','13854537','7866837','14296040','8007170','14294870','14295850','14300560','14771654','14738472','13939268','13939241','21173895','13744968','14122622','12964353','13405828','13368148','33047068','30341383','13695933','13692219','13398263','8176926','22663670','22810749','36131774','13030140','8311165','27960000','27959776','36144479','11635539','9108807','5499161','27973621','11643125','27960547','11254498','11254516','11254458','11254460','12686062','12851402','12874581','11972295','9056390','36119676','11905452','14429644','33729571','21708222','14345454','14346937','14346941','13085086','12932734','8356987','5270366','12446681','12333474','12030151','5294150','5296795','13627233','5296596','10125871','27624688','22802772','17665333','5286195','17669645','8338564','10870636','17670965','10092020','5270932','36286662','7221312','2471038','3882849','10541191','14159660','29656195','35378820','12908100','22456028','15379742','14841553','14841566','14841563','12605066','12655122','13180560','12851519','36143526','36137742','14346833','14608904','14301250','11045940','11061069','11091007','11490722','11490900','10672392','10672412','10914249','10991616','11175487','14237983','14823754','14463225','14463230','14463224','11521882','11521861','14282633','11021313','12881538','11151446','11490801','13180372','12881500','13888673','13888461','13888462','12851068','13891595','13887775','13888153','13887960','13888016','13893470','13892847','14628514','13889899','13891680','13892942','13890077','13887753','13888024','14301254','13888278','26322685','26323284','26346287','13887066','11512953','13892279','29937582','23422628','9154762','5309926','2301577','8179796','5668375','5656213','5312853','7327110','4130994','36318002','4272163','2301729','6396082','8310659','19050424','13042472','12968675','17378386','18908121','16824116','15708263','15328523','31892836','13854542','13854511','13854515','13435730','13435322','13435315','13435742','9201264','10150706','12072764','10897077','10240903','21162106','9820059','9790676','9790689','9753806','10897083','5312463','4131082','2301828','9790675','9699786','9699992','9699796','9699779','9154670','10897383','10897353','10897321','10897040','10897057','10897043','14974805','8569820','8569865','8570424','8576719','8575466','8566931','8566939','8566189','8566195','8565913','8566287','8564193','8581652','8574237','8572650','8562506','8557206','8561388','8561023','8563772','8562539','8564032','8563736','8580876','8580851','8581371','8578138','8549045','8546143','8546113','8545823','8548646','4878338','8549327','27533932','10207806','8541018','17378382','18919694','8563005','21348393','18918446','18918347','9792987','4335227','8019895','8139794','7850227','8178384','14294866','14294871','14295965','14771665','14771683','13726374','14123332','12963566','12963572','13011561','12937299','28436893','13692264','13001174','13979828','11783484','13858236','13368762','3589035','3631545','3605535','3606028','3614259','36259102','5498241','5497622','31969192','30420252','34095876','36117983','28441744','27960429','24926763','24843345','27218140','14003444','15018069','15336168','11635627','9277367','26373153','5498893','11254546','11254554','11254570','12686097','12626224','12874175','12875729','36110705','36222156','15067190','11254600','5496965','14245638','22758295','17671515','13201985','36313721','13231546','13085059','12929131','7815516','5270576','5296435','12444153','12448120','12448124','10793857','27624886','5277390','5296753','26136816','36125473','13888419','13085084','17671517',
 '25181040','36232368','8331976','5384726','7829756','17672278','10091279','12929307','17672707','5265859','27442104','17004576','8387632','8390644','3872184','9139921','14159671','13043630','10679469','13722676','15029241','15428287','24242846','17398088','15018128','14841567','14841557','14841574','12689477','13759266','36124024','36161517','36129184','14608884','14608931','11519510','11521410','11515758','11579872','11579437','11579483','10997621','11045297','11046018','11215431','11215456','11490463','11490828','10914263','10920968','10921122','11046214','11494904','11146018','15626783','12339088','14274570','14274572','8387640','36129457','15023197','13368981','15626683','11521341','14760389','14760401','14713972','14760405','14293220','12384914','12376014','11021378','14760415','17398117','36120046','12376086','35371510','9753980','15065634','17538474','13891040','13887610','13887233','13888141','13888144','13886774','13889166','13889168','26294515','26262156','26276650','26294815','13890113','13889748','13889823','13890701','13890541','13892100','13891672','13891506','13892911','13890448','13884974','26345842','26346825','29947186','29947420','30003897','13889138','36347220','13891637','5311668','5312440','2301580','8217628','5651022','5671026','5654627','5656141','4243110','8306891','2301852','8388179','5850145','8036605','36314802','2301731','36196652','4325418','2301723','6341830','6396960','31790898','19650207','18943450','13854576','13854558','13854566','13854563','13854571','18902700','27511629','13854514','14974745','31914174','9733796','9476685','10231089','10207060','24463834','27190259','20823038','21977552','18918580','18919190','9810914','9810920','9874647','9786918','9753809','4276933','20715462','12870843','5671095','9753802','20823566','15696795','18919729','23859658','2301732','9807368','9753805','14974733','10897367','10897382','12752868','4201647','9755021','18918539','24463491','8569784','8569793','8569671','8569692','8576635','8576727','8576630','8567565','8567566','8566942','8567645','8566210','8566219','8566222','8581648','8574154','8561299','8563784','8561311','8564037','8563703','8583835','8580556','8580965','8578135','8578081','8578082','8578543','8563319','8555936','8546150','8546646','8546118','8546099','8550137','8548660','8540147','4131100','8550027','8549955','8548627','8579278','8565010','27517799','27436385','8536552','8540798','8567240','8566984','8566866','15324712','17379888','19040537','27884457','36261404','22873638','27446390','5309458','8574102','18915143','18918282','7988828','8035481','8177368','3966187','36243436','11780215','14294878','14649826','13939200','22431138','13011553','13368184','12310660','22661947','13692671','13698719','13695981','13368442','8120241','13939246','14078086','5496678','24831856','22670323','26734167','13267663','11091702','11635127','11091342','11091532','10498220','10941017','9077543','10668845','9276227','10910468','10908127','10909282','5498124','11582659','12772698','12875627','12875783','36205434','5297363','24799364','14203077','22525144','14345389','26807178','17671962','21708478','13085031','27442192','12929297','24799787','24690238','14346912','8352664','7086692','7088761','10809231','10811718','10794388','12336083','13080006','5267764','17664791','5267719','27625025','9278947','25121741','11762172','8331053','5994155','5385201','3107886','5431178','5431342','5431905','10091386','5282727','26806770','8215856','2471096','2471003','3882547','4199017','36285096','33005197','12874418','15518093','23544139','24172640','14936151','14813994','14841555','14841571','12608649','13043618','12851346','12339132','36118207','14608889','14536818','11520931','11543644','11543688','11518528','11579731','11045335','11090608','11090907','11214967','11490622','11494853','11488511','11259612','13579780','13061783','14713979','14322853','25540739','10912493','6973013','17538387','12881522','36120045','10988960','10995112','10991479','14674053','15626732','13893920','13888549','13893359','13887181','13886877','13892780','13889730','13891266','13891528','13888434','13886793','26322826','26344970','26344015','36118189','5784338','13893325','26324728','8389466','13893384','5784648','9154953','5313706','5314063','4203859','7959239','2301841','5532326','5654629','5669279','5656735','7823333','8354579','4199265','6382170','4243469','2301680','2301622','4276785','4272183','2301734','6334492','20434436','13854562','13854574','13854549','13522063','13559290','18905989','33258180','27510936','27512505','27874375','27634371','13854548','28256299','9733790','9733791','10897062','10241487','24346317','18915135','21975010','21977862','20538273','20545942','18918591','18919197','9727538','9201241','36262433','11904647','18005867','9474723','20847850','9686893','13854532','9874654','9755875','9699790','9699814','27638832','13560090','10897044','10897050','18916183','10284776','18915668','10896987','8569809','8569835','8570045','8569748','8569598','8570316','8570356','8583708','8576633','8576632','8567320','8566934','8566206','8566216','8581644','8581653','8574238','8574264','8568505','8568534','8568537','8568913','8572535','8572544','8572465','8553719','8562532','8557198','8555121','8560186','8563604','8558358','8562763','8563724','8583828','8580548','8581172','8578076','8550916','8550920','8546146','8546100','8545438','8548216','8539300','8550020','36268147','8558193','31369575','28763008','8538785','27638601','5309541','18915670','19047245','18919604','8576902','18917887','8576690','8177667','10945752','10874183','14294868','14294874','14295945','14771561','13726321','14122613','14122614','13406712','13406728','13692265','13394072','3621133','3610371','3612853','3626551','11611342','31623361','32690739','34610868','36103936','36109977','28240495','18187811','27973363','36116081','11635479','11635512','11091344','10501634','11635211','10908423','11254663','11254567','29219547','11254535','12625961','12879637','22067982','11780529','11120182','11514249','11190804','11514505','11905411','5280491','5280086','7799647','36317239','14339682','33729633','22802745','22522622','14240089','14228032','21708857','21707833','14238543','25860187','17671508','13085161','13269082','13085185','5268284','24799251','17664633','13000462','7816444','25200052','24799211','12456055','12451543','10811533','11756258','10808165','5293471','5293681','5297302','5268618','17664692','6129366','22157366','15466001','11760909','11762059','5284683','36174024','35170081','30544039','25657653','24799722','36155311','36298753','25201336','11792177','5743734','3883243','3873102','36230884','36164127','9139985','9139982','9274991','13760111','12908120','13692545','12687236','13158086','13043620','13043625','12853152','14768807','14608890','14608888','14608929','14346829','14608898','11579521','11579605','11830792','11491225','10672401','10932349','11046165','11494732','11495279','11147737','11151273','13158158','14274567','14274577','14463223','15033379','14629542','9139861','12853065','36143495','14760394','14760399','10912482','14293197','14463228','11151623','11534405','10995011','14760417','35370682','14760422','11635139','15427439','14608893','13888891','13887581','13893316','13893712','13892850','13886674','13887005','13889283','13889294','13888844','26234111','26227295','26279858','13892486','13891821','13890502','13890804','13892921','13892657','26281673','26340219','13887953','26234214','5219356','5313309','7800290','8217174','5655314','5650026','5670935','8185643','8454653','6396443','4131188','9154673','36268009','36256114','36246268','4202484','2301744','6397153','31790855','31888355','29816554','20000781','13854578','13560569','12872763','17378477','18005673','18905819','18005744','18006034','14737338','13435272','13435308','13435355','12752303','10155098','9201224','9201213','9201270','10239691','23015444','24347197','22592979','22593218','20732216','18918633','9820155','9874651','9790688','9790701','9755022','9753796','9753800','15201570','10285214','23916702','20825745','11787981','16626598','20847681','5312027','12778765','10897380','12851428','8313924','8569841','8570036','8576636','8566191','8566982','8579673','8574648','8574651','8574234','8574235','8574236','8568527','8572584','8572469','8574621','8574622','8564196','8552634','8552784','8555122','8561172','8563624','8563636','8558182','8561390','8583772','8583832','8580552','8578137','8578190','8578080','8563322','8546156','8546094','8545441','8545696','8545707','8547912','8548430','8544713','8544428','8540151','8539790','8549980','8575993','8539644','8548238','33988077','27887152','26213930','8540828','14038495','14038502','8538239','8540809','2301827','8562483','8566966','18919751','15274594','15274599','15274610','8537407','36224229','4203942','8545703','18918943','30391323','36236200','10523465','14294879','14296053','14771562','14771578','14767071','13939298','14738469','14738487','14021511','19041436','20411724','13776367','13692676','22156767','13013357','8036923','20721220','13695965','13726714','3609114','3627615','36238479','5498052','5497731','5499662','28808724','36123938','27970353','24926838','23622185','25797067','12797469','10266002','10501690','10941056','11091540','9137554','13267998','10908536','10498178','11254653','11254664','36123512','36105113','12482553','12624881','12625574','12874118','12874811','24465130','11193265','11972263','5498482','11932434','11961097','11962686','15593550','5282236','6141789','36118848','27043278','14347038','25421403','17670560','13085130','13085105','13085174','27625082','17665308','12929241','22165824','7051735','17665636','12449472','12446691','10822942','10793564','12331839','10808085','5295460','13011715','13241111','13238436','5267937','12066310','12448125','23740325','17672755','17669903','13517431','27565678','11792015','11792228','5284147','23280781','5299279','36117163','35170845','36173842','19475475','30544009','5415782','5400311','36104385','36173951','22803490',
 '7715158','7715007','14608928','9140012','31074266','12874381','12874451','12874498','20058292','15518097','23424239','25534445','17198649','15018142','22140775','12599929','12481094','12691699','12386886','13180365','13043733','12874207','36143491','14608891','14430955','14608905','14608903','11519746','11521195','11632554','11830789','11060540','11495440','11145322','11151028','11175452','6995071','14428807','11048424','14760395','14649992','14649995','14713967','14760409','14760410','14760437','36189247','14391769','11048455','11091070','9736184','11534383','12874161','11521965','13559318','14760420','11150024','13888658','22906633','13887119','13888160','13893898','36250132','36241307','12339025','13888821','13886808','13889143','11048471','26234047','26227288','13892480','13890098','13892138','13892140','13893130','13892713','13892549','13890681','13890348','13887760','26281809','13888437','26299409','26346075','26346909','14936220','13887165','26269179','13888343','13894022','14035089','36189225','5313573','4277585','7812450','5668175','2301799','8434597','8308847','2301719','2301678','2301616','4272175','4199167','4243192','2301654','6395906','6343185','6396679','31888328','30218917','18968296','17378432','16583819','18005782','18005829','17054435','15343529','15343541','27636925','13435372','13435736','15202719','10161569','9733792','9201250','18916749','27149932','20781118','20545434','18918101','18917896','18917917','18911714','9790711','9790635','9790659','9790628','9753817','18916744','18919736','21160490','4272083','9474725','19651509','14940106','9790640','9733811','9733808','8209222','18915832','9790637','10897066','14974758','19300086','12777876','8215868','8569818','8569680','8570349','8575455','8566197','8566273','8564488','8565097','8579769','8579461','8568523','8563255','8563262','8557203','8564122','8571219','8571227','8571241','8561386','8561018','8560195','8560092','8560198','8557719','8562261','8561395','8562958','8583813','8583824','8580558','8580560','8580563','8581169','8580487','8577515','31892830','22503263','8556226','8551691','8546333','8546110','8546114','8546115','8546102','8546103','8550084','8545753','8545452','8545695','8545705','8547849','8538951','8558557','8579277','33982575','28706276','27503618','27462200','8536989','29702621','27883265','27884221','8538395','8544735','8539538','8546141','8550672','18919526','18916047','36190291','24727978','10323392','8537686','10239674','21154568','8536545','10241481','8563135','7989470','4335287','8139434','8008536','11692683','10944350','10944960','14294875','14294880','18515669','14005346','14078089','14005177','13726333','13700158','13012719','13757230','13406723','13698826','13695944','13695967','13697357','8124213','8019243','3617561','3627285','5498414','30423806','36119951','36108034','28807184','24670861','27963531','36129076','11540254','11091526','9108513','9274878','36222099','10909103','10910156','10908906','9140549','22669883','11583157','11254632','11254524','12447859','11931437','12850867','36226528','36119373','36127178','36226542','11905571','5280562','36195671','36298744','29429433','13517514','14349410','14345392','26806894','25241597','13085150','13085158','13085074','13085000','36125551','17669908','24799696','7051048','5270428','12453080','12061829','10811913','10793490','12376208','10654293','12929571','5278116','5269284','8036608','6242336','5295776','5297347','5267817','5268361','6129407','5269356','17665338','15581960','11791326','5282756','5270249','25210142','5270929','35843378','36171525','14054753','13269145','8338680','5372869','5421333','5385972','5386393','7360499','2471147','9139984','34126758','35369562','13376259','13404665','13720249','12853053','12874484','15020630','15428272','15427454','15428975','15518101','15518518','15379723','15380655','24242759','17538419','18432071','14936153','14936214','14936143','14936148','13530416','13158163','13158174','12423496','12336212','14627654','11545685','11518893','11045390','10914260','10626065','11151198','11151521','13061840','36120022','13619082','13579783','13579831','11521903','14713988','14713970','14713983','14713984','13529730','12908108','11021166','11020545','11151566','12874132','35378152','3882870','12407663','11090970','14275232','12908034','11610384','13889196','13889012','13672866','13893990','13893388','13893418','36247731','13888525','13888374','13888589','13886824','13887074','13889360','13889381','13889153','26280200','14760432','13890513','13889443','13891101','13890698','13892773','13892628','13892123','13892566','13892568','13890218','13890226','13887525','13887750','10910418','13888259','13886961','26298706','26282123','13892805','13888858','36130576','8389115','13892735','9154535','7814522','4370956','5656055','5657277','5658196','2301836','8205671','8209847','4277165','7796877','5850445','8535079','8037276','4368951','6385032','4271899','4272172','4205252','4268710','2301727','6396004','2301740','19873815','22020480','22159401','18005652','18006005','15708267','33393155','14737334','35489806','10161732','9733797','9733804','12021642','10240910','22888564','18914335','21219860','20773008','21975154','9810918','9810923','9811279','9811328','9840975','9790712','9873458','9879652','9874656','9790641','9790643','9790652','9753820','9753812','9753815','18916676','4201742','9079725','23869046','36267906','9201232','4198778','11483362','16626654','22160675','8357817','10315195','16626600','18919077','9790657','9755874','9755877','18919707','2301696','36280985','8569659','8583713','8583739','8583334','8566929','8566745','8565907','8580282','8579632','8579357','8574655','8574656','8574261','8574262','8574263','8568494','8568529','8553446','8554007','8553475','8552657','8562508','8552643','8563116','8561267','8563606','8563660','8558359','8561847','8562951','8563697','8559380','8583748','8583777','8583825','8583831','8580551','8577514','8578077','8578102','8545437','8545701','8545015','8548224','8540519','8561834','8558592','8541132','8550025','8547654','8579276','23110074','27632209','27633151','8536778','2301818','22592445','8566870','18919703','18919763','18918342','18914326','8581632','8584530','8553402','8539542','27468158','28767883','18917180','36347225','8007752','36280025','36280038','11734796','10944424','14296047','14294884','14771656','13776369',
 '13747284','14123354','13695962','13397426','25428254','8019419','3634797','6021090','5499818','5497717','5494696','36111827','27960311','27970629','21658296','21491000','24472145','36129122','36178721','10952296','11642438','11091521','9140395','9052820','9055864','9052842','9260825','9056403','10953853','36118002','10910232','10908330','5497917','11583551','11254648','11254505','11254670','11254474','16326332','12694327','12627855','14739796','11905716','11902175','12874721','11514609','14489671','11148246','14489672','11769846','11514314','11126986','11905473','11905542','36311288','7770193','36125542','36130093','14345462','14346944','14228648','27441115','27624656','24799923','22150757','21945300','14340528','8357275','7788219','7813989','12446672','7789054','12377629','6162181','5296746','36125523','32815975','17672271','5269734','6129626','5294096','13085194','17670923','5297272','27625115','10125885','11791057','5277978','36117170','36132464','22598712','22803653','5420625','22803354','13084987','8387979','2471050','11040603','9139983','9140037','10541090','10544327','9754165','8899118','14621963','33641973','29667528','35855030','12874129','12908054','15518521','17198823','17398076','15018132','14813983','14936158','14813985','13672924','12688201','12331594','13180394','13043709','12407979','12423144','36118193','14762621','14311928','14608915','14608907','14608901','14608899','11543731','11546787','11517181','11517353','11518759','11640413','11061986','11090669','11091056','11090300','10932647','10932822','11046195','11147698','11151235','6973422','7708716','11522064','14786821','31636162','14713974','14276527','12376016','11021281','12686342','14760419','13888690','12337744','36164192','14301256','13893331','13893872','13893916','13893977','13893284','13892843','13886627','13886832','13889066','14760426','13890029','13889474','13890801','13892933','13892740','13887915','13888100','13892273','26346573','26346343','26340662','13889644','13888050','26298879','11145250','14349127','5311615','5309353','5313011','5313162','5297479','5285046','8456039','5346555','4339860','4339729','5655576','5532396','5666894','5666028','2301834','8313222','2301798','5671172','8124747','8037931','36236791','8357113','8933712','4272124','4272187','4276257','4243053','4131002','18905977','15701712','15708275','34375866','31892838','28075770','13435745','13435364','8981723','9201235','9201205','9733803','9733805','11895142','10229784','23015915','24344800','24120027','22595856','22592398','18914276','18915848','18917347','27313114','21218841','20779192','20789335','20818695','21161404','18919018','18918585','9820113','9810921','9873587','9790665','9755029','9755032','9753799','18916254','6397285','18919055','36115624','36151207','18916433','36138698','8306233','36196614','9733810','9755023','9755878','9699810','9699788','8353435','12874180','8569682','8570052','8570567','8584001','8577100','8565900','8565905','8565909','8566658','8584077','8584323','8584329','8576007','8574642','8574151','8574100','8574259','8568532','8568485','8567826','8568916','8568918','8575067','8553090','8553358','8562530','8552649','8557207','8564281','8564131','8564140','8555154','8562558','8560997','8561020','8560409','8563654','8562174','8582085','8583780','8583793','8583801','8583803','8583809','8583821','8581163','8580484','8580486','8577508','8578119','8579460','8556126','8555939','8556141','8550917','8546474','8546105','8546106','8546112','8546116','8546101','8545426','8545439','8545440','8545697','8547852','8540157','8540402','8541429','8549956','8547628','33987534','36205306','28766474','27517197','27445704','8544734','8539087','8538636','8564248','15324768','8552159','8545381','19651754','18917905','8537825','8580166','8539275','36283493','36200743','8536661','18005509','11519952','11518835','2645269','8008197','14295969','14295948','14771553','13939289','13939191','14738482','13939315','13939257','14005221','14078143','31878737','28436899','30180293','36165933','3634803','3635896','22810634','5582970','32688422','32752970','27972962','19555856','20392213','23485703','24191813','25223447','27456938','27962591','36129079','24831683','22668235','27196171','12800160','10952678','11091530','11091688','10916028','9276388','27292284','36231768','10910412','36243710','11254599','11254603','11254614','11254510','11254579','36104809','12686347','12626278','12628523','11905655','11905736','12874454','12874638','12874933','12875140','12875593','12875825','11827977','11828166','11148274','15697363','11905523','11905597','11905632','11900320','11900326','11781936','10495382','36277466','36128105','5279869','5295182','5297395','5299027','25121677','24799580','34776166','22592679','26780185','26806726','17671958','21707994','21708102','14346923','25234197','17670797','13085848','13085017','13085020','13085067','27441035','17669779','13001878','27624912','12451572','12444073','10811789','10125876','10125898','27625075','6161198','6242394','13127074','13011749','5268300','5268534','5267369','23280218','6129627','5269657','7796311','5284724','23280806','36144736','36173856','14054876','11174662','10103569','5994838','5441176','25201707','25178984','36145423','5430852','5386132','10090654','17671953','24799973','7814124','36284864','36117156','23280285','5753806','5743047','7196182','2783007','7687684','7714723','2471020','2470945','3882948','14460014','3872241','9140011','9139905','9053959','14609432','26751256','34127670','34171235','13716943','12881506','12908107','12908126','15518527','15379698','15380659','15380666','19462742','14936175','14936155','14936217','13619639','12605279','12688973','12687657','12853167','36118099','14311903','14628516','14608906','14608917','14608908','11568944','11518591','11518717','11518837','11609955','11655782','11060073','11058405','11091115','11175400','11174973','11175496','25361237','15379711','36120025','13579567','14463226','11516885','35379833','29224110','14672978','36114284','14299905','14322860','29672589','29226628','35376840','14301271','11151890','12339681','13888918','13891578','13887121','13887290','13887299','13893174','13893214','13893407','13893846','13893097','13892866','13892875','13894013','13888815','13887307','13889304','13889036','26234120','13890181','13890016','13889426','13889493','13891062','13892092','13892725','13890878','13887350','13888175','13888081','12851098','26298016','26295695','26344914','13893200','28629345','13889667','13368990','15068626','5754820','14349129','9154758','5309149','5299347','8179354','4326462','5650496','5656801','5666816','5666879','5560809','2301833','7817714','8428432','8514700','6398650','2301649','2301604','4277313','4243445','4201661','4243198','2301743','7765458','30391293','31888348','20277101','19383591','13004145','17054483','15363560','28615557','14737293','13435750','10135932','10163075','8711056','10151553','9733799','9201244','9476720','8680456','12095226','10897260','11482889','11483203','10234595','9965100','23011415','18915599','20846838','21978696','18919932','9879031','9820060','9810941','9874655','9874657','9880203','9790697','9753823','9753811','9755071','14225962','33240141','14668554','36158093','16626680','14491581','10215038','9686902','16626614','22922330','14491151','9820076','9782549','9733812','18919149','18916238','8569369','8570415','8576826','8576901','8580165','8584327','8576064','8576066','8574350','8574260','8568503','8568524','8568530','8568531','8572600','8552669','8552518','8552153','8557265','8557189','8557196','8557201','8561229','8561295','8560192','8558194','8561559','8561569','8562543','8561392','8561968','8559036','8559379','8583784','8583787','8583798','8583810','8583816','8583820','8580483','8580485','8577513','8581238','8579156','8556080','8556136','8550797','8551139','8546120','8544991','8545433','8545853','8545698','8545711','8547913','8547855','8547856','8547858','8544717','8548217','8540635','8536701','8549954','8547621','31392422','27419458','8541109','8538431','8538613','8558220','8572298','8564249','15274602','8576288','18918804','18918939','8576900','8539164','8567215','27914422','30391421','11519856','8122606','8016276','36280042','36243378','11782813','11781036','10827178','10523463','14300577','14294882','14295954','13939284','18944002','13758244','12963581','12328370','32177271','31875676','31888504','13692182','27594940','33429552','32515771','10828025','3596290','3591244','24990012','11611169','5496955','5497032','36253400','29864350','36115351','27959875','27456375','25279959','36128094','10952930','10953445','10265229','10499089','10941045','9278468','9140842','9069478','27959617','27502632','10909538','5499052','36126190','11582893','11254492','11254493','11599123','11599234','36129112','25798562','11254677','13267616','27964360','36131246','12422764','12686178','12686235','12686260','12627796','14739789','11905681','14578394','12875032','12875082','12875538','11828168','12850943','11148400','11741950','11961140','11149085','10495612','27969482','5282153','5283148','6140591','6140822','5280076','5299416','36169771','24799420','14345435','33729587','22802763','14388421','17670555','13268147','13085022','27624976','27625093','27625126','5287546','24800247','24799741','14346920','8356551','7051804','12028515','12446690','10809019','10794183','6162109','23280631','13085818','13011714','13627237','5268004','5268184','5269848','12929277','29997887','12929320','13085024','14814250','22576228','5285229','36144739','36117079','36171623','36273196','14536052','23985065','10102722','30544127','5440884','10869769','8787749','5383896','28628788','36169775','5417336','5431686','5383103','6127669','22494224','17669523','5783101','5750539','8387283','8389838','3872250','2470939','2470944','3882094','36249841','10621574','9140046','9748350','10541254','10236102','26922871','13404667','12876034','15519234','15380667','25395489','24172569','17538380','14936192','25540858','25547815','12688592','12648716','12655118',
 '12480473','13043737','13043738','12424593','12339385','14604964','14628520','14432788','14346847','14608902','14608913','14608912','14608910','11517448','11534494','11091128','11494801','11495000','11145021','11164890','11151452','6982626','14275213','15023198','15023203','15023212','13368992','12853028','14713986','14713987','14760435','14322855','14275220','12376025','12604684','11090986','8389684','8389365','11633154','11187672','12851091','13888923','13889223','13891010','13891394','13887118','13888029','13893982','13893416','13893455','13893489','13893519','13893287','13892835','13894017','13886948','10910107','13888512','13887053','13886854','26234240','26272721','26267775','26234127','26276715','24143146','14760433','13892003','13891105','13890725','13892642','13892900','13892561','13892571','13892576','13892591','13890227','13887883','13887914','13889570','26340048','11569577','13888575','3882356','17606071','11145341','36149043','5219294','5310310','4110896','4110914','5308897','5346333','5346427','4339747','4354925','4354961','7812968','5650022','5657213','5651168','5658203','5560672','8210905','2301801','5681746','8454204','8425382','8388711','8356766','8360982','9008872','36330400','36298481','4325191','2301713','2301592','4272111','4202270','4198664','4217378','6390130','18920415','18946400','19051701','12871800','18902587','18005643','31892848','27635166','27637092','14737290','14608933','8610816','9733807','9201204','9476708','9476752','11483302','11483758','10213727','9965191','24405109','22509510','18915127','18915152','18915162','18917342','27150125','26964465','20771218','20729630','21157156','21155507','9820063','9820067','9820122','9810926','9820160','9874653','9879653','9790644','9807374','33470337','18916437','9474726','31893080','9474739','10320855','10241471','4355001','9474709','9699805','9699797','9700033','2301550','12870492','4243089','8574388','8569577','8570335','8576899','8575451','8575462','8567205','8566749','8566972','8566973','8566974','8566318','8567263','8565895','8576407','8575641','8581641','8573621','8568496','8572583','8575983','8575057','8562529','8562535','8552627','8552156','8563138','8564132','8564005','8555136','8561387','8561013','8563613','8563644','8563651','8558197','8583783','8583811','8580559','8580875','8581249','8581251','8578065','4326275','8579457','2301882','8548970','8546472','8546647','8546107','8546109','8545442','8545175','8544993','8544995','8548365','8547860','8544725','8548866','8548874','8544745','8541150','8538904','8538020','8537108','8549973','8549902','8548631','8579271','8575992','9699972','33988756','27622010','27630371','27468380','27445561','29194829','8544741','8558200','18919724','18919748','10285100','8563004','8550843','18918351','8576282','8567207','8567208','18916848','21155036','34026002','8544611','14083910','8017197','8009147','14300601','14295953','18097820','14771657','13939281','13939198','14652375','14005236','13939234','13758224','13857895','12963605','12937277','23677742','24792074','13692719','8019327','3634831','5499167','5497235','5491634','36249725','36111983','36114126','36102913','21491471','18188002','23507921','36157785','36142588','24839634','11788103','10501621','10941036','11635650','11091321','10669079','9052679','36292549','19582909','19547039','36129868','10909192','11254623','11254477','11254494','11254662','11254667','36123662','12448251','12850898','12686288','12686875','12628618','12693953','11906393','11931431','11931584','11932135','12875855','11515221','11972484','11972710','11148301','11148421','11679153','36120366','11900929','11828363','36126942','36123940','5280591','5279995','5297407','5298916','6139915','6140098','25117130','25201831','24800177','36118843','14345382','33730131','22522190','14347023','21708330','13085058','13084997','27441936','27625139','5297458','5297501','12931226','14339669','14347011','7754901','5270327','7768379','12456047','12451541','12444139','12444141','12448123','12374826','10656512','22525484','8482238','5294862','29997304','5270121','5268775','12066337','5279654','5269186','17669591','24727675','5286663','5285125','36173969','36117202','35849186','19474833','13268659','25656895','28948330','5430433','5430766','10090824','25210614','15582219','5269585','6242133','36282313','5743286','5743529','5781984','5743675','7714858','8216393','3882650','3881596','25547470','9140001','9745983','10544006','29667765','35378662','33134604','12874483','22456434','15427441','15518110','15518529','17606087','18527936','14936162','14936189','13559354','13550291','12608695','12688680','12655120','12419452','13087779','13043748','12853126','14608926','14608930','14667887','14311919','14762634','14608909','11060246','11061512','11062748','11494648','11495138','11150802','11151313','11175533','11175161','7131547','7523039','13061830','13061851','13061855','9780900','13576752','13368987','14428815','14650010','14713985','14322861','14428826','14391796','12376036','12604714','12376019','35378104','14936171','24268749','12874573','11521131','35370757','13158096','13061798','11534340','15626691','36114310','13891572','13577689','13887258','13887304','13887851','14349145','13894002','13893160','13893197','13893485','13893909','13886904','13887018','13889370','26263199','24737763','24752976','26227094','13891815','13891845','13890111','13890527','13892767','13892254','13893126','13892720','13890670','26346524','26345551','26346045','26346111','26340501','36120029','3883278','13892262','15014968','36190768','5312429','5312930','5312989','5301597','5285980','3306943','5313717','4014441','4277397','5656368','7795226','5655990','5650409','5656512','4272364','8207877','8210411','8211587','2301855','8355165','8458437','8489017','8513841','8124289','4130934','9008868','9008882','9154652','9154656','36267921','36195509','4353280','4268772','2301669','6395636','31674129','22159022','18920235','13524228','13521406','13003953','13001654','16283485','16314756','18905128','18005613','18905997','17054448','17054493','33308579','27587212','13342349','14608941','9201268','12753699','10153177','10140368','9476693','22916491','23108675','22596207','18915166','18915676','18917396','21218424','20782322','21162581','21975554','18917196','18918552','18919170','9820065','9810928','9810934','9874659','9874660','9943023','9790649','9790683','9790685','9753824','9476733','12753577','19626742','32157648','5312205','9755822','6351017','36240983','9686910','9474735','16626667','9755879','14608945','18919091','36231003','9699986','9699994','7825491','12871438','14940854','8575460','8566967','8566309','8566316','8567261','8576403','8576340','8564798','8580284','8579697','8579698','8579367','8581647','8576063','8568497','8568118','8568917','8568676','8568862','8572582','8576488','8564444','8552917','8553717','8553718','8562600','8552640','8563147','8557200','8564007','8573112','8571874','8562556','8561177','8560854','8563793','8563639','8558209','8557919','8561531','8561394','8583774','8580862','8577469','8578056','8578132','8578073','8578074','8578542','8563325','8563338','8556212','8556217','8556099','8556054','8555940','8555948','8546104','8546108','8546117','8550108','8545006','8545403','8548648','8548665','8548215','27479973','8541355','8541417','8537112','8537164','8536715','8550040','8549329','8549796','8547629','8547631','8575991','8575989','30473787','34025920','33982728','34024517','34028768','28743072','27883857','8539234','8538892','10207339','8540187','15324758','11167474','5650989','36260378','18914471','8353094','8581633','8567212','8584529','24335900','8567209','4131108','36280041','11783009','8695949','14281844','14771661','18515633','18515776','13939247','19955101','19050179','20549785','13776094','13776182','13858025','13405830','32181402','31867660','28436922','13758605','13692668','13692660','8695219','11782726','14296051','3611604','5496754','5494663','33999477','27959910','20725957','20525323','19179863','25799243','36129121','12898497','10265621','10501618','10885614','10941048','27959380','27973445','5497135','10908845','11599060','11254478','11254483','36128112','11254529','11254556','36103665','15737432','12575689','11931557','12874542','12874675','12874764','12874907','12875115','12875169','12875221','12879255','11148351','11515524','11125252','11090901','36021695','35041590','14757627','11254629','11254610','11962296','11937520','10496473','5280004','36310497','6157651','5299017','6129068','27624140','13517506','32818227','17671955','21708267','14346933','17670836','13085157','13085071','13085007','27441652','27441810','27442042','5287130','13052545','13039174','12956080','8355949','8352728','7092335','7459476','12444149','10822986','10823154','10808957','10812002','10812574','10655703','10655806','8035960','6242194','6242285','5294383','5297212','5297228','13011762','12066332','12066233','13085065','29996316','8333733','17672441','12449510','11791801','11792077','5293776','5278294','5277946','17669650','35171743','36171588','22598417','30544086','5423481','5293990','17671501','5431050','5417091','5397458','5413840','12571370','12453079','22290005','5753954','5743599','8389276','7548329','2470966','3882232','36249088','9140003','9053657','9755230','14159693','12874560',
 
 '12874568','15519244','15428275','15519227','15519230','15519233','15418785','22816416','25534287','24173404','24242538','17047981','17538401','14936165','14936194','14936200','14813992','14936224','5782414','12687833','12655128','13758845','13180409','13158077','13043744','13043652','13043656','13043636','12853168','12423442','36118185','14627651','14608925','14608932','14346831','14197495','14346848','11521375','11568996','11045892','11060633','11090406','11190631','10679348','11059145','11062101','11091101','11494190','11495225','11151674','13085900','11521693','13061817','13619093','13619120','13609103','14823744','13369093','14428816','13061788','13061794','13061809','31899307','36120072','36325225','29718049','14713991','14714003','14650004','14650005','14650007','15519254','14760443','14760449','14760450','14760452','14760457','14322858','14322864','13529636','12376021','14322888','14275230','14713990','36129195','10991429','35371429','14301281','28202497','15380653','11151730','13041968','11600637','5782303','13888721','35378564','13887286','13887932','13888011','13888048','14349158','13893173','13893346','13893840','13892803','13892834','13886679','13886719','13886783','13888479','13888524','13886618','13889305','13889327','13889177','26264272','26293144','13892443','13892020','13890494','13889907','13889925','13889974','13890007','13889398','13889485','13891465','13890550','13892389','13891914','13892916','13887766','13888289','13888294','26339166','26334950','26299813','26345635','26346016','26337930','26346788','13672586','15519262','9748758','15014976','13891819','13892300','4110917','5311494','6397070','4370531','5655210','5655795','5655920','5535213','2301851','9154620','8533745','7957854','4205218','4202345','4198707','4203349','6344117','6384656','31387333','30390796','20631759','18945121','18945862','16291322','18005631','18005692','18254239','15336531','15339343','15343528','27511845','27635627','28026973','28215954','14039450','13435287','13435297','36132652','10139042','10163491','9476695','9476722','10207548','9965193','24664522','22877967','18917344','18917352','21976037','18918090','18918216','18918540','9880168','9820075','9810933','9810935','9810937','9874661','9790631','9790671','9790684','9790710','9755039','9755056','11483165','5873521','18907970','8358146','32842575','6379375','15339335','30940038','24664100','4324959','4131060','20823225','13436663','10239686','14492609','18916943','9755880','9474741','12835394','9699808','6346261','36287640','8569794','8569697','8569698','8569372','8569512','8570416','8576818','8576897','8567477','8575461','8566747','8566969','8566791','8566975','8566976','8566980','8566265','8575633','8565096','8580347','8581645','8582267','8575950','8576065','8576067','8574641','8573771','8568495','8568500','8572160','8553275','8562538','8552157','8563016','8563017','8563156','8557197','8564285','8562786','8561000','8561164','8560196','8560129','8563781','8563641','8561369','8562044','8564015','8563845','8559317','8580557','8580871','8580872','8580481','8577505','8577506','8577509','8577510','8577512','8577377','8578125','8578059','8578133','8578060','8578136','8578067','8578169','8579458','8556100','8555945','8548971','8546119','8545435','8545448','8545800','8545801','8548259','8548469','8548499','8544720','8548225','8540352','8558587','8538080','8536669','8549958','8550038','8550267','8547669','8547623','8547627','8556134','8579275','8579273','8579269','8579272','9733823','9699970','11483246','36190295','28767502','27627018','29184956','27612152','8541116','8536578','8544726','8540759','8540526','8540554','4370508','9733819','8564251','2301588','8579812','32164320','20709224','4131130','8570126','8570103','14608951','8555336','28755759','11518010','36215861','11729214','11737881','10874455','14295950','14295951','13939277','13939293','13776373','13758247','13700165','13013368','13013378','13368114','11964875','22810914','18098494','8121867','36215875','3606949','14771570','21930658','13939352','5499767','5496644','5497769','36277470','36235319','36127982','20390643','36253315','10913928','10498181','11091697','10501652','10498195','10941055','11641872','11641932','11635606','11632584','9279999','10264958','36122645','11582726','11599068','11254585','11254475','11254481','11254638','11254495','11254537','11254540','12422677','12754466','12579245','12692942','12628495','12628575','12628688','12628877','12629729','15736457','12874847','12875335','12875374','12879607','11973524','11514415','11973258','36229618','16512436','11900617','11900659','11828662','11900477','11828806','11828903','11900613','5282358','5282254','5280513','5282625','25116860','27565723','24799518','29973101','29973871','14345453','14346988','23739486','14385956','13671056','13617372','27112050','26981972','17672752','21707983','13094093','27624899','17669951','24799657','24800253','22353030','22253003','14346915','8352866','7792873','7051681','14227461','21708061','12454593','12449476','12444138','12444151','12448122','12446682','10823206','10808771','12378606','12444069','5285895','5286073','27565743','5269687','5268132','12066333','14245728','29997525','17664699','24799907','7789941','36191060','16041259','20657932','15582945','11791867','5286513','5283952','5270423','17671895','5299563','5299773','5293511','6127605','36171543','36171546','36171580','36171595','36173864','14536000','11174012','30543910','25655856','30543861','8330673','8338636','5950746','23280075','5417352','6126781','12571374','12578859','23280269','23280226','23739111','8389533','8390007','2782906','2470973','2470997','2471007','8389173','8390618','3882630','2470955','2470961','36190357','9140016','9753557','14159830','11091104','35374912','31501010','36114623','33134566','13559390','13530814','13559320','13672822','13759924','13722777','13559337','15016458','15519250','15428974','15518534','15518539','15519222','15428128','24642876','17398084','17538464','14939483','12385469','12387075','12329938','13180319','13158110','13043739','13043660','13043672','13043645','12414503','12378815','36123604','36149044','14814015','14349144','14792228','14608923','11581291','11830817','11090624','11175540','11058508','11059502','11495189','11145401','11151082','11151530','11163635','11175469','11149321','15626694','36168944','13061813','13061824','13061834','13061838','13061847','13061849','13061854','8215261','8387045','13576787','14428808','14428809','14428824','14643628','13061805','13061808','35379617','12631173','11634814','14713992','14760463','14760466','14428825','14760451','14322875','14322909','14431471','12376034','12376047','12604608','12376022','12376092','36120043','36120036','14156352','14156359','14536890','11967864','8390029','13577711','13577700','13577673','13577677','12803567','5743826','13888676','12848764','13891599','13887586','13887126','14349149','14349151','13893535','13894010','13887089','13886887','13888423','13887205','13887049','13889359','13889366','13889109','13889385','26234035','26226813','13889400','13889434','13889470','13891508','13887523','13887746','13888070','26346704','26340582','31263621','13893499','34126327','8390542','8120964','15014969','13890220','13891507','13886928','15519272','14349134','5309394','5309521','5312113','5312473','5309994','5312650','5297285','5295543','2301630','7789652','7812325','7815995','4370715','5668461','5656315','5669422','2301835','5674225','5850532','8311027','9008883','9154650','9154659','9154681','36287693','36225775','36221710','4268728','4205066','4205098','4131149','4198723','4130923','6381119','2301802','6352049','6396775','30390834','31888363','31790963','19653047','18920315','18920337','19804549','14925796','14668635','17378410','17378415','17378421','18005886','15708264','15336537','34225076','27512702','27639017','14737294','14737397','14737379','13435275','13436640','14608935','14608939','9476705','9476749','9474783','9476740','9476704','9476711','8681689','8683361','10284739','20824234','21162850','18918014','18919248','19047832','9790384','9820064','9820077','9820107','9812243','9790713','9873588','9790694','9790704','9755024','9755026','9785773','5656222','4243165','8205231','18919654','9807372','22440288','9474727','9755832','9755824','23113091','5312662','36314775','19053103','9474740','9686900','9686920','9686932','10285240','9474731','10283376','9700016','9699976','18919542','14974786','14491305','10283557','28215689','8180458','8569673','8569749','8569581','8569593','8570412','8570338','8567204','8566732','8566740','8566748','8566326','8566337','8567264','8566977','8567873','8564476','8580265','8579628','8579630','8580127','8584319','8576006','8576008','8574647','8574650','8574028','8573613','8573618','8568678','8568970','8576200','8573387','8575060','8554156','8553729','8562519','8562536','8551765','8552504','8563249','8563257','8563143','8564125','8564188','8555130','8562982','8562992','8561003','8561015','8561171','8560191','8560419','8563596','8563642','8558206','8558212','8561561','8562262','8561858','8561859','8563688','8563690','8559039','8559382','8549589','8583756','8580553','8581088','8577504','8577511','8578124','8578127','8578128','8578129','8578134','8578189','8578066','8578075','8578665','8579459','8579340','4131143','8556238','8556104','8555935','8550794','8550925','8546473','8546475','8546485','8546628','8546634','8545446','8545864','8545427','8545428','8545848','8545451','8545017','8545795','8547847','8547854','8548218','8548229','8548239','8548869','8541471','8540484','8540580','8558579','8541316','8544314','8537895','8537324','8539880','8536663','8550037','8549970','8550470','8547140','8547624','8547625','8547524','19798774','8579274','8579270','27887214','8574098','28743233','33988797','36108472','33997667','28768369','27623641','27624074','27633076','27467193','27611830','8541043','8541076','8562467','8544729','27612409','9733818','8541184','18920335','15324722','8562582','8552647','8548637','8563019','9733826',
 '18912577','10239681','10232764','16590860','8569168','8570106','9729105','9728900','9733429','8035648','7867478','36215871','11737696','11739639','11739727','11779900','14296033','14296044','14296020','14296054','14297763','14005239','13939249','13939225','13700173','14078147','14079588','13368107','18098436','13758604','13001081','13700172','3601628','3629832','3610775','6898249','14079573','5498686','5498716','5499200','8462079','36226534','36188622','27973273','21491575','20392719','19687374','24925902','27964592','24926140','36136312','36129110','12904600','11144901','10265512','10941028','10941038','11641676','11091514','11091692','9279877','9280649','9056423','36124863','36124881','11599058','36123931','11254530','11254543','16509960','12453785','12499358','12506615','12421708','12421796','12422732','12483292','12449066','12483333','12421075','12421477','12850981','12569219','12686381','12772759','12772959','12625709','12626000','12628823','12629292','16509972','14739791','11931554','14579270','14505020','14489121','12875569','12875692','12875890','11964343','11769947','11932140','11962576','36158997','14817952','11254608','36126968','11962227','11828381','11828656','11828748','11829247','11829458','5286685','5295620','36311282','5282140','7815739','33729607','36118862','22522730','14227388','14244574','17672263','21707844','21708432','14345690','17671533','13085075','17669935','5285709','24799868','17664619','13003111','12929327','36154506','12449504','12451551','10808275','9854127','10136771','10136773','12333564','12375965','5285798','8017725','8485893','5298960','13011744','5268663','17664715','12066312','12066340','22774463','14245699','24799361','36132138','15464069','5284301','23280737','23280761','5278002','5265761','35171405','14535915','19474916','19474933','11173692','10105152','8330080','5385019','10868871','5371407','5441818','5950598','17671863','23280256','5399047','5432950','5434155','32633315','36122457','23738465','5754822','5743749','6960959','2471000','3882916','3872171','3881309','3881836','3882833','8388264','36204876','36195604','9140026','9140019','10236248','29962897','30380698','33095181','13717205','12908043','12874472','20443470','16097682','15029242','15519241','15519221','15519240','22816199','23095902','14936231','15018156','14936166','25391836','12453871','12337226','12330021','13158167','13180312','13158117','13043735','13043650','13044820','12339914','12380550','12380664','12378593','36118103','36118119','36118121','36143529','36129185','14627645','14349166','14814021','14346834','14792226','14349172','14664358','14301270','11521299','11586280','11632777','11634703','11060776','11190413','11494969','11175437','11152104','11152191','36222460','13061820','13061823','13579772','13579719','13061803','23133656','29718498','14760460','14760464','14322865','14322867','14428833','14428843','14428847','14431459','14325657','14322882','12376024','12376028','12384921','11149374','12376076','36222435','22318687','12376062','14431479','12376091','11830788','13665173','14571575','12691702','14156354','14665112','14301286','14823761','8388415','36304175','13888631','12655129','13577681','13887582','13887596','13887148','13887797','14349152','14349157','36250136','13408651','13893861','13893273','13893276','36241246','14349161','13888565','13888607','13887029','13889629','13889375','13889133','26324768','26272860','26272966','24467965','24753057','13656050','36319898','13892490','13891934','13891987','13891869','13889878','13889693','13889815','13890692','13890713','13890747','13887646','13887648','11973200','13886959','26339381','26339445','26339720','26336984','26331434','26298643','10910117','14349188','15519264','13886943','13887844','13886989','13888435','8115241','13689582','14433002','14349135','5309684','5310317','5300810','5313389','5313601','4354867','4277389','6397374','6397517','5657722','5658675','4243113','8176691','7825570','7797336','8429872','8035391','8389427','7798849','8354001','8359022','9154648','36262872','2301608','4131090','31891114','31790871','25999070','25999377','19654335','19684239','22007038','19381038','19050922','12871977','17378405','15336519','15336549','15363568','28161775','14737366','14608972','14668622','14608944','14608964','29165638','9201228','12778847','12838173','12632889','11482452','10240907','10285158','9965201','9994458','9994466','9994663','24514634','24666946','23108186','18915596','18916624','36316759','20780304','20782641','20783426','21163457','21975311','21975415','21753635','18918249','18918551','18918976','18911712','9839732','9820066','9820070','9820099','9812242','9873461','9785871','9786849','18919827','4203274','18920506','18920504','4277298','18915582','9796670','9807375','9474796','11900878','27996234','9782443','9755840','9755881','2301629','9474710','11831413','14665664','9686908','9686903','9686917','9686895','9686894','9686890','9820125','10897573','18919048','9966193','4201671','20521082','9700019','9700018','9699977','9699975','9699981','9700002','9699995','9700006','18916321','6340896','26673593','12776809','8569654','8569839','8569876','8569582','8569597','8569615','8570342','8569064','8570346','8583720','8576898','8566315','8566262','8575651','8564492','8565092','8565006','8564195','8579696','8579366','8582586','8581870','8581811','8581848','8584314','8584316','8584322','8574645','8574646','8574153','8574103','8568724','8568492','8568493','8567901','8575059','8575064','8575065','8564078','8553295','8552650','8552165','8552167','8563015','8563261','8563020','8563123','8563054','8556689','8555400','8554126','8555145','8561178','8560825','8561011','8560188','8560189','8560145','8559952','8563792','8563630','8558656','8558234','8561566','8561842','8562020','8563681','8563692','8559175','8580867','8580873','8577467','8577482','8577490','8577494','8578123','8578058','8578187','8578062','8578063','8578064','8578068','8578072','8578120','8578609','8578610','8578655','8563341','8563242','8556230','8556101','8556112','8555450','8555947','8555950','8548966','8548900','8546492','8546774','8546591','8545808','8545810','8545172','8545429','8545436','8545831','8545796','8547850','8547853','8548253','8550128','8550129','8548545','8548647','8548657','8537680','8539691','8549959','8549965','8549972','8550047','8549975','8549333','8549341','8547655','36197006','27613206','27886984','33982777','27584333','27427831','27445848','27611977','27463538','8540707','8536983','8540831','8540679','28755691','9008046','8581807','8546024','8550853','8580959','18918408','9733827','18917650','18918807','8576283','8574436','8576295','9755823','20524264','10321417','9733828','8680203','8123975','36286098','36308128','36196423','11783172','11781977','10827853','14295959','14295949','14295985','14771563','14602268','14005234','13013370','13013376','32528898','13368125','13368181','12937293','22659795','24991344','13368630','13398472','13395954','8140697','24792098','12311515','3609292','3619042','3604220','3614400','8717248','5496191','36266332','29174217','36123936','32837613','36101845','35868965','28807315','20395990','24003022','24472830','36142532','22668914','10265453','10501655','11635560','11632677','10914285','9279797','36114917','10498223','11254590','11254486','11254490','11254647','11254660','11759762','11599206','11254463','36129109','11254545','11254571','12453887','12575974','12506303','12376625','12421535','12422056','12422280','12336496','11973050','12420916','12506853','12448347','12850928','12850969','12694062','12694076','12686417','12687021','12772808','12693160','12693395','12693496','12628754','12628919','12629351','12629400','12629778','12851090','11931647','11931655','14489664','14489666','12879806','13319135','12879620','11734744','11972323','12851004','11084486','11086110','11938484','11942604','11190892','11973120','11640944','36126631','15593576','15593375','11142270','11962258','11904905','11900484','11828907','11829120','36317236','5296848','5299348','6161247','5296791','5280078','27565759','24799979','24799478','36118847','14429857','14245844','22594564','14347006','26807581','26807210','34618126','36125552','5207833','22154974','14340527','12929591','6958219','7051011','7051838','7088506','12449484','12456051','10823329','10808323','10125894','12379633','12373049','10655450','10666969','10860646','5277717','5287618','5296855','5295072','5295510','5295575','13269259','13672321','13898125','5207741','17665169','12066334','12066336','5296339','5269434','10655164','5270148','8019655','27513581','11762367','5284550','5285149','5285211','6126755','17670609','36154697','10105806','25655984','10868581','10869075','23280242','12060406','6129472','10125880','12571371','7814506','7814746','10125872','10125882','17057093','36273203','5265529','5781750','7208735','3883101','3882754','3881860','3882285','36224349','9140023','9139901','9139841','14159679','14159705','14159727','14159844','14622004','13672371','27934298','29591598','29967347','13404679','15065640','15519251','15519235','15519237','15379709','22816843','22817112','25402135','14814049','14936183','14936229','13719178','12689399','12655123','12419497','12329333','13158178','13184982','13041906','13043747','13044823','13044825','13043676','13043641','12851314','12853145','12339829','12380931','11908357','36118118','36129186','14768813','14814014','14814010','14346836','14349142','14346845','14761842','14346865','14349181','11519049','11632382','11640292','11656386','11061285','11090454','11058733','11148844','11151167','11151380','11163755','11174943','11151829','15626734','15626746','15626712','14650016','9140030','15626698','14238012','13576756','15023207','15023340','14428822','15626687','14624313','11830823','11516962','36120069','36120073','15015030','12376056','36106075','14713989','14708544','14650013','14708658','14760468','14821700','14391654','14322862','14322868','12506311','14428830','14428831','14428839','15519252','15519253','14322889','12376041',
 '12376049','12376053','12337656','15023208','13369169','12378880','15068616','11569032','14301282','12330117','14439278','15056787','13085939','13180359','14156361','14156367','14156369','17538477','36168939','14577505','13577698','11166267','14823762','15626728','15626772','14301264','14650059','15626714','13665164','15519281','36211437','13888932','13889229','13888714','13891340','13891020','13891030','7543970','13887969','14349140','14349146','14349170','13893240','13893242','13893963','13893756','13893972','13893974','13892952','13886697','36250124','13888529','13888583','13886634','13889276','13889337','13889172','13888866','15427450','26227113','26234228','26233855','24751157','13887659','13892318','13892325','13891879','13889889','13890703','13890547','13892174','13892177','13892183','13892056','13892907','13892745','13890832','13890846','13887880','13887902','13887701','14349136','13888236','13888292','26345504','26345513','26346250','26346638','26340456','14349184','13041905','15519259','13887234','29651428','13887808','8115151','14647522','13893087','13890037','14349131','15519274','13886956','5312790','5310646','5295861','5301761','8450480','8458200','8460043','5313494','4006679','4050719','4355008','6397443','5650362','5657628','5656587','2301761','8182907','8183095','9154528','5874414','8534090','6397668','6397707','4276909','9008874','36287584','36238468','36241401','4355072','6382018','4276963','4272121','4272231','4205221','6396984','6347746','5309938','5657436','6396248','30541137','31790955','31249439','18920388','18920214','19805252','18969042','19043013','18920488','19052161','18908060','18908065','17378436','36175228','16593548','15708219','15336568','15343568','15363575','28077321','14668659','14668568','14668575','10136207','10140496','9747737','9476703','9476743','9201261','9476731','12632850','11482978','11483404','10321341','23011676','24346426','24347026','24347255','23971092','18917184','26673200','20828722','20768183','20838142','20788973','21975883','18917541','18919955','18918015','18919172','19048444','9820168','9829635','9873456','9790656','9790667','9790695','9755025','9755031','9755046','9755070','22503397','18506708','9807324','9807352','16626693','4205077','9474746','9755882','9755883','14609015','36126842','14668349','18904283','10285352','11482824','9686899','9686901','9686896','9686933','9830453','9686935','9733466','14668512','9812283','24459828','9700009','9700014','9474742','14491389','9476714','8569787','8569674','8569678','8570559','8569165','8569575','8569579','8569585','8569589','8569490','8570410','8583249','8577133','8575447','8575450','8575453','8566915','8575646','8565003','8565286','8580344','8579687','8579625','8581814','8581817','8584318','8582261','8581263','8574136','8574149','8568491','8567827','8569014','8569162','8572581','8572599','8576000','8576002','8575058','8575066','8574914','8553031','8553704','8553824','8553722','8552626','8552166','8552458','8552460','8552467','8563010','8563163','8556427','8556363','8556545','8557279','8555017','8555131','8554406','8555161','8554720','8555135','8573067','8570769','8560826','8561231','8560010','8560193','8559975','8563609','8563610','8558208','8558226','8557904','8558122','8561564','8561838','8562091','8559042','8559048','8559377','8577636','8577493','8578055','8578130','8578057','8578185','8578061','8578188','8578069','8578070','8578071','8578170','22440014','8390276','8563335','8563344','8562998','8556215','8556106','8556109','8556117','8556121','8550918','8550923','8550933','8549023','8548975','8548895','8545807','8545173','8545186','8544997','8545126','8545404','8545450','8548451','8548483','8548243','8548227','8548872','8548875','8548893','8540427','8541127','8541152','8538775','8537678','8539646','8550121','8549957','8549960','8549976','8549979','8549984','8549330','8549336','8547622','8547626','8575990','8548242','36205317','36206453','34022484','33994383','31392924','27887089','27466260','27469404','27480026','27583612','27517256','8541091','8540968','8544733','8539067','20782909','10207844','10208030','18005495','18918904','15327073','18919758','8552180','8576884','8546037','8546021','21349107','18915294','18917754','18918465','18918806','28767037','34048195','8538922','36303378','14737388','2301603','36205331','27586876','9733831','9733433','8016438','10524350','14296031','14281831','14295999','14296007','14296008','14295981','14771413','14771419','14771439','13939351','13939194','14078094','13939211','13939227','21173940','13776371','13758332','14005259','14021588','12956823','23935133','13001067','36215876','13758258','13939250','3592796','3629472','3629277','6899605','14281832','5496745','5514984','5515284','5492701','36266331','27960248','20450539','24843987','24020976','25612905','36128096','36128108','27529427','11536640','11786223','11146221','10265428','11091694','11641697','11635324','11632529','11091317','9275825','36127986','36129101','9280737','36249728','9262685','30293325','11583336','11254596','11254488','36126244','11254577','16510049','16510070','16510114','16510149','16509938','16509952','12568791','12568829','12500772','12506509','12421881','12422153','12422339','12336159','12483179','12568522','12569254','12686461','12692881','16509977','16510008','16510022','12775935','11901329','11902038','14489667','12880335','13319480','12875405','12875490','11193088','11972523','11148451','11148499','11186137','11769669','11121893','11932302','11608531','14818136','15593124','11254501','11900784','11904759','11905045','11900438','11829920','5283470','5296939','5282516','5283126','7785566','6157500','24799590','29997219','29644292','14345429','33539223','28630142','22591526','22525033','22525214','14339698','26806802','26807160','17672450','17672315','17672332','17671937','17672714','21708409','21708521','14347032','14346951','34416301','25259035','25860879','17671854','17670892','13093037','13085101','36117933','17665245','24799839','24799179','13035465','36147467','12454589','12444146','12444164','12414836','10808902','10788618','10125877','10125890','12372287','12382050','5277831','6161253','5296965','5295374','5293613','25210329','32639511','13011746','13011754','13011760','21708748','12066338','26807193','22582422','15461378','15511576','12867113','11793481','5286169','5286321','5286551','5269000','36282268','19474786','19475515','10105352','10105576','8329754','23280249','5415124','5382990','10088677','10091119','10794677','27043299','5269261','8020164','5740593','5741005','5743137','5751630','7715362','8115153','7117341','7155750','2782978','2470965','8382869','3882907','3882944','4199000','2470946','2470958','36224347','5751923','14159738','14159782','13043701','14621966','36118208','36118210','26751351','33134594','13408675','12881028','15427444','15428120','22816712','14814058','24242652','15018152','14936207','14813997','13529068','13559352','13530196','13697735','12451613','12452337','12691704','12335992','12329504','13582780','13180334','13043670','13043679','13044821','12853172','12850270','12339159','12419978','12337836','11909796','36118142','36118123','14627655','14301265','14665543','14346842','14301258','14301291','14627627','14346858','14346864','14628541','11599811','11570449','11653635','11654464','11632462','11635668','11609456','11609720','11610635','11867012','11865389','11834288','11835135','11061891','11495031','11164686','11165356','11151999','11152039','12339249','15626744','15626778','15626692','15626727','13184466','13087763','15626706','36120013','13665119','13665151','13665161','13665189','13576769','13579854','13609109','13559224','14792267','36120079','14708553','14281176','14322871','14322876','12713577','14428848','12376059','14322877','14322880','14322883','14322897','14275225','12376042','12384915','12384918','14349176','13576784','12376082','12685165','12686346','11149395','11655843','12376067','12376073','12419518','36120044','15068585','11901555','14156363','11831093','14577494','14577516','12378914','13041972','15626710','15626740','14301268','11635972','11090648','14627648','12336088','14823760','14301293','14439261','13888878','13888647','13888655','29667409','13891389','13887260','13887281','13887919','13888007','14346846','14349168','13408654','13893300','13893319','13893170','13893220','13893803','13893843','13893525','13893530','13893759','13892965','13892846','13886932','36241361','14349159','14349160','13888319','13888618','13886638','13889373','26234059','26227167','26234129','26234198','26276192','13891875','13889465','13891169','13890794','13891483','13891534','13892668','13892515','13892685','13892588','13890851','13890195','13890065','13887909','13887514','13887531','12851097','26340274','26340334','26340391','26299099','26346156','14349175','13890640','15029303','13893329','15519270','15519256','29177323','25548418','29183055','8096491','9154753','5312346','5313168','5296007','5308463','4339817','6396781','6397479','8177843','4354706','5655771','5667337','5669499','5670786','9154596','7825121','8429219','8452497','8123925','8302428','6397592','36289491','36196368','2301590','4203205','6348223','6334658','6395881','6396646','6396721','31888397','31888408','31790810','31790390','25935776','19653930','20424153','18970539','18920441','14668627','14668639','13522356','13558135','13000775','17378397','17054534','15336511','15336522','31892876','32475418','28075783','28614296','14668559','14490994','13436651','14668641','14668653','14668580','28228403','10136023','10320828','9474805','9733440','9733441','9476750','9474784','9476735','9474705','9476742','9474782','10206592','10215330','10239698','9963647','9965224','9994464','22889633','23015375','22917923','24459929','24406587','18914272','18914054','18915851','18916069','26673732','21217957','21219528','20781642','20702437','20790329','21973931','21975739','20532979','21610303','18917193','18919848','18919688','18917893','18919152','18919201','9820074','9820095','9873457','9879654',
 '9786822','18919029','18914059','36277267','19631454','22440126','4174537','13559915','36292754','9755043','9782445','9755837','9755884','32226856','9474743','9686924','9686887','9686918','9686921','9686929','9686912','9686911','9686913','9686915','14668548','14668667','18917358','36303360','9686928','15343570','8428814','9755821','14608948','14490538','9700020','36231044','9699997','9700008','9699999','9700010','2301679','8569791','8569797','8569695','8569511','8569561','8570343','8584004','8583904','8576666','8576878','8576880','8576890','8576895','8576896','8567598','8567474','8575456','8566738','8575648','8576406','8575643','8564511','8565085','8565095','8580269','8579364','8579365','8581868','8576004','8575964','8574148','8574101','8574106','8574352','8574258','8573614','8567894','8569158','8568911','8568675','8576250','8575999','8575994','8575794','8575063','8564276','8553654','8553457','8554015','8554004','8553739','8553713','8552474','8563260','8563023','8563141','8556477','8556250','8556146','8556280','8564185','8555128','8555000','8554862','8554458','8555137','8571117','8573137','8571598','8570772','8570784','8562988','8561169','8560144','8559943','8558222','8558223','8558225','8558514','8562065','8559040','8559155','8583747','8583751','8582879','8580549','8580555','8581054','8581027','8577480','8577491','8577495','8577623','8577629','8581237','8578122','8578126','8578131','8578654','8578660','8580624','8579018','8579342','8579347','8579356','8579056','8563240','8556079','8556131','8555938','8555943','8555949','8550791','8551786','8551787','8551132','8546476','8546499','8546500','8546526','8546335','8545443','8545983','8545434','8545012','8548258','8550140','8550062','8548679','8548611','8548671','8544716','8548220','8548222','8548230','8548231','8548870','8541294','8558577','8558578','8544284','8539652','8539988','8539837','8550039','8550041','8549971','8549983','8549331','8549332','8550061','8549787','8549334','8549337','8549338','8547264','8547277','10214822','36109180','33995798','28767683','28767818','27957407','27627060','27504747','27518480','27533361','27459672','24417984','8536768','8537598','8562480','8562470','4276116','18919162','36132707','18919886','36289490','8546025','8544317','18918958','18917609','8555070','36190305','8544501','7817118','8553428','8545879','8574109','8546490','8570096','9733431','11519141','11537296','4267020','8035324','8121305','7850103','36307860','36214725','10523467','14296003','14295966','14295952','14295957','14771422','14771425','14771426','13939278','13939297','13939193','14738562','14079614','14021517','14005245','14021526','14005183','13939210','21930567','21930733','21930627','21391407','13776103','13700143','13700147','13758245','13700153','14080349','14078148','14079575','14005270','14079595','12311164','30628711','13701279','13758600','13697474','13368397','13398080','3598666','3602509','3595534','3608846','3605170','3605241','3607469','6896247','14296010','36121641','5498223','5497173','5497413','30423101','30971229','30981439','34982567','27959954','27960208','27960373','24836436','21491603','20393244','20397562','24472549','27246743','27963609','36292551','36128107','36128110','36128111','36132354','36129093','13727407','10266026','10498189','10499500','10498164','11213933','9262640','9021100','35865348','36287703','36126241','36123930','36226583','11582955','11254659','36124926','36124932','11609652','16510131','16509934','16370589','15738025','15737286','12568572','12568697','12452929','12500852','12500878','12506781','12383780','12422385','12422849','12422884','12375596','11973005','12448864','12450013','12451533','12384301','12384836','12568359','12568473','12448570','12803466','12851025','12694354','12629968','12569323','12772850','12693106','12655098','12693448','12693524','12629453','12629484','12629853','12687816','16509974','16509983','12852706','14739794','11902045','11904530','14489668','14489679','14488101','12879902','12880374','12875425','12875450','11972625','11972785','11972813','14818170','11148478','11148539','29269505','11741518','11769538','11720327','11083666','11068545','11514553','11190977','11306831','16369146','24836683','36120628','36123300','33634923','14819000','14818427','14818484','14818108','15593125','15593126','11254625','11932904','11935192','11935549','14740212','11829125','11829391','11041540','11043886','11044409','15197252','5283248','5283376','36310791','36311276','36300584','36291788','5280685','6158523','8485638','5298857','5299459','27565862','29976244','14345437','28630194','28630401','27874412','31222625','26463166','26807450','17672366','17672222','17672136','17672185','22851043','34417608','17671522','17671109','13085156','13304140','17665605','17670051','17665073','17664849','24799649','22399970','22300399','31691645','7049075','12451570','12444148','12446686','12446689','10259433','10125944','10788011','10655256','10656293','5285944','5269200','5294316','25210357','13011756','12066279','12066341','36217453','5279623','34777123','6127726','17670393','17670166','13042252','28526019','5284200','5285091','23280745','23280793','6136929','5299319','5299966','36128353','35171972','10104355','30544116','8329166','5270055','5397110','5421088','10795436','12446684','25183724','17664810','5284074','5781653','7715067','3872141','3882540','36220392','25403338','36162872','36162869','9139997','9140024','10622959','9750449','9140018','9139936','9755754','9754490','9754765','14159716','14159752','14159771','14621967','13559358','13659311','13404670','13404678','13408725','13408656','12853073','12908127','15065630','15428280','15428987','15429521','15379725','15428139','15519286','15519290','23438022','25402608','14892825','14814052','14814074','17538413','14814041','15018138','15018143','14814006','14814005','25541551','14883956','13697731','13659324','12655130','12336017','13185885','13181537','13043664','13043683','12423463','12379551','12337811','11902454','11905281','36125405','36126357','36118134','36129188','14628547','14349154','14349147','14536891','14791038','14792234','14622006','14346837','14762635','14628546','14628542','14622007','14349187','11655326','11639340','11632280','11640528','11655547','11867041','11831491','11048316','11516430','11516548','11516703','11516836','11145377','11145436','15626748','36118105','36222450','13085925','14650018','14650028','14704365','14647469','11835424','13665117','13665144','13665174','13665186','13576762','13576780','13665248','15023215','14823757','13368998','13369225','14466600','14643633','14643635','12498712','31492967','36120074','36120075','35379867','15577941','29718247','14539803','14713995','14713996','14740281','13320072','11967976','14428845','14322907','14428850','14322908','14322885','14322892','12376079','13577707','12453006','12686315','12686350','35378468','23373094','36222444','15068582','15068586','14665548','14301269','13577729','14156373','14301279','14577502','14276312','14301287','11865593','8382911','11634664','11609643','11909568','13889028','13891732','13891575','13890906','13891381','35330970','13887141','13887160','13887179','13887835','13887857','36114335','13408649','14349167','13408659','14665566','13893334','13893342','13893507','13893928','13886687','13888497','13888809','13888582','13888596','13656066','13887183','13887038','13886829','13886639','13886863','13889086','13889115','13889388','10910112','26227159','26227176','26234176','24750393','26284703','26283735','26227088','11611471','13892341','13889848','13889849','13889859','13889880','13889883','13889916','13889393','13889404','13889478','13890731','13890564','13890775','13892392','13892161','13892418','13892228','13892470','13892127','13892670','13890322','26281766','26281925','13888201','13888247','13888260','13888085','26346491','26331627','26321177','26340419','14349190','13892034','12415026','11149433','31387773','13888199','8388937','14628548','8388980','8096478','15428133','13892923','15519275','9155037','5309603','8451572','4110921','5313437','5313649','6397532','7789022','8178271','5658154','5654905','5665985','8492373','2301878','6397769','9008871','9154643','9154683','4080287','36269559','36232509','36253744','4201707','4131021','4198682','4205308','4203184','4205361','2301688','6342056','6342896','6345906','6395859','6396565','6396747','19712857','13000187','12875772','18908072','17378449','18005599','18904238','18005711','17054467','17054524','15336534','15336555','15339915','15343554','15343560','15343571','32164775','14608975','15339326','13344541','14668577','10152708','10136065','9201231','8667641','9733438','9733442','9476748','9474786','9476736','9476754','9201239','9476718','9476727','9476730','11020588','11483700','10231407','10283405','9964620','23014463','22872482','22872842','22595575','18915157','18914755','18914284','18917376','26672523','26672892','26673061','20774595','21215954','18918225','18919028','18919125','18911738','9820089','9812246','9820162','9830463','9879656','9790669','9790681','9785858','9747850','9755048','9755049','6395337','33180126','7794105','22920057','18919651','8354205','9807355','9755060','32246372','16626674','5311725','28614341','9755834','9474716','14668517','9820130','9474744','9474812','9807329','9686927','9686923','9686925','9687113','9686909','5312631','9686897','9686931','9686914','9686916','10241479','30391136','14668509','9820151','9755841','10285289','14668508','14668502','22595374','9840984','9700025','9700013','9474818','4131024','12802485','8569653','8569683','8569499','8569570','8569614','8569090','8569116','8569120','8569126','8584500','8584508','8576864','8576817','8576894','8576840','8577200','8577164','8575448','8575454','8567194','8567198','8566690','8566904','8566735','8567626','8567659','8575647','8564510','8565086','8565087','8565091','8564789','8565059','8579692','8579612','8581950','8581812','8582264','8582266','8576005','8576062','8574643','8573784','8574108','8573616','8568333','8568487','8568227',
 '8567822','8569146','8569148','8569150','8569152','8569154','8569157','8568542','8568713','8568967','8572580','8575998','8573534','8575795','8575945','8575061','8575062','8553039','8553094','8552868','8552788','8554157','8553715','8553721','8553478','8552628','8552632','8552158','8552174','8563011','8563012','8563014','8563126','8556691','8556249','8556748','8556516','8557375','8557509','8557202','8556153','8556159','8564290','8563928','8554443','8555151','8555153','8555158','8571608','8570966','8562993','8560823','8560709','8561007','8561008','8563616','8557720','8558217','8558227','8558230','8558650','8557796','8561366','8562188','8561839','8561854','8561856','8562070','8563672','8559308','8559335','8559047','8559050','8549757','8549720','8580547','8581196','8580858','8577485','8577487','8577635','8577500','8577503','8577507','8578186','8578121','8578541','8578638','8578664','8578616','8556229','8556233','8556237','8556111','8555622','8555941','8551789','8551661','8551664','8548979','8546477','8546478','8546481','8546483','8546494','8546498','8546807','8546525','8546269','8546786','8546644','8550111','8545444','8544994','8545196','8545830','8545641','8545566','8545039','8547897','8547902','8548248','8548255','8548261','8550124','8550139','8548682','8548604','8547859','8548405','8549621','8548881','8544854','8544712','8544714','8544415','8539149','8540517','8540329','8538452','8537319','8537327','8537171','8539655','8550120','8549967','8550048','8549978','8549981','8550059','8549777','8549335','8549343','8549345','8549346','8549347','8549349','8549350','8547262','8547656','8547659','8547064','8547143','8575987','36197027','34001984','34004448','34023873','31392707','27885211','27634272','27634435','27877030','27557937','27612596','27421863','8540835','28743191','8537946','8537359','8544727','8537248','8558571','8562482','8562477','8544731','14737317','8568952','18915180','10241478','8576302','13341969','8546724','8544320','14493034','8550015','4131105','18918301','8553034','36122546','8570132','27479220','2301797','36226107','8301676','10322531','8544645','8544651','8572457','8555164','28077392','11519065','8007048','36279993','36215858','36253621','8696717','14295962','14771411','14771434','14771435','14771441','18099505','18099709','14771575','18515620','18515754','15169356','13939279','14714345','14079600','14078090','14021515','14021520','14005187','14005210','18934258','18942410','21930920','21930553','13726494','13700145','13700146','13758222','14078100','14080348','14134860','14005248','14005264','14079585','13012970','32524393','23935287','13701274','14021553','13700175','3598966','12937298','3625407','8688098','8763212','22804183','5497057','5497097','8452865','5497801','36289384','30423542','30967450','32662260','34929946','36123643','25060414','20393848','20398520','19692017','21490922','24843495','23935393','24481146','27962170','25280774','36130401','36176582','36124859','16095655','12903616','10265816','10499097','10274810','10913154','11609947','11641979','11642179','11635364','11640353','10501642','9275835','36266646','10913383','11583022','11583282','11599062','11599085','11254644','11254652','11254506','11254520','11254552','11254562','16505537','16511153','15737431','12568910','12453349','12453425','12453472','12453958','12498821','12572400','12499480','12376520','12378882','12378975','12337803','12337822','12338422','12375188','12375648','12336410','12019110','11973027','12448635','12448706','12483087','12483217','12449354','12377238','12378674','12336790','12694009','12851049','12688014','12629903','12630088','12580031','12568987','12483680','12498391','12687085','12691871','12692337','12692383','12692862','12693356','12629818','16509985','16509989','16510001','12851125','12852327','12852412','12852934','14739793','11902179','11902390','11902395','11931689','11904540','14600645','14492649','14600653','14600671','14489181','14489676','14489681','12879779','12879443','11788001','10274584','11968650','11972673','11972752','11972851','11972879','12851362','19374561','11143608','11148593','11148669','11148726','11973214','11515339','11515399','11515480','11757424','11130161','11090049','11932574','11192501','11174974','36110515','36226908','15203920','24839332','11254604','11940454','11900782','11829257','11829385','11829465','11829476','11829781','11081585','10497906','36313725','36212677','36298749','5282843','6158641','36130111','36130120','25199843','24114172','24114741','29973207','14430889','14431784','14346953','33729720','28629244','22594797','26807281','17671952','21707872','14349079','13607582','36109017','17671073','17671272','17670366','36125548','17670137','17669874','17665552','17665130','12929286','24800234','22399108','17664640','14345408','12872802','8357454','7086255','12454598','12456045','12451559','12451564','12449475','12444144','12416318','12446685','12446687','12061314','10823465','10788670','8686880','10125891','10860808','5277677','5285987','8019107','8038218','6141907','6137335','5293349','5295315','5293567','5293580','5293627','5293628','5293724','12880360','13239628','13357695','5267723','5270028','12066244','6123377','5296298','6135435','17671417','17670013','36220240','29428566','28703272','15694247','17423581','11791502','11793227','5286446','5286749','5284362','5287055','5284989','12803573','6123565','5299479','36117195','36282258','36173872','10869532','5371696','14245530','5414899','5415559','5420371','5420604','7813546','7815166','14329295','8019992','11793614','17664815','13084980','5751773','5743626','5751517','8387832','7715281','3883309','3883325','2471013','2471017','3882929','3882886','36304374','13520544','14301262','36198458','3873087','9140045','9139853','9140021','14159799','14159794','14159764','14621972','35377597','35378762','13582887','13408723','13550212','15068631','15429518','15519278','15418776','15429513','23669212','24242418','24550466','17606072','14814027','14814036','14814023','19463540','15018144','14900741','18433747','14813999','14814001','25541788','25542911','13500334','12476690','12416206','12419330','12419386','12337334','13180322','13180333','13180385','13063637','13041908','13043705','13043715','13063596','12850394','12423762','12339180','12339336','12339703','12338524','12380527','12378960','11908446','11900634','36118144','36118195','36118205','36118131','36161548','36164198','14393394','14672965','14665547','14628521','14628525','14580330','14346850','14346856','14346857','14762643','14651089','14628533','14628530','14628536','14582082','11534249','11640744','11655518','11635847','11610690','11640005','11867093','11867162','11859353','11833871','11830797','11830804','11830809','11090070','10920361','11058628','11091081','11516596','11494391','11145048','11145201','11165889','11149292','15626762','15626767','15626723','15626724','13087764','14650024','14650056','14704362','7829965','7490693','7525512','14823777','13665137','13665149','13576774','13576798','13576808','13576817','13576822','13665240','13665243','13656047','13579720','13579724','13579729','14823752','13369166','13369211','13369003','13369220','13369222','13369023','13369027','13559218','35379906','35379710','14821640','14821681','14821695','14821703','14428835','14322904','14322905','14322906','14428853','12384926','11635743','14892808','13041945','12625700','12423800','14821668','13577715','14823768','13085934','36120042','12376084','14278826','13665207','12425264','14823759','15626722','15626756','11152312','14583865','14301280','14301267','14301274','12420220','15626758','13888941','13577688','13891759','13891039','13891422','13887782','13887265','13887963','13887839','14349163','13893303','13893145','13893322','13893246','13893950','13893770','13893065','13893074','13887085','13886655','13886691','13886951','36241341','13888534','13888587','13888386','13887308','13887316','13887195','13887217','13889362','13889183','26234083','26234221','24512500','26288568','13577764','13892491','13891960','13890475','13890182','13889657','13889391','13889811','13891111','13890583','13892206','13892114','13893133','13892689','13892536','13890208','13890221','13890290','13887652','13887710','13888167','13888425','13888432','13888452','13884971','26346414','26298120','26298618','26323969','26323996','26298805','26341651','26341768','14349173','14349179','36138675','5784395','26273552','36138603','13887768','8095989','8388285','8390500','13893127','26261815','9155009','5309206','5309383','5311914','5313141','5296211','5285845','8460719','5313343','5313614','2301842','4339739','4277417','8177343','5655239','5650285','5668352','5656648','4277162','8312982','9154609','8427816','8514120','8389050','8312711','4205054','9154525','36294962','6395985','36225762','4367688','2301597','4201676','4205106','4198836','4205304','4198472','4243452','4243460','4131225','6390176','6397661','6341662','6342349','6343464','6397033','6395635','31791093','31888811','31888301','30567270','31888370','31888384','30092080','25999252','6396103','19652906','18919530','14737353','14737358','14609002','14487642','12875464','18907952','18907284','18908085','15339298','15339943','15340020','15343563','15205758','31892708','35116200','27583606','14737374','14668530','14668607','14668599','14668505','14608988','14608982','13557118','13436639','14668624','14226907','31893011','9733447','9733448','9733452','9733454','9733457','8710425','10320826','9474808','9201196','8667583','9733468','9733469','9733472','9733437','9474747','9201249','4174340','11483106','9965209','9994467','9994907','23012436','24237943','22595198','22595702','18916761','18914360','18917187','26673486','20787551','20728462','20733525','21160238','21973397','21672694','18918107','18917408','18917928','18918544','18919182','18919000','18919424','9786665','9820128','9820131','9812293','9820078','9820079','9820081','9820083','9820092','9820094','9820100','9820103','9820111','9820124','9820127','9820163','9880221','9785838','9755034','9755037',
 '9755038','9755045','9785805','9755054','9755062','8184797','18919856','24458588','34167653','21216239','9474776','30390886','11829273','4205091','9736806','6349294','9474753','9474801','9782451','9755829','9755827','9755826','9782543','14668628','4275358','24729221','9686886','9686934','13436665','11896838','9474831','9782446','9755831','9755842','9755833','10320844','22885111','9792942','9474802','9474748','6339189','14491230','8574333','8569785','8570563','8570564','8570566','8569556','8569573','8569460','8569611','8570332','8570339','8569108','8569114','8569123','8570296','8576845','8576871','8576885','8576888','8576889','8576891','8576892','8576843','8577091','8567701','8567469','8567607','8575446','8575452','8575458','8567193','8567197','8567200','8566706','8566709','8566719','8566783','8566801','8566328','8566332','8567857','8567756','8566250','8566620','8576347','8575645','8564792','8564794','8565016','8565024','8565028','8565037','8565044','8565048','8565053','8565077','8579865','8579675','8579363','8582570','8582596','8581864','8581809','8581810','8581813','8581816','8581853','8581776','8575949','8574633','8574637','8574146','8574147','8573966','8574357','8571119','8568251','8568226','8567814','8567820','8567849','8569127','8569132','8569139','8569143','8569147','8569149','8569010','8568892','8568853','8568687','8576003','8573504','8573390','8572549','8572569','8575995','8575996','8575799','8574372','8574908','8564216','8564233','8564243','8553050','8553093','8553095','8553096','8553102','8553114','8553464','8553706','8554010','8553720','8552505','8552780','8552636','8552639','8552644','8552648','8552175','8552459','8563002','8563109','8563117','8556440','8556245','8556247','8556252','8556414','8564012','8555020','8554695','8555481','8555152','8555141','8573090','8573132','8573181','8570782','8562785','8562981','8562996','8560815','8560821','8561004','8561006','8561224','8560410','8560307','8559844','8563622','8563634','8563647','8557911','8558231','8561372','8561373','8561536','8561857','8562946','8562963','8562966','8563837','8563843','8563685','8559322','8558792','8558928','8558621','8559149','8559046','8583758','8580868','8580853','8577471','8577483','8577486','8577496','8578639','8578643','8578648','8578652','8578531','8578656','8578657','8578658','8578662','8578791','8563361','8556322','8556219','8556221','8556222','8556231','8556108','8556115','8556116','8556125','8556135','8555555','8555849','8555933','8555944','8556140','8551785','8551675','8550932','8549035','8548974','8546482','8546486','8546487','8546489','8546300','8546302','8546290','8546673','8546780','8546630','8546636','8546640','8546649','8550143','8545202','8545200','8545007','8545406','8545409','8545431','8545817','8545824','8545835','8545449','8545453','8545802','8547918','8548420','8548247','8548260','8550392','8550122','8550123','8548505','8548448','8548618','8548636','8548674','8549487','8548876','8548882','8548789','8544446','8540318','8539944','8540582','8558569','8558582','8538763','8538779','8539671','8550116','8549966','8549977','8550052','8549803','8549785','8549001','8549003','8549344','8549352','8547326','8547657','8547658','8547671','8547672','8547131','8547278','8547506','23107340','8575988','8575985','36205286','34632469','34030852','31568051','27885865','27612983','27504376','27446319','27465441','27419659','8537006','8536840','8540996','28762732','8540952','8540571','8538854','8573486','10207779','10208067','8562469','18005773','5656816','8552161','8550852','8546023','8544325','23917616','8545811','18918629','20544500','14737307','36226078','8549348','8560223','8544635','8570109','14925774','8567845','8570100','4277077','30390821','9729104','8544636','11519357','11519771','11519901','36243365','11739167','8687229','10523466','14296023','14296030','14296039','14296012','14296018','14646210','13939192','14078091','14021539','13939242','21930939','13858240','13700157','14080344','14078140','14005252','14005256','14079584','14005279','21930600','12937287','12744283','12327367','12327676','12327938','12328156','12328949','24995357','13692782','13374596','13758260','13013367','3596533','3613211','3613347','3627026','3606710','3614682','3635138','13939317','5498739','5515533','5496995','5497519','5491775','36181203','30423920','28630178','36123934','32741611','33484175','36123640','36125231','36123919','36123932','21491488','21491589','19699538','23962354','23351529','25224387','24926085','36171222','36129080','13727174','10265554','10501680','11091312','11091517','10501635','10914552','9280067','9025759','9069515','36127996','25224056','36125226','27963363','36292745','36118008','11599243','11599067','11599072','11599079','11254592','11254594','11599086','11599088','36124939','36123658','16510077','16510095','16510118','16510154','16510156','16510202','16509930','16509944','16511102','16511113','16511123','16326298','15738189','12568637','12479228','12452110','12571789','12572480','12576620','12336867','12337912','12338037','12422938','12338072','12338242','12338298','12372646','12372767','12381485','12383585','12336202','12448808','12448902','12449389','12449582','12449646','12450080','12450128','12384235','12384590','12384708','12376824','12336545','12385277','15736049','12869864','12754423','12768248','12688200','12694730','12769103','12630163','12630291','12630650','12579588','12569031','12569181','12569376','12715839','12752972','12630694','12687062','12630885','12687114','12687180','12690989','12773029','12692249','12773348','12773563','12692911','12632227','12687916','16509995','16510000','16510004','16510038','12851152','12851178','12851204','14740061','11901070','11901269','11901342','11901615','11902490','11904546','14600642','14600655','14489674','14489677','14489684','12880224','11788666','11788814','11307447','11484905','11195569','11962917','11972383','12851291','11148514','11148624','11148696','29264690','11515796','11680227','11083311','11086925','11085738','11082998','11966556','11932278','11932618','11191235','11192906','11973093','11966988','11973141','11961735','11175038','36119343','36225341','36182153','34163363','14741080','15593130','15593135','15204225','15708061','14818801','11254607','36229578','11149500','11140344','11962212','11940714','11905193','11900731','11900991','14818877','11829794','11081358','11081947','10496572','11044186','10497324','10497569','5283338','36287290','36250808','5282388','5299023','6158634','6129302','6141413','6142203','27624296','27624320','25184424','24800186','29429574','29428305','29429050','14430433','14345447','14245671','13518054','36118852','31466144','22802734','14339701','17672269','21708173','21707749','21707797','14346929','14346946','14347041','13620062','34564484','13084985','13085182','27441705','27624799','17669978','17669801','17669859','5287435','5298846','22304691','22156760','14339715','12957071','12932877','12880343','8334577','7051899','7089830','7092421','12449509','12456053','12451558','12451566','12451567','12453083','12454597','12448126','12446696','12380843','12381029','12382495','12444071','10655499','5285848','8018515','8035330','8485810','6157939','6138707','5294218','5296973','5293510','5293519','5293739','5293774','23280691','11769207','13239570','13357036','5268783','5270209','5268910','6139083','6141040','17665269','12066235','6128131','5296319','5269382','5279409','5279541','5267419','5267520','5267624','36118828','5269711','17671107','34778757','29996565','17664635','17665393','17669940','26807502','17670938','17665039','24799531','7714612','15463181','12847846','11793414','5299252','5286112','5286638','5293845','5287117','5287243','17672556','17672667','5299712','36117064','14054383','14535559','19474836','10103984','30543832','10870144','8356162','17671376','5430500','21708554','5299321','27874386','6135517','7812540','10125884','13084976','5781461','5781693','7034030','2470970','3882554','3882601','4198840','13519479','9139835','14159816','36114621','13408693','13408699','13408678','13408687','13408697','13408722','13408707','15013259','15018165','15018163','15029244','15029245','15429523','15379728','23131742','25220528','14892908','14814075','15018150','15018147','14792249','14900776','13559370','13719426','13530034','12476590','12452800','12387251','12339112','13158130','13183433','13182454','13182939','13041917','13043711','13043728','13044824','12425320','12379420','12339234','12339498','12383918','12336193','12338448','12338946','12378777','11902482','11908553','11905551','11902304','36126343','36118199','36138676','14814013','14814011','14792247','14301261','14792229','14665121','14628522','14346851','14301292','14301283','14626944','14346860','14762626','14331345','14762630','14628545','14583878','11653592','11654518','11655372','11635552','11632232','11632494','11609825','11634637','11635980','11640608','11640656','11634877','11656453','11870820','11835459','11859692','11060328','11164616','11151954','31635509','14712330','15626774','15626719','13087767','13085902','14650020','14708589','14647459','14647463','14704373','14704374','14647503','14647506','14647510','6994590','36120020','13616590','13576796','13576802','13576811','13576830','13576835','13576838','13369152','13665239','13665252','15023223','13369187','13368961','13369010','13369038','13369241','13369059','13369112','13041910','14643649','12498729','13665172','35379799','15015034','29718541','14704434','14708545','14821721','14821737','14322903','14428857','12506445','14322900','14275237','14276309','14278990','14280493','12423392','12631182','14432999','14665103','13369020','14536900','13577705','9752571','36114333','15068605','15068590','15068598','14311924','12686300','12686312','12686316','12686336','15068615','12686348','12691708','12686304','12686303','11967453','35377986','36222437','11908425','12451452','12452932','12421739','13577721','14823799','36120034','36120038','13085937','12384949','14570643','14156355','14156364','14939484','14276313',
 '14577490','11165809','11635125','12423543','15427459','14823770','14650037','11835035','13559275','13888684','13888735','13888752','13891746','13891591','13891598','13887282','13887982','13887836','13618675','13408663','13893307','13893189','13893194','13893355','13893252','13893853','13893266','13893030','13892839','13893101','13894012','13886876','13887091','13886917','13886777','36250118','11494359','13887728','13888508','13888322','13888333','13888545','13888578','13888407','15429526','13887011','13889093','26266129','26266462','26280445','26284246','26275663','13892281','13892314','13892496','13892001','13890468','13890097','13890107','13891122','13890729','13892609','13892200','13892072','13891660','13891695','13892655','13892665','13892511','13892518','13892726','13892752','13890838','13887720','13887562','13887774','13656057','36114317','13888186','13888208','13888273','26336196','26345777','13887224','36215029','28580380','3881905','8115199','13886673','13891357','9154534','9155014','9154524','5310138','5312887','5299100','4199179','6397402','6397436','6397566','5650680','5656820','5657372','5657460','5657463','5657905','5658319','5670605','5666077','8426694','8426940','8492466','7958231','8390405','36155558','9154653','9154662','36294896','36266740','4218212','4243054','6353067','6396934','6396951','6346522','6396009','6396051','6396078','6396458','6396629','31790499','31888800','31889483','30177834','30567647','22009134','18920273','12875146','18005517','18005586','18005834','15339309','15339911','15339926','15363573','33927773','32226868','27514702','28076663','14737364','14668611','14668592','14668603','14668485','14668490','14608996','14490648','15200162','15339321','14668650','14668644','14668587','14668625','14668479','14608959','36112290','31892990','31914320','31891973','28489431','28077113','28228680','9733453','9733455','9733459','9733460','9733462','9733465','9728875','9728915','12693004','8666372','9729172','9733434','9733435','9733436','9733444','9474702','9474760','9474777','9474781','9474718','9201237','12776934','12632822','11896921','11016570','11895642','11896133','11896645','11482643','10207237','10320835','10321388','9965119','9965216','9964593','9964609','22888301','22888792','24639200','24346501','24407476','24407960','22878838','22594687','18915156','18915574','20844459','20788693','20731767','21976303','21979101','21698049','18918023','18919950','18917914','18917503','18919278','18919059','19049494','18919127','18911748','9820150','9812284','9812296','9840969','9820085','9820086','9820087','9820093','9812256','9812257','9830467','9880217','9879658','9880204','9786871','9755044','9755047','9755050','9755068','9755073','9786836','31386990','4110911','36108158','9807326','9807358','9807344','9812277','14668612','9474775','11905321','18916510','16626686','14668527','9755830','9755828','9755843','4203244','4354994','9812244','9686888','9686922','9830504','9474713','9474758','9474762','9785779','9917995','9755825','9475378','36214790','17054476','9782467','12753334','9792980','9792876','9792922','9791334','9729110','4130931','36153321','8574328','8569832','8569509','8569844','8569864','8569873','8569744','8569564','8569609','8570341','8569113','8569124','8569125','8583705','8576578','8576852','8576866','8576870','8576875','8576876','8576877','8576881','8576886','8576827','8576887','8576893','8567464','8575457','8567350','8566710','8566916','8566734','8567085','8567086','8567773','8567623','8567786','8567672','8566233','8566264','8566656','8576428','8575650','8575632','8576269','8564779','8564986','8564995','8564996','8565000','8565014','8565015','8565035','8565041','8565057','8565066','8565069','8565071','8565075','8565078','8565080','8580883','8580958','8580346','8580272','8579690','8579691','8579693','8579694','8579629','8579866','8579606','8579676','8579621','8579358','8579360','8579362','8579768','8579626','8582834','8582866','8582038','8581858','8581264','8575948','8574144','8574145','8573789','8574099','8573987','8574008','8574353','8573611','8573612','8567835','8567719','8569129','8569136','8569137','8569031','8569051','8568972','8568682','8569059','8569062','8576242','8576001','8573527','8573398','8572576','8572577','8575997','8575798','8574913','8564275','8553098','8553103','8553360','8552799','8552904','8552804','8552809','8553453','8553459','8553711','8552662','8552767','8552179','8552009','8551836','8551697','8556959','8556682','8556711','8556542','8556549','8556148','8556151','8556154','8564151','8563827','8555016','8555193','8554204','8555146','8571606','8570759','8562985','8562989','8562990','8562692','8560818','8560852','8561005','8561221','8560759','8560183','8560051','8560306','8560148','8558294','8558651','8558653','8558540','8558556','8558252','8562284','8563831','8563676','8559780','8559346','8559349','8549772','8582084','8580578','8580854','8577585','8577464','8577468','8577470','8577472','8577479','8577488','8577489','8577492','8577764','8577873','8577497','8577498','8577499','8577501','8577502','8581235','8578050','8578663','8578637','8578640','8578641','8578651','8578653','8578659','8578661','18918931','8580611','8580638','8579343','8579345','8579346','8579349','8579353','8578790','8584488','8562999','8556220','8556227','8556241','8556242','8556119','8556128','8556130','8556137','8556057','8550673','8551790','8551793','8551794','8551795','8551802','8548977','8548978','8548896','8546479','8546480','8546484','8546488','8546491','8546497','8546619','8546838','8546779','8546781','8546637','8550069','8550071','8546009','8545877','8545814','8544996','8544999','8545008','8545816','8545818','8545849','8545629','8545570','8545798','8550125','8550130','8550131','8550133','8550138','8548503','8548677','8548607','8547951','8548500','8548408','8544719','8544724','8548234','8548877','8548878','8548886','8548891','8544534','8544465','8544405','8540354','8540482','4174358','8541139','8541179','8537913','8537169','8550118','8550119','8549961','8550044','8550051','8550054','8550057','8549740','8550439','8548989','8548990','8549002','8547325','8547160','8547664','8547133','8547144','8547303','8577942','8575986','9812286','36278174','31341498','31342099','34001259','27624556','27557950','24417612','8536725','8541089','27883805','8537743','8538356','8544740','8539016','8537641','34373551','36262838','10207944','10208149','8572240','8577116','8548776','8567339','8566712','11829248','22592262','8576289','8576286','8546032','8546051','8544333','8579814','12836352','26945002','8552838','18918375','18917813','8576293','8576300','8580445','8570128','8574408','8559956','8567899','8544344','36225769','8574613','8570725','8544616','8544612','8544633','8576682','8544614','9755868
 ','8570119
 ','8570129','36289353','4203390','10320824','11518733','8009407','36304297','36215688','11782073','10523435','10827264','14296025','14296035','14296043','14296004','14295977','14296021','14295976','18515652','14021586','13939299','14078071','14078074','14079607','14021535','14005188','14005195','14005201','13939215','19049497','21931072','13753380','13753376','13700182','13858032','14080339','14078110','14078112','14080345','14080346','14080347','14122399','14079576','14054339','14005267','14005284','13013350','36104777','22809939','13701270','12311019','36214941','3630685','3611701','3622103','13939306','3634784','21930586','23935276','5498860','5496954','5497269','5499275','5497910','36249684','34992628','32740268','36123638','36123681','35869398','36126184','21658361','20394984','20397201','19697392','19377312','19284806','23879613','36132979','36124856','11536096','11608631','10265257','10265486','10265839','10914188','10498182','10275359','10501683','36128099','36123510','11641761','11642033','11640319','11640449','23920425','10498203','9262417','10914426','10914487','9055873','9281047','9056408','28808631','36126232','10265866','36118007','36226530','36178714','36125246','11583225','11254583','11254584','11599073','11599095','11599105','11254641','11599287','11599237','11254528','16510062','16510069','16510128','16510161','16510178','16510205','16511149','16369243','16369255','12568598','12568880','12479158','12480015','12452433','12453187','12453810','12446187','12453915','12453999','12570703','12499294','12572679','12499617','12499807','12576691','12376078','12376535','12376765','12378770','12337215','12337240','12379026','12337705','12380331','12337863','12380375','12380411','12338054','12380608','12338281','12380781','12338349','12372731','12372857','12372882','12383454','12383494','12383550','12383709','11972955','12449178','12483409','12449219','12449719','12449951','12450303','12450501','12384445','12377624','12385897','12378097','12378595','12336756','12577843','12577923','15736039','15736058','15736062','15736069','12803391','12753782','12754500','12768324','12694619','12768651','12768682','12579698','12569075','12498467','12753008','12753071','12630785','12690822','12691216','12691418','12772913','12773072','12711745','12711798','12711853','12712160','12632360','12647129','12712234','12693551','12693861','12693979','12687288','12687878','12687950','15593754','15736475','16510209','16510029','16510045','12773890','12851229','12852363','12852388','12852440','12852982','12853084','12853103','14740054','11905640','11901493','11901496','11901621','11902624','11904755','14600648','14488104','12879741','12879857','12879881','12880291','12880470','13377591','12879469','12879658','11783802','11784139','11788037','11788159','11788619','11741205','14740468','11729924','11730040','11736651','11737863','11738378','11195567','11972284','11974366','12851336','11175385','11148749','11148923','11148960','12851262','11742545','11765381','11515436','11515940','11757328','11759833','11121426','11121504','11121995','11125303','11972931','11938483','11191016','11192554','11192672','11192881','11967075','36118693','36119757','36138338','16512439','15736905','15736635','14818334','14818157','14818174','14818200','14818428','14818013','14818251','14818482','14818099','14818106','14818110','15593132','15593153','15736027','14818578','14818372','14741033','36129090','11143253','11935014','11962119','11940626','11935818','11904980','11829800','11829906','11830022','11081534','11081876','11081906','11082359','10496515','10496604','7815998','36206152','5283036','6158011','5299069','6139508','6161199','6140178','5299465','25198307','24799524','24799331','29430937','29429119','14346960','23739556','14227887','26807235','17672330','17672078','17671948','21708050','21708090','21707809','21708450','14347039','25421394','17670474','17671082','17670579','13267949','13085111','13103956','13085049','13085085','36118823','27625072','17670028','17665646','17664783','17664859','5298739','5299403','24799964','22182076','22156392','14346977','12929329','8333063','36219606','7715783','7092310','12449491','12453094','12454580','12446695','10808354','10808849','10789038','10383364','10383877','12381287','10795409','10795606','10655386','10655626','10656438','10859510','10859607','10861119','10861509','6162125','5287630','5294307','5293516','5293568','5295502','5293689','23281107','13050060','13171981','13239084','13307580','13011743','13011761','13240778','13386389','5267858','5267972','5268774','5268972','17665155','17665164','12066237','5279241','5279428','5279583','5267430','36118821','5269674','17665222','17664901','6135486','17664784','36317235','5293901','22202750','16167102','20658926','15464693','12796835','11763089','11793555','5286354','5286772','5293971','5286968','5270285','5287264','27624986','29976462','36108106','36117089','36173901','36282317','8331361','10865192','10865755','5384652','14245713','17670841','17664764','10125886','13170760','21086306','6136139','15817621','6135393','3882814','8389879','7715431','3873138','8388615','8388457','3882912','3873070','2470953','3882800','36269663','36303349','36261781','36188934','12336260','9139998','10236460','10236538','9139833','29657748','35375037','36114331','13697716','13559378','13659312','13672537','13404681','13404684','13404690','13408727','13408680','13408688','13559339','13408669','15068629','15018177','15018190','15429527','23437614','23096116','23096255','14814050','14792272','14792258','14792261','14814048','14898460','13582787','12772581','12451664','12415115','12416112','12336064','11975248','11976141','13158126','13180404','13184356','13041929','12423385','12423923','12421615','12339560','12420155','12421472','12338353','11966935','11908049','11908300','11908414','11905795','11966879','11901387','11901453','36123573','36120067','36118182','36153218','14311909','14792242','14665583','14792231','14665546','14628526','14346852','14301289','14627625','14627629','14569668','14342525','14342523','14605896','14605893','14627641','14439262','14503981','14762641','14762636','11516923','11653576','11637907','11635504','11632324','11635696','11635936','11609485','11634516','11610002','11610346','11611307','11640153','11636012','11871555','11835386','11835225','11516659','11494424','11164812','11164963','11165741','11165851','11164102','11149193','14650026','14650033','14650036','14708583','14650040','14708603','14708607','14708611','14704370','14708649','14433001','36120021','8387488','13665130','13665141','13665155','13665158','13665159','13665163','13665169','13665187','13616591','13560329','13699278','13665224','13665231','13665218','13616533','13579727','15023218','14823747','13368956','13368959','13369237','13369047','13559228','13369088','14465979','14431487','14431495','14643641','12498738','11859742','14760714','14539813','14941144','14821641','14821650','14708555','14647524','14821711','14821745','14740247','14740257','14391751','14299932','13269777','12506313','14431462','14431467','14431470','14275833','14275842','14276314','14276524','14276525','12329510','12337614','12479512','12479877','12597775','12453561','11656442','13408713','14431496','14539802','13577690','15068589','12686301','12686325','12686317','35377938','35378224','14433010','14814009','15068634','12451917','15068610','15068611','12476569','13577725','13577731','13577726','36129192','13577716','14571577','14571596','14645387','14814061','12691725','12479872','11635653','14156379','13577738','13369036','13665176','14577510','8389987','13369158','14577542','8217349','14577556','11905775','14301275','8115291','14571591','11634925','15018201','11902226','13889025','13888759','13891767','13891379','13891392','13891410','13887223','13887236','13887264','13887817','13887825','13887829','13888042','15068600','13408657','12506375','13893777','13893477','13893889','13893765','13893419','13893289','13892967','13893061','13887105','36238450','36241315','13888495','15380651','13887187','13887042','13889259','13889641','26269756','26266964','26286889','26233827','26226819','24751592','26283874','24753356','24753754','36222453','13887356','26332978','13892330','13891950','13891855','13892013','13890483','13889853','13889666','13889673','13889722','13889737','13889747','13889756','13891136','13890522','13890528','13890782','13890571','13892180','13891510','13892719','13892723','13892577','13890855','13890637','13890661','13890667','13890674','13890040','13890327','13890451','13887704','13887346','13887770','26267868','26268072','11654411','13888066','13888076','26322630','26298207','26298624','26332420','26296373','26345118','26341782','26344773','13892888','31267114','13886620','13887678','13890259','13656126','9154518','9154548','5309488','5309961','5295765','7817384','5655462','5657937','5656371','5656750','2301832','7826270','5671717','5681713','8459397','8513900','7826380','4202566','6397587','6382341','8355453','8356469','9154682','36268061','4367689','4354570','4277078','4276795','4201695','4198734','4243382','4276502','4205398','6397502','6344395','6336610','6336787','6349683','6336883','6395962','6396214','31888825','31675258','31678227','31063656','30390732','31791025','31889325','30687030','22157718','18920260','18920268','19079513','14668640','13003670','18908091','18902748','18906005','15339338','31892091','31892926','27514111','28215373','28075848','28076467','14737291','14737287','14737399','14737382','14668538','14668565','14668496','14608997','14490754','14492955','15339317','13342095','14668573','14668483','14668507','14608961','14608958','29062239','36105262','29293561','9733449','9733458','9729109','9728911','8615508','10153221','10152606','10152776','10140527','10162458','10140592','9474817','9474807','8615477','9474825','9474827','8667289','9729092','9474755','9474785','9474778','9474779','9474750','11896711','11483075','10320839','10320840','9965219','9965229','9964257','9994463',
 '36214394','22888149','22888497','23012126','22917630','24728472','24465475','22593947','18915235','18917299','18917311','18916266','18914073','26674105','26672393','26387704','20825188','20784199','21398306','21976852','21978438','21791463','21611139','21156200','18918475','18917902','18917985','18917496','18919030','18919046','18918620','18918921','19047965','18919458','18911716','18911744','9808364','9820129','9820136','9820139','9820148','9812280','9812294','9820098','9820109','9820114','9812245','9812248','9840992','9820152','9820153','9820156','9820167','9820169','9880205','9916360','9879662','9786875','9755033','9755035','9755036','9755040','9755057','9755063','9755064','9755072','8390681','20832059','36189012','9807337','9807338','9807327','9807354','9807348','9807361','9807332','30217138','11830279','16626646','18902811','9728953','5311926','9755835','9755844','9755845','9755855','9755856','9782548','8389970','14489545','2301886','5311875','18909851','14668558','18914365','18917907','14668550','9474821','9474804','18917304','9474806','9999665','14668661','4268722','11897493','9792949','9792969','9792963','9792883','14492370','9792948','9792953','6397590','12779572','9792918','9792865','19047448','8574330','8569367','8569501','8569508','8569870','8569871','8569568','8569583','8569364','8569065','8569078','8569079','8569081','8569086','8569095','8569097','8569103','8569105','8569109','8569110','8569121','8584011','8584193','8584194','8583728','8583736','8584214','8576847','8576863','8576865','8576868','8576882','8576883','8577237','8567691','8567467','8567472','8567497','8575603','8575459','8566720','8566725','8566731','8567165','8567743','8567855','8567858','8567859','8567760','8567872','8567770','8567670','8566244','8566653','8575775','8575634','8575636','8575638','8576253','8576255','8576276','8564977','8564987','8565187','8564993','8564994','8565018','8565023','8565029','8565046','8565416','8564559','8564194','8565083','8579686','8579688','8579695','8579982','8579863','8579361','8582759','8582862','8582876','8581863','8581866','8581849','8582883','8582886','8582896','8581856','8584074','8582353','8582262','8582265','8574639','8574640','8574143','8573772','8573800','8574368','8573484','8571126','8568396','8567882','8567798','8567716','8567841','8567729','8567736','8569004','8569005','8569007','8569009','8569016','8569019','8569028','8569029','8569038','8569040','8569043','8569049','8569052','8568974','8568976','8568670','8569061','8569053','8572579','8572595','8576245','8576189','8573539','8573388','8573540','8572571','8572572','8575924','8575944','8564239','8553186','8553106','8553361','8552891','8553643','8553645','8554020','8553747','8553675','8553677','8553736','8553712','8553714','8553723','8553726','8553386','8552655','8552666','8552769','8552163','8551830','8551705','8552763','8563246','8563008','8563258','8563032','8563133','8563053','8563159','8556449','8556366','8556479','8556411','8557240','8557149','8557115','8556780','8556781','8557281','8563939','8563808','8563824','8555006','8555470','8555505','8554379','8554715','8573070','8573115','8571601','8571602','8571605','8570763','8570776','8570778','8571845','8562690','8560817','8560994','8561039','8560746','8560014','8560194','8560150','8560151','8557721','8558354','8558250','8557779','8561371','8561535','8561852','8562969','8563840','8563670','8563871','8559340','8559347','8549719','8582006','8581805','8581806','8583742','8583516','8582880','8581205','8581213','8580857','8581086','8580852','8580303','8580152','8580164','8577465','8577466','8577478','8577481','8577628','8577888','8577448','8581230','8581233','8578540','8578642','8578644','8578645','8578646','8578647','8578649','8578650','8580610','8580629','8580632','8580831','8578998','8579007','8579009','8579012','8578613','8578756','8578757','8579209','8579350','8579354','8579355','8578788','8576207','8576156','8576157','8563317','8556063','8555591','8550674','8551401','8551792','8551797','8551662','8551663','8551800','8551681','8551689','8550921','8550922','8550927','8550935','8551633','8549024','8549026','8549032','8549044','8548965','8548894','8548982','8548984','8546493','8546289','8546219','8546593','8546675','8546604','8546842','8546853','8546694','8546334','8546784','8546648','8546655','8546592','8550175','8550113','8545998','8545805','8545174','8545197','8545001','8545009','8545408','8545815','8545947','8545821','8545825','8545834','8545836','8545609','8545803','8545804','8545610','8550394','8550132','8550134','8550135','8550141','8550561','8548675','8548587','8548592','8548597','8548613','8548556','8548412','8548584','8547746','8544718','8544722','8544547','8549490','8548884','8548771','8548890','8548892','8544404','8540505','8540507','8539904','8540588','8547668','8558564','8541412','8541421','8538271','8537857','8537097','8539634','8550114','8550117','8550043','8550045','8550049','8550050','8550060','8549797','8549798','8549804','8549805','8549782','8549790','8548987','8548993','8548994','8548996','8548997','8548999','8549000','8548810','8548630','8547319','8547151','8547256','8547727','8547662','8547495','8547134','8547136','8547071','8547078','8547276','8547515','8581922','8573507','8575984','8580161','36190296','36205302','34031498','33995319','31344644','27957014','33982708','27504060','27504721','27519466','27389046','27421955','8536836','28763533','28763941','27883736','8538352','8538374','8544884','8544738','8544739','8544743','8538783','8540779','8540573','8537231','8539309','10207975','10207824','10208047','14737372','11831406','8571123','8556553','8550876','8546027','8546040','8551934','8550844','8544321','8544324','8579813','14492843','24729007','8538808','8549784','18918354','18918391','8545799','18918736','18918911','18918300','8548605','8576285','8576280','36269165','8548114','10321411','18916855','8544502','8540469','8555047','8553403','8544618','8544619','8544634','8544643','8576688','8570110','8544665','8582742','8544637','9785837','10320825','34022299','34002005','8008735','7869987','36213373','36215894','36210489','10826944','14296041','14303723','18099760','14646205','14646214','14646215','14645060','14021574','14652373','14079599','14021498','14021501','14079613','14021549','13939202','14005203','14005213','13939235','13939255','18945056','21931054','21389247','21390252','21930640','13701290','13701281','13700160','13700174','14078114','14078123','14078132','14005273','14005276','14079592','14005283','13013365','12329082','36105529','24004108','36131772','30181562','13701260','13701267','13939269','22431135','27112235','13701275','36131155','13776109','36215872','36147430','3608537','3610940','3615899','14021582','5499353','5497116','5497576','5497859','36253439','36226535','36127997','30423857','36123941','36101857','27968957','27292793','21491174','21492227','19700969','20394552','24473218','24926034','36129125','36128117','36128118','36139705','36129099','36129078','36129081','36126481','22670202','10265347','10265686','10913689','10275322','10265149','10498193','10501659','10501666','10501682','10498166','10275066','11641633','11640593','11091333','10501637','10498222','9280112','9261893','9281202','10501654','36303528','11599271','11599070','11599075','11254588','11599081','11599084','11599087','11599089','11599210','11599213','11599239','11254574','11254581','16510054','16510107','16510125','16510143','16510148','16510160','16510184','16510191','16510199','16369925','16511098','16511128','16369299','16326290','16369415','15738187','15738192','15737484','15737403','12568751','12452066','12452819','12480539','12453280','12480761','12480803','12453668','12453741','12445962','12481066','12481179','12498870','12499402','12499753','12500231','12500644','12577297','12376119','12376469','12376553','11975832','11976694','12378736','12336961','12378933','12337450','12337603','12337654','12379954','12380064','12337729','12380189','12380226','12380479','12380649','12338173','12444604','12338326','12338391','12338406','12372808','12375742','12336284','12336314','12019078','12448945','12384388','12384668','12376796','12336573','12378122','12419893','12336666','12378558','12336705','12378709','12481880','15736040','15736345','15736052','15736203','15736086','12871239','12753604','12753914','12768439','12768517','12688052','12694579','12694658','12768739','12769174','12769251','12579156','12579197','12630500','12579278','12579464','12568956','12569106','12649267','12630724','12687044','12631558','12769343','12690884','12691069','12691362','12772883','12773619','12692456','12692508','12711938','12712037','12687978','12632320','12632402','12645617','12655079','12646954','12578555','12687243','12631849','12653429','12631946','12632099','12632154','15736438','16510242','12852580','12852627','12776005','12852918','12776138','12853000','12853146','12853204','14740111','14740112','14603556','14740041','14740044','14578431','14740056','14603595','11905639','11906342','11906350','11906404','11902109','11904676','11904750','14489132','14579145','14489140','14489149','14600646','12880407','12880560','14489122','12879711','11782634','11784080','11784173','11787455','11787906','11788183','11788414','11788901','11741153','11307233','11740476','11740770','11195564','11195588','11965398','11965641','11965839','11972907','11149983','11150183','11150204','11148775','15594418','11741815','11742314','11743433','11744157','11744401','11756305','11515827','11760014','11084457','11129975','11129998','11130076','11122102','11122855','11123002','11125370','11125544','11125606','11082428','11086427','11082784','11086528','11083055','11941267','11932503','11939893','11191063','11191153','11192838','11193058','11973078','11973166','11175027','11149544','36158773','36238563','36240498','36178521','36138321','36139742','16585992','15593165','15736921','14818115','14818306','14818324','14818143','14818156','14818165','14818172','14741253','14818206','14818208','14818216','14818218','14818433',
 '14818435','14818439','14818091','14818483','15593131','15593137','15593140','15593142','15593156','14818741','14818798','11149108','11149192','11149449','11141098','11126438','11940417','11961783','11935087','11935290','11962086','11935453','11962175','11935527','11935570','11905053','11905130','14819135','14818499','15736113','11904909','11781282','11044328','11044628','10497878','36317234','5298498','5298606','7051821','7770634','7784532','7785283','5298829','5299370','5299375','6159257','6140185','6140492','6140872','25201551','25201591','27624624','25198176','24800159','14345372','33729616','36118861','27935657','23739688','23740607','22802690','14347022','14347010','14346902','26808710','17672570','17672464','17672473','17672318','21708143','14228582','17671104','17671106','17670643','13267295','13268380','13240277','13085187','27624782','17669938','17670205','17665277','5294061','22332530','22184266','14346913','14339721','8331859','36219801','7787632','6157236','22803474','14339680','12456049','12454596','12444082','12032842','12417310','10795169','10380082','12379066','12379841','12381517','12383899','10805746','10859654','10859783','10859926','10860261','10861265','10861358','10861589','5283520','5277517','5285869','5277554','6158124','5295415','5295669','25210436','25210623','36125489','36125498','23279894','13239856','13011765','13022218','13011758','13626950','5267790','5268419','5270185','5268844','6129516','6141045','6141083','23279766','12066245','6122953','5296529','5294042','5269411','5270031','17672423','17665082','17672639','27624696','16466603','15461698','15586394','5299140','5286265','5286287','5284048','5287012','5287031','5284797','5284900','5287132','5287168','5285218','6136554','5299630','5299662','5299682','36104402','36262679','36286660','36273371','36173924','8331741','8328463','5371477','5441657','12880361','12449492','17670905','14339717','17672262','5282779','7814968','5754670','5743314','5743417','5743903','5750612','8388105','8096562','8389409','7159342','3883249','2470972','8388701','8389802','3882621','3882862','36253933','36182213','36190364','36118213','14892905','36114325','14439282','36164075','9140031','9140010','9749324','10236291','9139834','13559321','14621970','13408730','34171671','27570898','35374730','35379187','35379424','36114616','13559464','13659317','13404680','13404688','13404692','13408728','13408738','13404700','13759977','13727549','13408700','13408684','13559332','13408668','20442802','15018194','15018198','15029335','15018203','15065631','15029274','15018169','15018170','15018175','15018183','15029247','15429514','22855843','14940275','14892923','14903474','17538436','14814030','14814037','14814039','14792259','14814044','18949475','14900744','22275666','22315882','13559374','13659327','13659348','12691722','12803702','12803714','12453773','12452904','12452949','12450707','12691710','12415275','12416244','12419198','12386953','12387297','12335901','11973235','13180352','13180397','13041913','13015335','13063261','12423531','12423582','12421491','12424688','12339216','12421257','12414522','12414534','12337865','12336236','12338486','12422039','12422303','12422343','12380442','12380615','12378860','11908343','11900284','36118104','36118130','36118135','36126415','14627649','14536896','14342538','14167643','14311900','14792245','14672967','14536882','14464136','14792238','14665125','14621987','14628528','14580853','14501700','14651980','14342530','14605888','14504306','14627639','14627636','14541151','14346855','14311935','14583869','11601429','11653697','11653890','11655237','11655468','11632424','11632521','11632585','11632680','11632715','11610044','11611427','11611448','11640357','11640572','11636383','11636602','11655823','11656560','11653523','11870543','11859614','11865565','11833425','11830824','11831185','11516412','11490087','12414504','12415204','22057329','20443364','36222457','36222459','13087768','13085869','13085933','14708563','14708570','14708573','14650043','14708594','14708606','14650063','14704382','14647504','14704395','6984874','12655124','13665112','13665113','13665128','13665170','13619144','13699271','13665229','13665235','13665200','13620407','13665205','13665212','13665222','15023219','15023231','15023295','15023626','14821760','14823743','13369176','13368948','13368953','13369215','13369050','13369079','13559231','13369095','13369102','14464791','13369123','14738965','14643637','14823780','36120080','35379663','36120070','36120085','14276316','15015046','36115627','28998145','14541513','14539771','15014959','15014963','14821644','14821652','14821655','14821665','14821676','14708551','14647529','36164134','14740277','14740288','14821714','14821717','14821732','14821734','14821742','14821748','14821752','14740253','14296055','12711428','14431464','14431485','14275229','14275240','14275837','14276317','12421799','12421815','12597831','12448048','12597800','12448035','12384927','12384936','12421740','14579628','13577704','14431472','13369118','13041951','13577692','13041933','12803699','15068606','12686307','12686327','12686339','15068601','12686323','12686320','12686330','14583867','14823792','14823795','15068607','3882658','12452779','12339051','15014992','13577723','13577736','12450811','36120048','14571580','14571652','14571611','12337619','13559366','13184861','13577743','14577482','14297255','14577487','14281175','13577771','13577786','15018171','14431498','11905240','11164742','12384213','12451944','11902355','14431504','15418737','36222455','12335335','15427446','11654665','5782528','14823765','14823766','14823771','12625704','13888650','13888707','13891571','13890897','13891384','13891027','12655125','13887232','13887235','13887132','13887297','13887923','13887847','13888049','13893295','13893149','13893313','13893328','13893333','13893791','13893927','13893763','13892973','13892799','13893090','13886901','13886748','13886953','36238440','36247735','13888801','13888544','13888368','14794645','13886990','13889604','13889609','13889626','13889038','26268557','26234011','26234355','26234092','26262513','26286922','26233876','26276170','26281263','26288271','26284368','24753141','11653875','13656063','13891956','13891823','13890125','13890187','13889920','13889927','13889704','13889740','13889761','13892778','13892441','13892455','13892266','13892472','13892054','13891920','13892110','13891633','13891498','13891500','13891715','13891538','13891718','13892931','13892728','13892593','13890841','13890623','13890646','13890203','13887772','26281438','26281853','26283289','13892310','13888198','13888285','26337179','26337615','26297950','26323461','26323934','26296326','14571640','2470969','13886986','13886703','13656070','29181350','13893312','9154531','9154515','5309849','5312849','5286244','4110910','5313349','5313587','7786949','6397357','5657119','5666633','5658488','5655160','2301762','8313101','8389259','4174501','8303397','9008879','9154644','9008866','36232219','36209410','36227628','36244606','36224473','4272341','4271891','4131164','4201621','4201635','4277228','6396640','6346209','6348547','6336654','6339323','6395823','31888839','31790715','30391171','31888422','31790862','19723387','18920223','20630530','18944920','18942029','14737309','14737312','14668636','13004403','18907924','17378465','18907294','18006128','18005561','18904290','17054529','15701837','15339301','15339304','15339307','15339939','15339942','15363582','15339327','31890431','35386331','14737296','14668526','14668543','14668557','14668555','14668609','14668504','14608979','14608969','14608973','14608968','14608987','14608983','14608984','14492438','15339310','15339311','14668662','14668652','14668571','14668585','14668578','14668511','35852318','9729096','9728889','12753922','10153319','10153103','10162859','9474819','9474815','8613274','9733467','9733474','9733475','9728938','9728944','9728947','9474803','8716341','9474832','12753097','12801236','11016909','10285140','10285307','10283616','10320843','10321308','9964237','9965227','9964240','9965107','9965111','9964608','22886318','23014891','23038091','24727305','24462523','24238119','24455292','22879965','22881088','22593122','22592873','18915508','18916668','18916085','18915243','26674137','27453768','20778526','21967634','21671958','21752699','21792048','18918021','18918503','18919853','18919617','18919161','18918728','9820137','9820140','9820141','9820145','9820149','9812274','9812281','9812287','9830512','9820115','9820118','9820119','9820120','9812250','9812253','9820157','9820158','9820164','9917964','9917974','9879661','9912331','9786920','9786924','9785814','9785833','9785844','9785870','9786829','9755065','9755066','9755067','9755074','9786845','21397935','14668481','9807325','9807345','9807359','9807349','9807376','9807335','9807340','9807333','9807328','5312261','11897504','11902610','27996145','18916496','18005451','11905932','5314097','9782452','9782502','22884575','29068099','14608993','14668528','15363578','11906013','9964245','9807341','15339935','9782519','9755863','14668506','9830419','9755869','4203993','14668560','19653650','6343336','9792939','9792943','9792977','9792928','9792902','9792868','28161554','30564060','8574402','8574326','8574415','8569371','8569504','8569506','8570032','8569862','8569742','8569746','8570331','8569068','8569069','8569070','8569075','8569076','8569084','8569093','8569094','8569102','8568985','8584010','8583933','8583345','8576846','8576851','8576853','8576862','8576867','8576869','8576737','8576879','8576748','8576825','8576780','8576963','8577095','8577097','8576941','8577184','8577122','8577132','8567702','8567367','8566913','8567072','8566729','8567075','8566796','8566311','8566325','8565999','8567747','8567751','8567864','8567758','8567867','8567868','8567869','8567766','8567768','8566236','8565932','8575628','8575640','8576423','8564485','8564523','8564774','8564953','8564973','8564989','8564992','8565032','8565764','8565718','8565742',
 '8565615','8564377','8564564','8581548','8580956','8580338','8580345','8579684','8579685','8579689','8579848','8579959','8579960','8579860','8579864','8579940','8579359','8579453','8579454','8579957','8579905','8579624','8582869','8582872','8582877','8582587','8582590','8582479','8581869','8581873','8581874','8582885','8582890','8582893','8582897','8583457','8581851','8581768','8581508','8584255','8582131','8575947','8574557','8573782','8573802','8573985','8573986','8573962','8573964','8573965','8574428','8574342','8574254','8574255','8574257','8574364','8573642','8573646','8571053','8568332','8568360','8568290','8568563','8568657','8568483','8568215','8568106','8567885','8567886','8567891','8567892','8567897','8567797','8567811','8567707','8567708','8567710','8567828','8567831','8567713','8567714','8567833','8567717','8567840','8567718','8568987','8568989','8568991','8568996','8569001','8569011','8569013','8569017','8568880','8569018','8569020','8569024','8569025','8568910','8569033','8569036','8569037','8569039','8568669','8568854','8568703','8568714','8569060','8569063','8568950','8568968','8569056','8569058','8572596','8572598','8576194','8576198','8573501','8573529','8572284','8572573','8572440','8575928','8575929','8575930','8575931','8575938','8575940','8575942','8575946','8574609','8574373','8574382','8574910','8564269','8564074','8553037','8553108','8553113','8553218','8553374','8553011','8553644','8553447','8553316','8553687','8553689','8553696','8553742','8553476','8553642','8552826','8562596','8562632','8552630','8552646','8552651','8552164','8552168','8552079','8552114','8552014','8551708','8552478','8552490','8552466','8552741','8552761','8563259','8563049','8556525','8556547','8556558','8557372','8557235','8557043','8557156','8557047','8557058','8557078','8557079','8557084','8556996','8564192','8563995','8564003','8554698','8555001','8555003','8555005','8555008','8555011','8554385','8554407','8555166','8554738','8571111','8571116','8573072','8573183','8571604','8571471','8571651','8571836','8570729','8570779','8570699','8571974','8562978','8562575','8560795','8561186','8560822','8560713','8560742','8560760','8560636','8560184','8560413','8560316','8560153','8560154','8560158','8560259','8559957','8559759','8563313','8557747','8558263','8558289','8558449','8558385','8557775','8561459','8561467','8561363','8562244','8562263','8562049','8561499','8561655','8562950','8562955','8562959','8562972','8562103','8562009','8562018','8563679','8563913','8559332','8559348','8559351','8559354','8559355','8558958','8549659','8549663','8549773','8549615','8582079','8582083','8581925','8581936','8583605','8581084','8580860','8580865','8580869','8580149','8577473','8577474','8577475','8577476','8577477','8577621','8577631','8577634','8577874','8577884','8577886','8581229','8581384','8578517','8578263','8578768','8578539','8580692','8579002','8579003','8579008','8579015','8579016','8579017','8578749','8579331','8579333','8579341','8579344','8578777','8578779','8578781','8578789','8584494','8576206','8576104','8576159','8556067','8556224','8555445','8555455','8555807','8556059','8555826','8550780','8550697','8551403','8551443','8550790','8551798','8551799','8551666','8551670','8550924','8550926','8551059','8551068','8551143','8550975','8549030','8549034','8548980','8546370','8546299','8546821','8546294','8546211','8546218','8546162','8546595','8546677','8546612','8546833','8546837','8546843','8546846','8546847','8546262','8546632','8546670','8545978','8550112','8545201','8545982','8545806','8545812','8545176','8545177','8545178','8545179','8545180','8545184','8545185','8545100','8545192','8545000','8545002','8545005','8545010','8545411','8545418','8545421','8545948','8545843','8545640','8545563','8547906','8547844','8548338','8548429','8548439','8550416','8550136','8550212','8550558','8550590','8548676','8548678','8548508','8548512','8548521','8548601','8548602','8548616','8547866','8547878','8548472','8548560','8548654','8549721','8548134','8544841','8544852','8544494','8544500','8544511','8544515','8544432','8544434','8544529','8544448','8544454','8544271','8539354','8538974','8540075','8540651','8540667','8545614','8558560','8541163','8541176','8538673','8538313','8537820','8547703','8539486','8550021','8550042','8550046','8549974','8550055','8550056','8549982','8549247','8549263','8549278','8549794','8549799','8549807','8549734','8549747','8549783','8549789','8549791','8548985','8548991','8548992','8548995','8548998','8548822','8548624','8547320','8547321','8547235','8547258','8547713','8547716','8547660','8547661','8547663','8547665','8547681','8547336','8547268','8559385','36190310','36190312','36197013','34001990','8546668','34028739','31343380','27622583','27887645','29134048','27426677','27558197','27558228','27480618','27428211','27554066','27457425','24418771','27883067','8537922','8538358','8544802','8544901','8539218','8539008','8539238','8540788','8540800','8538871','8537664','8577947','8549733','10207995','8572297','8562478','8562466','18919086','8579410','10206107','8567781','8550874','8546026','8546044','8546050','8575885','8575897','8575902','8551477','8551935','8547864','8550848','8560202','8550857','8544326','8544338','8579815','12803991','14491956','18919536','8576223','18917619','18915315','8576281','8576277','36285523','8576296','8555057','8555065','8555067','27460642','8579949','18918752','8561365','36294913','33993537','8584511','8584512','8584517','8578277','8544352','8547159','8553409','8553425','8570092','8544630','8544627','8544620','8544647','8544655','8544656','8544653','8570114','8570123','8544658','8544662','8544657','8544664','8553471','8544639','8544642','8544641','8572292','8547694','3598318','8149112','36313576','36264405','14296027','14296034','14281833','14281835','14296045','14281845','14296005','14296013','14296017','14295992','14295995','14646225','14646219','14646221','14646223','14767096','14767172','14767063','14767084','14021570','14021481','14079601','14021485','14078078','14021495','14079611','14080338','14021523','13939303','14005192','14005208','13701285','13700155','13700162','13700171','13700178','13701253','14078103','14078105','14078108','14078116','14078118','14078119','14078121','14078124','14078135','14079580','14005280','13013359','13013362','11963849','12328822','32527525','22810719','13701256','13701264','14021557','13001070','13001078','36176151','28436846','3595579','3628205','3628448','3635248','6895643','14281839','36215737','5496857','5499362','5497377','5491871','36256115','30416503','30971060','35169834','36123653','36125225','36125236','36101902','28808506','27969320','19702087','20396489','18187647','27961038','27967764','27963231','36295621','36129115','36128006','36124872','36124884','13726585','11608480','11788013','10265734','10913974','10276643','10265187','10499096','10499100','10501617','10275170','10941043','36103020','10498208','10501644','10915820','10915875','9279412','9023521','9275009','36126197','36123684','10669252','36124878','36114948','36322963','11599252','11599256','11599259','11599272','11599099','11599106','11759763','11599235','36129103','10914122','36127994','36127991','16510075','16510083','16510088','16510101','16510111','16510122','16510170','16510173','16511624','16369982','16511105','16511117','16511132','16511141','16511147','16369295','15737388','15737399','15593451','12578305','12480493','12453231','12480859','12445902','12481117','12498685','12499031','12499130','12499517','12499556','12574882','12499668','12575832','12499840','12500052','12576559','12500797','12500823','12576792','12504224','12577251','12019887','12021305','12021592','11976145','12028689','12378857','12379053','12380553','12380831','12380875','12381004','12381054','12444910','12381096','12381204','12381269','12381310','12029918','12030194','12018310','12030669','12018554','12030875','12019588','12019598','11966585','12450196','12450374','12384475','12384517','12384543','12384776','12384807','12385033','12385077','12377026','12385173','12336634','12578204','15736309','12853225','12871419','12803288','12871450','12753814','12753860','12768375','12768406','12768467','12768545','12768772','12579100','12579751','12579827','12599137','12599376','12753378','12652987','12630969','12687142','12653135','12653251','12687211','12631747','12690608','12691026','12691332','12694770','12711553','12773424','12773655','12773709','12773742','12711883','12712075','12632377','12647036','12647590','12655973','12631777','12687270','12631814','12687648','12631900','15736677','16510974','12803731','12773847','12773975','12774073','12774195','12774401','12774442','12774515','12852674','12852850','12852870','12852895','12852959','12853021','12853043','12853123','12853174','12853188','14740010','14740022','14740046','14740050','14740053','14740188','14740194','14740078','14579125','14489127','14579142','14579148','14489142','14489147','14600641','14489156','14600663','14603544','14578397','12879936','12879967','12880181','12880251','12880443','12880491','12880522','12880596','14489124','12879281','12879681','11782742','11784320','11786790','11787780','11788565','11788754','11762598','11741046','14741052',
 
 '14740790','11729496','11729612','11735379','11735475','11737396','11737724','11307552','11740061','11510794','11193133','11195580','11963133','11968634','11963421','11965407','11965900','11966040','11149744','11149777','11150069','11150094','11148650','11148845','11148946','11149007','11149026','29266024','29267008','29268122','14740173','15736426','11763129','11742113','11763299','11742240','11769718','11769995','11515668','11755349','11515729','11756062','11770395','11756924','11696209','11760172','11760349','11760491','11083457','11084129','11084237','11084303','11084734','11143419','11143440','11129825','11120305','11120591','11086755','11122051','11122068','11085419','11125423','11085722','11085771','11090392','11090782','11086139','11086397','11082594','11082916','11966333','11966389','11966437','11966526','11940972','11938473','11941107','11941234','11941275','11932637','11942661','11940033','11940109','11961046','11961113','11195600','11191104','11191216','11192524','11973279','11534664','36108172','36120811','36120921','35774541','36229790','36156124','15593354','15593167','15593173','15737248','15736484','15736704','14819250','14818301','14818112','14818309','14818126','14818315','14818338','14818129','14818130','14818132','14818138','14818150','14818184','14818197','14818201','14741106','14741273','14818203','14818219','14818220','14818234','14818016','14818025','14818248','14818036','14818449','14818256','14818051','14818067','14818073','14818473','14818078','14818298','14817968','14741009','14741010','15593149','15735068','14818760','14818764','14818769','14818777','14818778','14818547','14818803','14818374','14818383','14818391','14578435','11149213','11149338','11149376','11149399','11125827','11142207','11940472','11935342','11935509','11935592','11935610','11935691','11935762','11937636','11905534','14819063','14819107','14818670','15736107','15736246','15594357','11829915','11781555','11082058','11043950','11044247','10496650','10497614','11044503','10497833','10497920','5283251','5283262','5283272','5285329','5293903','36298737','5269029','5296883','7714235','7770784','6158586','6129239','6139991','6140072','6140924','36169768','25100613','24799506','29974303','29428212','28948554','14346995','14345426','33730139','34777934','34777954','22585708','14385684','14346900','14329276','14339708','14240199','26807012','26807523','26596786','17672564','17672416','17672431','17672440','17671939','17672722','17672461','21708829','21708423','13658026','17671038','17671092','17671163','17670974','13268995','13269300','13085068','27625054','17670385','17670132','17670219','17665661','17665117','17669759','17665002','17664869','5298950','5269120','5285061','24800224','24799235','22290694','22356829','17664684','14346981','14345424','14339723','13003809','8355020','7785880','7086567','7089355','7090222','6156617','6157588','12453093','12451554','12454578','12453082','12454584','12453084','12453092','12444075','12444145','12444150','12415195','12385190','12061098','10856583','12379367','12380602','12384994','10665899','10666127','10859719','10859875','10860879','10861176','10861420','13618703','5287396','5287478','5277802','5269094','5269130','8037233','8484942','5287563','5287646','5294398','5295197','5293455','5295343','5295381','25210339','36125513','36125475','23280054','13238966','13239176','12880371','13627230','5268285','5270132','6139134','6139652','6141002','6129572','12066288','12066311','5296624','17670356','17672275','17664643','7786879','22245254','20660256','11792452','11793473','5299229','5299251','5286125','5286452','5293831','5293899','5284305','5284977','5270301','25210102','6126524','6127051','5299466','5299553','6127604','36117186','36176062','36144764','35169880','35843688','35853363','36273267','19474798','19475495','5380117','5442127','12797300','17670879','17664707','17670712','27441863','5433910','17671582','6135549','7813803','5753758','5743941','7715314','8096593','8115173','8115292','3882839','3873142','2471012','2471014','2471019','8390275','3882923','3882600','4198852','4198865','2470964','3882865','3882893','36226956','25544429','13520551','9139662','14621971','36114288','34430349','35379335','31168045','29934650','36104667','13582891','13672798','13404682','13408737','13408729','13404708','13404696','13760038','13408677','13408709','13408704','13559348','13559326','20057692','15068644','15068658','15032236','15032246','15029281','15018174','15018180','15018187','15018193','15029252','15428990','15379748','15380635','14939506','14814072','14814065','14814069','23724098','23724249','24241799','14814034','14792254','14792270','14820909','14938042','14900773','25547229','25361311','14901476','14901864','13559369','13697761','13719606','13550282','13697738','13659331','13520561','13500335','12688372','12803680','12451732','12452162','12387036','12335817','13063627','13581930','13180315','13185544','13180726','13072420','13041922','13063611','12423830','12379378','12339375','12339602','12339767','12414512','12414515','12375966','12336285','12422011','12376843','12379000','11967031','11908023','11908243','11908402','11908474','11909435','11909592','36118140','36118183','36118191','36118106','36137702','36137721','36129189','36129190','14536894','14057887','14439294','14391108','14786032','14792239','14672970','14665584','14536889','14328922','14627658','14580851','14665562','14665557','14645367','14645350','14644501','14645375','14580877','14342524','14605899','14504305','14324049','14762647','14605918','14605923','14439267','14504300','14328144','14762646','11568928','11599504','11653839','11654388','11654531','11639695','11632635','11635959','11609654','11634476','11609924','11634555','11640249','11640272','11636739','11634899','11635019','11655925','11866206','11870681','11864697','11833505','11830816','10623407','11165616','12422127','12329470','36222462','13085917','14708565','14708592','14650052','14647468','14708614','14704376','14647483','14704389','14647502','6995327','7040057','7107227','6929210','7491797','7825572','36120026','11655630','14238907','13689598','13656148','13656164','13665122','13665125','13665177','13665182','13665183','13665197','13609112','13369163','13699275','14228848','13665203','13609158','13656103','13616542','13616545','13579735','15149848','15023253','15023305','14821756','14823742','14823748','13369172','13369190','13369245','13369252','13369257','13369061','13369070','13369077','13369083','14432217','14431489','14465994','14431502','13369105','13369132','13369139','13369147','13313326','36164204','15065646','12499072','12498714','12498720','12498732','36120082','36120068','36120081','35379766','15015011','14760722','29718212','14541514','14539783','14823767','15015000','14941145','14821659','14821661','14821670','14821674','14821688','14704428','14708560','14708659','14704411','14647517','14704422','14647534','14760699','14786824','14760705','14794641','14740259','14740261','14740263','14708664','14821706','14821712','14821724','14821730','14740243','14296061','14325749','14280498','14280499','14281181','14281186','14281776','14391777','14391789','14325660','14391797','14431474','14431481','14275830','14275840','14275843','14276310','14280496','12421789','12479881','12597814','12384933','12384942','12384946','12506868','13041937','13041959','36232309','13313315','13041948','15068624','12686335','15068619','12686332','35378044','14433005','14823794','10236123','14823788','36129194','14823758','12476549','14823800','12476100','15380703','14571586','14571649','14571629','14571594','14571617','14571602','14571637','14665102','13072434','12421786','11640699','13577770','14281781','14577526','13577781','15032228','3871962','13041962','13041949','14577540','14577537','12414510','14651988','12419892','11871709','13759711','13559282','13888938','13888667','12479840','13891734','13890910','13887592','13887611','13887158','13887940','13887976','13887793','13887981','13887821','13888019','13891486','12506363','10910116','12498758','13893406','13893876','13893939','13892958','13892791','13892977','13893070','13892861','13886912','13886753','13886762','36241254','13888483','13888346','13888358','13888606','13888611','13888403','13888409','13888417','13887193','13886978','13886985','13886843','13886867','13889290','26266536','26261986','26286980','26287905','26234171','26234179','26227319','26233848','24738999','26284592','26284723','26280417','26281212','26284272','26284461','26227047','24753664','13892484','13891961','13891997','13892019','13890488','13889742','13889577','13891265','13890709','13890534','13890785','13890805','13892772','13892614','13892189','13892438','13892242','13892466','13892269','13891917','13891923','13891489','13891523','13892918','13892523','13892762','13890596','13890659','13890665','13890671','13890216','13890229','13890060','13887872','13887670','13887338','13887742','13887544','13887758','13887764','26282876','13888255','13888090','13888121','26333613','26337113','26334622','26337326','26299638','26321198','26323782','26323848','26321880','26324250','26298899','26299183','26341744','26343495','14571623','12479875','36118200','36168841','13892416','8389304','8389653','9154993','5309852','5312351','5311538','5313716','4014681','6397057','6397271','5648303','5650778','5656811','5658153','5669550','8185215','9154601','9154606','9154613','4202605','9154669','36273379','36294987','36285754','36236445','36196576','36235057','4276980','4277055','4277060','4202448','4198766','4203232','4199154','4199195','4277203','4276075','6397071','6342544','6342742','6344611','6346855','6336967','6339761','31888312','31386950','31790911','31889347','30172553','30870368','25999526','25999979','19653744','19619963','18920135','18920410','19040238','14737348','14668633','14609013','14609005','14609000','13001311','36176332','36116825','18907181','18905140','18905200','18006190','18906030','18904106','17054501','16593911','15336563','15339917',
 '15339948','31890659','31891422','32475401','33188600','27510727','28216396','28076731','14668525','14668532','14668551','14668563','14668589','14608994','15191386','14668648','14668651','14668518','36122285','9729095','9729098','9729108','9728878','9729113','9728894','9728904','9729117','12693006','12753825','10153896','9201226','9729216','8612618','8693725','8681943','10185350','10185985','10285269','10283675','9965113','9999668','9965124','9965125','9965131','9965140','9964226','9966196','9964584','9994569','9994592','9995036','9965101','9965106','9964615','22887255','24345390','23917310','23540633','22595415','18914321','18916059','18916969','18914103','21220953','20843070','20845043','21158182','21976624','21672873','21282130','21283552','18917526','18918193','18918240','18918517','18918548','18918555','18919204','18919212','18920456','9785872','9820144','9820146','9812276','9812282','9812291','9830503','9840971','9820117','9812251','9812258','9812261','9809180','9830461','9820166','9820170','9820172','9830395','9918782','9880211','9880215','9880218','9880228','9941655','9917970','9918723','9916361','9918745','9786877','9786922','9786927','9786937','9785807','9785818','9785823','9785790','9785794','9786842','9785778','9786852','4277081','36297494','9807336','14668519','9807339','9807373','9807356','9807346','9807342','11897510','11831429','11829276','11900856','11900869','11830277','11790618','18916495','18916501','18916544','22153385','11897517','10322513','10323367','9785800','9830474','9755847','9755846','9755848','9841273','9785820','9755861','9785784','9786885','9965138','14668489','11900861','9785812','9755852','9830427','9755851','11897519','11904608','11897492','36217967','9782463','9782470','18005539','9792968','9792952','9792925','9792959','9792945','9792971','9792894','9792913','9792880','9792923','9792897','9792900','9792908','9792910','20838351','18920325','18917433','9792990','9964269','8574329','8574331','8574423','8570175','8570655','8570492','8569491','8569496','8569500','8569266','8569289','8569846','8569858','8569867','8569473','8569602','8569487','8570508','8570314','8570322','8570227','8569207','8569077','8569215','8570295','8584019','8583735','8583082','8583087','8583230','8584499','8576848','8576849','8576856','8576857','8576859','8576860','8576735','8576872','8576822','8576823','8576768','8576769','8576771','8576772','8576947','8576972','8576842','8576995','8577149','8577129','8577233','8567687','8567423','8567698','8567699','8567592','8567603','8567184','8567363','8567044','8566899','8566901','8567073','8567077','8566015','8567130','8567028','8567033','8567035','8567749','8567861','8567763','8567765','8567772','8567630','8567784','8567634','8567788','8567789','8567662','8567666','8567669','8567398','8566255','8576426','8575764','8575629','8575630','8575639','8564491','8564773','8564786','8564981','8564984','8565158','8565770','8565786','8565673','8565679','8565617','8580882','8580955','8580667','8580342','8579853','8579917','8579983','8579859','8579861','8579862','8579934','8579944','8579595','8579663','8579596','8579375','8579455','8579456','8579951','8579954','8579956','8579627','8582849','8582868','8582873','8582574','8582595','8582600','8581859','8581877','8581879','8582068','8582884','8582887','8582888','8583361','8583369','8583295','8583471','8581854','8581770','8581582','8581510','8584254','8584259','8584082','8584088','8574244','8573773','8573779','8573984','8573988','8573934','8573957','8573958','8573968','8573745','8574346','8574253','8574256','8573541','8568402','8568417','8568141','8568143','8568154','8568167','8568183','8568080','8568343','8568560','8568717','8568723','8568114','8568116','8568220','8568126','8568127','8567876','8567878','8567883','8567802','8567809','8567813','8567815','8567704','8567825','8567932','8567712','8567838','8567724','8567725','8567727','8567731','8567850','8567851','8567854','8568997','8568998','8569002','8568886','8568893','8568901','8568975','8568977','8568859','8568679','8568692','8568696','8568559','8568962','8569055','8572594','8576133','8576137','8576138','8576190','8576195','8573491','8573515','8573533','8573396','8573399','8572547','8572550','8572567','8572574','8572453','8572194','8572225','8574734','8576092','8576099','8575925','8575933','8575935','8575936','8575937','8574903','8574907','8574830','8574776','8553173','8553177','8553042','8553187','8553192','8553107','8553109','8553117','8553362','8553125','8553163','8553167','8552792','8552899','8552801','8552812','8552814','8553649','8553299','8553454','8554127','8553665','8553906','8553768','8553668','8553688','8553787','8553694','8553987','8554006','8554012','8553734','8552830','8552834','8552836','8552659','8562593','8562595','8552771','8552772','8552785','8552522','8552645','8552652','8552151','8552050','8552082','8552086','8551969','8552113','8551991','8551993','8552007','8551816','8552017','8551820','8552020','8551823','8551825','8552025','8551694','8552601','8552752','8563248','8563044','8563048','8563050','8556784','8556955','8556958','8556789','8556801','8556966','8556694','8556435','8556761','8556546','8556680','8557255','8557100','8556923','8556943','8556949','8557151','8557273','8557054','8557064','8556974','8557076','8556989','8557087','8557092','8557095','8556387','8564279','8564280','8564092','8563942','8564314','8564190','8563810','8563812','8563816','8555021','8555002','8555014','8555472','8555473','8555474','8555478','8555488','8554423','8554430','8554208','8554382','8554383','8554387','8555163','8573140','8573146','8573030','8573059','8571445','8571600','8570720','8570742','8570947','8562980','8562681','8562908','8561180','8560799','8560813','8560691','8560718','8560740','8560178','8560060','8560411','8560415','8560418','8560132','8560252','8560161','8560265','8559958','8559967','8559855','8559973','8559834','8559841','8559945','8563295','8563310','8557806','8557821','8557823','8557640','8557749','8558265','8558274','8558506','8558597','8558355','8557906','8557914','8557882','8558039','8561475','8561478','8561368','8561370','8562247','8562172','8562204','8561658','8562029','8561963','8562934','8562743','8562750','8562758','8562761','8562961','8562768','8562780','8562917','8562926','8563674','8563915','8559316','8559321','8559325','8559640','8558791','8558919','8558929','8558930','8559511','8549658','8549662','8549681','8549690','8549718','8549535','8581882','8581893','8581903','8581904','8581911','8581915','8581926','8581938','8581050','8581142','8581082','8580855','8580856','8580861','8581218','8581219','8580151','8580156','8578041','8577930','8577594','8577525','8577615','8577687','8577630','8577632','8577633','8577881','8577882','8577562','8581221','8581222','8577941','8578346','8578165','8578171','8578506','8578523','8578329','8578267','8578528','8578763','8578764','8578765','8578538','8580631','8580637','8580842','8579011','8578729','8578746','8578748','8578753','8579330','8579334','8579335','8579336','8579337','8579338','8579339','8579348','8579351','8579352','8578776','8578780','8578784','8578787','8578835','8578839','8578773','8584477','8584491','8576202','8576153','8576155','8576158','8563357','8556089','8555646','8555531','8555458','8555463','8555464','8555587','8555588','8555611','8555806','8555809','8556060','8551448','8551452','8550675','8550689','8551408','8551433','8550799','8550756','8551801','8551667','8551809','8551810','8551682','8551063','8550934','8551084','8551162','8551165','8550977','8550985','8549025','8549029','8549046','8549163','8549050','8546381','8546301','8546879','8546798','8546799','8546800','8546801','8546814','8546192','8546203','8546222','8546223','8546227','8546157','8546158','8546674','8546605','8546620','8546626','8546831','8546832','8546834','8546836','8546841','8546742','8546848','8546851','8546854','8546855','8546690','8546760','8546763','8546775','8546313','8546318','8546179','8546777','8546778','8546650','8546656','8546164','8546165','8546168','8546084','8546177','8550067','8550222','8550096','8545377','8545232','8545992','8545997','8545859','8545863','8545866','8545867','8545869','8546012','8545182','8545187','8545189','8545191','8545194','8545195','8545198','8545412','8545413','8545414','8545415','8545416','8545419','8545842','8545858','8545644','8545577','8545018','8544914','8545040','8545075','8547898','8548072','8547920','8548434','8550206','8550651','8548588','8548590','8548594','8548513','8548514','8548515','8548599','8548606','8548452','8547978','8547743','8548481','8548486','8548487','8548501','8548502','8548413','8547687','8547766','8548639','8548643','8548650','8548663','8548666','8548670','8549549','8548767','8548689','8548777','8548786','8544838','8544848','8544762','8544693','8544514','8544516','8544426','8544431','8544525','8544433','8544437','8544438','8544537','8544443','8544444','8544453','8544462','8544472','8544397','8544267','8539328','8539424','8538959','8540497','8540608','8545603','8578320','8583081','8558570','8541148','8541443','8538664','8538755','8537314','8537085','8536694','8539657','8539469','8539491','8539859','8550031','8549962','8549891','8549392','8549275','8549802','8549806','8549809','8549752','8549778','8549779','8548931','8548808','8548632','8547484','8547259','8547331','8547308','8547148','8547107','8547712','8547725','8547673','8547675','8547679','8547124','8547142','8547267','8547270','8547272','8547273','8547513','8577703','8549762','8579638','36197012','36205320','33995128','34028229','34031317','36318199','36346378','36190270','31343046','31392588','33982439','27612774','27957466','27876635','27557229','27422463','27585011','27535114','27419762','8537010','29194409','28763471','27883145','8537951','8537960','8537523','8537780','8537345','8537613','8538586','8544874','8544790','8544803','8544805','8544811','8544905','8538789','8538795','8540530','8537421','8549761','10208130','10208107','27755548','18914509','18919862','9782457','8555799','8548681','8576306','8576304',
 '8576301','8576287','8576816','8552181','8567790','8556559','8550679','8556556','8556555','8577713','8550879','8546029','8546033','8546035','8546039','8546042','8546052','8575883','8550862','8550865','8550851','8544340','8544343','8580189','8579830','11167238','14488172','14491714','8558572','8552839','18916392','18918809','8544470','9782497','8576279','8576294','28489346','8555026','8555027','8555030','8555060','8557294','8557308','8551423','24458539','24456780','36226070','36226203','8545846','8578294','8578298','8584522','8584523','8568311','8544346','8537427','8553407','8553437','8544631','8544624','8544621','8544632','8544648','8544649','8544644','8576687','8548652','8570116','8570117','8570121','8544659','8544666','8548475','8547691','28228578','8547695','11579834','8008120','36214816','10945830','18098344','14296028','14295998','14296006','14268725','14281903','14295994','14646228','14646211','14767094','14767069','14767075','14602267','18515682','14021560','14021572','14021578','14021579','14021584','13939195','14652374','14079597','14021482','14079603','14021486','14021489','14078076','14021494','14079609','14080337','14078097','14078098','14021537','14021544','13939342','14005216','13939221','14005220','13939325','13701287','13701289','14078099','14078122','14080340','14134862','14078138','14054035','14079577','13013380','36338498','12311304','12311437','12327519','32516965','28436824','13758602','13939271','13939205','3602030','3611851','3616121','12327269','13014677','5498484','5497274','5497334','5497113','5492806','31815867','30981532','30981830','30926048','36123937','36123639','36123650','36123656','36123673','36102892','36114929','27959200','19542482','27961194','24925985','28217265','36292554','36128095','36129072','36129094','36124843','11608423','10265794','10913779','10914029','10498183','10501663','10501667','10501607','10501616','10498165','11636900','11609981','11641726','11642072','11642394','11091336','10501632','10498218','9262335','10914605','9280193','9280315','9022453','9275882','36266363','36122632','9262607','11599240','11599266','11599282','11599076','11254589','11599097','11599107','11599112','11599125','11599132','11599230','36129075','36125244','36122633','16510058','16392752','16392861','16392871','16392898','16392266','16371112','16370209','16370626','16511024','16510769','16511120','16511131','16511136','36103521','16369287','16369321','16326238','16369390','16369409','16369436','16369267','16369567','16326068','15737330','15737341','15737188','15736819','15593194','15593224','15593240','15593472','12452769','12445144','12480695','12445720','12453531','12445940','12480928','12446388','12446535','12446637','12499183','12499240','12572740','12575778','12575936','12500269','12576253','12500698','12576871','12577398','12019903','12019933','12021250','12021260','12021265','11975414','11975429','12013426','12013449','12028845','12444445','12380702','12380736','12381136','12381367','12029132','12029886','12013979','12168011','12029908','12029924','12017915','12017922','12018342','12030675','12030714','12018594','12030830','12019582','12384129','12577797','15736030','15736043','15736064','15736085','15736089','12871281','12871360','12803154','12871391','12871482','12803412','12871517','12803535','12841726','12841745','12688149','12768607','12694688','12694704','12769216','12578925','12596561','12598434','12684882','12649337','12753555','12652468','12652500','12652542','12653025','12653083','12686746','12630933','12769302','12691378','12692429','12773788','12632438','12632485','12655033','12645814','12655167','12646801','12647096','12647478','12647512','12647874','12712195','12684633','12715699','12684656','12578501','12578592','12653364','15594247','15593896','15736689','15736573','15736275','16510968','16510214','16510220','16510227','16510234','16510249','12773931','12773998','12774168','12774223','12774476','12775838','12776040','12776183','12801181','14740456','14740084','14740103','14603546','14740150','14603573','14603579','14603590','14740057','14603600','11901014','11906433','14740069','14489133','14489138','14489152','14489154','14502593','14489163','14573022','14489165','14573025','14489168','14492135','14488103','14488105','12879825','13316754','14489123','13377933','11782670','11782707','11784728','11787693','11790362','11761116','11761121','11762250','11762413','11762587','11740826','11740929','11741097','11762832','11762986','14740937','14741041','14741061','14740770','11760794','11761027','11307353','11738176','11738492','11738577','11740321','11740413','11740551','11193103','11195464','11195595','11195597','11962763','11973722','11963172','11973874','11963598','11968684','11964890','11964898','11968871','11974127','11964900','11964927','11974135','11965654','11966104','15736452','11175073','11149577','11149596','11149620','11149658','11149687','11149716','11149882','11149905','11149925','11150001','11150036','11150114','11150160','11150229','11148794','11148981','11763123','11742178','11763365','11763368','11742407','11763588','11742450','11763667','11766049','11743588','11743723','11769899','11744227','11744361','11515575','11770102','11515612','11770293','11515759','11756093','11756836','11704757','11710580','11083366','11084262','11086627','11086789','11086827','11084785','11089767','11089805','11084961','11085018','11085125','11085326','11090158','11085748','11082869','11966420','11941127','11941166','11942543','11939838','11942636','11932716','11932726','11942931','11961015','11961074','11940314','11195614','11303756','11191196','11191264','11192568','11192585','11192720','11192737','11306738','11192856','11192932','11192957','11973068','11966806','11966885','11966906','11962372','11968097','11961199','11932806','16369166','36120738','36236669','16512348','16512166','15593521','15593359','15593363','15593367','15593185','15737240','15736883','15736648','15736513','15736523','15593994','15736711','14819185','14818970','14819029','15143221','15151421','14818495','14818303','14818121','14818319','14818321','14818330','14818348','14818352','14741204','14818147','14818151','14818175','14818181','14818187','14818192','14818212','14818223','14818225','14818429','14818237','14818432','14818024','14818253','14818261','14818045','14818054','14818266','14818060','14818268','14818475','14818477','14818283','14818093','14818097','14818294','14818102','14818299','14818300','14741134','14817976','14817978','14818004','14741182','14741186','15593145','15593160','15593163','15736029','15708052','15594162','14818519','14818751','14818770','14818771','14818783','14818786','14818790','14818354','14818356','14818360','14818368','14818644','14818648','11149065','11149129','11149162','11149233','11149260','11149309','11149473','11125853','11126520','11126720','11143278','11961743','11940480','11962049','11940502','11940530','11935364','11940583','11940600','11940697','11940824','11935797','11937584','11937830','11937867','11938129','11938155','11938360','11900994','14819105','14818672','14818678','14818696','14818717','14740392','14740233','15736102','15736241','15736254','15708184','11780825','11781471','11782028','11082013','11122477','13905360','5283355','5283746','5284899','36317238','5296869','5280693','5282139','6158305','6158547','7770447','7784850','7816292','6128986','6129194','6159529','6140102','6140276','6141336','6141822','6141950','6126511','24800200','36118834','29429677','29429791','29430020','29968463','29429126','14346897','14238943','33729732','32636329','28630099','22803420','23850127','34776645','14347003','14329277','14345452','14239892','14227982','26806776','26806850','26807297','26807592','26806708','17672395','17672592','17672620','17672291','17672302','21707882','21708623','14347035','14347048','14347034','14346950','13619469','36109021','17670500','17671096','17671098','17670280','17670604','13267764','13085103','13085029','13085193','17670129','17669912','17670406','17665567','17665601','17665084','17665453','5286830','5286935','22310833','22353412','17664892','14329301','12958601','12952773','12933104','36220182','7715092','7755324','7816105','7816181','5287210','17670337','12451561','12444155','12444162','12021453','12415877','12416079','12416752','10788831','10795266','10125888','10125896','12374131','12374376','12375718','10805492','10860395','5285530','5287439','5277641','5285896','5286027','5277798','5277854','5278052','8485405','6142221','6157995','6158656','5296698','5293398','5297134','5295156','5293416','5293668','5297238','5293751','36125486','36125474','23280167','13240568','13240881','13385840','5268635','6129007','6129037','6139377','6139410','6141153','6129634','6141309','6141330','6141364','6136196','6136421','6141718','17665189','12066236','5296366','5294071','5267556','17671463','14346987','17671858','17671117','17665217','17670635','7086404','17664880','17672732','17664702','17664914','17670889','5269787','11793659','36179923','20659956','25177253','25177915','11793310','5299154','5293921','5293940','5286878','5286974','5287284','5287358','23280710','23280724','36125481','6141869','6136818','6126926','5299564','36141422','36144742','36117075','35846193','35846393','35906404','36282256','36262621','36273173','36273332','36154693','36232363','25655605','5384293','5384378','8334855','17672635','17671466','17670268','5269611','17670716','5421566','5382757','10805381','36125515','36300587','5754936','5742454','8096590','3883108','2471016','8390216','8389828','3872165','3873041','2470963','3881994','36295094','36235359','36204961','9139925','9140009','9140042','9140044','9139844','9140055','10236575','9139839','9752926','14621979','14622014','34070280','34430406','31494620','30424934','13559392','13659315','13560515','13560542','13696104','13697728','13408717','13550218','13068211','13068224','15029314','15031012','15018205','15032234','15018208','15068643','15068653','15068655','15068659','15068627','15068630','15029255','15065648',
 '15065652','15067951','15029295','15029250','15029251','15379751','23544382','22884811','23096173','14892910','17538424','19463090','14900753','14900751','22143243','22275917','22318314','14892246','13582790','13559376','13559371','13582825','13719837','13529763','13659359','13659345','13498780','13520571','12803700','12476169','12476610','12457456','12479681','12451812','12451972','12452322','12457437','12453599','12691713','12691716','12691719','12691747','12415241','12416666','12386265','12335396','12335974','12336959','12337079','12337256','11975285','13500351','13180310','13158120','13183622','13072443','13067947','13067966','13063640','12423863','12421603','12424661','12424826','12425342','12339863','12376739','12340333','12420249','12414507','12414514','12336160','12421887','12423233','12380707','12378617','11967163','11967553','11967928','11968125','11908384','11908508','11908529','11908571','11909457','11973560','11900529','36126350','36126348','36118188','36118215','36143507','36137750','36164175','14627656','14621985','14665104','14672971','14651978','14583894','14460058','14328912','14673009','14665134','14580855','14569677','14502624','14665575','14651985','14665570','14651981','14665553','14665551','14645365','14342532','14739553','14673001','14665115','14651964','14605895','14605912','14605911','14605902','14605904','14504313','14504303','14504308','14342555','14652952','14627640','14627632','14605926','14605894','14605945','14536910','14439272','14439266','14439270','14712334','14762638','14665111','14651943','14622017','14622011','14622009','14583866','14328935','11599978','11653684','11653825','11653855','11653954','11654352','11654569','11655209','11635067','11635100','11638065','11635530','11632134','11635875','11635903','11633010','11609401','11609581','11634400','11610156','11610199','11610260','11610568','11639923','11640188','11636556','11634829','11636693','11656529','11653398','11866108','11867168','11870154','11871305','11835282','11865527','11149140','11166211','13500350','13500390','36280828','13085927','33093853','14708566','14708581','14708585','14650045','14650047','14708599','14708610','14708613','14708618','14708627','14647505','14704397','14536892','13520576','14712340','13665191','13579837','13369156','13698077','13699269','13699270','13689572','13609164','13656093','15015057','15015066','15023225','15023270','15023313','15023319','15023327','13369185','13368949','13368968','13369200','13369209','13369231','13369044','13369073','13369078','14432230','14465970','14431488','14431494','14431499','14431500','14431501','14465999','14432207','13369116','13369121','13369126','13369128','13369142','13369143','14643663','14738974','14624318','14643645','13034305','14536906','12498727','13500404','15015038','15015049','15583141','15014994','14941155','14941165','14704429','14704443','14647538','14647514','14786826','14740269','14740271','14740289','14740250','14325730','14281777','14281779','13034287','13034466','13034470','13320075','12713618','12713636','14391780','14394998','14431480','14395006','14325692','14276528','12453573','12453578','12421798','12421812','12337624','12479831','12479834','12479867','12480209','12597791','12337644','12337655','12448113','14823787','13659360','13041940','14823789','15068594','15068613','15068621','15068623','12686343','11973420','36222474','15029284','12387207','12457638','12419942','14571584','14571644','14571630','14571614','14571600','14571595','14571606','14432228','12337662','14645383','12691744','14645427','14814045','14573178','36199794','13085924','13085910','14577531','13313274','14577534','12451750','9736762','11633198','14577553','8113835','12450972','13659393','14647516','13889187','13888886','13888634','13888997','13889018','13888729','13888740','36259999','13891557','13890884','13891352','13891371','13891373','13890922','13891396','13891427','13887780','13887607','13887246','13887270','13887172','13888155','13887971','13887792','13888037','13888046','36222454','13408938','13893986','13894006','13893166','13893362','13893212','13893785','13893834','13893893','13892992','13892812','13893025','13892831','13892852','13893099','13887093','13886707','13886725','13886936','13886764','13886768','26333280','13892915','13888473','13887182','13887311','13887201','13886621','13886818','13886870','13889267','13889620','13889126','15429530','26265923','26266575','26275940','26234087','26262312','26227180','26234107','26234137','26226494','24749677','24750200','24750524','26279747','26279915','26281120','26276939','26277007','26283468','14580880','13892332','13891798','13891809','13891977','13890103','13890120','13890177','13890003','13889754','13890705','13890790','13892354','13892402','13892170','13892245','13892461','13892049','13891907','13892060','13892145','13891690','13891517','13891720','13892692','13892700','13892543','13892552','13892758','13890827','13890843','13887900','13887911','13887916','13887724','13887748','13887767','13887570','26282525','26283098','26279572','12498724','13888429','13888211','13888107','13888302','26333126','26339344','26336962','26298293','26300312','26300386','26332168','26323688','26332214','26323817','26332783','26333109','26295875','26345704','26343942','13887807','13887188','10236341','35372092','36179324','11640766','14244919','26341301','36347218','36228140','13520566','14903521','9155019','9154536','5310281','5311576','5313332','5346437','6396834','5650231','5656607','4277085','2301831','9154595','9154617','8117455','4174524','36289489','36267914','36214761','36257723','36225768','36195098','4272158','4201692','4201714','4202409','4131206','4243370','4199139','4199159','4199305','4201651','4243175','4130951','4130961','4277180','2301735','6344797','6345031','6345197','6397045','6338330','6338786','6350534','6341453','6395777','29108254','31888850','31888866','31888879','31339107','31888463','31888475','31790266','31790325','30869829','30390717','29567753','25999918','19685589','22009569','21966394','22357700','19651135','18920155','18920384','18919544','18919552','18967728','18969822','14609009','13001913','17378454','17378471','15802142','16276980','18907245','18907256','18907236','18903804','18005526','18902707','18904295','16592711','15708230','15708783','15339944','34425050','28215058','14737361','14737282','14737390','14737342','14737323','14668546','14668541','14668597','14490913','14668616','14222087','36132656','31893039','9729099','9729101','9729115','9729116','9728910','10153375','10153792','10136500','9728916','9728922','8613042','9728933','8683077','11865010','10185434','10186423','10186632','10321418','10320837','9965128','9965133','9965145','9963656','9963659','9964231','9965730','9964243','9966195','9963630','9964250','9964251','9964267','9964579','9964582','9964586','9964639','9964647','9999660','9964594','9964602','9964605','22888911','23014508','24665828','24346992','24347387','23918041','24422652','24344709','22594384','22594620','22595278','22594259','18914838','18914064','18915860','18915572','18916642','18916865','18916305','20831738','20705107','20527198','21399567','21976163','20542275','18918623','18918191','18919929','18919678','18917414','18917507','18919271','18919215','18919976','18919464','9812265','9812266','9812288','9812292','9830425','9820121','9809273','9809274','9812262','9809236','9842129','9830445','9820165','9830400','9830406','9809182','9941627','9941632','9880212','9916380','9915729','9917983','9918018','9879657','9879660','9879664','9786860','9786868','9786886','9786891','9786892','9786897','9786899','9786907','9785822','9785829','9785856','9786827','9786830','9786834','9786854','9785780','9785802','21398003','4205074','18916291','18903665','9830403','9915734','9751623','28161423','11897518','11829228','11829243','11900857','11830269','11830288','18916517','18916532','11831412','9755854','9782454','9782503','9782499','9782508','9782517','36168676','22508087','9941601','19650360','11831417','9782514','9782526','9840973','11831418','15339958','9755858','9941668','9830450','9841002','9755862','9755866','9755865','9782544','11906117','9792941','9792879','9792931','9792992','9792911','9792891','9792917','9792919','9792991','31889383','9792984','18919962','8574545','8574335','8574336','8570640','8570550','8570561','8570491','8569498','8569840','8569861','8569739','8569714','8569601','8569486','8569605','8569488','8570519','8570308','8570319','8570228','8569322','8569213','8569221','8569248','8568980','8568984','8568986','8583992','8583999','8584189','8584046','8583724','8583348','8583350','8584498','8584506','8584211','8584213','8584231','8584245','8576731','8576855','8576858','8576861','8576733','8576740','8576741','8576742','8576747','8576757','8576758','8576824','8576766','8576681','8576952','8576974','8576982','8576986','8577000','8577006','8577014','8577019','8577096','8577021','8577255','8577118','8577127','8577211','8577218','8577236','8577242','8577238','8577240','8567690','8567420','8575546','8567099','8567118','8566694','8566698','8566701','8567062','8567067','8567040','8567042','8566686','8567752','8567754','8567759','8567617','8567774','8567776','8567635','8567787','8567636','8567793','8567663','8566246','8566254','8566125','8566627','8566645','8566585','8566591','8576427','8575767','8575619','8575772','8576257','8576267','8576268','8564325','8564908','8564919','8564947','8564949','8564952','8564955','8564958','8564961','8564963','8564966','8564967','8564970','8565285','8565609','8565612','8565619','8564548','8564371','8581512','8581526','8581531','8580878','8580880','8580881','8580957','8580672','8580339','8580343','8580206','8580115','8580116','8579967','8579920','8579858','8579937','8579945','8579598','8579670','8579604','8579674','8579257','8579950','8579953','8582901','8582902','8582751','8582857','8582875','8582161','8582162','8581948','8582037','8581860','8581871','8581876','8581880','8581831','8583220','8582731','8582898','8583442','8583275','8583287','8583489','8581775','8581502','8584308','8584440','8584134','8584155','8584161','8582208','8582349','8582237','8575336','8575952','8575961','8575962','8575963','8574561','8574141','8574223','8574233','8573775','8573777','8573780','8573781','8573792','8573794','8574190','8573972','8573973','8573974','8573976','8573977','8573980','8573982','8573983','8573989','8574026','8573955','8573956','8573959','8573961','8573963','8573969','8573752','8573753','8573755','8574435','8574351','8574275','8574201','8574132','8574208','8573488','8571120','8571043','8568320','8568321','8568145','8568329','8568158','8568072','8568078','8568088','8568094','8568342','8568239','8568357','8568248','8568259','8568271','8568541','8568277','8568282','8568395','8568401','8568716','8568718','8568104','8568110','8568111','8568216','8568129','8567888','8567794','8567795','8567803','8567806','8567807','8567808','8567817','8567821','8567832','8567715','8567952','8567842','8567843','8567722','8567960','8567848','8567733','8567737','8567738','8568992','8568879','8568884','8568895','8568905','8568856','8568861','8568688','8568701','8568553','8568707','8568954','8568971','8572588','8572589','8572592','8572727','8576125','8576134','8576139','8576142','8573498','8573514','8573531','8573389','8573391','8573395','8573253','8572684','8572533','8572540','8572542','8572578','8572313','8576536','8576543','8576095','8576097','8575848','8575918','8575863','8575926','8575927','8575932','8575934','8575939','8575941','8575943','8575118','8575074','8574901','8574905','8574906','8574911','8574912','8564401','8564218','8564222','8553036','8553172','8553175','8553043','8553179','8553180','8553181','8553182','8553183','8553184','8553189','8553069','8553204','8553205','8553119','8553217','8553230','8553238','8553243','8553369','8553379','8553264','8553151','8553153','8553156','8552892','8552893','8552807','8552815','8553651','8553655','8553551','8553298','8553313','8553466','8554014','8554024','8553656','8553894','8553658','8553661','8553664','8553669','8553673','8553678','8553681','8553684','8553695','8553697','8553812','8553964','8553994','8554003','8553857','8553730','8553389','8552824','8552831','8552930','8552832','8552835','8552663','8552670','8552512','8552783','8552641','8552653','8552027','8552170','8552176','8552101','8551986','8551987','8551992','8551999','8552013','8551818','8552018','8551819','8552019','8552021','8551829','8552116','8552130','8552134','8552135','8551831','8551835','8551947','8551949','8551838','8551693','8551706','8551707','8552487','8552494','8552501','8552473','8552751','8552605','8552759','8563111','8563029','8563045','8563051','8556782','8556785','8556961','8556963','8556713','8556421','8556451','8556458','8556463','8556740','8556517','8556519','8556530','8556532','8556534','8556544','8556415','8557617','8557291','8557233','8557244','8557254','8557144','8557096','8557099','8556891','8556911','8556912','8556927','8556931','8556936','8557261','8557152','8557280','8557046','8557055','8557072','8557075','8556994','8557089','8556995','8557091','8556997','8556377','8564116','8563945','8564157','8563814','8555018','8554928','8555198','8555467','8555487','8555503','8555385','8554312','8554364','8554249','8554477','8554384','8555331','8554711','8554722','8554729','8554751
 ','8554459','8571482','8571376','8571404','8571114','8573085','8573009','8573122','8573031','8573034','8573036','8573040','8573043','8573180','8573063','8573194','8571459','8571770','8571938','8570717','8570979','8570980','8570753','8570624','8570911','8570913','8570928','8570695','8570955','8570711','8571880','8572006','8571844','8562976','8562680','8562899','8562900','8562700','8562707','8562725','8562564','8562726','8562459','8561199','8561201','8560710','8561374','8561236','8560781','8560783','8560757','8560767','8560768','8560004','8560414','8560417','8560298','8560322','8560327','8560329','8560337','8560354','8560226','8560233','8560138','8560245','8560155','8560164','8560264','8560176','8559964','8559757','8559830','8559942','8563298','8563306','8563311','8563316','8557808','8557651','8558259','8558260','8558262','8558271','8558272','8558283','8558288','8558292','8558659','8558515','8558770','8558442','8558455','8557781','8557782','8557800','8558057','8558092','8561457','8561346','8561473','8561358','8561367','8562046','8562170','8562452','8561526','8562034','8562040','8561674','8561965','8561832','8562740','8562945','8562957','8562762','8562772','8562088','8562090','8562093','8561997','8562920','8563863','8563869','8563910','8559172','8559311','8559314','8559318','8559565','8558790','8559328','8559330','8559333','8559334','8559091','8559607','8559160','8558942','8558953','8549660','8549661','8549758','8549676','8549774','8581883','8581884','8581986','8581891','8582081','8582082','8581896','8581900','8581906','8581914','8581916','8581919','8581928','8581929','8581932','8581935','8582878','8580460','8581043','8581045','8581052','8581198','8581201','8581080','8581085','8580850','8580147','8580148','8580155','8577910','8577912','8577989','8577520','8577686','8577624','8577627','8577701','8577887','8577456','8577256','8581226','8581359','8581241','8581314','8578011','8577955','8578026','8578027','8578029','8577972','8578159','8578166','8578168','8578114','8578524','8578769','8578704','8578772','8578530','8578532','8578534','8580612','8580676','8580615','8580691','8580634','8578997','8578999','8579001','8579004','8578745','8578747','8578752','8578754','8578759','8578761','8578762','8578778','8578782','8578783','8578786','8578856','8578717','8579230','8579237','8578830','8578833','8578834','8578838','8578774','8578775','8584468','8584478','8576203','8576208','8576150','8576106','8576107','8563236','8556066','8556073','8556088','8555984','8555635','8555636','8555427','8555869','8555871','8555601','8555619','8555801','8555814','8555830','8551453','8550757','8550677','8550624','8551406','8551415','8551418','8551435','8551440','8551442','8550782','8550785','8550796','8550724','8550800','8550741','8550752','8550754','8550755','8551643','8551645','8551665','8551805','8551668','8551811','8551813','8551676','8551814','8551815','8551685','8551686','8550929','8551064','8551227','8551067','8551079','8551241','8551081','8551082','8551088','8551090','8551096','8551099','8551100','8551101','8551103','8551112','8550978','8551127','8550979','8550980','8550982','8550983','8550984','8550988','8551640','8549028','8549031','8549033','8549039','8549041','8549043','8549047','8549048','8546367','8546369','8546524','8546858','8546859','8546802','8546803','8546805','8546813','8546819','8546820','8546825','8546826','8546191','8546193','8546293','8546221','8546224','8546231','8546159','8546672','8546594','8546602','8546607','8546608','8546616','8546622','8546624','8546828','8546829','8546839','8546845','8546744','8546850','8546747','8546852','8546757','8546762','8546695','8546769','8546699','8546772','8546773','8546233','8546257','8546178','8546259','8546338','8546180','8546184','8546266','8546188','8546782','8546783','8546787','8546789','8546790','8546639','8546795','8546797','8546645','8546734','8546579','8546667','8545969','8545979','8546166','8546167','8546173','8546174','8546175','8550065','8550213','8550215','8550218','8550219','8550220','8550224','8545985','8545860','8545862','8546003','8545868','8546010','8546013','8545873','8546017','8545880','8545181','8545004','8545487','8545422','8545820','8545955','8545826','8545957','8545832','8545962','8545833','8545967','8545839','8545844','8545847','8545854','8545553','8545565','8545581','8545467','8545599','8545607','8545611','8545613','8545011','8545072','8545073','8545601','8545606','8548164','8548419','8548431','8548440','8548441','8550391','8550190','8550191','8550198','8550202','8550207','8550627','8550632','8550707','8550645','8550713','8550714','8550656','8550663','8548591','8548509','8548511','8548596','8548600','8548603','8548530','8548608','8548461','8548540','8548620','8548542','8548621','8548543','8548544','8548470','8547867','8547818','8547740','8547745','8548471','8548478','8548479','8548484','8548403','8547840','8547686','8547775','8547701','8547709','8548725','8548645','8548739','8548659','8548569','8548577','8548667','8548586','8544544','8549536','8549537','8549538','8549539','8549627','8549629','8549548','8549640','8549641','8549492','8548119','8548121','8548130','8548958','8548864','8548688','8548695','8548783','8548790','8548793','8548707','8544839','8544840','8544843','8544844','8544846','8544850','8544777','8544668','8544495','8544499','8544503','8544505','8544506','8544507','8544509','8544512','8544517','8544422','8544519','8544424','8544520','8544425','8544521','8544522','8544523','8544527','8544528','8544435','8544436','8544530','8544531','8544439','8544440','8544535','8544536','8544442','8544538','8544447','8544450','8544460','8544407','8544408','8544272','8544414','8544417','8539360','8539155','8539199','8540153','8540160','8540377','8540391','8545580','4131035','8558574','8544302','8538086','8538106','8537079','8539482','8540023','8539596','8549225','8549209','8549795','8549800','8549801','8549808','8549731','8549737','8549738','8549744','8549745','8549652','8549655','8549775','8549786','8549860','8549788','8550441','8550447','8550525','8550459','8548818','8548925','8548711','8548802','8548803','8548804','8548809','8548721','8548949','8549417','8549459','8547323','8547327','8547335','8547187','8547189','8547191','8547116','8547666','8547737','8547738','8547670','8547676','8547677','8547678','8547680','8547682','8547685','8547126','8547130','8547346','8547348','8547349','8547353','8547212','8547214','8547359','8547604','8547510','8547614','8547520','8547433','8555525','8579640','8559486','36197008',
 '36205290','34001916','34019684','34024625','8576109','34028856','34029755','36109173','33995857','33997115','31392511','31545811','27613352','27623054','27627805','28698994','29141139','27479040','27612168','8541093','8536721','8536505','8536814','8536559','8541277','8540849','8541040','8540672','30040336','27879866','9782495','8537968','8537510','8538157','8544872','8544876','8544878','8544883','8544889','8544890','8544893','8544894','8544809','8544899','8544813','8544902','8544818','8544903','8544904','8544820','8544821','8544909','8544827','8544831','8539003','8538827','8537655','8537501','8537621','9782485','8573505','9782483','8571962','8550089','8576290','8576292','8576291','8544708','8550867','11167017','8550870','8550873','8550875','8546028','8550880','8546030','8550882','8546031','8550883','8546034','8546043','8546053','8575871','8575904','8551926','8546718','8546054','8550855','8550856','8550859','8544322','8544323','8544327','8544329','8544336','8580187','8580194','8579840','4198884','8580368','8554176','8580367','8537634','8550446','8552840','18915303','18918350','18918385','18917613','18918714','18918963','18915310','8576284','8539924','8576278','8576297','8576299','36269175','36268220','25316601','8555024','8555038','8555055','8555059','8555063','8557707','8576370','8580447','8545032','8569190','8556947','33993723','21398068','8580914','8579845','8578973','8578293','8579076','8578295','8578302','8579091','8558416','8584510','8579104','8579107','8555044','8557348','8555050','8562358','8544345','8544349','8544350','8536737','8553404','8553410','8553412','8553423','8553438','8553441','18918179','8570093','8544629','8544623','8544650','8576689','8570127','8570118','23917416','8553397','9785835','8547689','36262853','8547693','8008410','36279970','36304142','36307824','36304360','36338481','36201598','36215693','36214434','11739306','10945891','14295973','14281846','18099181','14646231','14767065','18515742','13939196','14079598','14079606','14078083','14021531','21389715','13701282','13701284','13758246','13700151','13700183','13700261','14134884','14134869','14078129','14079582','14079589','14079596','13013366','13013379','13013444','22805802','13701255','13701265','13701266','13001072','36213536','36214818','36211985','36215759','21173894','3597906','3600289','3618738','3606458','3627105','8763374','8797283','3621475','6898020','6898766','5499412','5498624','5499018','5499087','5499240','5492923','36254638','36226533','36226574','36226524','36249665','33308685','33035878','33045604','32667400','36123659','36123671','36118595','35868708','36101864','36126186','36114936','36127983','36127985','27959030','24950955','21491128','21491190','21491546','21490911','27961715','27965443','27965759','27968010','36129107','36129095','36169284','36124849','36124869','36124874','36129077','11536017','11787665','11787790','10274693','10501661','10501669','10274922','36125245','11609815','11610452','11610496','11640373','9262048','9024870','9275929','11610188','36123927','36122636','36126206','36125228','36117998','36127981','11599247','11599261','11599091','11599109','11599121','11599137','11599203','16392778','16392850','16370993','16371041','16392284','16392331','16392456','16371206','16369911','16511467','16369931','16369990','16370038','16370357','16370364','16370061','16370702','16511048','16510771','16510822','16510513','16510857','16510883','16511755','36101855','36105249','16370422','16370153','16369608','16369316','16369337','16369661','16369359','16369364','16369689','16326307','16369725','16369726','16369462','16369475','16369519','16369528','16369280','15738085','15738087','15738090','15738013','16316291','15738147','15738071','15737463','15737392','15737315','15737318','15737320','15737424','15737442','15736989','15737170','15593204','15593215','15593218','15593222','15593231','15593244','15593251','15593283','15593301','12477557','12480089','12480200','12445246','12445295','12445325','12445361','12445669','12445775','12480982','12446335','12455456','12481288','12481352','12481654','12572339','12499448','12572973','12576217','12577016','12577513','12577707','12577760','12033203','12033237','12021601','11975410','12022038','12022375','12028561','11976721','12444516','12028882','12013702','12013729','12030192','12017927','12030463','12018560','12030821','12018800','12018859','12019073','12019079','11972962','12384030','12384641','12578153','15736032','15736046','15736047','15736056','15736060','15736067','15736071','15736091','15736093','12803498','12841717','12803568','12871682','12841729','12841735','12753708','12754393','12578980','12609469','12579928','12599086','12648932','12648999','12684835','12649054','12649214','12684937','12652221','12685125','12652427','12652603','12652653','12630835','12630866','12769391','12769443','12691798','12692292','12712007','12654087','12654161','12654716','12654788','12654824','12655059','12646233','12646343','12655131','12655209','12646528','12646837','12647547','12647629','12647779','12647821','12648039','12648227','12655289','12684551','12653284','12653890','15594228','15594310','15736537','15736430','15736479','16510278','12803698','12774029','12841604','12841610','12841637','12774546','12775871','12775900','12776113','12777092','12841657','12801289','14740086','14740095','14740104','14740120','14740136','14740137','14603593','14739906','14739997','14740049','14578430','14740059','14739939','14740066','14739952','14603599','14739979','14819038','11901004','11901017','11901022','11906458','11906494','11906695','14740074','14579126','14492150','14579143','14579153','14489158','14489161','14600656','14489166','14573026','14573030','14600672','14489171','14573031','14573033','14573040','14443081','14492134','14492139','12871730','14488159','13321040','14489126','14443070','12803596','11782921','11762110','11762277','11762421','11762497','11762507','11762746','14740895','14740718','14740938','14741055','14740762','14740787','14740463','14740475','14740481','14740614','11760772','11760878','11760895','11735148','11761022','11735607','11307503','11307589','11510690','11195562','11511245','11195576','11195586','11195590','11195594','11512077','11973340','11962768','11973627','11963137','11973726','11968653','11968661','11968670','11974027','11965342','11972286','11965350','11965355','11974406','11972410','11965646','11965871','11965930','11965967','11175093','11143476','11149863','11149959','11175411','11763000','11763214','11763228','11763583','11763660','11742504','11763743','11742766','11743501','11743629','11743673','11744284','11688251','11780607','11780665','11760092','11714332','11760425','11729408','11062263','11083263','11083335','11083396','11083428','11083569','11062763','11083847','11083877','11083935','11083971','11064199','11084041','11084176','11084630','11084695','11143455','11119995','11120162','11086730','11086773','11121762','11086899','11086953','11122024','11087051','11089746','11084850','11084943','11084981','11085040','11085064','11085173','11085346','11085677','11125510','11090145','11090196','11125633','11125767','11085928','11090414','11086172','11086234','11086281','11082472','11082490','11082521','11082710','11082952','11966272','11972953','11940917','11940944','11941070','11938645','11938894','11939113','11941257','11942424','11939676','11932681','11932701','11939985','11942765','11932745','11942786','11932783','11960863','11940112','11940330','11195607','11191082','11306446','11306556','11306627','11192758','11306911','11192986','11193032','11966772','11962467','11962668','11968149','11940334','11940377','11961547','11940396','11932817','11149522','11062229','11083112','16369164','16369158','36152464','36120343','36234865','36236954','36176851','16512063','16512435','16512111','36149615','36150098','16595322','15593816','15593189','15593602','15736724','15737238','15737252','15736888','15736760','15736922','15736780','15736614','15736619','15736641','15736382','15736661','14819229','14819259','14819026','14819033','14819035','14818340','14741219','14741221','14818397','14818651','14818399','14818402','14818224','14818230','14818232','14818235','14818241','14818245','14818030','14818451','14818042','14818264','14818464','14818265','14818467','14818470','14818273','14818278','14818082','14818287','14818290','14818489','14741128','14817959','14817964','14817972','14817988','14817990','14817993','14817997','14817999','14818002','14741006','14741013','15593164','15708070','15708088','15594456','14818934','14818516','14818750','14818940','14818756','14818767','14818544','14818796','14818797','14818799','14818804','14818363','14818604','14818364','14818376','14818380','14818385','14818647','36129092','16369236','16369218','11125887','11125916','11125957','11126003','11126111','11126675','11141341','11143229','11143296','11143315','11143359','11940490','11940558','11940642','11962342','11940790','11940851','11937674','11937811','11938234','11938265','11938327','11938431','11900998','14819053','14818818','14818819','14818843','14818665','14819109','14818891','14818723','14818908','14818912','14818501','14740616','14740377','14740202','15736105','15708130','15708140','15708202','11792187','11780924','11781244','11820897','11781990','11782417','11084756','10497663','10497717','5283371','5293887','36310788','5296840','5282930','5283042','6157756','6158260','6127716','5299082','6140136','6140504','6140790','6141433','6141669','6126701','6126851','6126933','36125541','36125543','36145425','25201402','27565781','27624305','24799460','24800153','29428863','30036432','29973635','14054402','14202683','32510605','32813798','28630467','27935700','23738799','22775705','22540416','14339702','14339700','14329278','14240277','26806737','26806957','26807510','26718390','26982096','17672424','17672504','17672336','17672123','17671946','17671564','17672000','17672198','17672524','17672558','17672245','21708542','14346373','14347043','14347445','14346949',
 '14238597','14228302','14228366','34417806','34564300','36109023','34563941','34417309','17671352','17670914','17670698','36118097','36118824','36118830','27624839','17670421','17670164','17670215','17670015','17670018','17664735','17664745','17665259','17669782','17669789','17669557','17665026','17665238','5285772','24799780','24799897','22155998','14346982','14346921','14346910','12929334','8335152','8334323','7714898','7715529','7051767','7051773','36219824','7051865','12444079','12032002','12416543','10856905','10859000','10788769','10788894','10788954','10383602','10382827','12381697','12378892','10655663','10656402','10666185','10666377','10860075','10860498','10861312','5285554','5285811','5287561','8485154','8485262','8485478','5294130','5294204','5287572','5294217','5294281','5293382','5295417','24799277','36125485','36125488','36125526','36125537','36125477','12880359','12880368','12880372','13239930','13307793','26981999','5267896','5268543','5270199','6128890','6139413','6140965','6129562','6141149','6141251','6141315','6141426','6141469','6141557','23280501','17664786',
 '17665283','12066282','12066284','12066294','12066296','6128608','5294047','5296633','36192721','36217443','5279328','5267183','17664616','29429525','12449506','25201968','17672389','17671265','17672124','17664721','17671600','17671535','8354653','17664677','17672195','17671744','17671512','17670307','17670246','12059887','5287505','12797140','11791377','5283434','5286849','5286948','5285110','5285335','25210186','29976302','6126619','6126883','6127586','5299634','5299699','17670092','36011268','36103780','36104340','36104363','36104382','36104394','36144751','36144758','36117051','36117067','35844785','35905067','36262592','36286654','19474851','19475484','14347027','17669826','17671306','17670511','17670103','36217423','17670024','17671382','5432351','5421967','10788539','17665621','17665151','12066252','12453081','17672346','5781233','5782121','8112885','8115288','3882959','8389072','7272370','7689680','2470968','2471001','8390122','3882897','3882633','3872343','3872423','4198954','3882766','36263945','36279628','36303359','36294521','36225914','36225917','14569702','36164201','10236165','9140047','14621974','14903459','34127318','29667513','29715199','35377691','35377777','35379127','35379383','35857901','36106391','13697718','13559387','13659396','13520553','13408734','13404701','13717590','13408711','13408718','13559330','13559335','13559323','13067981','15029331','15032279','15029262','15029279','15029300','15065636','15065638','15379739','15380636','15418750','22884574','14903507','14903514','14903517','14939485','14900748','14900747','14892883','14903456','14900755','14892817','13559363','13697726','13697757','13529927','13697741','13659334','13659367','13500407','13520573','13520564','12691732','12655126','12688362','12688380','12803685','12803688','12803691','12803696','12803711','12476277','12476514','12476773','12457484','12457652','12480535','12479046','12451894','12452304','12453719','12453760','12452973','12691712','12691720','12691746','12414541','12415710','12421859','12329425','12329800','13063621','13063632','13183821','13072425','13072449','13067972','13072415','13063644','13015292','13041926','13062791','13063594','12423564','12423882','12424517','12414501','12414505','12384469','12337749','12336126','12422214','12423171','12423200','11968198','11968328','11908071','11909979','11966452','11902408','11900748','11901906','36126358','36126366','36126376','36126342','36118136','36118137','36118138','36118206','36118127','36118128','36121647','36153210','36137717','14645423','14605965','14605964','14342544','14342537','14342549','14665109','14665108','14665096','14665105','14502628','14439291','14439299','14439297','14439295','14342561','14342562','14342563','14672968','14328921','14328915','14197587','14665127','14665124','14673010','14622003','14621991','14580858','14342560','14651984','14665554','14580887','14502648','14504314','14504316','14342533','14342531','14665118','14665116','14672985','14672992','14672974','14605915','14504304','14331335','14311911','14627633','14627631','14627630','14605927','14605940','14569706','14328941','14311926','14602813','14711389','14651946','14651944','14583871','14583876','14583885','14328933','11653768','11653907','11653942','11654606','11655501','11635571','11635601','11633075','11609673','11634433','11634541','11610235','11610484','11639880','11640065','11640211','11640335','11640375','11636335','11640587','11636577','11634770','11653547','11865703','11866517','11870620','11871055','11859402','11859435','11859660','11865497','11830828','11830837','14624344','7301313','13500387','14823774','13500417','12803692','36222443','36222476','36222480','36222483','36279405','13085906','2470999','14708601','14704377','14647501','14708634','14708655','7034636','13044989','14712297','36120028','13044998','12479885','13656156','13616674','13619088','13619096','13619109','13619114','13609123','13619157','13579750','13579759','13616600','13616622','13579796','13609117','13369155','13369161','13689693','13699260','13699263','13699265','13699282','13699288','13699300','13689562','13656108','13619383','13609210','13611211','13559258','13559273','13579742','13579747','15023243','14941182','15023281','15023285','15023292','15023301','15023343','13369192','13369196','13369203','13530741','13559235','14464799','14464804','14391755','14541476','14465968','14465969','14465987','14465990','14465991','14465995','13369133','13313328','13313345','12498745','13500352','12498719','12498722','14892847','15068645','12479887','14624329','14432218','15029325','15149658','15015015','15015023','15015028','15015041','15583178','15583279','15583046','14760711','14786830','14786834','14760724','14760727','14760732','29718482','14541507','14541474','14541506','14704427','14704430','14704438','14794652','14760688','14760693','14760696','14786823','14794646','14760672','14391763','14740267','14740268','14740283','14740249','14740258','14740298','14740299','14395029','14325724','14296058','14299908','14391749','14391750','14391752','14299938','14281177','14281178','14281183','14281184','14281772','14281774','13313226','12625706','12753093','14391784','14325653','14395000','14325667','14395002','14431477','14431483','14431486','14325706','14325708','13015332','12421793','12421805','12421810','12421814','12448006','12448019','12448022','12597955','12506358','12479832','12479874','12479880','12604695','12622763','12597820','12384931','12384953','12506322','12448111','12448322','12631186','12337615','14823784','14433009','15029337','11640684','8115287','14823782','36222438','15029311','14645447','14571618','14571625','14571648','14571616','12631206','14788355','25361392','12597802','12376665','14939512','13616538','8113849','14577518','14432220','12414536','14577523','14577544','12422375','14577547','13034502','11635039','11968059','11901957','13888696','13889032','36222477','13891566','13891761','13891774','13890886','13890896','13890902','13891021','13891407','13887927','13887928','13887801','13887846','13888036','36230980','14645361','15029298','13894008','13893304','13893147','13893152','13893207','13893941','13893753','13892995','13893005','13892809','13892864','13894134','13886885','13887100','13887101','13886758','13886778','36241171','36241300','13892185','13888783','13888488','13888491','13888413','13886642','13889262','13889631','13889176','26267484','26261849','26287475','26290751','26234144','26234146','26234155','26234166','26227304','26226847','24749588','24749848','24751470','24752279','26281333','26283524','26284056','26279677','8112898','26277935','36228193','13891827','13891839','13891850','13891864','13891868','13890472','13890497','13889910','13889682','13890022','13889257','13891113','13891143','13890740','13890810','13890813','13892784','13892647','13892168','13892433','13892219','13892464','13891898','13891643','13891696','13891710','13891725','13892520','13892755','13890825','13890264','13890045','13890380','13890073','13887735','13887534','13887756','13887555','13887773','26282625','26282996','13888448','13888222','13888276','13888098','26333229','26333499','26336159','26336235','26336283','26336422','26340193','26324911','26299716','26300051','26331940','26332034','26332145','26323597','26332845','26300876','26296428','26292140','26345260','26337997','26344567','30344454','30352907','36221618','36199769','26322725','14245142','14245157','36227014','13888204','36138674','13892171','14794628','13656075','24752664','14794631','26278267','24752764','14903435','26266621','9154529','9155000','9155023','9155026','9155029','9155033','4080665','4091244','5313474','4203914','5539042','5657653','5668999','9154616','5671157','8481110','36155446','9008886','9007715','9154661','36279576','36266916','36221607','36222914','36260344','36220113','4277065','4277074','4272196','4201685','4201722','4202469','4131016','4131133','4276702','4205387','4276174','4130964','4130976','6397334','6397349','6397378','6347147','6347224','6347999','6348029','6348982','6336717','6349950','6340042','6340175','6395648','6395696','31888710','31790478','31790705','31638602','31889496','31888486','31888510','31888528','31790366','31889375','30092241','30174789','25937979','19485970','22357172','22360038','22087920','19651228','18920378','18920292','18920478','19053633','19054071','14737346','14737310','14737319','14668629','13001079','18907957','16324163','18907248','18907272','18907302','18907196','18907214','18907226','18904301','18904325','18905189','18902947','18903141','18005462','18902818','18906091','18904264','18902926','15708221','15696458','15339936','15339946','15339950','31889168','31891382','33005533','31892899','32453101','32474735','32475127','28076743','14668596','14492721','13556210','13557675','14668522','14668521','36135062','29062431','31893117','28489376','28489447','9747722','9747861','9747863','9728920','9728925','8666433','9728929','9728957','12632857','9965139','9964234','9966199','9964255','9964583','9964587','9966227','9964629','9964635','9964642','9994733','9964653','9964596','9964600','9964610','9964613','9964617','22885450','22883994','24666790','22445128','22497103','18915177','18915500','18914641','18915224','18914216','18914329','18914376','18914067','18915566','18916662','18915592','18916262','18916886','18917373','18915795','27203119','27146466','20790095','20791760','20526353','21754280','21754650','18917511','18917530','18917538','18918154','18918468','18918495','18918497','18919391','18918510','18918255','18919235','18919177','18919414','9830500','9809196','9809201','9809215','9809240','9809246','9809252','9840980','9840999','9842077','9841283','9841309','9830449','9830460','9830478','9830401','9830495','9809184','9880213','9880216','9880220','9880224','9880225','9880227','9915680','9941641','9917981','9915738','9916315','9918744','9786863','9786873','9786881','9786883','9786902','9786912','9786930','9785808','9785809','9785810','9785840','9786826','9786848','9841260',
 '21397974','18905073','21398130','18915188','18914697','9941670','18920509','4276152','18919195','9830409','9830479','11897507','11831416','11830283','11902521','11904697','36211793','36266882','9747720','9785849','9783440','9755859','9755857','9755860','9782535','9782547','9782546','9830442','4243211','11906866','18916189','9785852','4205207','9840997','6397103','9755864','9755872','9755870','18917815','36217802','9782474','9792960','9792937','9792887','9792935','9792946','9792955','9792970','9792954','9792965','9792932','9792956','9792907','9792914','9792886','9792893','8574546','8574548','8574475','8574406','8574324','8574325','8574417','8574424','8570178','8570180','8570184','8570190','8570639','8570545','8570643','8570553','8570646','8570557','8570647','8570496','8569497','8569263','8569264','8569417','8569278','8569302','8569852','8569854','8569723','8569740','8569711','8569454','8569476','8569479','8570505','8570311','8570329','8569318','8569194','8569195','8569201','8569327','8569205','8569328','8569210','8569336','8569338','8569229','8569232','8569233','8569237','8569246','8569247','8569254','8583994','8584170','8584175','8583883','8584003','8584184','8584016','8583910','8584058','8583939','8583056','8583057','8583058','8583060','8583070','8583175','8583076','8583180','8583184','8583674','8583690','8583228','8583246','8583260','8584349','8584215','8584219','8584220','8584228','8584241','8584495','8576732','8576844','8576854','8576743','8576744','8576751','8576752','8576753','8576821','8576763','8576679','8576767','8576680','8576770','8576773','8576774','8576782','8576951','8576959','8576962','8577050','8576969','8576976','8576980','8576841','8576985','8577148','8577005','8577010','8577023','8577028','8576942','8577180','8577251','8577252','8577109','8577253','8577254','8577120','8577123','8577197','8577124','8577204','8577206','8577212','8577213','8577215','8577217','8577219','8577230','8577232','8577243','8577244','8577245','8577239','8567683','8567491','8567493','8567495','8575595','8575599','8575445','8575612','8575614','8567179','8567344','8567091','8567370','8567372','8567111','8567386','8567387','8567388','8566704','8566902','8566909','8567070','8567080','8567081','8567084','8566780','8566675','8566317','8566397','8566000','8566002','8567392','8567125','8567141','8567161','8567016','8567036','8567043','8566652','8576521','8576363','8576364','8575488','8575491','8575494','8575576','8575496','8575502','8575762','8575766','8576256','8576258','8576272','8576273','8575753','8564835','8564500','8564320','8564507','8564518','8564912','8564759','8564767','8564932','8564937','8564578','8564579','8564959','8564965','8565352','8565167','8565175','8565178','8565202','8564859','8564869','8565264','8565469','8565275','8565101','8565776','8565784','8565804','8565556','8565600','8565601','8564552','8564381','8581519','8581018','8580896','8580954','8580607','8580671','8580340','8580341','8580283','8579787','8579795','8579634','8580112','8580122','8580123','8579958','8579966','8579971','8579910','8579916','8579918','8579922','8579987','8579857','8579931','8579935','8579936','8579946','8579947','8579655','8579658','8579661','8579597','8579600','8579672','8579524','8579607','8579444','8579373','8579374','8580014','8579955','8579736','8579764','8579770','8579550','8579554','8579556','8579559','8579561','8582900','8582738','8582739','8582858','8582859','8582861','8582863','8582943','8582581','8582582','8582588','8582160','8582031','8582036','8583211','8583218','8583221','8583223','8583227','8582892','8582899','8583368','8583279','8583288','8583289','8583385','8583470','8583485','8583487','8581702','8581574','8581499','8584249','8584252','8584261','8584437','8584443','8584446','8584451','8584454','8583966','8582203','8582333','8582118','8582151','8575478','8575328','8575951','8576014','8575957','8575959','8576071','8576075','8576076','8574559','8574211','8574299','8574214','8574142','8574217','8574229','8574232','8573776','8573696','8573708','8573715','8573717','8573971','8573979','8573981','8573949','8573960','8573967','8573970','8573747','8573748','8573754','8573757','8574345','8574193','8574197','8574198','8574203','8574130','8574204','8574205','8574207','8573633','8573651','8573628','8573629','8571259','8570999','8571006','8571018','8571024','8568314','8568418','8568325','8568151','8568336','8568156','8568163','8568067','8568171','8568074','8568178','8568184','8568187','8568193','8568195','8568206','8568096','8568213','8568346','8568348','8568350','8568237','8568241','8568242','8568246','8568249','8568378','8568265','8568384','8568274','8568389','8568294','8568397','8568400','8568719','8568744','8568475','8568480','8568656','8568102','8568105','8568107','8568113','8568117','8568120','8568221','8568135','8568231','8568234','8567904','8567805','8567909','8567919','8567941','8567946','8567954','8567846','8567962','8569260','8568889','8568904','8568766','8568767','8568858','8568698','8568819','8572585','8572586','8572587','8572590','8572591','8576243','8576120','8576170','8576122','8576247','8576127','8576128','8576129','8576135','8576136','8576140','8576188','8576141','8576143','8576144','8576192','8576145','8576193','8576146','8576147','8576148','8576149','8573492','8573495','8573502','8573508','8573512','8573517','8573519','8573524','8573537','8573538','8573393','8573397','8575283','8575314','8573337','8573232','8573254','8573113','8572682','8572350','8572255','8572163','8572557','8572561','8572564','8572436','8572314','8572206','8572312','8572224','8576610','8576674','8576675','8576676','8576487','8575003','8574794','8576091','8576096','8575779','8575922','8575923','8575787','8575793','8575796','8575834','8574616','8574379','8574385','8575117','8575121','8575136','8574962','8574781','8574624','8564225','8564058','8564068','8564070','8564072','8564073','8553171','8553038','8553296','8553297','8553044','8553047','8553185','8553190','8553202','8553078','8553085','8552947','8553338','8553227','8553352','8553354','8553236','8553355','8553262','8553150','8553152','8553166','8552994','8553008','8552786','8552791','8552896','8553016','8552795','8552805','8553032','8552808','8552813','8552818','8552820','8552821','8552823','8553646','8553550','8553304','8553460','8553461','8553326','8553327','8553328','8553209','8553213','8554013','8553744','8553873','8553662','8553666','8553670','8553682','8553686','8553692','8553802','8553956','8553957','8553978','8553984','8553832','8553992','8554008','8553855','8553738','8553741','8553743','8553733','8553477','8553479','8553482','8553616','8553387','8553519','8553391','8552825','8552672','8562615','8552506','8552774','8552782','8552514','8552519','8552142','8552146','8552149','8552150','8551973','8551975','8552098','8551979','8551981','8551982','8552108','8551985','8552115','8551988','8551989','8551994','8551998','8552001','8552003','8552004','8552005','8552010','8552011','8552012','8552023','8551828','8552026','8552123','8552126','8552133','8552138','8551832','8551833','8551837','8551840','8551959','8551692','8551699','8551700','8551701','8551704','8551616','8551870','8552484','8552488','8552496','8552502','8552292','8552405','8552565','8552461','8552469','8552593','8552586','8563040','8563282','8556954','8556967','8556812','8556708','8556720','8556566','8556428','8556445','8556454','8556457','8556375','8556521','8556528','8556529','8556406','8556407','8556548','8556551','8556557','8556416','8557614','8557618','8557292','8557121','8557129','8557245','8557251','8557257','8557259','8557148','8556890','8557005','8557006','8557106','8557010','8556903','8557015','8557114','8557018','8556922','8557039','8557041','8556930','8556932','8556948','8556951','8557263','8557161','8557044','8557049','8557052','8557053','8557059','8557061','8557065','8557067','8556971','8557070','8557071','8556981','8556983','8556985','8556987','8557080','8556990','8557083','8556991','8556992','8557086','8556993','8557093','8556287','8556297','8556169','8563921','8563927','8563935','8563937','8563941','8563802','8563992','8563820','8563822','8554911','8554808','8554850','8554697','8554877','8555468','8555469','8555471','8555356','8555504','8555509','8554424','8554206','8554356','8554491','8554497','8554522','8554394','8554400','8554408','8555325','8554703','8554706','8554708','8554714','8554737','8554742','8554748','8554463','8554471','8571343','8571499','8571363','8571370','8571375','8571099','8573107','8572993','8572998','8573010','8572847','8573116','8573138','8573149','8573026','8573154','8573157','8573163','8573198','8571711','8571712','8571714','8571455','8571467','8571622','8571659','8571681','8571683','8571540','8571693','8570977','8570718','8570723','8570726','8570985','8570728','8570750','8570607','8570614','8570616','8570626','8570632','8570533','8570541','8570934','8570942','8570943','8570696','8570961','8570708','8570968','8570714','8572146','8562979','8562888','8562684','8562691','8562695','8562902','8562699','8562903','8562712','8562724','8560796','8560693','8560697','8560711','8560850','8560716','8560720','8561235','8561162','8560782','8561166','8561168','8560748','8560750','8560755','8560756','8560758','8560764','8560180','8560283','8560074','8560422','8560296','8560125','8560160','8560163','8560167','8560170','8560172','8560174','8560175','8560272','8559850','8559861','8559982','8559826','8559828','8559839','8559843','8559846','8563757','8563770','8563800','8563286','8563290','8563297','8563302','8557807','8557825','8557724','8557725','8558401','8558266','8558273','8558276','8558279','8558286','8558290','8558302','8558310','8558645','8558507','8558533','8558377','8558381','8558253','8557905','8558030','8557913','8557915','8557916','8557776','8557780','8557783','8558041','8561338','8561340','8561461','8561347','8561357','8562248','8562047','8562052','8562054','8562448','8561625','8562023','8562026','8562038','8561962','8561584','8562935','8562739','8562938','8562746','8562747','8562748','8562942',
 '8562943','8562944','8562752','8562753','8562754','8562759','8562760','8562767','8562975','8562064','8562079','8562080','8562081','8561986','8561989','8561991','8561996','8562909','8562915','8562921','8562731','8562928','8562732','8562929','8562735','8562930','8562737','8562004','8562014','8562017','8564029','8563874','8563890','8563897','8563908','8559271','8559301','8559302','8559309','8559312','8559324','8559635','8559638','8559781','8559788','8558910','8558808','8558603','8558628','8558632','8559326','8559239','8559260','8559595','8559472','8559501','8558939','8558944','8558946','8558948','8558957','8559356','8559268','8549656','8549657','8549760','8549666','8549669','8549763','8549591','8549766','8549767','8549678','8549687','8549695','8549612','8582072','8581885','8581888','8581894','8581998','8581907','8582005','8581917','8581924','8582013','8581931','8582022','8583514','8583520','8582882','8580652','8580720','8580566','8580375','8580455','8580459','8581046','8581053','8581202','8581203','8581206','8581208','8581209','8581079','8581210','8581026','8580984','8580467','8580469','8580480','8581217','8581090','8580977','8580219','8580083','8580150','8580092','8580160','8577902','8577911','8577918','8577991','8577928','8577936','8577591','8577529','8577603','8577539','8577543','8577550','8577554','8577684','8577685','8577626','8577872','8577885','8577452','8577320','8577257','8581246','8581255','8578010','8577946','8577958','8578028','8577959','8578035','8577966','8577970','8577971','8578198','8578211','8578213','8578215','8578054','8578261','8578175','8578048','8578511','8578515','8578447','8578519','8578520','8578319','8578328','8578337','8578339','8578703','8578770','8578771','8578710','8578711','8578526','8578527','8578529','8578766','8578700','8578767','8578535','8578536','8578537','8580613','8580678','8580679','8580683','8580621','8580684','8580819','8580687','8580627','8580694','8580695','8580633','8580830','8580635','8580636','8580649','8579000','8579005','8579006','8579010','8579013','8579014','8578818','8578614','8578750','8578682','8578751','8578755','8578758','8579203','8579210','8578785','8578855','8578860','8578792','8578867','8578868','8578869','8578811','8578721','8578722','8579181','8578844','8584472','8584481','8584486','8576219','8576151','8576152','8576154','8576110','8576112','8556064','8556069','8556070','8556077','8555626','8555630','8555643','8555644','8555517','8555523','8555532','8555543','8555452','8555456','8555850','8555961','8555853','8555854','8555870','8555589','8555590','8555594','8555602','8555607','8556047','8555804','8555958','8555846','8551444','8551446','8551335','8551447','8551458','8551258','8551264','8551276','8550772','8550618','8550698','8551612','8551412','8551420','8551535','8551424','8551428','8551429','8551431','8551541','8551324','8551332','8551333','8550787','8550795','8550719','8550725','8550729','8550732','8550733','8551782','8551647','8551678','8551684','8551561','8551060','8551066','8551203','8551207','8551213','8551226','8551135','8551232','8551070','8551073','8551074','8551075','8551077','8551239','8551080','8551083','8551247','8551086','8551089','8551091','8551092','8551095','8551097','8551160','8551098','8551102','8551632','8551889','8549040','8549157','8548968','8546570','8546577','8546856','8546857','8546880','8546889','8546806','8546815','8546816','8546817','8546822','8546824','8546190','8546278','8546196','8546209','8546210','8546217','8546229','8546230','8546160','8546161','8546599','8546676','8546600','8546678','8546684','8546611','8546613','8546617','8546542','8546621','8546625','8546549','8546627','8546553','8546558','8546827','8546830','8546835','8546840','8546844','8546741','8546743','8546748','8546749','8546754','8546685','8546686','8546689','8546758','8546759','8546693','8546696','8546697','8546766','8546698','8546770','8546702','8546776','8546234','8546235','8546236','8546243','8546244','8546245','8546248','8546252','8546253','8546254','8546256','8546260','8546182','8546183','8546185','8546186','8546187','8546189','8546631','8546785','8546633','8546638','8546796','8546735','8546739','8546653','8545972','8545975','8545977','8546163','8546078','8546169','8546170','8546085','8546171','8546172','8546176','8550214','8550216','8550217','8550074','8550075','8550221','8550081','8550225','8545236','8545239','8545981','8546060','8546008','8545874','8546014','8546015','8545878','8545190','8545193','8545109','8545471','8545485','8545490','8545417','8545822','8545952','8545953','8545954','8545956','8545959','8545960','8545963','8545965','8545966','8545838','8545841','8545850','8545852','8545855','8545567','8545569','8545571','8545574','8545585','8545587','8545588','8545463','8545592','8545593','8545466','8545598','8545470','8545608','8545616','8545013','8544910','8544913','8545036','8548152','8548153','8548158','8548159','8548162','8548421','8548425','8548428','8548433','8548348','8548445','8548165','8548167','8548170','8550475','8550478','8550393','8550192','8550193','8550195','8550196','8550197','8550200','8550201','8550204','8550205','8550208','8550209','8550210','8550211','8550631','8550706','8550637','8550639','8550641','8550711','8550712','8550649','8550569','8550653','8550658','8550659','8550591','8550594','8550597','8548504','8548506','8548595','8548516','8548526','8548527','8548529','8548533','8548612','8548535','8548614','8548537','8548615','8548459','8548617','8548539','8548467','8548546','8548548','8547868','8547869','8547874','8547877','8547879','8547881','8547739','8548550','8548553','8548474','8548555','8548558','8548561','8548482','8548485','8548488','8548489','8548492','8548496','8548498','8548404','8548308','8548313','8548406','8548407','8548411','8548416','8548417','8547835','8547688','8547765','8547696','8547699','8547704','8547707','8547711','8548723','8548638','8548641','8548728','8548729','8548730','8548734','8548653','8548564','8548658','8548565','8548566','8548568','8548573','8548575','8548576','8548581','8548582','8547747','8544554','8549622','8549624','8549626','8549628','8549630','8549631','8549632','8549633','8549634','8549636','8549637','8549551','8549642','8549643','8549644','8549397','8549399','8548116','8548117','8548128','8548132','8548133','8548953','8548955','8548750','8548758','8548768','8548691','8548693','8548774','8548778','8548699','8548782','8548701','8548796','8548797','8548800','8548710','8548801','8544837','8544746','8544748','8544849','8544756','8544851','8544676','8544853','8544681','8544760','8544683','8544764','8544684','8544768','8544769','8544771','8544691','8544782','8544787','8544789','8544705','8544707','8544508','8544419','8544420','8544518','8544423','8544441','8544445','8544449','8544452','8544455','8544456','8544457','8544464','8544466','8544395','8544398','8544399','8545086','8545087','8544861','8544411','8544412','8544413','8544418','8539405','8540073','8540612','8540290','8544300','8541362','8541434','8538250','8537844','8578325','8537339','8539614','8550016','8549945','8549291','8549293','8549235','8549259','8549266','8549086','8549725','8549726','8549732','8549739','8549646','8549647','8549651','8549749','8549653','8549654','8549923','8550440','8550443','8550444','8550449','8550451','8550456','8550268','8550457','8550465','8548901','8548902','8548906','8548928','8548826','8548713','8548717','8548628','8548718','8548813','8548817','8548944','8549403','8549409','8549414','8549419','8549420','8549285','8549460','8547371','8547485','8547314','8547322','8547333','8547261','8547237','8547257','8547109','8547188','8547190','8547114','8547721','8547722','8547733','8547734','8547736','8547667','8547674','8547683','8547684','8547125','8547203','8547053','8547054','8547135','8547139','8547081','8547339','8547269','8547341','8547342','8547343','8547271','8547344','8547345','8547275','8547350','8547211','8547282','8547504','8547509','8547425','8547429','8547431','8547291
 ','8547222','8547224','8547304','8554496','8568217','8575969','8559392','8560788','4174515','36190309','36190313','36190315','34047107','34632653','33994291','8576115','34032080','33996517','33999293','33999697','33982428','27466939','27813722','27480585','27427178','27611814','27296792','27389825','8540715','8540922','8536734','8536744','8536789','8536557','8541449','8541247','8541002','8541025','8540863','8537752','8537797','8537371','8538176','8538415','8537990','8544869','8544873','8544875','8544877','8544879','8544880','8544882','8544887','8544888','8544797','8544807','8544808','8544896','8544897','8544898','8544812','8544900','8544814','8544819','8544906','8544907','8544822','8544908','8544823','8544825','8544826','8544828','8544832','8544833','8544834','8544835','8544836','8539018','8540768','8540536','8540802','8538609','8537657','8537638','8538567','8572295','8572293','8562474','8562475','8562472','9782477','8547265','20691341','8570499','9785817','18918499','9782480','8554022','18919772','8554348','18915775','8579921','8570503','11829233','8556563','8550877','8550878','8546041','8546047','8546048','8575875','8575896','8575905','8557327','8557334','8546714','8551494','8545382','8560203','8545397','8550858','8544330','8544339','8579808','8580184','8580188','8579818','8579834','8577721','8577722','14488448','8575866','8550019','8580328','8550469','8580350','18918368','18918386','18918365','8580363','18918286','18918961','8551509','8551914','8555023','8555025','8555034','8555036','8555037','8555041','8555054','8555056','8555061','8555071','8557695','8557315','8557318','8557319','8557322','8557323','8576375','8580431','8580443','8557145','8545845','8574845','8546692','24456615','8570407','8570405','8570402','8551329','8579842','8579843','8579846','8580171','8578974','8578283','8578289','8578297','8578299','8576059','8578300','8578301','8580441','8584514','8579105','8578276','8578279','8557343','8555049','8555052','8555053','8544347','8544348','8544353','8546731','8553408','8553413','8553416','8553420','8553422','8553434','8553444','8553445','8565518','8575853','8576686','8576684','8576685','8555493','8577031','8557638','8547690','8571055','9941645','18918854','36279976','36304165','36304189','36213565','36214684','36196726','36253587','8717831','14281837','14296022','14282072','14295987','18099818','14767055','14767074','14652409','14021505','13939214','13939252','13939318','13939327','21390010','13758262','13700184','13700187','14134882','14134863','14134865','14134868','14134874','36162379','36173676','36131781','30182679','13701271','32523322','13013354','21930709','36131400','36215882','36215880','21930698','3592148','3592952','3593403','3618555','3590808','3624002','3625415','3613368','3626786','3614270','3606558','3616058','36180675','13939216','5498775','5496680','5496748','5496922','5497011','5497313','5497571','36266341','36266360','36270098','36249677','30423440','30971267','30971299','30981574','34172713','36123637','36124942','36123660','36123668','36117929','35868855','36127980','36126195','27968793','27959093','25061805','25062685','21491450','21491720','27960873','23995342','27968668','27962465','36126239','36126250','36126305','36124866','13727100','22775180','11534496','10265887','10275267','10499099','10499101','10499102','10501604','10501684','11642489','11642551','11640504','11091524','10498199','10501636','9261402','10501687','9026453','9280943','9275794','27963857','36114959','36266405','36126201','27965918','11599251','11599281','11599108','11599118','11599128','11599217','11599218','11599224','11599228','36266396','36189742','16392645','16392742','16392747','16392768','16392809','16392846','16510471','16392925','16392933','16392232','16371009','16371026','16392280','16371068','16371074','16371079','16392374','16371097','16371128','16392435','16511919','16511933','16371186','16371203','16371218','16392610','16371227','16392627','16370972','16370218','16370252','16511973','16511975','16511621','16511731','16370276','16370281','16369959','16370311','16369998','16370012','16370050','16370054','16370079','16370116','16510508','16510581','16510595','16511758','16511277','16511286','16511313','16511812','16511003','16369302','16369314','16369632','16369332','16369353','16369680','16369374','16369384','16369730','16326485','16369445','16369537','16369553','16369274','16369866','15738176','15738004','15738112','15738118','16315616','15738123','15738138','15738144','15738052','15738078','15738080','15737362','15737271','15737273','15737291','15737302','15737764','15737402','15737872','15737993','15737417','15737338','15736934','15737352','15737356','15737452','15737195','15593201','15593410','15593413','15593205','15593210','15593211','15593422','15593220','15593653','15593654','15593225','15593229','15593448','15593234','15593235','15593466','15593267','15593477','15593269','15593299','15593309','15593313','12578448','12444938','12446259','12446434','12481619','12599861','12599911','12599969','12571710','12575024','12033225','12019623','12019908','12021607','12021644','12022025','12022030','12022096','12022366','12022369','11975827','12022417','12028362','12028565','11976701','12028839','12028842','12444089','12444561','12444660','12029078','12029084','12013705','12029088','12013708','12017910','12333840','12030210','12018317','12018323','12018555','12030824','12018804','12018818','12474643','12481768','15736033','15736042','15736173','15736054','15736183','15736379','15736073','15736074','15736210','15736079','15736222','15736088','12801654','12802235','12871541','12841710','12871646','12841720','12841748','12841779','12769053','12609452','12579532','12579992','12580090','12570534','12599602','12599657','12648599','12684692','12684728','12684963','12649397','12685032','12652289','12685064','12652328','12652374','12654103','12654133','12654191','12654227','12654259','12646396','12646470','12646586','12601194','12647986','12648095','12655332','12655400','12684590','12648332','12653326','12653408','12653853','12654031','12654063','15594238','15593658','15593853','15593694','15736672','15736405','15736413','15736415','15736565','15736568','15736271','15736467','15736478','16511212','16510687','16510994','12803762','12803835','12803888','12803902','12803931','12841620','12841635','12776095','12776165','12841664','12801319','12801431','14740457','14740328','14740096','14740099','14740353','14740108','14740130','14740131','14603548','14740149','14603578','14603582','14740002','14740038','14739924','14740170','14739904','14740436','14740440','11901007','11901010','11906549','11906602','11906621','11906671','14740073','14740079','14740080','14579128','14489130','14489137','14489139','14489146','14579155','14489164','14600666','14573029','14601331','14489172','14573032','14489177','14489179','14573034','14573046','14573053','14573055','14443082','14573088','14573266','14492140','14488107','14488110','14488118','14488125','13316679','13319180','14489125','13377749','13377795','13377820','12803630','12803658','11791775','11762129','14740707','14740906','14740720','14740914','14740727','14740934','14741037','14740749','14741057','14740759','14741060','14740765','14740768','14740359','11307821','11510496','11511150','11195572','11195575','11511350','11189588','11511614','11511667','11195589','11511722','11962731','11973295','11968307','11962812','11973555','11973557','11968568','11963428','11973877','11968821','11964919','11972280','11974251','11974368','11965976','36122625','11175107','11175139','11175201','11175205','11175215','11175242','11175287','11175350','11175370','11175378','11175436','11175480','14740174','11770180','11062467','11083619','11062578','11083712','11083750','11083793','11062822','11084002','11084065','11084148','11064438','11084206','11084363','11084519','11084563','11084592','11065165','11143387','11093095','11130191','11120462','11087023','11122153','11084820','11122227','11084890','11122306','11084922','11122397','11122552','11122929','11085196','11089886','11123256','11085306','11089914','11089925','11089980','11090021','11085654','11090034','11125466','11090174','11085820','11090230','11085838','11090258','11085864','11125800','11090283','11085889','11090302','11086330','11086371','11086478','11086550','11941188','11941202','11939571','11939580','11941304','11939667','11932762','11195603','11195609','11195617','11304454','11304492','11306156','11306338','11967994','11968048','11962418','11968135','11973270','11961174','11961467','11175049','11086571','16393948','16369183','16369191','16369198','35659563','35761374','36127034','36223063','36157136','36158289','36238477','36164132','16512341','34195889','34312908','15593809','15593519','15593527','15593533','15593325','15593573','15593368','15593175','15593180','15593187','15736847','15736729','15737242','15736858','15736866','15737260','15736744','15736873','15736753','15736886','15736902','15736761','15736910','15736769','15736776','15736786','15736793','15736803','15736497','15736498','15736499','15736504','15736383','15736506','15736508','15736388','15736396','15736522','15593501','15737198','15736708','15737212','15736717','15736718','15163794','14819179','14819181','15203940','15203944','14819217','14819220','14819249','14818975','14818979','14818987','14818990','14818996','15203988','14819010','14819020','14819024','14819031','14819297','15204071','14819153','14819171','14741220','14741228','14741230','14741108','14741263','14741271','14740960','14818650','14818445','14818456','14818493','14740969','14741147','14740856','14741015','14740863','14741191','15204503','15204336','15708053','15708056','15594406','15708057','15594421','15594441','15708091','15594039','15708105','14818511','14818513','14818942','14818947','14818533','14818536','14818961','14818784','14818549','14818556','14818561','14818562','14818570','14818572','14818574','14818802','14818592','14818609','14818618','14818624','14818630','14818642',
 '16369240','36123529','11141197','11126480','11126761','11961865','11961906','11962043','11962051','11940646','11940891','11900995','11900996','14819311','14818816','14819067','14818828','14819089','14818839','14818846','14818852','14818856','14818681','14819125','14818861','14818685','14818695','14818706','14818715','14818904','14818905','14818728','14818731','14818915','14818502','14818735','14818504','14818737','14740615','14740364','14740367','14740849','14740852','14740399','14740407','14740670','14740417','14740422','14740424','14740684','14740300','14740303','15736097','15736250','15736130','15736139','15708161','15708164','15594492','15708172','15708190','11792016','11792234','11780738','11820469','11820661','11781349','11781418','11821069','11781847','10497754','10497792','36124861','13904267','5283454','5283539','5283540','5283568','5283630','5283635','5286439','5293871','5293880','36317230','36313722','36302196','36298736','5282417','5282667','5282818','6156685','6157660','6158120','6127943','5298851','6139648','6141389','6141474','36125545','36125546','36130052','36130104','25116536','24799396','23947118','29430006','29971852','29428374','29428411','29973771','14346971','14346969','14346896','14345433','14339689','14339693','14339678','14245896','14202861','14199853','13517477','28629039','22595186','14390701','14346899','14346898','14347005','14339704','13617932','26413484','26807229','26806659','17672368','17672577','17672581','17672403','17672406','17672415','17672419','17672484','17672341','17671922','17671758','17671570','17671709','17671720','17671729','17672743','21707788','14346924','14345456','14345460','14346943','34564140','17671069','17670587','17670590','17671628','17671632','17671643','17671679','17671682','17670650','17670660','13268897','13240356','13240454','13269218','36117896','17670144','17670148','17669737','17669867','17669610','17665480','17664774','17665302','17665325','17665344','17669549','17665033','17665471','17664795','5293405','5282721','24799705','22353849','22189613','21944379','14345367','14345364','14346973','12929338','12880337','12880345','12880350','8355169','8382731','7714996','7715252','7754633','7051694','25196852','12454582','12454591','12454594','12453091','12444074','12444077','12444083','12032630','10125878','12376498','10795478','10655552','10656566','5285384','5287405','5277553','5269107','8037520','8037813','6141953','6158236','6158580','6158713','6137430','6137464','6138869','5296659','5296711','5294268','5293334','5295321','5293460','36125500','36125504','36125479','25210245','23280990','13239333','12880367','13240934','12373303','5268891','6139104','6139168','6128963','6139247','6139330','6139356','6129132','6129371','6141082','6141205','6141271','6141398','6136274','6136378','6136451','23279724','17664804','12066281','12066286','12066315','12066320','12066247','6127911','6128018','5296260','5296457','5294024','5294082','5296657','5279235','22232427','17665627','17671891','17665106','17672512','25210634','17665395','17671084','17670302','17672251','17665315','17669830','17671848','17671550','17665543','17670593','17669736','17664697','17670898','17669576','17671233','17670443','17671432','17670612','17671230','36173638','15589870','22237006','25177508','26982038','15589110','5299231','5286117','5286607','5286751','5293948','5286900','25210174','17671459','17672172','6141775','6123692','6136592','6126248','6126345','6136729','6126455','6136862','6136956','6137019','6137106','6137245','6127039','6127255','5299335','17664664','36117182','36132462','36103777','36117081','35848067','35904529','36282253','36286656','36282281','36282308','19474899','25656610','12066249','17670519','17669562','17671395','17671724','17670437','17665272','36313726','17665012','17671671','36117034','17664650','17670990','16354437','17669566','26807492','5781149','5781272','5743141','5743407','8387304','8112894','8112908','8112909','8113834','8115277','8115279','8115282','3882683','8388576','7109393','7550875','7653254','3873151','3873157','3873191','8388396','3872508','3882640','36221428','36228085','36230883','36225709','36272105','36280720','36297362','36265395','36235297','36182215','36225918','36204903','36189412','36164088','36164194','9140008','9140041','36106367','29667745','34430992','35379449','35379490','31897270','13697715','13692510','13559381','13015316','13560486','13404698','13717391','13559350','13500362','13068202','13068209','13068219','15032229','15068642','15068661','15029271','15029276','15065644','15428977','15380631','23436709','23093472','22911740','14939509','14939508','14900743','18527283','14892850','14892873','14892823','14900766','14900789','14900788','14892812','13582783','13559367','13582797','13398758','13550242','13550237','13500392','13697733','13659340','12691728','12691729','12688364','12688389','12688410','12451829','12452371','12453027','12451113','12691736','12691739','12415750','12421834','12337182','12337307','11973306','13550326','13180339','13185107','13187343','13072438','13067970','13182718','13154148','13072407','13063645','13015366','13015308','12850782','12424625','12339889','12376804','12421236','12414506','12414508','12384122','12414520','12414523','12338385','11905210','11967351','11909416','11905596','11973443','11973666','36123602','36126353','36126359','36126361','36126363','36126372','36126375','36126377','36126379','36126385','36126345','36126346','36118129','36153216','36161500','36137752','36164183','14621982','14580898','14505112','14536905','14439290','14439276','14439286','14439281','14465609','14342542','14342540','14787422','14665101','14665098','14580869','14580862','14439293','14439292','14311905','14311902','14583889','14505102','14504684','14504326','14536887','14328925','14328917','14328919','14665138','14665131','14673006','14621994','14622001','14621993','14569690','14502622','14342567','14665578','14645357','14645360','14645372','14645370','14645376','14569664','14569667','14504315','14759271','14665120','14672988','14651962','14651955','14651958','14605916','14504311','14342556','14311920','14762651','14762663','14605942','14505128','14505119','14439275','14328937','14328953','14311929','14311930','14197454','14504301','14651951','14651948','14583879','14583881');
return $array;
    }



}