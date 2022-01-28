<section class="page-header-area my-course-area">
    <div class="container">
        <div class="row">
            <div class="col">
                <h1 class="page-title print-hidden"><?php echo $page_title; ?></h1>
                <ul class="print-hidden">
                <li class="<?php if($page_name == 'manager_courses') echo 'active'; ?>"><a href="<?php echo site_url('home/manager_courses'); ?>"><?php echo 'Assigned courses'; ?></a></li>
                  <li class="<?php if($page_name == 'my_manager_wishlist') echo 'active'; ?>"><a href="<?php echo site_url('home/my_manager_wishlist'); ?>"><?php echo site_phrase('wishlists'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</section>