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

    public function enrol_history_by_manager_id($course_status = '' , $cr_user_id = 0)
    {
        $user_id = $this->session->userdata('user_id');
        $where = [];
        $where['manage_id'] = $user_id;
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

    public function enrol_request_by_manager_id()
    {
        $this->db->select('enrolment_request.*');
        $this->db->order_by('dated_request', 'desc');
        $this->db->join('users','users.id = enrolment_request.user_id' );
        $this->db->where('manage_id', $this->session->userdata('user_id'));
        // $this->db->where('company_id', $this->session->userdata('user_id'));
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
        $query = $this->db->select($category_column.", count(*) AS course_number",false)
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
                    redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                    redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                    redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
            }

            if ($this->input->post('amazon_s3_duration') == "") {
                $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_duration'));
                redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                    redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                }
            } else {
                $this->session->set_flashdata('error_message', get_phrase('please_select_valid_video_file'));
                redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
            };

            $tmp                = explode('.', $fileName);
            $fileExtension      = strtoupper(end($tmp));

            $video_extensions = ['WEBM', 'MP4'];

            if (!in_array($fileExtension, $video_extensions)) {
                $this->session->set_flashdata('error_message', get_phrase('please_select_valid_video_file'));
                redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
            }

            // custom random name of the video file
            $uploadable_video_file    =  md5(uniqid(rand(), true)) . '.' . strtolower($fileExtension);

            if ($this->input->post('system_video_file_duration') == "") {
                $this->session->set_flashdata('error_message', get_phrase('invalid_lesson_duration'));
                redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                    redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                }
                $data['attachment'] = $this->input->post('iframe_source');
            } else {
                if ($_FILES['attachment']['name'] == "") {
                    $this->session->set_flashdata('error_message', get_phrase('invalid_attachment'));
                    redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                    redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                    redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                    redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                    redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                        redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                    }
                } else {
                    $this->session->set_flashdata('error_message', get_phrase('please_select_valid_video_file'));
                    redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
                };

                $tmp                = explode('.', $fileName);
                $fileExtension      = strtoupper(end($tmp));

                $video_extensions = ['WEBM', 'MP4'];
                if (!in_array($fileExtension, $video_extensions)) {
                    $this->session->set_flashdata('error_message', get_phrase('please_select_valid_video_file'));
                    redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
                    redirect(site_url($this->session->userdata('role') . '/course_form/course_edit/' . $data['course_id']), 'refresh');
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
            // ->order_by("course.id", "desc")
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
        // $this->db->order_by("id", "desc");
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
        
 $array = array(
'32226868','27514702','28076663','14737364','14668611','14668592','14668603','14668485','14668490','14608996','14490648','15200162','15339321','14668650','14668644','14668587','14668625','14668479','14608959','36112290','31892990','31914320','31891973','28489431','28077113','28228680','9733453','9733455','9733459','9733460','9733462','9733465','9728875','9728915','12693004','8666372','9729172','9733434','9733435','9733436','9733444','9474702','9474760','9474777','9474781','9474718','9201237','12776934','12632822','11896921','11016570','11895642','11896133','11896645','11482643','10207237','10320835','10321388','9965119','9965216','9964593','9964609','22888301','22888792','24639200','24346501','24407476','24407960','22878838','22594687','18915156','18915574','20844459','20788693','20731767','21976303','21979101','21698049','18918023','18919950','18917914','18917503','18919278','18919059','19049494','18919127','18911748','9820150','9812284','9812296','9840969','9820085','9820086','9820087','9820093','9812256','9812257','9830467','9880217','9879658','9880204','9786871','9755044','9755047','9755050','9755068','9755073','9786836','31386990','4110911','36108158','9807326','9807358','9807344','9812277','14668612','9474775','11905321','18916510','16626686','14668527','9755830','9755828','9755843','4203244','4354994','9812244','9686888','9686922','9830504','9474713','9474758','9474762','9785779','9917995','9755825','9475378','36214790','17054476','9782467','12753334','9792980','9792876','9792922','9791334','9729110','4130931','36153321','8574328','8569832','8569509','8569844','8569864','8569873','8569744','8569564','8569609','8570341','8569113','8569124','8569125','8583705','8576578','8576852','8576866','8576870','8576875','8576876','8576877','8576881','8576886','8576827','8576887','8576893','8567464','8575457','8567350','8566710','8566916','8566734','8567085','8567086','8567773','8567623','8567786','8567672','8566233','8566264','8566656','8576428','8575650','8575632','8576269','8564779','8564986','8564995','8564996','8565000','8565014','8565015','8565035','8565041','8565057','8565066','8565069','8565071','8565075','8565078','8565080','8580883','8580958','8580346','8580272','8579690','8579691','8579693','8579694','8579629','8579866','8579606','8579676','8579621','8579358','8579360','8579362','8579768','8579626','8582834','8582866','8582038','8581858','8581264','8575948','8574144','8574145','8573789','8574099','8573987','8574008','8574353','8573611','8573612','8567835','8567719','8569129','8569136','8569137','8569031','8569051','8568972','8568682','8569059','8569062','8576242','8576001','8573527','8573398','8572576','8572577','8575997','8575798','8574913','8564275','8553098','8553103','8553360','8552799','8552904','8552804','8552809','8553453','8553459','8553711','8552662','8552767','8552179','8552009','8551836','8551697','8556959','8556682','8556711','8556542','8556549','8556148','8556151','8556154','8564151','8563827','8555016','8555193','8554204','8555146','8571606','8570759','8562985','8562989','8562990','8562692','8560818','8560852','8561005','8561221','8560759','8560183','8560051','8560306','8560148','8558294','8558651','8558653','8558540','8558556','8558252','8562284','8563831','8563676','8559780','8559346','8559349','8549772','8582084','8580578','8580854','8577585','8577464','8577468','8577470','8577472','8577479','8577488','8577489','8577492','8577764','8577873','8577497','8577498','8577499','8577501','8577502','8581235','8578050','8578663','8578637','8578640','8578641','8578651','8578653','8578659','8578661','18918931','8580611','8580638','8579343','8579345','8579346','8579349','8579353','8578790','8584488','8562999','8556220','8556227','8556241','8556242','8556119','8556128','8556130','8556137','8556057','8550673','8551790','8551793','8551794','8551795','8551802','8548977','8548978','8548896','8546479','8546480','8546484','8546488','8546491','8546497','8546619','8546838','8546779','8546781','8546637','8550069','8550071','8546009','8545877','8545814','8544996','8544999','8545008','8545816','8545818','8545849','8545629','8545570','8545798','8550125','8550130','8550131','8550133','8550138','8548503','8548677','8548607','8547951','8548500','8548408','8544719','8544724','8548234','8548877','8548878','8548886','8548891','8544534','8544465','8544405','8540354','8540482','4174358','8541139','8541179','8537913','8537169','8550118','8550119','8549961','8550044','8550051','8550054','8550057','8549740','8550439','8548989','8548990','8549002','8547325','8547160','8547664','8547133','8547144','8547303','8577942','8575986','9812286','36278174','31341498','31342099','34001259','27624556','27557950','24417612','8536725','8541089','27883805','8537743','8538356','8544740','8539016','8537641','34373551','36262838','10207944','10208149','8572240','8577116','8548776','8567339','8566712','11829248','22592262','8576289','8576286','8546032','8546051','8544333','8579814','12836352','26945002','8552838','18918375','18917813','8576293','8576300','8580445','8570128','8574408','8559956','8567899','8544344','36225769','8574613','8570725','8544616','8544612','8544633','8576682','8544614','9755868
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
 '16369240','36123529','11141197','11126480','11126761','11961865','11961906','11962043','11962051','11940646','11940891','11900995','11900996','14819311','14818816','14819067','14818828','14819089','14818839','14818846','14818852','14818856','14818681','14819125','14818861','14818685','14818695','14818706','14818715','14818904','14818905','14818728','14818731','14818915','14818502','14818735','14818504','14818737','14740615','14740364','14740367','14740849','14740852','14740399','14740407','14740670','14740417','14740422','14740424','14740684','14740300','14740303','15736097','15736250','15736130','15736139','15708161','15708164','15594492','15708172','15708190','11792016','11792234','11780738','11820469','11820661','11781349','11781418','11821069','11781847','10497754','10497792','36124861','13904267','5283454','5283539','5283540','5283568','5283630','5283635','5286439','5293871','5293880','36317230','36313722','36302196','36298736','5282417','5282667','5282818','6156685','6157660','6158120','6127943','5298851','6139648','6141389','6141474','36125545','36125546','36130052','36130104','25116536','24799396','23947118','29430006','29971852','29428374','29428411','29973771','14346971','14346969','14346896','14345433','14339689','14339693','14339678','14245896','14202861','14199853','13517477','28629039','22595186','14390701','14346899','14346898','14347005','14339704','13617932','26413484','26807229','26806659','17672368','17672577','17672581','17672403','17672406','17672415','17672419','17672484','17672341','17671922','17671758','17671570','17671709','17671720','17671729','17672743','21707788','14346924','14345456','14345460','14346943','34564140','17671069','17670587','17670590','17671628','17671632','17671643','17671679','17671682','17670650','17670660','13268897','13240356','13240454','13269218','36117896','17670144','17670148','17669737','17669867','17669610','17665480','17664774','17665302','17665325','17665344','17669549','17665033','17665471','17664795','5293405','5282721','24799705','22353849','22189613','21944379','14345367','14345364','14346973','12929338','12880337','12880345','12880350','8355169','8382731','7714996','7715252','7754633','7051694','25196852','12454582','12454591','12454594','12453091','12444074','12444077','12444083','12032630','10125878','12376498','10795478','10655552','10656566','5285384','5287405','5277553','5269107','8037520','8037813','6141953','6158236','6158580','6158713','6137430','6137464','6138869','5296659','5296711','5294268','5293334','5295321','5293460','36125500','36125504','36125479','25210245','23280990','13239333','12880367','13240934','12373303','5268891','6139104','6139168','6128963','6139247','6139330','6139356','6129132','6129371','6141082','6141205','6141271','6141398','6136274','6136378','6136451','23279724','17664804','12066281','12066286','12066315','12066320','12066247','6127911','6128018','5296260','5296457','5294024','5294082','5296657','5279235','22232427','17665627','17671891','17665106','17672512','25210634','17665395','17671084','17670302','17672251','17665315','17669830','17671848','17671550','17665543','17670593','17669736','17664697','17670898','17669576','17671233','17670443','17671432','17670612','17671230','36173638','15589870','22237006','25177508','26982038','15589110','5299231','5286117','5286607','5286751','5293948','5286900','25210174','17671459','17672172','6141775','6123692','6136592','6126248','6126345','6136729','6126455','6136862','6136956','6137019','6137106','6137245','6127039','6127255','5299335','17664664','36117182','36132462','36103777','36117081','35848067','35904529','36282253','36286656','36282281','36282308','19474899','25656610','12066249','17670519','17669562','17671395','17671724','17670437','17665272','36313726','17665012','17671671','36117034','17664650','17670990','16354437','17669566','26807492','5781149','5781272','5743141','5743407','8387304','8112894','8112908','8112909','8113834','8115277','8115279','8115282','3882683','8388576','7109393','7550875','7653254','3873151','3873157','3873191','8388396','3872508','3882640','36221428','36228085','36230883','36225709','36272105','36280720','36297362','36265395','36235297','36182215','36225918','36204903','36189412','36164088','36164194','9140008','9140041','36106367','29667745','34430992','35379449','35379490','31897270','13697715','13692510','13559381','13015316','13560486','13404698','13717391','13559350','13500362','13068202','13068209','13068219','15032229','15068642','15068661','15029271','15029276','15065644','15428977','15380631','23436709','23093472','22911740','14939509','14939508','14900743','18527283','14892850','14892873','14892823','14900766','14900789','14900788','14892812','13582783','13559367','13582797','13398758','13550242','13550237','13500392','13697733','13659340','12691728','12691729','12688364','12688389','12688410','12451829','12452371','12453027','12451113','12691736','12691739','12415750','12421834','12337182','12337307','11973306','13550326','13180339','13185107','13187343','13072438','13067970','13182718','13154148','13072407','13063645','13015366','13015308','12850782','12424625','12339889','12376804','12421236','12414506','12414508','12384122','12414520','12414523','12338385','11905210','11967351','11909416','11905596','11973443','11973666','36123602','36126353','36126359','36126361','36126363','36126372','36126375','36126377','36126379','36126385','36126345','36126346','36118129','36153216','36161500','36137752','36164183','14621982','14580898','14505112','14536905','14439290','14439276','14439286','14439281','14465609','14342542','14342540','14787422','14665101','14665098','14580869','14580862','14439293','14439292','14311905','14311902','14583889','14505102','14504684','14504326','14536887','14328925','14328917','14328919','14665138','14665131','14673006','14621994','14622001','14621993','14569690','14502622','14342567','14665578','14645357','14645360','14645372','14645370','14645376','14569664','14569667','14504315','14759271','14665120','14672988','14651962','14651955','14651958','14605916','14504311','14342556','14311920','14762651','14762663','14605942','14505128','14505119','14439275','14328937','14328953','14311929','14311930','14197454','14504301','14651951','14651948','14583879','14583881',
'14583884','14328929','11653655','11653810','11640785','11640839','11640853','11639526','11639563','11639598','11639757','11639450','11632956','11639966','11640092','11636072','11640473','11636179','11640507','11640546','11634730','11636624','11636655','11655905','11653450','11653470','11866420','11866621','11870880','11859542','12329533','12476835','36222439','36222440','36222441','36222461','36222467','13320121','13085907','13085923','14647473','14708624','14647493','14708632','14704392','14708640','14708645','7160940','7386220','14712312','12479870','36232306','14712299','12453589','14226207','14226229','14238909','13689690','13656170','13616637','13616642','13616645','13616647','13619100','13619105','13619126','13619135','13579755','13559293','13579791','13699273','13699286','13699296','13699302','13689558','13689560','13689575','13689578','13656117','13609189','13559254','13616552','15023237','15023248','14941180','15023298','13530765','13369250','14432219','14432227','14464814','14391758','14465975','14465985','14465998','14432214','13313320','14623018','14624321','14624324','14624360','14624370','14624379','14624380','14643647','12498748','12498752','12498756','13500346','14536915','14433004','13530748','13579761','12453591','12448034','13500374','12631225','15015022','15015054','15583086','15583101','15583155','15583055','14760707','14760716','14760720','14786840','14760728','14786849','29718229','29718573','14541515','14541517','14539782','14539806','15014982','15015004','14941159','14941162','14579432','14541492','14704440','14647537','14647513','14704416','14760673','14760677','14794655','14760685','14794656','14760691','14794661','14794637','14760666','14794648','14740282','14740293','14740296','14325716','14296064','14299912','14269277','13045001','13320073','13045003','12625660','12625672','12625683','12625767','12713613','13041878','12713589','12713604','12506316','14325677','14395012','14395020','14395021','14325713','14269284','14237994','12421783','12421816','12448004','12448008','12337628','12625639','12597848','12453598','12453600','12479833','12479837','12479841','12479876','12479886','12604691','12631218','12597776','12597783','12597789','12622788','12622799','12597794','12625625','12597805','12597821','12337672','12337905','12506699','12506323','12448097','12506351','12421768','12421771','12631199','15032235','14901860','36199805','14433007','22188161','14571622','14571642','14571632','14571604','12337660','12421824','13072441','14536917','14901879','14395030','14197474','14577496','14577492','13692532','14577522','13692568','14577545','14238903','14941169','14577561','11639799','36262240','11967889','13891726','13890899','13890928','13891042','13891051','14788425','36228107','13887261','13887273','13887275','13887294','13887861','13893095','14395013','13893337','13893196','13893495','13893760','13892794','13893001','13892807','13893023','13892822','13892832','13893111','13886744','36238431','36241296','36247751','36247757','36250025','36250112','13888469','13888422','13886971','13886980','13886999','13887034','13889300','13889328','13889159','13888830','13888836','26279652','26268336','26268487','26274653','26264135','26273326','26267297','26234362','26234051','26227130','26227152','26262363','26262425','26293558','26287285','26287334','26294655','26288167','26262807','26227250','26234184','26234191','26234272','26226469','26226486','24738447','26233984','24739639','24748708','24749288','24749443','24750739','24751318','24752180','26289314','26276819','26283817','24753577','14580857','14541479','36137736','13891784','13891793','13891951','13891846','13891852','13891862','13892039','13890500','13890013','13890014','13890019','13889835','13891141','13891154','13891472','13890543','13890757','13890569','13890580','13892602','13892630','13892639','13892644','13892398','13892404','13892407','13892193','13892426','13892221','13892457','13892257','13891908','13891647','13891509','13891512','13891702','13891548','13892648','13892671','13892532','13892584','13890638','13887869','13887876','13887905','13887336','13887344','13887363','13887515','13887739','13887741','13887556','26281595','26278009','26278433','26278992','26279505','13888442','13888060','13886957','13884982','26336371','26336835','26340127','26337381','26324986','26301609','26299224','26298233','26300014','26298432','26301635','26332354','26324173','26324225','26322057','26324391','26298648','26295916','26300626','26299011','26300981','26292272','26297116','26341092','26345020','26341222','26345803','26345928','26343533','26343884','26344847','15029309','13691060','30355275','31263973','24515668','26321153','36241274','14244369','14245147','14228385','14244357','13886734','26285303','36138594','15068662','8383418','33860804','36204947','13890601','36289826','13697725','12335956','13656174','36155553','9154545','9154995','9155013','9155021','6396865','6396942','6397377','6397497','5668373','5669092','4272348','4275571','5671591','8428035','9008867','36294954','36289486','36222337','36236546','4277033','4276840','4272066','4205058','4205118','4205268','4131199','4276191','4243430','4243488','4205309','4205333','4203284','4203925','4174328','4130932','4130967','2301819','6351371','6351771','6352122','6396818','6347772','6349696','6336927','6340513','31888720','31790433','31888730','31791130','31791140','31790538','31790615','31497228','31497625','31298201','31673762','31680083','31888631','31889304','31889335','31889440','29568208','25999437','19655114','19658180','19658493','21934816','19650850','19039986','18920332','18919794','18919804','18919813','18919557','18919825','18919560','19800659','19051169','14737354','14737360','14737345','13524639','13548645','13002829','13003416','18907933','18907984','18907997','18908021','18912506','18911125','17378476','18907315','18907323','18907329','18907524','18907172','18907204','18904317','18902609','18006162','18906018','18904055','18904216','18903525','31892204','31892100','32226885','27512950','27874741','28216491','28614324','14737292','31893052','31892480','28615564','28489042','28490052','10153554','10152906','10153122','10136674','10163445','10139741','9747721','9747859','9747879','12632851','10285182','10283689','10323231','10320832','9963640','9963650','9963655','9963662','9964239','9964246','9964247','9966200','9966201','9963635','9964265','9966220','9966222','9964644','9964645','9964655','9964660','9964664','22889270','22914433','24666163','24514449','24667083','24344997','24658608','24659577','24406343','22881296','22881536','22882737','22509623','22595800','22592638','18915452','18916858','18914833','18914371','18915331','18915352','18915403','18915578','18916670','18916174','18916199','18916244','18915948','18916056','18916630','18917379','18914080','18913603','18914097','27149890','27150410','26673697','26383089','20767188','20770203','20773429','20833759','20783663','20784407','20694022','21398638','21400001','21223842','21754490','21755035','21791785','20529350','21609070','18917579','18918401','18918111','18918162','18919831','18919911','18919023','18919027','18919260','18919143','18918917','18918925','18919168','18919007','18918719','18919450','18912779','9830511','9830420','9830422','9830518','9830520','9830431','9830439','9809186','9809187','9809199','9809200','9809212','9809230','9809241','9809251','9840977','9840988','9840994','9841000','9841271','9841388','9830468','9830394','9830397','9830404','9830405','9809185','9941596','9941623','9941639','9941656','9963608','9963609','9915686','9915695','9916384','9916390','9917977','9918015','9916325','9918733','9916368','9786857','9786861','9786879','9786894','9786895','9786896','9786900','9786913','9786934','9786935','9785806','9785815','9785826','9785828','9785830','9785876','9747702','9750120','9747708','9785788','9785789','9785776','18918064','18914474','22873451','18918071','9747853','11897501','11831372','11831419','11831422','11829227','11829238','11833400','11900852','11900872','11830274','11830319','11897502','36266847','29067214','9941613','10323284','9785860','9755873','9782488','9782490','9782506','9782522','36262747','9782538','9782532','9782533','18916128','18913253','9785821','18919022','21367276','11830316','11829253','11831371','36294939','11830312','11906070','9747858','9785796','9782511','18915525','4199072','4201610','18919683','4131031','8574544','8574390','8574466','8574394','8574469','8574407','8574416','8574419','8569643','8569652','8569828','8570189','8569974','8570205','8569981','8570211','8570544','8570547','8570641','8570551','8570552','8570645','8570556','8570560','8570651','8570494','8570500','8570501','8569493','8569262','8569271','8569272','8569275','8569277','8569288','8569300','8570215','8569731','8569539','8569466','8569485','8570604','8570605','8570518','8570521','8570522','8570524','8570317','8570325','8570225','8569191','8569315','8569193','8569197','8569199','8569200','8569204','8569333','8569211','8569339','8569220','8569223','8569239','8569240','8569241','8569245','8569249','8569251','8569256','8569258','8570269','8583871','8583872','8583875','8584178','8583885','8584185','8583896','8583710','8584045','8583936','8583733','8583942','8583496','8583051','8582970','8583062','8583169','8583086','8583010','8583089','8583699','8583700','8583584','8583336','8583337','8583342','8583344','8583254','8583255','8584502','8584348','8584201','8584203','8584205','8584207','8584218','8584223','8584229','8584242','8584243','8576672','8576673','8576987','8576992','8576850','8576734','8576736','8576738','8576739','8576745','8576746','8576749','8576750','8576754','8576755','8576756','8576819','8576820','8576759','8576760','8576762','8576764','8576765','8576701','8577032','8576950','8576953','8577044','8576955','8576960','8577048','8576961','8576968','8576970','8576975','8576979','8576984','8576998','8576999','8577004','8577007','8577085','8577008','8577086','8577089','8577016','8577018','8577024',
'8577029','8576943','8577248','8577249','8577250','8577115','8577117','8577119','8577121','8577053','8577199','8577126','8577201','8577128','8577203','8577130','8577131','8577061','8577207','8577210','8577214','8577287','8577216','8577220','8577221','8577223','8577224','8577225','8577155','8577229','8577158','8577235','8577241','8567681','8567682','8567421','8567442','8575591','8575521','8575522','8575597','8575600','8575601','8575608','8575611','8575540','8575548','8567186','8567353','8567356','8567092','8567094','8567097','8567098','8567371','8567103','8567382','8567385','8567113','8567116','8567119','8567058','8567071','8566306','8566678','8566176','8566005','8566011','8567389','8567390','8567393','8567124','8567133','8567136','8567140','8567142','8567144','8567146','8567154','8567170','8567018','8567030','8567031','8566685','8567620','8567661','8567399','8567401','8567403','8567404','8566258','8576357','8575574','8575495','8575575','8575504','8575756','8575693','8575761','8575615','8575618','8575622','8575623','8575774','8575626','8576252','8576259','8576261','8576266','8576338','8576408','8576275','8575742','8576500','8576424','8564833','8564837','8564844','8564612','8564469','8564478','8564494','8564515','8564522','8564527','8564531','8564543','8564901','8564904','8564909','8564756','8564757','8564922','8564765','8564926','8564927','8564772','8564930','8564777','8564778','8564938','8564783','8564942','8564944','8564580','8564581','8564588','8564595','8564829','8565165','8565166','8565168','8565170','8565364','8565181','8565184','8565282','8565112','8565152','8565762','8565774','8565783','8565651','8565663','8565677','8565551','8565555','8565604','8565607','8565413','8564545','8564363','8564551','8564553','8564375','8564557','8564893','8564896','8564899','8581611','8581004','8580877','8581006','8581010','8580608','8580258','8580369','8579772','8579783','8579789','8579796','8579643','8579718','8579644','8579645','8579646','8580114','8580117','8579972','8579915','8579985','8579989','8579929','8579930','8579932','8579938','8579939','8579941','8579942','8579943','8579948','8579652','8579653','8579654','8579734','8579662','8579665','8579666','8579669','8579671','8579605','8579608','8579505','8579430','8579513','8579437','8579521','8579440','8579441','8579368','8579452','8579384','8580015','8579952','8579899','8579744','8579757','8579760','8579766','8579555','8579558','8579488','8579560','8579422','8582748','8582750','8582848','8582852','8582855','8582860','8582573','8582575','8582707','8582305','8582501','8582280','8582284','8582285','8582024','8582025','8582032','8582033','8582035','8581947','8582046','8582060','8581975','8581978','8581827','8581757','8583091','8583199','8583097','8583210','8583100','8583101','8583102','8583112','8583226','8582733','8582825','8582734','8583261','8583263','8583360','8583273','8583447','8583452','8583453','8583455','8583459','8583461','8583383','8583465','8583468','8583473','8583299','8581772','8584246','8584247','8584456','8584111','8584458','8584150','8584156','8584158','8582106','8582110','8582111','8582145','8582153','8575316','8575483','8575484','8575322','8575323','8575324','8575341','8575954','8575955','8575956','8575958','8576073','8576081','8576089','8574636','8574638','8574135','8574210','8574138','8574213','8574216','8574219','8574226','8574230','8574157','8573764','8573899','8573767','8573684','8573688','8573691','8573693','8573694','8573699','8573788','8573790','8573707','8573797','8573728','8573729','8573730','8574191','8573912','8573943','8573756','8574434','8574349','8574356','8574360','8574361','8574366','8574268','8574284','8574202','8574131','8573634','8573638','8573645','8573648','8573649','8573490','8573744','8571118','8571249','8570992','8571281','8571019','8571052','8571062','8571066','8568415','8568317','8568420','8568142','8568144','8568148','8568149','8568150','8568157','8568160','8568166','8568169','8568176','8568081','8568082','8568198','8568201','8568202','8568091','8568203','8568093','8568205','8568095','8568207','8568209','8568099','8568100','8568236','8568354','8568355','8568359','8568243','8568245','8568369','8568247','8568377','8568253','8568256','8568380','8568263','8568268','8568393','8568298','8568398','8568721','8568743','8568470','8568471','8568476','8568477','8568482','8568108','8568115','8568119','8568121','8568218','8568123','8568222','8568134','8568136','8568228','8568137','8568229','8568139','8568040','8568233','8567907','8567914','8567929','8567948','8567950','8567956','8567958','8568873','8568823','8568668','8568673','8568550','8568558','8568929','8572693','8572699','8572733','8576116','8576240','8576117','8576119','8576244','8576121','8576123','8576124','8576248','8576249','8576126','8576130','8576131','8576132','8576191','8576199','8573493','8573503','8573676','8573511','8573379','8573383','8573392','8575349','8575277','8575351','8575280','8575281','8575282','8575286','8575293','8575302','8575307','8575313','8575315','8573343','8573359','8573367','8573370','8573378','8572901','8572237','8572241','8572377','8572281','8572167','8572174','8572179','8572541','8572555','8572560','8572563','8572452','8572327','8572186','8572072','8572234','8571953','8576534','8576541','8576421','8574851','8574852','8574853','8574856','8574860','8574797','8574800','8574738','8574751','8576094','8575858','8575917','8575919','8575920','8575921','8575781','8575864','8575784','8575786','8575789','8575790','8575792','8575827','8575830','8575832','8575833','8575835','8575837','8575839','8574601','8574607','8574614','8574380','8575122','8575125','8575083','8575143','8575148','8575009','8575010','8575012','8575041','8574918','8574928','8574988','8574782','8574620','8574791','8574626','8574628','8574826','8574827','8574762','8574828','8574763','8574829','8564571','8564573','8564575','8564230','8564235','8564272','8553170','8553051','8553054','8553055','8553060','8553073','8553201','8553082','8553087','8552962','8553118','8553332','8553337','8553341','8553222','8553350','8553224','8553237','8553241','8553242','8553120','8553247','8553250','8553251','8553253','8553371','8553259','8553381','8553157','8553165','8552870','8552871','8553021','8552806','8553393','8553528','8553531','8553548','8553564','8553300','8553302','8553306','8553322','8553324','8553325','8553468','8554129','8554153','8554016','8554021','8554158','8553942','8553943','8553946','8553948','8553746','8553876','8553880','8553749','8553750','8553751','8553754','8553758','8553901','8553766','8553771','8553916','8553775','8553789','8553693','8553798','8553805','8553949','8553951','8553955','8553958','8553976','8553977','8553834','8553842','8553844','8553845','8553999','8553847','8553848','8553849','8553856','8553821','8553568','8553571','8553572','8553592','8553600','8553473','8553606','8553620','8553498','8553626','8553506','8553510','8553639','8552707','8562598','8562602','8562604','8562606','8562624','8552621','8552623','8552777','8552513','8552516','8552523','8552524','8552525','8552435','8552436','8552442','8552143','8552147','8552148','8552259','8552057','8552059','8552070','8552071','8552088','8552089','8551970','8551972','8552095','8552096','8551974','8552097','8551977','8551978','8552102','8552105','8551980','8552106','8551892','8551983','8551984','8552110','8552112','8552300','8552418','8552321','8552118','8552129','8552131','8552132','8552136','8552137','8552139','8552140','8552141','8551834','8551950','8551698','8551857','8551863','8551617','8551868','8551620','8551871','8552475','8552483','8552489','8552493','8552497','8552498','8552503','8552398','8552465','8552747','8552756','8552613','8552618','8563253','8563266','8563268','8563028','8563273','8563274','8563275','8563043','8563283','8556786','8556957','8556787','8556794','8556965','8556803','8556805','8556818','8556703','8556710','8556858','8556724','8556735','8556560','8556569','8556570','8556426','8556431','8556327','8556437','8556439','8556343','8556452','8556464','8556467','8556469','8556478','8556625','8556746','8556756','8556522','8556408','8556409','8556554','8557287','8557123','8557236','8557237','8557140','8557249','8557143','8557146','8557147','8557260','8557001','8556888','8557002','8557003','8556893','8556896','8556897','8557107','8557007','8556899','8557009','8556900','8556902','8557011','8557111','8557012','8556906','8557016','8556908','8557117','8557019','8556913','8556914','8557022','8556915','8556916','8557025','8556919','8557026','8556920','8557031','8556926','8556928','8556929','8556940','8556942','8556773','8556946','8557270','8557271','8557272','8557274','8557275','8557277','8557284','8557062','8556970','8556976','8557074','8556988','8556998','8557094','8557000','8556271','8556405','8556289','8556161','8563924','8564297','8563931','8563943','8563967','8563984','8554790','8554931','8554932','8554949','8554961','8554854','8554855','8555362','8555483','8555495','8555229','8555511','8554211','8554213','8554347','8554351','8554357','8554363','8554657','8554475','8554660','8554669','8554483','8554490','8554499','8554503','8554506','8554367','8554508','8554370','8554513','8554377','8554515','8555319','8555328','8555329','8555334','8555343','8555075','8555191','8554712','8554721','8554724','8554732','8554736','8554746','8554747','8554759','8554464','8554470','8555305','8555418','8555311','8555312','8571617','8571327','8571483','8571487','8571335','8571490','8571358','8571365','8571510','8571367','8571380','8571392','8571400','8571402','8573080','8573084','8573091','8573095','8573110','8572979','8572985','8572987','8572996','8573001','8572831','8573133','8573136','8573142','8573033','8573168','8573169','8573042','8573048','8573056','8573189','8572939','8571549','8571699','8571703','8571706','8571415','8571428','8571724','8571447','8571449','8571450','8571451','8571298','8571465','8571309','8571612','8571474','8571315','8571613','8571317','8571478','8571782','8571944','8571620','8571807','8571645','8571650','8571662',
'8571669','8571839','8571672','8571520','8571526','8571678','8571532','8571679','8571535','8571537','8571541','8571543','8570978','8570845','8570984','8570727','8570731','8570737','8570738','8570747','8570749','8570757','8570608','8570609','8570613','8570617','8570628','8570528','8570631','8570634','8570635','8570637','8571067','8570914','8570922','8570924','8570930','8570933','8570941','8570946','8570806','8570951','8570953','8570958','8570960','8570704','8570963','8570971','8571734','8571760','8571847','8571984','8571547','8571697','8562783','8562791','8562889','8562890','8562893','8562896','8562687','8562897','8562706','8562709','8562711','8562715','8562717','8562719','8562722','8562457','8562727','8562569','8562462','8562576','8560790','8560791','8561184','8561185','8561188','8561192','8561196','8561203','8560694','8560706','8560714','8560723','8560724','8560727','8560729','8560733','8561215','8561233','8561120','8561264','8560779','8561163','8560765','8560771','8560774','8560384','8560274','8560031','8560066','8560421','8560292','8560324','8560325','8560341','8560116','8560225','8560246','8560258','8560260','8559960','8559858','8559760','8559762','8559837','8559842','8559949','8559954','8559955','8563917','8563761','8563285','8557809','8557815','8557816','8557848','8557738','8557852','8557643','8557645','8557649','8558396','8558411','8558280','8558296','8558299','8558640','8558751','8558512','8558760','8558764','8558423','8558596','8558601','8558365','8558368','8558387','8558032','8557918','8557784','8557785','8557792','8557798','8558049','8557880','8558002','8561342','8561469','8561352','8561359','8561361','8562241','8562131','8562139','8562258','8562267','8562274','8562043','8562048','8562050','8562051','8562173','8562057','8562058','8562213','8561879','8561518','8562031','8562033','8562036','8562039','8562042','8561940','8561678','8561798','8561959','8562936','8562937','8562939','8562971','8562190','8562075','8561988','8561990','8561993','8561994','8561998','8562924','8562728','8562736','8562931','8561889','8562003','8562006','8561898','8562010','8562013','8562019','8563853','8563861','8563862','8563866','8563868','8563872','8563877','8563884','8563885','8563889','8563895','8563902','8563903','8563905','8559289','8559421','8559300','8559423','8559178','8559304','8559434','8559315','8559789','8559792','8559660','8559689','8559693','8559695','8559704','8559713','8559719','8559011','8558789','8558912','8558916','8558918','8558811','8558921','8558923','8558610','8558924','8558925','8558932','8559222','8559263','8559021','8559460','8559462','8559626','8559468','8559344','8558947','8558950','8558965','8558975','8549754','8549755','8549756','8549759','8549671','8549764','8549672','8549765','8549769','8549771','8549680','8549599','8549601','8549686','8549609','8549694','8549616','8581989','8581990','8581995','8582003','8582007','8582016','8582017','8582019','8583944','8583753','8583614','8583506','8583511','8583512','8583513','8583636','8583517','8583641','8583522','8583650','8583660','8582508','8582527','8582549','8582554','8582431','8582569','8580659','8580660','8580661','8580662','8580577','8580450','8580451','8580379','8580454','8580383','8580463','8581049','8581051','8581197','8581200','8581141','8581207','8581214','8581161','8581215','8581216','8581022','8581102','8581107','8580926','8580990','8580992','8580931','8580993','8580589','8580404','8580475','8580411','8580979','8580235','8580017','8580087','8580154','8577974','8578043','8577924','8577841','8577937','8577568','8577569','8577571','8577573','8577574','8577577','8577581','8577582','8577583','8577584','8577587','8577589','8577590','8577521','8577592','8577522','8577523','8577524','8577526','8577527','8577528','8577531','8577532','8577533','8577534','8577535','8577537','8577609','8577541','8577613','8577544','8577548','8577551','8577553','8577555','8577556','8577766','8577622','8577695','8577625','8577777','8577699','8577700','8577810','8577877','8577879','8577880','8577883','8577412','8577560','8577563','8577360','8577361','8577324','8577258','8577406','8577260','8577340','8577344','8577345','8581245','8581376','8581247','8581309','8581383','8577943','8577944','8577948','8577951','8578022','8577952','8577954','8577956','8577957','8577967','8577969','8578039','8578040','8578345','8578347','8578199','8578200','8578350','8578351','8578352','8578203','8578353','8578220','8578225','8578053','8578194','8577996','8578003','8578226','8578227','8578233','8578161','8578167','8578593','8578596','8578598','8578503','8578606','8578505','8578608','8578507','8578611','8578442','8578521','8578326','8578330','8578335','8578268','8578340','8578706','8578636','8578709','8578533','8578699','8578702','8578471','9964268','8580609','8580677','8580814','8580680','8580682','8580622','8580688','8580625','8580690','8580630','8580837','8580838','8580644','8578723','8578815','8578816','8578817','8578727','8578821','8578822','8578737','8578615','8578684','8578760','8578695','8579387','8579388','8579390','8579391','8579392','8579393','8579394','8579202','8579206','8579216','8578848','8578919','8578852','8578853','8578857','8578931','8578859','8578865','8578716','8578718','8578812','8579090','8579163','8579094','8579115','8579119','8579120','8578996','8578824','8578827','8578983','8578831','8578832','8578906','8578907','8578840','8578841','8578915','8578845','8584461','8584470','8584475','8576351','8576204','8576209','8576213','8576214','8576111','8576113','8576238','8563513','8563350','8556074','8556083','8555973','8555979','8555980','8555982','8555983','8555986','8555988','8555993','8555627','8555637','8555512','8555519','8555526','8555563','8555461','8555960','8555962','8555963','8555856','8555857','8555721','8555872','8555875','8555597','8555603','8555605','8555610','8555615','8555617','8555811','8555812','8555819','8555834','8555954','8555840','8555842','8555957','8555844','8555959','8556062','8551337','8551339','8551451','8551249','8551455','8551251','8551254','8551269','8551270','8551273','8551278','8551282','8551284','8550758','8550759','8550760','8550761','8550767','8550769','8550771','8550665','8550666','8550667','8550668','8550775','8550669','8550670','8550776','8550671','8550778','8550603','8550607','8550608','8550610','8550611','8550617','8550619','8550695','8550621','8550549','8550625','8550626','8551421','8551533','8551534','8551537','8551538','8551539','8551430','8551318','8551320','8551432','8551321','8551325','8551436','8551438','8551330','8550781','8550783','8550788','8550789','8550723','8550727','8550728','8550730','8550731','8550734','8550735','8550736','8550738','8550742','8550744','8550745','8550746','8550747','8550749','8551644','8551650','8551654','8551674','8551560','8551574','8550992','8550998','8551015','8551290','8551293','8551205','8551206','8551210','8551211','8551311','8551214','8551314','8551218','8551316','8551220','8551128','8551223','8551225','8551231','8551142','8551246','8551085','8551109','8551178','8551119','8550990','8551623','8551624','8551625','8551629','8551772','8551630','8551881','8551884','8551638','8551639','8549216','8549217','8549142','8549218','8549037','8549038','8549042','8549159','8549164','8549049','8548962','8549056','8548964','8546563','8546568','8546571','8546574','8546576','8546373','8546304','8547090','8546881','8546883','8546884','8546887','8546888','8546271','8546194','8546286','8546212','8546220','8546228','8546597','8546601','8546679','8546603','8546681','8546530','8546682','8546606','8546533','8546609','8546610','8546614','8546615','8546618','8546544','8546547','8546548','8546550','8546629','8546554','8546906','8546914','8546751','8546687','8546756','8546688','8546691','8546761','8546765','8546307','8546239','8546240','8546242','8546250','8546251','8546336','8546258','8546337','8546263','8546793','8546641','8546737','8546652','8546740','8546654','8546669','8546671','8545971','8545973','8545976','8545980','8546082','8550064','8550223','8550005','8545203','8545360','8545234','8545242','8545157','8545248','8545250','8545251','8545252','8546056','8545986','8545987','8545988','8546063','8546067','8545861','8546005','8546011','8545875','8546016','8546018','8546020','8545255','8545256','8545257','8545259','8545262','8545600','8545472','8545477','8545484','8545486','8545488','8545275','8545951','8545827','8545958','8545964','8545968','8545840','8545635','8545564','8545456','8545582','8545583','8545584','8545590','8545464','8545465','8545596','8545469','8545612','8545617','8545622','8544912','8545035','8545602','8545604','8545605','8548143','8548144','8548148','8548156','8548160','8548163','8547845','8548423','8548337','8548442','8548443','8548281','8548166','8548290','8550395','8550188','8550199','8550628','8550629','8550704','8550705','8550634','8550708','8550709','8550710','8550644','8550646','8550647','8550648','8550715','8550716','8550650','8550717','8550718','8550572','8550657','8550579','8550660','8550581','8550661','8550662','8550664','8550585','8550592','8550593','8550595','8550596','8550598','8550599','8550600','8548683','8548593','8548684','8548686','8548531','8548536','8548538','8548460','8548462','8548541','8548463','8548464','8548465','8547935','8547871','8547882','8547819','8547822','8547742','8547826','8548552','8548473','8548554','8548476','8548557','8548477','8548480','8548562','8548563','8548490','8548491','8548493','8548494','8548495','8548497','8548318','8548319','8548322','8548415','8548328','8547832','8547752','8547836','8547754','8547755','8547838','8547839','8547761','8547764','8547768','8547770','8547771','8547774','8547700','8547776','8547702','8547705','8547706','8547708','8547710','8548724','8548726','8548727','8548642','8548731','8548732','8548733','8548735','8548738','8548740','8548571','8548664','8548578','8548579','8548668','8548585','8547829','8544549','8549620','8549540','8549546','8549635','8549469','8549573','8549580','8549398','8548299','8548304','8548120','8548125','8548126',
'8548127','8548138','8548142','8548951','8548954','8548743','8548748','8548751','8548753','8548755','8548756','8548763','8548765','8548692','8548694','8548775','8548697','8548779','8548788','8548702','8548703','8548794','8548705','8548799','8548709','8544842','8544669','8544671','8544672','8544673','8544675','8544677','8544678','8544679','8544765','8544685','8544686','8544688','8544690','8544692','8544775','8544694','8544779','8544696','8544780','8544697','8544698','8544701','8544785','8544704','8544706','8544451','8544458','8544467','8544396','8544477','8544402','8544403','8544406','8544948','8545081','8544863','8541488','8541497','8544409','8544410','8544275','8544416','8541307','8544667','8539110','8539206','8540067','8540610','8540277','8583099','8558594','8544303','8544304','8538661','8538933','8538471','8538738','8538293','8538538','8537675','8559849','8537102','8536872','8537142','8536930','8536942','8549462','8549463','8549294','8549295','8549296','8549297','8549222','8549223','8549387','8549393','8549229','8549396','8549234','8549245','8549169','8549261','8549265','8549267','8549276','8549277','8549208','8549215','8549722','8549813','8549727','8549728','8549730','8549735','8549741','8549743','8549751','8549994','8549928','8549937','8550511','8550513','8550448','8550455','8550526','8550462','8550463','8550538','8550466','8548903','8548917','8548918','8549004','8548819','8548821','8548926','8548712','8548714','8548625','8548807','8548716','8548814','8548722','8548934','8548936','8548942','8548948','8550542','8549402','8549407','8549408','8549413','8549415','8549416','8549421','8549422','8549281','8549283','8549284','8549286','8547440','8547441','8547442','8547445','8547446','8547447','8547451','8547317','8547324','8547260','8547334','8547263','8547232','8547233','8547309','8547238','8547147','8547152','8547093','8547095','8547098','8547100','8547101','8547102','8547103','8547180','8547110','8547111','8547112','8547113','8547714','8547715','8547717','8547718','8547724','8547728','8547492','8547494','8547592','8547501','8547122','8547197','8547198','8547200','8547127','8547201','8547128','8547129','8547132','8547137','8547138','8547141','8547070','8547080','8547083','8547086','8547266','8547338','8547274','8547347','8547351','8547279','8547280','8547281','8547210','8547357','8547286','8547217','8547289','8547219','8547605','8547516','8547517','8547432','8547438','8547292','8547296','8547226','8547299','8547120','8562619','36190298','36190314','36197014','36197020','36197023','34001936','34049898','34632577','34017316','34633162','33985967','34022207','34023072','34024455','34026506','34029967','34031158','34031480','33995860','33999531','31334223','31341383','27622546','27623607','27631152','27555533','27518521','27555293','27555313','27420245','27421771','27422201','8540702','8540911','8540940','8536732','8536763','8536771','8536534','8541020','8540837','8540684','28765645','8537719','8537799','8538371','8538378','8538410','8537986','8544988','8544881','8544791','8544885','8544891','8539010','8537447','8537266','8539521','8554486','18919781','8581803','8579496','8573216','8559848','18919412','8550868','8577714','8550869','8577717','8546036','8546038','8546045','8546046','8546049','8575873','8575874','8575882','8575892','8557326','8576042','8557329','8557331','8551923','8551925','8551929','8551931','8551471','8551937','8551483','8551938','8551484','8551487','8551488','8546725','8551497','8551500','8546055','8547863','8547865','8545385','8545387','8550854','8550861','8580178','8580182','8579807','8579809','8580190','8579817','8579825','8579826','8579827','8579829','8581726','8579832','8579833','8579835','8579837','8577719','8577720','8577723','8577726','21348673','8580349','8580354','8580348','8552857','8552858','18918360','8581837','8568385','18919784','8546958','8554322','8546962','8551896','8551899','8551901','8551907','8551909','8551912','8551916','8551917','8551920','8560794','8547205','8557667','8557684','8557685','8557300','8557301','8557692','8557311','8576029','8557699','8557313','8557702','8557704','8557320','8557321','8557709','8557324','8576394','8580415','8576331','8580417','8580424','8580440','18911697','8570983','8570403','8575056','8575049','8568392','24659024','18918739','8546384','8545088','8546703','18915419','18915944','8583987','8578971','8578280','8578281','8578282','8578284','8578285','8578286','8578287','8578288','8579072','8578290','8578291','8578292','8578296','8579084','8578303','8578304','8579092','8560536','8560554','8560556','8560582','8584520','8579098','8579106','8579108','8578274','8578275','8578278','8557341','8557349','8575006','8564406','8536509','8553414','8553418','8553431','8553435','8553439','8553442','8549907','8544626','8570112','8570105','8570122','8570102','8570098','8564302','8544546','8555304','8569312','8547692','34027940','34031470','3594323','36204530','36215691','36213621','36215866','36214808','36214822','36196621','36188940','11768723','10524722','14767058','14767061','14767086','13939304','13939204','13939307','13939309','13939316','21931010','13776383','14134880','13013363','13013375','34435373','28436915','36138779','36129353','13701258','13695970','13396100','24792084','36257448','13002569','36217693','36130344','3600517','3589803','3594252','3594425','3617445','3609742','3619076','3612298','3628916','12963604','5499384','5499327','5497367','5492913','5493948','36249681','36226571','36196016','30412360','30971151','36123933','33044893','32799268','36124888','36124935','36123649','36125237','36123678','28441191','36127984','27960045','27969206','27961652','27961872','27964197','27968434','36322922','36202681','36160452','36124841','36124846','36123635','36129083','27186394','10913517','10501653','10501623','10501614','10498172','11610026','11610310','11642525','11642602','11640544','11636874','10501641','10498216','9275735','9281173','9280854','36126198','27960924','36283980','27963910','33512691','36122640','36124927','29175917','11599242','11599245','11599254','11599276','11599280','11599293','11599204','11599209','11599225','11599231','36126246','36126237','16510344','16392647','16392674','16392678','16510402','16392758','16392819','16392833','16392912','16392916','16392951','16392240','16392249','16392254','16371034','16392289','16371055','16392291','16392347','16371087','16371114','16392421','16371122','16370810','16371131','16511867','16511903','16392472','16371187','16370897','16371211','16371222','16370932','16370952','16370956','16370187','16370199','16370500','16369878','16370514','16370235','16370257','16511964','16511636','16511654','16511677','16511701','16511708','16511719','16511228','16370557','16369948','16370566','16370293','16370316','16369997','16370325','16369999','16370006','16370337','16370017','16370349','16370041','16370071','16370086','16370410','16370713','16370128','16511399','16511025','16511403','16511069','16511075','16510775','16510797','16510516','16510846','16510525','16510534','16510877','16510900','16510621','16511789','16511330','16511347','16511008','36071307','36130583','36104640','16370146','16370454','16369596','16369626','16326261','16369340','16326271','16369347','16369669','16369673','16369378','16369396','16369405','16369421','16369426','16369442','16369746','16369747','16369453','16369469','16369500','16369827','16369513','16369843','16369869','15738171','15738092','15738178','15738180','15737999','15738181','15738000','15738002','15738184','15738117','15738119','16315624','15738014','15738129','15738016','16315784','15738028','16316272','15738140','15738029','15738030','15738070','15738073','15738075','15738081','15738083','15737361','15737365','15737480','15737482','15737373','15737487','15737496','15737498','15737282','15737502','15737284','15737287','15737509','15737379','15737380','15737300','15737528','15737532','15737389','15737309','15737398','15737535','15737312','15737314','15737766','15737407','15737324','15737409','15737411','15737415','15737327','15737423','15737425','15737428','15737437','15737440','15737353','15737354','15737167','15737453','15737455','15737180','15593199','15593623','15593419','15593428','15593648','15593433','15593457','15593463','15593470','15593271','15593275','15593287','15593289','15593294','15593311','15593316','12477412','12480613','12480897','12446710','12446752','12600113','12572808','12033193','12028571','12013443','12013464','12444800','12444837','12444863','12030681','12474369','16511488','15736312','15736141','15736179','15736066','15736205','15736075','15736078','15736083','12801685','12802148','12802180','12841681','12841721','12841907','12609417','12609624','12609748','12621933','12622317','12622425','12599035','12599779','12689414','12712121','12622467','12623166','12601108','12601329','12655358','12655498','12655540','12655587','12578845','15594218','15593822','15593838','15593672','15593882','15593887','15593685','15594292','15593915','15593928','15594320','15736525','15736676','15736530','15736534','15736540','15736699','15736544','15736419','15736548','15736422','15736572','15736446','15736261','15736579','15736582','15736458','15736464','15736280','15736470','15736600','15736300','16511208','16510643','16510675','16510683','16510996','16510328','12803803','12803854','12841600','12841614','12841625','12841636','12841639','12841643','12841649','12777644','12841652','12801127','12841670','12801516','12801546','14740313','14740701','14740332','14740088','14740092','14740350','14740100','14740357','14740110','14740117','14740118','14740119','14740124','14740126','14603551','14603554','14740142','14603560','14603565','14603570','14740152','14740154','14603584','14740164','14603585','14740165','14740005','14740007','14740012','14739920','14578439','14739940','14740167','14739949','14739951','14740176','14740177','14740180','14739977','14740696','14740697','14740312','14819036','14819043','14819046','14819050','11901003','11906518','11906658',
'14739941','14578472','14579139','14492172','14489159','14600660','14600664','14489174','14443077','14573268','14492142','14578399','14578405','14443089','14488127','14488133','13319241','13377640','13377771','14443069','14443071','11791861','14740900','14740709','14740907','14740721','14740731','14740939','14740743','14740943','14741044','14740947','14741048','14741066','14741068','14740772','14741072','14740775','14740460','14740798','11307320','11307776','11510908','11511072','11511112','11195574','11189528','11968201','11962761','11968482','11968509','11968532','11968604','11973762','11972816','11966170','36249682','15736453','11175183','11175230','11175257','11175337','11175392','11175397','11175490','11175509','11175524','15736417','15736418','11769801','11770057','11770368','11770445','11780185','11780284','11717439','11086615','11062351','11062385','11062420','11064165','11064287','11064725','11064897','11064937','11065130','11065182','11065227','11129903','11130011','11130049','11120124','11130216','11120256','11120340','11086657','11086682','11086715','11120538','11120620','11086807','11121564','11086846','11086991','11087041','11089652','11089792','11122346','11089835','11089866','11122784','11089997','11090074','11090353','11085915','11086004','11090743','11090765','11086032','11090798','11086206','11086307','11086453','11086501','11062173','11966211','11966459','11939125','11939192','11939789','11939989','11940123','11940223','11195601','11195618','11306422','11306668','11306699','11940363','11961252','16369172','16369150','16369212','16369203','36108875','35747504','35750471','36216984','36220265','36221561','36158504','36179927','36212432','16512221','16512370','16512385','16512105','16512124','16512191','16512195','36148582','16512457','16590694','15593524','15593529','15593321','15593330','15593339','15593346','15593356','15593383','15593186','15593387','15593600','15593390','15593192','15737234','15736856','15736732','15736738','15737254','15737255','15736746','15736750','15736754','15736879','15736903','15736609','15736610','15736612','15736784','15736615','15736932','15736787','15736617','15736797','15736487','15736491','15736646','15736494','15736649','15736500','15736652','15736384','15736400','15736401','15593762','15593999','15594011','15593800','15736824','15736828','15736719','15736721','15204141','14819190','15203946','14819241','14819242','14818977','14819252','14819005','14819018','14819022','14819286','14819290','15204396','15204404','15204289','14819325','15030107','14741198','14741233','14741091','14741094','14741249','14741101','14741256','14741118','14818652','14818660','14818423','14818458','14818486','14741277','14740971','14741139','14740973','14740981','14740983','14740985','14740990','14740993','14741020','14741172','14741021','14741173','14741024','14741031','14741183','14741196','14740889','15204489','15204182','15204355','15204385','15708211','15733869','15708054','15708059','15594417','15708063','15708068','15594424','15708073','15708076','15708080','15594443','15594045','15708096','15594063','15708111','15594073','15594105','14818937','14818941','14818522','14818525','14818943','14818528','14818529','14818535','14818956','14818537','14818959','14818541','14818543','14818559','14818566','14818577','14818583','14818598','14818814','14818614','14818621','14818635','16369228','36278074','11140399','11140935','11140986','11141034','11126067','11128614','11128636','11940465','11900999','11901000','11901001','11901002','14819056','14819078','14819081','14819086','14818832','14818833','14819097','14819103','14818853','14818854','14818689','14818863','14818694','14818874','14818699','14818702','14818888','14818712','14818890','14818895','14818898','14818720','14818727','14818733','14818921','14740361','14740804','14740845','14740846','14740621','14740374','14740630','14740389','14740204','14740659','14740224','14740229','14740420','14740679','14740421','14740238','14740687','14740302','15736098','15708126','15736239','15736109','15736244','15708139','15736117','15708154','15708171','15708185','15594360','15708197','15594366','15594367','15594371','15708207','15708210','11780884','11820763','11820804','11821126','11782214','11782347','11782470','8334192','5294272','5294303','36301231','36300306','5296802','5283002','5283095','5283103','6157604','6159592','6139585','6140259','6140402','6141368','6141742','6126787','36130076','25201012','29974216','29429509','29429922','30036486','29973492','29428236','29428333','29974094','14346963','14346992','14345432','14329275','14345431','14345442','33729668','32087741','22780638','22575233','14347017','14347029','14387086','14345396','14347009','14345451','26806763','26806858','26807031','26811404','26717203','27054674','17672379','17672605','17672443','17672225','17672476','17672293','17672055','17672062','17672073','17672081','17672116','17671932','17672009','17672011','17671752','17671810','17671972','17671982','17671988','17672197','17672725','17672459','17672237','21708152','21708680','14346945','14245316','14228388','13663318','34419816','33730168','33730182','17670468','17670484','17670536','17670800','17671480','17671489','17671091','17670244','17670295','17670086','17670108','17671330','17670819','17671176','17670606','17670620','17670685','17671831','17671610','13085154','13240656','13085048','13085010','27624997','17669899','17670142','17670412','17670168','17665524','17669871','17669883','17669623','17665641','17669694','17665253','17664942','17664954','17669761','17669765','17669793','17669560','17665381','17664996','17665412','17665427','17665052','17665062','17664695','17664808','17664872','17665509','5287452','5286584','24799888','24799933','22308186','21942517','14343334','14346918','14329297','14329291','12880341','12880353','36220235','7788690','7086966','12449488','12454586','12453087','12028292','10857022','10857935','10788716','10794793','10794857','10381212','10381865','8686687','10131257','12372464','12373510','12373799','10656478','10665981','17672176','5287370','5285730','5287491','5287498','6142259','6158053','6138925','5287627','5287700','5294382','5293415','5295335','5293716','25210347','24553708','36125490','36125491','36125524','36125527','36125480','23281018','23279993','23280102','23280184','12880364','13268267','5269905','6139191','6128984','6139295','6129036','6129076','6129180','6129288','6139913','6139976','6136303','6141624','6123450','23279800','12066290','12066291','12066317','12066248','12066255','12066266','12066238','6128079','6128220','6128514','6123015','6123295','5296565','36217419','36125483','5267542','17670781','17670162','17671496','17671822','17665460','17670506','17664965','17672013','17670088','17670452','17665250','17665370','17670668','17671009','17670549','17672665','17669730','12066304','17672544','17665433','17665582','17670546','17670102','17672612','17665530','17670222','17664718','17672628','17669744','17672608','17670297','17670429','17672190','14245558','36132151','22203947','15590558','22249278','17669515','22579357','22181619','13268091','15586594','5293777','5286643','5286718','25210079','25210149','25210159','25210218','17669509','17671385','29976186','6123640','6136677','6126553','6126657','6126990','6127060','6127274','6127345','6127409','5299283','6127413','6127664','5265954','17664855','36117151','36117172','36117190','36117191','36104349','36104380','36104390','35171086','35171568','35850513','35852613','35905380','36262586','36262673','36273315','36273336','36273351','36273378','36154687','36171528','36173916','36173933','23280601','30543930','12066297','17665355','17672143','17669485','17671047','17670934','17669675','17670472','17671308','17670079','17672530','17670591','17671842','23280209','5417539','5421323','29430030','17671942','17672307','17671992','17664878','36118851','36282298','5298941','5782207','5751654','8388006','7271174','2471018','8388703','3882932','3872228','3872287','3872468','3873022','4198960','3882256','36212258','36347216','36278388','36279503','36278287','36276070','36290880','36304221','36265485','36260074','36253932','36262862','36262494','36232287','36232291','36232296','36199801','36199807','36202696','36164207','36164139','9140043','36114279','29667434','35430930','35379245','35379545','33254075','31636482','13692528','13559395','13559388','13559391','13582918','13582861','13582925','13560535','13659383','13659386','13560495','13697730','13720024','13720789','13559341','13559351','13550206','13550223','13500366','13068214','13068281','13068286','13068288','13067974','13067982','20442722','15029318','15029339','15032232','15032247','15068660','15068673','15032289','15068632','15032237','15068635','15068639','15029259','15029287','15029288','15029305','15065641','22816894','23083834','14939495','14903503','14939516','14903499','14892943','14903482','14892912','24242322','14939486','14939489','14939492','22275372','22276463','22314729','14892833','14900785','14903447','14903440','14892888','14900771','14900760','25540587','14903538','14903526','14901867','14901869','14901861','13582813','13582816','13582804','13582848','13582829','13550253','13550269','13500403','13718233','13697750','13697747','13670763','13659355','13550305','13520584','13500342','13500336','13500332','12773958','12688400','12688417','12688422','12688426','12803672','12803675','12803682','12803686','12803705','12476223','12476724','12458715','12453814','12457340','12691738','12691745','12691748','12710876','12710901','12414539','12416694','12419221','12386022','12419362','12329906','13063613','13063635','13582870','13582872','13500356','13158186','13185263','13072429','13072448','13072452','13072457','13067958','13067964','13183073','13068421','13068425','13072412','13072417','13015359','13015363','13015300','12850680','12421544','12414538','12376703','12421450','12414518','12414528','12336326','12380858','11967006','11967190','11968159','11909477','11973497',
'36126402','36126411','36126431','36126436','36126351','36126364','36126365','36126367','36126370','36126373','36126380','36126387','36126391','36126393','36126349','36118141','36148994','36138684','36138691','36137741','36137718','36137720','36162874','14712304','14645415','14602788','14605957','14580894','14342551','14342546','14197395','14197387','14645457','14580867','14502634','14502626','14342565','14672972','14651974','14583891','14583893','14536878','14504327','14665139','14673008','14621997','14580856','14569685','14569680','14504321','14505157','14505149','14342571','14665581','14651987','14645349','14645368','14580891','14580886','14580889','14569673','14569672','14502651','14504319','14504320','14328936','14342535','14244561','14712336','14672990','14665117','14672983','14665114','14672979','14651970','14651967','14651963','14651969','14651954','14331339','14311921','14645409','14605921','14605934','14605954','14605953','14569693','14569697','14328940','14328945','14328948','14328951','14311932','14197432','14602793','14502661','14197465','14197468','14712277','14651950','14583883','14505133','14505136','14432063','14328926','11653608','11653727','11653745','11653782','11653791','11640806','11654439','11654493','11654638','11637951','11638002','11639381','11639490','11635630','11635718','11632923','11610303','11610439','11610765','11640110','11636124','11636227','11636272','11636534','11636764','11656473','11835322','11835344','11150886','14624325','14624328','14624343','14624348','7494050','7654932','36222463','36222475','36222479','36222486','14580868','36273286','36278312','13085914','13085930','14708628','14647499','14708636','14708651','14704404','14647512','13500354','14237997','14238007','14267858','14267880','14238037','14268648','14268652','14268653','14238898','14226241','14268849','14226242','14226260','14226263','14239458','14228818','13689601','13656136','13616627','13616633','13689660','13616687','13616694','13619149','13619155','13559280','13559284','13616599','13579766','13616613','13530572','13530601','13689700','13699283','13699291','13699298','13699301','13699304','13699306','13699318','13699322','13699325','13699327','13689569','13689593','13609162','13609226','13609247','13609249','13609262','13559242','13616539','13559266','13559278','15149779','15149825','14941187','14941188','14941194','14941195','14941198','15023331','14941208','14941243','13530755','13559239','14464786','14432226','14466625','14464807','14464809','14395054','14391757','14391761','14391762','14465971','14465974','14465992','14465997','14466000','14432208','14466601','13320099','13320102','13320123','13320152','13313337','14643669','14643670','14643674','14738768','14738968','14738993','14624366','14624372','14624375','14624382','29718308','12498751','12498754','12498757','14433012','12414526','15149692','15149704','15583119','15583121','15583135','15583138','15583148','15583215','15583257','14786829','14760718','14788344','29718290','29718337','33100938','36126420','14541508','14541512','14539775','14541519','14539784','14539797','14539799','14539801','14941254','14941151','14941153','14941158','14941160','14941170','14941172','14941178','14579627','14647535','14647533','14760678','14794654','14760683','14760700','14760702','14760703','14794632','14740265','14740276','14740280','14788387','14740295','14740297','14395031','14299906','14299922','14299945','14299946','14269077','14269276','13028929','13045060','13045077','13034355','13034365','13313206','13313211','13313216','13313222','13313283','13313292','13045018','13313303','13313308','12625657','12625665','12631235','12713614','12713631','12713639','13044992','13044993','13015351','13034327','25544070','14391766','14391781','14391787','14325665','14325670','14325675','14325683','14395018','13015284','14269282','12453585','12421791','12421831','12331586','12337621','12337631','12597839','12453587','12506362','12453592','12479866','12448047','12506422','12506424','12506586','12506590','12448061','12479883','12448068','12506683','12506696','12622766','12597777','12622784','12597792','12622804','12597803','12597806','12597809','12597818','12625634','12448036','12337653','12337663','12337669','12337675','12421750','12448100','12448107','12421754','12421765','12631176','12631179','13689666','14237986','14464789','14433011','14536275','12448064','13550226','13068412','13582857','13689640','14788347','14536921','36204956','14577533','14577543','14239448','14238882','14577558','12625703','15068663','14577562','36261598','13888949','13888972','13888984','13888756','15032233','14788368','14788386','14788362','14788383','14788396','14788421','13891554','13891749','13891771','13891624','13891358','13891402','13891036','13891424','14788399','13887589','13887600','13887602','13887619','13887629','13887240','13887241','13887244','13887169','13887921','13887991','13887832','12498734','13893136','13893795','13893945','13893270','13893000','13893007','13892894','13886898','13886915','13886916','36241174','36241383','36247763','36248938','36248947','36219922','36236357','13888773','13888790','14579446','13887323','13887200','13886822','13887057','13889594','13889597','13889297','26324849','26274554','26269246','26265856','26272996','26273242','26266673','26266740','26266902','26267102','26234291','26234304','26293688','26288223','26291572','26262864','26234282','24749180','26226887','26226898','26226943','24751004','26284778','26280523','26281156','26291672','26283612','26288510','26283786','26291970','13550279','13891927','13891789','13891953','13891992','13891884','13891887','13891890','13890149','13890152','13890157','13889973','13889396','13889792','13889838','13891434','13891441','13891449','13891458','13891262','13890529','13890545','13890553','13890770','13890820','13892633','13892636','13892371','13892391','13892436','13892230','13892445','13892451','13892458','13891902','13892090','13891916','13891628','13891669','13891714','13891552','13893119','13892663','13892717','13892574','13892732','13890826','13890594','13890605','13890673','13890293','13890346','13890407','13890408','13890067','13890071','13887751','13887576','26278141','26278231','26286245','26282582','26282773','26283190','26267824','13888440','13888057','13888086','13888113','13888296','13888308','13888310','13892473','26338076','26339557','26336495','26336635','26336711','26339822','26336771','26339974','26336928','26340151','26334580','26334728','26337441','26334898','26337675','26324652','26337814','26331494','26331551','26299795','26295021','26299885','26299951','26295080','26298332','26298479','26295307','26298632','26331888','26332108','26302100','26323622','26321531','26332640','26324290','26332907','26324548','26295835','26300686','26296306','26292682','26292853','26345184','26341669','26345882','26343574','26343837','26337874','26344295','26344541','26340850','26340925','26340713','26340752','30805972','31264206','36221617','36199771','36199792','13886711','14244375','14245153','14244358','26274590','8390111','13656085','14794625','9154546','9154755','9154757','9155007','9155010','9155011','9155015','4091306','4354975','4277574','6396905','6396973','6397000','5657387','5667395','5658571','5668800','5669200','4277121','4277156','9154594','5671269','5671366','5671466','8390127','36155519','4202577','36278705','36284321','36266746','36266875','36189022','4272242','4277070','4268767','4201733','4202256','4205262','4202383','4131155','4243204','4276304','4276555','4205352','4203339','4131216','4174320','6397415','6350680','6350804','6352349','6352364','6352469','6379558','6344748','6346906','6350115','6337914','6350500','6338944','31891739','31790506','31790765','31671761','31677472','31677870','31679128','31679474','31679796','25999787','25999850','19706384','19653173','19653854','22010184','22359439','18920146','18920278','18920302','18937505','18907948','18908080','18913164','18911513','36157128','18907308','18907318','18907355','18905467','18905058','18904335','18905203','18902955','18902712','18903297','18906052','18904225','18903499','16589661','18006081','31888928','31889704','34375069','31891366','31892291','33467811','31892082','32475358','31892886','31892905','34936795','33563701','33564551','33566021','27916858','28077410','28614182','28614285','14737367','14737283','14736229','14737324','14492193','15191109','29068428','31893070','28077132','10136173','9751618','9747855','9747869','8708617','8681806','11864913','9963651','9964238','9963627','9966202','9966206','10013613','9966211','9994462','9964625','9964633','9964648','23015126','22883786','24481394','24514984','24515452','24660059','24405698','24407067','24344623','22503636','22881622','22593279','22499571','22592700','18914554','18915190','18914645','18917270','18913905','18913543','18915269','18914864','18914952','18914958','18914983','18915432','18915680','18916416','18915907','18916037','18916639','18917370','18917076','18914087','18914095','18913618','18914113','18914118','18914123','18914133','18914136','18913730','18913771','18913780','18915804','27454500','20773212','20843427','20736166','21398470','21398769','21400451','21974142','21800361','21645921','21752914','21753049','21755359','21755597','21792456','21611952','21612840','21376512','21377007','18917532','18918057','18918067','18917687','18918414','18918168','18919621','18919385','18918505','18917423','18919255','18918770','18919404','18919416','18919432','18920002','18919492','18919512','18911867','18912769','18912773','18912808','18911729','18911479','9830513','9830515','9830430','9830433','9830436','9840974','9809265','9809191','9809192','9809193','9809202','9809204','9809211','9809214','9809217','9809222','9809228','9809234','9809235','9809257','9809258','9840978','9841006','9841266','9841286','9841291','9841304','9830451','9830464','9830484','9830398','9830492','9918770','9918779','9941630','9915677','9963622','9915687','9916378','9915696','9916386',
'9916388','9915705','9916389','9915709','9917249','9915724','9917984','9917990','9918000','9916303','9916304','9916305','9918008','9918022','9916323','9918731','9918736','9916362','9918737','9918739','9918749','9918752','9918762','9786906','9786910','9786916','9786928','9786936','9785866','9785867','9785868','9750185','9747849','9785804','9785775','18914518','18909829','9841262','9841257','9841267','9841311','11905859','11897513','11831421','11829230','11829246','11829252','11900877','11897506','11906975','36140662','33005225','36213622','11905487','36223398','18914957','22510235','21377090','4277189','28228381','9830487','9747743','11830323','18918970','9841281','9841293','11902341','9999656','4272328','9830481','11830281','9915737','11906370','11905433','9830407','18918974','16592672','4131038','4275504','22006315','18916626','31790697','18918984','31891683','8574387','8574463','8574391','8574392','8574393','8574395','8574396','8574397','8574399','8574473','8574474','8574478','8574410','8574482','8574411','8574413','8574418','8574334','8574420','8574421','8574337','8574340','8574341','8569629','8569646','8569651','8570182','8570187','8570202','8570642','8570649','8570650','8570490','8570493','8569390','8569405','8569267','8569269','8569270','8569274','8569281','8569285','8569287','8569291','8569297','8569306','8570221','8570018','8569838','8569843','8569845','8569848','8569849','8569860','8569726','8569735','8569708','8569551','8569442','8569450','8569453','8569456','8569459','8570502','8570504','8570509','8570510','8570511','8570512','8570513','8570515','8570517','8570309','8570318','8570223','8570226','8569310','8569311','8569326','8569330','8569224','8569259','8570242','8570252','8570256','8570266','8570273','8570281','8583869','8584164','8584169','8583997','8584171','8583877','8584176','8583881','8584180','8584183','8583890','8583895','8584187','8583897','8583899','8583900','8584023','8584030','8583711','8584033','8583912','8583915','8583918','8583923','8583930','8583725','8584061','8584070','8583490','8583493','8583313','8583499','8583501','8583047','8583050','8583052','8583054','8583055','8583065','8583170','8583172','8583173','8583073','8583178','8583080','8583181','8583001','8583084','8583186','8583189','8583014','8583670','8583672','8583678','8583682','8583687','8583548','8583688','8583555','8583698','8583702','8583704','8583322','8583327','8583431','8583332','8583339','8583257','8584503','8584196','8584209','8584222','8584233','8584235','8584236','8584239','8584240','8576667','8576668','8576669','8576670','8576671','8576988','8576993','8576994','8576785','8576815','8576678','8576831','8576944','8576945','8577033','8576946','8576948','8576949','8577038','8577040','8576954','8576958','8577046','8577051','8576966','8576967','8576973','8576977','8576978','8576981','8576839','8576983','8577136','8577145','8577001','8577002','8577003','8577009','8577090','8577092','8577020','8577026','8577246','8577176','8577247','8577179','8577182','8577183','8577125','8577056','8577057','8577202','8577058','8577059','8577060','8577205','8577062','8577350','8577353','8577354','8577208','8577355','8577281','8577209','8577356','8577288','8577222','8577226','8577227','8577228','8577159','8577231','8577163','8577234','8577165','8567405','8567407','8567410','8567412','8567414','8567685','8567417','8567693','8567694','8567696','8567697','8567606','8567479','8575513','8575593','8575520','8575604','8575606','8575444','8575607','8575532','8575538','8575547','8575551','8575552','8575553','8567182','8567183','8567185','8567190','8567358','8567090','8567102','8567375','8567105','8567378','8567107','8567112','8566687','8566680','8566683','8566403','8567121','8567122','8567126','8567127','8567128','8567132','8567134','8567138','8567143','8567148','8567151','8567152','8567156','8567159','8567163','8567002','8567166','8567171','8567176','8567022','8567029','8566034','8566243','8566122','8565937','8566616','8566623','8566630','8566612','8576505','8576508','8576511','8576355','8576519','8576358','8576365','8575554','8575564','8575566','8575568','8575569','8575490','8575492','8575493','8575499','8575579','8575580','8575506','8575585','8575587','8575689','8575755','8575758','8575700','8575616','8575768','8575769','8575770','8575621','8575771','8575773','8575625','8576251','8576254','8576323','8576260','8576263','8576264','8576265','8576270','8576339','8576271','8576341','8576274','8576343','8576344','8576345','8575747','8575686','8576569','8576501','8576422','8564601','8564603','8564606','8564840','8564610','8564463','8564489','8564323','8564512','8564524','8564345','8564534','8564538','8564358','8564541','8564360','8564544','8564906','8564911','8564914','8564924','8564770','8564928','8564935','8564939','8564586','8564590','8564591','8564592','8564594','8564598','8564600','8565163','8565171','8565177','8565179','8565186','8564852','8564854','8564866','8564870','8564871','8564874','8564875','8564878','8564882','8564884','8564885','8564888','8565460','8565465','8565250','8565257','8565267','8565103','8565115','8565118','8565122','8565138','8565141','8565155','8565766','8565767','8565781','8565789','8565646','8565656','8565527','8565843','8565681','8565687','8565539','8565543','8565546','8565554','8565714','8565412','8564550','8564367','8564555','8564379','8564561','8564566','8564386','8564570','8564897','8581528','8581614','8581540','8581625','8581628','8581015','8581019','8581020','8580789','8580902','8580904','8580666','8580606','8580670','8580263','8580268','8580280','8580281','8580370','8579771','8579775','8579778','8579779','8579780','8579784','8579788','8579852','8579854','8579792','8579793','8579642','8580111','8580113','8580051','8579962','8579963','8579965','8579911','8579973','8579974','8579913','8579914','8579977','8579979','8579919','8579984','8579923','8579924','8579925','8579926','8579927','8579928','8579992','8579933','8580004','8580006','8580007','8580008','8580010','8579648','8579583','8579657','8579659','8579599','8579603','8579623','8579509','8579431','8579514','8579435','8579517','8579371','8579377','8579380','8579381','8579382','8579385','8579798','8579892','8579800','8579897','8579900','8579906','8579907','8579756','8579758','8579759','8579761','8579763','8579765','8579551','8579552','8579553','8579557','8579416','8582744','8582838','8582844','8582864','8582441','8582577','8582446','8582579','8582705','8582599','8582298','8582302','8582311','8582502','8582314','8582159','8582390','8582289','8582048','8582051','8582052','8581974','8582065','8582066','8581823','8581751','8581832','8581834','8581836','8581842','8581760','8583090','8583194','8583019','8583200','8583203','8583204','8583098','8583206','8583213','8583214','8583219','8583045','8582824','8582736','8583351','8583262','8583265','8583266','8583267','8583270','8583446','8583274','8583278','8583464','8583384','8583466','8583476','8583300','8583478','8583301','8581767','8581773','8581774','8581709','8581559','8581563','8581567','8581570','8581503','8581509','8581511','8584073','8584080','8584081','8584434','8584447','8584453','8584455','8584113','8584115','8584346','8584124','8584125','8584128','8584131','8584152','8584160','8582318','8582322','8582326','8582330','8582115','8582117','8582343','8582351','8582133','8582134','8582135','8582137','8582139','8582142','8582238','8582149','8582268','8575392','8575474','8575476','8575317','8575318','8575480','8575319','8575481','8575482','8575320','8575321','8575485','8575406','8575487','8575325','8575327','8575953','8575960','8576069','8576074','8576082','8576083','8576084','8576087','8574630','8574632','8574634','8574584','8574585','8574730','8574593','8574595','8574600','8574209','8574137','8574298','8574212','8574215','8574218','8574220','8574222','8574225','8574228','8574186','8573761','8573681','8573770','8573683','8573689','8573692','8573695','8573697','8573698','8573785','8573786','8573702','8573703','8573704','8573710','8573714','8573718','8573721','8573722','8573724','8573725','8573726','8573727','8573610','8574188','8574189','8573915','8573922','8574039','8574127','8573946','8573947','8573951','8573954','8573746','8573749','8573886','8573760','8574494','8574426','8574430','8574344','8574347','8574348','8574354','8574359','8574362','8574363','8574367','8574192','8574271','8574272','8574194','8574199','8574200','8574128','8574129','8574206','8574133','8573650','8573733','8573734','8573737','8573738','8573740','8573743','8573626','8573627','8571253','8570997','8571263','8571267','8571270','8571012','8571278','8571016','8571282','8571287','8571294','8571031','8571036','8571045','8571047','8571049','8571063','8568308','8568405','8568310','8568408','8568412','8568313','8568413','8568322','8568421','8568326','8568335','8568337','8568339','8568340','8568069','8568077','8568083','8568086','8568345','8568349','8568353','8568361','8568365','8568366','8568371','8568373','8568375','8568382','8568388','8568390','8568391','8568287','8568301','8568302','8568467','8568763','8568469','8568649','8568479','8568653','8568025','8568225','8568140','8567902','8567908','8567916','8567918','8567921','8567922','8567923','8567927','8567928','8567930','8567936','8567940','8567942','8568774','8568781','8568828','8568833','8568834','8568947','8568806','8572688','8572803','8572696','8572697','8572722','8572729','8572730','8576246','8576196','8573669','8573380','8573381','8573384','8573385','8573386','8575278','8575279','8575356','8575211','8575285','8575288','8575291','8575292','8575295','8575228','8575304','8575306','8575309','8573325','8573420','8573340','8573341','8573440','8573346','8573249','8573351','8573250','8573361','8573366','8573368','8573369','8573372','8573373','8573374','8573375','8573376','8573377','8572771','8572679','8572683','8572685','8572238','8572242','8572250','8572358','8572378','8572184','8572552','8572423','8572434','8572185','8572303','8572305','8572306','8572308','8572074','8572094','8572233','8572137',
'8571966','8572144','8576677','8576526','8576528','8576540','8576545','8576548','8576549','8576409','8576555','8576562','8574946','8575001','8574947','8575002','8575005','8574792','8574857','8574861','8574795','8574731','8574733','8574804','8574806','8574807','8574739','8574812','8574740','8574816','8574746','8574817','8574753','8574754','8574820','8575978','8576090','8576093','8576098','8575844','8575845','8575846','8575847','8575849','8575912','8575850','8575913','8575855','8575856','8575777','8575778','8575862','8575780','8575782','8575783','8575788','8575791','8575829','8575831','8575729','8575730','8575733','8574603','8574504','8574615','8574512','8574618','8574513','8574619','8574371','8574374','8574533','8574377','8574538','8574386','8575110','8575115','8575119','8575123','8575128','8575201','8575130','8575203','8574956','8575044','8574927','8574932','8574938','8574939','8574998','8574999','8574768','8574770','8574773','8574774','8574775','8574779','8574785','8574787','8574789','8574821','8574756','8574822','8574757','8574823','8574824','8574759','8574825','8574761','8564221','8564050','8564447','8564449','8564060','8564061','8564064','8564067','8553056','8553059','8553061','8553064','8553066','8553197','8553071','8553199','8553074','8553075','8553077','8553084','8553086','8553469','8553214','8553215','8553216','8553342','8553221','8553344','8553345','8553223','8553347','8553351','8553353','8553356','8553357','8553244','8553122','8553249','8553367','8553254','8553261','8553385','8553271','8553148','8553160','8552977','8552874','8552886','8552887','8552902','8552905','8552913','8552921','8552922','8553523','8553392','8553524','8553526','8553527','8553534','8553539','8553540','8553541','8553544','8553546','8553553','8553556','8553558','8553560','8553562','8553565','8553309','8553311','8553312','8553315','8553319','8553463','8553331','8554138','8554141','8554271','8554025','8554032','8554173','8553936','8554045','8553944','8554188','8553871','8553882','8553752','8553753','8553756','8553761','8553763','8553764','8553767','8553774','8553776','8553917','8553779','8553780','8553781','8553783','8553784','8553788','8553790','8553797','8553803','8553807','8553808','8553810','8553815','8553952','8553961','8553965','8553967','8553969','8553974','8553982','8553983','8553825','8553831','8553989','8553990','8553991','8553836','8553993','8553838','8553996','8553997','8554005','8553853','8553854','8553860','8553861','8553865','8553866','8553869','8553819','8553823','8553566','8553573','8553576','8553577','8553578','8553582','8553583','8553584','8553585','8553587','8553590','8553595','8553596','8553601','8553603','8553604','8553605','8553607','8553608','8553610','8553612','8553483','8553613','8553484','8553487','8553489','8553617','8553491','8553618','8553494','8553495','8553622','8553625','8553502','8553504','8553631','8553508','8553634','8553512','8553636','8553638','8553515','8553517','8553390','8553521','8552924','8562588','8562590','8562617','8552764','8552619','8552768','8552622','8552624','8552510','8552430','8552549','8552439','8552440','8552441','8552268','8552061','8552068','8552069','8552072','8552074','8552075','8552077','8552078','8552090','8552094','8552107','8552409','8552425','8552427','8551951','8551841','8551954','8551842','8551843','8551844','8551846','8551847','8551849','8551852','8551854','8551858','8551860','8551861','8551613','8551864','8551615','8551865','8551866','8551618','8551619','8551869','8551766','8552476','8552404','8552296','8552297','8552358','8552463','8552472','8552589','8552595','8552745','8552600','8552754','8552755','8552607','8552610','8552760','8552612','8552715','8563269','8563035','8563039','8563277','8563041','8563281','8556796','8556807','8556809','8556810','8556705','8556706','8556840','8556709','8556854','8556729','8556730','8556733','8556422','8556443','8556446','8556466','8556364','8556470','8556365','8556367','8556369','8556254','8556372','8556741','8556745','8556750','8556639','8556677','8556410','8556417','8556420','8557615','8557520','8557433','8557211','8557126','8557127','8557131','8557132','8557133','8557134','8557137','8557142','8557361','8557256','8557104','8557108','8557109','8557110','8557113','8557017','8557119','8557021','8557120','8557023','8557024','8556918','8557030','8557034','8557035','8557037','8557038','8556944','8556380','8556384','8556270','8556272','8556398','8556281','8556401','8556283','8556285','8556164','8556293','8564295','8564298','8564299','8564307','8564310','8564154','8563988','8554800','8554801','8554809','8554817','8554936','8554938','8554820','8554828','8554951','8554840','8554842','8554847','8554853','8554856','8555201','8555113','8554969','8554878','8555346','8555347','8555348','8555350','8555351','8555352','8555359','8555360','8555361','8555479','8555480','8555364','8555482','8555485','8555490','8555492','8555494','8555496','8555510','8555402','8554415','8554416','8554215','8554346','8554358','8554360','8554473','8554658','8554474','8554478','8554479','8554481','8554484','8554489','8554492','8554494','8554683','8554500','8554501','8554369','8554511','8554373','8554376','8554514','8554519','8554389','8554390','8554391','8554392','8554393','8554397','8554398','8554399','8554401','8554402','8554405','8555315','8555322','8555323','8555324','8555327','8555330','8555332','8555333','8555335','8555337','8555338','8555340','8555167','8555345','8554858','8554705','8554710','8554868','8554717','8554530','8554723','8554725','8554726','8554728','8554739','8554743','8554754','8554448','8554587','8554450','8554451','8554761','8554764','8554766','8554461','8554767','8554465','8554468','8554776','8555308','8555310','8555314','8571323','8571480','8571324','8571331','8571333','8571489','8571495','8571497','8571345','8571502','8571352','8571503','8571506','8571509','8571513','8571369','8571516','8571372','8571394','8571398','8571408','8571409','8571412','8571413','8573074','8573088','8572982','8572984','8572813','8572817','8572990','8572818','8572995','8572997','8573000','8573006','8572832','8572834','8573117','8573131','8573012','8573013','8573015','8573017','8573019','8573024','8573152','8573028','8573037','8573170','8573044','8573187','8573060','8572936','8573064','8571702','8571414','8571419','8571422','8571426','8571716','8571719','8571440','8571443','8571448','8571596','8571454','8571456','8571462','8571306','8571469','8571470','8571611','8571476','8571477','8571615','8571319','8571931','8571784','8571796','8571798','8571803','8571627','8571631','8571632','8571633','8571634','8571635','8571640','8571643','8571815','8571647','8571652','8571657','8571658','8571666','8571673','8571674','8571676','8571529','8571677','8571534','8571680','8571684','8571685','8571686','8571544','8571690','8571545','8570841','8570721','8570847','8570850','8570987','8570988','8570733','8570735','8570739','8570740','8570744','8570752','8570755','8570606','8570620','8570622','8570627','8570527','8570530','8570531','8570535','8570537','8570638','8571069','8571071','8570906','8571076','8570910','8571080','8570912','8571087','8570917','8570787','8570789','8570793','8570936','8570939','8570800','8570801','8570803','8570688','8570692','8570807','8570949','8570693','8570810','8570701','8570702','8570822','8570824','8570706','8570830','8570973','8570716','8571738','8571751','8571754','8571758','8571915','8571762','8571765','8571989','8562721','8562455','8560792','8561182','8560810','8560684','8560696','8560700','8560703','8560708','8560844','8560847','8560728','8560730','8561252','8561253','8561257','8561261','8561126','8561158','8560784','8560785','8560735','8560739','8560769','8560485','8559999','8560280','8560036','8560064','8560408','8560287','8560288','8560446','8560448','8560295','8560310','8560320','8560330','8560331','8560332','8560335','8560355','8560357','8560123','8560231','8560372','8560239','8560139','8560241','8560249','8560253','8560271','8559860','8559862','8559758','8559947','8559845','8563763','8563595','8557811','8557819','8557820','8557620','8557622','8557623','8557627','8557831','8557839','8557639','8557735','8557850','8557641','8557642','8557648','8557650','8557750','8557657','8557663','8557664','8558393','8558399','8558400','8558170','8558402','8558403','8558404','8558405','8558408','8558412','8558418','8558755','8558758','8558513','8558767','8558768','8558772','8558776','8558782','8558542','8558421','8558422','8558546','8558424','8558425','8558432','8558433','8558436','8558440','8558445','8558450','8558452','8558467','8558356','8558477','8558363','8558366','8558372','8558375','8558376','8558379','8558380','8558382','8558497','8558391','8557907','8557909','8558027','8558029','8557912','8557786','8557788','8557789','8557793','8557952','8557799','8557802','8557805','8557466','8557596','8557598','8558044','8558046','8558055','8558077','8558079','8558080','8557962','8557968','8557976','8558098','8558102','8557999','8557883','8558006','8558008','8558009','8558042','8561424','8561334','8561335','8561460','8561462','8561348','8561349','8561476','8562242','8562249','8562133','8562254','8562140','8562257','8562259','8562270','8562271','8562275','8562185','8562063','8562285','8562291','8562446','8562293','8562447','8562299','8562201','8562203','8562237','8561624','8561511','8561522','8561665','8561523','8561408','8561943','8561953','8561954','8561956','8561799','8561958','8561819','8561823','8561609','8561615','8562940','8562082','8562084','8562086','8562089','8562092','8562096','8561995','8562100','8561999','8562007','8562008','8562015','8562016','8564018','8563880','8563882','8563900','8563901','8563906','8563912','8559269','8559272','8559280','8559401','8559290','8559414','8559293','8559417','8559422','8559428','8559432','8559305','8559636','8559773','8559774','8559782','8559651','8559654','8559662','8559665','8559670','8559677','8559678','8559684','8559702','8559705','8559570','8559714','8559720','8559724','8559725','8558894','8558797','8558799','8558803',
'8558602','8558812','8558815','8558926','8558927','8558620','8558623','8558626','8559225','8559249','8559027','8559729','8559443','8559447','8559449','8559451','8559454','8559461','8559464','8559471','8559480','8559338','8559342','8559161','8558959','8558962','8558963','8558967','8558968','8558969','8558971','8558985','8558873','8559267','8549664','8549665','8549670','8549673','8549674','8549768','8549675','8549770','8549679','8549598','8549683','8549684','8549685','8549688','8549689','8549691','8549693','8549611','8549613','8549614','8549533','8549617','8581979','8581980','8582073','8581983','8582077','8581993','8581997','8582000','8582001','8582002','8582009','8582101','8582010','8582015','8582018','8582020','8582021','8584071','8583611','8583616','8583508','8583510','8583629','8583632','8583634','8583638','8583518','8583640','8583657','8583658','8582512','8582517','8582518','8582520','8582528','8582542','8582546','8582629','8582553','8582555','8582557','8582562','8582563','8582690','8580718','8580723','8580656','8580658','8580729','8580663','8580664','8580564','8580567','8580570','8580572','8580575','8580576','8580373','8580449','8580377','8580452','8580453','8580457','8580458','8580386','8580388','8580461','8580462','8580391','8580464','8581123','8581186','8581125','8581126','8581127','8581129','8581130','8581131','8581132','8581195','8581135','8581136','8581137','8581199','8581138','8581139','8581143','8581204','8581069','8581155','8581405','8581092','8581098','8581100','8581106','8581109','8581110','8581113','8581181','8580919','8580921','8580922','8580986','8580989','8580927','8580991','8580932','8580994','8580995','8580996','8580465','8580398','8580399','8580468','8580400','8580401','8580470','8580402','8580471','8580472','8580473','8580474','8580476','8580477','8580410','8580478','8580479','8580908','8580909','8580910','8580848','8580849','8580371','8580298','8580226','8580230','8580234','8580076','8580078','8580146','8580021','8580153','8580088','8580157','8580027','8580028','8577903','8578042','8577904','8577975','8577978','8577983','8577927','8577931','8577933','8577935','8577855','8577637','8577565','8577566','8577567','8577570','8577572','8577575','8577576','8577578','8577579','8577580','8577586','8577588','8577593','8577536','8577538','8577540','8577542','8577545','8577546','8577547','8577549','8577552','8577557','8577558','8577559','8577690','8577692','8577696','8577698','8577702','8577939','8577800','8577870','8577871','8577875','8577876','8577878','8577822','8577831','8577561','8577414','8577564','8577422','8577423','8577358','8577365','8577439','8577369','8577458','8577460','8577461','8577321','8577402','8577405','8577259','8577407','8577341','8577343','8577347','8577348','8577374','8577375','8577376','8581362','8581363','8581248','8581379','8581380','8581381','8581382','8581313','8578008','8578009','8577940','8578091','8578092','8578094','8578015','8578016','8577949','8577950','8577953','8578030','8578031','8577962','8577963','8578342','8578343','8578344','8578197','8578349','8578204','8578207','8578210','8578216','8578219','8578223','8577995','8577997','8578004','8578006','8578228','8578231','8578232','8578146','8578234','8578156','8578160','8578163','8578164','8578045','8578118','8578047','8578568','8578499','8578500','8578502','8578504','8578607','8578435','8578612','8578443','8578315','8578321','8578322','8578323','8578465','8578324','8578327','8578331','8578332','8578333','8578334','8578336','8578338','8578341','8578705','8578707','8578708','8578712','8578713','8578714','8578715','8578696','8578697','8578698','8578701','8578473','8578474','8580673','8580675','8580614','8580813','8580815','8580816','8580618','8580817','8580820','8580698','8580704','8580840','8580648','8580651','8579062','8578937','8578938','8578813','8578814','8578726','8578728','8578819','8578820','8578670','8578686','8578690','8578691','8578692','8578694','8579386','8579389','8579395','8579328','8579397','8579398','8579204','8579213','8578847','8578849','8578850','8578851','8578854','8578858','8578861','8578862','8578864','8578866','8578870','8578871','8578803','8578719','8578720','8579218','8579219','8579222','8579223','8579154','8579227','8579157','8579232','8579236','8579095','8579174','8579179','8579116','8579118','8579197','8579035','8578988','8578995','8578976','8578823','8578897','8578826','8578980','8578828','8578829','8578836','8578837','8578842','8578843','8578846','8584466','8584467','8584469','8584474','8584489','8584493','8576201','8576205','8576212','8576218','8576224','8576100','8576225','8576101','8576103','8576105','8576231','8576233','8576236','8576114','8563073','8563081','8563239','8556205','8556076','8556085','8556086','8555971','8555972','8555974','8555975','8555978','8555981','8555985','8555990','8555785','8555624','8555625','8555520','8555528','8555540','8555441','8555554','8555559','8555560','8555568','8555569','8555571','8555572','8555575','8555700','8555859','8555863','8555864','8555865','8555964','8555965','8555967','8555876','8555969','8555877','8555970','8555592','8555748','8555596','8555599','8555604','8556050','8555810','8555816','8555817','8555825','8555828','8555831','8555835','8555837','8555955','8551334','8551336','8551449','8551341','8551342','8551345','8551248','8551346','8551252','8551253','8551256','8551257','8551259','8551261','8551359','8551265','8551361','8551267','8551268','8551272','8551274','8551275','8551387','8551277','8551279','8551280','8551393','8551286','8551287','8550762','8550765','8550766','8550768','8550770','8550773','8550774','8550777','8550779','8550676','8550602','8550604','8550680','8550606','8550609','8550686','8550687','8550612','8550613','8550615','8550690','8550616','8550620','8550622','8550547','8550548','8550699','8551591','8551596','8551606','8551427','8551317','8551540','8551437','8551326','8551328','8551439','8551441','8550722','8550753','8551642','8551781','8551655','8551660','8551546','8551571','8551575','8551576','8550995','8550997','8550999','8551008','8551016','8550941','8551294','8551295','8551296','8551297','8551204','8551300','8551303','8551306','8551307','8551209','8551308','8551309','8551310','8551312','8551215','8551313','8551216','8551217','8551315','8551221','8551129','8551130','8551131','8551228','8551229','8551137','8551233','8551234','8551235','8551236','8551237','8551144','8551238','8551243','8551245','8551104','8551108','8551111','8551113','8551117','8551118','8551621','8551872','8551622','8551873','8551769','8551877','8551878','8551879','8551888','8551778','8551641','8549109','8549143','8549219','8549144','8549145','8549220','8549151','8549158','8549161','8549162','8549166','8548959','8548961','8548969','8546561','8546562','8546564','8546423','8546565','8546566','8546567','8546569','8546572','8546573','8546575','8546368','8546372','8546383','8546303','8547091','8546882','8546885','8546890','8546896','8546272','8546275','8546204','8546205','8546206','8546207','8546208','8546214','8546216','8546225','8546226','8546527','8546680','8546683','8546538','8546540','8546541','8546543','8546546','8546623','8546551','8546555','8546556','8546559','8546560','8546918','8546745','8546746','8546750','8546752','8546753','8546755','8546314','8546232','8546237','8546324','8546255','8546268','8546738','8546580','8546658','8546659','8546661','8546662','8546663','8546664','8545970','8545974','8546077','8546080','8550063','8550070','8550076','8550249','8550176','8550177','8550107','8545207','8545208','8545210','8545368','8545371','8545148','8545237','8545238','8545241','8545156','8545243','8545244','8545247','8545249','8545253','8545984','8545989','8545990','8545993','8545994','8545995','8545996','8545999','8546000','8546001','8546004','8545872','8546019','8545881','8545169','8545258','8545260','8545264','8545103','8545107','8545127','8545473','8545475','8545476','8545478','8545479','8545480','8545482','8545483','8545493','8545265','8545266','8545268','8545271','8545887','8545751','8545637','8545638','8545639','8545645','8545653','8545576','8545659','8545454','8545455','8545458','8545459','8545461','8545462','8545589','8545595','8545597','8545468','8544911','8545041','8545074','8548003','8548146','8548147','8548149','8547899','8547916','8547921','8548330','8548336','8548347','8548349','8548353','8548282','8548171','8548294','8550476','8550701','8550560','8550562','8550564','8550565','8550567','8550568','8550570','8550571','8550573','8550574','8550575','8550578','8550580','8550582','8550583','8550586','8550587','8550588','8550589','8548589','8548507','8548598','8548454','8548456','8547870','8547875','8547876','8547984','8547880','8547883','8547820','8547821','8547823','8547824','8547825','8547744','8547827','8547828','8548310','8548316','8548320','8548329','8547748','8547831','8547833','8547834','8547837','8547758','8547760','8547841','8547842','8547843','8547697','8547698','8547773','8547779','8548572','8547830','8544484','8544485','8549541','8549542','8549547','8549468','8549577','8549497','8549499','8548296','8548297','8548300','8548301','8548302','8548137','8548139','8548140','8548952','8548744','8548747','8548752','8548757','8548759','8548760','8548687','8548772','8548696','8548698','8548700','8548706','8548708','8544749','8544751','8544670','8544752','8544754','8544755','8544757','8544758','8544759','8544680','8544682','8544766','8544767','8544689','8544774','8544776','8544784','8544699','8544702','8544703','8544475','8544478','8544401','8545077','8545078','8545082','8545083','8545084','8545085','8544268','8544270','8541284','8544273','8541311','8544491','8539549','8539566','8539197','8540090','8539950','8540363','8540209','8540627','8540638','8540453','8544286','8544298','8541339','8544310','8544313','8544315','8541218','8538667','8538691','8538696','8538713','8538297','8538540','8536914','8536945','8539474','8539489','8540183','8539980','8539628','8536627','8549944','8549947','8550026','8550028','8550033','8550034','8549892','8549288',
'8549289','8549365','8549292','8549221','8549224','8549226','8549228','8549230','8549231','8549232','8549233','8549236','8549237','8549238','8549240','8549243','8549244','8549246','8549251','8549175','8549177','8549264','8549269','8549271','8549272','8549194','8549279','8549087','8549090','8549093','8549207','8549210','8549211','8549212','8549213','8549214','8549736','8549742','8549746','8549748','8549750','8549753','8549920','8549924','8549925','8549926','8549849','8550601','8550445','8550518','8550519','8550523','8550454','8550255','8550532','8550278','8550467','8548905','8548909','8548921','8548922','8548923','8548924','8548823','8548929','8548932','8548715','8548812','8548933','8548937','8548938','8548939','8548940','8548941','8548946','8550468','8550474','8549400','8549405','8549505','8549406','8549410','8549411','8549513','8549520','8549418','8549280','8549282','8549457','8549461','8549287','8547443','8547444','8547448','8547449','8547450','8547372','8547452','8547453','8547454','8547455','8547489','8547313','8547315','8547316','8547318','8547328','8547329','8547330','8547305','8547234','8547310','8547236','8547311','8547312','8547149','8547150','8547092','8547096','8547097','8547099','8547104','8547106','8547108','8547115','8547192','8547118','8547720','8547723','8547729','8547731','8547493','8547593','8547594','8547595','8547196','8547123','8547199','8547202','8547060','8547145','8547084','8547085','8547087','8547088','8547337','8547340','8547206','8547207','8547208','8547352','8547354','8547355','8547356','8547283','8547284','8547215','8547358','8547285','8547216','8547288','8547218','8547361','8547613','8547519','8547555','8547426','8547427','8547428','8547430','8547434','8547435','8547436','8547437','8547439','8547290','8547220','8547221','8547293','8547223','8547294','8547295','8547225','8547297','8547298','8547228','8547300','8547229','8547302','8547230','8547231','8547193','8547119','8547194','8547195','8555401','8579637','36190273','36197018','36197024','34035314','34003339','34003579','33983686','34023591','33990013','33991419','34023931','34025882','8576080','8583088','34028937','36278253','33999486','33999816','31343641','31345356','31392823','27612969','27624495','27626230','27629590','27629651','27633820','27753012','27557804','27422275','27582446','27427535','27427996','27535156','27458959','27421085','27421133','27421205','8540892','8536967','8536539','8536854','8572345','8541225','8541233','8540982','8541282','8541063','8541079','30040204','30040297','8537725','8537727','8537756','8537974','8537525','8537543','8537601','8537605','8537619','8538380','8538441','8544793','8544794','8544795','8544798','8544801','8539038','8539085','8540764','8538641','8537478','8537495','8537274','8537384','8537629','8537632','8538564','8538324','8538576','8572294','20692080','18919882','18919895','8562584','8539611','8545105','8550871','8575872','8575879','8575890','8575891','8575894','8575899','8575900','8575903','8557717','8557718','8557328','8557336','8551927','8557339','8551930','8551932','8551933','8551474','8551475','8551479','8551480','8551481','8551936','8551482','8551942','8551943','8551945','8551485','8551946','8551486','8554107','8551492','8551495','8580172','8580173','8580176','8580177','8580179','8580181','8579803','8579804','8579810','8579811','8580191','8580192','8579820','8579821','8579822','8579823','8579824','8579828','8579831','8579836','8579838','8579839','8579841','8577718','8560206','8577724','8577728','8575869','8577921','8554174','8580366','8580352','8580357','8580351','8552851','8580362','8547488','8549904','36268207','18919658','8546957','8551508','8551510','8551895','8551898','8551902','8551904','8551906','8550910','8551908','8551911','8551460','8551915','8551918','8551465','8551919','8551468','34047955','8557666','8557670','8557672','8557678','8557679','8557680','8557683','8557293','8574704','8557295','8557298','8557687','8557688','8576022','8557689','8557302','8557304','8557693','8557306','8557696','8557310','8557698','8557312','8576031','8557703','8557317','8557705','8557706','8576036','8557708','8576039','8557713','8557715','8576395','8576399','8577849','8576330','8576367','8576377','8576383','8576384','8576385','8576388','8576392','8545033','8574844','8569170','8569179','8574839','8570395','8550030','8575055','8575050','8575052','8575051','18920351','8540439','8570386','8570385','8570384','8582109','8546390','8546391','8583977','8579844','8579847','8580169','8578970','8578972','8578975','8579067','8579070','8579073','8576052','8579078','8576056','8579079','8579080','8576058','8579083','8579088','8579089','8579093','8560542','8560546','8560551','8560555','8560566','8560575','8560580','8560584','8580442','8579096','8578947','8578948','8578952','8578956','8579111','8578963','8557345','8557347','8574683','8551506','8552907','8579378','3603435','36215708','36198235','36279985','36305145','36310745','36200615','36202656','36214656','36214668','36207504','36214823','36215907','36214933','36258709','36191365','36196367','14281841','14282455','13939288','13757139','13776378','13758261','13719845','13013373','36174012','11965162','34436180','34386339','36105379','36110275','36165016','36176176','36177468','22810204','30182108','13939273','13398654','36215843','32521911','3598744','3603450','3588794','3630087','3609466','3618839','3611723','3628310','3632070','6898423','6905734','22810070','12940326','5499411','5498559','5496904','5497547','5492785','5492853','36249727','36266352','36249687','36226539','36226541','36249705','36249707','36249708','36112046','36127987','30414748','33117844','33121363','32673916','36123686','36122648','35869103','35869243','36126196','36126203','25063308','21658229','21658321','27960973','25223652','27963748','27964018','27966012','36314964','36295931','36249751','36221089','36129123','36129106','36168465','36171209','36129088','21490869','11091714','10501624','11609849','11640575','11636966','11091315','11091329','10501638','9280550','9280456','9026893','9280625','9281066','36122651','11599248','11599263','11599296','11599214','36123672','36129085','36124868','11609670','16392653','16392658','16392668','16392726','16510407','16392796','16392838','16392867','16392883','16392906','16392941','16401396','16392226','16370997','16392247','16371000','16392259','16371020','16371022','16392270','16371062','16392301','16371072','16371082','16392364','16392380','16371092','16370753','16370803','16371126','16392431','16370808','16370815','16371139','16371146','16511847','16511861','16511879','16511882','16511886','16511894','16511902','16511930','16511943','16371153','16392487','16392497','16392520','16370866','16370891','16392586','16370909','16392595','16392604','16392620','16392220','16370939','16370943','16370979','16370988','16370992','16370458','16370175','16370184','16370194','16370491','16370222','16369880','16370227','16369889','16370244','16369921','16511982','16511627','16511632','16511651','16511662','16511436','16511678','16511457','16511705','16511459','16511714','16511727','16511497','16370270','16369939','16369944','16370284','16370572','16369976','16370593','16369993','16370319','16370607','16370330','16370343','16370346','16370024','16370658','16370369','16370373','16370676','16370393','16370678','16370092','16370400','16370100','16370105','16370709','16511013','16511389','16511397','16511029','16511404','16511044','16510762','16510782','16510785','16510812','16510493','16510565','16510572','16511155','16511238','16511501','16511239','16511521','16511769','16511283','16511295','16511791','16511297','16511543','16511310','16511565','16511346','16511352','16511602','16511607','16511383','16511613','36067390','36100752','16370134','16370142','16369598','16369604','16369636','16326250','16326318','16369702','16326328','16369718','16326342','16369750','16369762','16369465','16369780','16369487','16369801','16369495','16369814','16369508','16369831','16369833','16369524','16369845','16369541','16369550','16369556','16369862','16369560','16369572','16369576','16369580','15738173','15738094','15738001','15738005','15738105','15738008','15738106','15738109','15738009','15738111','15738010','15738196','15738114','15738121','16315632','16315702','16315728','15738018','16315734','16315741','15738021','15738023','15738027',
'16315860','16323010','15738034','16326081','15738036','15738048','15738160','15738059','15738161','15738163','15738062','15738164','15737460','15737470','15737473','15737478','15737274','15737497','15737276','15737503','15737374','15737293','15737296','15737383','15737384','15737519','15737533','15737307','15737534','15737310','15737553','15737316','15737406','15737408','15737325','15737337','15737436','15737344','15737346','15737347','15736937','15737445','15737174','15737175','15737176','15737456','15736811','15737191','15736816','15737197','15593403','15593408','15593641','15593644','15593429','15593437','15593256','15593260','15593262','15204428','15593278','15593496','15204436','15204448','15593291','15204457','15593297','15204461','15204465','15204470','15204472','12477463','12446489','12456149','12456220','12600220','12600274','12600383','12600679','12031383','12015421','12475393','15736310','15736311','15736315','15736143','15736320','15736147','15736149','15736325','15736156','15736157','15736160','15736344','15736166','15736346','15736169','15736348','15736181','15736363','15736185','15736189','15736191','15736194','15736201','15736208','15736215','15736221','15736224','12622173','12622248','12598713','12599325','12599432','12599504','12599715','12622896','12623113','12623242','12601038','12601771','12604794','12604926','12606800','12606855','12608421','12608464','12608527','15594484','15594487','15594222','15594254','15593836','15594272','15594282','15593845','15593849','15593855','15593861','15593864','15593678','15593890','15593687','15593690','15593897','15593710','15593906','15593920','15594315','15593935','15594325','15594332','15593943','15736403','15736409','15736678','15736532','15736688','15736691','15736696','15736701','15736420','15736427','15736564','15736574','15736576','15736267','15736268','15736273','15736592','15736465','15736287','15736289','15736601','15736476','15736294','15736481','16511173','16510647','16510942','16510654','16510950','16510660','16511221','16510958','16510971','16510736','16510743','16510296','16510304','16510335','12841660','12801587','12801616','14740699','14740700','14740314','14740443','14740446','14740336','14740347','14740349','14740355','14740147','14740157','14740159','14739943','14739907','14739911','14739913','14740013','14740024','14740039','14739925','14739927','14739932','14739934','14739936','14740184','14739981','14739982','14739903','14740305','14740694','14740306','14740308','14579134','14492151','14492153','14492161','14492165','14505506','14573045','14573051','14443075','14573061','14443076','14573067','14443078','14443080','14443085','14443086','14492144','14492145','14578408','14443088','14448413','14488115','14488131','14488134','14488141','13316595','14488150','13377690','14443063','14443068','11855662','11791813','14740899','14740904','14740716','14740915','14740725','14740932','14740933','14740936','14740941','14740746','14740751','14740753','14740948','14740758','14740954','14741064','14741070','14740777','14740780','14740781','11307730','11510834','11510874','11510951','11511203','11511446','11189562','11511506','11511543','11189610','11189631','11189728','11511802','11968282','11974574','15736454','11143522','11143548','11175271','11175320','11175427','11175448','11175457','11175465','11175500','16512388','11062445','11062533','11062560','11062626','11062712','11064241','11064268','11064601','11064803','11065038','11129918','11130276','11130327','11120367','11130369','11120424','11120495','11122668','11938651','11939970','11189773','11189908','11195619','11303629','11304308','11304396','11304863','11306516','36110436','36120442','35145453','35542394','35954586','36127201','36230934','36154873','36241387','16512244','16512309','16511986','16512351','16512004','16512015','16512018','16512383','16512389','16512037','16512411','16512062','16512075','16512095','16512146','16512171','16512184','16511836','36216658','36147892','16512445','16512490','33519037','15593516','15593821','15593537','15593538','15593548','15593554','15593347','15593566','15593369','15593579','15593581','15593374','15593380','15737230','15736725','15736853','15736726','15736854','15736730','15737245','15736731','15736742','15737261','15736871','15737262','15736876','15736755','15736891','15736895','15736912','15736914','15736774','15736618','15736788','15736623','15736625','15736628','15736632','15736638','15736639','15736485','15736643','15736645','15736653','15736655','15736657','15736511','15736517','15736395','15736518','15736665','15736520','15736670','15593947','15594010','15594018','15593795','15593503','15593507','15736702','15737208','15736709','15737210','15736710','15737215','15736712','15737216','15737219','15737221','14819173','15204149','14819204','15203951','14819231','14818966','14819247','15203979','14819257','14819266','14819277','14819284','14819291','14819293','15204246','15204027','15204255','15204417','15204054','15204056','15204058','15204075','15204080','14819321','15204108','14819344','14819156','15057336','14819159','14819162','14819166','14741200','14741208','14741227','14741079','14741234','14741237','14741090','14741239','14741241','14741104','14741260','14741266','14741112','14741267','14741119','14741122','14818403','14818408','14818413','14818414','14818416','14818419','14818425','14818426','14740961','14740964','14741126',
'14740972','14741140','14741143','14740976','14741161','14741163','14740979','14741165','14740980','14741170','14740989','14741025','14741028','14740869','14740870','14741193','14740875','14740879','14740880','14740881','14740888','14740893','15204497','15204511','15204178','15204361','15204206','15204370','15594541','15594546','15708212','15594385','15708213','15594400','15594408','15594413','15708071','15594434','15708079','15708086','15708093','15594450','15594049','15708100','15594053','15708101','15594464','15594059','15708107','15594474','14818930','14818944','14818949','14818952','14818809','11141006','11141080','11141225','11128717','14818820','14818825','14819087','14819093','14819119','14819121','14819128','14818862','14819140','14818869','14819142','14819143','14819149','14818878','14818883','14818886','14818928','14740369','14740371','14740640','14740382','14740643','14740396','14740654','14740210','14740401','14740405','14740663','14740221','14740410','14740669','14740416','14740234','14740678','14740235','14740681','14740240','14740689','14740691','14740693','15708121','15736234','15708134','15736111','15708138','15736247','15708142','15736251','15736124','15708143','15708144','15708146','15736258','15708148','15708150','15708159','15708167','15708178','15594351','15594516','15708188','15594518','15594527','15594370','15594535','15708205','11791891','11791933','11792011','11792137','11792241','11792618','11820594','11821188','36317232','36313719','36300588','36300286','36298738','5282934','5282963','6156587','6157242','6157699','6157847','6158169','6128310','6129032','6139701','6140132','6140469','6140831','6140905','6142020','24800131','24799494','24800208','36118845','29975297','29429718','29430947','29971720','29972972','29428264','29428394','29428585','14346968','14345369','13518324','33729949','33729653','33729745','33729780','28292687','34494069','34558022','34778420','31484848','22547314','22589826','14347019','14347021','14347025','14347000','14329283','14244912','13616753','26806964','26806999','27118403','26950534','17672381','17672603','17672426','17672428','17672205','17672217','17672501','17672505','17672507','17672324','17672074','17672085','17672344','17672106','17672109','17671883','17672138','17672017','17671772','17672033','17671538','17671573','17671974','17671704','17672200','17672705','17672730','17672746','21707927','21708161','21708193','14347040','14347051','14245383','36109022','34416099','33730148','33730159','17670998','17670725','17670730','17670462','17670492','17670538','17670543','17671469','17671478','17671229','17671247','17671256','17671268','17671101','17671282','17670575','17670300','17670097','17670347','17670113','17670122','17670370','17671617','17671621','17671645','17671371','17671662','17671675','17671690','17671317','17671144','17671340','17671343','17671180','17670833','17670943','17670944','17670692','17670707','17671825','13267642','13267675','13268436','13268919','13268949','13231876','13240834','13241216','27624945','27624964','27625019','17669972','17670202','17670006','17670216','17670021','17669616','17669629','17665574','17669662','17669706','17665119','17665145','17664758','17665183','17665289','17664935','17664958','17669804','17669820','17665485','17669569','17664982','17665437','17665098','17664842','5285276','22331739','17664921','17664674','31678717','14346979','14345401','14345413','14329299','12880346','12929316','6157498','12453096','12451557','12444076','12444078','12444080','12444081','12032483','12059955','10857132','10859089','10859189','8686124','12384670','5277720','6158178','6137387','6137493','5294261','5287634','5287682','5293369','23281253','36125487','36125507','36125514','36125529','36125532','23281031','23279818','23279835','23279845','23279888','23281148','23280066','23280137','13239238','13267979','13268023','6138991','6139273','6129223','6129232','6140059','6129449','23280418','17664709','17664865','17665296','17669497','12066293','12066300','12066301','12066253','12066268','12066273','12066277','6127905','6128749','5296590','36217404','36217439','17671869','17665597','17671465','17671210','17670614','17672164','17672005','17665482','17665516','17671100','17665559','17669980','17671615','17669592','17671554','17670570','17671057','17669741','17670639','17669842','17664802','17665438','17671457','17670994','17671113','17664950','17671443','17670876','17670105','17671254','17671291','17670160','17669601','17671721','17671025','17665263','17672097','17670830','17669917','17671142','17664790','17669959','17670236','17664883','12066295','17669575','17671426','17671041','17665366','17669815','17671440','17665500','27565710','27624927','22309176','27476080','22254348','12866022','5286194','5287331','25210114','23280853','17672412','6136488','6136774','6127203','6127275','6127344','6127358','6127427','6127668','6127714','6127764','6127833','23281238','23281211','23281200','17669637','36173974','36103762','36141426','36141428','36144749','36117016','36117072','35847728','35851199','35852161','35852341','35853001','35906771','36262596','36262680','36262685','36273320','36273339','36273362','36275611','36282236','19475507','17672044','17664776','17670979','17669949','17671050','17671216','17671413','17665474','17670569','17672103','17670426','17671257','17670785','17669734','17671409','17671012','17670387','17665589','17671214','17672710','17665152','36118855','5743232','5743863','5743032','7817599','7705725','3873053','4198944','3882791','36218796','36224449','36212369','36225720','36264087','36278314','36279394','36286707','36276996','36322529','36314606','36305356','36249780','36230875','36249087','36249839','36232278','36180871','36221461','36205996','36199797','36200419','36191388','36164142','14331323','36114315','34431010','34431204','36106358','36114307','13692517','13692525','13697724','13692522','13692514','13692520','13582923','13582930','13560528','13560510','13560557','13659371','13659374','13659377','13659401','13716130','13716757','13723214','13550217','13560620','13068204','13068207','13068210','13068216','13068229','13068418','15029323','15032248','15032268','15032278','15032282','15032288','15068633','15068638','15068641','15065650','15029292','15428982','15379689','15379749','15380629','14939493','14939503','14939510','14903480','14892938','14903484','14903495','14903489','17538431','14901884','14892855','14892869','14903437','14903450','14903463','14892899','25546938','14901859','14903544','14903536','14903530','14903528','13673103','13582820','13582796','13582856','13582837','13582844','13692565','13692541','13500402','13500398','13697754','13718155','13520590','13520579','13520621','13520601','13520610','13520616','13500337','12688376','12688394','12688411','12688419','12688425','12686735','12476468','12481076','12688443','12688445','12710890','12710893','12710912','12419254','12419479','11973267','11973350','13759371','13582867','13582877','13550318','13500358','13500343','13180317','13072432','13072437','13072453','13072456','13072461','13067516','13067961','13068533','13072418','13063654','13015327','13015373','13015299','13063601','13063606','12339445','12339635','12376768','12414511','12414513','12414519','12414531','11967491','36126397','36126399','36123549','36123580','36126362','36126368','36126371','36126374','36126378','36126382','36126383','36126389','36126390','36126392','36126394','36126395','36126396','36138685','36138689','36137724','36137726','36137731','36138583','14602790','14602310','14580901','14580892','14580896','14505103','14505116','14505111','14505114','14331326','14197386','14580870','14580863','14580873','14580864','14502635','14311908','14583887','14504325','14504328','14504322','14328920','14328923','14197486','14206114','14712302','14712296','14673011','14580861','14569676','14569675','14569683','14569688','14645382','14578497','14580890','14580881','14568810','14502653','14331325','14712337','14672995','14672975','14311914','14311913','14762667','14762657','14762655','14652938','14645399','14605935','14605949','14569695','14569703','14505118','14536913','14505122','14197420','14197436','14712356','14645450','14652958','14652956','14580907','14580905','14602802','14331348','14197459','14197482','14197469','14197476','14712287','14580902','14505135','14505139','14505137','14268343','11640815','11654368','11655433','11632881','11636440','11655765','14624327','14624336','14624337','14624339','14624350','13500415','14580875','36222470','36222471','36222473','36222484','36222487','12448026','14580899','36264136','14708638','7030038','14330283','14299937','14712314','14536902','14536924','14536883','14536880','36232313','36199816','14237995','14238004','14238023','14238024','14267881','14267883','14267885','14267889','14267891','14226211','14226219','14226223','14226224','14226226','14268841','14226237','14239450','14239453','14228810','14239462','13689608','13616629','13689649','13689657','13689661','13616624','13579810','13579819','13579827','13579843','13579848','13530617','14228822','14228828','14228835','13699308','13699310','13699313','13699316','14228852','13699324','13689576','13689586','13689588','13689591','14226205','13609127','13609133','13609134','13609141','13609147','13609154','13609168','13609171','13609196','13559241','13559248','13559249','13559253','13616561','13616568','13559268','15149795','15149814','15149840','14941190','14941193','14941197','14941218','14941225','14941231','13530771','14432221','14466619','14464787','14464788','14432229','14464798','14464801','14464816','14395036','14395045','14395050','14541478','14465972','14465973','14465980','14465982','14465984','14465986','14432211','14432212','14466610','13320093','13320096','13320107','13320129','13313366','13034352','14299930','13034490','14643660','14643679','14738971','14738973','14738979','14624323','14624355','14624367','14624369','14624383','12498737','13500395','14536914','14536920',
'14536901','12498717','12498735','14673013','12625694','15149674','15149696','15149730','15149740','15149743','15149749','15583072','15583105','15583159','15583250','15583282','15583285','15583288','15583058','14786827','14786842','14788352','14788356','29715225','14541510','14541511','14539772','14539785','14539789','14539795','14539796','14539798','14539805','14541473','14541475','14941255','14941175','14579443','14579461','14579464','14541482','14541483','14541491','14579633','14579637','14794660','14794638','14794647','12330473','14740272','14788380','14788415','14395033','14325734','14325746','14325747','14299914','14299918','14299920','14299941','14299947','14269074','14269270','13045056','13045065','13045075','13034354','13034498','13044995','13313229','13044996','13045006','13045032','13045037','13045041','12625669','12713580','12631239','12625649','12713643','12753029','13034514','13034523','12713593','14391773','14391779','14391782','14391783','14391791','14325663','14325666','14325668','14395003','14395008','14325680','14395014','12337674','12453586','12421794','12421807','12421819','12329540','12448001','12448002','12337618','12337630','12597827','12597836','12597851','12506359','12453596','12506365','12506367','12453815','12506371','12506373','12506378','12506379','12479835','12506399','12506402','12448045','12479869','12448051','12448056','12506588','12448059','12506591','12506595','12506598','12448073','12448076','12479888','12448077','12448079','12448082','12506697','12506698','12604688','12631215','12622753','12622755','12622758','12597782','12622783','12622787','12622791','12622796','12625629','12625636','12337634','12337638','12337643','12337645','12337646','12421752','12506866','12448099','12506330','12448104','12448114','12448117','12421757','12453571','12453572','12631188','12631190','12631193','12631201','12337633','14536873','14433013','13616581','14433015','13500361','12448005','13034482','14331336','13072450','14788364','13034473','14536893','14536875','14643652','14536918','36113577','12448032','14903481','14903505','14903452','14238001','12631236','36191367','14577546','13888883','13888902','13888703','13888761','13888764','14788389','14788423','13891747','13891587','13891604','13891613','13891623','13891351','14788410','14788408','13887247','13887253','13887291','13887996','13888000','13888005','13887822','13888035','14788402','13893308','13893155','13893335','13893354','13893209','13893237','13893092','13893117','13886874','13887088','13886907','36241261','36248924','36241347','36241351','13888357','13887189','13887192','13887208','13887048','13886839','13889589','13889611','13889617','13889068','13889369','14579439','26268730','26263942','26268936','26264038','26264202','26274946','26269530','26272350','26266242','26266306','26275827','26273152','26273284','26276037','26267029','26267130','26234343','26267603','26267682','26227120','26262015','26262056','26234072','26262266','26227184','26287101','26287185','26287362','26287414','26294008','26294064','26287635','26294446','26262583','26262733','26262761','26262995','26234258','26234277','26233882','26233979','24738617','24739276','26226980','26284947','26280117','26276314','26276495','26285030','26280481','26285398','26284140','26227064','26227072','15032274','26341383','13892502','13891781','13891813','13891835','13891842','13892021','13892026','13891896','13892044','13889886','13889917','13889663','13890030','13889746','13889820','13889256','13889584','13891118','13891452','13891129','13891477','13891171','13890735','13890795','13890577','13890802','13892611','13892640','13892369','13892387','13892166','13892414','13892437','13892225','13891921','13891629','13891642','13891482','13891655','13891675','13891684','13891518','13891539','13892676','13892683','13892521','13892696','13892699','13892707','13892721','13892581','13892764','13890200','13890672','13890233','13890047','13890352','13890069','13890460','13890467','13887518','13887537','13887548','26281484','26285787','26282211','26278533','26283031','26279103','26279146','26283386','14788376','13888055','13892477','26344135','26338188','26338301','26338375','26338596','26333344','26336574','26334206','26336668','26339773','26339928','26337277','26337298','26322537','26301454','26301516','26301575','26297356','26297414','26299356','26299473','26299620','26299686','26298067','26298181','26299836','26295062','26300190','26300491','26300513','26331746','26301816','26331959','26323573','26323756','26332289','26332380','26321731','26324093','26322084','26295761','26300538','26300710','26300736','26300821','26300989','26301131','26286619','26345039','26341256','26341323','26345322','26341545','26341600','26341808','26344259','26344597','26340693','15149718','36215022','30953444','31162599','31341148','36224328','36199780','28629784','30009538','14244368','14244370','14244377','14244926','14244941','14245146','14244360','36241372','36278335','9154549','9155005','8437704','4110904','6396956','5671050','5657713','5670927','4277170','36268226','36267567','36268119','36268189','36258773','36260412','36219659','4276995','4277002','4204036','4204093','4204124','4272179','4205121','4243214','4276465','4243392','4276679','4202505','4203300','4217385','4199096','4199291','4277241','4130947','4130954','6397462','6397622','6351686','6351890','6352492','6352584','6352867','6345914','6347052','6347579','6348907','6349103','6349347','6350266','31891550','31891608','31891127','31891141','31888754','31888770','31888784','31790679','31790725','31635180','31889540','31790813','31790990','31791000','31790301','31791064','30869276','29567267','29815712','19705636','20177228','20177246','22006004','22358323','22359193','22354064','19628239','18920088','18920102','18920111','18920145','18920181','18920202','18920232','18919546','19800128','18969374','19047015','18920492','14737352','14737306','14487986','18909241','18910384','18907962','18913178','18913187','18909617','18913838','36161551','36147566','18911159','18907511','18905150','18905163','18905175','18904612','18903829','18902969','18902551','18902596','18006147','18906984','18906042','18906044','18906063','18903943','18904023','18904175','16589463','15697690','31889025','31889088','34417243','31892307','31891476','31892061','32475318','32455842','32475214','34564248','33565651','27511107','27916550','27916981','28161037','28075798','28076321','28076378','28614193','28614236','14737371','14737302','14737326','14737332','13556467','13557476','13557927','14492075','34734093','31892998','31893018','31893096','31892451','28615622','28615630','28489279','28489391','28228908','9751606','9747860','9747872','9747880','9747740','9747742','9747715','9747729','9747719','8716457','8681886','11866524','9963637','9963643','9963629','9963634','9964263','9964273','10013771','9966212','9966214','9966215','9966218','9966224','9999664','22885710','24481260','24513300','24465718','22504372','22507343','22507952','22460364','22510019','18915463','18914576','18915476','18914808','18914258','18914404','18914853','18915388','18915395','18914969','18915413','18914990','18914998','18915437','18916141','18916147','18915603','18916403','18916410','18916010','18916077','18916277','18917381','18917401','18917059','18914494','18913609','18913622','18913629','18913663','18913679','18913688','18914165','18913720','18913774','18915788','27202520','27455159','27145589','26673240','26673453','20825530','20832436','20735589','21399138','21400231','21251601','21977223','20527832','21619092','21366395','18917661','18917700','18917244','18918624','18918676','18918104','18918684','18918157','18918171','18919841','18919940','18919946','18919399','18918230','18917991','18918009','18918773','18919045','18919065','18918550','18919150','18918948','18919192','18918732','18919403','18919485','18919495','18919519','18912791','18911723','9747438','9830229','9830498','9830507','9830417','9830435','9809267','9809270','9809271','9809189','9809203','9809208','9809213','9809218','9809221','9809224','9809226','9809227','9809229','9809231','9809239','9809242','9809244','9809245','9809247','9809248','9809253','9809256','9809259','9809263','9840981','9840995','9841263','9841264','9841285','9841294','9841299','9841436','9841451','9830452','9830475','9830393','9830486','9830491','9830402','9830496','9941595','9918778','9941604','9941610','9941615','9941617','9941619','9915679','9941638','9941659','9941666','9963605','9941567','9963612','9963615','9941580','9963617','9963625','9941588','9915685','9916372','9916373','9916376','9915688','9915691','9915693','9916379','9915707','9917186','9917215','9915715','9915720','9917997','9918004','9916308','9916311','9916314','9916317','9918023','9916319','9918025','9916321','9918026','9918720','9916357','9918729','9916363','9916365','9916366','9918758','9918767','9749675','9749679','9747694','9751587','9751588','9749710','9751594','9747852','9747854','9751601','9747712','18914524','21376446','21376206','18911176','18916153','23917081','11830280','13854523','9830434','4276986','11831407','11831423','11900866','11830270','11830314','11897495','11897496','11897503','11906325','11906761','36213286','36213382','18916506','18914532','4277305','9830493','18915192','18916213','9915746','21377229','18915198','18915927','4272286','9941621','9830470','22595899','11831404','28256449','18919477','21377573','36262792','24515290','18918305','28077184','18914386','8574543','8574470','8574472','8574403','8574404','8574409','8574414','8574332','8574422','8574339','8574425','8569641','8569647','8569830','8569701','8570162','8570174','8569954','8569975','8570206','8569977','8569984','8570209','8569986','8570656','8569616','8569516','8569396','8569280','8569284','8569290','8569294','8569298','8569304','8570220','8570031','8569856','8569724','8569728','8569734','8569619','8569548','8569444','8569557','8569447','8569470','8569475','8569482','8570323','8570230','8569319','8569321',
'8569323','8569325','8570237','8570243','8570244','8570245','8570246','8570249','8570258','8570260','8570264','8570272','8570274','8570276','8570282','8570284','8570285','8570286','8570291','8570297','8583990','8583998','8583892','8584026','8584028','8583905','8583911','8584036','8584048','8583920','8584055','8583928','8583937','8584066','8583310','8583315','8583500','8583318','8583320','8582967','8582975','8583064','8583151','8583071','8583072','8583188','8583667','8583669','8583534','8583536','8583540','8583541','8583546','8583693','8583557','8583566','8583569','8583573','8583577','8583591','8583323','8583600','8583603','8583331','8583245','8583433','8583247','8583252','8583253','8584347','8584402','8576709','8576714','8576796','8576801','8577034','8577037','8577041','8577042','8577043','8577045','8577047','8577049','8577052','8577069','8577147','8577078','8577151','8577083','8577011','8577015','8577093','8577094','8577022','8577025','8577027','8577030','8577177','8577178','8577181','8577110','8577111','8577112','8577113','8577198','8577054','8577055','8577349','8577351','8577352','8577286','8581829','8577300','8577161','8577162','8577166','8567411','8567695','8567490','8575515','8575590','8575517','8575594','8575518','8575437','8575438','8575439','8575440','8575442','8575443','8575535','8575536','8575542','8575543','8575544','8575545','8575549','8575550','8567361','8567384','8566360','8566379','8566183','8566393','8566398','8566019','8566999','8567009','8567014','8567021','8567613','8567651','8567656','8567395','8567673','8566235','8566238','8566065','8566241','8565882','8566119','8566621','8566624','8566626','8566637','8576571','8576573','8576575','8576506','8576507','8576513','8576457','8576524','8576463','8576366','8575555','8575556','8575557','8575558','8575559','8575560','8575561','8575562','8575563','8575565','8575567','8575489','8575570','8575571','8575572','8575573','8575577','8575578','8575500','8575582','8575583','8575508','8575586','8575510','8575511','8575588','8575754','8575690','8575691','8575765','8575701','8575702','8575703','8575708','8575716','8576328','8576262','8576334','8576335','8576346','8575741','8575744','8575748','8575750','8575752','8575718','8575721','8575722','8575723','8576568','8576425','8576499','8564454','8564331','8564513','8564341','8564824','8565358','8565368','8565189','8565192','8565194','8565437','8565245','8565467','8565468','8565270','8565106','8565109','8565114','8565119','8565509','8565124','8565128','8565132','8565318','8565134','8565322','8565140','8565333','8565144','8565341','8565147','8565349','8565351','8565761','8565968','8565639','8565641','8565643','8565649','8565811','8565653','8565655','8565658','8565661','8565669','8565671','8565524','8565674','8565545','8565860','8565547','8565698','8565868','8565728','8565733','8565583','8565587','8565597','8564364','8564373','8564378','8564380','8564390','8581425','8581515','8581525','8581529','8581612','8581530','8581613','8581617','8581556','8581558','8581346','8581007','8581008','8581011','8581014','8581016','8581017','8580895','8580732','8580898','8580899','8580900','8580901','8580903','8580905','8580798','8580262','8580264','8580267','8580199','8580200','8580279','8580202','8580203','8580205','8579777','8579781','8579782','8579786','8579849','8579790','8579791','8579794','8579797','8579714','8579641','8580040','8580042','8580043','8580046','8580048','8580049','8580119','8580054','8580064','8580069','8580075','8579964','8579908','8579970','8579909','8579912','8579975','8579976','8579980','8579981','8579986','8579988','8579990','8579855','8579991','8579856','8579993','8579994','8579995','8579997','8579998','8580002','8580003','8580005','8580009','8580011','8580012','8580013','8579647','8579649','8579650','8579582','8579651','8579585','8579656','8579591','8579601','8579611','8579617','8579425','8579427','8579428','8579510','8579438','8579439','8579523','8579443','8579369','8579370','8579445','8579372','8579880','8579885','8579891','8579802','8579904','8579762','8579542','8579547','8579549','8579492','8579414','8579424','8582831','8582745','8582841','8582747','8582847','8582758','8582942','8582439','8582440','8582695','8582578','8582703','8582458','8582704','8582589','8582464','8582591','8582466','8582709','8582469','8582473','8582474','8582290','8582189','8582291','8582190','8582293','8582294','8582296','8582192','8582300','8582497','8582304','8582198','8582498','8582201','8582273','8582276','8582277','8582279','8582282','8582286','8582027','8581943','8582182','8582045','8582055','8581970','8582064','8581754','8581681','8581761','8581845','8581846','8581691','8583191','8583015','8583198','8583093','8583094','8583029','8583113','8582814','8582819','8582725','8582727','8582827','8582828','8583354','8583268','8583443','8583281','8583283','8583286','8583291','8583294','8583296','8583298','8583484','8583302','8583303','8583305','8583307','8583404','8581766','8581708','8581560','8581778','8581561','8581782','8581784','8581791','8581792','8581497','8581500','8581501','8581506','8584086','8584093','8584103','8584106','8584109','8584342','8584119','8583865','8582320','8582105','8582323','8582324','8582114','8582116','8582334','8582336','8582119','8582120','8582123','8582344','8582126','8582127','8582132','8582228','8582138','8582140','8582371','8582148','8582373','8582152','8582155','8582271','8582157','8582272','8581261','8575389','8575479','8575486','8575407','8575411','8575415','8575424','8575338','8575339','8575340','8575431','8575432','8575433','8575343','8576011','8576012','8576019','8576070','8576072','8576077','8576085','8576086','8576088','8574629','8574631','8574713','8574582','8574586','8574588','8574589','8574591','8574594','8574597','8574139','8574140','8574161','8574165','8574166','8574184','8573763','8573766','8573682','8573904','8573906','8573907','8573706','8573713','8573723','8573607','8573609','8573910','8573911','8573919','8573921','8573926','8573927','8573929','8573932','8573933','8574045','8573944','8573945','8573950','8574061','8573860','8573872','8573884','8573751','8573892','8573758','8573759','8574431','8574355','8574358','8574269','8574274','8574134','8573631','8573573','8573481','8573735','8573739','8573741','8573625','8571250','8571251','8570991','8571254','8571256','8570995','8571262','8571265','8571009','8571271','8571274','8571276','8571014','8571284','8571291','8571297','8571056','8568079','8568370','8568297','8568307','8568741','8568750','8568754','8568648','8568661','8567994','8568010','8568020','8568029','8568035','8568041','8567910','8567924','8568768','8568772','8568775','8568831','8568665','8568546','8568556','8568557','8568792','8568931','8568793','8568794','8568798','8568941','8568946','8568802','8568805','8568814','8568818','8568784','8568791','8572686','8572724','8572728','8572731','8572732','8572508','8576239','8576241','8576118','8573667','8573670','8573671','8573672','8573409','8575348','8575350','8575352','8575355','8575357','8575212','8575213','8575358','8575284','8575360','8575215','8575362','8575364','8575289','8575290','8575221','8575222','8575223','8575294','8575296','8575297','8575298','8575301','8575303','8575305','8575308','8575310','8575311','8575312','8573413','8573319','8573320','8573324','8573327','8573328','8573330','8573332','8573427','8573333','8573428','8573334','8573434','8573339','8573441','8573342','8573347','8573348','8573352','8573354','8573363','8573365','8573258','8572889','8572769','8572662','8572666','8572668','8572908','8572912','8572681','8572798','8572235','8572353','8572354','8572253','8572254','8572375','8572272','8572157','8572279','8572282','8572386','8572169','8572173','8572175','8572288','8572180','8572181','8572182','8572394','8572537','8572433','8572442','8572445','8572446','8572451','8572325','8572188','8572058','8572192','8572301','8572304','8572196','8572203','8572309','8572211','8572218','8572219','8572223','8572099','8572228','8572232','8572117','8572130','8572131','8571958','8572138','8576529','8576544','8576484','8576546','8576547','8576411','8576490','8576414','8576558','8576415','8576560','8576417','8576564','8575000','8574948','8574847','8575004','8574950','8574951','8574793','8574796','8574798','8574799','8574865','8574801','8574732','8574802','8574803','8574805','8574735','8574736','8574808','8574737','8574809','8574810','8574811','8574877','8574813','8574743','8574814','8574744','8574815','8574745','8574749','8574818','8574819','8574883','8575840','8575841','8575842','8575843','8575907','8575909','8575851','8575916','8575859','8575860','8575861','8575823','8575824','8575825','8575826','8575828','8575727','8575731','8575732','8575836','8575838','8574496','8574608','8574612','8574506','8574617','8574376','8574539','8575166','8575173','8575116','8575120','8575126','8575127','8575129','8575078','8575133','8575134','8575135','8575085','8575089','8575090','8575097','8575007','8575008','8575011','8574959','8575023','8574969','8575034','8575036','8575042','8574978','8575043','8575046','8575048','8574925','8574926','8574930','8574931','8574935','8574937','8574764','8574765','8574766','8574767','8574835','8574769','8574772','8574784','8574786','8574788','8574790','8574623','8574627','8574755','8574758','8574760','8564398','8564051','8553294','8553041','8552960','8552964','8552969','8552972','8553333','8553142','8553147','8552863','8552866','8552998','8552879','8552882','8553004','8552888','8552890','8553014','8552898','8552900','8552914','8552923','8553396','8553545','8553321','8554026','8554028','8554031','8554036','8554043','8553940','8554187','8553883','8553884','8553918','8553921','8553962','8553985','8553488','8553632','8552925','8562594','8562613','8562625','8562626','8562630','8552620','8552526','8552533','8552537','8552429','8552431','8552433','8552438','8552445','8552449','8552451','8552051','8552053','8552062','8552065','8552073','8552076','8552081','8551960','8551961','8552085','8552410','8552413','8552303','8552419',
'8552304','8552421','8552426','8552428','8552317','8552320','8552322','8552200','8551953','8551955','8551862','8552486','8552397','8552284','8552400','8552403','8552289','8552293','8552408','8552564','8552575','8552360','8552581','8552604','8552724','8552587','8563244','8563088','8563095','8563098','8563105','8563108','8563027','8556792','8556798','8556697','8556828','8556830','8556844','8556714','8556848','8556716','8556718','8556723','8556728','8556620','8556736','8556424','8556430','8556325','8556434','8556591','8556447','8556450','8556345','8556460','8556461','8556465','8556751','8556764','8556645','8556655','8556660','8556681','8556412','8556413','8557491','8557383','8557616','8557619','8557403','8557533','8557439','8557455','8557130','8557350','8557353','8556769','8556777','8556260','8556386','8556388','8556155','8556292','8556166','8556295','8556170','8556176','8564081','8564309','8564312','8564316','8563961','8554786','8554787','8554789','8554791','8554794','8554799','8554802','8554804','8554805','8554811','8554813','8554815','8554818','8554819','8554821','8554940','8554822','8554825','8554829','8554831','8554834','8554835','8554956','8554836','8554841','8554843','8554965','8554845','8554967','8554846','8554851','8555098','8555108','8555109','8554968','8554872','8554875','8554904','8554785','8555576','8555582','8555366','8555367','8555489','8555382','8555390','8555395','8555396','8555399','8555291','8554426','8554313','8554200','8554219','8554239','8554253','8554779','8554783','8554664','8554666','8554670','8554672','8554674','8554686','8554688','8554518','8554523','8554524','8554412','8555173','8555177','8555178','8555183','8554866','8554533','8554536','8554548','8554551','8554557','8554755','8554455','8554769','8554770','8554616','8554771','8554773','8554777','8555307','8555420','8571336','8571339','8571348','8571379','8571382','8571384','8571388','8571396','8571397','8571407','8571096','8573069','8573075','8573078','8573082','8573087','8573097','8573100','8573105','8572809','8572811','8572828','8572839','8572843','8573124','8573126','8573135','8573165','8573167','8573173','8572923','8573051','8573182','8573055','8573184','8573058','8573190','8573066','8571713','8571420','8571715','8571427','8571431','8571729','8571434','8571436','8571437','8571594','8571597','8571299','8571304','8571308','8571311','8571313','8572044','8571772','8571773','8571774','8571778','8571779','8571786','8571789','8571792','8571800','8571802','8571805','8571819','8571822','8571830','8570839','8570844','8570868','8570875','8570898','8570908','8571077','8571084','8571090','8570788','8570796','8570805','8570814','8570816','8570818','8570820','8570825','8570827','8570828','8570712','8570833','8570836','8571879','8571732','8571737','8571739','8571741','8571742','8571746','8571750','8571753','8571903','8572029','8571768','8572153','8571982','8571872','8571875','8562788','8562792','8562796','8562562','8562567','8560689','8560839','8561376','8561256','8561123','8561260','8561136','8561138','8561155','8561160','8560780','8560618','8560776','8560464','8560632','8560489','8560652','8560403','8559990','8559994','8560277','8560002','8560034','8559912','8559915','8559920','8559930','8559932','8559934','8560104','8560425','8560284','8560447','8560303','8560343','8560347','8560210','8560111','8560351','8560353','8560356','8560118','8560119','8560121','8560360','8560127','8560365','8560128','8560232','8560141','8560378','8560247','8560251','8560254','8560256','8560262','8560266','8559852','8559853','8559854','8559968','8559971','8559978','8559979','8559980','8559764','8559766','8559769','8559941','8563760','8563578','8563767','8563580','8563589','8557812','8557826','8557828','8557625','8557628','8557726','8557629','8557728','8557836','8557630','8557730','8557632','8557731','8557633','8557635','8557734','8557740','8557853','8557745','8557746','8557655','8557659','8557660','8557665','8558398','8558407','8558409','8558304','8558306','8558314','8558639','8558851','8558663','8558500','8558759','8558761','8558519','8558771','8558773','8558774','8558783','8558420','8558547','8558428','8558431','8558439','8558443','8558444','8558451','8558456','8558461','8558469','8558470','8558361','8558362','8558364','8558486','8558490','8558493','8558494','8558383','8558499','8558386','8558388','8558389','8557902','8558034','8558037','8557921','8557922','8557949','8557795','8557953','8557543','8557545','8557467','8557478','8557481','8557601','8557604','8557956','8557961','8558083','8557963','8557964','8557965','8557966','8557969','8558090','8557972','8557973','8557984','8557985','8557995','8558000','8558001','8558343','8561433','8561331','8561336','8561464','8561472','8562238','8562250','8562255','8562269','8562272','8562276','8562159','8562162','8562166','8562177','8562181','8562060','8562187','8562281','8562296','8562193','8562198','8562207','8562208','8562215','8562121','8562234','8561870','8561875','8561652','8561654','8561514','8561668','8561534','8561544','8561405','8561411','8561412','8561927','8561946','8561789','8561948','8561957','8561603','8561607','8562638','8562640','8562775','8562067','8562069','8561985','8562099','8562102','8562110','8561897','8561900','8561908','8561909','8563875','8559270','8559273','8559274','8559275','8559276','8559278','8559282','8559284','8559286','8559288','8559291','8559296','8559418','8559297','8559298','8559424','8559427','8559303','8559430','8559180','8559438','8559441','8559770','8559771','8559637','8559775','8559783','8559786','8559657','8559791','8559658','8559797','8559800','8559801','8559663','8559803','8559666','8559667','8559668','8559674','8559688','8559694','8559700','8559701','8559709','8559712','8559574','8559575','8558880','8559004','8559007','8558784','8558785','8558899','8558905','8558794','8558798','8558800','8558802','8558804','8558608','8558813','8558614','8558616','8558934','8558936','8558629','8558636','8558835','8559226','8559093','8559233','8559258','8559015','8559265','8559121','8559020','8559022','8559035','8559726','8559600','8559444','8559446','8559458','8559465','8559629','8559467','8559469','8559470','8559474','8559478','8559482','8559484','8559485','8559157','8558938','8559163','8559167','8559169','8558976','8558867','8559266','8558972','8558973','8549595','8549677','8549596','8549597','8549610','8549696','8549697','8549532','8549618','8582103','8583743','8583604','8583609','8583621','8583622','8583624','8583627','8583643','8583645','8583648','8583651','8583652','8583655','8583659','8582952','8582505','8582515','8582519','8582524','8582813','8582534','8582535','8582539','8582550','8582552','8582682','8582556','8582560','8582565','8582691','8580719','8580653','8580654','8580655','8580724','8580726','8580727','8580728','8580568','8580374','8580376','8580382','8580384','8580385','8580389','8580390','8581124','8581128','8581042','8581133','8581061','8581062','8581072','8581073','8581401','8581093','8581096','8581099','8581021','8581101','8581105','8581025','8581178','8581180','8581116','8581183','8581118','8581184','8581122','8580982','8580983','8580920','8580985','8580923','8580987','8580924','8580988','8580929','8580930','8580999','8581000','8581001','8581003','8580392','8580394','8580466','8580395','8580403','8580405','8580599','8580406','8580407','8580409','8581089','8581091','8580906','8580907','8580844','8580845','8580976','8580911','8580912','8580978','8580913','8580980','8580917','8580981','8580918','8580240','8580244','8580225','8580301','8580302','8580228','8580231','8580131','8580233','8580077','8580143','8580016','8580086','8580020','8580022','8580023','8580024','8580091','8580025','8580026','8577973','8577976','8577979','8577980','8577982','8577985','8577916','8577917','8577988','8577922','8577925','8577785','8577851','8577932','8577934','8577938','8577638','8577646','8577649','8577651','8577655','8577656','8577658','8577664','8577597','8577463','8577608','8577610','8577681','8577682','8577683','8577617','8577688','8577689','8577620','8577691','8577693','8577694','8577697','8577779','8577867','8577868','8577807','8577869','8577812','8577819','8577829','8577413','8577415','8577416','8577421','8577424','8577425','8577357','8577428','8577429','8577359','8577431','8577434','8577362','8577363','8577364','8577438','8577366','8577440','8577367','8577368','8577444','8577370','8577371','8577372','8577459','8577323','8577325','8577398','8577338','8577263','8577342','8577346','8577373','8577449','8581357','8581358','8581369','8581242','8581244','8581377','8578007','8578087','8578088','8578089','8578090','8578012','8578093','8577945','8578017','8577960','8577961','8578032','8578033','8577964','8578034','8577965','8578036','8578037','8578038','8578195','8578196','8578205','8578206','8578208','8578209','8578354','8578212','8578214','8578217','8578218','8578221','8578222','8578224','8578183','8578184','8578191','8578192','8577999','8578000','8578002','8578312','8578229','8578230','8578147','8578255','8578257','8578097','8578098','8578158','8578162','8578108','8578046','8578174','8578423','8578498','8578427','8578437','8578439','8578441','8578513','8578444','8578451','8578314','8578457','8578464','8578466','8578265','8578633','8578629','8578630','8580808','8580821','8580823','8580824','8580825','8580826','8580827','8580828','8580699','8580829','8580702','8580834','8580835','8580836','8580709','8580841','8580711','8580843','8580714','8580645','8579065','8578944','8579024','8579028','8578731','8578733','8578736','8578681','8578685','8578687','8578688','8578689','8579198','8579199','8579200','8579201','8579205','8579207','8579208','8579211','8579212','8579217','8578922','8578926','8578929','8578793','8578801','8578807','8578808','8579224','8581839','8579160','8579231','8579162','8579233','8579234','8579165','8579235','8579169','8579170','8579097','8579180','8579182','8579187','8579040','8579048','8579051','8578989','8578992','8578886','8578978','8578982','8578911','8578913','8576348','8576352',
'8576210','8576211','8576215','8576216','8576217','8576220','8576221','8576222','8576226','8576102','8576227','8576228','8576229','8576230','8576108','8576232','8576234','8576235','8576237','8563508','8563351','8563354','8563356','8563193','8563202','8563207','8563209','8555977','8555547','8555549','8555433','8555550','8555552','8555553','8555447','8555558','8555561','8555565','8555566','8555573','8555574','8555861','8555712','8555600','8555763','8555612','8555800','8556051','8556053','8555821','8551347','8551348','8551255','8551354','8551355','8551356','8551357','8551364','8551370','8551373','8551378','8551383','8551390','8551391','8551395','8550681','8550684','8550693','8550694','8550545','8550623','8550700','8551584','8551601','8551511','8551608','8551611','8551519','8551536','8551331','8550798','8550804','8550805','8550815','8551646','8551542','8551543','8551544','8551545','8551549','8551552','8551553','8551554','8551555','8551556','8551558','8551562','8551564','8551565','8551568','8551569','8551572','8551573','8551577','8551579','8551000','8551001','8551002','8551004','8551005','8551006','8551007','8551009','8551010','8551011','8551013','8551014','8551212','8551133','8551134','8551136','8551138','8551145','8551147','8551148','8551174','8551110','8551114','8551115','8551116','8551120','8551121','8551122','8551123','8551124','8551125','8551126','8551768','8551776','8549146','8549148','8549149','8549150','8549152','8549153','8549155','8549160','8549167','8548897','8546426','8546371','8546376','8546380','8546296','8546297','8546298','8546874','8546950','8546886','8546892','8546897','8546899','8546904','8546352','8546273','8546197','8546287','8546295','8546215','8546528','8546529','8546532','8546539','8546545','8546408','8546412','8546413','8546917','8546921','8546923','8546925','8546305','8546310','8546322','8546329','8546344','8546345','8546267','8546657','8546581','8546582','8546589','8546590','8546079','8546081','8546083','8550066','8550078','8550082','8550093','8550104','8550178','8550105','8545204','8545206','8545213','8545215','8545365','8545216','8545369','8545153','8545154','8545159','8545245','8545160','8545164','8545165','8545991','8546065','8545870','8545871','8545876','8545170','8545101','8545106','8545112','8545119','8545269','8545272','8545273','8545828','8545765','8545771','8545508','8545632','8545642','8545650','8545457','8545586','8545623','8545132','8545135','8545034','8545146','8545667','8548004','8547900','8547901','8548080','8547919','8548331','8548335','8548339','8548344','8548356','8548362','8548369','8548283','8548286','8548289','8548291','8548293','8550543','8550477','8550180','8550182','8550584','8547798','8547981','8547816','8547817','8547741','8548305','8548306','8548309','8548312','8548314','8548317','8548323','8548327','8547749','8547750','8547751','8547753','8547756','8547757','8547759','8547763','8547767','8547769','8547772','8547777','8547778','8547780','8547781','8547782','8548669','8544545','8544551','8544552','8544556','8544483','8544486','8549543','8549544','8549467','8549553','8549477','8549493','8549582','8549583','8548295','8548298','8548303','8547990','8548704','8544747','8544788','8544539','8544366','8541484','8544963','8544855','8544964','8544856','8544966','8544858','8544862','8544867','8541297','8539334','8539123','8539428','8538945','8539159','8540500','8540503','8540521','8539886','8540133','8539908','8540136','8540395','8540602','8540621','8540463','8540260','8544306','8544307','8544308','8544309','8541144','8541171','8541174','8541406','8541440','8538450','8538682','8538494','8538721','8538514','8538777','8538259','8538030','8538089','8538104','8538125','8537699','8537911','8537281','8537070','8537300','8537156','8537166','8536903','8536938','8539659','8539285','8539976','8540051','8539622','8539625','8536619','8549894','8549900','8549464','8549366','8549299','8549382','8549316','8549395','8549248','8549252','8549170','8549253','8549171','8549254','8549172','8549173','8549255','8549174','8549256','8549257','8549258','8549178','8549260','8549179','8549180','8549181','8549183','8549184','8549270','8549083','8549085','8549200','8549091','8549092','8549094','8549095','8549206','8549104','8549105','8549021','8549812','8549815','8549816','8549821','8549825','8549832','8549834','8549990','8549908','8549993','8549996','8550001','8549921','8549922','8549840','8549930','8549932','8549935','8549851','8549941','8549855','8549861','8550424','8550512','8550514','8550516','8550517','8550520','8550522','8550524','8550269','8550529','8550530','8550531','8550535','8550536','8550537','8548907','8548912','8549005','8549006','8548837','8550539','8550540','8550541','8550471','8550472','8549404','8549504','8549508','8549512','8549412','8549514','8549517','8549527','8547364','8547365','8547367','8547457','8547486','8547487','8547490','8547491','8547239','8547158','8547178','8547182','8547037','8547730','8547732','8547578','8547579','8547580','8547583','8547496','8547497','8547498','8547500','8547204','8547072','8547075','8547079','8547089','8547414','8547209','8547360','8547502','8547503','8547505','8547609','8547507','8547508','8547511','8547616','8547512','8547514','8547619','8547518','8547521','8547522','8547554','8547557','8547564','8547570','8547572','8547362','8547363','8547227','36190278','36205305','36206414','34033514','34002512','34631928','34004718','34632820','34632962','34633047','34021417','34021831','33994966','34633356','34027294','34029159','36290040','36301464','36190271','31334034','31334091','33999661','28767316','29079640','27624843','28687950','27633762','27635330','27556750','29130550','29190400','27557661','27582482','27516892','27586866','27611637','27446243','27612050','27554710','27458903','27461215','27374450','27389343','27419540','27419592','27419829','27419993','27420203','27421258','8536495','8536523','8536527','8572343','8541013','8541060','29195851','29698795','8537948','8537529','8537793','8537575','8538382','8538180','8538190','8537992','8544984','8539240','8538810','8540762','8540766','8540545','8540811','8538867','8538648','8537411','8537193','8537250','8537261','8537268','8537276','8539306','29066323','8571972','18919872','18919866','18919662','8549918','8570594','8577716','8575876','8575877','8575878','8575880','8575881','8575884','8575886','8575887','8575893','8575895','8575901','8577708','8577709','8557335','8574665','8551473','8551478','8546715','8546721','8551489','8551490','8546728','8545389','8538361','8539662','8580174','8580175','8579243','8580180','8579246','8580183','8580185','8580195','8581728','8581730','8581738','8583968','8577725','8575865','8577727','8575867','8575868','8580358','8580359','8580361','8538890','8580337','8580327','8580322','8580356','8578896','8552856','8552844','8552845','8552846','18918372','18917629','22509838','36268223','8551462','8551464','8551467','8545671','8545676','8545681','8557675','8574703','8576021','8576025','8576026','8576028','8576030','8576032','8576033','8576035','8576038','8576396','8576397','8576400','8576401','8576402','8580413','8576368','8576369','8576371','8576372','8576373','8576374','8576376','8580430','8576378','8580432','8576379','8580433','8576380','8580435','8576381','8580436','8580438','8580439','8576386','8576387','8580444','8576390','8576393','8580448','8583127','8536585','8569187','8574843','8569188','8570387','8570393','8570400','8570398','8575053','8575054','8570392','8570389','8545693','8546387','8546388','8545089','8545097','28615541','8580167','8580168','8580170','8579066','8576044','8579068','8579069','8579071','8576049','8576050','8579074','8579075','8576053','8579077','8576054','8576057','8579081','8576060','8576061','8579085','8576908','8576911','8578946','8558417','8560533','8560535','8560539','8560544','8560548','8560558','8560561','8560564','8560565','8560579','8560583','8579099','8578949','8579100','8579101','8579102','8579103','8578954','8576922','8578957','8579109','8579110','8579112','8576928','8574673','8574677','8564440','8564441','8550703','8562347','8562350','8562353','8562354','8562367','8537008','8579114','8579117','8551502','8551507','8546956','8536921','8538366','8582842','14488290','4198749','18915231','36279982','36280009','36214939','36213551','36215702','36213625','36214665','36215798','36215852','36215890','36196571','36258742','36196390','10524364','10524378','10524415','8696910','14282320','14767264','13939305','13939308','13939312','13939314','21930949','21931024','21931085','36122460','32522474','32523851','22809979','36131756','30181193','36134190','36215744','36215930','36213616','36215751','3600242','3601071','3590106','3622282','3622896','3627548','36213614','3633891','36214929','5499500','5499130','5497460','5496459','36266346','36242747','36226536','36249714','36128001','30971183','35112853','36124943','36124946','36125229','36125230','36125233','36125238','36125241','36123698','36123922','36123925','36118000','36118591','36118604','36102896','35869174','36102918','36101898','25064784','27962102','27964063','27962264','27962650','36292550','36251330','36129108','36128004','36128115','36129071','36179772','36126252','36124875','11535590','10501668','11609603','11609788','11609131','36126193','27959576','36129069','16392636','16510352','16510357','16510361','16510365','16510375','16392722','16510381','16510385','16392731','16510390','16510396','16392785','16510423','16510430','16510435','16510450','16510479','16392960','16371016','16371046','16392295','16392309','16392334','16370743','16371089','16392383','16371099','16370767','16371104','16392388','16370772','16392399','16370778','16392405','16370786','16392414','16371121','16370790','16370799','16392441','16371135','16392446','16370836','16392464','16511841','16511848','16511857','16511863','16511871','16511947','16511950','16511952','16511957','16370840','16370847','16371178','16392508','16370859','16371183','16370862','16392540','16392552','16370879','16392567','16392577',
'16370913','16370929','16392624','16370964','16370975','16370984','16370206','16370506','16369884','16370526','16370530','16369904','16370248','16511970','16511981','16511628','16511630','16511643','16511674','16511431','16511446','16511696','16511452','16511711','16511482','16511740','16511492','16511743','16370262','16370264','16370552','16369934','16370560','16369953','16370574','16370290','16369967','16370585','16370301','16370611','16370617','16370021','16370646','16370649','16370664','16370378','16370397','16370688','16370693','16370414','16370419','16370122','16511384','16511019','16511409','16511055','16511058','16511063','16511082','16510791','16511089','16510489','16510832','16510840','16510847','16510541','16510871','16510879','16510590','16510888','16510607','16510893','16510611','16510613','16510906','16511233','16511749','16511506','16511245','16511252','16511510','16511759','16511516','16511258','16511518','16511262','16511773','16511526','16511782','16511290','16511539','16511300','16511794','16511550','16511303','16511320','16511575','16511327','16511581','16511343','16511596','16511356','16511365','16511378','16511611','16511010','36129166','36089920','36100614','36136633','36101825','36105282','16370428','16370716','16370444','16370161','16370168','16369588','16369616','16326213','16369649','16369657','16369692','16369698','16326323','16369708','16369734','16326515','16369768','16369776','16369791','16369808','16369819','16369837','16369849','16369853','16369857','15738096','15738182','15738099','15738100','15738193','15738195','16095238','16274443','15738124','16315638','15738126','15738127','15738128','15738015','15738130','15738132','16315775','15738133','16315841','16315853','15738142','15738031','15738033','16326093','15738039','15738150','16326107','15738040','15738152','15738154','15738156','15738159','16326186','15738054','15738061','15738068','15738165','15738166','15738074','15738076','15737464','15737468','15737369','15737370','15737483','15737265','15737268','15737279','15737511','15737514','15737299','15737518','15737385','15737303','15737306','15737538','15737666','15737765','15737317','15737326','15737995','15737413','15737996','15737328','15737419','15737329','15737339','15737433','15737342','15737441','15736936','15737349','15737443','15737102','15737454','15737457','15737190','15737192','15736817','15593399','15593608','15593611','15593617','15593628','15593414','15593631','15593416','15593442','15593443','15593482','15593483','15593487','15593493','15204454','15204469','15204473','12476411','12477746','12456029','12600036','12600723','12600769','12600818','12600887','12600957','12475861','12456370','12474302','12474420','12474862','12475014','12475159','12475524','12476000','12476065','12476106','12456332','15736145','15736322','15736323','15736327','15736154','15736329','15736333','15736339','15736163','15736353','15736357','15736365','15736368','15736192','15736378','15708116','12609364','12609508','12609686','12622087','12622130','12622741','12622801','12622841','12622873','12623382','12623490','12623549','12601244','12601447','12601561','12601634','12601698','12604094','12604983','12605230','12605447','12608016','12608049','12608073','15594249','15594259','15594267','15593841','15593662','15593666','15593673','15593676','15593682','15593893','15593895','15593902','15593708','15594299','15593904','15594308','15593717','15593725','15593726','15593730','15594323','15594326','15593747','15736528','15736407','15736410','15736684','15736536','15736414','15736694','15736550','15736562','15736431','15736439','15736444','15736260','15736448','15736264','15736577','15736586','15736588','15736596','15736283','15736292','15736295','15736297','16510912','16510916','16511171','16510631','16510637','16510927','16510639','16511205','16510937','16511210','16510658','16511219','16510954','16510665','16510670','16510693','16510984','16510703','16510718','16510257','16510730','16510734','16510264','16510269','16510316','16510337','14740441','14740442','14740316','14740319','14740449','14740451','14740320','14740323','14740326','14740327','14740330','14740331','14740335','14740337','14740341','14740354','14740105','14739945','14739909','14740000','14739912','14739919','14578418','14578420','14578425','14739930','14578443','14739955','14740175','14604144','14739962','14739965','14739976','14740186','14740187','14739798','14739902','14740191','14740197','14739988','14740304','14740434','14740439','14819302','14819308','14739942','14578463','14578467','14579129','14492147','14492156','14492160','14492162','14492167','14573075','14573078','14573080','14573085','14443083','14443084','14575457','14578409','14488114','14443065','14443066','14443067','14443073','14740702','14740704','14740903','14740905','14740714','14740910','14740723','14740918','14740730','14740737','14740738','14740741','14740946','14740952','14740955','14740958','14740769','14741069','14741076','14740783','14740788','14740789','14740471','14740794','14740479','14740482','14740801','11307628','11738532','11307675','11511289','11511576','11189680','11511774','11512171','11189407','11062295','11062495','11062784','11062848','11064488','11064565','11065101','11128823','11120209','11130249','11130295','11062054','11189839','11189853','11189872','11189897','11189923','11306088','36108111','36152476','36117213','36120110','36222767','36231640','36158074','36242454','36180892','36212671','36215730','34477511','34668618','34768131','34915847','16512239','16512257','16512266','16512273','16512291','16512294','16512303','16512320','16511998','16512010','16512358','16512379','16512022','16512036','16512039','16512404','16512047','16512070','16512072','16512114','16512161','16511827','16512450','16512453','16512477','16512481','16512485','16593308','33708466','33781538','15593514','15593318','15593547','15593335','15593552','15593577','15593588','15593590','15593594','15593605','15593395','15736844','15737224','15737227','15736723','15736848','15736850','15737233','15736851','15736862','15736881','15736757','15736759','15736899','15736900','15736762','15736764','15736767','15736604','15736924','15736927','15736621','15736800','15736629','15736804','15736634','15736650','15736381','15736658','15736392','15593757','15593763','15593996','15593767','15593772','15594001','15594020','15593796','15593511','15736820','15737205','15736831','15737223','15204133','14819176','15203919','15204139','15203926','14819192','15203936','14819195','14819209','14819211','15203945','14819224','14819228','14819234','15203959','14819244','14819263','14819273','15203991','14819275','14819281','15203998','14819287','15204244','15204387','15204393','15204031','15204038','15204400','15204041','15204259','15204413','15204278','15204281','15204283','15204082','14819320','15204297','14819324','14819330','15204102','14819333','14819336','14819340','14819341','15204112','14819350','15204119','15032824','14741202','14741203','14741212','14741224','14741078','14741082','14741084','14741235','14741088','14741238','14741096','14741099','14741250','14741102','14741109','14741115','14741268','14741135','14741149','14741152','14740975','14741158','14740986','14740995','14741000','14740859','14740862','14741181','14741030','14740866','14740871','14740872','14741195','14740882','14740883','14740894','15204303','15204484','15204305','15204486','15204309','15204316','15204494','15204327','15204506','15204333','15204515','15204168','15204170','15204349','15204186','15204354','15204193','15204201','15204207','15204372','15204374','15204379','15204006','15204384','15594379','15594381','15594551','15666404','15594388','15594398','15594402','15594404','15594437','15594025','15708089','15594035','15594048','15594460','15594056','15594466','15594067','15594070','15708112','15708115','15594199','15594482','15594213','11140274','11140546','11140581','11141123','11141157','11141261','11128551','11128693','14819313','14819116','14740802','14740617','14740622','14740376','14740631','14740378','14740633','14740381','14740642','14740385','14740645','14740387','14740647','14740649','14740391','14740650','14740200','14740653','14740397','14740655','14740657','14740403','14740661','14740218','14740666','14740414','14740227','14740673','14740237','14740426','14740427','14740431','14740432','15708123','15736230','15708124','15736237','15708131','15708136','15736121','15736256','15736133','15736134','15736137','15708162','15708165','15708169','15594494','15594498','15708175','15594510','15708194','15594523','15708201','15594531','15594537','15708208','15594375','11791961','11820998','11821400','13904861','36317227','36317228','36301218','36301244','36301423','36302185','36283921','36300262','36300274','36300289','6157706','27624130','24800111','29429447','29429619','29429631','29429664','29428826','29429749','29428891','29429899','29430952','29428366','29429079','29429139','29973977','14346993','14339690','13518478','33729895','33729926','33730024','33729686','33729756','33729791','32815675','31604030','22543687','22545377','22533436','22533587','22533590','22533592','14347024','14345449','26806756','26806911','26807549','17672572','17672384','17672388','17672595','17672408','17672619','17672433','17672209','17672210','17672643','17672227','17672457','17672247','17672471','17672485','17672266','17672486','17672306','17672310','17672328','17672084','17672087','17672089','17672348','17672112','17671864','17672126','17671888','17671916','17671917','17672153','17671931','17671933','17672175','17671738','17671756','17672022','17671766','17672034','17671778','17671780','17671785','17671798','17671980','17672181','17671719','17671733','17672673','17672689','17672700','17672712','17672736','17672538','21707858','21708244','14346925','14346940','14345457','14347045','14347049','14391050','14348297','13659705','13662230','34419339','34417590','17670721','17671006','17670449','17671019','17670734',
'17671028','17670742','17670770','17670777','17670779','17670780','17670523','17670788','17670529','17671205','17671215','17671218','17671223','17671250','17671277','17671281','17671293','17671301','17670243','17670274','17670282','17670599','17670315','17670331','17670333','17670338','17670120','17670124','17670125','17671642','17671644','17671348','17671355','17671374','17671387','17671692','17671323','17671328','17671154','17671332','17671159','17670815','17670817','17671171','17671175','17670822','17671183','17670845','17670871','17670625','17670671','17670947','17670687','17670697','17670703','17670976','17670984','17671821','17671836','17671614','17671125','17671133','13267252','13268342','13240223','13269113','13240514','13269372','36117914','17670382','17670390','17670395','17670405','17669924','17670152','17670154','17669945','17670178','17670181','17670190','17669989','17670198','17669991','17670008','17669726','17670030','17670035','17669860','17669865','17665534','17669617','17669894','17669632','17669635','17669653','17665612','17669667','17669670','17669679','17669682','17669499','17665123','17665142','17665160','17664763','17669530','17664961','17664971','17665360','17670070','17669786','17669544','17669798','17669546','17669547','17665401','17665410','17665092','17665465','17665114','17665490','17665503','17665505','17669848','17665511','24799632','22400084','22288747','22308989','22253418','22154650','17664905','17664929','17664627','17664652','14346980','14345420','14339673','14329304','36220197','7092387','12453090','10858052','10858541','12376774','12384512','36220218','36220210','25210353','25210453','25210479','23281325','23280587','36125484','36125505','25210229','23279862','23279912','23281172','23280086','23280115','23280155','13239704','13267149','13269723','13239998','6129107','23280350','23280400','23280406','23280448','23280480','23279745','23279748','12066269','12066270','12066239','36217421','36217442','36217450','5279268','17670287','17671542','17671493','17671290','17672391','17672584','17672232','17672231','17671558','17671260','17671187','17671262','17669833','17672167','17669784','17670002','17672649','17671191','17664750','17672024','17670012','17669809','17664798','17672101','17670074','17669578','17665268','17670321','17670533','17669580','17671140','17671177','17669805','17670478','17669598','17669607','17670099','17672162','17669624','17670263','17669589','17670304','17672257','17672192','17671436','17669976','17665508','17669503','17671967','17671149','17669984','17669853','17671486','17671120','17672019','17671016','17671659','17669845','17669889','15586931','25210093','23280927','17672402','17672482','17672656','17672661','6141827','6126494','17669839','36173937','36117147','36176128','36132469','36141417','36141425','36144738','36144744','36108107','36144757','36108883','36154681','36117020','36117036','36117144','35848545','35905244','36262635','36273180','36273220','36286652','36273226','36273236','36273359','36173857','36173899','36173910','36282271','36282287','36282322','17671345','17672353','17671124','17665456','17672058','17669755','17670826','17664987','17671357','17669998','17671824','17671668','17664647','17671833','17670736','17670083','17672695','17672148','17664991','17670457','5397754','36176101','22572912','36298732','17671873','5743601','3873114','3873199','3872417','3873056','3882757','3882795','3881962','36224442','36220198','36228055','36226980','36264101','36278321','36278429','36278446','36281028','36278302','36269178','36277136','36273122','36316440','36306617','36265457','36265462','36267522','36263797','36263869','36262511','36262523','36262549','36230881','36249085','36232283','36182214','36225895','36204858','36204917','36204925','36199811','36202675','36205001','36198517','32114545','36164077','36198514','34431095','34431551','34066982','36106348','36114292','36106365','36114299','29718598','29718628','35745955','36106390','32852634','13692518','13582860','13560478','13692584','13692592','13582933','13582900','13582908','13560518','13560527','13560523','13560539','13560483','13560482','13560491','13697729','13719931','13720377','13728485','13550220','13550221','13550209','13500379','13500364','13500363','13500376','13068283','13068285','13068289','13068414','15029319','15029328','15029332','15032230','15032231','15032259','15032267','15032292','15032294','15032244','15380626','15380644','15380647','15379684','15379695','25402874','14939505','14939496','14939497','14939502','14903510','14939517','14892960','14903497','14892925','14892931','23724297','17538443','22319120','14892828','14892879','14892864','14892820','14903454','25360957','25361080','14903546','14903542','14903500','14901862','13692552','13550263','13550257','13550231','13550275','13550272','13500393','13717865','13697745','13718524','13697752','13500411','13550307','13520596','12688381','12688392','12688402','12688412','12688444','12710881','12710899','12710907','12710916','12416281','13759618','13759458','13550323','13072446','13181707','13158071','13072419','13063650','13063658','13015338','13015341','13015379','13015298','13015307','36126398','36126404','36126405','36126406','36126407','36126408','36123547','36123559','36123561','36123567','36123572','36123584','36123585','36123589','36123590','36123598','36123605','36126419','36126423','36126424','36126425','36126428','36126430','36126437','36126441','36123520','36123531','36123533','36153214','36138686','36138687','36138688','36137740','36137728','36138572','36138573','36138581','36138592','36138615','36173203','36177848','14712323','14712316','14712326','14712308','14645418','14602786','14580900','14505117','14505105','14505106','14331334','14331327','14331329','14331331','14197402','14166401','14163882','14197390','14502633','14505170','14502632','14502629','14505164','14505166','14311906','14311907','14672964','14652936','14652913','14349132','14311936','14206264','14197492','14712293','14673002','14673005','14673012','14536874','14505146','14505153','14502657','14762669','14712338','14712342','14672981','14331340','14331337','14311916','14311918','14240366','14762665','14762659','14652953','14652945','14652940','14645397','14645390','14645392','14645395','14536911','14505127','14328939','14331350','14331352','14311922','14197443','14197449','14712358','14645452','14602808','14580903','14602797','14580906','14580904','14502669','14502663','14502665','14502660','14502667','14331344','14331342','14197475','14197471','14197473','14712280','14602816','14538403','14505131','11541273','11637853','11653506','14652931','14624334','14624341','14624342','12329489','14941253','36222469','36222478','36222482','36222485','36265296','36265488','36280852','36253600','14238014','14238033','14238881','14267893','14267895','14226214','14268645','14226217','14268647','14238894','14268843','14268845','14226232','14268846','14226244','14226245','14239440','14239445','14226257','14239456','14228813','14226206','13689612','13689620','13689625','13689627','13689637','13616652','13616661','13616668','13689662','13616681','13689684','13616588','13616596','13616609','13579803','13579804','13579813','13579824','13522147','13530590','13530593','13530625','14228821','13689703','14228823','14228831','14228832','14228838','14228841','14228844','14228854','13689554','13689556','14226203','13609138','13609185','13609208','13609229','13609238','13609255','13559264','13616572','15149807','14941201','14941204','14941206','14941207','14941213','14941217','14941226','14941228','14941229','14941230','14941238','14941241','14941245','13530640','13530716','13530720','13530726','13530744','13530752','13530775','14466611','14466617','14466618','14432223','14432224','14466623','14464794','14464797','14466626','14464802','14464803','14464805','14464806','14464815','14464817','14395038','14541477','14464820','14432210','14466606','14466608','14432216','13320116','13320146','13320150','13313349','13313362','13313364','13034476','13034389','14643672','14643675','14738277','14738766','14738767','14738986','14739000','14624357','14624365','14624377','14643651','14643654','14643657','12498742','12498750','12330373','14903439','15149713','15149726','15149756','15583064','15583096','15583125','15583128','15583162','15583188','15583201','15583210','15583227','15583242','15583245','15583264','14786833','14786838','14786843','14788372','14788378','29715294','14539773','14541518','14539774','14539780','14539811','14539812','14539814','14941257','14941176','14579433','14579451','14579468','14541480','14579473','14541481','14579619','14579622','14541487','14541489','14541490','14541493','14541494','14541498','14541502','14541504','14579632','14579635','14794650','14788428','14794634','14794635','14794649','14788391','14788397','14788420','14325726','14299898','14325731','14299904','14325738','14299926','14299936','14299943','14268853','14269072','14269080','14269084','14269265','13034283','13045057','13034293','13034297','13034308','13045070','13034334','13034360','13034373','13034377','13034385','13034395','13034454','13313233','13313241','13313254','13313260','13313268','13313280','13313296','13045010','13045014','13045033','13045046','13045048','12631228','12631243','12645854','12625648','12625654','12625656','12713615','12713619','12713623','13034508','13039848','12713585','12713598','12713606','14391772','14391795','14395001','14395004','14325671','14395010','14325678','14325688','14395015','14395016','14325695','14325696','14325701','14395023','14395026','13034339','14237989','14237992','12453576','12453581','12421797','12421829','12448009','12448014','12448015','12337623','12448020','12448033','12625647','12597837','12597847','12597865','12604640','12506355','12506356','12453601','12506374','12506377','12506395','12506400','12448043','12506418','12448050','12448054','12448062','12506597','12448066','12448067','12479884','12506681','12448072','12506688',
'12506689','12448078','12448080','12448089','12448093','12604696','12622768','12622803','12622850','12625627','12625633','12421748','12448095','12506325','12448098','12506326','12506327','12448105','12421763','12453563','12453565','12421774','12421777','12631177','12631178','12604644','12631192','12604681','12631203','12329471','12329472','12329508','13034346','12625675','14892964','36230982','14652939','14652963','14652968','14712344','14579625','12713645','14738991','14238911','14645432','14240284','14238896','12631222','12625680','12604645','34431258','36114619','13888967','13888692','13888466','13891736','13891559','13891579','13891365','13891376','13890918','13891385','13891390','13891418','13887785','13887593','13887251','13888020','13888038','13893249','13893801','13893445','13893522','13893952','13893279','13892787','13892858','13892870','13893100','13886906','36236398','36241271','36247728','36241364','13888767','13888785','13888392','13886965','13886975','13887006','13889602','14793907','14794627','26263691','26268593','26263906','26268886','26269128','26269336','26275151','26266046','26269704','26275506','26275580','26267173','26234005','26234329','26234018','26267545','26261794','26261893','26234068','26227143','26293104','26290009','26290075','26290242','26287140','26293832','26290731','26287598','26287780','26294742','26283416','26262546','26227200','26262627','26262937','26227270','26227279','26263109','26234205','26263261','26233837','26263529','26233921','26233942','26233951','26233964','26226800','24749041','26226854','24750068','26226954','24751938','26227015','26279796','26279885','26279974','26280153','26288353','26288461','26288663','26288970','26289064','26227055','13892306','13892337','13891929','13891939','13891944','13891803','13891982','13892017','13890492','13889926','13889764','13889776','13889567','13891439','13890694','13890536','13890744','13892411','13892430','13892271','13891922','13891478','13891657','13891667','13891532','13891551','13892651','13892653','13892703','13890597','13890384','13887733','13887560','26278063','26278306','26278500','26282413','26286407','26286466','26278712','26273597','26273641','26279067','26279213','26268042','26274104','13888245','13888264','13888109','13886604','13886609','26338335','26333216','26335814','26333570','26339107','26333725','26337528','26334855','26324697','26337759','26335095','26322494','26325072','26301371','26301403','26322982','26331579','26323035','26323070','26322150','26322269','26322304','26297570','26299433','26299515','26299557','26297703','26297718','26297994','26295203','26295398','26298602','26295625','26298639','26301686','26323137','26301853','26323343','26301890','26323444','26301963','26323539','26321499','26332473','26332533','26332606','26332723','26324298','26298654','26295991','26296232','26300901','26301186','26301223','26289551','26297148','26297230','26289709','26292904','26286650','26289927','26341040','26341173','26345289','26345375','26343796','26344091','26344710','26340956','26340986','30354140','30355418','31162290','31264769','31264984','31269119','31270971','29947628','36221612','36221616','36230923','36199778','28581066','14244364','14244373','14244383','14244924','14244931','14244932','14245138','14245149','14245150','14245152','12604611','14244359','13892210','36225884','36199782','26266371','14794630','36234372','9155016','36155080','6397327','5668906','4275621','8389615','36229926','6398623','4272332','36304152','36268071','36268144','36268164','36266947','36267520','36232600','36233048','36218762','36219604','4277022','4277036','4277046','4272254','4268779','4205061','4198821','4205280','4131011','4131097','4243226','4276610','4202544','4205339','4205578','4205650','4199085','4199127','4199190','4174545','4199278','4243140','6397126','6397565','6350732','6351942','6352179','6352752','6353265','6379538','6379599','6346113','6347412','6349469','6350658','31892323','31890709','31890894','31888743','31790579','31790664','31790757','31298173','30487273','30564953','31889511','31790279','31790289','31791004','31888659','31790345','31888680','31889178','31889237','31889272','31889297','31890017','30090427','30173576','30175831','25999713','22006727','22007290','22008297','21951458','22358883','19651341','18920329','18920354','18920389','18920174','18920212','18919798','18919811','18919817','18920018','18920255','18920024','19047637','14737351','28615704','18910375','18912503','18913102','18909515','18908967','18909639','18909716','18913226','18913281','18912848','18913331','18911089','36161457','18910103','18910155','18907828','18907124','18907699','18905097','18904504','18904515','18904572','18904595','18903842','18903557','18902559','18902574','18903763','18006118','18006136','18902658','18902832','18906021','18906073','18906117','18903933','18903937','18903959','18903976','18904007','18904234','18904246','18904274','18903519','18902871','15363558','31891240','31891264','31889598','31889644','31888942','31889070','31890594','31890610','34375455','31892782','31892808','31891285','31891347','31891414','31892721','32165076','33193904','32454570','32474903','28076347','28614203','28614221','28614252','28614309','14737378','14737297','14737304','13342164','28956875','29067719','31893139','31892399','31892404','31892443','31891944','28615577','28615644','28489205','28256209','28228815','28228890','28256352','28228543','28228713','9747865','9747723','9747866','9747718','9751612','9747727','9747730','9747732','9747875','9747734','9747877','9747736','9747878','9751605','9747713','9747868','11865399','11866861','10321317','10321330','9999669','9966209','9966232','9999648','9999652','9999657','23011998','22884390','24665974','24513581','24659380','22502726','22503461','22504272','22446177','22508223','22497939','22498013','22498905','22500318','22500611','18914572','18915185','18915514','18915517','18915211','18914790','18914825','18917745','18917285','18914221','18914843','18914244','18914250','18914266','18913934','18914037','18915275','18915379','18914941','18915406','18916124','18915686','18915953','18916070','18916868','18916949','18916954','18916967','18917067','18916352','18916371','18914499','18914106','18914521','18914546','18913749','18913765','18915802','26673972','27453275','27454917','26606819','26608171','26385028','20845476','21640026','21367552','21376382','21643949','20738285','20738834','21610516','21620004','21367163','18917668','18917223','18917713','18918686','18918421','18918138','18918491','18918188','18918207','18919567','18919610','18918244','18918317','18918843','18918847','18918512','18918979','18918987','18918994','18919013','18919977','18919429','18919447','18920454','18920460','18919500','18912048','18911595','18912068','18912249','18912296','18911899','18912805','18912355','18912360','18912813','18912826','18911692','9749180','9830423','9830752','9840985','9840986','9840989','9841265','9841276','9841287','9841297','9841298','9841306','9830454','9830473','9830389','9830494','9941591','9941599','9941602','9941607','9941642','9941644','9941647','9941648','9941663','9941665','9941667','9941561','9941800','9941564','9941565','9941571','9941573','9941578','9941581','9941583','9941587','9915698','9915699','9915704','9915728','9915732','9915736','9915739','9915744','9915753','9915754','9918017','9916318','9918751','9749687','9749697','9749698','9749699','9749702','9747695','9747698','9751592','9747700','9749994','9747705','9750254','9747707','9747709','22507502','18903044','18903108','23429413','23429718','18918095','27996348','11831402','11831405','11831426','11830324','11906821','11905526','18916526','18916551','18916615','31893109','18918883','18903820','21377368','18915462','18920028','36215742','12632853','18914549','18918429','36271475','8574541','8574459','8574465','8574549','8574552','8574468','8574477','8574479','8574480','8574486','8574487','8574489','8574490','8574491','8574492','8569625','8569636','8569638','8569526','8570203','8569976','8570207','8569979','8570208','8570660','8570569','8570573','8570574','8570579','8570675','8570679','8569618','8569389','8569391','8569394','8569517','8569399','8569401','8569406','8569415','8569424','8570212','8570217','8570219','8569705','8569710','8569538','8569717','8569719','8569545','8569449','8569464','8569465','8570313','8570320','8570222','8570327','8570229','8570231','8570232','8570234','8570235','8570238','8570257','8570265','8570267','8570268','8570270','8570278','8570288','8570294','8584025','8584035','8584037','8584044','8584051','8584057','8584062','8584064','8584067','8583312','8582960','8582968','8582977','8582980','8583149','8583532','8583542','8583552','8583562','8583411','8583412','8583579','8583580','8583420','8583428','8583328','8583429','8583602','8583329','8584356','8584360','8584407','8576707','8576710','8576711','8576712','8576713','8576577','8576579','8576586','8576588','8576589','8576592','8576596','8576598','8576933','8576784','8576787','8576788','8576791','8576795','8576797','8576800','8576803','8576627','8576704','8576705','8576706','8576907','8577138','8577067','8577140','8577073','8577075','8577077','8577079','8577080','8577081','8577152','8577153','8577099','8576938','8577102','8577103','8577104','8577107','8577108','8577190','8577196','8577273','8577282','8577291','8577294','8577295','8577296','8577154','8577156','8577157','8577302','8577172','8577174','8577175','8577168','8567679','8567549','8567551','8567552','8567434','8567439','8567610','8567488','8575435','8575436','8575373','8575388','8567056','8566549','8566533','8566535','8566346','8566354','8566153','8566365','8566368','8566370','8566372','8566373','8566381','8566385','8566184','8566400','8566988','8566992','8567001','8567005','8567642','8567644','8567648','8567523','8567533','8567675','8566032','8566071','8566084','8566087','8566091','8566103','8566117','8565936','8565756','8566613','8566632','8566475','8566647','8566649','8566556',
'8566568','8566569','8566417','8566607','8576502','8576503','8576504','8576574','8576430','8576576','8576509','8576510','8576512','8576514','8576515','8576516','8576517','8576455','8576518','8576356','8576520','8576458','8576522','8576523','8576359','8576360','8576525','8576361','8576362','8576469','8576475','8575725','8575726','8575757','8575692','8575759','8575694','8575760','8575695','8575696','8575697','8575763','8575698','8575699','8575704','8575705','8575706','8575707','8575709','8575710','8575711','8575712','8575713','8575714','8575715','8576321','8576322','8576324','8576325','8576326','8576332','8576333','8576405','8576336','8576337','8576342','8575736','8575737','8575745','8575683','8575684','8575685','8575687','8575688','8575717','8575719','8575720','8575724','8564467','8564473','8564484','8564487','8564330','8564332','8564336','8564337','8564800','8564807','8564817','8565355','8565362','8565226','8565237','8565239','8565243','8565463','8565102','8565493','8565294','8565498','8565313','8565327','8565337','8565342','8565345','8565952','8565956','8565974','8565979','8565984','8565797','8565802','8565808','8565665','8565668','8565841','8565852','8565536','8565859','8565701','8565709','8565710','8565717','8565565','8565721','8565569','8565722','8565724','8565580','8565581','8565737','8565591','8565595','8565596','8565388','8565391','8565394','8565623','8565411','8565429','8581520','8581605','8581523','8581606','8581608','8581532','8581533','8581615','8581537','8581618','8581619','8581620','8581621','8581622','8581623','8581547','8581626','8581549','8581550','8581465','8581552','8581553','8581554','8581555','8580942','8580884','8580886','8580948','8580890','8580893','8580894','8580953','8580733','8580791','8580793','8580794','8580799','8580800','8580803','8580259','8580270','8580273','8580197','8580276','8580278','8580207','8580210','8580211','8580212','8580213','8580291','8580216','8580217','8580293','8580218','8580294','8579700','8579704','8579705','8579631','8579635','8579636','8579720','8579577','8580098','8580103','8580105','8581843','8580107','8580041','8580110','8580044','8580045','8580047','8580050','8580118','8580053','8580120','8580056','8580057','8580060','8580061','8580062','8580065','8580070','8580071','8580072','8580073','8580074','8579724','8579581','8579586','8579732','8579588','8579733','8579590','8579735','8579592','8579593','8579622','8579506','8579426','8579508','8579429','8579511','8579512','8579432','8579433','8579515','8579434','8579516','8579436','8579518','8579519','8579520','8579522','8579442','8579252','8579254','8579879','8579881','8579882','8579883','8579886','8579887','8579888','8579799','8579896','8579898','8579902','8579538','8579539','8579486','8579490','8579562','8579564','8579565','8579494','8579415','8579569','8579570','8579418','8579419','8579572','8579420','8579421','8579504','8579423','8582829','8582830','8582743','8582836','8582746','8582843','8582757','8582941','8582771','8582694','8582442','8582445','8582700','8582449','8582453','8582454','8582456','8582459','8582460','8582706','8582470','8582472','8582191','8582193','8582194','8582195','8582202','8582388','8582393','8582181','8581944','8581951','8581952','8582047','8582049','8582053','8582057','8581965','8582059','8581967','8581969','8582061','8581819','8581820','8581821','8581825','8581826','8581676','8581680','8581835','8581755','8581756','8581688','8581690','8581694','8581763','8581696','8583021','8583027','8583106','8583044','8582815','8582820','8582821','8582822','8582732','8582823','8582826','8582735','8583356','8583358','8583362','8583372','8583375','8583377','8583293','8583387','8583393','8583400','8581697','8581701','8581771','8581706','8581710','8581713','8581779','8581788','8581581','8581491','8581493','8581409','8581410','8581413','8581419','8581422','8584417','8584421','8584424','8584079','8584098','8584331','8584335','8584340','8582215','8582331','8582222','8582340','8582350','8582361','8582233','8582364','8582375','8582381','8581396','8581397','8575472','8575391','8575473','8575394','8575395','8575404','8575329','8575416','8575417','8575331','8575419','8575420','8575421','8575422','8575425','8575337','8575426','8575427','8575428','8575429','8575430','8575342','8575434','8575344','8575345','8576015','8576016','8575967','8575970','8575973','8574556','8574711','8574587','8574592','8574596','8574240','8574243','8574173','8574175','8574074','8574177','8573678','8573898','8573905','8573908','8573909','8574090','8573918','8573925','8573928','8574038','8573930','8573931','8574044','8573935','8574046','8573938','8573939','8573941','8573942','8574051','8574052','8574054','8574056','8573948','8574057','8573952','8573854','8573865','8573869','8573883','8573885','8573888','8573889','8573890','8573891','8573893','8573895','8574267','8574270','8574273','8574280','8574293','8573637','8573641','8573460','8573562','8573464','8573469','8573643','8573472','8573476','8573647','8573571','8573572','8573480','8573582','8573544','8573547','8571003','8571011','8571020','8571029','8571032','8571035','8571042','8571050','8571065','8568299','8568602','8568740','8568609','8568624','8568762','8568643','8568645','8568662','8568011','8568016','8568017','8568023','8568027','8568028','8568033','8568036','8568038','8568837','8568663','8568551','8568933','8568935','8568799','8568807','8568810','8568958','8568811','8568816','8568821','8568927','8572804','8572807','8572702','8572718','8572721','8572726','8572482','8572486','8572491','8572498','8572639','8572511','8572516','8572517','8572519','8576165','8576186','8576187','8573584','8573585','8573586','8573594','8573597','8573598','8573599','8573600','8573603','8573606','8573400','8573402','8573403','8573406','8573407','8573317','8575346','8575276','8575210','8575214','8575361','8575216','8575217','8575218','8575219','8575220','8575224','8575225','8575227','8575231','8575234','8573415','8573416','8573418','8573419','8573424','8573426','8573430','8573435','8573436','8573233','8573257','8572854','8572860','8572749','8572869','8572871','8572873','8572875','8572758','8572880','8572762','8572883','8572885','8572766','8572657','8572768','8572658','8572659','8572660','8572664','8572902','8572667','8572670','8572783','8572671','8572784','8572673','8572674','8572676','8572677','8572791','8572797','8572346','8572352','8572244','8572249','8572356','8572256','8572266','8572155','8572274','8572159','8572380','8572164','8572176','8572177','8572291','8572524','8572393','8572526','8572527','8572403','8572404','8572405','8572416','8572443','8572448','8572449','8572320','8572056','8572299','8572062','8572063','8572065','8572070','8572207','8572087','8572217','8572088','8572230','8571950','8572128','8572129','8571954','8572133','8572142','8576527','8576622','8576535','8576538','8576539','8576479','8576481','8576542','8576483','8576410','8576491','8576556','8576492','8576557','8576559','8576418','8576563','8576496','8576420','8576565','8576498','8576566','8574945','8574846','8574848','8574952','8574954','8574870','8574871','8574872','8574874','8574876','8574878','8574882','8575980','8575906','8575801','8575802','8575804','8575805','8575807','8575811','8581830','8575728','8575734','8575735','8574495','8574602','8574497','8574604','8574498','8574605','8574500','8574501','8574503','8574611','8574505','8574507','8574508','8574509','8574510','8574511','8574515','8574517','8574522','8574523','8574532','8574534','8574535','8574458','8575177','8575179','8575180','8575107','8575182','8575111','8575112','8575113','8575114','8575189','8575192','8575069','8575077','8575208','8575209','8575086','8575087','8575088','8575142','8575144','8575146','8575147','8575149','8575150','8575098','8575100','8575156','8575106','8574958','8575018','8574965','8574966','8574900','8574970','8575035','8575040','8574919','8575045','8574980','8574922','8574981','8574923','8574982','8574924','8574983','8574984','8574985','8574986','8574987','8574929','8574993','8574895','8574831','8574837','8574625','8574890','8574893','8564273','8553287','8552931','8552936','8552951','8552953','8552956','8552958','8552959','8552963','8552966','8553126','8553129','8553138','8553272','8552974','8552860','8552864','8552982','8552867','8552993','8552872','8552873','8552881','8552999','8553003','8552885','8553019','8552912','8552918','8554130','8554257','8554260','8554261','8554146','8554166','8554169','8554170','8554033','8554037','8554039','8554040','8554178','8554046','8553909','8553927','8553930','8552927','8552929','8562587','8562599','8562609','8562621','8562627','8562628','8552542','8552452','8552052','8552054','8552055','8552060','8552063','8552067','8552183','8551963','8551966','8552299','8552301','8552305','8552306','8552309','8552310','8552312','8552185','8552313','8552186','8552315','8552187','8552318','8552196','8552197','8552199','8552348','8552243','8552278','8552395','8552285','8552286','8552361','8552577','8552364','8552578','8552736','8552590','8552738','8552739','8552710','8552722','8552585','8552726','8563087','8563090','8563091','8563094','8563096','8563097','8563099','8563101','8563103','8563107','8556688','8556816','8556821','8556823','8556831','8556845','8556846','8556600','8556847','8556715','8556722','8556617','8556622','8556580','8556331','8556346','8556349','8556623','8556758','8556759','8556640','8556642','8556768','8556650','8556657','8556661','8556663','8556667','8556668','8556675','8557612','8557379','8557380','8557522','8557414','8557537','8557430','8557444','8557451','8557460','8557212','8557215','8557358','8557364','8557365','8556772','8556774','8556778','8556378','8556263','8556264','8556389','8556391','8556392','8556395','8556273','8556400','8556284','8556290','8556168','8556300','8556309','8556191','8556310','8556193','8556314','8556196','8564083','8564090','8564293','8564108','8564111','8563951','8563953','8563959','8563963','8563972','8554924','8554939','8554942','8554945','8554950','8554952','8554955','8554959',
'8554963','8555195','8555196','8555199','8555100','8555103','8555215','8555104','8555217','8555105','8555218','8555107','8555219','8555223','8555114','8555115','8555117','8555118','8554973','8554978','8554982','8554871','8554880','8554994','8554996','8554883','8555577','8555365','8555370','8555372','8555374','8555375','8555379','8555380','8555381','8555383','8555227','8555384','8555386','8555268','8555269','8555281','8555293','8554419','8554422','8554428','8554432','8554436','8554194','8554196','8554441','8554197','8554216','8554223','8554229','8554232','8554242','8554659','8554675','8554677','8554679','8554525','8554527','8555076','8555078','8555087','8554863','8554865','8554531','8554532','8554535','8554537','8554539','8554543','8554544','8554545','8554620','8555301','8555414','8572944','8572945','8572948','8572821','8572822','8572824','8572825','8572829','8572837','8572846','8572851','8572916','8572927','8571595','8571925','8572046','8571935','8571936','8571941','8571947','8571820','8571828','8570851','8570853','8570989','8570858','8570859','8570878','8570879','8570883','8570887','8570890','8571070','8570892','8570894','8570902','8571083','8572004','8571888','8571890','8572020','8571892','8571895','8572035','8572147','8572150','8571969','8571849','8571852','8571986','8571990','8571867','8571996','8562655','8562661','8562793','8562666','8562667','8562797','8562671','8562673','8562677','8562453','8562460','8562571','8562461','8562573','8560803','8560804','8560671','8560672','8560688','8560830','8560840','8560842','8561129','8561134','8560594','8560615','8560616','8560622','8560629','8560481','8560490','8560388','8560657','8560398','8560510','8560276','8560278','8560003','8560009','8560013','8560018','8560029','8560032','8560042','8560044','8560045','8560058','8559902','8560071','8559914','8559917','8560077','8559919','8560078','8559921','8559923','8559925','8560088','8559928','8560093','8559936','8560666','8560290','8560449','8560457','8560311','8560336','8560217','8560359','8560366','8560370','8560371','8560373','8560377','8559983','8559633','8559634','8559938','8559939','8560407','8563576','8563421','8563442','8557822','8557840','8557842','8557732','8557733','8557637','8557761','8558324','8558637','8558657','8558503','8558504','8558859','8558863','8558762','8558516','8558526','8558528','8558539','8558543','8558545','8558453','8558457','8558458','8558462','8558464','8558471','8558478','8558480','8558481','8558482','8558484','8558487','8558489','8558491','8558492','8558495','8558150','8557890','8558151','8558011','8557897','8558169','8557932','8557936','8557937','8557940','8557544','8557572','8557463','8557464','8557473','8557586','8557475','8557588','8557487','8557954','8557957','8557958','8558085','8558088','8558089','8557978','8557981','8558100','8557986','8557991','8557855','8557997','8557879','8558004','8557884','8558007','8558235','8561417','8561421','8561426','8561429','8561574','8561441','8561454','8561479','8562123','8562124','8562127','8562128','8562135','8562138','8562156','8562158','8562163','8562169','8562175','8562062','8562277','8562282','8562286','8562288','8562297','8562195','8562205','8562116','8562117','8562118','8562232','8562233','8562122','8561860','8561623','8561489','8561491','8561629','8561630','8561634','8561876','8561502','8561882','8561649','8561650','8561663','8561664','8561527','8561528','8561398','8561552','8561910','8561918','8561672','8561815','8561816','8561821','8561596','8561599','8561605','8561610','8561612','8561616','8562642','8562643','8562644','8562645','8562646','8562774','8562647','8562777','8562071','8561883','8562097','8561886','8561887','8563071','8563072','8562105','8561891','8562107','8561892','8561894','8561895','8561896','8562115','8564026','8563539','8563544','8563558','8563565','8559404','8559191','8559192','8559193','8559197','8559203','8559207','8559209','8559772','8559647','8559649','8559802','8559804','8559809','8559568','8559577','8559582','8559583','8559584','8558998','8558885','8558888','8558890','8559010','8558893','8558898','8558906','8558907','8558817','8558624','8558827','8559090','8559228','8559230','8559235','8559244','8559246','8559247','8559106','8559017','8559128','8559145','8559592','8559620','8559621','8559502','8559165','8559051','8558983','8558864','8558866','8558996','8558878','8558997','8549588','8549592','8549593','8549594','8549530','8582071','8582076','8581794','8581795','8581941','8583957','8583617','8583639','8583523','8583524','8583646','8583525','8583528','8583530','8583666','8582785','8582953','8582786','8582808','8582809','8582812','8582621','8582622','8582623','8582625','8582626','8582419','8582428','8582693','8580721','8580731','8580565','8581031','8581189','8581190','8581041','8581194','8581055','8581057','8581060','8581063','8581066','8581148','8581068','8581150','8581070','8581074','8581078','8581156','8581332','8581269','8581023','8581175','8581024','8581185','8580588','8580594','8580595','8580600','8580319','8580243','8580245','8580333','8580295','8580297','8580222','8580299','8580300','8580224','8580227','8580229','8580232','8580236','8580238','8580137','8580079','8580145','8580080','8580081','8580082','8580085','8580089','8580090','8580096','8577905','8577977','8577907','8577908','8577909','8577981','8577984','8577986','8577987','8577919','8577923','8577926','8577786','8577929','8577790','8577648','8577650','8577652','8577653','8577654','8577661','8577662','8577663','8577598','8577604','8577605','8577607','8577611','8577612','8577614','8577410','8577411','8577742','8577665','8577667','8577668','8577670','8577674','8577756','8577757','8577759','8577616','8577618','8577619','8577772','8577774','8577776','8577778','8577780','8577706','8577858','8577861','8577862','8577804','8577866','8577805','8577808','8577814','8577816','8577817','8577825','8577827','8577417','8577418','8577419','8577420','8577426','8577427','8577430','8577432','8577433','8577435','8577436','8577443','8577307','8577445','8577446','8577447','8577315','8577453','8577454','8577380','8577455','8577381','8577457','8577462','8577387','8577322','8577388','8577326','8577397','8577399','8577400','8577401','8577333','8577403','8577404','8577335','8577408','8577339','8577409','8577264','8577265','8577270','8577450','8577312','8577451','8581485','8581360','8581367','8581239','8581368','8581240','8581243','8581372','8581373','8581378','8581253','8581312','8581254','8581385','8581315','8581386','8581316','8581259','8581392','8581321','8581323','8578013','8578014','8578018','8578019','8578020','8578021','8578023','8578024','8578025','8577898','8577899','8577901','8578202','8578366','8578051','8578140','8578193','8577993','8577994','8578001','8578005','8578253','8578254','8578256','8578258','8578154','8578155','8578260','8578100','8578157','8578262','8578110','8578113','8578044','8578116','8578117','8578176','8578049','8578570','8578571','8578490','8578492','8578495','8578373','8578497','8578424','8578425','8578586','8578426','8578428','8578429','8578430','8578600','8578432','8578433','8578434','8578436','8578509','8578438','8578510','8578440','8578512','8578514','8578445','8578516','8578446','8578448','8578449','8578450','8578313','8578452','8578453','8578454','8578316','8578456','8578318','8578458','8578460','8578461','8578462','8578467','8578468','8578469','8578264','8578266','8578271','8578272','8578635','8578631','8578632','8578470','8578476','8580809','8580810','8580700','8580701','8580706','8580707','8580708','8580710','8580712','8580715','8580716','8580717','8579063','8579064','8578934','8578935','8578936','8578943','8579029','8578724','8578725','8578730','8578732','8578734','8578735','8578683','8579400','8579407','8579214','8579215','8578924','8578927','8578794','8578797','8578798','8578799','8578800','8578802','8578804','8578882','8579220','8579221','8579155','8579226','8579228','8579159','8579229','8579161','8579168','8579175','8579176','8579185','8579034','8579039','8579043','8579044','8578984','8578985','8578986','8578987','8579053','8579054','8579055','8578990','8578991','8578993','8578994','8578883','8578889','8578893','8578894','8578895','8578977','8578981','8578908','8578909','8578910','8578912','8578914','8578916','18913876','18914529','8576349','8576350','8576353','8576354','8576160','8576161','8563510','8563514','8563518','8563522','8563188','8563527','8563190','8563532','8563373','8563194','8563533','8563197','8563205','8563211','8563215','8563222','8563225','8563227','8563074','8563233','8563076','8563079','8563083','8563084','8563085','8556323','8555683','8555536','8555545','8555548','8555551','8555714','8555732','8555757','8555768','8556318','8556321','8556315','8551349','8551350','8551352','8551360','8551365','8551366','8551392','8551396','8550826','8550828','8550829','8550682','8550688','8550692','8550696','8550550','8551585','8551592','8551593','8551598','8551607','8551522','8551530','8550884','8550885','8550803','8550806','8550899','8550812','8550821','8551780','8551559','8551578','8551580','8551582','8550906','8550907','8550840','8550936','8551195','8551149','8551177','8551179','8551185','8551770','8551773','8551774','8551779','8549147','8549154','8549068','8546421','8546425','8546356','8546359','8546362','8546363','8546374','8546375','8546876','8546877','8546953','8547017','8547020','8546891','8546893','8546894','8546895','8546898','8546900','8546901','8546902','8546905','8546195','8546282','8546283','8546284','8546198','8546285','8546199','8546200','8546288','8546291','8546075','8546531','8546537','8546411','8546415','8546908','8546910','8546912','8546916','8546919','8546920','8546924','8546306','8546317','8546319','8546320','8546321','8546326','8546265','8546348','8546270','8546586','8546587','8550068','8550077','8550085','8550087','8550088','8550092','8550010','8550011','8550012','8545356','8545358','8545359','8545361','8545214','8545217','8545370','8545374','8545149','8545150','8545152','8545155','8545158','8545161','8545162',
'8545167','8546061','8546062','8546064','8546066','8546071','8545883','8545885','8545168','8545110','8545113','8545115','8545122','8545123','8545124','8545496','8545497','8545500','8545501','8545267','8545270','8545889','8545777','8545510','8545633','8545511','8545554','8545643','8545646','8545648','8545130','8545131','8545139','8545143','8545664','8545666','8545788','8548017','8548023','8547903','8547905','8548077','8547907','8547908','8547909','8547910','8547911','8547914','8548343','8548351','8548352','8548262','8548361','8548269','8548364','8548288','8550544','8550396','8550399','8550185','8550186','8550406','8550187','8550189','8550415','8550553','8550557','8548518','8548522','8548524','8548449','8548457','8547794','8547936','8547801','8547979','8547890','8548373','8548377','8548387','8548397','8548399','8547762','8547641','8544542','8544548','8544480','8544481','8544482','8544487','8544488','8544489','8544490','8549550','8549466','8549472','8549476','8549478','8549479','8549481','8549485','8549574','8549488','8549491','8549494','8549495','8548104','8548853','8544492','8544473','8544474','8544479','8545080','8544965','8544864','8544866','8541287','8544278','8539545','8539346','8539571','8539575','8539115','8539403','8539426','8539169','8538966','8539188','8538991','8538994','8540514','8540071','8540123','8540129','8540600','8540405','8540214','8540656','8540282','8544299','8544305','8544311','8541381','8544316','8541159','8541166','8541427','8541215','8538655','8538910','8538913','8538671','8538454','8538459','8538930','8538936','8538485','8538707','8538496','8538503','8538544','8538304','8538267','8537862','8537870','8538123','8536860','8536917','8536923','8539456','8539460','8539679','8539476','8539709','8539281','8539502','8539723','8539518','8539763','8540002','8540199','8540006','8540036','8540054','8539851','8539632','8536688','8536635','8549943','8550024','8550035','8549893','8549895','8549896','8549897','8549369','8549370','8549373','8549374','8549377','8549381','8549383','8549385','8549312','8549391','8549182','8549084','8549097','8549098','8549100','8549102','8549106','8549817','8549819','8549822','8549826','8549827','8549828','8549829','8549830','8549831','8549833','8549836','8549985','8549911','8549914','8549997','8550000','8550002','8549837','8549838','8549839','8549843','8549931','8549933','8549934','8549936','8549938','8549939','8549853','8549940','8549942','8549858','8550418','8550419','8550420','8550421','8550425','8550426','8550527','8550270','8550271','8550272','8550273','8550274','8550275','8550276','8548904','8548914','8548920','8549008','8549009','8549010','8548831','8548833','8549014','8549017','8548842','8550281','8549500','8549503','8549507','8549509','8549518','8549521','8549522','8549523','8549524','8549525','8549430','8549440','8547366','8547368','8547369','8547379','8547241','8547244','8547247','8547251','8547156','8547253','8547167','8547171','8547176','8547179','8547181','8547653','8547581','8547046','8547057','8547073','8546934','8547606','8547612','8547617','8547419','8547556','8547632','8547560','8547561','8547568','8547571','8549899','18916113','18915689','36190289','34035265','34001911','34624146','34003957','34003992','34023908','34023917','33993904','34027500','34027705','34030317','34031811','34032380','34032604','36289644','36289709','36290482','33995510','33998003','33999332','34000876','34000960','31370036','31370348','31370651','28767978','29079292','28766102','27914353','27624107','27957185','27630740','27556414','27556727','29129955','29192207','27557417','27503662','27584961','27586579','27436747','27587732','27611949','27459580','27420369','27420552','27420789','27420865','27421601','27422095','8540903','8540906','8540930','8572339','8572334','8572342','8536741','8536977','8536752','8536766','8536991','8536497','8536502','8536822','8536832','8536566','8536843','8536596','8572344','8540178','8541221','8541452','8541457','8540979','8541263','8541000','8541010','8540824','8541050','8540687','8540700','29195325','27877499','28764741','8537941','8537754','8537563','8537809','8538582','8538349','8538363','8538369','8538389','8538184','8538213','8538435','8539221','8539251','8539048','8538849','8539099','8537653','8537198','8537200','8537443','8537453','8537237','8537035','8539532','8539536','8539322','8537649','27426885');
return $array;
    }



}