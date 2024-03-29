<section class="home-banner-area" id="home-banner-area"
    style="background-image: url('<?= base_url("uploads/system/" . get_frontend_settings('banner_image')); ?>'); background-position: right; background-repeat: no-repeat; padding: 100px 0 75px; background-size: auto 100%; color: #fff;">
    <div class="cropped-home-banner"></div>
    <div class="container-xl">
        <div class="row">
            <div class="col position-relative">
                <div class="home-banner-wrap">
                    <h2 class="fw-bold"><?php echo site_phrase(get_frontend_settings('banner_title')); ?></h2>
                    <p><?php echo site_phrase(get_frontend_settings('banner_sub_title')); ?></p>
                    <form class="" action="<?php echo site_url('home/search'); ?>" method="get">
                        <div class="input-group">
                            <input type="text" class="form-control" name="query"
                                placeholder="<?php echo site_phrase('what_do_you_want_to_learn'); ?>?">
                            <div class="input-group-append p-6px bg-white">
                                <button class="btn" type="submit"><?php echo site_phrase('search'); ?> <i
                                        class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
        $banner_size = getimagesize("uploads/system/" . get_frontend_settings('banner_image'));
        $banner_ratio = $banner_size[0]/$banner_size[1];
    ?>
    <script type="text/javascript">
    var border_bottom = $('.home-banner-wrap').height() + 242;
    $('.cropped-home-banner').css('border-bottom', border_bottom + 'px solid #f1f7f8');

    mRight = Number("<?php echo $banner_ratio; ?>") * $('.home-banner-area').outerHeight();
    $('.cropped-home-banner').css('right', (mRight - 65) + 'px');
    </script>
</section>




