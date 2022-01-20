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
        // echo "<pre>"; $where['manage_id']; exit;
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
        if($category_column == "") {
            $category_column = "category_id";
        }
        $query = $this->db->select($category_column.", count(*) AS course_number",false)
            ->from ("course")
            ->group_by($category_column)
            //->order_by("course_number","DESC")
            ->where('status', 'active')
            ->where($category_column.' !=', '0')
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

    public function handleWishManagerList($course_id)
    {
        $wishlists = array();
        $user_details = $this->user_model->get_manager($this->session->userdata('user_id'))->row_array();
        // echo $user_details; exit;
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

    public function is_added_to_manager_wishlist($course_id = "")
    {
        if ($this->session->userdata('manager_login') == 1) {
            $wishlists = array();
            $user_details = $this->user_model->get_manager($this->session->userdata('user_id'))->row_array();
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

    public function getWishListsOfManager($user_id = "")
    { 
        if ($user_id == "") {
            $user_id = $this->session->userdata('user_id');
        }
        $user_details = $this->user_model->get_manager($user_id)->row_array();
        //  echo "<pre>"; print_r($user_details); exit;
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
            // echo $data['user_id'].'---'.$data['course_id']; exit;
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
            $enrol_last_date = strtotime(date('D, d-M-Y'));
            $data['enrol_last_date'] = strtotime("+1 month", $enrol_last_date);
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
    { //echo "<pre>"; print_r($_POST); exit;
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

    public function get_courses_by_manager_wishlists()
    {
        $wishlists = $this->getWishListsOfManager();
       // print_r($wishlists); exit;
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

    public function get_courses_of_wishlists_by_manager_search_string($search_string)
    {
        $wishlists = $this->getWishListsOfManager();
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
        // send email to super Admin from user//
        $this->email_model->send_email_user_first_message_to_super_user($sender);

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
        // $insert_id = $this->db->insert_id();
       // echo $insert_id; exit;
        $this->email_model->send_email_user_message_to_super_user($sender, $message);
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
        
        $course_ids = array();
        if ($selected_category_id != "all") {
            $category_details = $this->get_category_details_by_id($selected_category_id)->row_array();

            if ($category_details['parent'] > 0) {
             
            $search="FIND_IN_SET ('$selected_category_id',sub_category_id)";
             $this->db->where($search);
               
            } else {
                $this->db->where('category_id', $selected_category_id);
            }
        }


        if ($selected_level != "all") {
            $this->db->where('level', $selected_level);
        }

        if ($selected_language != "all") {
            $this->db->where('language', $selected_language);
        }
        $this->db->select('id');
        $this->db->where('status', 'active');
        $courses = $this->db->get('course')->result();

        foreach ($courses as $course) {
            if ($selected_rating != "all") {
                $total_rating =  $this->get_ratings('course', $course->id, true)->row()->rating;
                $number_of_ratings = $this->get_ratings('course', $course->id)->num_rows();
                if ($number_of_ratings > 0) {
                    $average_ceil_rating = ceil($total_rating / $number_of_ratings);
                    if ($average_ceil_rating == $selected_rating) {
                        array_push($course_ids, $course->id);
                    }
                }
            } else {
                array_push($course_ids, $course->id);
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
'8574960','8575021','8574961','8575022','8575024','8575026','8575027','8575029','8574899','8575033','8574972','8574973','8575037','8574974','8575038','8574975','8575039','8574977','8574979','8574920','8575047','8574933','8574934','8574936','8574994','8574997','8574942','8574832','8574897','8574833','8574834','8574885','8574894','8553288','8553289','8552933','8552973','8553131','8553134','8553136','8553137','8553266','8553139','8553269','8553270','8553146','8553274','8552978','8552865','8552986','8552995','8552878','8553002','8552883','8553006','8552889','8553023','8553026','8554255','8554256','8554131','8554258','8554132','8554133','8554263','8554137','8554264','8554269','8554143','8554148','8554152','8554160','8554161','8554164','8554165','8554035','8554038','8553932','8553933','8554185','8554189','8553890','8553892','8553896','8553912','8553929','8554191','8554192','8554193','8562614','8562629','8562633','8562634','8562635','8552529','8552532','8552536','8552538','8552543','8552544','8552546','8552547','8552551','8552554','8552556','8552558','8552560','8552248','8552258','8552261','8552262','8552263','8552264','8552267','8552271','8552184','8551962','8551964','8552189','8552190','8552194','8552195','8552198','8552330','8552207','8552209','8552337','8552340','8552216','8552221','8552223','8552373','8552378','8552387','8552389','8552275','8552276','8552391','8552279','8552394','8552280','8552282','8552283','8552396','8552563','8552355','8552567','8552569','8552357','8552572','8552576','8552365','8552580','8552366','8552372','8552591','8552597','8552598','8552582','8552583','8552734','8556814','8556695','8556819','8556698','8556826','8556834','8556839','8556603','8556850','8556606','8556852','8556607','8556857','8556613','8556585','8556333','8556334','8556335','8556337','8556338','8556593','8556342','8556350','8556353','8556256','8556627','8556628','8556629','8556755','8556634','8556637','8556766','8556644','8556647','8556649','8556652','8556654','8557608','8557492','8557610','8557495','8557497','8557499','8557501','8557386','8557389','8557390','8557393','8557513','8557521','8557407','8557415','8557417','8557419','8557437','8557438','8557445','8557447','8557448','8557452','8557453','8557458','8557219','8557220','8557221','8557222','8557357','8557360','8557362','8557162','8557176','8557178','8557179','8557182','8556257','8556258','8556259','8556262','8556396','8556397','8556276','8556277','8556399','8556278','8556157','8556158','8556305','8556177','8556307','8556178','8556308','8556190','8556313','8564088','8564098','8564099','8564104','8563948','8563955','8563969','8563980','8563985','8563993','8554906','8554909','8554910','8554920','8554927','8554692','8555090','8555097','8555200','8555205','8554972','8554974','8554975','8554979','8554986','8554989','8554991','8554993','8554881','8554999','8554897','8554898','8554901','8555578','8555579','8555581','8555584','8555585','8555369','8555273','8555393','8555274','8555394','8555280','8555283','8555398','8555284','8555285','8555288','8555403','8555404','8555405','8554314','8554440','8554319',
'8554199','8554320','8554444','8554446','8554220','8554225','8554226','8554233','8554235','8554238','8554241','8554247','8554252','8554254','8554668','8554676','8554682','8554526','8554528','8554529','8555169','8555170','8555171','8555175','8555081','8555082','8555084','8555085','8555186','8555187','8555088','8554540','8554542','8554547','8554554','8554593','8554621','8555295','8555298','8555411','8555417','8555423','8571092','8572943','8573199','8573207','8573214','8573218','8572736','8572849','8573275','8573279','8573280','8573284','8573287','8573294','8572919','8572924','8572925','8572926','8572932','8572933','8572937','8572045','8571930','8572048','8571948','8571809','8571811','8571812','8571817','8571818','8571833','8570855','8570857','8570862','8570864','8570867','8570871','8570874','8570885','8570886','8571068','8570889','8571072','8571074','8570686','8572000','8571882','8571883','8572014','8571885','8571889','8572018','8572021','8572023','8572025','8572028','8571911','8572032','8572038','8572041','8571970','8571976','8571977','8571978','8571980','8571851','8571858','8571864','8571995','8571870','8571997','8562654','8562657','8562660','8562663','8562665','8562668','8562675','8562560','8562566','8560801','8560805','8560832','8560834','8560836','8560590','8561377','8561382','8561383','8561384','8561385','8561117','8561122','8561124','8561128','8561130','8561143','8561147','8561149','8561153','8560592','8560595','8560597','8560598','8560600','8560603','8560604','8560606','8560610','8560611','8560612','8560617','8560461','8560624','8560462','8560626','8560627','8560468','8560630','8560472','8560631','8560475','8560482','8560483','8560380','8560647','8560381','8560491','8560492','8560387','8560655','8560392','8560503','8560658','8560505','8560400','8560509','8560021','8560023','8560027','8560033','8560038','8560046','8560048','8560053','8559900','8560063','8559910','8560069','8560073','8560079','8560086','8559813','8559815','8559819','8560097','8560102','8560667','8560426','8560452','8560454','8560108','8560215','8560112','8560113','8560115','8560221','8560362','8560363','8560228','8560235','8560237','8560375','8559863','8559873','8559884','8559890','8559893','8559895','8559822','8560107','8559824','8563569','8563571','8563572','8563585','8563591','8563413','8563416','8563420','8563436','8563440','8557832','8557843','8557845','8557744','8557753','8557754','8557757','8557758','8558307','8558309','8558313','8558318','8558320','8558321','8558325','8558328','8558330','8558331','8558334','8558335','8558336','8558642','8558658','8558661','8558854','8558523','8558531','8558549','8558550','8558344','8558347','8558348','8558349','8558351','8557894','8558155','8558159','8557901','8558016','8558017','8558165','8558023','8558025','8557927','8557930','8557939','8557941','8557943','8557944','8557945','8557946','8557768','8557770','8557771','8557772','8557546','8557468','8557472','8557585','8557587','8557477','8557484','8557485','8557366','8557368','8557490','8557605','8558247','8558082','8558084','8558087','8558096','8558097','8558103','8558105','8557857','8558132','8557876','8557878','8558149','8558337','8558340','8558341','8558237','8558241','8561415','8561416','8561418','8561432','8561437','8561439','8561577','8561440','8561578','8561445','8561448','8561450','8561452','8561456','8561481','8561483','8562129','8562130','8562145','8562266','8562146','8562268','8562151','8562155','8562157','8562164','8562178','8562183','8562287','8562289','8562290','8562218','8562222','8562223','8562230','8562119','8561619','8561622','8561485','8561866','8561627','8561494','8561497','8561633','8561501','8561637','8561504','8561642','8561643','8561505','8561644','8561507','8561647','8561509','8561515','8561517','8561659','8561662','8561520','8561666','8561669','8561400','8561402','8561548','8561556','8561912','8561925','8561931','8561935','8561787','8561790','8561796','8561808','8561969','8561586','8561589','8561593','8561829','8561618','8562637','8562649','8562650','8562651','8561971','8561972','8561975','8561980','8561982','8561984','8561884','8561885','8561890','8562112','8562113','8561899','8562114','8561901','8561902','8561904','8561905','8561906','8562580','8564021','8564024','8564027','8563538','8563548','8563554','8563559','8563560','8563561','8563567','8559396','8559399','8559408','8559182','8559184','8559186','8559189','8559194','8559199','8559202','8559205','8559645','8559650','8559805','8559589','8558999','8558881','8559000','8558883','8559001','8558884','8559003','8558887','8559005','8558889','8559008','8558895','8558896','8558897','8558901','8558904','8558908','8558613','8558819','8558823','8558830','8559215','8559221','8559092','8559095','8559096','8559097','8559098','8559102','8559103','8559104','8559105','8559107','8559252','8559108','8559111','8559112','8559113','8559114','8559257','8559115','8559012','8559116','8559117','8559118','8559120','8559016','8559019','8559126','8559130','8559131','8559029','8559030','8559033','8559591','8559594','8559602','8559604','8559605','8559608','8559610','8559612','8559617','8559622','8559623','8559625','8559630','8559492','8559498','8559063','8559068','8559069','8558977','8559079','8559080','8558981','8558986','8558987','8558989','8559088','8558993','8558872','8558875','8558879','8549600','8581793','8581796','8581797','8581798','8581801','8583949','8583951','8582947','8582955','8582787','8582604','8582606','8582607','8582799','8582616','8582617','8582619','8582620','8582408','8580581','8580583','8581033','8581187','8581034','8581188','8581191','8581192','8581147','8581149','8581151','8581152','8581153','8581154','8581159','8581160','8581399','8581331','8581268','8581336','8581342','8581408','8581174','8581176','8581177','8581179','8581028','8581029','8580933','8580545','8580318','8580320','8580324','8580239','8580329','8580242','8580330','8580331','8580332','8580257','8580296','8580220','8580221','8580223','8580132','8580144','8580084','8577906','8577914','8577915','8577834','8577838','8577782','8577840','8577783','8577784','8577850','8577787','8577788','8577789','8577852','8577791','8577792','8577854','8577793','8577794','8577857','8577795','8577639','8577641','8577642','8577643','8577644','8577657','8577659','8577660','8577595','8577596','8577599','8577600','8577601','8577602','8577606','8577748','8577749','8577666','8577751','8577753','8577669','8577680','8577758','8577760','8577762','8577767','8577769','8577781','8577704','8577705','8577796','8577859','8577797','8577860','8577798','8577799','8577801','8577802','8577803','8577806','8577809','8577811','8577813','8577818','8577821','8577730','8577824','8577826','8577734','8577735','8577828','8577736','8577830','8577738','8577832','8577740','8577308','8577309','8577316','8577317','8577318','8577319','8577382','8577383','8577384','8577386','8577392','8577393','8577396','8577327','8577329','8577330','8577331','8577332','8577334','8577336','8577262','8577266','8577271','8577310','8577311','8577313','8577314','8581351','8581355','8581356','8581311','8581256','8581257','8581387','8581319','8581258','8581320','8577900','8578201','8578355','8578357','8578360','8578362','8578307','8578309','8578052','8578182','8578143','8578310','8578311','8578149','8578095','8578096','8578153','8578259','8578099','8578103','8578104','8578105','8578106','8578107','8578109','8578111','8578112','8578115','8578172','8578177','8578479','8578480','8578566','8578567','8578569','8578485','8578488','8578489','8578574','8578368','8578577','8578578','8578371','8578582','8578583','8578585','8578587','8578508','8578317','8578269','8578273','18910130','8580807','8580811','8580812','8580639','8580643','8579061','8578940','8578941','8578942','8579022','8579026','8579030','8579327','8579396','8579399','8579402','8579403','8579405','8579406','8578918','8578921','8578923','8578925','8578928','8578930','8578932','8578933','8578795','8578880','8578805','8578881','8578806','8578809','8578810','8579153','8579164','8579166','8579167','8579171','8579178','8579249','8579184','8579188','8579189','8579190','8579191','8579192','8579193','8579194','8579195','8579196','8579036','8579037','8579038','8579041','8579042','8579045','8579046','8579047','8579049','8579050','8579052','8579032','8579033','8578884','8578887','8578888','8578890','8578891','8578892','8578979','8578905','18911103','8563498','8563500','8563504','8563189','8563528','8563381','8563204','8563394','8563395','8563216','8563228','8563231','8563234','8563235','8556206','8556090','8556097','8555998','8555789','8555792','8555798','8555684','8555689','8555542','8555439','8555440','8555448','8555702','8555711','8555716','8555727','8555879','8555881','8555738','8555762','8555764','8555765','8555780','8555783','8555929','8555899','8555914','8555916','8555917','8555919','8555927','8556202','8556317','8551382','8551388','8551188','8551193','8550825','8550827','8550830','8550831','8550832','8550834','8550835','8551588','8551594','8551599','8551602','8551604','8551513','8551514','8551609','8551516','8551518','8551529','8551532','8550886','8550893','8550802','8550896','8550807','8550808','8550810','8550811','8550813','8550814','8550816','8550817','8550818','8550819','8550820','8550822','8550823','8550824','8550902','8550837','8550841','8550842','8551196','8551151','8551153','8551159','8551163','8551166','8551168','8551169','8551176','8551180','8551184','8550901','8551890','8549108','8549110','8549065','8549071','8546422','8546424','8546360','8546361','8546366','8546377','8546378','8546379','8546502','8546382','8546944','8546875','8546948','8546949','8546878','8546955','8547018','8547028','8547029','8547031','8547032','8547034','8547036','8546353','8546354','8546074','8546076','8546535','8546536','8546409','8546410','8546414','8546418','8546420','8546308','8546316','8546323','8546328','8546330','8546346','8546347','8546349','8546350','8546578','8546583','8546588','8550250','8550086','8550252','8550097','8550098','8550101','8550106',
'8550006','8550007','8550013','8545293','8545357','8545362','8545364','8545366','8545372','8545373','8545375','8545147','8545151','8545166','8546057','8546059','8546068','8545882','8545884','8545108','8545114','8545118','8545120','8545121','8545125','8545495','8545499','8545503','8545274','8545888','8545772','8545775','8545512','8545515','8545556','8545559','8545560','8545561','8545654','8545657','8545658','8545618','8545624','8545625','8545136','8545138','8545140','8545141','8545142','8545037','8545038','8545145','8545779','8545780','8545791','8548005','8548007','8548015','8548019','8548027','8548033','8548034','8548073','8547904','8548076','8548079','8548081','8548084','8548086','8548087','8547915','8547917','8547922','8547923','8547924','8548355','8548357','8548358','8548265','8548360','8548268','8548363','8548271','8548272','8548366','8548367','8548276','8548371','8548372','8550179','8550397','8550181','8550183','8550402','8550184','8550404','8550405','8550414','8550551','8550552','8550554','8550556','8548519','8548458','8547925','8547926','8547928','8547931','8547932','8547934','8547799','8547980','8547802','8547982','8547983','8547809','8547812','8547884','8547885','8547814','8547886','8547893','8548379','8548381','8548384','8548385','8547638','8547784','8547787','8544540','8544553','8549552','8549554','8549555','8549470','8549556','8549471','8549473','8549475','8549561','8549480','8549564','8549567','8549568','8549482','8549484','8549486','8549496','8548089','8548092','8548094','8548095','8548106','8548108','8548109','8547987','8547988','8547989','8547994','8547997','8547998','8547999','8548001','8548849','8548850','8548852','8548857','8548858','8541480','8544857','8544860','8544865','8544868','8541291','8544276','8544277','8544280','8544282','8539330','8539332','8539336','8539582','8539113','8539383','8539117','8539401','8539141','8539421','8539166','8538961','8538963','8538979','8539204','8539210','8540310','8540316','8540509','8540069','8540327','8540077','8540103','8540116','8540139','8539915','8539938','8540162','8540172','8540594','8540598','8540606','8540225','8540231','8540248','8540661','8540275','8540288','8540477','8544283','8541325','8541343','8544312','8541378','8541445','8538445','8538447','8538918','8538680','8538926','8538474','8538703','8538725','8538498','8538745','8538524','8538290','8538295','8538771','8538546','8538316','8538012','8538014','8538023','8538038','8538285','8537832','8538072','8538076','8537853','8537855','8537859','8537865','8538097','8538102','8537886','8537283','8537077','8537094','8537104','8537106','8537337','8536862','8536866','8537127','8536870','8537138','8536875','8539882','8539452','8539676','8539478','8539695','8539507','8539730','8539297','8540185','8540203','8539777','8540031','8540040','8539800','8539809','8539814','8539605','8539846','8539619','8536685','8536630','8536658','8550032','8549889','8549890','8549898','8549901','8549371','8549300','8549375','8549301','8549376','8549302','8549378','8549380','8549308','8549384','8549386','8549390','8549317','8549320','8549249','8549185','8549186','8549191','8549202','8549204','8549205','8549096','8549101','8549019','8549020','8549022','8549107','8549867','8549875','8549876','8549878','8549881','8549885','8549835','8549905','8549986','8549987','8549991','8549992','8549909','8549995','8549915','8549998','8549999','8550003','8549841','8549842','8549844','8549845','8549846','8549847','8549850','8549852','8549854','8549856','8549857','8549859','8549863','8550423','8550429','8550430','8550431','8549080','8548910','8548913','8548919','8549007','8548829','8548720','8548836','8548838','8549018','8550279','8550280','8550282','8550283','8550284','8549501','8549423','8549426','8549427','8549428','8549444','8549353','8549357','8549360','8547373','8547374','8547375','8547376','8547377','8547378','8547380','8547381','8547240','8547242','8547243','8547245','8547246','8547153','8547154','8547248','8547252','8547157','8547254','8547255','8547161','8547168','8547169','8547040','8547646','8547575','8547576','8547577','8547586','8547048','8547049','8547055','8547056','8547059','8547062','8547074','8546926','8547076','8547077','8546931','8547082','8546932','8546936','8546939','8546940','8547608','8547610','8547611','8547615','8547618','8547620','8547416','8547420','8547423','8547633','8547558','8547634','8547559','8547562','8547563','8547565','8547567','8547573','8547574','8547042','8547043','8574171','8574169','36190274','36190281','34033900','34037037','34017936','34019374','34019869','33984307','33987757','34026199','34031352','36289703','36290463','36311626','34027245','33995790','28766619','27957136','27629009','27556495','27557640','27557828','27422417','27582615','27583031','27585739','27586217','27587692','27611610','27446049','27534637','27446140','27535787','27457903','27554461','27555127','27462055','27419297','27419378','27420083','27420453','27420498','27422022','8540890','8541101','8540712','8572330','8572329','8572337','8536717','8536953','8536971','8536981','8536996','8537002','8536784','8536812','8536550','8536820','8536850','8541228','8541245','8541257','8541036','8541038','8540669','8541066','8540681','29199987','29201572','28764333','28764580','28764905','8537722','8537749','8537506','8537548','8537788','8537551','8537570','8537573','8537805','8537807','8537815','8537347','8537376','8538334','8538597','8538127','8538131','8538376','8538172','8538397','8538400','8538009','8538248','8544870','8539006','8539027','8539031','8538797','8539269','8539054','8538835','8539076','8539082','8540947','8540955','8540523','8540807','8540560','8538869','8538627','8538630','8538900','8537414','8537423','8537666','8537439','8537214','8537216','8537224','8537459','8537462','8537472','8537487','8537264','8537039','8539324','8537625','8537397','8537403','8537644','8538328','8538330','8570590','8549916','8574661','8574662','8574663','8574664','8574667','8574669','8546711','8546717','8546719','8554113','8554114','8545384','8545391','8545399','8545401','8581714','8581721','8581724','8581727','8581733','8581737','8560209','8580336','8580334','8580335','8549578','8552843','8552849','18918398','18917643','36268199','36268205','36268215','36268214','8554125','8554323','8548110','8548112','8554330','8550937','8554334','8550938','8554337','8545679','8536793','8536858','8545684','8557399','8574689','8574701','8538509','8583114','8583120','8583131','8583133','8550412','34048789','18914872','8540568','8538006','8539853','34050573','8545687','8545689','8545691','8545713','8546386','8546389','8539875','8546393','8545092','8546395','8546396','8545093','8545095','8545098','8545218','8546404','8545222','8546406','8545225','8545226','8545227','8546708','8545230','8545231','18916024','8576909','8538408','8576913','8576914','8576917','8576919','8576923','8576925','8576927','8576930','8576931','8574684','8564426','8541204','8562345','8562349','8562352','8564410','8544949','8544953','8544954','8544957','8544960','8544961','8544962','8544968','8544972','8544974','8544978','8544981','8546729','8546733','8554123','9749677','9751599','9749681','9751595','8574451','36234945','36304131','36304158','36200654','36214938','36214940','36215684','36215697','36215777','36214662','36214664','36215802','36214716','36205949','36189310','10524561','10524290','36121569','36119110','32370903','32372252','33190818','36180006','36162413','28099202','36131273','36131771','36121544','36215820','35535679','36214812','36215811','36143388','3591520','3631190','3590421','3623071','3604076','3605275','3613631','3634574','8688414','22810798','36266324','36266358','36266367','36249675','36249678','36249680','36249688','36249710','36249713','36249715','36249663','36113043','36114300','36127992','36123935','36123666','36123926','36117902','36117939','36102911','35868600','36102923','36126183','36126200','36115791','36125242','27962520','36321921','36322004','36249731','36249734','36249737','36249738','36249742','36249750','36128114','36129073','36129089','36126251','36126254','36129082','11535407','11609270','11608800','11608986','9280406','32669886','36124936','36124857','16510349','16510359','16511876','16511891','16511928','16511954','16511652','16511665','16511670','16511474','16370577','16370641','16510760','16511078','16511083','16510875','16511157','16511256','16511766','16511777','16511530','16511785','16511536','16511554','16511586','16511590','16511594','16511368','36131350','36100740','36101702','36104946','16326197','16326208','16326335','16326350','16326357','16326479','16326495','16352450','16291240','16326111','16326120','16326175','15204419','15204299','12477633','12472621','12474547','12474800','12475301','12475557','15593846','15593698','16510930','16511214','16510712','16510252','16510747','16510342','14739956','14582002','14492175','14488121','14488123','14488138','14488157','36249744','11128961','36150104','36150109','36152108','36152407','36109086','36154241','36118379','36219601','36223142','36228145','36229840','36232236','36234484','36156455','36236686','36158269','36236978','36244309','36177340','36178182','36179737','36182706','36214982','16512234','16512247','16512316','16512326','16511990','16512012','16512013','16512390','16512043','16512055','16512434','16512078','16512082','16512098','36216747','36139557','36145227','36145394','36146553','34263316','34391647','15593779','15594008','15737201','15736827','15203913','15204135','15204143','15203955','15203957','15203968','15203984','15203994','15203999','15204020','15204025','15204248','15204251','15204411','15204263','15204048','15204266','15204051','15204053','15204267','15204268','15204418','15204273','15204276','15204277','15204086','15204088','15204093','15204106','15204114','15204120','15204129','15204131','14740892','15204307','15204325','15204337','15204153','15517034','15204155','15204156','15204166','15204341','15204351','15204197','15204215','15204217','15204220','15204226','15204380','15204229',
'15204382','15594349','36123939','36316888','36302099','36302168','36302171','36283926','36300247','36300269','36300279','36300280','36300287','36296281','24800031','36118835','36118840','36118841','36118844','29975019','29429868','29429389','29429402','29429418','29428625','29429483','29428637','29428654','29429560','29429592','29428747','29428768','29428783','29428793','29428849','29429739','29429771','29428912','29428971','29429821','29429931','29430920','29429240','29429246','29429303','29429331','29972708','29972871','29429170','29428254','29428312','29428322','29428501','29428997','29429846','29429024','29429858','14391959','14339675','33729833','33729842','33729847','33729969','33730041','33730048','33730061','33730080','33729679','33729796','32691651','32195031','36118858','36118859','36118860','27935678','34500936','34500947','34501149','34547335','34549099','34558000','34558349','34563616','31484673','22541427','22542858','22546612','22593174','14329280','14339705','26806927','26451695','26807127','17672618','17672671','14347050','14347823','34419367','34422379','34563626','17671406','17671605','13269787','13267342','13267404','13267487','13267528','13267581','13267625','13267818','13268822','13269039','13269341','13269400','13269594','13241275','13269768','13241832','36118822','36118826','36118832','27624857','27625042','17670042','17664948','24799814','24799189','22310655','22333898','22183522','14346974','14329302','14329294','8356674','36220215','10383150','10859305','29977050','25210383','25210472','25210505','25210513','25210526','23281264','25210571','23281282','25210593','23281313','23280517','23280560','23280682','36125493','36125517','36125530','36125536','36125538','36125540','23280973','23281056','23281086','23281159','23279937','23279951','23279981','23281183','23280011','23280025','13239414','13239808','13241315','13241375','13241504','13267185','13268069','13268300','13269687','13240148','23280319','23280335','23280369','23280382','23280467','36217405','36217425','36217432','36217436','36217438','36217444','36217445','36217446','36217447','36217449','5267486','36118820','32687527','36191092','22246462','22541828','22775965','26982049','23280823','25210128','23280877','23280892','23280902','23280914','23280957','25210289','36173947','36176006','36117189','36104334','36108114','36108873','36108880','35845854','36117118','35851814','35852006','35904104','35905587','35905961','36282247','36282249','36262583','36262585','36262588','36262595','36262612','36262628','36262633','36262638','36262641','36262654','36262686','36262688','36271676','36273169','36273174','36282327','36282329','36273218','36273248','36273254','36273257','36273353','36273365','36282244','36171532','36173903','36173912','36273191','36273205','36125518','36216270','36220377','36220388','36218781','36218833','36218855','36218867','36221451','36224378','36224402','36224492','36224497','36225636','36225697','36225703','36217930','36228126','36228183','36228188','36229525','36212308','36216092','36216133','36206063','36206090','36204971','36207695','36218984','36207690','36207693','36325044','36325184','36325211','36325219','36346210','36347128','36347195','36342904','36268202','36271979','36264146','36266915','36278457','36278504','36278510','36283639','36280949','36269167','36269179','36270698','36270701','36277148','36277155','36277211','36271999','36272004','36272016','36272103','36310431','36310437','36293006','36280765','36306806','36290783','36290828','36289520','36280790','36283520','36303340','36301046','36266945','36266964','36265474','36265908','36261771','36261773','36261578','36261593','36261604','36261732','36261741','36261742','36261758','36261764','36261770','36249795','36249796','36257775','36257816','36257828','36257830','36249840','36249770','36250188','36250205','36249782','36262647','36256113','36263727','36263756','36260003','36260022','36235350','36235353','36262562','36235260','36235291','36229532','36230866','36245505','36249084','36249802','36249808','36249809','36242607','36249785','36249786','36249792','36232225','36232240','36232312','36232328','36233883','36182166','36182174','36182210','36225786','36225795','36225797','36221533','36225865','36221539','36225886','36225902','36204716','36206060','36204957','36199702','36199745','36199751','36199754','36199809','36202662','36202666','36202673','36197050','36197053','36197057','36198268','36198498','36204983','36204988','36204998','36202688','36205000','36202703','36205007','36202738','36202753','36204710','36198518','36198496','36218894','36254968','36106368','36106369','34429933','29718613','13015319','13692574','13692571','15032277','15032245','23545002','14903524','14892965','14892972','14892935','14901880','14901883','14901887','14901873','14901890','25361169','14901863','13718889','13718357','12688365','12688385','12688418','12688433','12710878','13015356','13015369','13015376','13015289','13015303','13015304','36123544','36123564','36123569','36138632','36137733','36137735','36138696','36138533','36138537','36138559','36138561','36141786','36138571','36138579','36138591','36177866','36177957','36177961','36177964','36177969','36179029','36179059','36179136','14652923','14652916','14505147','14240394','14652950','14652943','14652947','14652942','14712349','14652965','14652966','14652957','14602814','14502659','14240329','36262368','36262387','14238889','14238899','14228809','13689688','13689616','13689653','13689671','13689679','13530575','13530577','13530582','13530599','13530602','13530613','13530621','14228819','14228820','14228826','13530630','13530633','13530635','13530736','13530737','13530760','14466620','14466628','14466605','13320078','13320088','13320130','13320132','13320147','13320154','14738966','14738976','14738977','15068620','15583261','29717389','12625678','12625681','12625691','12716293','12329531','12329532','12329539','12329547','12479889','12622778','12622780','12622794','12421747','12604646','12604661','12329480','12329481','12329482','36270663','36177938','36225888','13889185','36270627','13891347','13891361','13891428','13887250','13887256','13887268','13887296','13887298','13887786','36238400','36241258','36241379','36250020','36241368','13888862','26274287','26263649','26274390','26274461','26263779','26263837','26268838','26274731','26264120','26275010','26266015','26269606','26269666','26275221','26275383','26266263','26275903','26275986','26266853','26276085','26273452','26233998','26234320','26267388','26267518','26234022','26234364','26267743','26227138','26234079','26227189','26286694','26293059','26289960','26286744','26290265','26293417','26290333','26293470','26293491','26290378','26290450','26293649','26290474','26290517','26290567','26293915','26290627','26290908','26290959','26287703','26291163','26291203','26287936','26294595','26288100','26291499','26227209','26262709','26227224','26227228','26234163','26227299','26263179','26263422','26263443','26263480','26233928','26233945','26233959','26233970','26226825','26226832','26226867','26226967','26226985','26289179','26284858','26280035','26280333','26276510','26285137','26285201','26285459','26285522','26285545','26291645','26288434','26291893','26292003','26292084','26288823','26288890','24752849','13891857','13889932','13889970','13889984','13889766','13889779','13889781','13891455','13891471','13890559','13892410','13891644','13891663','13891699','13891701','13891719','13892599','13890627','13890445','13887723','13887725','13887359','13887731','26277974','26285997','26286074','26286100','26282021','26278332','26286189','26278377','26286311','26278586','26278644','26278926','26279353','26273891','13888447','13888237','13892276','26338268','26335228','26335294','26335352','26335456','26338530','26335706','26333367','26335739','26335770','26339009','26335994','26333779','26333809','26333856','26334055','26334089','26339634','26334135','26334158','26334316','26334517','26334669','26334766','26334773','26335040','26322604','26325286','26331362','26331388','26322839','26297290','26322209','26297461','26297642','26322379','26297674','26322420','26297787','26294910','26295144','26298509','26300145','26295343','26298550','26295563','26300345','26295602','26295655','26331806','26331838','26323386','26301935','26321686','26324199','26324483','26322114','26296099','26300775','26301040','26296578','26296606','26292216','26292239','26301277','26292466','26296879','26296917','26289348','26289399','26289468','26289633','26292771','26297181','26289653','26292799','26289797','26292977','26341186','26345584','26344043','26344900','36215019','30351869','30352193','30352469','30352683','30353092','30354703','30354961','30755700','29186189','29241578','30807784','30952149','30952433','30952550','31075695','29936823','31075924','29938389','31263358','29942905','29944498','29945161','29945384','31340710','36221629','36224308','36224309','36224323','36224334','36224345','36224359','36224372','36179158','36179238','36230893','36179243','36230913','36230915','36182117','36230925','36230968','36190328','36190346','36199773','36260053','36191396','29947988','30000338','30008412','30008792','30342770','30343144','30343792','36260069','36199738','36216152','36260048','14244939','14245136','36264271','36232229','36269184','36202705','36309243','6396924','36190360','36309024','36309042','36309094','36309151','36309155','36315983','36317939','36267951','36267958','36268010','36266718','36266928','36265635','36267296','36226093','36262860','36227490','36221181','36226118','36219591','36219625','36227573','36225740','36225750','4277011','4272293','4276877','4276584','4276727','4243056','4174353','4174410','4174509','31892341','31890713','31891509','31892365','31892380','31890752','31890764','31890774','31891568','31891586','31890822','31890870','31891633','31890922','31891747','31890141','31890149','31891783','31890995','31891037','31891067','31890253','31891099','31791084','31791103',
'31791134','31791146','31791152','31790557','31888885','31888898','31790600','31790743','31790786','31298189','30471813','30473053','30474225','30486702','30487673','31888445','31790825','31790840','31790879','31888498','31790919','31888618','31791014','31888691','31889859','31889246','31889875','31889942','31889985','31889370','31889396','31889406','31890050','31889423','31890070','31889463','31889472','31890108','30568756','30687296','25936416','25936633','19698108','21810593','21812487','21813114','21813356','21813633','21926969','21927462','21927801','22009813','21934551','21947753','21947944','21949822','21801013','21801652','22356818','22356947','22358515','22358563','22358733','22359963','22355170','18920097','18920348','18920361','18920396','18920399','18920401','18920192','18920220','18920238','18920034','18920074','18920485','18920234','14737314','14487848','28615658','28615668','28490083','18908688','18909261','18909311','18908282','18908859','18909816','18909843','18909868','18909874','18910381','18909993','18909456','18909475','18910054','18908441','18908911','18907890','18908476','18907966','18907977','18913120','18912189','18912207','18911486','18911497','18910939','18910948','18910504','18910523','18910535','18911041','18910564','18908954','18909579','18909023','18909608','18909660','18909683','18909160','18909172','18909186','18912884','18912892','18912900','18912909','18912963','18913426','18913432','18913805','18913811','18913816','18913824','18913830','18913267','18913851','18913297','18913306','18912854','18912858','18913318','18912872','18913324','18912881','18911201','18912133','18912151','18910692','18910084','18910094','18910140','18910146','18910161','18910176','18910180','18910185','18910194','18909749','18909761','18910243','18909789','18907819','18907838','18907340','18907853','18907865','18906782','18907429','18906853','18906928','18907491','18908098','18908138','18907585','18907652','18907658','18907683','18907687','18907133','18907153','18907743','18907759','18906184','18906203','18906215','18905093','18905119','18904354','18904375','18904397','18904411','18904433','18904446','18904492','18904582','18904603','18904621','18904625','18904662','18903919','18903549','18902972','18903597','18903026','18903039','18903658','18902568','18903702','18903709','18903717','18903724','18903727','18903770','18903778','18902726','18903214','18903227','18903234','18902753','18902760','18903266','18902765','18902796','18903318','18902844','18902851','18902862','18906935','18904865','18906958','18904884','18907009','18904925','18907032','18905002','18904739','18904031','18904047','18904064','18904093','18903333','18904112','18904146','18903410','18904198','18904250','18903490','18903505','18902935','18902876','18902880','18902923','18506728','16590745','31891156','31890294','31891168','31891176','31891189','31891230','31890359','31891253','31890375','31890412','31889577','31889588','31889606','31889626','31889662','31890483','31889671','31888964','31888980','31890540','31890550','31889727','31890584','31890622','31889105','31890639','31890642','31889150','34351049','31892112','31892821','31892162','31892176','31891294','31892194','31892870','31891299','31891306','31891373','31891394','31892275','31890676','31890686','31891486','31892314','31890699','31891986','31892017','31892664','31892675','31892694','31892731','31892761','32452448','32453344','32454108','32474949','33565087','28076853','28255864','28077268','28255931','28256118','28075539','28075603','28075613','28075644','28075680','28075726','28075733','28075820','28076550','28076760','28614352','28614376','28615464','28615470','28488811','28488832','13345376','31892980','31893028','31893105','31893108','31893148','31892435','31892471','31912361','31891901','31891925','31891960','31892605','28615596','28488969','28615651','28489029','28489086','28489130','28489157','28489219','28489325','28489954','28256196','28489977','28256222','28228780','28228858','28229040','28229057','28229265','28229299','28230638','28255582','28077006','28077060','28255841','28077156','28489997','28256242','28256272','28256285','28256303','28256389','28256404','28228740','22888340','23119725','24481621','24728207','22501131','22501329','22501504','22446682','22458821','22459953','22460059','22460612','22508809','22509791','22510075','22497811','22499335','22500160','22461019','22500918','18914560','18915468','18915474','18915482','18915488','18914630','18915206','18914657','18914672','18914683','18914726','18914734','18914769','18914787','18914818','18917747','18917252','18917761','18917763','18917769','18917772','18917279','18917287','18914237','18913862','18914288','18914298','18913950','18913962','18913976','18913982','18914027','18914047','18914401','18914883','18915877','18915889','18914911','18914928','18914973','18914978','18916107','18916161','18916170','18916231','18915764','18916264','18917175','18915918','18916089','18916862','18916269','18916279','18917388','18917089','18916363','18917099','18913634','18913646','18913669','18914541','18913713','18914177','18913787','18913791','18913215','18913799','18915806','18915261','27454666','26606252','26607089','26608548','26672638','20762642','20829270','20829558','20829760','20830505','20774195','20845962','20527367','21639154','21639801','21538102','21538507','21640313','21540416','21477604','21223345','21223578','21487462','21251359','21252078','21367884','21368306','21159686','21369336','21979418','21643357','21644732','21646046','21646218','21549364','21585526','21585718','21791202','21641099','20740891','20759939','20761977','21587272','21609933','21612592','21618010','21618554','21619725','21366287','21366700','21367046','21155984','18918077','18917707','18917229','18917237','18917240','18917731','18917248','18918644','18918683','18918113','18918147','18918435','18918458','18919296','18919613','18919305','18919312','18919623','18919334','18919339','18919346','18919923','18917803','18918268','18918272','18917420','18919244','18918763','18919276','18919095','18918822','18918832','18918863','18918865','18918523','18918558','18918560','18918564','18918325','18918618','18919166','18919185','18919974','18919408','18919988','18919439','18919998','18920453','18920462','18920466','18920005','18920012','18919118','18912475','18911553','18911555','18912215','18912704','18912718','18912252','18912268','18912742','18911875','18912300','18911882','18911888','18912322','18911895','18912365','18912378','18912404','18911961','18912433','18911989','18912114','18912111','18911184','18911249','18911271','18911291','18911368','18910775','18911386','18911434','9942325','24481571','18916098','18902802','18902893','18918127','18903094','23430134','23430531','27996457','36213452','27995222','27995990','18918651','18918657','18905659','18918704','36268056','18909968','18918870','22593809','18904134','8574314','8574316','8574317','8574318','8574321','8574322','8574481','8569524','8570163','8570169','8570172','8569946','8569949','8569951','8569953','8570191','8569956','8570194','8569961','8570195','8569962','8570197','8570198','8569964','8570199','8569969','8569989','8570657','8570658','8570664','8570571','8570572','8570669','8570672','8570677','8570583','8570585','8570681','8570684','8570591','8570685','8570592','8569407','8569410','8569416','8569421','8570001','8570002','8570007','8570008','8570009','8570133','8570135','8570016','8570139','8570017','8570025','8570027','8570030','8569541','8569542','8569547','8569550','8569461','8569463','8570598','8570601','8570321','8570142','8570146','8570307','8570148','8570156','8570158','8582964','8583134','8583135','8583136','8583142','8583143','8583146','8583147','8582983','8582984','8582985','8582986','8582988','8582989','8583587','8584353','8584359','8584368','8584373','8584375','8584379','8584386','8584395','8584396','8584401','8584403','8584409','8584411','8584415','8584416','8576583','8576585','8576590','8576593','8576594','8576597','8576599','8576600','8576601','8576602','8576603','8576604','8576606','8576607','8576934','8576935','8576786','8576789','8576790','8576794','8576829','8576781','8576696','8576783','8576697','8576700','8576702','8576835','8576837','8577135','8577137','8577141','8577144','8577146','8577084','8577088','8576937','8577185','8577187','8577188','8577189','8577192','8577193','8577275','8577276','8577277','8577279','8577280','8577284','8577289','8577290','8577292','8577293','8577297','8577299','8577301','8577303','8577304','8577305','8577306','8577171','8577173','8567541','8567543','8567555','8567559','8567424','8567431','8567593','8567600','8567608','8567481','8567484','8567501','8567502','8575374','8575375','8575381','8575383','8575385','8575386','8567504','8567053','8566486','8566666','8566500','8566505','8566512','8566520','8566530','8566543','8566546','8566147','8566158','8566161','8566164','8566168','8566179','8566182','8567505','8567508','8567511','8567525','8567532','8567535','8566040','8566043','8566049','8566058','8566060','8566067','8565878','8565880','8565884','8565889','8566097','8566099','8565915','8565918','8565925','8565926','8565929','8565931','8565935','8565939','8565947','8565949','8566427','8566428','8566433','8566438','8566441','8566445','8566454','8566459','8566463','8566640','8566465','8566642','8566482','8566483','8566485','8566557','8566562','8566574','8566406','8566575','8566412','8566415','8566416','8566420','8566421','8566423','8576429','8576431','8576432','8576434','8576435','8576436','8576437','8576438','8576307','8576308','8576310','8576311','8576312','8576314','8576315','8576316','8576317','8564349','8565365','8565369','8565372','8565191','8565195','8565204','8565220','8565442','8565443','8565445','8565452','8565454','8565241','8565474','8565481','8565485','8565486','8565490','8565289','8565496','8565297','8565307','8565504','8565311','8565511','8565951','8565757','8565972','8565986','8565987','8565991','8565995','8565810','8565813',
'8565815','8565818','8565819','8565821','8565823','8565831','8565531','8565534','8565847','8565535','8565688','8565854','8565689','8565690','8565862','8565864','8565559','8565712','8565561','8565577','8565754','8565380','8565620','8565624','8565626','8565628','8565633','8565426','8565634','8565433','8565210','8581429','8581430','8581431','8581432','8581439','8581441','8581444','8581447','8581449','8581453','8581454','8581542','8581457','8581458','8581459','8581461','8581463','8581469','8581470','8581472','8581476','8581480','8581350','8580938','8580940','8580943','8580945','8580947','8580950','8580951','8580952','8580603','8580277','8580286','8580287','8580288','8580289','8580290','8579703','8579706','8579707','8579708','8579710','8579711','8579576','8580099','8580108','8580129','8579878','8579615','8579616','8579618','8579250','8579251','8579446','8579447','8579448','8579449','8579450','8579451','8579737','8579739','8579741','8579742','8579743','8579745','8579746','8579747','8579748','8579540','8579543','8579544','8579545','8579489','8579411','8579493','8579498','8579499','8579571','8579500','8579501','8579573','8579502','8579503','8582903','8582904','8582906','8582907','8582908','8582909','8582911','8582912','8582915','8582752','8582921','8582753','8582754','8582924','8582925','8582926','8582931','8582761','8582932','8582762','8582763','8582935','8582765','8582767','8582939','8582769','8582711','8582720','8582721','8582723','8582163','8582164','8582168','8582169','8582171','8582172','8582173','8582178','8582186','8582187','8581953','8581956','8581961','8581963','8581964','8581753','8583022','8583023','8583024','8583026','8583028','8583105','8583032','8583034','8583109','8583111','8583042','8583043','8583435','8583437','8583439','8583366','8583374','8583378','8583380','8581576','8581486','8581494','8581496','8581417','8581418','8584425','8584429','8584432','8584090','8584096','8584102','8583961','8583963','8583852','8583853','8583856','8583858','8583862','8582204','8582214','8582221','8582337','8582224','8582359','8582231','8582363','8582365','8581394','8581395','8581328','8581329','8581265','8581330','8575271','8575273','8575965','8575972','8575975','8575977','8574706','8574707','8574708','8574716','8574718','8574720','8574721','8574722','8574723','8574724','8574725','8574728','8574295','8574303','8574305','8574310','8574160','8574162','8574164','8574068','8574070','8574072','8574076','8574178','8574080','8574083','8574084','8574086','8574089','8574092','8574094','8574096','8574064','8573864','8573868','8573870','8573873','8573877','8573878','8573879','8573880','8573882','8574278','8574283','8574289','8574290','8574291','8574292','8573549','8573550','8573551','8573552','8573553','8573556','8573563','8573564','8573565','8573566','8573567','8573568','8573570','8573576','8573656','8573579','8573657','8573545','8568726','8568731','8568732','8568733','8568734','8568597','8568748','8568758','8568630','8568759','8568633','8568635','8568636','8568639','8568009','8572734','8572642','8572645','8576166','8576167','8576168','8576169','8576171','8576172','8576173','8576174','8576176','8576177','8576178','8576181','8576182','8576183','8576184','8576185','8573661','8573665','8573666','8573303','8573305','8573308','8573309','8573310','8573312','8573313','8575274','8575365','8575366','8575367','8575368','8575226','8575230','8575232','8575235','8575161','8575163','8575164','8573225','8573228','8573229','8573235','8573237','8573241','8573243','8573255','8573261','8573263','8573271','8572743','8572655','8572656','8572772','8572774','8572775','8572780','8572782','8572785','8572789','8572360','8572362','8572366','8572367','8572374','8572287','8572290','8572649','8572654','8572429','8572430','8572317','8572318','8572052','8572054','8572195','8572213','8572216','8572221','8572222','8572100','8572105','8572139','8571964','8576614','8576615','8576616','8576617','8576618','8576619','8576621','8576530','8576623','8576531','8576624','8576625','8576626','8576486','8576494','8576497','8574949','8574859','8574866','8575981','8575908','8575910','8575914','8575808','8574437','8574441','8574448','8574450','8574453','8575168','8575169','8575170','8575171','8575175','8575176','8575183','8575184','8575191','8575193','8575194','8575195','8575068','8575070','8575198','8575071','8575199','8575072','8575204','8575080','8575081','8575091','8575092','8575093','8575094','8575095','8575152','8575153','8575099','8575101','8575155','8575102','8575103','8575159','8575013','8575014','8575015','8575016','8575017','8574963','8575025','8574964','8575028','8575030','8574967','8574968','8575031','8575032','8574971','8574976','8574989','8574990','8574991','8574992','8574995','8574940','8574996','8574941','8574943','8574896','8574898','8574836','8574884','8574886','8574887','8574888','8574889','8574891','8553290','8552939','8553273','8553276','8553277','8553278','8553282','8552975','8552984','8552987','8552990','8552996','8553005','8553007','8554134','8554268','8554144','8554149','8554150','8554171','8554172','8554175','8554177','8553935','8554180','8554181','8553941','8554186','8553887','8553891','8553899','8553905','8562631','8552530','8552541','8552545','8552550','8552552','8552350','8552561','8552453','8552247','8552249','8552250','8552252','8552253','8552254','8551891','8551893','8551894','8552323','8552324','8552326','8552327','8552328','8552204','8552329','8552205','8552333','8552208','8552334','8552210','8552339','8552211','8552341','8552214','8552342','8552345','8552217','8552346','8552347','8552224','8552226','8552227','8552228','8552231','8552233','8552238','8552239','8552242','8551764','8552375','8552376','8552377','8552379','8552380','8552381','8552385','8552390','8552392','8552393','8552454','8552352','8552456','8552353','8552457','8552356','8552368','8552369','8552596','8552709','8552712','8552718','8552725','8552732','8556696','8556820','8556599','8556602','8556611','8556612','8556615','8556618','8556583','8556586','8556588','8556589','8556592','8556595','8556597','8556344','8556351','8556352','8556354','8556355','8556358','8556359','8556361','8556362','8556624','8556632','8556664','8556665','8556669','8556673','8557378','8557496','8557505','8557506','8557391','8557507','8557512','8557514','8557515','8557516','8557518','8557524','8557404','8557406','8557527','8557531','8557532','8557409','8557412','8557535','8557536','8557538','8557420','8557422','8557425','8557427','8557428','8557435','8557449','8557461','8557216','8557218','8557225','8557226','8557228','8557230','8557231','8557165','8557166','8557167','8557168','8557170','8557171','8557174','8557177','8557180','8556267','8556268','8556269','8556192','8564109','8563976','8554914','8554917','8554923','8555095','8555096','8555203','8555209','8555213','8555221','8554980','8554985','8554992','8554998','8554884','8554885','8554886','8554888','8554892','8554894','8554896','8555225','8555228','8555276','8554435','8554437','8554439','8554318','8554442','8554201','8554445','8554203','8555086','8554566','8554594','8554597','8554600','8554602','8554607','8554611','8554612','8555407','8555409','8555413','8573202','8573206','8573209','8573210','8573211','8573220','8573221','8572735','8572737','8572738','8572739','8572740','8573283','8573288','8573290','8573291','8573296','8573297','8573299','8573301','8573302','8572917','8572921','8571923','8571928','8571881','8572013','8572016','8572017','8571897','8571898','8571901','8571906','8571917','8572037','8571918','8571920','8571967','8571968','8571850','8571854','8571856','8571866','8562794','8561381','8560465','8560470','8560477','8560480','8560641','8560643','8560646','8560651','8560497','8560654','8560498','8560391','8560500','8560394','8560396','8560506','8560660','8560661','8559896','8559897','8559899','8559906','8559908','8559911','8560081','8559814','8560091','8559817','8559820','8559821','8560664','8560668','8560670','8560429','8560430','8560432','8560433','8560434','8560435','8560436','8560439','8560441','8560442','8560443','8560445','8560451','8560453','8560455','8560314','8560212','8560219','8560222','8559864','8559865','8559867','8559871','8559874','8559879','8559882','8559886','8559887','8559889','8559894','8559825','8560513','8563419','8563424','8563430','8563433','8563438','8557846','8557742','8557755','8557760','8558171','8558173','8558174','8558175','8558177','8558836','8558837','8558838','8558839','8558840','8558843','8558844','8558845','8558847','8558848','8558849','8558852','8558671','8558853','8558696','8558855','8558858','8558860','8558248','8558010','8557893','8558152','8558153','8557896','8558156','8558014','8558015','8557898','8558161','8558162','8557903','8558019','8558163','8558020','8558167','8558022','8558168','8558026','8557763','8557765','8557769','8557540','8557773','8557774','8557548','8557550','8557551','8557553','8557554','8557556','8557558','8557563','8557565','8557566','8557569','8557570','8557571','8557462','8557573','8557577','8557578','8557465','8557579','8557580','8557583','8557584','8557590','8557479','8557592','8557593','8557483','8557594','8557595','8557488','8557489','8558246','8558138','8558139','8558141','8558144','8558146','8558148','8557887','8557889','8558239','8558240','8558243','8558245','8561572','8561576','8561581','8561442','8561455','8562141','8562142','8562143','8562144','8562147','8562154','8562210','8562221','8562224','8562228','8562229','8561861','8561862','8561865','8561867','8561868','8561869','8561503','8561639','8561641','8561513','8561538','8561542','8561551','8561554','8561914','8561915','8561928','8561930','8561800','8561802','8561810','8561824','8561591','8561828','8561594','8561830','8561597','8561598','8561973','8561974','8561976','8561978','8561981','8562104','8562108','8562109','8559387','8559388','8559390','8559393','8559397','8559402','8559405','8559407','8559412','8559413','8559208','8559211','8559648','8559806','8559810','8559812','8558892','8558824','8558825','8558829','8558831',
'8558833','8559216','8559218','8559240','8559241','8559099','8559242','8559100','8559248','8559253','8559254','8559255','8559014','8559122','8559123','8559125','8559025','8559132','8559031','8559135','8559138','8559141','8559142','8559144','8559616','8559489','8559490','8559494','8559496','8559504','8559053','8559054','8559055','8559056','8559059','8559060','8559061','8559065','8559067','8559070','8559076','8559077','8558979','8558980','8559081','8559082','8559084','8559085','8559086','8558865','8559087','8558870','8558994','8558995','8559505','8559506','8559507','8559073','8549602','8549603','8549604','8549606','8549607','8549608','8549714','8583946','8583953','8583954','8583955','8583956','8583960','8582781','8582788','8582790','8582602','8582791','8582794','8582796','8582608','8582609','8582610','8582611','8582614','8582615','8582402','8582405','8582407','8582409','8582411','8582421','8582685','8582423','8582686','8582425','8582687','8582688','8582429','8582430','8582689','8582434','8582437','8580579','8580580','8580582','8580584','8581037','8581038','8581193','8581400','8581333','8581402','8581335','8581404','8581339','8581406','8581341','8581407','8581343','8581344','8581030','8580935','8580936','8580937','8580585','8580586','8580587','8580590','8580591','8580593','8580596','8580597','8580601','8580602','8580130','8580133','8580134','8580135','8580136','8580138','8580139','8580141','8580142','8580093','8580094','8580095','8580097','8577913','8577833','8577835','8577836','8577837','8577839','8577856','8577640','8577645','8577647','8577741','8577752','8577754','8577755','8577765','8577768','8577770','8577771','8577773','8577775','8577863','8577864','8577865','8577820','8577729','8577823','8577731','8577732','8577733','8577737','8577739','8577378','8577379','8577385','8577389','8577328','8577337','8577261','8577267','8577268','8577269','8581483','8581352','8581353','8581310','8581252','8581388','8581393','8578356','8578358','8578363','8578365','8578305','8578306','8578308','8578178','8578179','8578180','8578181','8578141','8578142','8578144','8578145','8578148','8578150','8578151','8578152','8578101','8578173','8578477','8578478','8578481','8578482','8578483','8578484','8578486','8578487','8578572','8578573','8578367','8578575','8578576','8578491','8578369','8578493','8578370','8578579','8578494','8578580','8578372','8578581','8578496','8578270','8580640','8580641','8578939','8579019','8579020','8579021','8579023','8579025','8579027','8578666','8578667','8578668','8579401','8579404','8579408','8579409','8578917','8578920','8578796','8579158','8579172','8579173','8579177','8579247','8579248','8579183','8579186','8579057','8579058','8579059','8579060','8579031','8576162','8576163','8576164','8563501','8563516','8563520','8563524','8563368','8563378','8563380','8563386','8563388','8563392','8563397','8563400','8563403','8563404','8563405','8563409','8563411','8563232','8556208','8556094','8556095','8555895','8555786','8555794','8555796','8555533','8555686','8555535','8555690','8555692','8555696','8555541','8555697','8555425','8555428','8555430','8555449','8555699','8555701','8555703','8555705','8555709','8555717','8555723','8555725','8555730','8555735','8555737','8555885','8555888','8555892','8555740','8555743','8555746','8555749','8555752','8555753','8555759','8555760','8555770','8555773','8555774','8555776','8555777','8555931','8555896','8555900','8555903','8555905','8555908','8555911','8555915','8555918','8555921','8555923','8555928','8556204','8556201','8551186','8551187','8551189','8551194','8551515','8551520','8551521','8551525','8551526','8551528','8551531','8550887','8550888','8550889','8550890','8550891','8550892','8550801','8550894','8550895','8550903','8550904','8550905','8550908','8551197','8551198','8551199','8551202','8551242','8551150','8551152','8551154','8551155','8551156','8551157','8551158','8551167','8551170','8551172','8551175','8551182','8551183','8550900','8549131','8549057','8549058','8549061','8549062','8549066','8549067','8549069','8549070','8549072','8549073','8549074','8546355','8546357','8546358','8546364','8546365','8546941','8546942','8546943','8546945','8546946','8546862','8546947','8546954','8547019','8547022','8547023','8547024','8547026','8547027','8547030','8546325','8546331','8546332','8546351','8550251','8550253','8550094','8550095','8550100','8550102','8550103','8545363','8545367','8546058','8546069','8546072','8546073','8545128','8545494','8545886','8545829','8545752','8545755','8545756','8545757','8545759','8545761','8545762','8545763','8545764','8545766','8545767','8545768','8545769','8545770','8545773','8545776','8545661','8545662','8545778','8545663','8545628','8545509','8545530','8545555','8545557','8545558','8545562','8545649','8545655','8545656','8545660','8545619','8545621','8545504','8545506','8545627','8545507','8545134','8545781','8545782','8545783','8545784','8545785','8545786','8545787','8545789','8545790','8545792','8548002','8548006','8548008','8548011','8548012','8548013','8548016','8548018','8548021','8548022','8548029','8548030','8548031','8548032','8548074','8548075','8548078','8548085','8548263','8548264','8548266','8548267','8548273','8548274','8548368','8548277','8548279','8548280','8548370','8550400','8550401','8550403','8550407','8550408','8550409','8550411','8548525','8547927','8547929','8547930','8547791','8547792','8547793','8547933','8547795','8547796','8547937','8547800','8547803','8547806','8547807','8547808','8547810','8547811','8547813','8547815','8547887','8547888','8547889','8547891','8547892','8547894','8547895','8548374','8548375','8548378','8548382','8548383','8548390','8548391','8548392','8548394','8548398','8548401','8547636','8547637','8547639','8547640','8547785','8547786','8547642','8547789','8547643','8547790','8549557','8549558','8549559','8549560','8549563','8549569','8549570','8549571','8549575','8549576','8549579','8549585','8548090','8548098','8548101','8548107','8547993','8547996','8548854','8548856','8548861','8548862','8548863','8541495','8544274','8541301','8544279','8539540','8539556','8539578','8539362','8539374','8539378','8539381','8539397','8539410','8539151','8539433','8538956','8539171','8539178','8539180','8539193','8538984','8539212','8540308','8540320','8540108','8540125','8539911','8540142','8539922','8539928','8539936','8540155','8540165','8540169','8539955','8540176','8540368','8540370','8540422','8540619','8540211','8540218','8540223','8540228','8540233','8540458','8540236','8540460','8540239','8540654','8540251','8540264','8540269','8540271','8540665','8540294','22883131','8541332','8541345','8541369','8541388','8541391','8541154','8541168','8541394','8541403','8541189','8538916','8538462','8538480','8538483','8538705','8538507','8538733','8538512','8538750','8538529','8538753','8538767','8538562','8538781','8538253','8538026','8538036','8538043','8538047','8538049','8538052','8538058','8537841','8538084','8537868','8537668','8538120','8537889','8537713','8537285','8537288','8537073','8537296','8537298','8537317','8537087','8537089','8537100','8537333','8537134','8537178','8537180','8536926','8536933','8536696','8539638','8539664','8539464','8539669','8539467','8539683','8539685','8539480','8539494','8539496','8539498','8539283','8539725','8539294','8539734','8540181','8539748','8540193','8539761','8540197','8539997','8539767','8539770','8540206','8540014','8539781','8539785','8539794','8539798','8539819','8539584','8539826','8539834','8536603','8536608','8536611','8536613','8536621','8550022','8550029','8549903','8549363','8549364','8549303','8549304','8549305','8549307','8549309','8549310','8549311','8549313','8549314','8549315','8549318','8549319','8549322','8549323','8549187','8549188','8549189','8549190','8549193','8549195','8549196','8549082','8549197','8549198','8549199','8549201','8549203','8549865','8549866','8549868','8549869','8549870','8549871','8549874','8549877','8549879','8549880','8549882','8549883','8549884','8549886','8549887','8549888','8549906','8549913','8549848','8549862','8549864','8550427','8550254','8549075','8549076','8549077','8549079','8548827','8549011','8548832','8549012','8549013','8549015','8549016','8548840','8548841','8548843','8548848','8549587','8549424','8549429','8549432','8549441','8549442','8549443','8549445','8549448','8549449','8549354','8549356','8549359','8549361','8549362','8547155','8547170','8547172','8547173','8547174','8547175','8547177','8547038','8547039','8547041','8547644','8547645','8547647','8547648','8547649','8547650','8547651','8547582','8547587','8547588','8547589','8547590','8547591','8547044','8547045','8547050','8547051','8547052','8547058','8547061','8547063','8546927','8546929','8546930','8546933','8546935','8546937','8547415','8547418','8547421','8547422','8547424','8547635','36190279','36190285','34037678','34049014','34003375','34023248','33988508','33988720','34023925','33993897','33994046','34028991','34029439','36289675','36289678','36289699','36289713','36289718','36289724','36290071','36290470','36297696','36297716','36297724','36297743','36297760','33995775','33996476','31333882','34000723','31368722','31370175','28765927','27613192','27623106','27957074','27957260','27627927','27630329','27631716','27556474','27556940','27556966','27806959','29079819','27504043','27584365','27585669','27533406','27446519','27554006','27612375','27554960','27460530','27419876','27420680','27420914','27421520','27421664','27422152','8541096','8540897','8540899','8540717','8540915','8540927','8536957','8536963','8536739','8536974','8536746','8536754','8536775','8536796','8536554','8536824','8536563','8541237','8541252','8541254','8541266','8541268','8541007','8541031','8540861','8540868','8540677','29698694','27877321','27879480','27879650','28765297','8537935','8537938','8537963','8537758','8537976','8537778','8537782','8537545','8537784','8537556','8537795','8537566','8537587','8537609','8538332','8538347','8538134','8538146','8538153','8538160',
'8538162','8538166','8538386','8538170','8538186','8538402','8538417','8538217','8538420','8538221','8537988','8538429','8538225','8538232','8537995','8537997','8538003','8544986','8544987','8544989','8538791','8539035','8539273','8538814','8539050','8538823','8538832','8539080','8540958','8540563','8540814','8538605','8538894','8538902','8537425','8537196','8537430','8537242','8537246','8537482','8537484','8537259','8537503','8537278','8539526','8537386','8537388','8537636','8537399','8537405','8538571','30472540','8554117','8554118','8554120','8545386','8545390','8545392','8545393','8545394','8545395','8545398','8545400','8579245','8581719','8581720','8581722','8581723','8581732','8581735','8552859','8552852','8552854','18917633','36268209','36268216','36268218','28614362','36268154','36269166','8548111','8554324','8554329','8550912','8554333','8550939','8554336','8536881','8545673','8545675','8545677','8545678','8536599','8545680','8545682','8545683','8537771','8545685','8574686','8574687','8574688','8574690','8538884','8583118','8583119','8583121','8583123','8583124','8583132','18914738','36341406','18918748','8545688','8545690','8538920','8538977','8539107','8545717','8539636','8546392','8546397','8546398','8546400','8546401','8546402','8545219','8545221','8546405','8545223','8546407','36341357','36341819','8576910','8576915','8576918','8576921','8576924','8576926','8579240','8576929','8579241','8557397','8574685','8564424','8564432','8545665','8573861','8564403','8564414','8564420','8544951','8544975','8544979','8544980','8544982','36227552','18912308','36215721','36279987','36279997','36280003','36213560','36215700','36215746','36215817','36215847','36214806','36214813','36215897','36214930','36261205','10524660','10524564','14282372','14282419','21930932','21930961','21931045','21931096','34436028','32517936','32521455','36162419','36169901','22810098','22810586','36144582','36151181','36214805','36214676','36215793','36214807','8764148','9112672','36249724','36249726','36266311','36249668','36249670','36249671','36249672','36249674','36249676','36249679','36249686','36249689','36249692','36249693','36249695','36249697','36249698','36249699','36249701','36249703','36249704','36249706','36249711','36249716','36249717','36249718','36249719','36249720','36249721','36249662','36249723','36249664','30421497','32656052','32665492','32668357','32752248','36125234','36123677','36126192','27962714','36322892','36322916','36322931','36322957','36249730','36249732','36249733','36249735','36249736','36249739','36249741','36249743','36249745','36249746','36249748','36249749','36129105','16511601','36127300','36137759','36102130','36102134','36102170','36102887','36103063','14578473','14492146','14488136','36151296','36151329','36151821','36151933','36110475','36117685','35627327','35691413','35786230','36216913','36216930','36218868','36222508','36222676','36222762','36229641','36154828','36233974','36157219','36236820','36158899','36242614','36165713','36174702','36177112','36179139','36213675','36214411','36215405','36215562','36215868','36216168','36216240','36216348','36216374','36216422','36216493','36216499','36137791','36137827','34407655','36216826','36142028','36144867','36145153','36145360','36145435','36145460','36146522','36146567','36148393','36148682','36148985','36149593','16941094','17109046','17197815','17269222','17318822','17327564','17328993','17335410','17601397','36300592','36301205','36301227','36301247','36301252','36301405','36301414','36301430','36301431','36300251','36300272','36300278','36300291','36300311','36300312','36300315','36300320','36300321','36293978','36293984','36293991','36296266','36296278','36296280','27624115','24800058','36118836','36118837','36118838','36118842','36118849','36118850','29429879','29429890','29428598','29428607','29428614','29429458','29428660','29429536','29428671','29428694','29429639','29429652','29428808','29429692','29428814','29429698','29428869','29428878','29429759','29429780','29428936','29428945','29429797','29428954','29429811','29428983','29429942','29429960','29429967','29429188','29429195','29429222','29430930','29429274','29429287','29429319','29429347','29429160','29428274','29428288','29428296','29428345','29428432','29428452','29428462','29428469','29428476','29428482','29428493','29428519','29428533','29429833','29429036','29429065','29429089','29429103','29429148','33729850','33729860','33729872','33730087','33730092','33730114','33730121','32691074','36118854','36118856','36118857','34492123','34499550','34500557','34500693','34500704','34501154','34548030','34777545','34558357','22593866','22540137','22542233','22575601','22533446','22533449','22533581','14339697','14245402','34564029','22183061','31616991','31663161','31663941','31688717','25210410','25210431','23280534','23280545','23280651','23280659','23280671','36125495','36125497','36125519','36125521','36125539','36217407','36217411','36217412','36217414','36217416','36217418','36217429','36179753','36191906','36302460','22533588','29976520','25210279','29976636','29976692','25210302','29976963','25210311','25210317','36176091','36141415','36108104','36108105','36154679','36117043','36117096','36117100','36117102','36117110','35847389','35848226','35849645','35850029','35850385','35850685','35851417','35851573','35852758','35852856','35906130','36282250','36262579','36262580','36262581','36236397','36236401','36236404','36262662','36262666','36262691','36262695','36271938','36273094','36273118','36273133','36273146','36273175','36282334','36273244','36273261','36273328','36273377','36282238','36154690','36171534','36173891','36117145','36282263','36282264','36282279','36273184','36282294','36273199','30543878','36217940','36220275','36220369','36217942','36216274','36216275','36218050','36216276','36220372','36218055','36216278','36218057','36220384','36218065','36218066','36218067','36220395','36218068','36220397','36218070','36218080','36220403','36218081','36220407','36220410','36218736','36220411','36218872','36218890','36218957','36218958','36218964','36224408','36224451','36224455','36224496','36216153','36216157','36217918','36220183','36216268','36217931','36216269','36228050','36228052','36228059','36228067','36228097','36228191','36230988','36229524','36229526','36230998','36228196','36216108','36206127','36206133','36204978','36204980','36207696','36207697','36226835','36226840','36226848','36226849','36226857','36226879','36226930','36226960','36227005','36227015','36218974','36218981','36218985','36218987','36207668','36207682','36324054','36344454','36324058','36344466','36324060','36344474','36324062','36344477','36344478','36324071','36344492','36324092','36344493','36325016','36325040','36325160','36325170','36345691','36345720','36346094','36346099','36346166','36326291','36346171','36326298','36326327','36339088','36346173','36339095','36339116','36346178','36339137','36346190','36340076','36346194','36340126','36346203','36346206','36347096','36347147','36347154','36347203','36347207','36347213','36323976','36324000','36324021','36324024','36344364','36324027','36266800','36266802','36266906','36266907','36264268','36266920','36266921','36264274','36266932','36264277','36266934','36278500','36278501','36278506','36278508','36278509','36278512','36278513','36278514','36278515','36283647','36280959','36283655','36280972','36283656','36280973','36281004','36281024','36285635','36286689','36286693','36273312','36269163','36269177','36269181','36269182','36269183','36269185','36270554','36270565','36270582','36270607','36270626','36270632','36270634','36271805','36271809','36271849','36268155','36268159','36268166','36268169','36271960','36268183','36268192','36268196','36271964','36277222','36277280','36277299','36277300','36277301','36277317','36277331','36277332','36277333','36271984','36272054','36272091','36272100','36272104','36272112','36272114','36272116','36272117','36272118','36309240','36309241','36310294','36310315','36310323','36310335','36310833','36311236','36311242','36311243','36311295','36311298','36311300','36311301','36311319','36311320','36311324','36311325','36311326','36312347','36312410','36323959','36323967','36323974','36306646','36289523','36290867','36289525','36290869','36289526','36290871','36290874','36289527','36289528','36292829','36292991','36290678','36290683','36290687','36290714','36290718','36290728','36290732','36290733','36290740','36290741','36282441','36282443','36282447','36282449','36282450','36282455','36282459','36282462','36282466','36306744','36306773','36306784','36306785','36306803','36306844','36306851','36306854','36307670','36290761','36290767','36290769','36290774','36290790','36290795','36290797','36290805','36290808','36290818','36290819','36290820','36290826','36289517','36289518','36290841','36290842','36289521','36290847','36289522','36290863','36307992','36307994','36307997','36307999','36306573','36283534','36283636','36283638','36303343','36298740','36306633','36264280','36266935','36266957','36266974','36266983','36266984','36266985','36266988','36265904','36265907','36265911','36265914','36262668','36262671','36262675','36262678','36262694','36262699','36262759','36262763','36261612','36256131','36261706','36261735','36261736','36257632','36257689','36257737','36257743','36257755','36257758','36257774','36249797','36257776','36249798','36257778','36257781','36257814','36257815','36257817','36257819','36249799','36257821','36257825','36257826','36257831','36249843','36249844','36249764','36249767','36250156','36249769','36249773','36249775','36250157','36249776','36249777','36250159','36250161','36250165','36249779','36250167','36250186','36250192','36250194','36250204','36260025','36260044','36260081','36260089','36260091','36253865','36253872','36253881','36260097','36253883','36260102','36260103','36260135','36260150','36260156','36260157','36260158','36262602','36262620','36262625','36262637',
'36260001','36260006','36260012','36262642','36260021','36262646','36235337','36232212','36262768','36262778','36262783','36262789','36262791','36239987','36235320','36229529','36229531','36229533','36230867','36245488','36245492','36245494','36245502','36245503','36245508','36245509','36245511','36249079','36249082','36249801','36249803','36249804','36249805','36249806','36249807','36249793','36249794','36245465','36245470','36245475','36245483','36249784','36249787','36249790','36232213','36235368','36232214','36232222','36235382','36235384','36232243','36232248','36232264','36236592','36232290','36232293','36236728','36236732','36236756','36232337','36182176','36182180','36182196','36182201','36225817','36225834','36221482','36221598','36221606','36225889','36225897','36225900','36225904','36225908','36225910','36225912','36225915','36204758','36204790','36204808','36204809','36204878','36204881','36205925','36205933','36205946','36206056','36199685','36199723','36199728','36199729','36199734','36199736','36199740','36199747','36199750','36202664','36197052','36197055','36191391','36191393','36191410','36192739','36198506','36204982','36204987','36202676','36204999','36202698','36205005','36205008','36202704','36202737','36202739','36202743','36202744','36197035','36197037','36197041','36197046','36197048','36239989','36250197','36113470','36106388','33252068','14901865','36161529','36161616','36170118','36177870','36177873','36177878','36177880','36177933','36177945','36177946','36177948','36177958','36177962','36177965','36177967','36177970','36179041','36262681','36257779','36252678','36246543','13689609','29718364','29718444','29718523','12713627','36225873','36241304','36241332','26274180','26274260','26274320','26274816','26268997','26274842','26275113','26275275','26275349','26234042','26286759','26293262','26293350','26293379','26293957','26294214','26294353','26291256','26287978','26291406','26227195','26227204','26227242','26226911','26226918','26226926','26226961','26227001','26227008','26227020','26276567','26285583','26291742','26291836','26292041','26227106','26285640','26285702','26285820','26285862','26285931','26282307','26273655','26273750','26273769','26273911','26268161','26338027','26338055','26335478','26338453','26335522','26338553','26335573','26335631','26338723','26335857','26333588','26335969','26333667','26336032','26339127','26336118','26339276','26333979','26334068','26339674','26334291','26336913','26334367','26324622','26335163','26335189','26335209','26299273','26322253','26298407','26295284','26321489','26321621','26333031','26298666','26296643','26296675','26296758','26292407','26296848','26292490','26292527','26292582','26292657','26292820','26341466','26343480','26344349','26344397','26344463','26340800','36221625','36224332','36224362','36179151','36179228','36179322','36182091','36182099','36182136','36182148','36190260','36190268','36190325','36190337','36190340','36190348','36207707','36207711','26298676','14245141','36273212','36229922','36189057','36303980','36303994','36304008','36304019','36304028','36304170','36309046','36309051','36309059','36309066','36309073','36309085','36309090','36309099','36309107','36309113','36309119','36309132','36309143','36309147','36344365','36344851','36344856','36344881','36344970','36346296','36346313','36346328','36341363','36346451','36341806','36341943','36315953','36315961','36315967','36315973','36309160','36315979','36342955','36342973','36342983','36342985','36342993','36315993','36316005','36267938','36267970','36267976','36267986','36267995','36267999','36268015','36268021','36268035','36268039','36268046','36268051','36268101','36268111','36268150','36266856','36266862','36266868','36266886','36266936','36264418','36266941','36264422','36264429','36264438','36266958','36266965','36266975','36266979','36266993','36266999','36267493','36267536','36267556','36262760','36262773','36262779','36262786','36262798','36227642','36218810','36219607','36219615','36219623','36219632','36227517','36227519','36227523',
'4276856','31891501','31890734','31891557','31890783','31890801','31890812','31891596','31890847','31890862','31891666','31890909','31891702','31891714','31890930','31891729','31890942','31890121','31890956','31891755','31890964','31891766','31890166','31891778','31890988','31890192','31891794','31891004','31891807','31890207','31891016','31890219','31891819','31890229','31891829','31891049','31890242','31891837','31891075','31891850','31890260','31891859','31890277','31890284','31791110','31790461','31791123','31791160','31790593','31790612','31790631','30486387','30486949','31889528','31790844','31790929','31888542','31790933','31888553','31888569','31888581','31888591','31888646','31790358','31889817','31889190','31889825','31889207','31889227','31889843','31889852','31889883','31889892','31889898','31889918','31889934','31889955','31889992','31890023','31890031','31890039','31890059','31889449','31890098','30686776','25937194','21803231','21803729','21805001','21805409','21805793','21806049','21806276','21811103','21811370','21811630','21812005','21812833','21813896','21814146','21814370','21814572','21814917','21931706','21933082','21933361','21933644','21933918','21928519','21928822','21930440','21934216','21936716','21947246','21947463','21948143','21951555','21951706','21801368','22355764','22357095','22357562','22357754','22359303','18920084','18920095','18920107','18920117','18920124','18920150','18920162','18920167','18920184','18920190','18920197','18920208','18920230','18964298','18920043','18920064','18920069','18920076','18920480','14737313','28614163','18908634','18909207','18908641','18909213','18909217','18908659','18909225','18908664','18909235','18908671','18909251','18908678','18909254','18908228','18909258','18908695','18908232','18908701','18908239','18909268','18908706','18908245','18909281','18908252','18908712','18909289','18908260','18908728','18909295','18908264','18908730','18909301','18908271','18908745','18909316','18908290','18908752','18909322','18908295','18908759','18908301','18908765','18908776','18908305','18908786','18908313','18908791','18908319','18908804','18908324','18908327','18908819','18908333','18908342','18908827','18908351','18908357','18908836','18908370','18908847','18908379','18908385','18908863','18908394','18908866','18908401','18908874','18908406','18908877','18908414','18908883','18908422','18910255','18910262','18910266','18909839','18910271','18910275','18910285','18909880','18910300','18909884','18910305','18909888','18910312','18909896','18909907','18910321','18910326','18909919','18909330','18909929','18909938','18909339','18909945','18909342','18909348','18910337','18909354','18910345','18909358','18910355','18909370','18909959','18910363','18909376','18910366','18909384','18909389','18909395','18909972','18909408','18910388','18909980','18909988','18909417','18909426','18910005','18909429','18909431','18910012','18909437','18910024','18909449','18910029','18910033','18909461','18910038','18909468','18910043','18910050','18910060','18909483','18910066','18909489','18908937','18908946','18908890','18908432','18908902','18908434','18908907','18907876','18908446','18907883','18908453','18908916','18908463','18907905','18908469','18908922','18907915','18908925','18908484','18908932','18908491','18908501','18908509','18908510','18908518','18908522','18908531','18908537','18908540','18908548','18908553','18908557','18908043','18908055','18913059','18913508','18913515','18913064','18912485','18913521','18913070','18912492','18913078','18912499','18913085','18913096','18912512','18912518','18913128','18912530','18913133','18912537','18913140','18912542','18913146','18912551','18913151','18912560','18913157','18912570','18912574','18913161','18913171','18912584','18912590','18912598','18912603','18912609','18912622','18912630','18912166','18912632','18912640','18912173','18912182','18912648','18912660','18912195','18912666','18912198','18912671','18912675','18912211','18912687','18910851','18910862','18910871','18910876','18910884','18910897','18910903','18910908','18911501','18910911','18910921','18910402','18910925','18910408','18910933','18910414','18911505','18910420','18910959','18911520','18910961','18911526','18910973','18910444','18911538','18910986','18910989','18910451','18910999','18910461','18910472','18910477','18910487','18910495','18911015','18910508','18911027','18910529','18910542','18910549','18910555','18910576','18911054','18910579','18910590','18910594','18909501','18909507','18908963','18909530','18908974','18909534','18908985','18909545','18908993','18909558','18908999','18909562','18909017','18909571','18909018','18909586','18909032','18909597','18909037','18909605','18909047','18909057','18909067','18909073','18909633','18909089','18909643','18909094','18909652','18909103','18909671','18909122','18909126','18908566','18909148','18908577','18909695','18908584','18909708','18909166','18908597','18908603','18908608','18908616','18909196','18908621','18909201','18913343','18913354','18913362','18913366','18912923','18913381','18912932','18912937','18913388','18912941','18913397','18912943','18913406','18912948','18913409','18912959','18913412','18913415','18912971','18913416','18912978','18913421','18912982','18912985','18912987','18913441','18912989','18913444','18913002','18913450','18913013','18913464','18913017','18913472','18913021','18913482','18913030','18913490','18913040','18913497','18913052','18913499','18913284','18913291','18913316','18911065','18910602','18911072','18910607','18910618','18911077','18910624','18910631','18910637','18911085','18911094','18910644','18910652','18911112','18910662','18910071','18910672','18911132','18910080','18912122','18911680','18912130','18912141','18911217','18912149','18911224','18911686','18910682','18911136','18911147','18910700','18910706','18910710','18910719','18910722','18910099','18910725','18910729','18910737','18910115','18910121','18910747','18910128','18910753','18910135','18910756','18909726','18909732','18909737','18909742','18910198','18909751','18910203','18910206','18909767','18910217','18909772','18910219','18909776','18910230','18909781','18909783','18910250','18909798','18909806','18909809','18907772','18907795','18907799','18907805','18906227','18906364','18906375','18907825','18906384','18906391','18906448','18906539','18907843','18906554','18906564','18907858','18906567','18907363','18906574','18907370','18906581','18907377','18906600','18906745','18907384','18906755','18907387','18906765','18907395','18906772','18907408','18906779','18907412','18907419','18906795','18907434','18906803','18907443','18906815','18906826','18906836','18905261','18906846','18905271','18905283','18906867','18905286','18906870','18905289','18906876','18905303','18905308','18906883','18905322','18906888','18905330','18906895','18906899','18905474','18905479','18906908','18905489','18906912','18905495','18905542','18907452','18907466','18907500','18907505','18907519','18907530','18907535','18908147','18907543','18907548','18908156','18907554','18907040','18907568','18908158','18907049','18907573','18908160','18907056','18908166','18907636','18907060','18908172','18907645','18908180','18907650','18907066','18908189','18907069','18908194','18907079','18908206','18907675','18907085','18908214','18907092','18908222','18907099','18907692','18907695','18907109','18907706','18907715','18907147','18907721','18907728','18907737','18907750','18907767','18905026','18906139','18906153','18905037','18906162','18906169','18905046','18905051','18905221','18905226','18905231','18905233','18905238','18905244','18903810','18904638','18904645','18905257','18904648','18904653','18904658','18904686','18903542','18903556','18903564','18903574','18903581','18903613','18903623','18903632','18903646','18903736','18903749','18903158','18903168','18903178','18903183','18903186','18903193','18903197','18903204','18902717','18903218','18903246','18903270','18902778','18903276','18903284','18903288','18903311','18902827','18902837','18902840','18906940','18905649','18906955','18904874','18905653','18904888','18906962','18906973','18904894','18906979','18904900','18904911','18904922','18907017','18907022','18904930','18904937','18907036','18904943','18904950','18904959','18904966','18904976','18904979','18905017','18904700','18904726','18904734','18904746','18904754','18904769','18904776','18904781','18904786','18904043','18904797','18904808','18904072','18904814','18904087','18903322','18904816','18903327','18904829','18904837','18904843','18903342','18904125','18904852','18903350','18903357','18904153','18903387','18904183','18903421','18903433','18903445','18903452','18903460','18903464','18903471','18902930','18452371','18506718','18006096','18006103','15339930','31890304','31890311','31890331','31890341','31890366','31890382','31890398','31890439','31890452','31890459','31890471','31890480','31890491','31889691','31890502','31889708','31890523','31889717','31890531','31890560','31889011','31890571','31889734','31889738','31889752','31889762','31889775','31889794','31890645','31889807','34374587','34375679','31892773','31892122','31892797','31892132','31892816','31892137','31892148','31892155','31892185','31892212','31892226','31892236','31892242','31892255','31892260','31891467','31891493','31892625','31892000','31892635','31892643','31892021','31892028','31892037','31892051','31892073','31892733','31892742','31892752','31892921','31892940','31892947','31892958','28076829','28077213','28255881','28255903','28255912','28075505','28075519','28075579','28216256','28076651','28076674','28076688','28076777','28076787','28076802','28076817','28614389','28615476','28615494','28615530','28488662','28488793','14737377','29065990','29066967','29067474','36111201','31892967','31893161','31893176','31893186','31892460','31893683','31893690','31892489','31891874','31892501','31892523','31891881','31892535','31891893','31892542',
'31891909','31892556','31891919','31892565','31892572','31892589','31892616','28488886','28488897','28488912','28615609','28488936','28488954','28489012','28489058','28489072','28489191','28489237','28489268','28489303','28256163','28489858','28256183','28489967','28489991','28228795','28228880','28228960','28228978','28228994','28229207','28229246','28229283','28229291','28229312','28230619','28076870','28076915','28076940','28255548','28076968','28255567','28076981','28255597','28255754','28077019','28255776','28077032','28255792','28077083','28255808','28490013','28256328','28256340','28256374','28256440','28228566','24481490','24481699','22501156','22501212','22501273','22501398','22444926','22445673','22455499','22459217','22460255','22460554','22496932','22497008','22497251','22497390','22497672','22498089','22498198','22498501','22499153','22500245','22500422','22500547','22460816','22500999','22501091','18915458','18914566','18914583','18914585','18914587','18914592','18914598','18914603','18914608','18914616','18914622','18914635','18914638','18914655','18914667','18914679','18914686','18914701','18914704','18914721','18914752','18914778','18914195','18914203','18914209','18917250','18917257','18917271','18917778','18917782','18917289','18917293','18913892','18913900','18914304','18914313','18913910','18913917','18913924','18913932','18913941','18913959','18913972','18913522','18914001','18913525','18914009','18913535','18913547','18913552','18913560','18913566','18913569','18913585','18915281','18915287','18914889','18915882','18914898','18914905','18914917','18914921','18915439','18915441','18916217','18915695','18915756','18915773','18917168','18917177','18915899','18915935','18915940','18916026','18916283','18917393','18917070','18916337','18917102','18913596','18913701','18914156','18913708','18914172','18914186','18914187','18915249','27146026','21221242','20828434','20763210','20763947','20829951','20773980','20521572','20734032','20736646','20737186','21537484','21639443','21537780','21539820','21540777','21541338','21541967','21542662','21543109','21543647','21546370','21546816','21547298','21547805','21476823','21477161','21222849','21478140','21478538','21484741','21485477','21367432','21368030','21368144','21368423','21368706','21369032','21369164','21369453','21643064','21797312','21797547','21799970','21643756','21644199','21800765','21644364','21644587','21644931','21645066','21645352','21548300','21548697','21562145','21564699','21569792','21570122','21570724','21570957','21672264','21571208','21585353','21585884','21586022','21586766','21586935','21640568','21640820','21792830','21793131','21641318','21793449','21641553','21793709','21793940','21641796','21794188','21642024','21642525','21642742','20737664','20739305','20739727','20740268','21587124','21610930','21611442','21619367','21536881','21537106','21252238','21366165','21366249','21366591','18918075','18917648','18918081','18918085','18917198','18917201','18917675','18917202','18917211','18917215','18917226','18917236','18917738','18918637','18918642','18918648','18918665','18918118','18918123','18918137','18918438','18918203','18917798','18917802','18919298','18919303','18919899','18919317','18919910','18919323','18919630','18919331','18919634','18919918','18919642','18919645','18919348','18919354','18919358','18919366','18919370','18919372','18918236','18917819','18917821','18917825','18918251','18918261','18918265','18918295','18918000','18919240','18919246','18918760','18919266','18918781','18918810','18918812','18919099','18918819','18918827','18918829','18918836','18918839','18918844','18918862','18918527','18918873','18918879','18918880','18918882','18918895','18918899','18918901','18918561','18918568','18918324','18918605','18918607','18918611','18918972','18919009','18919984','18919990','18919999','18920467','18919102','18919106','18919114','18912468','18912010','18912016','18912024','18912029','18912481','18911546','18912032','18912040','18912054','18912057','18911559','18911565','18911572','18911582','18911587','18911593','18911601','18911606','18911614','18911622','18911632','18911633','18911639','18912064','18911643','18911647','18911651','18911658','18912080','18911664','18912094','18912100','18911163','18912107','18911670','18912695','18912225','18912229','18912708','18912714','18912234','18912244','18912721','18912728','18911848','18912735','18911856','18912737','18912275','18911858','18912750','18912301','18912760','18912331','18912338','18911902','18911908','18911915','18911917','18912370','18911922','18912374','18911936','18911937','18912386','18911939','18912389','18911943','18912394','18912400','18911953','18911958','18912410','18912412','18911963','18912417','18911967','18912427','18911980','18912441','18912449','18911995','18912453','18912001','18911192','18911229','18911256','18911261','18911700','18911708','18911277','18911280','18911303','18911305','18911309','18911317','18911327','18911333','18911337','18911344','18911760','18911352','18911764','18911359','18911770','18911774','18910767','18911779','18911788','18911375','18911795','18910784','18911804','18911383','18911816','18910797','18911832','18911393','18911836','18911401','18910805','18911843','18911409','18911414','18910812','18911427','18911449','18910823','18910829','18911464','18910833','18911473','18910838','18910847','18903058','18903081','18903062','18903092','18903051','18903137','18903127','18903121','18903117','24659179','8574484','8579740','8579497','8582936','8582176','8583434','8583965','8582205','8582223','8582226','8581262','8573256','8553904','8556685','8556693','8556699','8556702','8556679','8554903','8558172','8557568','8561801','8561805','8558984','8558990','8581267','8577390','8545502','8545754','8545760','8548402','8549562','8539559','8539344','8539563','8539364','8539390','8539119','8539125','8539143','8539416','8539153','8538947','8539161','8539442','8539445','8539173','8538968','8538988','8540297','8540303','8540314','8540080','8540085','8540094','8540100','8540105','8540118','8539893','8539917','8539931','8540174','8539958','8540366','8540372','8540410','8540416','8540425','8540617','8540648','8540246','8540465','8540256','8540266','8540273','8540467','8540475','8540292','8541313','8541320','8541322','8541347','8541349','8541357','8541366','8541375','8541383','8541399','8538443','8538464','8538941','8538943','8538478','8538491','8538719','8538727','8538730','8538518','8538520','8538531','8538536','8538299','8538550','8538555','8538016','8538255','8538261','8538264','8538274','8538277','8538280','8538282','8538054','8537830','8538060','8538064','8538066','8538069','8537848','8537850','8538092','8537879','8538111','8537673','8538114','8537881','8538117','8537892','8537692','8537901','8537694','8537703','8537915','8537706','8537711','8537919','8537062','8537065','8537067','8537290','8537293','8537075','8537083','8537329','8537331','8537335','8537116','8537124','8537140','8536878','8537150','8537159','8536889','8536896','8536900','8536906','8537176','8536910','8537184','8537187','8536691','8536935','8536705','8536940','8536710','8539865','8539870','8539650','8539448','8539450','8539458','8539471','8539687','8539697','8539706','8539277','8539500','8539504','8539727','8539514','8539736','8539303','8539962','8539965','8539746','8539750','8539752','8539756','8540189','8539758','8540191','8539991','8540010','8539772','8539775','8540025','8540044','8540046','8539804','8540059','8539807','8540061','8540063','8539816','8539821','8539824','8539591','8539829','8539832','8539602','8539841','8539844','8539848','8539857','8539861','8536672','8536675','8536678','8536605','8548845','8547652','8547585','8547417','34001908','34003888','34004791','34016706','34017085','36289694','36290384','36290454','36297748','33997161','33998544','34000087','28766383','29078748','28697807','27631105','27466214','27467147','27557183','27422513','27445939','27612576','27389422','27389797','27421332','27421444','27422064','8540894','8540704','8541098','8541105','8540909','8540920','8540924','8540933','8540936','8540943','8540945','8536719','8536985','8536993','8536999','8536507','8536520','8537004','8536787','8537012','8536817','8536576','8541241','8540962','8540966','8540972','8540976','8541259','8540994','8541272','8540822','8540844','8540854','8540856','8540858','8540865','8540870','8540873','8540881','8540883','8540885','8540694','8540888','8537715','8537929','8537732','8537955','8537957','8537760','8537972','8537513','8537769','8537515','8537978','8537558','8537560','8537802','8537577','8537813','8537592','8537595','8537817','8537353','8537374','8537616','8538589','8538342','8538140','8538144','8538174','8538393','8538193','8538197','8538209','8538215','8537984','8538425','8538228','8538236','8538244','8538246','8538997','8539216','8539230','8539244','8539033','8539256','8539262','8538801','8539044','8538817','8538821','8539071','8539073','8538845','8538852','8540754','8540949','8540960','8540532','8540542','8540816','8540820','8540575','8538856','8538860','8539102','8538864','8538607','8538611','8538881','8538623','8538886','8538652','8537418','8537190','8537202','8537207','8537212','8537218','8537455','8537467','8537470','8537254','8537257','8537489','8537020','8537499','8537043','8537048','8537053','8539738','8539740','8539312','8539530','8539315','8537394','8537651','8538322','8538326','8538578','36268204','36268233','8537050','8537441','8537480','8536625','8536698','8537520','8537740','8538033','8539599','8540300','8538045','8538078','8538678','8539138','8539510','8539512','36341353','36341354','36341358','36341359','36341809','36341816','36341824','36341828','8576912','8536574','36279988','36279995','36214934','36213626','36215796','36215846','36215851','36214920','36248943','32525299','36169907','36280019','3833058','7551433','8695332','7551437','7551480','16791793','33609180','2519366','18868613','7551486','8662108','23034669','8717224',
'18868522','18867614','7551503','3634980','3509984','23085699','8659738','3628416','21174250','3601597','8665114','8766164','33226635','6636871','26850992','3615065','3617755','17541920','8390167','17659387','8762530','33603773','6641024','5901805','8797269','5763624','5921350','12632869','8689034','33227419','7193368','10262864','3612934','6641947','6641395','6641506','36144462','10257447','5762410','11144674','6643648','3611954','10262638','9746626','11410198','3588834','8796601','9275440','11898711','3591096','21174275','3630455','33226247','9747022','23086591','16591262','21712645','26794255','5786467','3594441','9748221','3615479','3619347','6640378','18868222','17386020','9276240','36227838','6636742','3628880','5761168','19335278','10186063','6643287','26490218','11864303','3607414','21712776','7551477','5883315','6643500','36114402','36227526','3616439','33227359','10256545','36161905','6652508','17658894','16591185','17606096','16591194','29008832','3634019','33136510','17526969','16591201','17524620','21174271','9276732','33136570','8764073','17386964','3604225','3600785','33603821','26794325','17658867','6631923','6632106','7551472','8389863','17541919','26793654','6637603','17661155','28955100','8389788','33227348','17660076','10257620','8763233','6637745','33227312','21174252','33227262','9755941','5539305','6653306','12654427','10259834','33226236','33609804','3615042','8696245','3602931','6644147','8658409','10257666','26793496','9276622','9276098','6644252','3597751','3598262','17660786','26793780','10261986','3612428','3598001','17659500','26794147','27875782','5767953','10257093','8695768','25036678','26793220','17586623','29117205','6651979','29011561','17657908','33136523','25036105','11897548','3624116','8763949','8765948','8654739','11408040','17606101','36158263','17589241','33604492','3604816','3607709','24664217','33604422','28286439','9276999','11408511','33603446','6078123','6644491','5762624','5345130','8696892','6632371','28398490','5883216','10257024','8765873','12654422','10256341','3614357','6632589','17529837','10256732','33603507','33227425','5920060','10163350','10257832','10259950','21174358','29117975','6652269','11409811','33227308','6652152','36227863','10136778','8390157','5763472','28396288','3611090','6637138','12654426','9276796','33603553','24081053','28396419','12654423','5348350','10257776','33609054','6078624','17585891','5655794','8765604','8765692','17541916','11408940','28404507','10125900','11410281','33608831','8797272','28287442','5346167','17378547','6083770','10260112','8764548','8796531','21924394','3606244','27655929','6634160','9276833','9781090','12632836','21174365','6082845','10136783','5296143','5784675','8389451','15427473','12654429','10260210','33136590','6654139','6655074','8340575','6633817','27795592','28293147','5785066','9276894','12654435','5347706','10260447','21713098','10186702','11410096','8797058','8796642','8796829','33609411','6082623','6655309','6083609','5786660','8388571','5786950','10257303','10260573','8765645','25036412','31388826','5761636','28287644','28396851','30569596','10125899','32618677','9276429','5786430','10261401','36180413','11410331','21174362','36262594','35548298','36231777','30662844','6636617','28402790','5787238','12654425','12654430','5668138','5560621','5674990','8797113','6642468','6638038','5903638','10125904','9755300','12654432','8663104','8766668','8797289','35536168','24135023','32694228','5787075','17526918','5668796','8663020','10263791','10256241','10258597','23118781','21930740','21930756','6644366','22817010','12654434','5348026','5668297','11897285','10257907','11409654','11426376','10524255','6652399','6652786','8366801','8366189','24081483','36204958','5786403','9755720','17526928','12654428','8388200','12654431','5785337','5786389','5652677','8655027','34989927','5758855','28287772','28393742','5920951','10136782','9275536','15427481','21891593','9754670','34834242','6652049','6633638','6637391','30600176','10125919','30602283','22748367','17526944','17526949','21891751','5668679','10260147','21451090','35611917','10524729','30261631','6654378','36111342','6644557','36206147','36157242','35607616','28405802','5786535','15427464','17526827','5666388','8535529','10162694','12632837','10256869','10261066','22844667','3591654','3614662','8762150','20273099','32545682','33136551','6654961','6653198','6634247','17606113','6634587','5267662','36205877','30569757','5785571','17585116','9276477','10236142','22747580','22816345','17587748','22058498','5347919','5666252','8602715','8603048','10256613','10257573','17389852','21174281','10524761','33608977','10524346','8365566','6637284','36172735','8365625','6084130','30601451','30601512','5883541','5769347','10125910','5786282','5786684','5784195','11148786','5347292','5347863','5348129','8667233','8663257','10257162','8695080','22431120','8766746','33609399','3618759','6653981','6633537','8366165','36227831','6637945','27696484','30605512','5785030','8388658','8387735','9276871','9276590','10236486','17526875','19334053','5785317','5655396','5657382','5666163','5666337','12632891','10257975','10262923','10261770','10262111','21713273','7551505','11405990','10524604','3632464','33608909','26243471','21930785','6653614','6633169','30212402','32100952','36227882','36205901','5773584','36158272','28398744','5760129','5296046','5785613','8388975','8389410','22749387','15427470','15427495','22749815','22057523','8387909','5348411','5348646','5354510','5346163','5666472','5654137','5671880','21901603','16591333','34834186','8709351','8710436','10257492','10258762','10260529','23123742','34834146','3612867','8766076','21930833','24792061','8687127','3606090','8365395','8424477','8366242','24691248','17541915','17606105','31263584','27697228','36227900','5763105','10125912','26794000','5785831','5784912','5785268','5782311','5786617','8389264','8390079','8390564','17588707','9752437','9753855','17584431','29008300','5785684','5348909','8529649','8487670','36227732','30350399','30412162','32363502','11900616','8709541','8632060','8707910','8609557','8664318','22934786','23118562','34832404','23119630','35551148','22843787','34835847','20266891','22431072','12939165','35547585','33608847','35547571','6643146','8366013','8366727','6641778','6083893','28928105','27501902','36150331','6633725','27696829','5773421','5760485','5760804','35607601','5930891','5918208','5769930','10125913','10125940','10136776','10136781','5296112','5296115','5787183','5785215','5782635','5786868','8388034','8388586','8390458','22747914','17588136','5786530','26491304','22749072','22750807','17526911','17586993','19336947','5348077','5350021','5649084','36219308','10161305','8614206','8614875','10255780','10257242','22845053','30015633','10265265','22845126','3623793','3604690','3596077','36289483','8716732','12939167','26176066','14904849','8366305','6637503','6632753','30208160','27398443','36227849','5762261','5901801','36158261','28404432','28293786','30571834','30602995','27279616','5769219','5762477','5760099','5296056','5296093','36206153','5769313','10125926','5785798','5784772','5782548','5786499','8388344','8389253','8430207','9749577','15427478','15428185','17526956','36157269','22748274','5785449','11148554','5648355','5649259','5561100','8340726','8714378','10262405','10262568','10263188','10264113','10257362','10259657','21968120','21713070','21713990','34832348','35684258','10264267','3610742','3588734','8717438','8764451','9111477','33608876','23214126','23214045','23935362','30256683','6084698','8365980','8366490','8424415','8365131','8380718','6641629','36227883','36228448','27335674','8366640','30213790','36205893','5773465','5775997','5760446','5883564','35607611','28288086','28387971','28393415','5901704','5760572','5759378','5296018','10125931','10125927','5773189','5785605','5786322','5786464','8389058','8389103','8389951','10236103','9754881','15428965','15427490','15428226','15427519','15427534','15428076','15428080','22747666','22816568','22816804','22816864','35063902','5785073','5348545','5348813','5354618','5346305','5649150','5649739','5657571','8339994','8451553','36105286','8638821','11899159','10160945','8710927','8689747','8664233','10184788','10255870','10263941','10258132','10265179','10264579','22935200','22889516','36304167','10264906','17386979','23062663','3590047','3628694','3607686','3595026','8796861','8718138','8762602','20424468','33609389','33226527','32102960','32101703','31607695','21173896','30258726','8366782','8365510','6079271','6083436','8364989','8380534','6081812','6632965','6641191','25078826','24698333','36143363','36177688','36227856','8365601','31314118','27702367','28137226','28451808','36208788','5756883','5883285','5921461','36158268','32539857','28403722','30570622','30570928','30602363','30605823','27280249','5904912','10125928','10125930','10136780','10125901','5922548','5296084','10125915','5760077','5782817','5786029','5782509','5784365','5786394','5786273','8387346','8015973','8216623','8389884','8389993','8390261','8216212','22751300','10236097','9781645','34178280','15418808','15427487','15428192','15427526','15428146','15428151','22749554','22750520','17588451','36284409','29119258','11148785','9154760','5560849','5665558','5665657','5667689','8509482','8527387','8487999','8506233','8507676','8529037','8534109','21967038','16591236','11898509','8710362','8711190','8682382','8657241','8654797','8689402','8659126','8657706','8663553','8664236','11864117','10184278','10255164','10263350','10263532','10257713','10258058','10262221','10264529','10264641','23162268','24664310','21713199','21713586','34832244','36227509','33995099','17386993','3606759','10524458','8695647','21930748','11406515','10524273','8765827','21930771','35536188','33608897','33609817','33226515','33603207','26250201','33227292','36180417','6084952','6083894','8366383','17606093','27501726','36227826','36177684','25035753','8365432','36227818',
'30208526','31388336','32100146','28136882','28451645','30114540','36204948','36206157','5760640','5921116','28293288','28392630','28393573','30603969','5901698','5901706','5767884','5907828','5909273','10125903','10125923','10125933','10125943','5916823','10125917','5785750','5787145','5785295','5785421','5785481','5783164','5782216','5786065','5786143','5786198','5784647','5786176','8387222','8217074','8388745','8390013','8390670','32117016','9749134','9736470','10236379','26488340','36114413','32116772','22748077','22749614','22749709','22750159','22747074','22747881','22747952','22751416','17585460','17589035','8387554','36285350','11148789','5648433','5650406','5666558','5666933','5667011','5665748','5656366','5560718','5682156','5667747','8461543','8530372','8534123','8514953','8527026','8507967','36227512','30014342','22271953','16582834','16591249','16591280','16591312','16591848','27873773','34834214','32361930','32363447','10139265','10161427','8711019','8711347','10139826','8666543','8627391','8715680','8690463','8707653','8708848','8680008','8663154','8663492','8684520','8665138','8688330','10202760','10187238','10255683','10256011','10263881','10256108','10261284','10262317','10265007','10265056','10264454','10264784','23120068','23116951','23118420','21713009','34832198','34832298','35612728','23121605','17378557','17386032','17386972','11407374','8762651','35536204','35116330','36104441','33609165','33603223','33603258','33604338','27268408','33226991','33227278','30258208','21930861','6081863','6085075','6643372','8366140','8366257','8366613','8365288','8313455','8365949','6078638','8365674','36227862','36227870','36227884','36228417','27336776','24697206','24697358','36162626','30663015','31114111','31388092','32102517','28798061','28451121','30112563','5764338','5765130','36204964','5883134','5883261','5883302','5762796','5922786','5773086','5773095','36157248','32632937','32527236','28404173','28404734','28405383','28388597','30571199','5904392','5762296','5759884','10125902','10125908','10125925','10125935','10125936','10125937','5918796','28396781','5785599','5782727','5783099','5784752','5785733','5785980','5782361','5785154','5785332','5782594','5781660','5781976','5786340','5786431','5784510','5786730','5784848','8387199','8387409','8388395','8389070','8389830','8390614','8390647','36278465','36307687','10236266','10236315','9755488','9754277','26361353','34178269','36114609','33253049','15428189','15428212','15428087','15428105','15428164','15427512','22748053','22748143','22748220','22748537','22748683','22750371','22750391','22746426','22747256','22747706','22747751','22816061','22750894','22751076','22751452','19336147','22058945','21891095','29116553','5785240','9154544','9154759','5649418','5666630','5667951','5658568','8340412','8340825','8534163','8535460','8510258','8513797','8490326','8491089','8491223','8491893','8451740','8487567','16591211','16591274','16591303','36107279','35802942','8715984','8615916','8628518','11900394','10139361','10161876','10163019','8709284','8711407','8682717','8682713','8657162','8654861','8655137','8630890','8630868','8693811','8654449','8680666','8663169','8683754','8683757','8683768','8682048','12632842','12632876','12632880','12632886','12632890','11861658','10184415','10185881','10262700','10262783','10255421','10263287','10263606','10263987','10264062','10260643','10260968','10264833','10264965','10265032','23070607','22845213','22846529','21713725','10265099','10265234','35613613','11899726','35678489','17378509','17378522','17379898','30321460','34835042','3632486','3632877','3625604','36277746','11407775','36144445','23213955','20738377','11407062','10524594','21174329','21173855','21173928','21930844','21930907','21930797','21174272','35535823','35536176','35536193','35536215','33227321','33603132','27274492','27656640','28090974','27534773','26181591','26959376','36277767','35536239','30238611','30242438','6082117','6085029','6082540','6083166','8366053','8366094','8366465','8366751','8313556','8365884','6083446','6084033','6079042','8313941','8313974','6082322','6080899','36227860','36227880','36227850','36227852','36227854','36197446','36127079','27338302','24697584','27502389','36172751','25033443','30293725','30661663','30662108','30663134','30663655','31386999','32101807','30115462','30116103','5762922','5758259','5765578','5765585','5765846','5759285','36205892','5773647','5773752','5773785','5776005','5883115','5919302','5919853','5921596','36157245','36157247','36158267','36158271','28405677','28294135','28294232','28393183','28397963','28400945','28401024','28402027','28403882','36316902','5883875','5769747','5761684','5761945','5762352','5759361','5757628','10125911','10125921','10125929','10125934','5919483','5758602','5762671','5762942','5765685','5773731','5775971','5883786','5904710','5785698','5782974','5787003','5784854','5785460','5785464','5785573','5784317','5786072','5786247','5786533','5786578','5784574','5786732','5786813','5786606','8387746','8388215','8215761','8388348','8388876','8382777','8390518','8390528','8389882','36277159','36306705','36304203','32116543','22750260','10236435','9752774','36114440','36113354','36113508','33252217','36114347','36114380','15428959','15428971','15428182','15428218','15428100','15428116','15428159','22747994','22748319','22748490','22749131','22749190','22749673','22749733','22750435','22747134','22747453','22750551','22750634','22750737','22750967','22751130','22751196','17526882','22058859','22059034','22018162','36114579','22057141','32713243','32250164','29086047','29117310','5648684','5648875','5560986','5669295','8340311','5671244','8433245','8433363','8434137','8461679','8536561','8534518','8535412','8534552','8509392','8513084','8489261','8514085','8489727','8490068','8479011','8483434','8450742','8506560','8506753','30324258','21965195','18902996','16591323','27876052','32363424','8717093','8615975','8612358','8612403','8638824','8711888','10138637','10138836','8709654','10140041','10140633','10140775','10149773','10151668','8658395','8665351','8615381','8662119','8660041','8610034','8682726','8658880','8614805','8607949','8690245','8690575','8693760','8692524','8709036','8659244','8716153','8716248','8707173','8707270','8679975','8680362','8664405','11865624','11894169','11859992','10186837','10187298','10263030','10255538','10255610','10255953','10263710','10264162','10264224','10260740','10260900','10261870','10265134','10264407','10264692','23064365','22934950','22889307','23120012','23121811','23063796','23064036','24665740','24666897','24664854','23122348','23123256','23116399','22848277','22843436','21713156','21713355','35488778','35611193','35616430','35619901','35620540','35677475','35613152','16582683','17379905','17380193','17381054','17386022','17386028','33996582','3625074','3625892','3626112','3603274','36180414','36180415','11406758','10524726','10524455','8695156','8687751','8687837','8766926','8765606','14941850','11407235','10524559','8796777','21930853','21930898','27606727','12940264','12842514','35536226','35536231','35536154','35740589','36107072','36104428','33226501','32108751','33227249','33227333','27231766','25889074','26126749','35548297','30293771','6084414','6084543','6084878','6082212','6082432','8366352','8366661','8366678','8365540','8313693','8313742','6082955','6083034','6083131','8365182','6084327','6085179','6085269','6085443','8365085','8365248','8313866','8313909','8366210','6084272','36227873','36227875','36227876','36228476','36228519','36228522','36228523','36231766','36231767','36228418','36227853','36227324','36227350','36197449','36206584','36206603','36206606','27338723','27502383','36227830','36227832','36227590','36227839','36227601','36227304','36206571','36206578','36206579','36227886','36149739','36149197','36162639','36172749','36177670','36177682','36172726','17541918','6081615','30212935','31332321','31387487','32101228','30115826','5757062','5757176','5757270','5757433','5759483','5769433','5773038','36205895','36205903','36204950','36204963','36204965','36204967','36206151','36206154','5773292','5914961','5773392','5916928','5773708','5882984','5883049','5919912','5920910','5885323','5921251','5901695','5901869','5902311','5921649','5922084','5904318','5922250','5905151','5905578','5923358','5909408','5773185','28405196','28406032','28398594','28398658','28402487','28403490','5921817','5912809','5883920','5773122','5918536','5902263','5768828','5770418','5770488','5760986','5762060','5919770','5909678','5759213','5760028','5755876','5758131','5920556','5760347','5922043','36316859','5767828','5782768','5785676','5783146','5782435','5782474','5781915','5782113','5784416','5786228','5786642','5786650','5786681','8387565','8388151','8217589','8389591','8390346','8388511','8388755','8388780','8382399','8384389','8389745','8388468','36323733','36323738','9748507','10236195','9754072','26485594','36113296','36114594','36114607','36113502','33252849','36114343','36114426','36114613','36114620','32117279','15427502','15428197','15428205','15428207','15428110','15428112','15428167','15428172','15428175','15427505','22749294','22749961','22747306','22747782','22747848','22750471','22829762','22057444','22058700','22020171','21891382','36157268','14296764','22058374','29117755','29012210','29116833','5785135','5785340','5648589','5666444','5666531','5652432','5653240','5657497','5654797','5668497','5669002','5669230','5669428','5665963','8340139','5671743','5671964','5667643','8433053','8433489','8434337','8460295','8434458','8460659','8461961','8530616','8530800','8530905','8533717','8533940','8534192','8535397','8535573','8536385','8534871','8509488','8509652','8488753','8513439','8513685','8489171','8514024','8514297','8514690','8514818','8490538','8526964','8491298','8505375','8508460','8508527','8508721','8508811','8508817','8508949','8485114','8509154','8450875','8450999','8451184','8451636','8452830','8487816',
'8487911','8527548','8505745','8505879','8528645','8529165','8508360','36304063','36227613','18902990','16592435','16592439','32363528','27875726','27877427','27874206','35943959','32363338','32363466','8715884','8716999','8716121','8717114','8758683','8615718','8610813','11899901','8714695','8714792','8714799','8714861','8714939','8715007','10152407','10135843','10140406','10162505','10162576','10163273','10163323','8709904','8710020','8710217','8710540','8710637','8710825','8711551','10140167','10140258','10151147','10151820','8658362','8658063','8682150','8682573','8666181','8665483','8637852','8615239','8637855','8615318','8656107','8637093','8637085','8657225','8637089','8662615','8655568','8634572','8661725','8634597','8656104','8654947','8655423','8610352','8658888','8682739','8682852','8682720','8613841','8629790','8630097','8614122','8630101','8612777','8613219','8614328','8614329','8614736','8629785','8614355','8606669','8689447','8689763','8689932','8690071','8690195','8690232','8690288','8692812','8692882','8692937','8692986','8691573','8709217','8631547','8639253','8659236','8631774','8655416','8654219','8659485','8716173','8716190','8716215','8706988','8707112','8707179','8707544','8707613','8707334','8707403','8707735','8708434','8708808','8708559','8608273','8608472','8607972','8609202','8608976','8684937','8679968','8680002','8685807','8680367','8680371','8680376','8680555','8680668','8681589','8680669','8681592','8680672','8683759','8683762','8684017','8684130','8679126','8684361','8679134','8679139','8684595','8679145','8679507','8684860','8682145','8665135','8664495','8663727','8664501','8682144','8658050','8682148','8663732','8664512','8663720','12632827','12632831','12632865','12632875','12632878','12632881','12632883','11015726','11019132','11898241','11895029','11861751','11864006','10184492','10184578','10187002','22934822','22888719','22843697','22889904','22844915','23161855','23119028','22891892','24666571','24666623','23122111','23123475','23116159','23116833','23117709','22890887','22804681','21713945','35177551','35486705','35490132','35553780','35614244','35616597','35679895','35681296','36102849','3621347','3622635','3635660','3628091','3631035','3613697','3617607','3617910','3611558','3596115','3600868','3600946','3601080','3601420','36277771','36277853','36277589','36253602','10524715','10524719','8691211','8687747','8765852','8765798','10524349','10524682','8766791','8796475','9111487','21174320','21930881','21174255','21174259','21174273','21174277','12940330','12940260','12939278','35536256','35536268','35536288','35547927','35535814','35737034','35738184','36104454','36104474','36109810','36109831','32247097','32248164','32311889','32530070','32546512','36180122','26346418','26189915','36144435','6084473','6084508','6081777','6084654','6085150','6082324','6083278','8366690','8365349','8365214','8366542','8366581','6083334','6083738','6084157','6081497','6082717','6085209','6085289','6085355','8365647','8365712','8365792','8365844','6081115','6081218','6082080','6082428','6080681','36227859','36227865','36227866','36227872','36228421','36228427','36228432','36228433','36228434','36228446','36228471','36228491','36228492','36228493','36228498','36228505','36231763','36227842','36227844','36227845','36227848','36227851','36227314','36227322','36227648','36227653','36227709','36227710','36197414','36197419','36197438','36197441','36197444','36197453','36206583','36206617','27343293','27502661','27502768','36227823','36227827','36227591','36227834','36227836','36227604','36227608','36227302','36227307','36206507','36228455','36228466','36227624','36227625','36227634','36227311','36227312','36149701','36149710','36149715','36149727','36149742','36149179','36149185','36149188','36149190','36149194','36149196','36172738','36163999','36176417','36177667','36177673','36177680','36177689','36172727','36227867','30293922','30663305','30663486','31718875','33241961','28888983','28449493','28889183','36103581','36103726','36206123','36208773','5908307','5762819','5762915','5757481','5763644','5764577','5758185','5764747','5764933','5758507','5758718','5766900','5767553','5759557','5769037','5769903','5770686','5770709','5770967','36316895','36316900','36205879','36205899','36205913','36205927','36204949','36204962','36204970','36206155','5913753','5773311','5773364','5915922','5916451','5773527','5773549','5773644','5773657','5773690','5773767','5773792','5760403','5917252','5917499','5917574','5917930','5918644','5919651','5760741','5760839','5761068','5883597','5761377','5919952','5920280','5883784','5883853','5920764','5883938','5921029','5921225','5921363','5901818','5921398','5901883','5901951','5921538','5902706','5921998','5903835','5922435','5773054','5907366','5909949','5910436','5911805','5911981','5773171','5773196','5773211','28404591','28405533','28405893','28293986','28397088','28397451','28400435','28401083','28402628','28403768','5901991','5884317','5901803','5773346','5903281','5769130','5769811','5770436','5761887','5906195','5883070','5759809','5759878','5763138','5759902','5759976','5760055','5760111','5760192','5916147','5758303','5758807','5759313','5920807','5760377','5921056','5760596','5921193','5921692','5761767','5762579','5763036','5764140','5764827','5765204','5765280','5883568','5902168','5906066','5910310','5785662','5785922','5785285','5785518','5782684','5784382','5782175','5786462','5786577','5786620','5784459','5785078','8388652','8389605','8390042','8390112','8389393','8383056','8383058','8389619','8389705','36323720','36303362','36306782','36303351','9747778','9745803','10236560','9755890','9753175','36114432','36114438','36114534','36114536','36114540','36114548','36114582','36114585','36114589','36113317','36113340','36114598','36113357','36113499','36113512','33251467','33252617','33252707','36114366','36114367','36114386','36114391','36114419','36114618','32114226','15428092','15428178','22748180','22748626','22749444','22749878','22749914','22751260','22751379','36158055','14296532','14296902','36114393','36128210','36128282','32250882','32712316','32712554','32712972','29117001','29117537','5309736','8533536','8533796','8534313','8534342','8535503','8535571','8534926','8535272','8509458','8513336','8488902','8526787','8527072','8478609','8527126','8527348','8479547','8528331','8481212','8534081','36227703','36219370','36219537','36219596','36227712','36227721','36227584','30326648','30349582','30351144','22207421','18902984','16591293','32361783','27874906','27875102','27875655','27875896','27875947','27876260','27877623','27877701','35938234','32361985','32363377','8715912','8612191','8627003','8610807','8615685','8628521','8710711','8658058','8665249','8658198','8682244','8638234','8632420','8658418','8627398','8612769','8612817','8615026','8709277','8715695','8689553','8693580','8659240','8631779','8716225','8708739','8608512','8609582','8608972','8609573','8608970','8662727','8662736','8662942','8682141','8664641','12632826','12632834','12632868','12632813','11867134','11858435','11859774','11860054','11860202','11863801','23064216','23064924','23064996','23065085','23066127','22935050','22888616','22935283','22935681','22889412','22844600','22890381','22890532','23118618','23118696','23161950','23119860','23122012','23063911','24665537','24666373','24666704','24667267','24664148','24664421','24664927','23122194','23122601','23123087','23123389','23116207','23117151','23117281','23117333','23118214','23118298','23118346','22890811','22890984','22846072','22891251','22891448','22804523','22843532','35489449','35489773','35614779','35615727','35615818','35616246','35678188','35684644','36101928','36102792','36103710','36106069','35612452','35617035','3589653','3590076','3631821','3632538','3632578','3620583','3615245','3616580','3606771','3606973','3608809','3608881','3608994','3609568','3613496','3593716','3594074','3598931','3588453','36277766','36277773','36277694','36277697','36277762','36277727','36277714','36277655','11407484','10524732','10524739','10524460','8696992','8689370','8688719','8696624','8764743','8717028','10524342','8765237','8766514','8766744','8797431','9110991','8797647','8717996','21173897','21173905','21930871','21930892','21931360','21930805','21930818','21174243','12842327','35547960','35536242','35536251','35536262','35535666','35536298','35536307','35536312','35536317','35536325','35535737','35535776','35535784','35535798','35548613','35536162','35536182','34181846','35737691','36107083','36104406','36104463','36104479','36109852','32317334','33696300','32534497','33136528','32550529','32396617','32143591','32526587','36180114','27490652','26471948','26476366','23935242','36144434','36144440','36144447','36144457','36144458','30293278','35535844','12939186','30241712','30231472','8365758','36227857','36228420','36227868','36227869','36227878','36228422','36228423','36228424','36228425','36228426','36228428','36228429','36228430','36228431','36228435','36228436','36228437','36228438','36228440','36228441','36228442','36228443','36228444','36228445','36228447','36228449','36228472','36228473','36228474','36228475','36228477','36228478','36228479','36228480','36228481','36228482','36228485','36228486','36228487','36228488','36228489','36228490','36228494','36228495','36228496','36228500','36228501','36228502','36228503','36228504','36228506','36228507','36228508','36228509','36228510','36228511','36228512','36228516','36228517','36228520','36228521','36231764','36227843','36227847','36228419','36227635','36227316','36227637','36227318','36227638','36227319','36227639','36227640','36227320','36227321','36227644','36227645','36227646','36227326','36227328','36227651','36227349','36197424','36197429','36197434','36197435','36197440','36206581','36206586','36206589','36206592','36206594','36206596','36206600','36206602','36206609','36208459','36206613','27502159','27502193','36227711','36227817','36227829',
'36227833','36227592','36227835','36227594','36227597','36227288','36227290','36227599','36227840','36227600','36227292','36227294','36227602','36227297','36227298','36227300','36227606','36227301','36227609','36227611','36227306','36227308','36227614','36206512','36206516','36206523','36206528','36206530','36206536','36206540','36206543','36206549','36206557','36206558','36206560','36206561','36206562','36206565','36206566','36206568','36206576','36228450','36228451','36228452','36228453','36228454','36228456','36228457','36228459','36228460','36228463','36228464','36228465','36228467','36228470','36227626','36227627','36227629','36227630','36227631','36227632','36227633','36227313','36227309','36227615','36227310','36227616','36227618','36227621','36227622','36149697','36149699','36149702','36149704','36149705','36149706','36149707','36149709','36149712','36149713','36149718','36149720','36149721','36149722','36149724','36149728','36149729','36149730','36149733','36149735','36149736','36149737','36149180','36149181','36149186','36149187','36149189','36149192','36149193','36149195','36149695','36149696','36162652','36162653','36162655','36162656','36162657','36162659','36162661','36162662','36162663','36162664','36162668','36172729','36172731','36172732','36163989','36163990','36172736','36172739','36172742','36163991','36172744','36163994','36172746','36172748','36163995','36163996','36163997','36163998','36164000','36164002','36162575','36162577','36162578','36162581','36164006','36162583','36162585','36164007','36162586','36164010','36162587','36164011','36162589','36164013','36162591','36162593','36164014','36162597','36162599','36162601','36162602','36162603','36162604','36162605','36162607','36162623','36162627','36162628','36162630','36162632','36162634','36162636','36162637','36162640','36162643','36176411','36176413','36176416','36172753','36172754','36176418','36172756','36172757','36172759','36172762','36176419','36172763','36172764','36176421','36172765','36176422','36176423','36172766','36176425','36172767','36172768','36176427','36172770','36172772','36172773','36172774','36176428','36172775','36176470','36172777','36172778','36172781','36177645','36177647','36172782','36177649','36172966','36177650','36177662','36177664','36177666','36177668','36177672','36177675','36177677','36177679','36177681','36177685','36177690','36172968','36172969','36172970','36177652','36172972','36172975','36177653','36172976','36172977','36172978','36172979','36177655','36177658','36177659','36177660','36162670','36162671','36162672','36162673','36162675','36162676','36172724','36162677','36172725','36162646','36162647','36162649','36162651','36162669','30293580','36197437','36164004','5763563','5757712','5763927','5757809','5758012','5758857','5765662','5758995','5759075','5759138','5767154','5759476','5767259','5770735','5772997','5773223','5773236','5914122','5773270','5914374','5914531','5915543','5916649','5773483','5917168','5759921','5759925','5760219','5760288','5882942','5882963','5760634','5918996','5760694','5883192','5760929','5883435','5761134','5761298','5883641','5920163','5920462','5884086','5920987','5884153','5884314','5921091','5884824','5885202','5921289','5902356','5921721','5902405','5921843','5921902','5902895','5905335','5906614','5773063','5773072','5910653','5910987','5773120','5911479','5773130','5773148','5912305','5912490','5912626','28397546','5915104','5915409','5773158','5773245','5765043','5902534','5767843','5773675','5761547','5759181','5920694','5759538','5759776','5762830','5759816','5759848','5759870','5759909','5759950','5759954','5759986','5760041','5760188','5758342','5917785','5918321','5757554','5919100','5757913','5919234','5920087','5920231','5758892','5920396','5759043','5759331','5920636','5760211','5920735','5760314','5920862','5760534','5761223','5921777','5761502','5762160','5762504','5763185','5763465','5764633','5767683','5773103','5773198','5883010','5884733','5902086','5902212','5902235','5904805','5908782','5913371','8388900','8382193','36306672','36306752','36303363','36306777','36303374','36306781','36306789','36306798','36307667','36307686','36300245','36305309','36305317','36306516','36306587','36306625','36297368','36303335','36114433','36114522','36114526','36114538','36114543','36114575','36114576','36114586','36114590','36113325','36113343','36114599','36113358','36113359','36113366','36113389','36113476','36113485','36113496','33250802','33251671','33252324','36114370','36114374','36114378','36114405','36114622','36123369','36158259','36170305','36173180','36113375','36128159','36128170','36128188','36128239','36128274','36128288','8453880','8453954','8454065','8454347','8459711','8459942','8460072','8460208','8460553','8460861','8460941','8461003','8461632','8461846','8461915','8530859','8531174','8533494','8533606','8533680','8533934','8534038','8534304','8534448','8534505','8534509','8534512','8534514','8534519','8534520','8534523','8535577','8534525','8534528','8534954','8535020','8535054','8535141','8535193','8535245','8512724','8512898','8488605','8488836','8489388','8489802','8489895','8490003','8490206','8462010','8462053','8490608','8526589','8462105','8490626','8477280','8478314','8478491','8491690','8478702','8478867','8527482','8479164','8481281','8482061','8484320','8484751','8508992','8509078','8484987','8509164','8485329','8485421','8452648','8452939','8453053','8453178','8453322','8453706','8453798','8527810','8479979','8527899','8480028','8528121','8480142','8528189','8480216','8480314','8528247','8480701','8480795','8528706','8480915','8529396','36304049','36304070','36304079','36304103','36304112','36304125','36304135','36304166','36341823','36262803','36262818','36227662','36227668','36227682','36227504','36219322','36219328','36219364','36219554','36219565','36219571','36219577','36219586','36219655','36227725','36227534','36227537','36227561','36227596','35566722','35876315','18903004','32361806','27874569','27874640','27875035','27876137','27877270','27877334','27877463','27877529','27873534','27873633','27873889','27873943','27874073','36107154','35778878','36112268','32361834','32361863','32361887','32361905','32361956','32362022','32362061','32363360','32363400','8717234','8715704','8715855','8715871','8715939','8715971','8716016','8716022','8716051','8717021','8716083','8716107','8717266','8639067','8610894','8629499','8626892','8629500','8629502','8627185','8612448','8638451','8628805','8638825','8628806','8638828','8610825','8629496','8638450','8638447','8615542','8638444','11900269','8711793','8711933','8714770','8714824','8709309','8709668','8709764','8709872','8710087','8710170','8710272','8710912','8711274','8711514','8711596','8711690','8682196','8658059','8657671','8637730','8637849','8637853','8638230','8638232','8627882','8638236','8628507','8662114','8655138','8656106','8636574','8662442','8657227','8637391','8637083','8628513','8662291','8662692','8637398','8657229','8637396','8636527','8659757','8655852','8634584','8655898','8634590','8654906','8655899','8654911','8655902','8654916','8635924','8655905','8659745','8632419','8655428','8635936','8635928','8635933','8654606','8632414','8656103','8659749','8655496','8632426','8610477','8658885','8627395','8613843','8612628','8613932','8630094','8627864','8614060','8612771','8627866','8612776','8614139','8627874','8614142','8630106','8614145','8630396','8614327','8630463','8630895','8614598','8614602','8615094','8615184','8602547','8601963','8627310','8613358','8629787','8612517','8613840','8627394','8629788','8613271','8630892','8614590','8614594','8602012','8613268','8614330','8693669','8715614','8631544','8639250','8631549','8631552','8658889','8639071','8639075','8639076','8639256','8631773','8655367','8654129','8655418','8632066','8659503','8654223','8632058','8659241','8655143','8639254','8631770','8655148','8659495','8654221','8659489','8716161','8716770','8716533','8707431','8708983','8709074','8708590','8708645','8608463','8607951','8607975','8608470','8608466','8608968','8609566','8685742','8662734','8662735','8680381','8662902','8680600','8681587','8663158','8663164','8681594','8657710','8663396','8657715','8680705','8657731','8663716','8684271','8684709','8664234','8657857','8657806','8664230','8658043','12632818','12632820','12632825','12632829','12632835','12632840','12632862','12632866','12632873','12632874','12632812','12632815','11865795','11900110','11859599','11859882','11859933','11860300','11861207','11861572','23065238','23066644','23070423','23070481','23070535','22934867','23070675','22934911','22935019','22888492','22935116','22935135','22935162','22935238','22889616','22889793','22843867','22890131','22890273','22844779','22890477','23161722','23119541','23120179','23120271','23120362','23121412','22891760','22892185','22895423','22895478','23062432','24665333','24665592','24665658','24666228','24666296','24666497','24666538','24664388','24664956','24665053','24665275','23122268','23122666','23122742','23123572','23123672','23116486','23116672','23117006','23117076','22845378','22845554','22891065','22846152','22891178','22846248','22846356','22846449','22804095','22804253','22803523','23118101','23120443','35487311','35487594','35488612','35549086','35550798','35553190','35610674','35613066','35613826','35615145','35616788','35617344','35617776','35680385','35680863','35681899','35684460','35684867','36101905','35489101','3589708','3591763','3634428','3625449','3626414','3635062','3635653','3627325','3627686','3629136','3620785','3614591','3615860','3616734','3616953','3602641','3604267','3604541','3606805','3607940','3610463','3610538','3610947','3612112','3594231','3597763','3599265','36277846','36277768','36277770','36277772','36277775','36277603','36277606','36277685','36277687','36277695','36240960','36180416','36277566','36277732','36277752','36277753','36277758','36277840','36277841','36277842','36277843','36277845','36277874',
'36277728','36277717','36277650','36277652','36277653','36277654','36253605','36253614','36186042','10524720','8717566','8694971','8695031','8689224','8688687','8688710','8692173','8695390','8716766','8687380','8687150','8687584','8695410','8764904','8695572','8696112','8766856','8766484','8766276','8766306','8765984','8688772','8689223','8765804','10524673','10524319','10524320','10524323','10524583','8797124','8796497','8796467','8796558','8796856','9110968','8717852','8717878','8718167','8718018','8762229','8696776','8716853','8762577','21174324','21174335','21174340','21174342','21173902','21930867','21931376','21931390','21174318','22430365','22429912','12939318','12940329','12940333','12940335','12940342','12940247','12842325','12842328','12842332','12842334','35535758','35535835','35535853','35536150','36103706','36107985','36104386','32323868','32326521','32528098','33226883','36180113','36180119','27281751','27551808','26463903','26187976','26238384','26489446','36144420','36144424','36144426','36144429','36144430','36144437','36144456','36144459','35740847','35553597','35553602','35553607','36187057','30293025','30293500','30293587','30259541','30260231','30292715','8762394','8717540','36161249','8797379','36161309','7404376','7404125','28968060','8695350','8765485','8796742','8687232','8687400','8762779','7404707','7404552','28968831','8766374','7404146','7404158','8695565','8688050','8695083','8696284','36161167','36161109','8762490','36161152','8763611','8764228','8766513','36161245','8689401','8763730','8696456','36161253','7404211','8717920','8718067','8717670','36161127','36161154','36160945','8695299','8697054','8691524','8766288','8718066','8717462','36161168','8797482','36161157','28968777','36277583','8766146','8717601','8692368','8765821','8765825','8695544','8763734','36161189','36161128','8688254','36161213','8717584','8687110','36160418','8718195','7404228','8717043','8765899','8696785','8763371','36161151','36160512','8765358','8718286','36160840','8717562','8691315','8692194','8718110','8716867','36160780','36160815','36160746','8691528','36277616','36277660','8691785','8688229','8687624','8797349','8717778','36159357','36161033','7404447','8763288','8694958','8692142','8687155','8695989','8766474','36159442','36159333','36160590','7404247','36277690','36277691','36183247','36185063','8765980','8688507','8796528','8717638','8718251','8762551','8762897','36160752','36159317','36160797','36160465','36160597','8717206','3593952','8765348','7404144','7404270','36277992','36277711','8716889','8763989','8691154','8695316','8764380','8764081','8766503','8689107','8765682','8797465','8797240','8796459','8718168','8717527','36159447','36159449','36160769','36160771','36160778','36160375','36159488','36160378','36160390','36160442','36160681','36160701','36160844','36160865','36160568','36160571','36160630','36160644','36160338','36160526','8690665','7404469','7404149','7404167','7404177','7404200','7404527','7404549','7404154','36277671','36277673','36277794','36277891','36183227','36183314','36183253','36183842','36183585','36277712','36277720','8717376','8763698','8695251','8691702','8695054','8689233','8690581','8690876','8688119','8696670','8764544','8764391','8764444','8764095','8695988','8695525','8695658','8687269','8687319','8766331','8765913','8765777','8766533','8796471','8796891','8797522','8797594','8717855','8717618','8717983','8717784','8762160','8718094','8717458','8717219','8763594','36159348','36159430','36159440','36160645','36160661','36160996','36160371','36159491','36160393','36160397','36160413','36159322','36160256','36160795','36160857','36160858','36160714','36160716','36160751','36160899','36160534','36160537','36160582','36160489','36160501','36160503','36160505','36160637','36160506','36160365','36159476','36159325','7404282','7404341','7404445','7404357','7404131','7404137','7404465','7404482','7404510','7404250','7404654','7404655','7404526','7404529','7404576','7404603','7404613','7404614','7404615','7404630','7404262','7404268','36277847','36277769','36277852','36277855','36277582','36277667','36277669','36277684','36277610','36277696','36277628','36277569','36277570','36277571','36277806','36277737','36277809','36277740','36277820','36277743','36277823','36277835','36277761','36277868','36277896','36277662','36183224','36183225','36183426','36183319','36183428','36185031','36185032','36183554','36183445','36183375','36183476','36183478','36183494','36186748','36186756','36186671','36186679','36277645','36185067','36185019','36185374','8717388','8716965','8716928','8696936','8696962','8716946','8764258','8764300','8763896','8763937','8763181','8763393','8692672','8695231','8689383','8691792','8688684','8689016','8690539','8689074','8690654','8695279','8692519','8697076','8716685','8716784','8716806','8696754','8687133','8687578','8688010','8687367','8696238','8696661','8696685','8695474','8696367','8764897','8764562','8765374','8764701','8764720','8764211','8764244','8696080','8695607','8695062','8766450','8766860','8766894','8766251','8765858','8766277','8766118','8688783','8688835','8688205','8688845','8688900','8688498','8688946','8687642','8687267','8687901','8688596','8688631','8765987','8765880','8766364','8766016','8765907','8765917','8766032','8765794','8765396','8765463','8762377','8765399','8765407','8765841','8765267','8765316','8764843','8764856','8796593','8766691','8766826','8766748','8797372','8797348','8797391','8796983','8797080','8797451','8797170','8797171','8796669','8796494','8796474','8796789','8796843','9114423','8797622','8718279','8717927','8717952','8717861','8717968','8718156','8717791','8763109','8762364','8718332','8762383','8718062','8762175','8762512','8762409','8718245','8762415','8718085','8696764','8696571','8696601','8696888','8696197','8717715','8717827','8717728','8717444','8717739','8717453','8717529','8717312','8716848','8717323','8717468','8717196','8717346','8716873','8763214','8763223','8762645','8762684','8763256','8762925','8763274','8763102','36144421','28968873','36161113','36160765','36160789','36160966','36160234','36160240','36160249','36160460','36160472','36160587','36160639','36160529','36160530','36185884','3607449','7404441','7404446','7404463','7404602','7404288','7404303','7404307','7404311','7404319','7404322','7404323','7404336','7404339','7404344','7404346','7404355','7404360','7404361','7404389','7404409','7404414','7404423','7404424','7404432','7386795','7404453','7404136','7404462','7404143','7404471','7404475','7404153','7404484','7404488','7404489','7404157','7404494','7404160','7404165','7404168','7404172','7404194','7404202','7404225','7404227','7404231','7404234','7404235','7404236','7404242','7404248','7404251','7404513','7404515','7404517','7404519','7404520','7404521','7404524','7404534','7404536','7404543','7404544','7404546','7404548','7404551','7404558','7404562','7404568','7404569','7404570','7404571','7404581','7404590','7404592','7404608','7404611','7404612','7404616','7404622','7404625','7404628','7404632','7404634','7404636','7404638','7404644','7404645','7404646','7404648','7404649','7404652','7404255','7404259','7404263','7404271','7404278','7404284','36277848','36277849','36277850','36277851','36277776','36277777','36277854','36277779','36277780','36277857','36277781','36277858','36277782','36277786','36277788','36277789','36277578','36277579','36277663','36277581','36277664','36277665','36277584','36277668','36277586','36277588','36277672','36277594','36277596','36277675','36277597','36277677','36277599','36277678','36277602','36277680','36277604','36277609','36277688','36277692','36277693','36277617','36277620','36277698','36277621','36277699','36277624','36277700','36277625','36277703','36277627','36277704','36277705','36277630','36277707','36277708','36277631','36277567','36277568','36277572','36277573','36277575','36277576','36277904','36277905','36277907','36277909','36277981','36277982','36277985','36277986','36277987','36277988','36277990','36277995','36277791','36277792','36277793','36277795','36277797','36277730','36277798','36277731','36277799','36277800','36277801','36277733','36277802','36277734','36277803','36277735','36277805','36277736','36277808','36277738','36277739','36277810','36277812','36277741','36277817','36277742','36277744','36277825','36277745','36277826','36277828','36277830','36277748','36277831','36277749','36277832','36277751','36277834','36277836','36277754','36277755','36277756','36277837','36277757','36277838','36277839','36277759','36277763','36277765','36277866','36277867','36277870','36277872','36277873','36277876','36277877','36277878','36277879','36277880','36277881','36277882','36277883','36277884','36277885','36277886','36277887','36277888','36277889','36277890','36277894','36277895','36277898','36277900','36277901','36277902','36277657','36277729','36277998','36277859','36277860','36277861','36277862','36277863','36277864','36277865','36183417','36183308','36183418','36183309','36183419','36183310','36183420','36183312','36183226','36183421','36183313','36183422','36183228','36183229','36183423','36183230','36183424','36183316','36183231','36183317','36183232','36183425','36183318','36183234','36183235','36183320','36183427','36183236','36183239','36183321','36183322','36183429','36183240','36183430','36183241','36183323','36183431','36183242','36183326','36183243','36183432','36183327','36183244','36183433','36183328','36183245','36183434','36183329','36183330','36183248','36183331','36183249','36183332','36183250','36183333','36183252','36183339','36183340','36183257','36183345','36183361','36183258','36183259','36183362','36183260','36183363','36183262','36183365','36183269','36183366','36183270','36183367','36183271','36183368','36183272','36183657','36185368','36183658','36185021','36183819','36185022','36183820','36185023','36183821','36185024','36183545','36183822','36185026','36183823','36183546','36185029','36183824','36183547',
'36183826','36183548','36183828','36183549','36185034','36183832','36185037','36183833','36183550','36185038','36183835','36183551','36183836','36185039','36183552','36183837','36183553','36183838','36183839','36183555','36183435','36183556','36183436','36183843','36183557','36183437','36183844','36183558','36183438','36183559','36183845','36183439','36183560','36183847','36183440','36183561','36183848','36183562','36183851','36183564','36183853','36183566','36183854','36183441','36183567','36183856','36183571','36183442','36183443','36183575','36183857','36183576','36183447','36183449','36183583','36183858','36183584','36183450','36183603','36183451','36183604','36183376','36183454','36183607','36183458','36183608','36183378','36183460','36183609','36183461','36183379','36183610','36183463','36183611','36183380','36183464','36183612','36183381','36183613','36183465','36183382','36183614','36183466','36183385','36183615','36183468','36183387','36183616','36183472','36183388','36183617','36183474','36183618','36183389','36183475','36183619','36183391','36183477','36183620','36183621','36183480','36183392','36183622','36183481','36183393','36183623','36183482','36183394','36183624','36183483','36183395','36183625','36183396','36183485','36183626','36183397','36183487','36183627','36183488','36183398','36183489','36183399','36183490','36183295','36183400','36183491','36183296','36183401','36183492','36183297','36183402','36183493','36183298','36183403','36183299','36183404','36183495','36183300','36183405','36183496','36183301','36183406','36183538','36183302','36183407','36183539','36183303','36183409','36183541','36183304','36183542','36183413','36183305','36183543','36183414','36183306','36183544','36183415','36183307','36186553','36186740','36186554','36186741','36186742','36186555','36186743','36186556','36186744','36186745','36186557','36186746','36186558','36186559','36186058','36186750','36186560','36186059','36186751','36186607','36186609','36186060','36187313','36186610','36186061','36187318','36186611','36187319','36186062','36186613','36187320','36186063','36186614','36187321','36186064','36186615','36187322','36186065','36187323','36186066','36186672','36187324','36187522','36186067','36186673','36187523','36186070','36187524','36186676','36187525','36186071','36186677','36187528','36186678','36186072','36187529','36186073','36187530','36186680','36187532','36186074','36186681','36187537','36186682','36186075','36187538','36187539','36186683','36186076','36187540','36186685','36186077','36187541','36186686','36187542','36186687','36187543','36186078','36186688','36187544','36186079','36186689','36186080','36186690','36186081','36186692','36185586','36186082','36185587','36186693','36186717','36186718','36186727','36186728','36186729','36186730','36186731','36186732','36186530','36186733','36186734','36186531','36186735','36186546','36186736','36186547','36186737','36186548','36186738','36186551','36186739','36277709','36277710','36277632','36277633','36277635','36277713','36277637','36277638','36277641','36277718','36277643','36277644','36277647','36277648','36277649','36277722','36277723','36277724','36277656','36277726','36183222','36183223','36183369','36183370','36183273','36183371','36183274','36183373','36183275','36183374','36183277','36183279','36183280','36183282','36183283','36183286','36183288','36183289','36183290','36183291','36183292','36183293','36183294','36185554','36183868','36185068','36185555','36183869','36185069','36185556','36185070','36183870','36185071','36185557','36184045','36185178','36184046','36185179','36184047','36185184','36184055','36183628','36185185','36183629','36185186','36184056','36183630','36184326','36185187','36184327','36183634','36185189','36184402','36183635','36185191','36184403','36183636','36185347','36184663','36183637','36185348','36184664','36183638','36185350','36184702','36183640','36185351','36184703','36183641','36185352','36184705','36183642','36185355','36183643','36185356','36185007','36183645','36185008','36185357','36185009','36185358','36185010','36185359','36185011','36183649','36185361','36185014','36185362','36183650','36185016','36183653','36185364','36185017','36183654','36185365','36185018','36183656','36185367','36185020','36185041','36185492','36185042','36185493','36186043','36185494','36186046','36185043','36185495','36186047','36185496','36185497','36185045','36186048','36185498','36185046','36186049','36185500','36185047','36186050','36185048','36186051','36185501','36185049','36186052','36185502','36186053','36185050','36185503','36186054','36185504','36185051','36186055','36185505','36185055','36185506','36186056','36185056','36185510','36186057','36185057','36185512','36185058','36185513','36185059','36185514','36183859','36183860','36185060','36185515','36183861','36185061','36185517','36183862','36185518','36185062','36183863','36185519','36183864','36185548','36185064','36183865','36185549','36185065','36185550','36183866','36185552','36183867','36186098','36185375','36185706','36186099','36185376','36185707','36186100','36185377','36185736','36186101','36185380','36185737','36185381','36186102','36186103','36185781','36185382','36186104','36185782','36186105','36185383','36185783','36185384','36186106','36185784','36186107','36185385','36185785','36185386','36185787','36186527','36185388','36185788','36186528','36185789','36186529','36185487','36185883','36185488','36185489','36185885','36185490','36185886','36185887','36185491','36186041','36186083','36185589','36186084','36186694','36185590','36186695','36185591','36186697','36185592','36186085','36186707','36185692','36186086','36186708','36185693','36186711','36186087','36185694','36186713','36186088','36186714','36186090','36185695','36186715','36185697','36186091','36186716','36186092','36185698','36186093','36185699','36186094','36185700','36186095','36185701','36185369','36186096','36185703','36185370','36186097','36185704','36185371','36185705','8717505','8717374','8717507','8717525','8717574','8717251','8717404','8717407','8717596','8716895','8717605','8716949','8716926','8716927','8716932','8716990','8716992','8716935','8696976','8717002','8763843','8764255','8763608','8764126','8763856','8764269','8763859','8764287','8764139','8763893','8763653','8764304','8764145','8763924','8764321','8764166','8764167','8763772','8763986','8763797','8763299','8763999','8763311','8763142','8763331','8763180','8690878','8695393','8690885','8690912','8691137','8691160','8691537','8694967','8691180','8689351','8691606','8691638','8691685','8691688','8689375','8694997','8691402','8689379','8691418','8691747','8691425','8691763','8691459','8689392','8695016','8691788','8691514','8691802','8691816','8691841','8695041','8695044','8691898','8691899','8688653','8688989','8688675','8688997','8688685','8688690','8690514','8688695','8689033','8689235','8689238','8690611','8688712','8690619','8689244','8688733','8689261','8689084','8690643','8695073','8691967','8695729','8695261','8695710','8692366','8695330','8692374','8692419','8695370','8690858','8697043','8717046','8716756','8717070','8716772','8696705','8716696','8696709','8696720','8696733','8696735','8696737','8696741','8696751','8687886','8687389','8687435','8687449','8687453','8687462','8687118','8687122','8687500','8687508','8687139','8687513','8687153','8687524','8687555','8687540','8687162','8687180','8687182','8687225','8687241','8687249','8687345','8688646','8687834','8687365','8696613','8696199','8696904','8696214','8696221','8696906','8696909','8696632','8696223','8696635','8696914','8696242','8696920','8696923','8696658','8696932','8696664','8696669','8696267','8696274','8695822','8696280','8696688','8696701','8696302','8696307','8696315','8696332','8695943','8695443','8764863','8765346','8765353','8765357','8764372','8764632','8764932','8764639','8765368','8764936','8764681','8764390','8764170','8764722','8764403','8764182','8764191','8764222','8764109','8763839','8764248','8695971','8696376','8695491','8696390','8696016','8696035','8696036','8696061','8696075','8696451','8696093','8696458','8696460','8696468','8695941','8696475','8696142','8695615','8696476','8696165','8695654','8695675','8766853','8766854','8766444','8766628','8766652','8766654','8766655','8766883','8766892','8766908','8766913','8766915','8796450','8766092','8766093','8766099','8766281','8765946','8765863','8766125','8765957','8766127','8766304','8765872','8765964','8765874','8766320','8689273','8690675','8690739','8689277','8689297','8688785','8690771','8688803','8690787','8689334','8689350','8689210','8688857','8688212','8688853','8688860','8688870','8688877','8688888','8687591','8688902','8687595','8687598','8688921','8688315','8688486','8688488','8688943','8688504','8688957','8688977','8688337','8687641','8688512','8688513','8688362','8687268','8688363','8688553','8688562','8687280','8687283','8687299','8687923','8687303','8688611','8688612','8687317','8688439','8687743','8687324','8765876','8766328','8765877','8765988','8766363','8765886','8766012','8766195','8765890','8765912','8765927','8766035','8765941','8765701','8765705','8765708','8766046','8765709','8766059','8765723','8765737','8766084','8765763','8765379','8765385','8765637','8765799','8765391','8765648','8765649','8765655','8765657','8765454','8765811','8765398','8765820','8765660','8765476','8765409','8765414','8765672','8765421','8765494','8765423','8765686','8765429','8765495','8765435','8765500','8765694','8765561','8765257','8765570','8765591','8765596','8765283','8764779','8765326','8765336','8764488','8796899','8796950','8766389','8766395','8766517','8766795','8766405','8766719','8766735','8766835','8766595','8766600','8766433','8766614','8766756','8766847','8766623','8797303','8797358','8797368','8797061','8797392','8796987','8797075','8797102','8797413','8797419','8797428','8797138','8797447','8797458','8797474','8797200','8797477','8797219','8797484','8797494','8797499',
'8797277','8797278','8797279','8797280','8797521','8796625','8797295','8796486','8796726','8796461','8796465','8796469','8796504','8796748','8796509','8796765','8796519','8796479','8796797','8796520','8796815','8796822','8796535','8796544','8796547','8796860','8796568','8796571','8796576','9114651','9114653','9114718','9114394','9114419','8797523','8797526','8797541','8797542','8797555','8797557','8797581','8797604','8797611','8797639','8797644','8718284','8717832','8717838','8718288','8718114','8717613','8717844','8717614','8717746','8717865','8717747','8717866','8717758','8717624','8718143','8717867','8718149','8717625','8718152','8717763','8718164','8717767','8717642','8717894','8717643','8717667','8718016','8717683','8717788','8718024','8717414','8718323','8762347','8762475','8718324','8763120','8762484','8762488','8762376','8762492','8762495','8762379','8718169','8762496','8762146','8762382','8718187','8762151','8762500','8718192','8762397','8762169','8762399','8762511','8762192','8718214','8762407','8762514','8718076','8762295','8718237','8762299','8718078','8762301','8762520','8762305','8762525','8718252','8762527','8718253','8718093','8718260','8696479','8696483','8696489','8696506','8696510','8696780','8696515','8696523','8696800','8696842','8696808','8696528','8696812','8696849','8696820','8696549','8696553','8696827','8696869','8696830','8696569','8696166','8696831','8696873','8696577','8696875','8696580','8696880','8696584','8696585','8696605','8696609','8696591','8717690','8717415','8717694','8717416','8718042','8717824','8717435','8717720','8717436','8717723','8717724','8717081','8717737','8717445','8717446','8717109','8717457','8717526','8717149','8717153','8717302','8717306','8717460','8717176','8717319','8717179','8717464','8716851','8717327','8717181','8717332','8717476','8717338','8716865','8717494','8717221','8717362','8717559','8763449','8763460','8762830','8762598','8763241','8762872','8762636','8762890','8763506','8763249','8762903','8762760','8762947','8762963','8762964','8762416','8762985','8762781','8763006','8762783','8718292','8762427','8762796','8763022','8762318','8718296','8762428','8718299','8763030','8762803','8718307','8762432','8762326','8718308','8762438','8718311','8762327','8718319','8763087','8762330','8762457','16999876','35549013','35549014','35549019','35549028','35549034','35536173','35549036','22439335','22439347','36159351','36159352','36159355','36159356','36159359','36159364','36159365','36159426','36159427','36159428','36159429','36159431','36159437','36159438','36159443','36159445','36159451','36159455','36159459','36159466','36159469','36159471','36159472','36144416','36144441','36144448','36144452','36161266','36161281','36161043','36161282','36161049','36161283','36161056','36161287','36161075','36161300','36161077','36161078','36161317','36161080','36161318','36161088','36161323','36161091','36161325','36161093','36161359','36161100','36161104','36161107','36161108','36161110','36161112','36161114','36161115','36161117','36161123','36161138','36160754','36160758','36160761','36160646','36160767','36160647','36160648','36160770','36160649','36160652','36160655','36160656','36160779','36160657','36160660','36160783','36160667','36160787','36160669','36160670','36160794','36160672','36161141','36161147','36161148','36161150','36160901','36161165','36160908','36160917','36160918','36160922','36161170','36160931','36161175','36160932','36161184','36160934','36161185','36160937','36161201','36160948','36161209','36160949','36160964','36160965','36161217','36161219','36160967','36161225','36160968','36161227','36160970','36160973','36160999','36161251','36161014','36161259','36161261','36161022','36161262','36161024','36159485','36160374','36159487','36160376','36160377','36159489','36160379','36160380','36160387','36159492','36159495','36160391','36159497','36159500','36159502','36159505','36160402','36160403','36159507','36160404','36159508','36160405','36159509','36160406','36160412','36159510','36159557','36160416','36159558','36160228','36160230','36160420','36160231','36160422','36160424','36160232','36160432','36160438','36160233','36160440','36160441','36160235','36160443','36160444','36160238','36160241','36160243','36159316','36160244','36160245','36159318','36160246','36159320','36160248','36159321','36160253','36160254','36159330','36160255','36159332','36160259','36160260','36159335','36160261','36159337','36160262','36159338','36160263','36159344','36159346','36160335','36159347','36160673','36160678','36160680','36160798','36160799','36160682','36160805','36160683','36160806','36160684','36160685','36160808','36160686','36160809','36160687','36160690','36160821','36160826','36160691','36160694','36160830','36160698','36160842','36160703','36160707','36160709','36160711','36160859','36160864','36160717','36160718','36160866','36160719','36160867','36160731','36160869','36160733','36160870','36160736','36160737','36160871','36160738','36160873','36160740','36160877','36160741','36160878','36160744','36160879','36160881','36160750','36160889','36160892','36160900','36160535','36160549','36160550','36160445','36160551','36160447','36160554','36160451','36160563','36160564','36160453','36160570','36160454','36160456','36160572','36160576','36160457','36160577','36160459','36160578','36160579','36160580','36160466','36160468','36160584','36160585','36160469','36160470','36160586','36160473','36160593','36160480','36160594','36160481','36160483','36160598','36160599','36160487','36160610','36160488','36160611','36160612','36160613','36160491','36160616','36160493','36160617','36160494','36160618','36160620','36160496','36160621','36160497','36160622','36160498','36160623','36160627','36160502','36160634','36160636','36160638','36160507','36160509','36160640','36160641','36160514','36160515','36160516','36160517','36160336','36160518','36160337','36160519','36160339','36160522','36160340','36160524','36160342','36160344','36160528','36160345','36160349','36160359','36160533','36160360','36160361','36160363','36159473','36160367','36159474','36160368','36160369','36159477','36160370','36159479','36159484','36277682','36183646','36183647','36183648','36159329','7404210','8766481','36159503','8766432');
return $array;
    }



}