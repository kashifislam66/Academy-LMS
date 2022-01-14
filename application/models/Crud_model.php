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
'8549928','8549937','8550511','8550513','8550448','8550455','8550526','8550462','8550463','8550538','8550466','8548903','8548917','8548918','8549004','8548819','8548821','8548926','8548712','8548714','8548625','8548807','8548716','8548814','8548722','8548934','8548936','8548942','8548948','8550542','8549402','8549407','8549408','8549413','8549415','8549416','8549421','8549422','8549281','8549283','8549284','8549286','8547440','8547441','8547442','8547445','8547446','8547447','8547451','8547317','8547324','8547260','8547334','8547263','8547232','8547233','8547309','8547238','8547147','8547152','8547093','8547095','8547098','8547100','8547101','8547102','8547103','8547180','8547110','8547111','8547112','8547113','8547714','8547715','8547717','8547718','8547724','8547728','8547492','8547494','8547592','8547501','8547122','8547197','8547198','8547200','8547127','8547201','8547128','8547129','8547132','8547137','8547138','8547141','8547070','8547080','8547083','8547086','8547266','8547338','8547274','8547347','8547351','8547279','8547280','8547281','8547210','8547357','8547286','8547217','8547289','8547219','8547605','8547516','8547517','8547432','8547438','8547292','8547296','8547226','8547299','8547120','8562619','36190298','36190314','36197014','36197020','36197023','34001936','34049898','34632577','34017316','34633162','33985967','34022207','34023072','34024455','34026506','34029967','34031158','34031480','33995860','33999531','31334223','31341383','27622546','27623607','27631152','27555533','27518521','27555293','27555313','27420245','27421771','27422201','8540702','8540911','8540940','8536732','8536763','8536771','8536534','8541020','8540837','8540684','28765645','8537719','8537799','8538371','8538378','8538410','8537986','8544988','8544881','8544791','8544885','8544891','8539010','8537447','8537266','8539521','8554486','18919781','8581803','8579496','8573216','8559848','18919412','8550868','8577714','8550869','8577717','8546036','8546038','8546045','8546046','8546049','8575873','8575874','8575882','8575892','8557326','8576042','8557329','8557331','8551923','8551925','8551929','8551931','8551471','8551937','8551483','8551938','8551484','8551487','8551488','8546725','8551497','8551500','8546055','8547863','8547865','8545385','8545387','8550854','8550861','8580178','8580182','8579807','8579809','8580190','8579817','8579825','8579826','8579827','8579829','8581726','8579832','8579833','8579835','8579837','8577719','8577720','8577723','8577726','21348673','8580349','8580354','8580348','8552857','8552858','18918360','8581837','8568385','18919784','8546958','8554322','8546962','8551896','8551899','8551901','8551907','8551909','8551912','8551916','8551917','8551920','8560794','8547205','8557667','8557684','8557685','8557300','8557301','8557692','8557311','8576029','8557699','8557313','8557702','8557704','8557320','8557321','8557709','8557324','8576394','8580415','8576331','8580417','8580424','8580440','18911697','8570983','8570403','8575056','8575049','8568392','24659024','18918739','8546384','8545088','8546703','18915419','18915944','8583987','8578971','8578280','8578281','8578282','8578284','8578285','8578286','8578287','8578288','8579072','8578290','8578291','8578292','8578296','8579084','8578303','8578304','8579092','8560536','8560554','8560556','8560582','8584520','8579098','8579106','8579108','8578274','8578275','8578278','8557341','8557349','8575006','8564406','8536509','8553414','8553418','8553431','8553435','8553439','8553442','8549907','8544626','8570112','8570105','8570122','8570102','8570098','8564302','8544546','8555304','8569312','8547692','34027940','34031470','3594323','36204530','36215691','36213621','36215866','36214808','36214822','36196621','36188940','11768723','10524722','14767058','14767061','14767086','13939304','13939204','13939307','13939309','13939316','21931010','13776383','14134880','13013363','13013375','34435373','28436915','36138779','36129353','13701258','13695970','13396100','24792084','36257448','13002569','36217693','36130344','3600517','3589803','3594252','3594425','3617445','3609742','3619076','3612298','3628916','12963604','5499384','5499327','5497367','5492913','5493948','36249681','36226571','36196016','30412360','30971151','36123933','33044893','32799268','36124888','36124935','36123649','36125237','36123678','28441191','36127984','27960045','27969206','27961652','27961872','27964197','27968434','36322922','36202681','36160452','36124841','36124846','36123635','36129083','27186394','10913517','10501653','10501623','10501614','10498172','11610026','11610310','11642525','11642602','11640544','11636874','10501641','10498216','9275735','9281173','9280854','36126198','27960924','36283980','27963910','33512691','36122640','36124927','29175917','11599242','11599245','11599254','11599276','11599280','11599293','11599204','11599209','11599225','11599231','36126246','36126237','16510344','16392647','16392674','16392678','16510402','16392758','16392819','16392833','16392912','16392916','16392951','16392240','16392249','16392254','16371034','16392289','16371055','16392291','16392347','16371087','16371114','16392421','16371122','16370810','16371131','16511867','16511903','16392472','16371187','16370897','16371211','16371222','16370932','16370952','16370956','16370187','16370199','16370500','16369878','16370514','16370235','16370257','16511964','16511636','16511654','16511677','16511701','16511708','16511719','16511228','16370557','16369948','16370566','16370293','16370316','16369997','16370325','16369999','16370006','16370337','16370017','16370349','16370041','16370071','16370086','16370410','16370713','16370128','16511399','16511025','16511403','16511069','16511075','16510775','16510797','16510516','16510846','16510525','16510534','16510877','16510900','16510621','16511789','16511330','16511347','16511008','36071307','36130583','36104640','16370146','16370454','16369596','16369626','16326261','16369340','16326271','16369347','16369669','16369673','16369378','16369396','16369405','16369421','16369426','16369442','16369746','16369747','16369453','16369469','16369500','16369827','16369513','16369843','16369869','15738171','15738092','15738178','15738180','15737999','15738181','15738000','15738002','15738184','15738117','15738119','16315624','15738014','15738129','15738016','16315784','15738028','16316272','15738140','15738029','15738030','15738070','15738073','15738075','15738081','15738083','15737361','15737365','15737480','15737482','15737373','15737487','15737496','15737498','15737282','15737502','15737284','15737287','15737509','15737379','15737380','15737300','15737528','15737532','15737389','15737309','15737398','15737535','15737312','15737314','15737766','15737407','15737324','15737409','15737411','15737415','15737327','15737423','15737425','15737428','15737437','15737440','15737353','15737354','15737167','15737453','15737455','15737180','15593199','15593623','15593419','15593428','15593648','15593433','15593457','15593463','15593470','15593271','15593275','15593287','15593289','15593294','15593311','15593316','12477412','12480613','12480897','12446710','12446752','12600113','12572808','12033193','12028571','12013443','12013464','12444800','12444837','12444863','12030681','12474369','16511488','15736312','15736141','15736179','15736066','15736205','15736075','15736078','15736083','12801685','12802148','12802180','12841681','12841721','12841907','12609417','12609624','12609748','12621933','12622317','12622425','12599035','12599779','12689414','12712121','12622467','12623166','12601108','12601329','12655358','12655498','12655540','12655587','12578845','15594218','15593822','15593838','15593672','15593882','15593887','15593685','15594292','15593915','15593928','15594320','15736525','15736676','15736530','15736534','15736540','15736699','15736544','15736419','15736548','15736422','15736572','15736446','15736261','15736579','15736582','15736458','15736464','15736280','15736470','15736600','15736300','16511208','16510643','16510675','16510683','16510996','16510328','12803803','12803854','12841600','12841614','12841625','12841636','12841639','12841643','12841649','12777644','12841652','12801127','12841670','12801516','12801546','14740313','14740701','14740332','14740088','14740092','14740350','14740100','14740357','14740110','14740117','14740118','14740119','14740124','14740126','14603551','14603554','14740142','14603560','14603565','14603570','14740152','14740154','14603584','14740164','14603585','14740165','14740005','14740007','14740012','14739920','14578439','14739940','14740167','14739949','14739951','14740176','14740177','14740180','14739977','14740696','14740697','14740312','14819036','14819043','14819046','14819050','11901003','11906518','11906658',
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