<section class="home-fact-area">
    <div class="container-lg">
        <div class="row">
            <?php // $courses = $this->crud_model->get_courses(); ?>
            <div class="col-md-4 d-flex">
                <div class="home-fact-box mr-md-auto mr-auto">
                    <i class="fas fa-bullseye float-start"></i>
                    <div class="text-box">
                        <h4><?php
                            $status_wise_courses = $this->crud_model->get_status_wise_courses();
                            $number_of_courses = $status_wise_courses['active']->num_rows();
                            echo $number_of_courses . ' ' . site_phrase('online_courses'); ?></h4>
                        <p><?php echo site_phrase('explore_a_variety_of_fresh_topics'); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 d-flex">
                <div class="home-fact-box mr-md-auto mr-auto">
                    <i class="fa fa-check float-start"></i>
                    <div class="text-box">
                        <h4><?php echo site_phrase('expert_instruction'); ?></h4>
                        <p><?php echo site_phrase('find_the_right_course_for_you'); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 d-flex">
                <div class="home-fact-box mr-md-auto mr-auto">
                    <i class="fa fa-clock float-start"></i>
                    <div class="text-box">
                        <h4><?php echo site_phrase('Unlimited_access'); ?></h4>
                        <p><?php echo site_phrase('learn_on_your_schedule'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-5">
    <div class="container-lg">
        <h3 class="course-carousel-title my-4"><?php echo site_phrase('top_categories'); ?></h3>
        <div class="row justify-content-center">

            <?php $top_10_categories = $this->crud_model->get_top_categories(4, 'category_id'); ?>
            <?php foreach($top_10_categories as $top_10_category): ?>
            <?php $category_details = $this->crud_model->get_category_details_by_id($top_10_category['category_id'])->row_array(); ?>
            <div class="col-md-6 col-lg-4 col-xl-3 mb-3">
                <a href="<?php echo site_url('home/courses?category='.$category_details['slug']); ?>"
                    class="top-categories">
                    <div class="category-icon">
                        <i class="<?php echo $category_details['font_awesome_class']; ?>"></i>
                    </div>
                    <div class="category-title">
                        <?php echo $category_details['name']; ?>
                        <p><?php echo $top_10_category['course_number'].' '.site_phrase('courses'); ?></p>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="course-carousel-area">
    <div class="container-lg">
        <div class="row">
            <div class="col">
                <h3 class="course-carousel-title mb-4"><?php echo site_phrase('Trending_courses'); ?></h3>

                <!-- page loader -->
                <div class="animated-loader">
                    <div class="spinner-border text-secondary" role="status"></div>
                </div>

                <div class="course-carousel shown-after-loading" style="display: none;">
                    <?php $top_courses = $this->crud_model->get_future_courses();
                    $cart_items = $this->session->userdata('cart_items');
                    foreach ($top_courses as $top_course) : ?>
                    <?php
                            $lessons = $this->crud_model->get_lessons('course', $top_course['id']);
                            $course_duration = $this->crud_model->get_total_duration_of_lesson_by_course_id($top_course['id']);
                        ?>
                    <div class="course-box-wrap">
                        <a onclick="return check_action(this);"
                            href="<?php echo site_url('home/course/' . rawurlencode(slugify($top_course['title'])) . '/' . $top_course['id']); ?>"
                            class="has-popover">
                            <div class="course-box">
                                <div class="course-image">
                                 <img class="img-fluid lazy" src="<?php echo $top_course['thumbnail']; ?>">
                                </div>
                                <div class="course-details">
                                    <h5 class="title"><?php echo $top_course['title']; ?></h5>
                                    <div class="rating">
                                        <?php
                                            $total_rating =  $this->crud_model->get_ratings('course', $top_course['id'], true)->row()->rating;
                                            $number_of_ratings = $this->crud_model->get_ratings('course', $top_course['id'])->num_rows();
                                            if ($number_of_ratings > 0) {
                                                $average_ceil_rating = ceil($total_rating / $number_of_ratings);
                                            } else {
                                                $average_ceil_rating = 0;
                                            }

                                            for ($i = 1; $i < 6; $i++) : ?>
                                        <?php if ($i <= $average_ceil_rating) : ?>
                                        <i class="fas fa-star filled"></i>
                                        <?php else : ?>
                                        <i class="fas fa-star"></i>
                                        <?php endif; ?>
                                        <?php endfor; ?>
                                        <div class="d-inline-block">
                                            <span
                                                class="text-dark ms-1 text-15px">(<?php echo $average_ceil_rating; ?>)</span>
                                            <span
                                                class="text-dark text-12px text-muted ms-2">(<?php echo $number_of_ratings.' '.site_phrase('reviews'); ?>)</span>
                                        </div>
                                    </div>
                                    <div class="d-flex text-dark">
                                        <div class="">
                                            <i class="far fa-clock text-14px"></i>
                                            <span class="text-muted text-12px"><?php echo $course_duration; ?></span>
                                        </div>
                                        <div class="ms-3">
                                            <i class="far fa-list-alt text-14px"></i>
                                            <span
                                                class="text-muted text-12px"><?php echo $lessons->num_rows().' '.site_phrase('lectures'); ?></span>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-6">
                                            <span
                                                class="badge badge-sub-warning text-11px"><?php echo site_phrase($top_course['level']); ?></span>
                                        </div>
                                      
                                    </div>

                                    <hr class="divider-1">

                                    <div class="d-block">
                                        <div class="floating-user d-inline-block">
                                            <?php if ($top_course['multi_instructor']):
                                                    $instructor_details = $this->user_model->get_multi_instructor_details_with_csv($top_course['user_id']);
                                                    $margin = 0;
                                                    foreach ($instructor_details as $key => $instructor_detail) { ?>
                                            <img style="margin-left: <?php echo $margin; ?>px;"
                                                class="position-absolute lazy"
                                                src="<?php echo $this->user_model->get_user_image_url($instructor_detail['id']); ?>"
                                                width="30px" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="<?php echo $instructor_detail['first_name'].' '.$instructor_detail['last_name']; ?>"
                                                onclick="return check_action(this,'<?php echo site_url('home/instructor_page/'.$instructor_detail['id']); ?>');">
                                            <?php $margin = $margin+17; ?>
                                            <?php } ?>
                                            <?php else: ?>
                                            <?php $user_details = $this->user_model->get_all_user($top_course['user_id'])->row_array(); ?>
                                            <img class="lazy" src="<?php echo $this->user_model->get_user_image_url($user_details['id']); ?>"
                                                width="30px" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="<?php echo $user_details['first_name'].' '.$user_details['last_name']; ?>"
                                                onclick="return check_action(this,'<?php echo site_url('home/instructor_page/'.$user_details['id']); ?>');">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <div class="webui-popover-content">
                            <div class="course-popover-content">
                                <?php if ($top_course['last_modified'] == "") : ?>
                                <div class="last-updated fw-500">
                                    <?php echo site_phrase('last_updated') . ' ' . date('D, d-M-Y', $top_course['date_added']); ?>
                                </div>
                                <?php else : ?>
                                <div class="last-updated">
                                    <?php echo site_phrase('last_updated') . ' ' . date('D, d-M-Y', $top_course['last_modified']); ?>
                                </div>
                                <?php endif; ?>

                                <div class="course-title">
                                    <a class="text-decoration-none text-15px"
                                        href="<?php echo site_url('home/course/' . rawurlencode(slugify($top_course['title'])) . '/' . $top_course['id']); ?>"><?php echo $top_course['title']; ?></a>
                                </div>
                                <div class="course-meta">
                                    <?php if ($top_course['course_type'] == 'general') : ?>
                                    <span class=""><i class="fas fa-play-circle"></i>
                                        <?php echo $this->crud_model->get_lessons('course', $top_course['id'])->num_rows() . ' ' . site_phrase('lessons'); ?>
                                    </span>
                                    <span class=""><i class="far fa-clock"></i>
                                        <?php echo $course_duration; ?>
                                    </span>
                                    <?php elseif ($top_course['course_type'] == 'scorm') : ?>
                                    <span class="badge bg-light"><?= site_phrase('scorm_course'); ?></span>
                                    <?php endif; ?>
                                    <span class=""><i
                                            class="fas fa-closed-captioning"></i><?php echo ucfirst($top_course['language']); ?></span>
                                </div>
                                <div class="course-subtitle"><?php echo $top_course['short_description']; ?></div>
                                <div class="what-will-learn">
                                    <ul>
                                        <?php
                                            $outcomes = json_decode($top_course['outcomes']);
                                            foreach ($outcomes as $outcome) : ?>
                                        <li><?php echo $outcome; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="popover-btns ">
                                    <?php if (is_purchased($top_course['id'])) : ?>
                                    <div class="purchased">
                                        <a
                                            href="<?php echo site_url('home/my_courses'); ?>"><?php echo site_phrase('already_enroled'); ?></a>
                                    </div>
                                    <?php else : ?>   
                                     <?php  if (!$this->session->userdata('manager_login')){
                                                if ($this->session->userdata('user_login') != 1) {
                                                    $url = "#";
                                                } else {
                                                    $url = site_url('home/get_enrolled/' . $top_course['id']);
                                                } ?>
                                    <a href="<?php echo $url; ?>" class="btn green radius-10"
                                        onclick="handleEnrolledButton()"><?php echo site_phrase('get_enrolled'); ?></a>
                                         <?php } ?>
                                    <?php endif; 
                                    $eventFunction = $stdAndManagerActiveCourse='';
                                    if ($this->session->userdata('manager_login')){
                                       $eventFunction = 'handleWishListManager(this)'; 
                                       $stdAndManagerActiveCourse = $this->crud_model->is_added_to_manager_wishlist($top_course['id']);
                                    }else{
                                        $eventFunction = 'handleWishList(this)';
                                        $stdAndManagerActiveCourse = $this->crud_model->is_added_to_wishlist($top_course['id']);
                                    } ?>
                                    <button type="button"
                                        class="wishlist-btn <?php if (!empty($stdAndManagerActiveCourse)) echo 'active'; ?>"
                                        title="Add to wishlist" onclick="<?php echo $eventFunction; ?>"
                                        id="<?php echo $top_course['id']; ?>"><i class="fas fa-heart"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="course-carousel-area">
    <div class="container-lg">
        <div class="row">
            <div class="col">
                <h3 class="course-carousel-title mb-4">
                    <?php echo site_phrase('top') .' '. site_phrase('rated_courses'); ?></h3>

                <!-- page loader -->
                <div class="animated-loader">
                    <div class="spinner-border text-secondary" role="status"></div>
                </div>

                <div class="course-carousel shown-after-loading" style="display: none;">
                    <?php
                    $latest_courses = $this->crud_model->get_latest_10_course();
                    // print_r($latest_courses); die();
                    foreach ($latest_courses as $latest_course) : ?>
                    <?php
                            $lessons = $this->crud_model->get_lessons('course', $latest_course['id']);
                            $course_duration = $this->crud_model->get_total_duration_of_lesson_by_course_id($latest_course['id']);
                        ?>
                    <div class="course-box-wrap">
                        <a onclick="return check_action(this);"
                            href="<?php echo site_url('home/course/' . rawurlencode(slugify($latest_course['title'])) . '/' . $latest_course['id']); ?>"
                            class="has-popover">
                            <div class="course-box">
                                <div class="course-image">
                                    <img src="<?php echo $latest_course['thumbnail']; ?>"
                                        alt="" class="img-fluid lazy">
                                </div>
                                <div class="course-details">
                                    <h5 class="title"><?php echo $latest_course['title']; ?></h5>
                                    <div class="rating">
                                        <?php
                                            $total_rating =  $this->crud_model->get_ratings('course', $latest_course['id'], true)->row()->rating;
                                            $number_of_ratings = $this->crud_model->get_ratings('course', $latest_course['id'])->num_rows();
                                            if ($number_of_ratings > 0) {
                                                $average_ceil_rating = ceil($total_rating / $number_of_ratings);
                                            } else {
                                                $average_ceil_rating = 0;
                                            }

                                            for ($i = 1; $i < 6; $i++) : ?>
                                        <?php if ($i <= $average_ceil_rating) : ?>
                                        <i class="fas fa-star filled"></i>
                                        <?php else : ?>
                                        <i class="fas fa-star"></i>
                                        <?php endif; ?>
                                        <?php endfor; ?>
                                        <div class="d-inline-block">
                                            <span
                                                class="text-dark ms-1 text-15px">(<?php echo $average_ceil_rating; ?>)</span>
                                            <span
                                                class="text-dark text-12px text-muted ms-2">(<?php echo $number_of_ratings.' '.site_phrase('reviews'); ?>)</span>
                                        </div>
                                    </div>
                                    <div class="d-flex text-dark">
                                        <div class="">
                                            <i class="far fa-clock text-14px"></i>
                                            <span class="text-muted text-12px"><?php echo $course_duration; ?></span>
                                        </div>
                                        <div class="ms-3">
                                            <i class="far fa-list-alt text-14px"></i>
                                            <span
                                                class="text-muted text-12px"><?php echo $lessons->num_rows().' '.site_phrase('lectures'); ?></span>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-6">
                                            <span
                                                class="badge badge-sub-warning text-11px"><?php echo site_phrase($latest_course['level']); ?></span>
                                        </div>
                                      
                                    </div>

                                    <hr class="divider-1">

                                    <div class="d-block">
                                        <div class="floating-user d-inline-block">
                                            <?php if ($latest_course['multi_instructor']):
                                                    $instructor_details = $this->user_model->get_multi_instructor_details_with_csv($latest_course['user_id']);
                                                    $margin = 0;
                                                    foreach ($instructor_details as $key => $instructor_detail) { ?>
                                            <img style="margin-left: <?php echo $margin; ?>px;"
                                                class="position-absolute lazy"
                                                src="<?php echo $this->user_model->get_user_image_url($instructor_detail['id']); ?>"
                                                width="30px" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="<?php echo $instructor_detail['first_name'].' '.$instructor_detail['last_name']; ?>"
                                                onclick="return check_action(this,'<?php echo site_url('home/instructor_page/'.$instructor_detail['id']); ?>');">
                                            <?php $margin = $margin+17; ?>
                                            <?php } ?>
                                            <?php else: ?>
                                            <?php $user_details = $this->user_model->get_all_user($latest_course['user_id'])->row_array(); ?>
                                            <img src="<?php echo $this->user_model->get_user_image_url($user_details['id']); ?>"
                                                width="30px" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="<?php echo $user_details['first_name'].' '.$user_details['last_name']; ?>"
                                                onclick="return check_action(this,'<?php echo site_url('home/instructor_page/'.$user_details['id']); ?>');">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <div class="webui-popover-content">
                            <div class="course-popover-content">
                                <?php if ($latest_course['last_modified'] == "") : ?>
                                <div class="last-updated fw-500">
                                    <?php echo site_phrase('last_updated') . ' ' . date('D, d-M-Y', $latest_course['date_added']); ?>
                                </div>
                                <?php else : ?>
                                <div class="last-updated">
                                    <?php echo site_phrase('last_updated') . ' ' . date('D, d-M-Y', $latest_course['last_modified']); ?>
                                </div>
                                <?php endif; ?>

                                <div class="course-title">
                                    <a class="text-decoration-none text-15px"
                                        href="<?php echo site_url('home/course/' . rawurlencode(slugify($latest_course['title'])) . '/' . $latest_course['id']); ?>"><?php echo $latest_course['title']; ?></a>
                                </div>
                                <div class="course-meta">
                                    <?php if ($latest_course['course_type'] == 'general') : ?>
                                    <span class=""><i class="fas fa-play-circle"></i>
                                        <?php echo $this->crud_model->get_lessons('course', $latest_course['id'])->num_rows() . ' ' . site_phrase('lessons'); ?>
                                    </span>
                                    <span class=""><i class="far fa-clock"></i>
                                        <?php echo $course_duration; ?>
                                    </span>
                                    <?php elseif ($latest_course['course_type'] == 'scorm') : ?>
                                    <span class="badge bg-light"><?= site_phrase('scorm_course'); ?></span>
                                    <?php endif; ?>
                                    <span class=""><i
                                            class="fas fa-closed-captioning"></i><?php echo ucfirst($latest_course['language']); ?></span>
                                </div>
                                <div class="course-subtitle"><?php echo $latest_course['short_description']; ?></div>
                                <div class="what-will-learn">
                                    <ul>
                                        <?php
                                            $outcomes = json_decode($latest_course['outcomes']);
                                            foreach ($outcomes as $outcome) : ?>
                                        <li><?php echo $outcome; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="popover-btns">
                                    <?php if (is_purchased($latest_course['id'])) : ?>
                                    <div class="purchased">
                                        <a
                                            href="<?php echo site_url('home/my_courses'); ?>"><?php echo site_phrase('already_enroled'); ?></a>
                                    </div>
                                    <?php else : ?>
                                     <?php if (!$this->session->userdata('manager_login')) : ?>
                                        <?php   if ($this->session->userdata('user_login') != 1) {
                                                    $url = "#";
                                                } else {
                                                    $url = site_url('home/get_enrolled/' . $latest_course['id']);
                                                } ?>
                                    <a href="<?php echo $url; ?>" class="btn green radius-10"
                                        onclick="handleEnrolledButton()"><?php echo site_phrase('get_enrolled'); ?></a>
                                     <?php endif; ?>
                                    <?php endif; 
                                    $eventFunction = $stdAndManagerActiveCourse = '';
                                    if ($this->session->userdata('manager_login')){
                                       $eventFunction = 'handleWishListManager(this)'; 
                                       $stdAndManagerActiveCourse = $this->crud_model->is_added_to_manager_wishlist($top_course['id']);
                                    }else{
                                        $eventFunction = 'handleWishList(this)';
                                        $stdAndManagerActiveCourse = $this->crud_model->is_added_to_wishlist($top_course['id']);
                                    }?>
                                    <button type="button"
                                        class="wishlist-btn <?php if (!empty($stdAndManagerActiveCourse)) echo 'active'; ?>"
                                        title="Add to wishlist" onclick="<?php echo $eventFunction; ?>"
                                        id="<?php echo $latest_course['id']; ?>"><i class="fas fa-heart"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>



<div class="container-xl">
    <div class="row py-3 mb-4">
        <div class="col-md-6 mt-3 mt-md-0">
            <div class="become-user-label text-center mt-3">
                <h3 class="pb-4"><?php echo site_phrase('join_now_to_start_learning'); ?></h3>
                <a href="<?php echo site_url('home/sign_up'); ?>"><?php echo site_phrase('get_started'); ?></a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="become-user-label text-center mt-3">
                <h3 class="pb-4"><?php echo 'Current Users - Login'; ?></h3>
                <?php if ($this->session->userdata('admin_login')): ?>
                            <a href="<?php echo site_url('admin'); ?>"
                                style="border: 1px solid transparent; margin: 0px; font-size: 14px; width: max-content; border-radius: 5px; "><?php echo 'Manage Account'; ?></a>
                    <?php elseif ($this->session->userdata('super_admin_login')): ?>
                            <a href="<?php echo site_url('Super_Admin'); ?>"
                                style="border: 1px solid transparent; margin: 0px; font-size: 14px; width: max-content; border-radius: 5px;"><?php echo 'Super Admin'; ?></a>
                    <?php elseif ($this->session->userdata('manager_login')): ?>
                            <a href="<?php echo site_url('manager'); ?>"
                            style="border: 1px solid transparent; margin: 0px; font-size: 14px; width: max-content; border-radius: 5px;"><?php echo 'Manage Account'; ?></a>
                <?php else: ?>
                <a href="<?php echo site_url('home/login'); ?>"><?php echo site_phrase('Login'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
function handleWishList(elem) {
    $.ajax({
        url: '<?php echo site_url('home/handleWishList'); ?>',
        type: 'POST',
        data: {
            course_id: elem.id
        },
        success: function(response) {
            if (!response) {
                window.location.replace("<?php echo site_url('login'); ?>");
            } else {
                if ($(elem).hasClass('active')) {
                    $(elem).removeClass('active')
                } else {
                    $(elem).addClass('active')
                }
                $('#wishlist_items').html(response);
            }
        }
    });
}

function handleWishListManager(elem){
    $.ajax({
        url: '<?php echo site_url('home/handleWishManagerList'); ?>',
        type: 'POST',
        data: {
            course_id: elem.id
        },
        success: function(response) {
            if (!response) {
                window.location.replace("<?php echo site_url('login'); ?>");
            } else {
                if ($(elem).hasClass('active')) {
                    $(elem).removeClass('active')
                } else {
                    $(elem).addClass('active')
                }
                $('#wishlist_items_manager').html(response);
            }
        }
    });
}


function handleCartItems(elem) {
    url1 = '<?php echo site_url('home/handleCartItems'); ?>';
    url2 = '<?php echo site_url('home/refreshWishList'); ?>';
    $.ajax({
        url: url1,
        type: 'POST',
        data: {
            course_id: elem.id
        },
        success: function(response) {
            $('#cart_items').html(response);
            if ($(elem).hasClass('addedToCart')) {
                $('.big-cart-button-' + elem.id).removeClass('addedToCart')
                $('.big-cart-button-' + elem.id).text("<?php echo site_phrase('add_to_cart'); ?>");
            } else {
                $('.big-cart-button-' + elem.id).addClass('addedToCart')
                $('.big-cart-button-' + elem.id).text("<?php echo site_phrase('added_to_cart'); ?>");
            }
            $.ajax({
                url: url2,
                type: 'POST',
                success: function(response) {
                    $('#wishlist_items').html(response);
                }
            });
        }
    });
}

function handleEnrolledButton() {
    $.ajax({
        url: '<?php echo site_url('home/isLoggedIn'); ?>',
        success: function(response) {
            if (!response) {
                window.location.replace("<?php echo site_url('login'); ?>");
            }
        }
    });
}

$(document).ready(function() {
    if (!/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        if ($(window).width() >= 840) {
            $('a.has-popover').webuiPopover({
                trigger: 'hover',
                animation: 'pop',
                placement: 'horizontal',
                delay: {
                    show: 500,
                    hide: null
                },
                width: 330
            });
        } else {
            $('a.has-popover').webuiPopover({
                trigger: 'hover',
                animation: 'pop',
                placement: 'vertical',
                delay: {
                    show: 100,
                    hide: null
                },
                width: 335
            });
        }
    }

    if ($(".course-carousel")[0]) {
        $(".course-carousel").slick({
            dots: false,
            infinite: false,
            speed: 300,
            slidesToShow: 4,
            slidesToScroll: 4,
            swipe: false,
            touchMove: false,
            responsive: [{
                    breakpoint: 840,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 3,
                    },
                },
                {
                    breakpoint: 620,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 2,
                    },
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                    },
                },
            ],
        });
    }

    if ($(".top-istructor-slick")[0]) {
        $(".top-istructor-slick").slick({
            dots: false
        });
    }
    
});
</script>
