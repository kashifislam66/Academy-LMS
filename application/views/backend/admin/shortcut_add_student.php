<form class="required-form ajaxForm" action="<?php echo site_url('admin/add_shortcut_student'); ?>" method="post"
    enctype="multipart/form-data">
    <div class="form-group">
        <label for="first_name"><?php echo get_phrase('first_name'); ?><span class="required">*</span> </label>
        <input type="text" id="first_name" name="first_name" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="last_name"><?php echo get_phrase('last_name'); ?></label>
        <input type="text" id="last_name" name="last_name" class="form-control">
    </div>
    <input type="hidden" value="<?php echo $this->session->userdata('user_id') ?>" id="company_id" name="company_id"
        class="form-control">

    <div class="form-group">
        <label for="email"><?php echo get_phrase('email'); ?><span class="required">*</span> </label>
        <input type="text" id="email" name="email" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="password"><?php echo get_phrase('password'); ?><span class="required">*</span> </label>
        <input type="password" id="password" name="password" class="form-control" required>
    </div>
    <div class="loader_ajax_call" style="display:none"></div>
    <button type="submit" id="disable_button"
        class="btn btn-primary float-right"><?php echo get_phrase('submit'); ?></button>
</form>

<script type="text/javascript">
$(".ajaxForm").submit(function(e) {
    e.preventDefault(); // avoid to execute the actual submit of the form.
    var form = $(this);
    var url = form.attr('action');
    $(".loader_ajax_call").css("display", "block");
    $("#disable_button").prop('disabled', true);
    $.ajax({
        type: "POST",
        url: url,
        data: form.serialize(), // serializes the form's elements.
        success: function(response) {
            console.log(response);
            var myArray = jQuery.parseJSON(response);

            if (myArray['status']) {
                location.reload();
            } else {
                error_notify(myArray['message']);
            }
            $(".loader_ajax_call").css("display", "none");
            $("#disable_button").prop('disabled', false);
        }
    });
});
</script>