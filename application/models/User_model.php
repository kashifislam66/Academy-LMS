<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        /*cache control*/
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    public function get_admin_details()
    {
        return $this->db->get_where('users', array('role_id' => 1));
    }

    public function get_user($user_id = 0)
    {
        if ($user_id > 0) {
            $this->db->where('id', $user_id);
        }
        $this->db->where('role_id', 2);
        return $this->db->get('users');
    }
     
    public function get_user_by_company()
    {
        $user_id = $this->session->userdata('user_id');
        $this->db->order_by("id", "DESC");
        $array = array('role_id' => 2,  'company_id'=> $user_id);
        $this->db->where($array);
        // echo "<pre>";print_r($test); exit;
        return $this->db->get('users');
    }

    public function get_manager_by_company()
    {
        $user_id = $this->session->userdata('user_id');
        //  echo $user_id; exit;
        $this->db->order_by("id", "DESC");
        $array = array('role_id' => 4,'company_id'=> $user_id);
        $this->db->where($array);
        return $this->db->get('users');
    }

    public function get_user_by_manager()
    {
        $user_id = $this->session->userdata('user_id');
        //  echo $user_id; exit;
        $this->db->order_by("id", "DESC");
        $array = array('role_id' => 2, 'manage_id'=> $user_id);
        $this->db->where($array);
        return $this->db->get('users');
    }

    public function get_user_and_manager_by_manager()
    {
        $user_id = $this->session->userdata('user_id');
        $student = $this->db->select('users.*')
		->where('users.manage_id', $user_id);
        return$this->db->get('users');
       
    }

    public function get_manager($user_id = 0)
    {   
        if ($user_id > 0) {
            //$this->db->order_by("id", "DESC");
            $this->db->where('id', $user_id);
        }
        $this->db->where('role_id', 4);
        return $this->db->get('users');
    }

    public function get_all_user($user_id = 0)
    {
        if ($user_id > 0) {
            $this->db->order_by("id", "DESC");
            $this->db->where('id', $user_id);
        }
        return $this->db->get('users');
    }

    public function add_user($is_instructor = false, $is_admin = false)
    {   
     
        $validity = $this->check_duplication('on_create', $this->input->post('email'));
        if ($validity == false) {
            $this->session->set_flashdata('error_message', get_phrase('email_duplication'));
        } else {
            $data['first_name'] = html_escape($this->input->post('first_name'));
            $data['last_name'] = html_escape($this->input->post('last_name'));
            $data['email'] = $email = html_escape($this->input->post('email'));
            $data['company_id'] = html_escape($this->input->post('company_id'));
            $data['manage_id'] = html_escape($this->input->post('manage_id'));
            $userPass = html_escape($this->input->post('password'));
            $data['password'] = sha1(html_escape($this->input->post('password')));
            $social_link['facebook'] = html_escape($this->input->post('facebook_link'));
            $social_link['twitter'] = html_escape($this->input->post('twitter_link'));
            $social_link['linkedin'] = html_escape($this->input->post('linkedin_link'));
            $data['social_links'] = json_encode($social_link);
            $data['biography'] = $this->input->post('biography');

            if ($is_admin) {
                $data['role_id'] = 3;
                $data['is_instructor'] = 1;
            } else {
                $data['role_id'] = 2;
            }
            // echo "<pre>"; print_r($data['company_id']); exit;
            $data['date_added'] = strtotime(date("Y-m-d H:i:s"));
            $data['wishlist'] = json_encode(array());
            $data['watch_history'] = json_encode(array());
            $data['status'] = 1;
            $data['image'] = md5(rand(10000, 10000000));

            // Add paypal keys
            $paypal_info = array();
            $paypal['production_client_id']  = html_escape($this->input->post('paypal_client_id'));
            $paypal['production_secret_key'] = html_escape($this->input->post('paypal_secret_key'));
            array_push($paypal_info, $paypal);
            $data['paypal_keys'] = json_encode($paypal_info);

            // Add Stripe keys
            $stripe_info = array();
            $stripe_keys = array(
                'public_live_key' => html_escape($this->input->post('stripe_public_key')),
                'secret_live_key' => html_escape($this->input->post('stripe_secret_key'))
            );
            array_push($stripe_info, $stripe_keys);
            $data['stripe_keys'] = json_encode($stripe_info);
            if ($is_instructor) {
                $data['is_instructor'] = 1;
            }
            // activated user go1 API
                $get_login = $this->api_model->login_go1();
                $get_login_decode = json_decode($get_login);
                    if(isset($get_login_decode->access_token)) {
                        $search_user = $this->api_model->search_user($get_login_decode->access_token, $email);
                        $search_user_decode = json_decode($search_user);
                        if(isset($search_user_decode->hits[0]->id)) {
                            $data['go1_id'] = $search_user_decode->hits[0]->id;
                            $update_user = $this->api_model->update_user_go1($get_login_decode->access_token, $data,$data['go1_id']);          
                        } else {
                            $post_user = $this->api_model->add_user_go1($get_login_decode->access_token, $data);
                            $post_user_decode = json_decode($post_user);
                            if(isset($post_user_decode->id)) {
                            $data['go1_id'] = $post_user_decode->id;
                            }     
                        }           
                    }
            $this->db->insert('users', $data);
            $this->email_model->send_email_company_by_user_activition($data['email'], $userPass);
            $user_id = $this->db->insert_id();

            // IF THIS IS A USER THEN INSERT BLANK VALUE IN PERMISSION TABLE AS WELL
            if ($is_admin) {
                $permission_data['admin_id'] = $user_id;
                $permission_data['permissions'] = json_encode(array());
                $this->db->insert('permissions', $permission_data);
            }

            $this->upload_user_image($data['image']);
            $this->session->set_flashdata('flash_message', get_phrase('user_added_successfully'));
        }
    }

    public function add_company($is_instructor = false, $is_admin = false)
    {
        $validity = $this->check_duplication('on_create', $this->input->post('email'));
        if ($validity == false) {
            $this->session->set_flashdata('error_message', get_phrase('email_duplication'));
        } else {
            $data['first_name'] = html_escape($this->input->post('first_name'));
            $data['last_name']  = html_escape($this->input->post('last_name'));
            $data['email'] = $email = html_escape($this->input->post('email'));
            $data['number_of_empolyes'] = $email = html_escape($this->input->post('number_of_empolyes'));
            $data['company_number'] = $email = html_escape($this->input->post('company_number'));
            $data['company_id']     = html_escape($this->input->post('company_id'));
            $userPass = html_escape($this->input->post('password'));
            $data['password']        =  sha1(html_escape($this->input->post('password')));
            $social_link['facebook'] = html_escape($this->input->post('facebook_link'));
            $social_link['twitter']  = html_escape($this->input->post('twitter_link'));
            $social_link['linkedin'] = html_escape($this->input->post('linkedin_link'));
            $data['social_links'] = json_encode($social_link);
            $data['biography'] = $this->input->post('biography');
            $data['role_id']   = 3;
            // echo "<pre>"; print_r($data['company_id']); exit;

            $data['date_added'] = strtotime(date("Y-m-d H:i:s"));
            $data['wishlist']   = json_encode(array());
            $data['watch_history'] = json_encode(array());
            $data['status'] = 1;
            $data['image']  = md5(rand(10000, 10000000));

            // Add paypal keys
            $paypal_info = array();
            $paypal['production_client_id']  = html_escape($this->input->post('paypal_client_id'));
            $paypal['production_secret_key'] = html_escape($this->input->post('paypal_secret_key'));
            array_push($paypal_info, $paypal);
            $data['paypal_keys'] = json_encode($paypal_info);

            // Add Stripe keys
            $stripe_info = array();
            $stripe_keys = array(
                'public_live_key' => html_escape($this->input->post('stripe_public_key')),
                'secret_live_key' => html_escape($this->input->post('stripe_secret_key'))
            );
            array_push($stripe_info, $stripe_keys);
            $data['stripe_keys'] = json_encode($stripe_info);

            if ($is_instructor) {
                $data['is_instructor'] = 1;
            }

            // activated user go1 API
                $get_login = $this->api_model->login_go1();
                $get_login_decode = json_decode($get_login);
                    if(isset($get_login_decode->access_token)) {
                        $search_user = $this->api_model->search_user($get_login_decode->access_token, $email);
                        $search_user_decode = json_decode($search_user);

                        if(isset($search_user_decode->hits[0]->id)) {
                            $data['go1_id'] = $search_user_decode->hits[0]->id;
                        
                        } else {
                            $post_user = $this->api_model->add_user_go1($get_login_decode->access_token, $data);
                            $post_user_decode = json_decode($post_user);
                            if(isset($post_user_decode->id)) {
                            $data['go1_id'] = $post_user_decode->id;
                            }
                        
                        }
                        
                    }
                    
            $this->db->insert('users', $data);
            $this->email_model->send_email_to_company_activited_by_system($data['email'], $userPass);
            $user_id = $this->db->insert_id();

            // IF THIS IS A USER THEN INSERT BLANK VALUE IN PERMISSION TABLE AS WELL
            if ($is_admin) {
                $permission_data['admin_id'] = $user_id;
                $permission_data['permissions'] = json_encode(array());
                $this->db->insert('permissions', $permission_data);
            }

            $this->upload_user_image($data['image']);
            $this->session->set_flashdata('flash_message', get_phrase('user_added_successfully'));
        }
    }

    public function add_shortcut_user($is_instructor = false)
    {
        $validity = $this->check_duplication('on_create', $this->input->post('email'));
        if ($validity == false) {
            $response['status'] = 0;
            $response['message'] = get_phrase('this_email_already_exits') . '. ' . get_phrase('please_use_another_email');
            return json_encode($response);
        } else {
            $data['first_name'] = html_escape($this->input->post('first_name'));
            $data['company_id'] = html_escape($this->input->post('company_id'));
            $data['manage_id'] = html_escape($this->input->post('manage_id'));
            $data['last_name'] = html_escape($this->input->post('last_name'));
            $data['email'] = $email = html_escape($this->input->post('email'));
            $data['password'] = sha1(html_escape($this->input->post('password')));
            $social_link['facebook'] = '';
            $social_link['twitter'] = '';
            $social_link['linkedin'] = '';
            $data['social_links'] = json_encode($social_link);
            $data['role_id'] = 2;
            $data['date_added'] = strtotime(date("Y-m-d H:i:s"));
            $data['wishlist'] = json_encode(array());
            $data['watch_history'] = json_encode(array());
            $data['status'] = 1;
            $data['image'] = md5(rand(10000, 10000000));

            // Add paypal keys
            $paypal_info = array();
            $paypal['production_client_id']  = '';
            $paypal['production_secret_key'] = '';
            array_push($paypal_info, $paypal);
            $data['paypal_keys'] = json_encode($paypal_info);

            // Add Stripe keys
            $stripe_info = array();
            $stripe_keys = array(
                'public_live_key' => '',
                'secret_live_key' => ''
            );
            array_push($stripe_info, $stripe_keys);
            $data['stripe_keys'] = json_encode($stripe_info);

            if ($is_instructor) {
                $data['is_instructor'] = 1;
            }

            // activated user go1 API
            $get_login = $this->api_model->login_go1();
            $get_login_decode = json_decode($get_login);
                if(isset($get_login_decode->access_token)) {
                    $search_user = $this->api_model->search_user($get_login_decode->access_token, $email);
                    $search_user_decode = json_decode($search_user);

                    if(isset($search_user_decode->hits[0]->id)) {
                        $data['go1_id'] = $search_user_decode->hits[0]->id;
                    
                    } else {
                        $post_user = $this->api_model->add_user_go1($get_login_decode->access_token, $data);
                        $post_user_decode = json_decode($post_user);
                        if(isset($post_user_decode->id)) {
                            $data['go1_id'] = $post_user_decode->id;
                            }
                    
                    }
                    
                }

            $this->db->insert('users', $data);
            $this->email_model->send_email_to_company_activited_by_system($email, $this->input->post('password'));

            $this->session->set_flashdata('flash_message', get_phrase('user_added_successfully'));
            $response['status'] = 1;
            return json_encode($response);
        }
    }

    public function check_duplication($action = "", $email = "", $user_id = "")
    {
        $duplicate_email_check = $this->db->get_where('users', array('email' => $email));

        if ($action == 'on_create') {
            if ($duplicate_email_check->num_rows() > 0) {
                if ($duplicate_email_check->row()->status == 1) {
                    return false;
                } else {
                    return 'unverified_user';
                }
            } else {
                return true;
            }
        } elseif ($action == 'on_update') {
            if ($duplicate_email_check->num_rows() > 0) {
                if ($duplicate_email_check->row()->id == $user_id) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        }
    }

    public function edit_user($user_id = "")
    { // Admin does this editing
        $validity = $this->check_duplication('on_update', $this->input->post('email'), $user_id);
        if ($validity) {
            $data['first_name'] = html_escape($this->input->post('first_name'));
            $data['last_name'] = html_escape($this->input->post('last_name'));

            if (isset($_POST['email'])) {
                $data['email'] = $email =  html_escape($this->input->post('email'));
            }
            $data['company_id'] = html_escape($this->input->post('company_id'));
            $social_link['facebook'] = html_escape($this->input->post('facebook_link'));
            $social_link['twitter'] = html_escape($this->input->post('twitter_link'));
            $social_link['linkedin'] = html_escape($this->input->post('linkedin_link'));
            $data['social_links'] = json_encode($social_link);
            $data['biography'] = $this->input->post('biography');
            $data['title'] = html_escape($this->input->post('title'));
            $data['skills'] = html_escape($this->input->post('skills'));
            $data['last_modified'] = strtotime(date("Y-m-d H:i:s"));
            $data['manage_id'] = html_escape($this->input->post('manage_id'));


            if (isset($_FILES['user_image']) && $_FILES['user_image']['name'] != "") {
                unlink('uploads/user_image/' . $this->db->get_where('users', array('id' => $user_id))->row('image') . '.jpg');
                $data['image'] = md5(rand(10000, 10000000));
                $this->upload_user_image($data['image']);
            }

            
            // go1 api code start
           
            // if($this->input->post('status') == 1) {
                $get_login = $this->api_model->login_go1();
                $data['status']  = $this->input->post('status');
                $get_login_decode = json_decode($get_login);
            if(isset($get_login_decode->access_token)) {
                $search_user = $this->api_model->search_user($get_login_decode->access_token, $email);
                $search_user_decode = json_decode($search_user);
                $search_user_decode = json_decode($search_user);
         
                if(isset($search_user_decode->hits[0]->id)) {
                    $data['go1_id'] = $go1_id = $search_user_decode->hits[0]->id;
                    $data['status']  = $this->input->post('status');
                    $update_user = $this->api_model->update_user_go1($get_login_decode->access_token, $data,$go1_id);
                    $this->db->where('id', $user_id);
                    $this->db->get('users');
                } else {
                    $post_user = $this->api_model->add_user_go1($get_login_decode->access_token, $data);
                    $post_user_decode = json_decode($post_user);
                    if(isset($post_user_decode->id)) {
                    $data['go1_id'] = $post_user_decode->id;
                    }
                 
                }
                // $data['status'] = 1;
              }
            // }
            $this->db->where('id', $user_id);
            $this->db->update('users', $data);
            $this->email_model->send_email_company_by_user_activition($data['email']);
            $this->session->set_flashdata('flash_message', get_phrase('user_update_successfully'));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('email_duplication'));
        }
    }

    public function edit_manage_user($user_id = "")
    { // Admin does this editing
        $validity = $this->check_duplication('on_update', $this->input->post('email'), $user_id);
        if ($validity) {
            $data['first_name'] = html_escape($this->input->post('first_name'));
            $data['last_name'] = html_escape($this->input->post('last_name'));

            if (isset($_POST['email'])) {
                $data['email'] = $email =  html_escape($this->input->post('email'));
            } else {
                $data['email'] = $email =  $this->db->get_where('users', array('id' => $user_id))->row('email'); 
            }
            if($this->input->post('status') != "" || $this->input->post('status') != NULL) { 
                $status =  $this->input->post('status'); 
            }  else {   
                $status = $this->db->get_where('users', array('id' => $user_id))->row('status'); 
            }
            if($this->input->post('role_id') != "" || $this->input->post('role_id') != NULL) { 
                $role_id =  $this->input->post('role_id'); 
            }  else {   
                $role_id = $this->db->get_where('users', array('id' => $user_id))->row('role_id'); 
            }
            if($this->input->post('manage_id') != "" || $this->input->post('manage_id') != NULL) { 
                $manage_id =  $this->input->post('manage_id'); 
            }  else {   
                $manage_id = $this->db->get_where('users', array('id' => $user_id))->row('manage_id'); 
            }
   
            $data['company_id'] = html_escape($this->input->post('company_id'));
            $social_link['facebook'] = html_escape($this->input->post('facebook_link'));
            $social_link['twitter'] = html_escape($this->input->post('twitter_link'));
            $social_link['linkedin'] = html_escape($this->input->post('linkedin_link'));
            $data['social_links'] = json_encode($social_link);
            $data['biography'] = $this->input->post('biography');
            $data['title'] = html_escape($this->input->post('title'));
            $data['skills'] = html_escape($this->input->post('skills'));
            $data['last_modified'] = strtotime(date("Y-m-d H:i:s"));
            $data['manage_id'] = $manage_id;


            if (isset($_FILES['user_image']) && $_FILES['user_image']['name'] != "") {
                unlink('uploads/user_image/' . $this->db->get_where('users', array('id' => $user_id))->row('image') . '.jpg');
                $data['image'] = md5(rand(10000, 10000000));
                $this->upload_user_image($data['image']);
            }

           
            $data['role_id'] = $role_id;
            // go1 api code start
           
            // print_r($data); die();
            $this->db->where('id', $user_id);
            $this->db->update('users', $data);
            $this->session->set_flashdata('flash_message', get_phrase('user_update_successfully'));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('email_duplication'));
        }
    }
    public function delete_user($user_id = "")
    {
        $this->db->where('id', $user_id);
        $this->db->delete('users');
        $this->session->set_flashdata('flash_message', get_phrase('user_deleted_successfully'));
    }

    public function unlock_screen_by_password($password = "")
    {
        $password = sha1($password);
        return $this->db->get_where('users', array('id' => $this->session->userdata('user_id'), 'password' => $password))->num_rows();
    }

    public function register_user($data)
    {
        $this->db->insert('users', $data);
        return $this->db->insert_id();
    }

    public function register_user_update_code($data)
    {
        $update_code['verification_code'] = $data['verification_code'];
        $update_code['password'] = $data['password'];
        $this->db->where('email', $data['email']);
        $this->db->update('users', $update_code);
    }

    public function my_courses($user_id = "")
    {
        if ($user_id == "") {
            $user_id = $this->session->userdata('user_id');
        }
        return $this->db->get_where('enrol', array('user_id' => $user_id));
    }

    public function manager_courses($user_id = "")
    {
        if ($user_id == "") {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('enrol.*');
        $this->db->join('users','users.id = enrol.user_id');
        $this->db->where('users.id', $user_id);
        return $this->db->get('enrol');
    }

    public function upload_user_image($image_code)
    {
        if (isset($_FILES['user_image']) && $_FILES['user_image']['name'] != "") {
            move_uploaded_file($_FILES['user_image']['tmp_name'], 'uploads/user_image/' . $image_code . '.jpg');
            $this->session->set_flashdata('flash_message', get_phrase('user_update_successfully'));
        }
    }

    public function update_account_settings($user_id)
    {
        $validity = $this->check_duplication('on_update', $this->input->post('email'), $user_id);
        if ($validity) {
            if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
                $user_details = $this->get_user($user_id)->row_array();
                $current_password = $this->input->post('current_password');
                $new_password = $this->input->post('new_password');
                $confirm_password = $this->input->post('confirm_password');
                if ($user_details['password'] == sha1($current_password) && $new_password == $confirm_password) {
                    $data['password'] = sha1($new_password);
                } else {
                    $this->session->set_flashdata('error_message', get_phrase('mismatch_password'));
                    return;
                }
            }
            $data['email'] = html_escape($this->input->post('email'));
            $this->db->where('id', $user_id);
            $this->db->update('users', $data);
            $this->session->set_flashdata('flash_message', get_phrase('updated_successfully'));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('email_duplication'));
        }
    }

    public function change_password($user_id)
    {
        $data = array();
        if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
            $user_details = $this->get_all_user($user_id)->row_array();
            $current_password = $this->input->post('current_password');
            $new_password = $this->input->post('new_password');
            $confirm_password = $this->input->post('confirm_password');

            if ($user_details['password'] == sha1($current_password) && $new_password == $confirm_password) {
                $data['password'] = sha1($new_password);
            } else {
                $this->session->set_flashdata('error_message', get_phrase('mismatch_password'));
                return;
            }
        }

        $this->db->where('id', $user_id);
        $this->db->update('users', $data);
        $this->session->set_flashdata('flash_message', get_phrase('password_updated'));
    }


    public function get_instructor($id = 0)
    {
        if ($id > 0) {
            return $this->db->get_where('users', array('id' => $id, 'is_instructor' => 1));
        } else {
            return $this->db->get_where('users', array('is_instructor' => 1));
        }
    }

    public function get_instructor_by_email($email = null)
    {
        return $this->db->get_where('users', array('email' => $email, 'is_instructor' => 1));
    }

    public function get_admins($id = 0)
    {
        if ($id > 0) {
            return $this->db->get_where('users', array('id' => $id, 'role_id' => 1));
        } else {
            return $this->db->get_where('users', array('role_id' => 1));
        }
    }

    public function get_all_company($id = 0)
    {
        if ($id > 0) {
            $this->db->order_by("id", "DESC");
            return $this->db->get_where('users', array('id' => $id, 'role_id' => 3));
        } else {
            $this->db->order_by("id", "DESC");
            return $this->db->get_where('users', array('role_id' => 3));
        }
    }

    public function get_number_of_active_courses_of_instructor($instructor_id)
    {
        $multi_instructor_course_ids = $this->crud_model->multi_instructor_course_ids_for_an_instructor($instructor_id);

        $this->db->where('user_id', $instructor_id);
        if ($multi_instructor_course_ids && count($multi_instructor_course_ids)) {
            $this->db->or_where_in('id', $multi_instructor_course_ids);
        }
        $result = $this->db->get('course')->num_rows();
        return $result;
    }

    public function get_user_image_url($user_id)
    {
        $user_profile_image = $this->db->get_where('users', array('id' => $user_id))->row('image');
        if (file_exists('uploads/user_image/' . $user_profile_image . '.jpg'))
            return base_url() . 'uploads/user_image/' . $user_profile_image . '.jpg';
        else
            return base_url() . 'uploads/user_image/placeholder.png';
    }
    public function get_instructor_list()
    {
        $query1 = $this->db->get_where('course', array('status' => 'active'))->result_array();
        $instructor_ids = array();
        $query_result = array();
        foreach ($query1 as $row1) {
            if (!in_array($row1['user_id'], $instructor_ids) && $row1['user_id'] != "") {
                array_push($instructor_ids, $row1['user_id']);
            }
        }
        if (count($instructor_ids) > 0) {
            $this->db->where_in('id', $instructor_ids);
            $query_result = $this->db->get('users');
        } else {
            $query_result = $this->get_admin_details();
        }

        return $query_result;
    }

    public function update_instructor_paypal_settings($user_id = '')
    {
        // Update paypal keys
        $paypal_info = array();
        $paypal['production_client_id'] = html_escape($this->input->post('paypal_client_id'));
        $paypal['production_secret_key'] = html_escape($this->input->post('paypal_secret_key'));
        array_push($paypal_info, $paypal);
        $data['paypal_keys'] = json_encode($paypal_info);
        $this->db->where('id', $user_id);
        $this->db->update('users', $data);
    }
    public function update_instructor_stripe_settings($user_id = '')
    {
        // Update Stripe keys
        $stripe_info = array();
        $stripe_keys = array(
            'public_live_key' => html_escape($this->input->post('stripe_public_key')),
            'secret_live_key' => html_escape($this->input->post('stripe_secret_key'))
        );
        array_push($stripe_info, $stripe_keys);
        $data['stripe_keys'] = json_encode($stripe_info);
        $this->db->where('id', $user_id);
        $this->db->update('users', $data);
    }

    // POST INSTRUCTOR APPLICATION FORM AND INSERT INTO DATABASE IF EVERYTHING IS OKAY
    public function post_instructor_application()
    {
        // FIRST GET THE USER DETAILS
        $user_details = $this->get_all_user($this->input->post('id'))->row_array();

        // CHECK IF THE PROVIDED ID AND EMAIL ARE COMING FROM VALID USER
        if ($user_details['email'] == $this->input->post('email')) {

            // GET PREVIOUS DATA FROM APPLICATION TABLE
            $previous_data = $this->get_applications($user_details['id'], 'user')->num_rows();
            // CHECK IF THE USER HAS SUBMITTED FORM BEFORE
            if ($previous_data > 0) {
                $this->session->set_flashdata('error_message', get_phrase('already_submitted'));
                redirect(site_url('user/become_an_instructor'), 'refresh');
            }
            $data['user_id'] = $this->input->post('id');
            $data['address'] = $this->input->post('address');
            $data['phone'] = $this->input->post('phone');
            $data['message'] = $this->input->post('message');
            if (isset($_FILES['document']) && $_FILES['document']['name'] != "") {
                if (!file_exists('uploads/document')) {
                    mkdir('uploads/document', 0777, true);
                }
                $accepted_ext = array('doc', 'docs', 'pdf', 'txt', 'png', 'jpg', 'jpeg');
                $path = $_FILES['document']['name'];
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                if (in_array(strtolower($ext), $accepted_ext)) {
                    $document_custom_name = random(15) . '.' . $ext;
                    $data['document'] = $document_custom_name;
                    move_uploaded_file($_FILES['document']['tmp_name'], 'uploads/document/' . $document_custom_name);
                } else {
                    $this->session->set_flashdata('error_message', get_phrase('invalide_file'));
                    redirect(site_url('user/become_an_instructor'), 'refresh');
                }
            }
            $this->db->insert('applications', $data);
            $this->session->set_flashdata('flash_message', get_phrase('application_submitted_successfully'));
            redirect(site_url('user/become_an_instructor'), 'refresh');
        } else {
            $this->session->set_flashdata('error_message', get_phrase('user_not_found'));
            redirect(site_url('user/become_an_instructor'), 'refresh');
        }
    }


    // GET INSTRUCTOR APPLICATIONS
    public function get_applications($id = "", $type = "")
    {
        if ($id > 0 && !empty($type)) {
            if ($type == 'user') {
                $applications = $this->db->get_where('applications', array('user_id' => $id));
                return $applications;
            } else {
                $applications = $this->db->get_where('applications', array('id' => $id));
                return $applications;
            }
        } else {
            $this->db->order_by("id", "DESC");
            $applications = $this->db->get_where('applications');
            return $applications;
        }
    }

    // GET APPROVED APPLICATIONS
    public function get_approved_applications()
    {
        $applications = $this->db->get_where('applications', array('status' => 1));
        return $applications;
    }

    // GET PENDING APPLICATIONS
    public function get_pending_applications()
    {
        $applications = $this->db->get_where('applications', array('status' => 0));
        return $applications;
    }

    //UPDATE STATUS OF INSTRUCTOR APPLICATION
    public function update_status_of_application($status, $application_id)
    {
        $application_details = $this->get_applications($application_id, 'application');
        if ($application_details->num_rows() > 0) {
            $application_details = $application_details->row_array();
            if ($status == 'approve') {
                $application_data['status'] = 1;
                $this->db->where('id', $application_id);
                $this->db->update('applications', $application_data);

                $instructor_data['is_instructor'] = 1;
                $this->db->where('id', $application_details['user_id']);
                $this->db->update('users', $instructor_data);

                $this->session->set_flashdata('flash_message', get_phrase('application_approved_successfully'));
                redirect(site_url('admin/instructor_application'), 'refresh');
            } else {
                $this->db->where('id', $application_id);
                $this->db->delete('applications');
                $this->session->set_flashdata('flash_message', get_phrase('application_deleted_successfully'));
                redirect(site_url('admin/instructor_application'), 'refresh');
            }
        } else {
            $this->session->set_flashdata('error_message', get_phrase('invalid_application'));
            redirect(site_url('admin/instructor_application'), 'refresh');
        }
    }

    // ASSIGN PERMISSION
    public function assign_permission()
    {
        $argument = html_escape($this->input->post('arg'));
        $argument = explode('-', $argument);
        $admin_id = $argument[0];
        $module = $argument[1];

        // CHECK IF IT IS A ROOT ADMIN
        if (is_root_admin($admin_id)) {
            return false;
        }

        $permission_data['admin_id'] = $admin_id;
        $previous_permissions = json_decode($this->get_admins_permission_json($permission_data['admin_id']), TRUE);

        if (in_array($module, $previous_permissions)) {
            $new_permission = array();
            foreach ($previous_permissions as $permission) {
                if ($permission != $module) {
                    array_push($new_permission, $permission);
                }
            }
        } else {
            array_push($previous_permissions, $module);
            $new_permission = $previous_permissions;
        }

        $permission_data['permissions'] = json_encode($new_permission);

        $this->db->where('admin_id', $admin_id);
        $this->db->update('permissions', $permission_data);
        return true;
    }

    // GET ADMIN'S PERMISSION JSON
    public function get_admins_permission_json($admin_id)
    {
        $admins_permissions = $this->db->get_where('permissions', ['admin_id' => $admin_id])->row_array();
        return $admins_permissions['permissions'];
    }

    // GET MULTI INSTRUCTOR DETAILS WITH COURSE ID
    public function get_multi_instructor_details_with_csv($csv)
    {
        $instructor_ids = explode(',', $csv);
        $this->db->where_in('id', $instructor_ids);
        return $this->db->get('users')->result_array();
    }

    // select company name to add user dropdown
    public function select_company_name()
    {   
        $this->db->select('u.id,u.first_name,u.last_name,u.role_id, u.status');
        $this->db->from('users u');
        $this->db->join('role r', 'r.id = u.role_id');
        $array = array('r.id' => 3, 'u.status'=>1);
        $this->db->where($array);
        return  $this->db->get()->result();
    }
     
     // select manager name to add user dropdown
    public function select_manager_name()
    {   
        $this->db->select('u.id,u.first_name,u.last_name,u.role_id, u.status');
        $this->db->from('users u');
        $this->db->join('role r', 'r.id = u.role_id');
        $array = array('r.id' => 4, 'u.status'=>1);
        $this->db->where($array);
        return  $this->db->get()->result();
    }
    
    // Get user company name;
    public function add_user_company_name($user_id=0){
        
        $full_name = '';
        if(!empty($user_id)){
         $this->db->select('u.id,u.first_name,u.last_name,u.role_id, u.status');
         $this->db->from('users u');
         $this->db->join('role r', 'r.id = u.role_id');
         $array = array('r.id' => 3, 'u.status'=>1, 'u.id'=> $user_id);
         $this->db->where($array);
         $result = $this->db->get()->result();

         $full_name='';
        foreach($result as $value){
           $full_name = $value->first_name.' '.$value->last_name;
         } 
         return $full_name;
        }else{
         return null;
        }
    }

    public function edit_company($user_id = "")
    { // Admin does this editing 
        $validity = $this->check_duplication('on_update', $this->input->post('email'), $user_id);
        if ($validity) {
            $data['first_name'] = html_escape($this->input->post('first_name'));
            $data['last_name'] = html_escape($this->input->post('last_name'));

            if (isset($_POST['email'])) {
                $data['email'] = $email =  html_escape($this->input->post('email'));
            }
            $data['company_id'] = html_escape($this->input->post('company_id'));
            $social_link['facebook'] = html_escape($this->input->post('facebook_link'));
            $social_link['twitter'] = html_escape($this->input->post('twitter_link'));
            $social_link['linkedin'] = html_escape($this->input->post('linkedin_link'));
            $data['social_links'] = json_encode($social_link);
            $data['biography'] = $this->input->post('biography');
            $data['title'] = html_escape($this->input->post('title'));
            // $data['role_id'] = html_escape($this->input->post('role_id'));
            $data['skills'] = html_escape($this->input->post('skills'));
            $data['number_of_empolyes'] = html_escape($this->input->post('number_of_empolyes'));
            $data['company_number'] = html_escape($this->input->post('company_number'));
            $data['last_modified'] = strtotime(date("Y-m-d H:i:s"));
            $data['role_id']   = 3;
            if (isset($_FILES['user_image']) && $_FILES['user_image']['name'] != "") {
                unlink('uploads/user_image/' . $this->db->get_where('users', array('id' => $user_id))->row('image') . '.jpg');
                $data['image'] = md5(rand(10000, 10000000));
                $this->upload_user_image($data['image']);
            }

            // Update paypal keys
            $paypal_info = array();
            $paypal['production_client_id']  = html_escape($this->input->post('paypal_client_id'));
            $paypal['production_secret_key'] = html_escape($this->input->post('paypal_secret_key'));
            array_push($paypal_info, $paypal);
            $data['paypal_keys'] = json_encode($paypal_info);
            // Update Stripe keys
            $stripe_info = array();
            $stripe_keys = array(
                'public_live_key' => html_escape($this->input->post('stripe_public_key')),
                'secret_live_key' => html_escape($this->input->post('stripe_secret_key'))
            );
            array_push($stripe_info, $stripe_keys);
            $data['stripe_keys'] = json_encode($stripe_info);
            // go1 api code start
            
            
                $get_login = $this->api_model->login_go1();
                $get_login_decode = json_decode($get_login);
            if(isset($get_login_decode->access_token)) {
                $search_user = $this->api_model->search_user($get_login_decode->access_token, $email);
                $search_user_decode = json_decode($search_user);
               
                if(isset($search_user_decode->hits[0]->id)) {
                    $data['go1_id'] = $go1_id = $search_user_decode->hits[0]->id;
                    $data['status']  = $this->input->post('status');
                    $update_user = $this->api_model->update_user_go1($get_login_decode->access_token, $data,$go1_id);
                    $this->db->where('company_id', $user_id);
                    $result_company_users =  $this->db->get('users')->result_array();
                    // print_r($update_user); die();
                    if(!empty($result_company_users)){
                    foreach($result_company_users as $company_user) {
                        $user_data['go1_id'] = $company_user['go1_id'];
                        $user_data['first_name'] = $company_user['first_name'];
                        $user_data['last_name'] = $company_user['last_name'];
                        $user_data['status'] =  $data['status'];
                        $user_data['company_id'] =  $company_user['company_id'];
                        $update_user = $this->api_model->update_user_go1($get_login_decode->access_token, $user_data,$user_data['go1_id']);
                        $this->db->where('id', $company_user['id']);
                        $this->db->update('users', $user_data);
                    }
                }
                   
                 
                } else {
                    $post_user = $this->api_model->add_user_go1($get_login_decode->access_token, $data);
                    $post_user_decode = json_decode($post_user);
                    if(isset($post_user_decode->id)) {
                    $data['go1_id'] = $post_user_decode->id;
                    }
                 
                }
                // $data['status'] = 1;
              } 

            $this->db->where('id', $user_id);
            $this->db->update('users', $data);
            $this->email_model->send_email_to_company_activited_by_system($data['email']);
            $this->session->set_flashdata('flash_message', get_phrase('user_update_successfully'));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('email_duplication'));
        }
    }

    public function add_manager($is_instructor = false, $is_admin = false)
    {
        $validity = $this->check_duplication('on_create', $this->input->post('email'));
        if ($validity == false) {
            $this->session->set_flashdata('error_message', get_phrase('email_duplication'));
        } else {
            $data['first_name'] = html_escape($this->input->post('first_name'));
            $data['last_name'] = html_escape($this->input->post('last_name'));
            $data['email'] = $email = html_escape($this->input->post('email'));
            $data['company_id'] = html_escape($this->input->post('company_id'));
            $data['manage_id'] = html_escape($this->input->post('manage_id'));
            $userPass = html_escape($this->input->post('password'));
            $data['password'] = sha1(html_escape($this->input->post('password')));
            $social_link['facebook'] = html_escape($this->input->post('facebook_link'));
            $social_link['twitter'] = html_escape($this->input->post('twitter_link'));
            $social_link['linkedin'] = html_escape($this->input->post('linkedin_link'));
            $data['social_links'] = json_encode($social_link);
            $data['biography'] = $this->input->post('biography');

            if ($is_admin) {
                $data['role_id'] = 3;
                $data['is_instructor'] = 1;
            } else {
                $data['role_id'] = 4;
                $data['is_manager'] = html_escape($this->input->post('is_manager'));
            }
            // echo "<pre>"; print_r($data['company_id']); exit;

            $data['date_added'] = strtotime(date("Y-m-d H:i:s"));
            $data['wishlist'] = json_encode(array());
            $data['watch_history'] = json_encode(array());
            $data['status'] = 1;
            $data['image'] = md5(rand(10000, 10000000));

            // Add paypal keys
            $paypal_info = array();
            $paypal['production_client_id']  = html_escape($this->input->post('paypal_client_id'));
            $paypal['production_secret_key'] = html_escape($this->input->post('paypal_secret_key'));
            array_push($paypal_info, $paypal);
            $data['paypal_keys'] = json_encode($paypal_info);

            // Add Stripe keys
            $stripe_info = array();
            $stripe_keys = array(
                'public_live_key' => html_escape($this->input->post('stripe_public_key')),
                'secret_live_key' => html_escape($this->input->post('stripe_secret_key'))
            );
            array_push($stripe_info, $stripe_keys);
            $data['stripe_keys'] = json_encode($stripe_info);

            if ($is_instructor) {
                $data['is_instructor'] = 1;
            }

            // activated user go1 API
                $get_login = $this->api_model->login_go1();
                $get_login_decode = json_decode($get_login);
                    if(isset($get_login_decode->access_token)) {
                        $search_user = $this->api_model->search_user($get_login_decode->access_token, $email);
                        $search_user_decode = json_decode($search_user);

                        if(isset($search_user_decode->hits[0]->id)) {
                            $data['go1_id'] = $search_user_decode->hits[0]->id;
                            $update_user = $this->api_model->update_user_go1($get_login_decode->access_token, $data,$data['go1_id']);
                        
                        } else {
                            $post_user = $this->api_model->add_user_go1($get_login_decode->access_token, $data);
                            $post_user_decode = json_decode($post_user);
                            if(isset($post_user_decode->id)) {
                            $data['go1_id'] = $post_user_decode->id;
                            }
                        
                        }
                        
                    }

            $this->db->insert('users', $data);
           $this->email_model->send_email_company_by_user_activition($data['email'], $userPass);
            $user_id = $this->db->insert_id();

            // IF THIS IS A USER THEN INSERT BLANK VALUE IN PERMISSION TABLE AS WELL
            if ($is_admin) {
                $permission_data['admin_id'] = $user_id;
                $permission_data['permissions'] = json_encode(array());
                $this->db->insert('permissions', $permission_data);
            }

            $this->upload_user_image($data['image']);
            $this->session->set_flashdata('flash_message', get_phrase('user_added_successfully'));
        }
    }

    public function edit_manager($user_id = "")
    { // Admin does this editing

        $validity = $this->check_duplication('on_update', $this->input->post('email'), $user_id);
        if ($validity) {
            $data['first_name'] = html_escape($this->input->post('first_name'));
            $data['last_name'] = html_escape($this->input->post('last_name'));

            if (isset($_POST['email'])) {
                $data['email'] = $email =  html_escape($this->input->post('email'));
            }
            $data['company_id'] = html_escape($this->input->post('company_id'));
            $data['manage_id']  = html_escape($this->input->post('manage_id'));
            $social_link['facebook'] = html_escape($this->input->post('facebook_link'));
            $social_link['twitter']  = html_escape($this->input->post('twitter_link'));
            $social_link['linkedin'] = html_escape($this->input->post('linkedin_link'));
            $data['social_links'] = json_encode($social_link);
            $data['biography'] = $this->input->post('biography');
            $data['title']  = html_escape($this->input->post('title'));
            $data['skills'] = html_escape($this->input->post('skills'));
            $data['last_modified'] = strtotime(date("Y-m-d H:i:s"));
            $data['is_manager']    = html_escape($this->input->post('is_manager'));
            if (isset($_FILES['user_image']) && $_FILES['user_image']['name'] != "") {
                unlink('uploads/user_image/' . $this->db->get_where('users', array('id' => $user_id))->row('image') . '.jpg');
                $data['image'] = md5(rand(10000, 10000000));
                $this->upload_user_image($data['image']);
            }
           

            // Update paypal keys
            $paypal_info = array();
            $paypal['production_client_id']  = html_escape($this->input->post('paypal_client_id'));
            $paypal['production_secret_key'] = html_escape($this->input->post('paypal_secret_key'));
            array_push($paypal_info, $paypal);
            $data['paypal_keys'] = json_encode($paypal_info);
            // Update Stripe keys
            $stripe_info = array();
            $stripe_keys = array(
                'public_live_key' => html_escape($this->input->post('stripe_public_key')),
                'secret_live_key' => html_escape($this->input->post('stripe_secret_key'))
            );
            array_push($stripe_info, $stripe_keys);
            $data['stripe_keys'] = json_encode($stripe_info);
            // go1 api code start
           
            // if($this->input->post('status') == 1) {
                $get_login = $this->api_model->login_go1();
                $data['status']  = $this->input->post('status');
                $get_login_decode = json_decode($get_login);
            if(isset($get_login_decode->access_token)) {
                $search_user = $this->api_model->search_user($get_login_decode->access_token, $email);
                $search_user_decode = json_decode($search_user);
                $search_user_decode = json_decode($search_user);
         
                if(isset($search_user_decode->hits[0]->id)) {
                    $data['go1_id'] = $go1_id = $search_user_decode->hits[0]->id;
                    $data['status']  = $this->input->post('status');
                    $update_user = $this->api_model->update_user_go1($get_login_decode->access_token, $data,$go1_id);
                    $this->db->where('id', $user_id);
                    $this->db->get('users');
                } else {
                    $post_user = $this->api_model->add_user_go1($get_login_decode->access_token, $data);
                    $post_user_decode = json_decode($post_user);
                    if(isset($post_user_decode->id)) {
                    $data['go1_id'] = $post_user_decode->id;
                    }
                 
                }
                // $data['status'] = 1;
              }
            // }
            $this->db->where('id', $user_id);
            $this->db->update('users', $data);
            $this->email_model->send_email_company_by_user_activition($data['email']);
            $this->session->set_flashdata('flash_message', get_phrase('user_update_successfully'));
        } else {
            $this->session->set_flashdata('error_message', get_phrase('email_duplication'));
        }
    }

    public function get_user_full_name($user_id= 0){
        if(!empty($user_id)){
          $this->db->where('id', $user_id);
        }
        $query = $this->db->get('users');
        $ret = $query->row();
        $full_name = $ret->first_name.' '.$ret->last_name;
        return $full_name;
    }
    
}