<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->database();
        $this->load->library('session');
       
    }
      // code by kashif islam catalauge code for getting results from api 
      public function get_catalague($action = '')
      {
          ini_set('display_errors', 1);
          ini_set('max_execution_time', 0); 
          ini_set('memory_limit','2048M');
     
  
          // CHECK ACCESS PERMISSION
          check_permission('catalague');
          // get language name from iso code
          $codes    = getLanguageCode();
         
          // api code start 
          // login api to get access token 
          $get_login = $this->api_model->login_go1();
          $get_login_decode = json_decode($get_login);
      
          if(isset($get_login_decode->access_token)) {
              $array = $this->crud_model->go1Array();
              $arr = 0;
              $count = 0;
              while ($arr <= count($array)) {
                 
                  $value_id = $array[$arr];
                  $arr++;
                 
                 
              
             // get course from db if exist
             $course_details = $this->crud_model->get_course_by_api_id($value_id)->row_array();
             if(empty($course_details) || $course_details == "") {
                if ($count > 100) { break; }
                 $count++; 
             // get catalauge
              $get_catalauge = $this->api_model->catalauge_response($get_login_decode->access_token, $value_id);
              $catalague_result = json_decode($get_catalauge);
              
              $get_catalauge_play = $this->api_model->catalauge_play_response($get_login_decode->access_token,$value_id);
                  $get_catalauge_play_decode = json_decode($get_catalauge_play);
                  
                  if(isset($catalague_result->title)) {
                    
                  $cat_value = array();
                  $i = 0;
                  foreach($catalague_result->attributes->topics as $cataguary) {
                     
                      
                      if(isset($cataguary->value)) {
                          
                          
                          $course_details = $this->crud_model->get_cat_by_api_id($cataguary->value)->row_array();
                          
                          
                          if(empty($course_details) ||  $course_details == "") {
                              if($i == 0) {
                                  $parent = 0;
                                  $icon = "fab fa-500px";
                              } else {
                                  $parent = $cat_value[0];
                                  $icon = "fab fa-accessible-icon";
                              }
                              $cataguary_add = [
                                  'name'=>$cataguary->value,
                                  'code'=>$cataguary->key,
                                  'slug'=>slugify(html_escape($cataguary->value)),
                                  'parent'=>$parent,
                                  'font_awesome_class'=>$icon,
                                  'date_added'=>strtotime($catalague_result->created_time),
                                  'last_modified'=>strtotime($catalague_result->updated_time),
                                  
                              ];
                              $cat_value[] =  $this->crud_model->add_category_api($cataguary_add);
  
                          } else {
                              $cat_value[] = $course_details['id'];
                          }
                      }
                      $i++;
                    
                  }
                  
                  $outcomes = json_encode(array());
                  if(isset($catalague_result->attributes->learning_outcomes)) {
                  $outcomes = $this->crud_model->trim_and_return_json($catalague_result->attributes->learning_outcomes);
                  }
                  $skills = json_encode(array());
                  if(isset($catalague_result->attributes->skills)) {
                  $skills = $this->crud_model->trim_and_return_json($catalague_result->attributes->skills);
                  }
              if(isset($catalague_result->authors)) {
                  $author_values = array();
                  foreach($catalague_result->authors as $key => $authors) {
                    if(isset($authors->name)) {
                      $author_values[] = $authors->name;
                    } else {
                      $author_values[] = $authors->first_name .' '. $authors->last_name; 
                    }
                  
                  }
                  $author_result =  implode(',',$author_values);
  
              }
                 
                  $cat_values = array();
                  foreach($cat_value as $key => $val) {
                    if($key != 0) {
                      $cat_values[] = $val;
                    } 
                  }
                  $sub_catagoury = "";
                  if(isset($cat_values)) {
                      // array_shift($cat_value);
                     $sub_catagoury =  implode(',',$cat_values);
                  }
                  $course_add = [
                      'title'=>$catalague_result->title,
                      'description'=>$catalague_result->description,
                      'short_description'=> substr($catalague_result->description, 0, 200),
                      'language'=>isset($codes[$catalague_result->language]) ? $codes[$catalague_result->language] : '',
                      'is_admin'=>1,
                      'course_type'=>'general',
                      'category_id'=>isset($cat_value[0]) ?  $cat_value[0] : '',
                      'sub_category_id'=>$sub_catagoury,
                      'date_added'=>strtotime($catalague_result->created_time),
                      'thumbnail'=>$catalague_result->image,
                      'last_modified'=>strtotime($catalague_result->updated_time),
                      'price'=> 0,
                      'level'=> isset($catalague_result->attributes->entry_level->value) ? $catalague_result->attributes->entry_level->value : '',
                      'outcomes'=>$outcomes,
                      'requirements'=>$skills,
                      'multi_instructor'=>1,
                      'section'=>json_encode(array()),
                      'course_overview_provider'=>"html5",
                      'is_free_course'=> 1,
                      'meta_keywords'=>isset($catalague_result->tags) ? implode(', ', $catalague_result->tags) : '',
                      'meta_description'=>isset($catalague_result->summary) ?  $catalague_result->summary : '',
                      'status'=>'active',
                      'video_url'=>isset($get_catalauge_play_decode->player) ? $get_catalauge_play_decode->player : $catalague_result->image,
                      'user_id'=>$this->session->userdata('user_id'),
                      'creator'=>$this->session->userdata('user_id'),
                      'api_id'=>$catalague_result->id,
                      'provider_author'=> isset($author_result) ? $author_result : '',
                      'is_top_course'=> isset($catalague_result->attributes->featured_status) ? $catalague_result->attributes->featured_status : '',
                  ];
                  
                  $course_add_id =  $this->crud_model->add_course_api($course_add);
  
                  // section table code start
                  $section = [
                      'title'=>html_escape($catalague_result->title),
                      'course_id'=>$course_add_id,
                  ];
  
                  $section_id =  $this->crud_model->add_section_api($section);
  
                  // check duration
                      $duration = '00:00:00';
                      $seconds = 00;
                      if(isset($catalague_result->delivery->duration) && $catalague_result->delivery->duration != "") {
                          $time = $catalague_result->delivery->duration; 
                          $format = '%02d:%02d:00'; 
                          $hours = floor($time / 60);
                          $minutes = ($time % 60);
                          
                          $duration =  sprintf($format, $hours, $minutes,$seconds);
                      }
  
                  $lesson_add = [
                      'title'=>$catalague_result->title,
                      'duration'=>$duration,
                      'video_type'=>'html5',
                      'video_url'=>isset($get_catalauge_play_decode->player) ? $get_catalauge_play_decode->player : '',
                      'date_added'=>strtotime($catalague_result->created_time),
                      'lesson_type'=>'html5',
                      'last_modified'=>strtotime($catalague_result->updated_time),
                      'attachment'=> isset($get_catalauge_play_decode->player) ? $get_catalauge_play_decode->player : '',
                      'section_id'=> $section_id,
                      'course_id'=>$course_add_id,
                      'attachment_type'=> 'iframe',
                      'video_url_for_mobile_application'=>isset($get_catalauge_play_decode->player) ? $get_catalauge_play_decode->player : '',
                      'summary'=>isset($catalague_result->summary) ?  $catalague_result->summary : '',
                      'video_type_for_mobile_application'=>'html5',
                      'video_url_for_mobile_application'=>isset($get_catalauge_play_decode->player) ? $get_catalauge_play_decode->player : '',
                      'duration_for_mobile_application'=>$duration,
                      'is_free'=> 1,
                     
                  ];
                  $lesson_add_id =  $this->crud_model->add_lesson_api($lesson_add);
  
                  if(isset($catalague_result->items)) {
                      foreach($catalague_result->items as $items_value) {
                          
                          $section = [
                              'title'=>html_escape($items_value->title),
                              'course_id'=>$course_add_id,
                          ];
          
                          $section_id_value =  $this->crud_model->add_section_api($section);
                          if(isset($items_value->items)) {
                              foreach($items_value->items as $items_sub) {
                              
  
                                              $lesson_add = [
                                                  'title'=>$items_sub->title,
                                                  'duration'=> '',
                                                  'video_type'=>'html5',
                                                  'video_url'=>"",
                                                  'date_added'=>isset($items_sub->created_time) ? strtotime($items_sub->created_time) : '',
                                                  'lesson_type'=>'html5',
                                                  'last_modified'=>isset($items_sub->updated_time) ?strtotime($items_sub->updated_time) : '',
                                                  'attachment'=> "",
                                                  'section_id'=> $section_id_value,
                                                  'course_id'=>$course_add_id,
                                                  'attachment_type'=> $items_sub->type,
                                                  'video_url_for_mobile_application'=>"",
                                                  'summary'=>isset($items_sub->summary) ?  $items_sub->summary : '',
                                                  'video_type_for_mobile_application'=>'html5',
                                                  'video_url_for_mobile_application'=>"",
                                                  'duration_for_mobile_application'=>'',
                                                  'is_free'=> 1,
                                              ];
                                               $this->crud_model->add_lesson_api($lesson_add);
                              }
                          }
  
                      }
                  }
          
             
                  if(!file_exists('uploads/thumbnails/lesson_thumbnails/'.$lesson_add_id.'.jpg')) {
                      $content = file_get_contents($catalague_result->image);
                      file_put_contents('uploads/thumbnails/lesson_thumbnails/'.$lesson_add_id.'.jpg', $content);
                  }
  
                 } else {
                      echo "<pre>";
                      print_r($value_id."wrong"); 
                      echo "</pre>";
                  }
                 
                  
                  
              }
              }
          }
          
         
          die();
  
          $page_data['page_name']  = 'theme_settings';
          $page_data['page_title'] = get_phrase('theme_settings');
          $this->load->view('backend/index', $page_data);
      }
      // end code catalauge api
  
      // code for course status update
      public function update_enrol_course_status($action = '') 
      {
          ini_set('display_errors', 1);
          ini_set('max_execution_time', 0); 
          ini_set('memory_limit','2048M');
  
          $get_login = $this->api_model->login_go1();
          $get_login_decode = json_decode($get_login);
          if(isset($get_login_decode->access_token)) {
              $this->db->select('enrol.id,enrol_go1_id');

              $enrol_reult =  $this->db->get('enrol')->result_array();
              
              foreach($enrol_reult as $enrol) {
                  $enrol_api = $this->api_model->get_status_course($get_login_decode->access_token, $enrol['enrol_go1_id']);
                  $enrol_api_decode = json_decode($enrol_api);
                 
                    $data['course_status'] = "in-progress";
                    if(isset($enrol_api_decode->status)) {
                       $data['course_status'] = $enrol_api_decodestatus;
                    }
  
                    $this->db->where('id', $enrol['id']);
                    $this->db->update('enrol', $data);
              }
          }
       
          die();
  
      }

}