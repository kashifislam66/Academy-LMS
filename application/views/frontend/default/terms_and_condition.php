<section class="category-header-area" style="
    background-size: contain;
    background-repeat: no-repeat;
    background-position-x: right;
    background-color: #ec5252;">
    <div class="image-placeholder-1"></div>
    <div class="container-lg breadcrumb-container" style="padding:50px 0px;">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item display-6 fw-bold">
                <a href="<?php echo site_url('home'); ?>">
                    <?php echo site_phrase('home'); ?>
                </a>
            </li>
            <li class="breadcrumb-item active text-light display-6 fw-bold">
                <?php echo site_phrase('terms_and_condition'); ?>
            </li>
          </ol>
        </nav>
    </div>
</section>

<section class="category-course-list-area">
    <div class="container">
        <div class="row">
            <div class="col" style="padding: 35px;">
                <?php echo get_frontend_settings('terms_and_condition'); ?>
            </div>
        </div>
    </div>
</section>
