<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Moderate extends CI_Controller 
{
	
	/**
	* @var stirng
	* @access Public
	*/
	public $selected_tab = '';
    public $title = '';
    public $title_s = '';
	
	/** 
	* Controller constructor
	* 
	* @access public 
	*/

	public function __construct()
	{
		parent::__construct();
        $this->load->database();
        $this->load->library('session');
        /*cache control*/
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');

		$this->selected_tab = 'moderate';
        $this->title = 'Moderate';
        $this->title_s = 'Moderate';
		//$this->layout = 'template';
        $this->load->model('course_model', 'course');
		// if($this->session->userdata('super_admin_login') != true){
  //           redirect(site_url('login'), 'refresh');
  //       }
	}
	
	// public function index()
	// {
	// 	$data = [];
 //        $where = "id > 0";
 //        $data['products'] = $this->products->get_where('*', $where, true, '' , '', '');
 //        $where = "status = 1";
 //        $data['users'] = $this->users->get_where('*', $where, true, '' , '', '');
 //        $data['min_date'] = date('Y-m-d');
	// 	$this->load->view('admin/coupons/index', $data);
	// }

    public function process_add()
    {
        $data = [];
        $this->layout = " ";
        if(!$this->input->is_ajax_request()){
           exit('No direct script access allowed');
        }
        $data['response'] = false;
        $formData = $this->input->post();
        $checkPrivacy = json_encode($formData);
        if(empty($this->input->post('product_id'))){
            $this->form_validation->set_rules('product_id','products','required|trim');
        }
        $this->form_validation->set_rules('title','title','required|trim|callback_is_coupon_exists');
        $this->form_validation->set_rules('discount_percentage','discount percentage','required|trim|numeric|callback_is_valid_discount_percentage');
        $this->form_validation->set_rules('end_date','end date','required|trim');
        $this->form_validation->set_rules('code','code','required|trim');
        $this->form_validation->set_rules('is_active','status','required|trim');
        $this->form_validation->set_rules('privacy','privacy','required|trim|callback_check_privacy['.$checkPrivacy.']');
        if($this->form_validation->run()===TRUE){
            $product_ids = $formData['product_id'];
            unset($formData['product_id']);
            if(!empty($formData['user_id'])){
                $user_ids = $formData['user_id'];
            }
            if(isset($formData['user_id'])){
                unset($formData['user_id']);
            }
            //debug($this->input->post() , true);
            if($formData['is_active']==1 && $formData['privacy']==1){
                $where = "id > 0 AND privacy = 1";
                $this->coupons->update_by_where(['is_active'=>0],$where);
            }
            $id = $this->coupons->save($formData);
            foreach ($product_ids as $key => $value) {
                $save = [];
                $save['coupon_id'] = $id;
                $save['product_id'] = $value;
                $this->coupon_products->save($save);
            }
            if(!empty($user_ids) && $formData['privacy']==2){
                foreach ($user_ids as $key => $value) {
                    $save = [];
                    $save['coupon_id'] = $id;
                    $save['user_id'] = $value;
                    //$where = "user_id = '".$value."'";
                    //$result = $this->coupon_users->update_by_where(['is_active'=>0], $where);
                    $this->coupon_users->save($save);
                }
            }
            $data['response'] = true;
        }
        else{
            $data['errors'] = all_errors($this->form_validation->error_array());
        }
        echo json_encode($data);
    }

    public function fetch_courses()
    {
        $data = [];
        if(!$this->input->is_ajax_request()){
            exit;
        }
        $title = $_GET["q"];
        if(strlen($title) >= 3){
            $where = "status = 'active' AND course.title LIKE CONCAT('%','" . $title . "' ,'%')";
            $result = $this->course->get_where('*', $where, true, '' , '', '');
            if(!empty($result)){
                $allData = [];
                foreach($result as $key => $value){
                    $course = [];
                    $course['id'] = $value["id"];
                    $course['title'] = $value["title"];
                    $allData[] = $course;
                }
                $data = $allData;
            }
        }
        echo json_encode($data);
    }

    public function process_update()
    {
        $data = [];
        $this->layout = " ";
        if(!$this->input->is_ajax_request()){
            exit('No direct script access allowed');
        }
        $data['response'] = false;
        $data['image_error'] = '';
        $id = $this->input->post('id');
        $formData = $this->input->post();
        $checkPrivacy = json_encode($formData);
        if(empty($this->input->post('product_id'))){
            $this->form_validation->set_rules('product_id','products','required|trim');
        }
        $this->form_validation->set_rules('title','title','required|trim|callback_is_coupon_exists['.$id.']');
        $this->form_validation->set_rules('discount_percentage','discount percentage','required|trim|numeric|callback_is_valid_discount_percentage');
        $this->form_validation->set_rules('end_date','end date','required|trim');
        $this->form_validation->set_rules('code','code','required|trim');
        $this->form_validation->set_rules('is_active','status','required|trim');
        $this->form_validation->set_rules('privacy','privacy','required|trim|callback_check_privacy['.$checkPrivacy.']');
        if($this->form_validation->run()===TRUE){
            $formData = $this->input->post();
            $id = $formData['id'];
            $product_ids = $formData['product_id'];
            unset($formData['id'] , $formData['product_id']);
            if(!empty($formData['user_id'])){
                $user_ids = $formData['user_id'];
            }
            if(isset($formData['user_id'])){
                unset($formData['user_id']);
            }
            if($formData['is_active']==1 && $formData['privacy']==1){
                $where = "id > 0 AND privacy = 1";
                $this->coupons->update_by_where(['is_active'=>0],$where);
            }
            $this->coupons->update_by('id',$id,$formData);
            $where = "coupon_id = '".$id."'";
            $this->coupon_products->delete_where($where);
            foreach ($product_ids as $key => $value) {
                $save = [];
                $save['coupon_id'] = $id;
                $save['product_id'] = $value;
                $this->coupon_products->save($save);
            }
            $where = "coupon_id = '".$id."'";
            $this->coupon_users->delete_where($where);
            if(!empty($user_ids) && $formData['privacy']==2){
                //$where = "coupon_id = '".$id."'";
                //$result = $this->coupon_users->update_by_where(['is_active'=>0], $where);
                foreach ($user_ids as $key => $value) {
                    $save = [];
                    $save['coupon_id'] = $id;
                    $save['user_id'] = $value;
                    $this->coupon_users->save($save);
                }
            }
            $data['response'] = true;
        }
        else{
            $data['errors'] = all_errors($this->form_validation->error_array());
        }
        echo json_encode($data);
    }

    public function is_coupon_exists($str, $id = 'ci_validation')
    {
        if(!empty($str)){
            $where = "title = '".$str."'";
            if($id!='ci_validation'){
                $where .= " AND id != '".$id."'";
            }
            $result = $this->coupons->get_where('*', $where, true, '' , '', '');
            if(empty($result)){
                return TRUE;
            }
            else{
                $this->form_validation->set_message('is_coupon_exists', 'This %s is already taken.');
                return FALSE;
            }
        }
    }

    public function is_valid_discount_percentage($str = '')
    {
        if(!empty($str)){
            if($str > 100 || $str < 1){
                $this->form_validation->set_message('is_valid_discount_percentage', 'This %s is invalid.');
                return FALSE;
            }
            return TRUE;
        }
    }

    public function get_datatable()
    {
        $this->layout = '';
        $like = [];
        $result_array = [];

        $orderByColumnIndex = $_POST['order'][0]['column'];
        $orderByColumn = $_POST['columns'][$orderByColumnIndex]['data'];
        $orderType = $_POST['order'][0]['dir'];
        $offset = $this->input->post('start');
        $limit = $this->input->post('length');
        $draw = $this->input->post('draw');
        $search = $_POST['search']['value'];
        // $user_id = $this->input->post('user_id');
        
        $where = "coupons.id > 0";
        $result_count = $this->coupons->count_rows($where);

        // if(isset($status_filter) && $status_filter != ''){
        //     $where .= " AND orders.status ='".$status_filter."'";
        // }

        if (isset($search) && $search != '') {
            $where .= " AND (coupons.title  LIKE CONCAT('%','" . $search . "' ,'%') OR coupons.code  LIKE CONCAT('%','" . $search . "' ,'%'))";
        }

        $joins = array(
            // '0' => array('table_name' => 'deal_products deal_products',
            //     'join_on' => 'deal_products.deal_id = deals.id',
            //     'join_type' => 'left'
            // )
        );
        $from_table = "coupons coupons";
        $select_from_table = 'coupons.*';
        $result_data = $this->coupons->get_by_join($select_from_table, $from_table, $joins, $where, $orderByColumn, $orderType, '', '', '', '', $limit, $offset , '' ,'');
        //debug($result_data,true);
        $result_count_rows = $this->coupons->get_by_join_total_rows('*', $from_table, $joins, $where, $orderByColumn, $orderType, '', '', '', '', '', '');

        if (isset($result_data)) {
            foreach ($result_data as $item) {
                $single_field['title'] = $item['title'];
                $single_field['discount_percentage'] = $item['discount_percentage'].'%';
                $single_field['code'] = $item['code'];
                $single_field['end_date'] = $item['end_date'];
                $single_field['status'] = (!empty($item['is_active']))?'Active':'Inactive';
                $single_field['privacy'] = ($item['privacy']==1)?'Public':'Private';

                $where = "coupon_id = '".$item['id']."'";
                $joins = array(
                    '0' => array('table_name' => 'products products',
                        'join_on' => 'products.id = coupon_products.product_id',
                        'join_type' => 'left'
                    )
                );
                $from_table = "coupon_products coupon_products";
                $select_from_table = 'products.title as product_title';
                $result = $this->coupon_products->get_by_join($select_from_table, $from_table, $joins, $where, '', '', '', '', '', '','', '' , '' ,'');
                $products = '';
                foreach ($result as $key => $value) {
                    $products .= $value['product_title'].' | ';
                }
                $products = implode( '|', array_slice( explode( '|', $products ), 0, -1 ) );
                $single_field['products'] = $products;

                $single_field['created_at'] = $item['created_at'];
                $single_field['action'] = '<a href="javascript::" style="margin-top:3px;" rel="'.$item['id'].'" class="update_id btn btn-xs btn-info">Edit</a> <a href="javascript::" style="margin-top:3px;" rel="'.$item['id'].'" class="delete_id btn btn-xs btn-danger">Delete</a>';
                //$single_field['action'] = '<a href="javascript::" rel="'.$item['id'].'" class="update_id">Edit</a> | <a href="javascript::" rel="'.$item['id'].'" class="delete_id">Delete</a>';
                $result_array[] = $single_field;
            }

            $data['draw'] = $draw;
            $data['recordsTotal'] = $result_count;
            $data['recordsFiltered'] = $result_count_rows;
            $data['data'] = $result_array;
        } else {
            $data['draw'] = $draw;
            $data['recordsTotal'] = 0;
            $data['recordsFiltered'] = 0;
            $data['data'] = '';
        }
        //debug($data , true);
        echo json_encode($data);
    }

    public function delete($id = 0)
    {
        $this->layout = " ";
        $data = [];
        if(!$this->input->is_ajax_request()){
            exit;
        }
        $data['response'] = false;
        $where = "id = '".$id."'";
        $result = $this->coupons->get_where('*', $where, true, '' , '', '');
        if(!empty($result)){
            $update = ['is_deleted' => 1,'is_active' => 0];
            $this->coupons->update_by('id', $id, $update);
            //$this->coupons->delete_by('id', $id);
            $data['response'] = true;
        }
        echo json_encode($data);
    }

}