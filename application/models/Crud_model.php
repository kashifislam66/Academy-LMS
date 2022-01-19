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
'15593767','15593772','15594001','15594020','15593796','15593511','15736820','15737205','15736831','15737223','15204133','14819176','15203919','15204139','15203926','14819192','15203936','14819195','14819209','14819211','15203945','14819224','14819228','14819234','15203959','14819244','14819263','14819273','15203991','14819275','14819281','15203998','14819287','15204244','15204387','15204393','15204031','15204038','15204400','15204041','15204259','15204413','15204278','15204281','15204283','15204082','14819320','15204297','14819324','14819330','15204102','14819333','14819336','14819340','14819341','15204112','14819350','15204119','15032824','14741202','14741203','14741212','14741224','14741078','14741082','14741084','14741235','14741088','14741238','14741096','14741099','14741250','14741102','14741109','14741115','14741268','14741135','14741149','14741152','14740975','14741158','14740986','14740995','14741000','14740859','14740862','14741181','14741030','14740866','14740871','14740872','14741195','14740882','14740883','14740894','15204303','15204484','15204305','15204486','15204309','15204316','15204494','15204327','15204506','15204333','15204515','15204168','15204170','15204349','15204186','15204354','15204193','15204201','15204207','15204372','15204374','15204379','15204006','15204384','15594379','15594381','15594551','15666404','15594388','15594398','15594402','15594404','15594437','15594025','15708089','15594035','15594048','15594460','15594056','15594466','15594067','15594070','15708112','15708115','15594199','15594482','15594213','11140274','11140546','11140581','11141123','11141157','11141261','11128551','11128693','14819313','14819116','14740802','14740617','14740622','14740376','14740631','14740378','14740633','14740381','14740642','14740385','14740645','14740387','14740647','14740649','14740391','14740650','14740200','14740653','14740397','14740655','14740657','14740403','14740661','14740218','14740666','14740414','14740227','14740673','14740237','14740426','14740427','14740431','14740432','15708123','15736230','15708124','15736237','15708131','15708136','15736121','15736256','15736133','15736134','15736137','15708162','15708165','15708169','15594494','15594498','15708175','15594510','15708194','15594523','15708201','15594531','15594537','15708208','15594375','11791961','11820998','11821400','13904861','36317227','36317228','36301218','36301244','36301423','36302185','36283921','36300262','36300274','36300289','6157706','27624130','24800111','29429447','29429619','29429631','29429664','29428826','29429749','29428891','29429899','29430952','29428366','29429079','29429139','29973977','14346993','14339690','13518478','33729895','33729926','33730024','33729686','33729756','33729791','32815675','31604030','22543687','22545377','22533436','22533587','22533590','22533592','14347024','14345449','26806756','26806911','26807549','17672572','17672384','17672388','17672595','17672408','17672619','17672433','17672209','17672210','17672643','17672227','17672457','17672247','17672471','17672485','17672266','17672486','17672306','17672310','17672328','17672084','17672087','17672089','17672348','17672112','17671864','17672126','17671888','17671916','17671917','17672153','17671931','17671933','17672175','17671738','17671756','17672022','17671766','17672034','17671778','17671780','17671785','17671798','17671980','17672181','17671719','17671733','17672673','17672689','17672700','17672712','17672736','17672538','21707858','21708244','14346925','14346940','14345457','14347045','14347049','14391050','14348297','13659705','13662230','34419339','34417590','17670721','17671006','17670449','17671019','17670734',
'17671028','17670742','17670770','17670777','17670779','17670780','17670523','17670788','17670529','17671205','17671215','17671218','17671223','17671250','17671277','17671281','17671293','17671301','17670243','17670274','17670282','17670599','17670315','17670331','17670333','17670338','17670120','17670124','17670125','17671642','17671644','17671348','17671355','17671374','17671387','17671692','17671323','17671328','17671154','17671332','17671159','17670815','17670817','17671171','17671175','17670822','17671183','17670845','17670871','17670625','17670671','17670947','17670687','17670697','17670703','17670976','17670984','17671821','17671836','17671614','17671125','17671133','13267252','13268342','13240223','13269113','13240514','13269372','36117914','17670382','17670390','17670395','17670405','17669924','17670152','17670154','17669945','17670178','17670181','17670190','17669989','17670198','17669991','17670008','17669726','17670030','17670035','17669860','17669865','17665534','17669617','17669894','17669632','17669635','17669653','17665612','17669667','17669670','17669679','17669682','17669499','17665123','17665142','17665160','17664763','17669530','17664961','17664971','17665360','17670070','17669786','17669544','17669798','17669546','17669547','17665401','17665410','17665092','17665465','17665114','17665490','17665503','17665505','17669848','17665511','24799632','22400084','22288747','22308989','22253418','22154650','17664905','17664929','17664627','17664652','14346980','14345420','14339673','14329304','36220197','7092387','12453090','10858052','10858541','12376774','12384512','36220218','36220210','25210353','25210453','25210479','23281325','23280587','36125484','36125505','25210229','23279862','23279912','23281172','23280086','23280115','23280155','13239704','13267149','13269723','13239998','6129107','23280350','23280400','23280406','23280448','23280480','23279745','23279748','12066269','12066270','12066239','36217421','36217442','36217450','5279268','17670287','17671542','17671493','17671290','17672391','17672584','17672232','17672231','17671558','17671260','17671187','17671262','17669833','17672167','17669784','17670002','17672649','17671191','17664750','17672024','17670012','17669809','17664798','17672101','17670074','17669578','17665268','17670321','17670533','17669580','17671140','17671177','17669805','17670478','17669598','17669607','17670099','17672162','17669624','17670263','17669589','17670304','17672257','17672192','17671436','17669976','17665508','17669503','17671967','17671149','17669984','17669853','17671486','17671120','17672019','17671016','17671659','17669845','17669889','15586931','25210093','23280927','17672402','17672482','17672656','17672661','6141827','6126494','17669839','36173937','36117147','36176128','36132469','36141417','36141425','36144738','36144744','36108107','36144757','36108883','36154681','36117020','36117036','36117144','35848545','35905244','36262635','36273180','36273220','36286652','36273226','36273236','36273359','36173857','36173899','36173910','36282271','36282287','36282322','17671345','17672353','17671124','17665456','17672058','17669755','17670826','17664987','17671357','17669998','17671824','17671668','17664647','17671833','17670736','17670083','17672695','17672148','17664991','17670457','5397754','36176101','22572912','36298732','17671873','5743601','3873114','3873199','3872417','3873056','3882757','3882795','3881962','36224442','36220198','36228055','36226980','36264101','36278321','36278429','36278446','36281028','36278302','36269178','36277136','36273122','36316440','36306617','36265457','36265462','36267522','36263797','36263869','36262511','36262523','36262549','36230881','36249085','36232283','36182214','36225895','36204858','36204917','36204925','36199811','36202675','36205001','36198517','32114545','36164077','36198514','34431095','34431551','34066982','36106348','36114292','36106365','36114299','29718598','29718628','35745955','36106390','32852634','13692518','13582860','13560478','13692584','13692592','13582933','13582900','13582908','13560518','13560527','13560523','13560539','13560483','13560482','13560491','13697729','13719931','13720377','13728485','13550220','13550221','13550209','13500379','13500364','13500363','13500376','13068283','13068285','13068289','13068414','15029319','15029328','15029332','15032230','15032231','15032259','15032267','15032292','15032294','15032244','15380626','15380644','15380647','15379684','15379695','25402874','14939505','14939496','14939497','14939502','14903510','14939517','14892960','14903497','14892925','14892931','23724297','17538443','22319120','14892828','14892879','14892864','14892820','14903454','25360957','25361080','14903546','14903542','14903500','14901862','13692552','13550263','13550257','13550231','13550275','13550272','13500393','13717865','13697745','13718524','13697752','13500411','13550307','13520596','12688381','12688392','12688402','12688412','12688444','12710881','12710899','12710907','12710916','12416281','13759618','13759458','13550323','13072446','13181707','13158071','13072419','13063650','13063658','13015338','13015341','13015379','13015298','13015307','36126398','36126404','36126405','36126406','36126407','36126408','36123547','36123559','36123561','36123567','36123572','36123584','36123585','36123589','36123590','36123598','36123605','36126419','36126423','36126424','36126425','36126428','36126430','36126437','36126441','36123520','36123531','36123533','36153214','36138686','36138687','36138688','36137740','36137728','36138572','36138573','36138581','36138592','36138615','36173203','36177848','14712323','14712316','14712326','14712308','14645418','14602786','14580900','14505117','14505105','14505106','14331334','14331327','14331329','14331331','14197402','14166401','14163882','14197390','14502633','14505170','14502632','14502629','14505164','14505166','14311906','14311907','14672964','14652936','14652913','14349132','14311936','14206264','14197492','14712293','14673002','14673005','14673012','14536874','14505146','14505153','14502657','14762669','14712338','14712342','14672981','14331340','14331337','14311916','14311918','14240366','14762665','14762659','14652953','14652945','14652940','14645397','14645390','14645392','14645395','14536911','14505127','14328939','14331350','14331352','14311922','14197443','14197449','14712358','14645452','14602808','14580903','14602797','14580906','14580904','14502669','14502663','14502665','14502660','14502667','14331344','14331342','14197475','14197471','14197473','14712280','14602816','14538403','14505131','11541273','11637853','11653506','14652931','14624334','14624341','14624342','12329489','14941253','36222469','36222478','36222482','36222485','36265296','36265488','36280852','36253600','14238014','14238033','14238881','14267893','14267895','14226214','14268645','14226217','14268647','14238894','14268843','14268845','14226232','14268846','14226244','14226245','14239440','14239445','14226257','14239456','14228813','14226206','13689612','13689620','13689625','13689627','13689637','13616652','13616661','13616668','13689662','13616681','13689684','13616588','13616596','13616609','13579803','13579804','13579813','13579824','13522147','13530590','13530593','13530625','14228821','13689703','14228823','14228831','14228832','14228838','14228841','14228844','14228854','13689554','13689556','14226203','13609138','13609185','13609208','13609229','13609238','13609255','13559264','13616572','15149807','14941201','14941204','14941206','14941207','14941213','14941217','14941226','14941228','14941229','14941230','14941238','14941241','14941245','13530640','13530716','13530720','13530726','13530744','13530752','13530775','14466611','14466617','14466618','14432223','14432224','14466623','14464794','14464797','14466626','14464802','14464803','14464805','14464806','14464815','14464817','14395038','14541477','14464820','14432210','14466606','14466608','14432216','13320116','13320146','13320150','13313349','13313362','13313364','13034476','13034389','14643672','14643675','14738277','14738766','14738767','14738986','14739000','14624357','14624365','14624377','14643651','14643654','14643657','12498742','12498750','12330373','14903439','15149713','15149726','15149756','15583064','15583096','15583125','15583128','15583162','15583188','15583201','15583210','15583227','15583242','15583245','15583264','14786833','14786838','14786843','14788372','14788378','29715294','14539773','14541518','14539774','14539780','14539811','14539812','14539814','14941257','14941176','14579433','14579451','14579468','14541480','14579473','14541481','14579619','14579622','14541487','14541489','14541490','14541493','14541494','14541498','14541502','14541504','14579632','14579635','14794650','14788428','14794634','14794635','14794649','14788391','14788397','14788420','14325726','14299898','14325731','14299904','14325738','14299926','14299936','14299943','14268853','14269072','14269080','14269084','14269265','13034283','13045057','13034293','13034297','13034308','13045070','13034334','13034360','13034373','13034377','13034385','13034395','13034454','13313233','13313241','13313254','13313260','13313268','13313280','13313296','13045010','13045014','13045033','13045046','13045048','12631228','12631243','12645854','12625648','12625654','12625656','12713615','12713619','12713623','13034508','13039848','12713585','12713598','12713606','14391772','14391795','14395001','14395004','14325671','14395010','14325678','14325688','14395015','14395016','14325695','14325696','14325701','14395023','14395026','13034339','14237989','14237992','12453576','12453581','12421797','12421829','12448009','12448014','12448015','12337623','12448020','12448033','12625647','12597837','12597847','12597865','12604640','12506355','12506356','12453601','12506374','12506377','12506395','12506400','12448043','12506418','12448050','12448054','12448062','12506597','12448066','12448067','12479884','12506681','12448072','12506688',
'12506689','12448078','12448080','12448089','12448093','12604696','12622768','12622803','12622850','12625627','12625633','12421748','12448095','12506325','12448098','12506326','12506327','12448105','12421763','12453563','12453565','12421774','12421777','12631177','12631178','12604644','12631192','12604681','12631203','12329471','12329472','12329508','13034346','12625675','14892964','36230982','14652939','14652963','14652968','14712344','14579625','12713645','14738991','14238911','14645432','14240284','14238896','12631222','12625680','12604645','34431258','36114619','13888967','13888692','13888466','13891736','13891559','13891579','13891365','13891376','13890918','13891385','13891390','13891418','13887785','13887593','13887251','13888020','13888038','13893249','13893801','13893445','13893522','13893952','13893279','13892787','13892858','13892870','13893100','13886906','36236398','36241271','36247728','36241364','13888767','13888785','13888392','13886965','13886975','13887006','13889602','14793907','14794627','26263691','26268593','26263906','26268886','26269128','26269336','26275151','26266046','26269704','26275506','26275580','26267173','26234005','26234329','26234018','26267545','26261794','26261893','26234068','26227143','26293104','26290009','26290075','26290242','26287140','26293832','26290731','26287598','26287780','26294742','26283416','26262546','26227200','26262627','26262937','26227270','26227279','26263109','26234205','26263261','26233837','26263529','26233921','26233942','26233951','26233964','26226800','24749041','26226854','24750068','26226954','24751938','26227015','26279796','26279885','26279974','26280153','26288353','26288461','26288663','26288970','26289064','26227055','13892306','13892337','13891929','13891939','13891944','13891803','13891982','13892017','13890492','13889926','13889764','13889776','13889567','13891439','13890694','13890536','13890744','13892411','13892430','13892271','13891922','13891478','13891657','13891667','13891532','13891551','13892651','13892653','13892703','13890597','13890384','13887733','13887560','26278063','26278306','26278500','26282413','26286407','26286466','26278712','26273597','26273641','26279067','26279213','26268042','26274104','13888245','13888264','13888109','13886604','13886609','26338335','26333216','26335814','26333570','26339107','26333725','26337528','26334855','26324697','26337759','26335095','26322494','26325072','26301371','26301403','26322982','26331579','26323035','26323070','26322150','26322269','26322304','26297570','26299433','26299515','26299557','26297703','26297718','26297994','26295203','26295398','26298602','26295625','26298639','26301686','26323137','26301853','26323343','26301890','26323444','26301963','26323539','26321499','26332473','26332533','26332606','26332723','26324298','26298654','26295991','26296232','26300901','26301186','26301223','26289551','26297148','26297230','26289709','26292904','26286650','26289927','26341040','26341173','26345289','26345375','26343796','26344091','26344710','26340956','26340986','30354140','30355418','31162290','31264769','31264984','31269119','31270971','29947628','36221612','36221616','36230923','36199778','28581066','14244364','14244373','14244383','14244924','14244931','14244932','14245138','14245149','14245150','14245152','12604611','14244359','13892210','36225884','36199782','26266371','14794630','36234372','9155016','36155080','6397327','5668906','4275621','8389615','36229926','6398623','4272332','36304152','36268071','36268144','36268164','36266947','36267520','36232600','36233048','36218762','36219604','4277022','4277036','4277046','4272254','4268779','4205061','4198821','4205280','4131011','4131097','4243226','4276610','4202544','4205339','4205578','4205650','4199085','4199127','4199190','4174545','4199278','4243140','6397126','6397565','6350732','6351942','6352179','6352752','6353265','6379538','6379599','6346113','6347412','6349469','6350658','31892323','31890709','31890894','31888743','31790579','31790664','31790757','31298173','30487273','30564953','31889511','31790279','31790289','31791004','31888659','31790345','31888680','31889178','31889237','31889272','31889297','31890017','30090427','30173576','30175831','25999713','22006727','22007290','22008297','21951458','22358883','19651341','18920329','18920354','18920389','18920174','18920212','18919798','18919811','18919817','18920018','18920255','18920024','19047637','14737351','28615704','18910375','18912503','18913102','18909515','18908967','18909639','18909716','18913226','18913281','18912848','18913331','18911089','36161457','18910103','18910155','18907828','18907124','18907699','18905097','18904504','18904515','18904572','18904595','18903842','18903557','18902559','18902574','18903763','18006118','18006136','18902658','18902832','18906021','18906073','18906117','18903933','18903937','18903959','18903976','18904007','18904234','18904246','18904274','18903519','18902871','15363558','31891240','31891264','31889598','31889644','31888942','31889070','31890594','31890610','34375455','31892782','31892808','31891285','31891347','31891414','31892721','32165076','33193904','32454570','32474903','28076347','28614203','28614221','28614252','28614309','14737378','14737297','14737304','13342164','28956875','29067719','31893139','31892399','31892404','31892443','31891944','28615577','28615644','28489205','28256209','28228815','28228890','28256352','28228543','28228713','9747865','9747723','9747866','9747718','9751612','9747727','9747730','9747732','9747875','9747734','9747877','9747736','9747878','9751605','9747713','9747868','11865399','11866861','10321317','10321330','9999669','9966209','9966232','9999648','9999652','9999657','23011998','22884390','24665974','24513581','24659380','22502726','22503461','22504272','22446177','22508223','22497939','22498013','22498905','22500318','22500611','18914572','18915185','18915514','18915517','18915211','18914790','18914825','18917745','18917285','18914221','18914843','18914244','18914250','18914266','18913934','18914037','18915275','18915379','18914941','18915406','18916124','18915686','18915953','18916070','18916868','18916949','18916954','18916967','18917067','18916352','18916371','18914499','18914106','18914521','18914546','18913749','18913765','18915802','26673972','27453275','27454917','26606819','26608171','26385028','20845476','21640026','21367552','21376382','21643949','20738285','20738834','21610516','21620004','21367163','18917668','18917223','18917713','18918686','18918421','18918138','18918491','18918188','18918207','18919567','18919610','18918244','18918317','18918843','18918847','18918512','18918979','18918987','18918994','18919013','18919977','18919429','18919447','18920454','18920460','18919500','18912048','18911595','18912068','18912249','18912296','18911899','18912805','18912355','18912360','18912813','18912826','18911692','9749180','9830423','9830752','9840985','9840986','9840989','9841265','9841276','9841287','9841297','9841298','9841306','9830454','9830473','9830389','9830494','9941591','9941599','9941602','9941607','9941642','9941644','9941647','9941648','9941663','9941665','9941667','9941561','9941800','9941564','9941565','9941571','9941573','9941578','9941581','9941583','9941587','9915698','9915699','9915704','9915728','9915732','9915736','9915739','9915744','9915753','9915754','9918017','9916318','9918751','9749687','9749697','9749698','9749699','9749702','9747695','9747698','9751592','9747700','9749994','9747705','9750254','9747707','9747709','22507502','18903044','18903108','23429413','23429718','18918095','27996348','11831402','11831405','11831426','11830324','11906821','11905526','18916526','18916551','18916615','31893109','18918883','18903820','21377368','18915462','18920028','36215742','12632853','18914549','18918429','36271475','8574541','8574459','8574465','8574549','8574552','8574468','8574477','8574479','8574480','8574486','8574487','8574489','8574490','8574491','8574492','8569625','8569636','8569638','8569526','8570203','8569976','8570207','8569979','8570208','8570660','8570569','8570573','8570574','8570579','8570675','8570679','8569618','8569389','8569391','8569394','8569517','8569399','8569401','8569406','8569415','8569424','8570212','8570217','8570219','8569705','8569710','8569538','8569717','8569719','8569545','8569449','8569464','8569465','8570313','8570320','8570222','8570327','8570229','8570231','8570232','8570234','8570235','8570238','8570257','8570265','8570267','8570268','8570270','8570278','8570288','8570294','8584025','8584035','8584037','8584044','8584051','8584057','8584062','8584064','8584067','8583312','8582960','8582968','8582977','8582980','8583149','8583532','8583542','8583552','8583562','8583411','8583412','8583579','8583580','8583420','8583428','8583328','8583429','8583602','8583329','8584356','8584360','8584407','8576707','8576710','8576711','8576712','8576713','8576577','8576579','8576586','8576588','8576589','8576592','8576596','8576598','8576933','8576784','8576787','8576788','8576791','8576795','8576797','8576800','8576803','8576627','8576704','8576705','8576706','8576907','8577138','8577067','8577140','8577073','8577075','8577077','8577079','8577080','8577081','8577152','8577153','8577099','8576938','8577102','8577103','8577104','8577107','8577108','8577190','8577196','8577273','8577282','8577291','8577294','8577295','8577296','8577154','8577156','8577157','8577302','8577172','8577174','8577175','8577168','8567679','8567549','8567551','8567552','8567434','8567439','8567610','8567488','8575435','8575436','8575373','8575388','8567056','8566549','8566533','8566535','8566346','8566354','8566153','8566365','8566368','8566370','8566372','8566373','8566381','8566385','8566184','8566400','8566988','8566992','8567001','8567005','8567642','8567644','8567648','8567523','8567533','8567675','8566032','8566071','8566084','8566087','8566091','8566103','8566117','8565936','8565756','8566613','8566632','8566475','8566647','8566649','8566556',
'8566568','8566569','8566417','8566607','8576502','8576503','8576504','8576574','8576430','8576576','8576509','8576510','8576512','8576514','8576515','8576516','8576517','8576455','8576518','8576356','8576520','8576458','8576522','8576523','8576359','8576360','8576525','8576361','8576362','8576469','8576475','8575725','8575726','8575757','8575692','8575759','8575694','8575760','8575695','8575696','8575697','8575763','8575698','8575699','8575704','8575705','8575706','8575707','8575709','8575710','8575711','8575712','8575713','8575714','8575715','8576321','8576322','8576324','8576325','8576326','8576332','8576333','8576405','8576336','8576337','8576342','8575736','8575737','8575745','8575683','8575684','8575685','8575687','8575688','8575717','8575719','8575720','8575724','8564467','8564473','8564484','8564487','8564330','8564332','8564336','8564337','8564800','8564807','8564817','8565355','8565362','8565226','8565237','8565239','8565243','8565463','8565102','8565493','8565294','8565498','8565313','8565327','8565337','8565342','8565345','8565952','8565956','8565974','8565979','8565984','8565797','8565802','8565808','8565665','8565668','8565841','8565852','8565536','8565859','8565701','8565709','8565710','8565717','8565565','8565721','8565569','8565722','8565724','8565580','8565581','8565737','8565591','8565595','8565596','8565388','8565391','8565394','8565623','8565411','8565429','8581520','8581605','8581523','8581606','8581608','8581532','8581533','8581615','8581537','8581618','8581619','8581620','8581621','8581622','8581623','8581547','8581626','8581549','8581550','8581465','8581552','8581553','8581554','8581555','8580942','8580884','8580886','8580948','8580890','8580893','8580894','8580953','8580733','8580791','8580793','8580794','8580799','8580800','8580803','8580259','8580270','8580273','8580197','8580276','8580278','8580207','8580210','8580211','8580212','8580213','8580291','8580216','8580217','8580293','8580218','8580294','8579700','8579704','8579705','8579631','8579635','8579636','8579720','8579577','8580098','8580103','8580105','8581843','8580107','8580041','8580110','8580044','8580045','8580047','8580050','8580118','8580053','8580120','8580056','8580057','8580060','8580061','8580062','8580065','8580070','8580071','8580072','8580073','8580074','8579724','8579581','8579586','8579732','8579588','8579733','8579590','8579735','8579592','8579593','8579622','8579506','8579426','8579508','8579429','8579511','8579512','8579432','8579433','8579515','8579434','8579516','8579436','8579518','8579519','8579520','8579522','8579442','8579252','8579254','8579879','8579881','8579882','8579883','8579886','8579887','8579888','8579799','8579896','8579898','8579902','8579538','8579539','8579486','8579490','8579562','8579564','8579565','8579494','8579415','8579569','8579570','8579418','8579419','8579572','8579420','8579421','8579504','8579423','8582829','8582830','8582743','8582836','8582746','8582843','8582757','8582941','8582771','8582694','8582442','8582445','8582700','8582449','8582453','8582454','8582456','8582459','8582460','8582706','8582470','8582472','8582191','8582193','8582194','8582195','8582202','8582388','8582393','8582181','8581944','8581951','8581952','8582047','8582049','8582053','8582057','8581965','8582059','8581967','8581969','8582061','8581819','8581820','8581821','8581825','8581826','8581676','8581680','8581835','8581755','8581756','8581688','8581690','8581694','8581763','8581696','8583021','8583027','8583106','8583044','8582815','8582820','8582821','8582822','8582732','8582823','8582826','8582735','8583356','8583358','8583362','8583372','8583375','8583377','8583293','8583387','8583393','8583400','8581697','8581701','8581771','8581706','8581710','8581713','8581779','8581788','8581581','8581491','8581493','8581409','8581410','8581413','8581419','8581422','8584417','8584421','8584424','8584079','8584098','8584331','8584335','8584340','8582215','8582331','8582222','8582340','8582350','8582361','8582233','8582364','8582375','8582381','8581396','8581397','8575472','8575391','8575473','8575394','8575395','8575404','8575329','8575416','8575417','8575331','8575419','8575420','8575421','8575422','8575425','8575337','8575426','8575427','8575428','8575429','8575430','8575342','8575434','8575344','8575345','8576015','8576016','8575967','8575970','8575973','8574556','8574711','8574587','8574592','8574596','8574240','8574243','8574173','8574175','8574074','8574177','8573678','8573898','8573905','8573908','8573909','8574090','8573918','8573925','8573928','8574038','8573930','8573931','8574044','8573935','8574046','8573938','8573939','8573941','8573942','8574051','8574052','8574054','8574056','8573948','8574057','8573952','8573854','8573865','8573869','8573883','8573885','8573888','8573889','8573890','8573891','8573893','8573895','8574267','8574270','8574273','8574280','8574293','8573637','8573641','8573460','8573562','8573464','8573469','8573643','8573472','8573476','8573647','8573571','8573572','8573480','8573582','8573544','8573547','8571003','8571011','8571020','8571029','8571032','8571035','8571042','8571050','8571065','8568299','8568602','8568740','8568609','8568624','8568762','8568643','8568645','8568662','8568011','8568016','8568017','8568023','8568027','8568028','8568033','8568036','8568038','8568837','8568663','8568551','8568933','8568935','8568799','8568807','8568810','8568958','8568811','8568816','8568821','8568927','8572804','8572807','8572702','8572718','8572721','8572726','8572482','8572486','8572491','8572498','8572639','8572511','8572516','8572517','8572519','8576165','8576186','8576187','8573584','8573585','8573586','8573594','8573597','8573598','8573599','8573600','8573603','8573606','8573400','8573402','8573403','8573406','8573407','8573317','8575346','8575276','8575210','8575214','8575361','8575216','8575217','8575218','8575219','8575220','8575224','8575225','8575227','8575231','8575234','8573415','8573416','8573418','8573419','8573424','8573426','8573430','8573435','8573436','8573233','8573257','8572854','8572860','8572749','8572869','8572871','8572873','8572875','8572758','8572880','8572762','8572883','8572885','8572766','8572657','8572768','8572658','8572659','8572660','8572664','8572902','8572667','8572670','8572783','8572671','8572784','8572673','8572674','8572676','8572677','8572791','8572797','8572346','8572352','8572244','8572249','8572356','8572256','8572266','8572155','8572274','8572159','8572380','8572164','8572176','8572177','8572291','8572524','8572393','8572526','8572527','8572403','8572404','8572405','8572416','8572443','8572448','8572449','8572320','8572056','8572299','8572062','8572063','8572065','8572070','8572207','8572087','8572217','8572088','8572230','8571950','8572128','8572129','8571954','8572133','8572142','8576527','8576622','8576535','8576538','8576539','8576479','8576481','8576542','8576483','8576410','8576491','8576556','8576492','8576557','8576559','8576418','8576563','8576496','8576420','8576565','8576498','8576566','8574945','8574846','8574848','8574952','8574954','8574870','8574871','8574872','8574874','8574876','8574878','8574882','8575980','8575906','8575801','8575802','8575804','8575805','8575807','8575811','8581830','8575728','8575734','8575735','8574495','8574602','8574497','8574604','8574498','8574605','8574500','8574501','8574503','8574611','8574505','8574507','8574508','8574509','8574510','8574511','8574515','8574517','8574522','8574523','8574532','8574534','8574535','8574458','8575177','8575179','8575180','8575107','8575182','8575111','8575112','8575113','8575114','8575189','8575192','8575069','8575077','8575208','8575209','8575086','8575087','8575088','8575142','8575144','8575146','8575147','8575149','8575150','8575098','8575100','8575156','8575106','8574958','8575018','8574965','8574966','8574900','8574970','8575035','8575040','8574919','8575045','8574980','8574922','8574981','8574923','8574982','8574924','8574983','8574984','8574985','8574986','8574987','8574929','8574993','8574895','8574831','8574837','8574625','8574890','8574893','8564273','8553287','8552931','8552936','8552951','8552953','8552956','8552958','8552959','8552963','8552966','8553126','8553129','8553138','8553272','8552974','8552860','8552864','8552982','8552867','8552993','8552872','8552873','8552881','8552999','8553003','8552885','8553019','8552912','8552918','8554130','8554257','8554260','8554261','8554146','8554166','8554169','8554170','8554033','8554037','8554039','8554040','8554178','8554046','8553909','8553927','8553930','8552927','8552929','8562587','8562599','8562609','8562621','8562627','8562628','8552542','8552452','8552052','8552054','8552055','8552060','8552063','8552067','8552183','8551963','8551966','8552299','8552301','8552305','8552306','8552309','8552310','8552312','8552185','8552313','8552186','8552315','8552187','8552318','8552196','8552197','8552199','8552348','8552243','8552278','8552395','8552285','8552286','8552361','8552577','8552364','8552578','8552736','8552590','8552738','8552739','8552710','8552722','8552585','8552726','8563087','8563090','8563091','8563094','8563096','8563097','8563099','8563101','8563103','8563107','8556688','8556816','8556821','8556823','8556831','8556845','8556846','8556600','8556847','8556715','8556722','8556617','8556622','8556580','8556331','8556346','8556349','8556623','8556758','8556759','8556640','8556642','8556768','8556650','8556657','8556661','8556663','8556667','8556668','8556675','8557612','8557379','8557380','8557522','8557414','8557537','8557430','8557444','8557451','8557460','8557212','8557215','8557358','8557364','8557365','8556772','8556774','8556778','8556378','8556263','8556264','8556389','8556391','8556392','8556395','8556273','8556400','8556284','8556290','8556168','8556300','8556309','8556191','8556310','8556193','8556314','8556196','8564083','8564090','8564293','8564108','8564111','8563951','8563953','8563959','8563963','8563972','8554924','8554939','8554942','8554945','8554950','8554952','8554955','8554959',
'8554963','8555195','8555196','8555199','8555100','8555103','8555215','8555104','8555217','8555105','8555218','8555107','8555219','8555223','8555114','8555115','8555117','8555118','8554973','8554978','8554982','8554871','8554880','8554994','8554996','8554883','8555577','8555365','8555370','8555372','8555374','8555375','8555379','8555380','8555381','8555383','8555227','8555384','8555386','8555268','8555269','8555281','8555293','8554419','8554422','8554428','8554432','8554436','8554194','8554196','8554441','8554197','8554216','8554223','8554229','8554232','8554242','8554659','8554675','8554677','8554679','8554525','8554527','8555076','8555078','8555087','8554863','8554865','8554531','8554532','8554535','8554537','8554539','8554543','8554544','8554545','8554620','8555301','8555414','8572944','8572945','8572948','8572821','8572822','8572824','8572825','8572829','8572837','8572846','8572851','8572916','8572927','8571595','8571925','8572046','8571935','8571936','8571941','8571947','8571820','8571828','8570851','8570853','8570989','8570858','8570859','8570878','8570879','8570883','8570887','8570890','8571070','8570892','8570894','8570902','8571083','8572004','8571888','8571890','8572020','8571892','8571895','8572035','8572147','8572150','8571969','8571849','8571852','8571986','8571990','8571867','8571996','8562655','8562661','8562793','8562666','8562667','8562797','8562671','8562673','8562677','8562453','8562460','8562571','8562461','8562573','8560803','8560804','8560671','8560672','8560688','8560830','8560840','8560842','8561129','8561134','8560594','8560615','8560616','8560622','8560629','8560481','8560490','8560388','8560657','8560398','8560510','8560276','8560278','8560003','8560009','8560013','8560018','8560029','8560032','8560042','8560044','8560045','8560058','8559902','8560071','8559914','8559917','8560077','8559919','8560078','8559921','8559923','8559925','8560088','8559928','8560093','8559936','8560666','8560290','8560449','8560457','8560311','8560336','8560217','8560359','8560366','8560370','8560371','8560373','8560377','8559983','8559633','8559634','8559938','8559939','8560407','8563576','8563421','8563442','8557822','8557840','8557842','8557732','8557733','8557637','8557761','8558324','8558637','8558657','8558503','8558504','8558859','8558863','8558762','8558516','8558526','8558528','8558539','8558543','8558545','8558453','8558457','8558458','8558462','8558464','8558471','8558478','8558480','8558481','8558482','8558484','8558487','8558489','8558491','8558492','8558495','8558150','8557890','8558151','8558011','8557897','8558169','8557932','8557936','8557937','8557940','8557544','8557572','8557463','8557464','8557473','8557586','8557475','8557588','8557487','8557954','8557957','8557958','8558085','8558088','8558089','8557978','8557981','8558100','8557986','8557991','8557855','8557997','8557879','8558004','8557884','8558007','8558235','8561417','8561421','8561426','8561429','8561574','8561441','8561454','8561479','8562123','8562124','8562127','8562128','8562135','8562138','8562156','8562158','8562163','8562169','8562175','8562062','8562277','8562282','8562286','8562288','8562297','8562195','8562205','8562116','8562117','8562118','8562232','8562233','8562122','8561860','8561623','8561489','8561491','8561629','8561630','8561634','8561876','8561502','8561882','8561649','8561650','8561663','8561664','8561527','8561528','8561398','8561552','8561910','8561918','8561672','8561815','8561816','8561821','8561596','8561599','8561605','8561610','8561612','8561616','8562642','8562643','8562644','8562645','8562646','8562774','8562647','8562777','8562071','8561883','8562097','8561886','8561887','8563071','8563072','8562105','8561891','8562107','8561892','8561894','8561895','8561896','8562115','8564026','8563539','8563544','8563558','8563565','8559404','8559191','8559192','8559193','8559197','8559203','8559207','8559209','8559772','8559647','8559649','8559802','8559804','8559809','8559568','8559577','8559582','8559583','8559584','8558998','8558885','8558888','8558890','8559010','8558893','8558898','8558906','8558907','8558817','8558624','8558827','8559090','8559228','8559230','8559235','8559244','8559246','8559247','8559106','8559017','8559128','8559145','8559592','8559620','8559621','8559502','8559165','8559051','8558983','8558864','8558866','8558996','8558878','8558997','8549588','8549592','8549593','8549594','8549530','8582071','8582076','8581794','8581795','8581941','8583957','8583617','8583639','8583523','8583524','8583646','8583525','8583528','8583530','8583666','8582785','8582953','8582786','8582808','8582809','8582812','8582621','8582622','8582623','8582625','8582626','8582419','8582428','8582693','8580721','8580731','8580565','8581031','8581189','8581190','8581041','8581194','8581055','8581057','8581060','8581063','8581066','8581148','8581068','8581150','8581070','8581074','8581078','8581156','8581332','8581269','8581023','8581175','8581024','8581185','8580588','8580594','8580595','8580600','8580319','8580243','8580245','8580333','8580295','8580297','8580222','8580299','8580300','8580224','8580227','8580229','8580232','8580236','8580238','8580137','8580079','8580145','8580080','8580081','8580082','8580085','8580089','8580090','8580096','8577905','8577977','8577907','8577908','8577909','8577981','8577984','8577986','8577987','8577919','8577923','8577926','8577786','8577929','8577790','8577648','8577650','8577652','8577653','8577654','8577661','8577662','8577663','8577598','8577604','8577605','8577607','8577611','8577612','8577614','8577410','8577411','8577742','8577665','8577667','8577668','8577670','8577674','8577756','8577757','8577759','8577616','8577618','8577619','8577772','8577774','8577776','8577778','8577780','8577706','8577858','8577861','8577862','8577804','8577866','8577805','8577808','8577814','8577816','8577817','8577825','8577827','8577417','8577418','8577419','8577420','8577426','8577427','8577430','8577432','8577433','8577435','8577436','8577443','8577307','8577445','8577446','8577447','8577315','8577453','8577454','8577380','8577455','8577381','8577457','8577462','8577387','8577322','8577388','8577326','8577397','8577399','8577400','8577401','8577333','8577403','8577404','8577335','8577408','8577339','8577409','8577264','8577265','8577270','8577450','8577312','8577451','8581485','8581360','8581367','8581239','8581368','8581240','8581243','8581372','8581373','8581378','8581253','8581312','8581254','8581385','8581315','8581386','8581316','8581259','8581392','8581321','8581323','8578013','8578014','8578018','8578019','8578020','8578021','8578023','8578024','8578025','8577898','8577899','8577901','8578202','8578366','8578051','8578140','8578193','8577993','8577994','8578001','8578005','8578253','8578254','8578256','8578258','8578154','8578155','8578260','8578100','8578157','8578262','8578110','8578113','8578044','8578116','8578117','8578176','8578049','8578570','8578571','8578490','8578492','8578495','8578373','8578497','8578424','8578425','8578586','8578426','8578428','8578429','8578430','8578600','8578432','8578433','8578434','8578436','8578509','8578438','8578510','8578440','8578512','8578514','8578445','8578516','8578446','8578448','8578449','8578450','8578313','8578452','8578453','8578454','8578316','8578456','8578318','8578458','8578460','8578461','8578462','8578467','8578468','8578469','8578264','8578266','8578271','8578272','8578635','8578631','8578632','8578470','8578476','8580809','8580810','8580700','8580701','8580706','8580707','8580708','8580710','8580712','8580715','8580716','8580717','8579063','8579064','8578934','8578935','8578936','8578943','8579029','8578724','8578725','8578730','8578732','8578734','8578735','8578683','8579400','8579407','8579214','8579215','8578924','8578927','8578794','8578797','8578798','8578799','8578800','8578802','8578804','8578882','8579220','8579221','8579155','8579226','8579228','8579159','8579229','8579161','8579168','8579175','8579176','8579185','8579034','8579039','8579043','8579044','8578984','8578985','8578986','8578987','8579053','8579054','8579055','8578990','8578991','8578993','8578994','8578883','8578889','8578893','8578894','8578895','8578977','8578981','8578908','8578909','8578910','8578912','8578914','8578916','18913876','18914529','8576349','8576350','8576353','8576354','8576160','8576161','8563510','8563514','8563518','8563522','8563188','8563527','8563190','8563532','8563373','8563194','8563533','8563197','8563205','8563211','8563215','8563222','8563225','8563227','8563074','8563233','8563076','8563079','8563083','8563084','8563085','8556323','8555683','8555536','8555545','8555548','8555551','8555714','8555732','8555757','8555768','8556318','8556321','8556315','8551349','8551350','8551352','8551360','8551365','8551366','8551392','8551396','8550826','8550828','8550829','8550682','8550688','8550692','8550696','8550550','8551585','8551592','8551593','8551598','8551607','8551522','8551530','8550884','8550885','8550803','8550806','8550899','8550812','8550821','8551780','8551559','8551578','8551580','8551582','8550906','8550907','8550840','8550936','8551195','8551149','8551177','8551179','8551185','8551770','8551773','8551774','8551779','8549147','8549154','8549068','8546421','8546425','8546356','8546359','8546362','8546363','8546374','8546375','8546876','8546877','8546953','8547017','8547020','8546891','8546893','8546894','8546895','8546898','8546900','8546901','8546902','8546905','8546195','8546282','8546283','8546284','8546198','8546285','8546199','8546200','8546288','8546291','8546075','8546531','8546537','8546411','8546415','8546908','8546910','8546912','8546916','8546919','8546920','8546924','8546306','8546317','8546319','8546320','8546321','8546326','8546265','8546348','8546270','8546586','8546587','8550068','8550077','8550085','8550087','8550088','8550092','8550010','8550011','8550012','8545356','8545358','8545359','8545361','8545214','8545217','8545370','8545374','8545149','8545150','8545152','8545155','8545158','8545161','8545162',
'8545167','8546061','8546062','8546064','8546066','8546071','8545883','8545885','8545168','8545110','8545113','8545115','8545122','8545123','8545124','8545496','8545497','8545500','8545501','8545267','8545270','8545889','8545777','8545510','8545633','8545511','8545554','8545643','8545646','8545648','8545130','8545131','8545139','8545143','8545664','8545666','8545788','8548017','8548023','8547903','8547905','8548077','8547907','8547908','8547909','8547910','8547911','8547914','8548343','8548351','8548352','8548262','8548361','8548269','8548364','8548288','8550544','8550396','8550399','8550185','8550186','8550406','8550187','8550189','8550415','8550553','8550557','8548518','8548522','8548524','8548449','8548457','8547794','8547936','8547801','8547979','8547890','8548373','8548377','8548387','8548397','8548399','8547762','8547641','8544542','8544548','8544480','8544481','8544482','8544487','8544488','8544489','8544490','8549550','8549466','8549472','8549476','8549478','8549479','8549481','8549485','8549574','8549488','8549491','8549494','8549495','8548104','8548853','8544492','8544473','8544474','8544479','8545080','8544965','8544864','8544866','8541287','8544278','8539545','8539346','8539571','8539575','8539115','8539403','8539426','8539169','8538966','8539188','8538991','8538994','8540514','8540071','8540123','8540129','8540600','8540405','8540214','8540656','8540282','8544299','8544305','8544311','8541381','8544316','8541159','8541166','8541427','8541215','8538655','8538910','8538913','8538671','8538454','8538459','8538930','8538936','8538485','8538707','8538496','8538503','8538544','8538304','8538267','8537862','8537870','8538123','8536860','8536917','8536923','8539456','8539460','8539679','8539476','8539709','8539281','8539502','8539723','8539518','8539763','8540002','8540199','8540006','8540036','8540054','8539851','8539632','8536688','8536635','8549943','8550024','8550035','8549893','8549895','8549896','8549897','8549369','8549370','8549373','8549374','8549377','8549381','8549383','8549385','8549312','8549391','8549182','8549084','8549097','8549098','8549100','8549102','8549106','8549817','8549819','8549822','8549826','8549827','8549828','8549829','8549830','8549831','8549833','8549836','8549985','8549911','8549914','8549997','8550000','8550002','8549837','8549838','8549839','8549843','8549931','8549933','8549934','8549936','8549938','8549939','8549853','8549940','8549942','8549858','8550418','8550419','8550420','8550421','8550425','8550426','8550527','8550270','8550271','8550272','8550273','8550274','8550275','8550276','8548904','8548914','8548920','8549008','8549009','8549010','8548831','8548833','8549014','8549017','8548842','8550281','8549500','8549503','8549507','8549509','8549518','8549521','8549522','8549523','8549524','8549525','8549430','8549440','8547366','8547368','8547369','8547379','8547241','8547244','8547247','8547251','8547156','8547253','8547167','8547171','8547176','8547179','8547181','8547653','8547581','8547046','8547057','8547073','8546934','8547606','8547612','8547617','8547419','8547556','8547632','8547560','8547561','8547568','8547571','8549899','18916113','18915689','36190289','34035265','34001911','34624146','34003957','34003992','34023908','34023917','33993904','34027500','34027705','34030317','34031811','34032380','34032604','36289644','36289709','36290482','33995510','33998003','33999332','34000876','34000960','31370036','31370348','31370651','28767978','29079292','28766102','27914353','27624107','27957185','27630740','27556414','27556727','29129955','29192207','27557417','27503662','27584961','27586579','27436747','27587732','27611949','27459580','27420369','27420552','27420789','27420865','27421601','27422095','8540903','8540906','8540930','8572339','8572334','8572342','8536741','8536977','8536752','8536766','8536991','8536497','8536502','8536822','8536832','8536566','8536843','8536596','8572344','8540178','8541221','8541452','8541457','8540979','8541263','8541000','8541010','8540824','8541050','8540687','8540700','29195325','27877499','28764741','8537941','8537754','8537563','8537809','8538582','8538349','8538363','8538369','8538389','8538184','8538213','8538435','8539221','8539251','8539048','8538849','8539099','8537653','8537198','8537200','8537443','8537453','8537237','8537035','8539532','8539536','8539322','8537649','27426885');
return $array;
    }



}