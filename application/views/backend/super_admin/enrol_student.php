<!-- start page title -->
<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i>
                    <?php echo get_phrase('enrol_a_student'); ?></h4>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<div class="row justify-content-center">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-body">
                <div class="col-lg-12">
                    <h4 class="mb-3 header-title"><?php echo get_phrase('enrolment_form'); ?></h4>

                    <form class="required-form" action="<?php echo site_url('super_admin/enrol_student/enrol'); ?>"
                        method="post" enctype="multipart/form-data">

                        <div class="form-group">
                            <label for="user_id"><?php echo get_phrase('user'); ?><span class="required">*</span>
                            </label>
                            <select class="form-control select2" data-toggle="select2" name="user_id" id="user_id"
                                required>
                                <option value=""><?php echo get_phrase('select_a_user'); ?></option>
                                <?php $user_list = $this->user_model->get_user()->result_array();
                                foreach ($user_list as $user):?>
                                <option value="<?php echo $user['id'] ?>">
                                    <?php echo $user['first_name'].' '.$user['last_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="course_id"><?php echo get_phrase('course_to_enrol'); ?><span
                                    class="required">*</span> </label>
                            <select class="form-control select2 fetch_courses" data-toggle="select2" name="course_id"
                                id="course_id" required>

                            </select>
                        </div>
                        <!-- last date of course -->
                        <div class="form-group">
                            <label for="enrol_last_date"><?php echo get_phrase('course_end_date'); ?><span
                                    class="required">*</span> </label>
                            <input type="date" name="enrol_last_date" class=" form-control" required>
                        </div>

                        <button type="button" class="btn btn-primary"
                            onclick="checkRequiredFields()"><?php echo get_phrase('enrol_student'); ?></button>
                    </form>
                </div>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>


<script type="text/javascript">
if ($('select').hasClass('select2') == true) {
    $('div').attr('tabindex', "");
    $(function() {
        $(".select2").select2()
    });
}

$(document).ready(function() {
    var URL = "<?php echo base_url();?>" + "moderate/fetch_courses";
    $(".fetch_courses").select2({
        minimumInputLength: 2,
        tags: false,
        ajax: {
            url: URL,
            dataType: 'json',
            type: "GET",
            quietMillis: 50,
            data: function(params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function(data) {
                return {
                    results: $.map(data, function(obj) {
                        return {
                            id: obj.id,
                            text: obj.title
                        };
                    })
                };
            },
            cache: true
        }
    });
});
</script>