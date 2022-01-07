<form class="required-form ajaxForm" action="<?php echo site_url('super_admin/add_shortcut_student'); ?>" method="post"
    enctype="multipart/form-data">
    <div class="form-group">
        <label for="first_name"><?php echo get_phrase('first_name'); ?><span class="required">*</span> </label>
        <input type="text" id="first_name" name="first_name" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="last_name"><?php echo get_phrase('last_name'); ?></label>
        <input type="text" id="last_name" name="last_name" class="form-control">
    </div>

    <div class="form-group">
        <label for="email"><?php echo get_phrase('email'); ?><span class="required">*</span> </label>
        <input type="email" id="email" name="email" class="form-control" required>
    </div>
    <?php $select_user_company = $this->user_model->select_company_name(); ?>
    <div class="form-group">
        <label class="col-form-label" for="admin_list"><?php echo 'Select comany'; ?><span
                class="required">*</span></label>
        
            <select name="company_id" id="company_id" class="form-control" required>
                <option value="">Select Company</option>
                <?php foreach ($select_user_company as $row) {?>
                <option value="<?php echo $row->id; ?>">
                    <?php echo $row->first_name.' '.$row->last_name; ?></option>
                <?php } ?>
            </select>
        
    </div>

    <div class="form-group">
        <label for="password"><?php echo get_phrase('password'); ?><span class="required">*</span> </label>
        <input type="password" id="password" name="password" class="form-control" required>
    </div>
    <button type="submit" 
        class="btn btn-primary float-right"><?php echo get_phrase('submit'); ?></button>
</form>

<script type="text/javascript">
$(".ajaxForm").submit(function(e) {
    e.preventDefault(); // avoid to execute the actual submit of the form.
    var form = $(this);
    var url = form.attr('action');
    $.ajax({
        type: "POST",
        url: url,
        data: form.serialize(), // serializes the form's elements.
        success: function(response) {
            var myArray = jQuery.parseJSON(response);
            if (myArray['status']) {
                location.reload();
            } else {
                error_notify(myArray['message']);
            }
        }
    });
});
</script>