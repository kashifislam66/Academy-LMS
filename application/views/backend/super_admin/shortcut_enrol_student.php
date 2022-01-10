<form class="required-form ajaxForm" action="<?php echo site_url('super_admin/shortcut_enrol_student'); ?>"
    method="post" enctype="multipart/form-data">

    <div class="form-group">
        <label for="user_id"><?php echo get_phrase('user'); ?><span class="required">*</span> </label>
        <select class="form-control select2" data-toggle="select2" name="user_id[]" id="user_id" required>
            <option value=""><?php echo get_phrase('select_a_user'); ?></option>
            <?php $user_list = $this->user_model->get_user()->result_array();
                foreach ($user_list as $user):?>
            <option value="<?php echo $user['id'] ?>"><?php echo $user['first_name'].' '.$user['last_name']; ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="course_id"><?php echo get_phrase('course_to_enrol'); ?><span class="required">*</span> </label>
        <select class="form-control select2 fetch_courses" data-toggle="select2" name="course_id" id="course_id"
            required>

        </select>
    </div>
    <!-- last date of course -->
    <div class="form-group">
        <label for="enrol_last_date"><?php echo get_phrase('course_end_date'); ?><span class="required">*</span>
        </label>
        <input type="date" name="enrol_last_date" class=" form-control" required>
    </div>
    <div class="loader_ajax_call" style="display:none"></div>
    <button type="button" class="btn btn-primary float-right submit" id="disable_button"
        onclick="checkRequiredFields()"><?php echo get_phrase('enrol_student'); ?></button>
</form>

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
$('#modelLoader').hide();
$(".ajaxForm").submit(function(e) {
    e.preventDefault(); // avoid to execute the actual submit of the form.
    var form = $(this);
    var url = form.attr('action');
    $(".loader_ajax_call").css("display", "block");
    $("#disable_button").prop('disabled', true);

    $.ajax({
        type: "POST",
        url: url,
        data: form.serialize(),
        // serializes the form's elements.
        beforeSend: function() {
            $('#modelLoader').show();
            $('.submit').show();
        },
        success: function(response) {
            var myArray = jQuery.parseJSON(response);
            if (myArray['status']) {
                location.reload();
                //  $('#modelLoader').hide();
            } else {
                error_notify(myArray['message']);
            }
            $(".loader_ajax_call").css("display", "none");
            $("#disable_button").prop('disabled', false);
        }
    });
});
</script>

<style>
#modelLoader {
    background: rgba(255, 255, 255, 0.8);
    display: none;
    height: 100%;
    position: fixed;
    width: 100%;
    z-index: 9999;
}

#modelLoader img {
    left: 50%;
    margin-left: -32px;
    margin-top: -32px;
    position: absolute;
    top: 50%;
}
</style>