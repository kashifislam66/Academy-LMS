<link rel="favicon" href="<?php echo base_url() . 'assets/frontend/default/img/icons/favicon.ico' ?>">
<link rel="apple-touch-icon" href="<?php echo base_url() . 'assets/frontend/default/img/icons/icon.png'; ?>">

<?php if ($page_name == "home" || $page_name == "instructor_page") : ?>
    <link rel="stylesheet" href="<?php echo base_url() . 'assets/frontend/default/css/jquery.webui-popover.min.css'; ?>">
    <link rel="stylesheet" href="<?php echo base_url() . 'assets/frontend/default/css/slick.css'; ?>">
    <link rel="stylesheet" href="<?php echo base_url() . 'assets/frontend/default/css/slick-theme.css'; ?>">
<?php endif; ?>

<!-- font awesome 5 -->
<link rel="stylesheet" href="<?php echo base_url() . 'assets/frontend/default/css/fontawesome-all.min.css'; ?>">

<link rel="stylesheet" href="<?php echo base_url() . 'assets/frontend/default/css/bootstrap.min.css'; ?>">

<link rel="stylesheet" href="<?php echo base_url() . 'assets/frontend/default/css/main.css'; ?>">
<link rel="stylesheet" href="<?php echo base_url() . 'assets/frontend/default/css/responsive.css'; ?>">
<link rel="stylesheet" href="<?php echo base_url() . 'assets/frontend/default/css/custom.css'; ?>">
<link rel="stylesheet" href="<?php echo base_url() . 'assets/global/toastr/toastr.css' ?>">
<script src="<?php echo base_url('assets/backend/js/jquery-3.3.1.min.js'); ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.lazyload/1.9.1/jquery.lazyload.js"></script>
<script>
$(document).ready(function() {
    $("img.lazy").lazyload();
});
</script>