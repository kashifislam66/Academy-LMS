<?php
$status_wise_courses = $this->crud_model->get_status_wise_courses();
?>
<!-- ========== Left Sidebar Start ========== -->
<div class="left-side-menu left-side-menu-detached">
    <div class="leftbar-user">
        <a href="javascript: void(0);">
            <img src="<?php echo $this->user_model->get_user_image_url($this->session->userdata('user_id')); ?>"
                alt="user-image" height="42" class="rounded-circle shadow-sm">
            <?php
			$admin_details = $this->user_model->get_all_user($this->session->userdata('user_id'))->row_array();
			?>
            <span
                class="leftbar-user-name"><?php echo $admin_details['first_name'] . ' ' . $admin_details['last_name']; ?></span>
        </a>
    </div>

    <!--- Sidemenu -->
    <ul class="metismenu side-nav side-nav-light">

        <li class="side-nav-title side-nav-item"><?php echo get_phrase('navigation'); ?></li>

        <li class="side-nav-item <?php if ($page_name == 'dashboard') echo 'active'; ?>">
            <a href="<?php echo site_url('manager/dashboard'); ?>" class="side-nav-link">
                <i class="dripicons-view-apps"></i>
                <span><?php echo get_phrase('dashboard'); ?></span>
            </a>
        </li>

        <?php if (has_permission('course')) : ?>
        <li
            class="side-nav-item <?php if ($page_name == 'courses' || $page_name == 'course_add' || $page_name == 'course_edit' || $page_name == 'categories' || $page_name == 'category_add' || $page_name == 'category_edit' || $page_name == 'coupons' || $page_name == 'coupon_add' || $page_name == 'coupon_edit' || $page_name == 'add_bundle' || $page_name == 'manage_course_bundle' || $page_name == 'edit_bundle' || $page_name == 'active_bundle_subscription_report' || $page_name == 'expire_bundle_subscription_report' || $page_name == 'bundle_invoice') echo 'active'; ?>">
            <a href="javascript: void(0);"
                class="side-nav-link <?php if ($page_name == 'courses' || $page_name == 'course_add' || $page_name == 'course_edit' || $page_name == 'categories' || $page_name == 'category_add' || $page_name == 'category_edit' || $page_name == 'coupons' || $page_name == 'coupon_add' || $page_name == 'coupon_edit') : ?> active <?php endif; ?>">
                <i class="dripicons-archive"></i>
                <span> <?php echo get_phrase('courses'); ?> </span>
                <span class="menu-arrow"></span>
            </a>
            <ul class="side-nav-second-level" aria-expanded="false">
                <?php if (has_permission('course')) : ?>
                <li class="<?php if ($page_name == 'courses' || $page_name == 'course_edit') echo 'active'; ?>">
                    <a href="<?php echo site_url('manager/courses'); ?>"><?php echo get_phrase('manage_courses'); ?></a>
                </li>
                <?php endif; ?>

                <?php if (has_permission('course')) : ?>
                <li class="<?php if ($page_name == 'course_add') echo 'active'; ?>">
                    <a
                        href="<?php echo site_url('manager/course_form/add_course'); ?>"><?php echo get_phrase('add_new_course'); ?></a>
                </li>
                <?php endif; ?>

                <?php if (has_permission('category')) : ?>
                <li
                    class="<?php if ($page_name == 'categories' || $page_name == 'category_add' || $page_name == 'category_edit') echo 'active'; ?>">
                    <a
                        href="<?php echo site_url('manager/categories'); ?>"><?php echo get_phrase('course_category'); ?></a>
                </li>
                <?php endif; ?>



            </ul>
        </li>
        <?php endif; ?>





        <?php if (has_permission('user')) : ?>
        <li
            class="side-nav-item <?php if ($page_name == 'admins' || $page_name == 'admin_add' || $page_name == 'admin_edit' || $page_name == 'admin_permission' || $page_name == 'instructors' || $page_name == 'instructor_add' || $page_name == 'instructor_edit' || $page_name == 'instructor_payout' || $page_name == 'instructor_settings' || $page_name == 'application_list' || $page_name == 'users' || $page_name == 'user_add' || $page_name == 'user_edit') : ?> active <?php endif; ?>">
            <a href="javascript: void(0);"
                class="side-nav-link <?php if ($page_name == 'admins' || $page_name == 'admin_add' || $page_name == 'admin_edit' || $page_name == 'admin_permission' || $page_name == 'instructors' || $page_name == 'instructor_add' || $page_name == 'instructor_edit' || $page_name == 'instructor_payout' || $page_name == 'instructor_settings' || $page_name == 'application_list' || $page_name == 'users' || $page_name == 'user_add' || $page_name == 'user_edit') : ?> active <?php endif; ?>">
                <i class="dripicons-box"></i>
                <span> <?php echo get_phrase('users'); ?> </span>
                <span class="menu-arrow"></span>
            </a>
            <ul class="side-nav-second-level" aria-expanded="false">




                <?php if (has_permission('student')) : ?>
                <li
                    class="side-nav-item <?php if ($page_name == 'users' || $page_name == 'user_add' || $page_name == 'user_edit') : ?> active <?php endif; ?>">
                    <a href="javascript: void(0);" aria-expanded="false"
                        class="<?php if ($page_name == 'users' || $page_name == 'user_add' || $page_name == 'user_edit') : ?> active <?php endif; ?>"><?php echo get_phrase('students'); ?>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul class="side-nav-third-level" aria-expanded="false">
                        <li class="<?php if ($page_name == 'users' || $page_name == 'user_edit') echo 'active'; ?>">
                            <a
                                href="<?php echo site_url('manager/users'); ?>"><?php echo get_phrase('manage_students'); ?></a>
                        </li>
                        <li class="<?php if ($page_name == 'user_add') echo 'active'; ?>">
                            <a
                                href="<?php echo site_url('manager/user_form/add_user_form'); ?>"><?php echo get_phrase('add_new_student'); ?></a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <li class="side-nav-item <?php if ($page_name == 'manage_profile') echo 'active'; ?>">
            <a href="<?php echo site_url(strtolower($this->session->userdata('role')) . '/manage_profile'); ?>"
                class="side-nav-link">
                <i class="dripicons-user"></i>
                <span><?php echo get_phrase('manage_profile'); ?></span>
            </a>
        </li>
        <!-- code by kasif islam -->
        <li class="side-nav-item <?php if ($page_name == 'enrol_request') echo 'active'; ?>">
            <a href="<?php echo site_url(strtolower($this->session->userdata('role')) . '/enrol_request'); ?>"
                class="side-nav-link">
                <i class="dripicons-user"></i>
                <span><?php echo get_phrase('enrol_request'); ?></span>
            </a>
        </li>

        <li class="side-nav-item <?php if ($page_name == 'course_status') echo 'active'; ?>">
            <a href="<?php echo site_url(strtolower($this->session->userdata('role')) . '/course_status'); ?>"
                class="side-nav-link">
                <i class="dripicons-user"></i>
                <span><?php echo get_phrase('course_status'); ?></span>
            </a>
        </li>
        <!-- end code -->
    </ul>
</div>