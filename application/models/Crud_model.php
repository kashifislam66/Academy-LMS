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
'8575544','8575545','8575549','8575550','8567361','8567384','8566360','8566379','8566183','8566393','8566398','8566019','8566999','8567009','8567014','8567021','8567613','8567651','8567656','8567395','8567673','8566235','8566238','8566065','8566241','8565882','8566119','8566621','8566624','8566626','8566637','8576571','8576573','8576575','8576506','8576507','8576513','8576457','8576524','8576463','8576366','8575555','8575556','8575557','8575558','8575559','8575560','8575561','8575562','8575563','8575565','8575567','8575489','8575570','8575571','8575572','8575573','8575577','8575578','8575500','8575582','8575583','8575508','8575586','8575510','8575511','8575588','8575754','8575690','8575691','8575765','8575701','8575702','8575703','8575708','8575716','8576328','8576262','8576334','8576335','8576346','8575741','8575744','8575748','8575750','8575752','8575718','8575721','8575722','8575723','8576568','8576425','8576499','8564454','8564331','8564513','8564341','8564824','8565358','8565368','8565189','8565192','8565194','8565437','8565245','8565467','8565468','8565270','8565106','8565109','8565114','8565119','8565509','8565124','8565128','8565132','8565318','8565134','8565322','8565140','8565333','8565144','8565341','8565147','8565349','8565351','8565761','8565968','8565639','8565641','8565643','8565649','8565811','8565653','8565655','8565658','8565661','8565669','8565671','8565524','8565674','8565545','8565860','8565547','8565698','8565868','8565728','8565733','8565583','8565587','8565597','8564364','8564373','8564378','8564380','8564390','8581425','8581515','8581525','8581529','8581612','8581530','8581613','8581617','8581556','8581558','8581346','8581007','8581008','8581011','8581014','8581016','8581017','8580895','8580732','8580898','8580899','8580900','8580901','8580903','8580905','8580798','8580262','8580264','8580267','8580199','8580200','8580279','8580202','8580203','8580205','8579777','8579781','8579782','8579786','8579849','8579790','8579791','8579794','8579797','8579714','8579641','8580040','8580042','8580043','8580046','8580048','8580049','8580119','8580054','8580064','8580069','8580075','8579964','8579908','8579970','8579909','8579912','8579975','8579976','8579980','8579981','8579986','8579988','8579990','8579855','8579991','8579856','8579993','8579994','8579995','8579997','8579998','8580002','8580003','8580005','8580009','8580011','8580012','8580013','8579647','8579649','8579650','8579582','8579651','8579585','8579656','8579591','8579601','8579611','8579617','8579425','8579427','8579428','8579510','8579438','8579439','8579523','8579443','8579369','8579370','8579445','8579372','8579880','8579885','8579891','8579802','8579904','8579762','8579542','8579547','8579549','8579492','8579414','8579424','8582831','8582745','8582841','8582747','8582847','8582758','8582942','8582439','8582440','8582695','8582578','8582703','8582458','8582704','8582589','8582464','8582591','8582466','8582709','8582469','8582473','8582474','8582290','8582189','8582291','8582190','8582293','8582294','8582296','8582192','8582300','8582497','8582304','8582198','8582498','8582201','8582273','8582276','8582277','8582279','8582282','8582286','8582027','8581943','8582182','8582045','8582055','8581970','8582064','8581754','8581681','8581761','8581845','8581846','8581691','8583191','8583015','8583198','8583093','8583094','8583029','8583113','8582814','8582819','8582725','8582727','8582827','8582828','8583354','8583268','8583443','8583281','8583283','8583286','8583291','8583294','8583296','8583298','8583484','8583302','8583303','8583305','8583307','8583404','8581766','8581708','8581560','8581778','8581561','8581782','8581784','8581791','8581792','8581497','8581500','8581501','8581506','8584086','8584093','8584103','8584106','8584109','8584342','8584119','8583865','8582320','8582105','8582323','8582324','8582114','8582116','8582334','8582336','8582119','8582120','8582123','8582344','8582126','8582127','8582132','8582228','8582138','8582140','8582371','8582148','8582373','8582152','8582155','8582271','8582157','8582272','8581261','8575389','8575479','8575486','8575407','8575411','8575415','8575424','8575338','8575339','8575340','8575431','8575432','8575433','8575343','8576011','8576012','8576019','8576070','8576072','8576077','8576085','8576086','8576088','8574629','8574631','8574713','8574582','8574586','8574588','8574589','8574591','8574594','8574597','8574139','8574140','8574161','8574165','8574166','8574184','8573763','8573766','8573682','8573904','8573906','8573907','8573706','8573713','8573723','8573607','8573609','8573910','8573911','8573919','8573921','8573926','8573927','8573929','8573932','8573933','8574045','8573944','8573945','8573950','8574061','8573860','8573872','8573884','8573751','8573892','8573758','8573759','8574431','8574355','8574358','8574269','8574274','8574134','8573631','8573573','8573481','8573735','8573739','8573741','8573625','8571250','8571251','8570991','8571254','8571256','8570995','8571262','8571265','8571009','8571271','8571274','8571276','8571014','8571284','8571291','8571297','8571056','8568079','8568370','8568297','8568307','8568741','8568750','8568754','8568648','8568661','8567994','8568010','8568020','8568029','8568035','8568041','8567910','8567924','8568768','8568772','8568775','8568831','8568665','8568546','8568556','8568557','8568792','8568931','8568793','8568794','8568798','8568941','8568946','8568802','8568805','8568814','8568818','8568784','8568791','8572686','8572724','8572728','8572731','8572732','8572508','8576239','8576241','8576118','8573667','8573670','8573671','8573672','8573409','8575348','8575350','8575352','8575355','8575357','8575212','8575213','8575358','8575284','8575360','8575215','8575362','8575364','8575289','8575290','8575221','8575222','8575223','8575294','8575296','8575297','8575298','8575301','8575303','8575305','8575308','8575310','8575311','8575312','8573413','8573319','8573320','8573324','8573327','8573328','8573330','8573332','8573427','8573333','8573428','8573334','8573434','8573339','8573441','8573342','8573347','8573348','8573352','8573354','8573363','8573365','8573258','8572889','8572769','8572662','8572666','8572668','8572908','8572912','8572681','8572798','8572235','8572353','8572354','8572253','8572254','8572375','8572272','8572157','8572279','8572282','8572386','8572169','8572173','8572175','8572288','8572180','8572181','8572182','8572394','8572537','8572433','8572442','8572445','8572446','8572451','8572325','8572188','8572058','8572192','8572301','8572304','8572196','8572203','8572309','8572211','8572218','8572219','8572223','8572099','8572228','8572232','8572117','8572130','8572131','8571958','8572138','8576529','8576544','8576484','8576546','8576547','8576411','8576490','8576414','8576558','8576415','8576560','8576417','8576564','8575000','8574948','8574847','8575004','8574950','8574951','8574793','8574796','8574798','8574799','8574865','8574801','8574732','8574802','8574803','8574805','8574735','8574736','8574808','8574737','8574809','8574810','8574811','8574877','8574813','8574743','8574814','8574744','8574815','8574745','8574749','8574818','8574819','8574883','8575840','8575841','8575842','8575843','8575907','8575909','8575851','8575916','8575859','8575860','8575861','8575823','8575824','8575825','8575826','8575828','8575727','8575731','8575732','8575836','8575838','8574496','8574608','8574612','8574506','8574617','8574376','8574539','8575166','8575173','8575116','8575120','8575126','8575127','8575129','8575078','8575133','8575134','8575135','8575085','8575089','8575090','8575097','8575007','8575008','8575011','8574959','8575023','8574969','8575034','8575036','8575042','8574978','8575043','8575046','8575048','8574925','8574926','8574930','8574931','8574935','8574937','8574764','8574765','8574766','8574767','8574835','8574769','8574772','8574784','8574786','8574788','8574790','8574623','8574627','8574755','8574758','8574760','8564398','8564051','8553294','8553041','8552960','8552964','8552969','8552972','8553333','8553142','8553147','8552863','8552866','8552998','8552879','8552882','8553004','8552888','8552890','8553014','8552898','8552900','8552914','8552923','8553396','8553545','8553321','8554026','8554028','8554031','8554036','8554043','8553940','8554187','8553883','8553884','8553918','8553921','8553962','8553985','8553488','8553632','8552925','8562594','8562613','8562625','8562626','8562630','8552620','8552526','8552533','8552537','8552429','8552431','8552433','8552438','8552445','8552449','8552451','8552051','8552053','8552062','8552065','8552073','8552076','8552081','8551960','8551961','8552085','8552410','8552413','8552303','8552419',
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