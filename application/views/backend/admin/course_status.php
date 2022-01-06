<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i>
                    <?php echo $page_title; ?>
                    <!-- <a href="<?php echo site_url('admin/user_form/add_user_form'); ?>"
                        class="btn btn-outline-primary btn-rounded alignToTitle"><i
                            class="mdi mdi-plus"></i><?php echo get_phrase('add_student'); ?></a> -->
                </h4>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <?php
                $cr_status = '';
                $cr_user_id = 0;
                if(!empty($this->input->post("course_status")))
                {
                    $cr_status = $this->input->post("course_status");
                }
                if(!empty($this->input->post("user_id"))){
                    $cr_user_id = $this->input->post("user_id");
                }
                ?>
                <form action="" method="post" id="filtersForm">
                <div class="row" style="margin-bottom: 10px;">
                    <div class="col-md-6">
                      <div class="form-group row">
                        <label for="inputPassword" class="col-sm-3 col-form-label">Course Status</label>
                        <div class="col-sm-9">
                            <select name="course_status" class="form-control select2 filterInputs">
                                <option value="">Select Status</option>
                                <?php
                                $cs_arr = ["not-started","completed","in-progress","Not yet Started"];
                                foreach($cs_arr as $key => $value){
                                ?>
                                <option value="<?php echo $value;?>" <?php echo ($value==$cr_status)?"selected":'';?>><?php echo $value;?></option>
                                <?php } ?>
                            </select>
                        </div>
                      </div>
                    </div>
                    <?php
                    $company_users = companyUsers();
                    ?>
                    <div class="col-md-6">
                      <div class="form-group row">
                        <label for="inputPassword" class="col-sm-3 col-form-label">Select User</label>
                        <div class="col-sm-9">
                            <select name="user_id" class="form-control select2 filterInputs">
                                <option value="">Select Status</option>
                                <?php
                                foreach($company_users as $key => $value){
                                ?>
                                <option value="<?php echo $value["id"];?>" <?php echo ($value["id"]==$cr_user_id)?"selected":'';?>><?php echo $value["first_name"]." ".$value["last_name"];?></option>
                                <?php } ?>
                            </select>
                        </div>
                      </div>
                    </div>
                </div>
                </form>
                <script type="text/javascript">
                $(document).ready(function(){
                    $(document).on("change",".filterInputs",function(e){
                        e.preventDefault();
                        var val = $(this).val();
                        if(val!=''){
                            $("#filtersForm").submit();
                        }
                    });
                });
                </script>
                <div class="table-responsive-sm mt-4">
                    <table id="basic-datatable" class="table table-striped table-centered mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo get_phrase('photo'); ?></th>
                                <th><?php echo get_phrase('name'); ?></th>
                                <th><?php echo get_phrase('Enrolment_date'); ?></th>
                                <th><?php echo get_phrase('last_date'); ?></th>
                                <th><?php echo get_phrase('enrolled_courses'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                                <th><?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $users = $enrolled_courses = $this->crud_model->enrol_history_by_company_id($cr_status , $cr_user_id);
                            //echo "<pre>"; print_r($users->result_array()); exit;
                            foreach ($users->result_array() as $key => $user) : ?>
                            <tr>
                                <td><?php echo $key + 1; ?></td>
                                <td>
                                    <img src="<?php echo $this->user_model->get_user_image_url($user['id']); ?>" alt=""
                                        height="50" width="50" class="img-fluid rounded-circle img-thumbnail">
                                </td>
                                <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?>
                                    <?php if ($user['status'] != 1) : ?>
                                    <small>
                                        <p><?php echo get_phrase('status'); ?>: <span
                                                class="badge badge-danger-lighten"><?php echo get_phrase('Un_Active'); ?></span>
                                        </p>
                                    </small>
                                    <?php else : ?>
                                    <small>
                                        <p><?php echo get_phrase('status'); ?>: <span
                                                class="badge badge-info-lighten"><?php echo get_phrase('Active'); ?></span>
                                        </p>
                                    </small>
                                    <?php  endif; ?>
                                </td>
                                <td><?php echo date('D, d-M-Y', $user['date_added']); ?></td>
                                <td><?php echo date('D, d-M-Y', $user['enrol_last_date']); ?></td>
                                <td>
                                        <?php $course_details = $this->crud_model->get_course_by_id($user['course_id'])->row_array();
                                         echo $course_details['title']; ?>
                                 </td>
                                 <td>
                                        <?php echo $user['course_status']; ?>
                                 </td>
                                <td>
                                    <div class="dropright dropright">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary btn-rounded btn-icon"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="mdi mdi-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item"
                                                    href="<?php echo site_url('admin/course_status/email_send/' . $user['id'].'/'.$user['course_id']) ?>"><?php echo get_phrase('Email_send'); ?></a>
                                            </li>
                                           
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>