<?php 
    $nav = $nav_user ='';
  if($this->session->userdata('admin_login') == 1 || $this->session->userdata('manager_login')== 1){
    $nav = 'navbar-custom topnav-navbar topnav-navbar-danger';
    $nav_user = 'nav-user-custom';
  }else{
    $nav = 'navbar-custom topnav-navbar topnav-navbar-dark';
    $nav_user = 'nav-user';
  }
?>
<style>
   .topnav-navbar-danger {background-color: #ec5252 !important; color: #fff !important; }
   .nav-user-custom{background-color: #ec5252 !important; color: #fff !important; border: 1px solid #f171a0 !important;}
   .topnav-navbar-danger .topbar-right-menu .nav-link{color: #fff !important;}
</style>
<!-- Topbar Start -->
<div class="<?php echo $nav; ?>">
    <div class="container-fluid">
        <!-- LOGO -->
        <a href="<?php echo site_url($this->session->userdata('role')); ?>" class="topnav-logo"
            style="min-width: unset;">
            <span class="topnav-logo-lg">
                <img src="<?php echo base_url('uploads/system/'.get_frontend_settings('light_logo'));?>" alt=""
                    height="40">
            </span>
            <span class="topnav-logo-sm">
                <img src="<?php echo base_url('uploads/system/'.get_frontend_settings('light_logo'));?>" alt=""
                    height="40">
            </span>
        </a>

        <ul class="list-unstyled topbar-right-menu float-right mb-0">
            <li class="dropdown notification-list topbar-dropdown">
                <a class="nav-link dropdown-toggle arrow-none" data-toggle="dropdown" href="#" role="button"
                    aria-haspopup="false" aria-expanded="false">
                    <span class="align-middle"><?php echo ucwords($this->session->userdata('language')); ?></span> <i
                        class="mdi mdi-chevron-down"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right dropdown-menu-animated topbar-dropdown-menu"
                    x-placement="bottom-end"
                    style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(-59px, 72px, 0px);">

                    <?php $languages = $this->crud_model->get_all_languages();
                    foreach ($languages as $language): ?>
                    <?php if (trim($language) != "" && $this->session->userdata('language') != strtolower($language)): ?>
                    <a href="javascript:void(0);" onclick="switch_language('<?php echo strtolower($language); ?>')"
                        class="dropdown-item notify-item">
                        <span class="align-middle"><?php echo ucwords($language);?></span>
                    </a>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <!-- item-->

                </div>
            </li>
            <?php if($this->session->userdata('is_instructor') == 1 || $this->session->userdata('admin_login') == 1 || $this->session->userdata('super_admin_login') == 1 || $this->session->userdata('manager_login')== 1): ?>
            <li class="dropdown notification-list">
                <a class="nav-link dropdown-toggle arrow-none" data-toggle="dropdown" href="#" role="button"
                    aria-haspopup="false" aria-expanded="false">
                    <i class="dripicons-view-apps noti-icon"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right dropdown-menu-animated dropdown-lg p-0 mt-5 border-top-0"
                    x-placement="bottom-end"
                    style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(-278px, 70px, 0px);">

                    <div class="rounded-top py-3 border-bottom bg-primary">
                        <h4 class="text-center text-white"><?php echo get_phrase('quick_actions') ?></h4>
                    </div>

                    <div class="row row-paddingless" style="padding-left: 15px; padding-right: 15px;">
                        <!--begin:Item-->
                        <?php if ($this->session->userdata('is_instructor') == 1 && $this->session->userdata('super_admin_login')  ) : ?>
                        <div class="col-6 p-0 border-bottom border-right">
                            <a href="#" class="d-block text-center py-3 bg-hover-light"
                                onclick="showAjaxModal('<?= site_url($logged_in_user_role.'/course_form/add_course_shortcut'); ?>', '<?= get_phrase('create_course'); ?>')">
                                <i class="dripicons-archive text-20"></i>
                                <span class="w-100 d-block text-muted"><?= get_phrase('add_course'); ?></span>
                            </a>
                        </div>

                        <div class="col-6 p-0 border-bottom">
                            <a href="#" class="d-block text-center py-3 bg-hover-light"
                                onclick="showAjaxModal('<?php echo site_url('modal/popup/lesson_types/add_shortcut_lesson'); ?>', '<?php echo get_phrase('add_new_lesson'); ?>')">
                                <i class="dripicons-media-next text-20"></i>
                                <span class="d-block text-muted"><?= get_phrase('add_lesson'); ?></span>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if($this->session->userdata('admin_login') && has_permission('student') || $this->session->userdata('super_admin_login') || $this->session->userdata('manager_login')): ?>
                        <?php if(!$this->session->userdata('manager_login')) : ?>
                        <div class="col-6 p-0 border-right">
                            <a href="#" class="d-block text-center py-3 bg-hover-light"
                                onclick="showAjaxModal('<?php echo site_url('modal/popup/shortcut_add_student'); ?>', '<?php echo get_phrase('add_student'); ?>')">
                                <i class="dripicons-user text-20"></i>
                                <span class="w-100 d-block text-muted"><?= get_phrase('add_student'); ?></span>
                            </a>
                        </div>
                        <?php endif ?>
                        <?php endif; ?>

                        <?php if($this->session->userdata('admin_login') && has_permission('enrolment') || $this->session->userdata('super_admin_login') || $this->session->userdata('manager_login')): ?>
                        <div class="col-6 p-0">
                            <a href="#" class="d-block text-center py-3 bg-hover-light"
                                onclick="showAjaxModal('<?php echo site_url('modal/popup/shortcut_enrol_student'); ?>', '<?php echo get_phrase('enrol_a_student'); ?>')">
                                <i class="dripicons-network-3 text-20"></i>
                                <span class="d-block text-muted"><?= get_phrase('enrol_student'); ?></span>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
            <?php endif; ?>
            <li class="dropdown notification-list">
                <a class="nav-link dropdown-toggle nav-user arrow-none mr-0 <?php echo $nav_user; ?>" data-toggle="dropdown" id="topbar-userdrop"
                    href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <span class="account-user-avatar">
                        <img src="<?php echo $this->user_model->get_user_image_url($this->session->userdata('user_id')); ?>"
                            alt="user-image" class="rounded-circle">
                    </span>
                    <span style="color: #fff;">
                        <?php
                    $logged_in_user_details = $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();;
                    ?>
                        <span
                            class="account-user-name"><?php echo $logged_in_user_details['first_name'].' '.$logged_in_user_details['last_name'];?></span>
                        <span class="account-position">
                            <?php //echo $this->session->userdata('role');
                            //echo strtolower($this->session->userdata('role')) == 'user' ? get_phrase('instructor') : get_phrase('admin'); 
                            if(strtolower($this->session->userdata('role') =='Super_Admin')){
                                echo "Super Admin";
                             }
                             else if(strtolower($this->session->userdata('role') == 'user')){
                                echo get_phrase('instructor');
                             }
                             else if(strtolower($this->session->userdata('role') == 'Manager')){
                                echo get_phrase('Manager');
                             }
                             else if(strtolower($this->session->userdata('role') =='Admin')){
                                echo get_phrase('admin');
                             }
                              
                            ?></span>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right dropdown-menu-animated topbar-dropdown-menu profile-dropdown"
                    aria-labelledby="topbar-userdrop">
                    <!-- item-->
                    <div class=" dropdown-header noti-title">
                        <h6 class="text-overflow m-0"><?php echo get_phrase('welcome'); ?> !</h6>
                    </div>

                    <!-- Account -->
                    <a href="<?php echo site_url($this->session->userdata('role').'/manage_profile'); ?>"
                        class="dropdown-item notify-item">
                        <i class="mdi mdi-account-circle mr-1"></i>
                        <span><?php echo get_phrase('my_account'); ?></span>
                    </a>

                    <?php if (strtolower($this->session->userdata('role')) == 'Super_Admin'): ?>
                    <!-- settings-->
                    <a href="<?php echo site_url($this->session->userdata('role').'/system_settings'); ?>"
                        class="dropdown-item notify-item">
                        <i class="mdi mdi-settings mr-1"></i>
                        <span><?php echo get_phrase('settings'); ?></span>
                    </a>

                    <?php endif; ?>

                    <!-- Logout-->
                    <a href="<?php echo site_url('login/logout'); ?>" class="dropdown-item notify-item">
                        <i class="mdi mdi-logout mr-1"></i>
                        <span><?php echo get_phrase('logout'); ?></span>
                    </a>

                </div>
            </li>
        </ul>
        <a class="button-menu-mobile disable-btn">
            <div class="lines">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </a>
        <div class="visit_website">
            <h4 style="color: #fff; float: left;" class="d-none d-md-inline-block">
                <?php echo $this->db->get_where('settings' , array('key'=>'system_name'))->row()->value; ?></h4>
            <a href="<?php echo site_url('home'); ?>" target=""
                class="btn btn-outline-light ml-3 d-none d-md-inline-block"><?php echo get_phrase('visit_website'); ?></a>
        </div>
    </div>
</div>
<!-- end Topbar -->