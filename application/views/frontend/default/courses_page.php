<?php
isset($layout) ? "" : $layout = "list";
isset($selected_category_id) ? "" : $selected_category_id = "all";
isset($selected_level) ? "" : $selected_level = "all";
isset($selected_language) ? "" : $selected_language = "all";
isset($selected_rating) ? "" : $selected_rating = "all";

// echo $selected_category_id.'-'.$selected_level.'-'.$selected_language.'-'.$selected_rating.'-'.$selected_price;
$number_of_visible_categories = 10;
if (isset($sub_category_id)) {
    $sub_category_details = $this->crud_model->get_category_details_by_id($sub_category_id)->row_array();
    $category_details     = $this->crud_model->get_categories($sub_category_details['parent'])->row_array();
    $category_name        = $category_details['name'];
    $sub_category_name    = $sub_category_details['name'];
}
?>

<section class="category-header-area" style="background-image: url('<?php echo base_url('uploads/system/course_page_banner.png'); ?>');">
    <div class="image-placeholder-1"></div>
    <div class="container-lg breadcrumb-container">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item display-6 fw-bold">
                <a href="<?php echo site_url('home/courses'); ?>">
                    <?php echo site_phrase('all_courses'); ?>
                </a>
            </li>
            <li class="breadcrumb-item active text-light display-6 fw-bold">
                <?php
                if ($selected_category_id == "all") {
                    echo site_phrase('all_category');
                } else {
                    $category_details = $this->crud_model->get_category_details_by_id($selected_category_id)->row_array();
                    echo $category_details['name'];
                }
                ?>
            </li>
          </ol>
        </nav>
    </div>
</section>


<section class="category-course-list-area">
    <div class="container">
        <div class="row">
          
            <div class="col-lg-9">
                <div class="row category-filter-box mx-0" >
                    <div class="col-md-6">
                        <button class="btn py-1 px-2 mx-2 <?php if($this->session->userdata('layout') == 'grid'){echo 'btn-danger';}else{echo 'btn-light'; } ?>" onclick="toggleLayout('grid')"><i class="fas fa-th-large"></i></button>
                        <button class="btn py-1 px-2 mx-2 <?php if($this->session->userdata('layout') == 'list'){echo 'btn-danger';}else{echo 'btn-light'; } ?>" onclick="toggleLayout('list')"><i class="fas fa-list"></i></button>
                        <span class="text-12px fw-700 text-muted"><?php echo site_phrase('showing').' '.count($courses).' '.site_phrase('of').' '.$total_result.' '.site_phrase('results'); ?></span>
                    </div>
                    <div class="col-md-6 text-end filter-sort-by">
                        <!-- <span><?php echo site_phrase('sort_by'); ?> : </span>
                        <div class="dropdown d-inline-block">
                            <button class="btn bg-background dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                Newest
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                                <li><a class="dropdown-item text-12px fw-500" href="#">Action</a></li>
                                <li><a class="dropdown-item text-12px fw-500" href="#">Another action</a></li>
                            </ul>
                        </div> -->
                    </div>
                </div>
                <div class="category-course-list">
                    <?php include 'category_wise_course_' . $layout . '_layout.php'; ?>
                    <?php if (count($courses) == 0) : ?>
                        <?php echo site_phrase('no_result_found'); ?>
                    <?php endif; ?>
                </div>
                <nav>
                    <?php //if ($selected_category_id == "all" && $selected_price == 0 && $selected_level == 'all' && $selected_language == 'all' && $selected_rating == 'all') {
                        echo $this->pagination->create_links();
                    // }?>
                </nav>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
    function get_url() {
        var urlPrefix = '<?php echo site_url('home/courses?'); ?>'
        var urlSuffix = "";
        var slectedCategory = "";
        var selectedPrice = "";
        var selectedLevel = "";
        var selectedLanguage = "";
        var selectedRating = "";

        // Get selected category
        $('.categories:checked').each(function() {
            slectedCategory = $(this).attr('value');
        });

        // Get selected price
        $('.prices:checked').each(function() {
            selectedPrice = $(this).attr('value');
        });

        // Get selected difficulty Level
        $('.level:checked').each(function() {
            selectedLevel = $(this).attr('value');
        });

        // Get selected difficulty Level
        $('.languages:checked').each(function() {
            selectedLanguage = $(this).attr('value');
        });

        // Get selected rating
        $('.ratings:checked').each(function() {
            selectedRating = $(this).attr('value');
        });

        urlSuffix = "category=" + slectedCategory + "&&price=" + selectedPrice + "&&level=" + selectedLevel + "&&language=" + selectedLanguage + "&&rating=" + selectedRating;
        var url = urlPrefix + urlSuffix;
        return url;
    }

    function filter() {
        var url = get_url();
        window.location.replace(url);
        //console.log(url);
    }

    function toggleLayout(layout) {
        $.ajax({
            type: 'POST',
            url: '<?php echo site_url('home/set_layout_to_session'); ?>',
            data: {
                layout: layout
            },
            success: function(response) {
                location.reload();
            }
        });
    }

    function showToggle(elem, selector) {
        $('.' + selector).slideToggle(20);
        if ($(elem).text() === "<?php echo site_phrase('show_more'); ?>") {
            $(elem).text('<?php echo site_phrase('show_less'); ?>');
        } else {
            $(elem).text('<?php echo site_phrase('show_more'); ?>');
        }
    }

    $('.course-compare').click(function(e) {
        e.preventDefault()
        var redirect_to = $(this).attr('redirect_to');
        window.location.replace(redirect_to);
    });
</script>