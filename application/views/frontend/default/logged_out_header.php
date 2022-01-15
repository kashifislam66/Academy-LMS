<section class="menu-area bg-white">
    <div class="container-xl">
        <nav class="navbar navbar-expand-lg bg-white">

            <ul class="mobile-header-buttons">
                <li><a class="mobile-nav-trigger" href="#mobile-primary-nav">Menu<span></span></a></li>
                <li><a class="mobile-search-trigger" href="#mobile-search">Search<span></span></a></li>
            </ul>

            <a href="<?php echo site_url(''); ?>" class="navbar-brand" href="#"><img
                    src="<?php echo base_url('uploads/system/'.get_frontend_settings('dark_logo')); ?>" alt=""
                    height="35"></a>

            <?php include 'menu.php'; ?>

            <form class="inline-form" action="<?php echo site_url('home/search'); ?>" method="get">
                <div class="input-group search-box mobile-search">
                    <input type="text" name='query' class="form-control"
                        placeholder="<?php echo site_phrase('search_for_courses'); ?>">
                    <div class="input-group-append">
                        <button class="btn" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>

            <div class="cart-box menu-icon-box ms-auto" id="cart_items">
                <?php include 'cart_items.php'; ?>
            </div>
            <?php $btnSyle =""; ?>
            <span class="signin-box-move-desktop-helper"></span>
            <div class="sign-in-box btn-group">
            <?php if ($this->session->userdata('admin_login')): ?>
            <div class="instructor-box menu-icon-box ms-auto">
                <div class="icon">
                    <a href="<?php echo site_url('admin'); ?>"
                        style="border: 1px solid transparent; margin: 0px; font-size: 14px; width: max-content; border-radius: 5px; max-height: 40px; line-height: 40px; padding: 0px 10px;"><?php echo 'Manage Account'; ?></a>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($this->session->userdata('super_admin_login')): ?>
            <div class="instructor-box menu-icon-box ms-auto">
                <div class="icon">
                    <a href="<?php echo site_url('Super_Admin'); ?>"
                        style="border: 1px solid transparent; margin: 0px; font-size: 14px; width: max-content; border-radius: 5px; max-height: 40px; line-height: 40px; padding: 0px 10px;"><?php echo 'Super Admin'; ?></a>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($this->session->userdata('manager_login')): 
                $btnSyle ="height: 38px; margin-top: 13px;"; ?>
            <div class="instructor-box menu-icon-box ms-auto" style="<?php echo $btnSyle; ?>">
                <div class="icon">
                    <a href="<?php echo site_url('manager'); ?>"
                        style="border: 1px solid transparent; margin: 0px; font-size: 14px; width: max-content; border-radius: 5px; max-height: 40px; line-height: 40px; padding: 0px 10px;"><?php echo 'Manage Account'; ?></a>
                </div>
            </div>
            <div class="instructor-box menu-icon-box">
            <div class="wishlist-box menu-icon-box" id="wishlist_items_manager">
                <?php include 'manager_wishlist_items.php'; ?>
            </div>
            </div>
            <?php endif; ?>
            <?php if (!$this->session->userdata('super_admin_login') && !$this->session->userdata('admin_login') && !$this->session->userdata('manager_login')): ?>
                <a href="<?php echo site_url('home/login'); ?>"
                    class="btn btn-sign-in"><?php echo site_phrase('log_in'); ?></a>
            <?php endif; ?>
                <a href="<?php echo site_url('home/sign_up'); ?>"
                    class="btn btn-sign-up" style="<?php echo $btnSyle; ?>"><?php echo site_phrase('Contact_us'); ?></a>

            </div> <!--  sign-in-box end -->
        </nav>
    </div>
</section>