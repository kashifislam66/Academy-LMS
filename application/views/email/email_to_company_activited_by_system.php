<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?= get_settings('system_name'); ?></title>
    <style type="text/css" rel="stylesheet" media="all">
    /* Base ------------------------------ */

    @import url("https://fonts.googleapis.com/css?family=Nunito+Sans:400,700&display=swap");

    body {
        width: 100% !important;
        height: 100%;
        margin: 0;
        -webkit-text-size-adjust: none;
    }

    a {
        color: #3869D4;
    }

    a img {
        border: none;
    }

    td {
        word-break: break-word;
    }

    .preheader {
        display: none !important;
        visibility: hidden;
        mso-hide: all;
        font-size: 1px;
        line-height: 1px;
        max-height: 0;
        max-width: 0;
        opacity: 0;
        overflow: hidden;
    }

    /* Type ------------------------------ */

    body,
    td,
    th {
        font-family: "Nunito Sans", Helvetica, Arial, sans-serif;
    }

    h1 {
        margin-top: 0;
        color: #333333;
        font-size: 22px;
        font-weight: bold;
        text-align: left;
    }

    h2 {
        margin-top: 0;
        color: #333333;
        font-size: 16px;
        font-weight: bold;
        text-align: left;
    }

    h3 {
        margin-top: 0;
        color: #333333;
        font-size: 14px;
        font-weight: bold;
        text-align: left;
    }

    td,
    th {
        font-size: 16px;
    }

    p,
    ul,
    ol,
    blockquote {
        margin: .4em 0 1.1875em;
        font-size: 16px;
        line-height: 1.625;
    }

    p.sub {
        font-size: 13px;
    }

    /* Utilities ------------------------------ */

    .align-right {
        text-align: right;
    }

    .align-left {
        text-align: left;
    }

    .align-center {
        text-align: center;
    }

    /* Buttons ------------------------------ */

    .button {
        background-color: #3869D4;
        border-top: 10px solid #3869D4;
        border-right: 18px solid #3869D4;
        border-bottom: 10px solid #3869D4;
        border-left: 18px solid #3869D4;
        display: inline-block;
        color: #FFF;
        text-decoration: none;
        border-radius: 3px;
        box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
        -webkit-text-size-adjust: none;
        box-sizing: border-box;
    }

    .button--green {
        background-color: #22BC66;
        border-top: 10px solid #22BC66;
        border-right: 18px solid #22BC66;
        border-bottom: 10px solid #22BC66;
        border-left: 18px solid #22BC66;
    }

    .button--red {
        background-color: #FF6136;
        border-top: 10px solid #FF6136;
        border-right: 18px solid #FF6136;
        border-bottom: 10px solid #FF6136;
        border-left: 18px solid #FF6136;
    }

    @media only screen and (max-width: 500px) {
        .button {
            width: 100% !important;
            text-align: center !important;
        }
    }

    /* Attribute list ------------------------------ */

    .attributes {
        margin: 0 0 21px;
    }

    .attributes_content {
        background-color: #F4F4F7;
        padding: 16px;
    }

    .attributes_item {
        padding: 0;
    }

    /* Related Items ------------------------------ */

    .related {
        width: 100%;
        margin: 0;
        padding: 25px 0 0 0;
        -premailer-width: 100%;
        -premailer-cellpadding: 0;
        -premailer-cellspacing: 0;
    }

    .related_item {
        padding: 10px 0;
        color: #CBCCCF;
        font-size: 15px;
        line-height: 18px;
    }

    .related_item-title {
        display: block;
        margin: .5em 0 0;
    }

    .related_item-thumb {
        display: block;
        padding-bottom: 10px;
    }

    .related_heading {
        border-top: 1px solid #CBCCCF;
        text-align: center;
        padding: 25px 0 10px;
    }

    /* Discount Code ------------------------------ */

    .discount {
        width: 100%;
        margin: 0;
        padding: 24px;
        -premailer-width: 100%;
        -premailer-cellpadding: 0;
        -premailer-cellspacing: 0;
        background-color: #F4F4F7;
        border: 2px dashed #CBCCCF;
    }

    .discount_heading {
        text-align: center;
    }

    .discount_body {
        text-align: center;
        font-size: 15px;
    }

    /* Social Icons ------------------------------ */

    .social {
        width: auto;
    }

    .social td {
        padding: 0;
        width: auto;
    }

    .social_icon {
        height: 20px;
        margin: 0 8px 10px 8px;
        padding: 0;
    }

    /* Data table ------------------------------ */

    .purchase {
        width: 100%;
        margin: 0;
        padding: 35px 0;
        -premailer-width: 100%;
        -premailer-cellpadding: 0;
        -premailer-cellspacing: 0;
    }

    .purchase_content {
        width: 100%;
        margin: 0;
        padding: 25px 0 0 0;
        -premailer-width: 100%;
        -premailer-cellpadding: 0;
        -premailer-cellspacing: 0;
    }

    .purchase_item {
        padding: 10px 0;
        color: #51545E;
        font-size: 15px;
        line-height: 18px;
    }

    .purchase_heading {
        padding-bottom: 8px;
        border-bottom: 1px solid #EAEAEC;
    }

    .purchase_heading p {
        margin: 0;
        color: #85878E;
        font-size: 12px;
    }

    .purchase_footer {
        padding-top: 15px;
        border-top: 1px solid #EAEAEC;
    }

    .purchase_total {
        margin: 0;
        text-align: right;
        font-weight: bold;
        color: #333333;
    }

    .purchase_total--label {
        padding: 0 15px 0 0;
    }

    body {
        background-color: #F2F4F6;
        color: #51545E;
    }

    p {
        color: #51545E;
    }

    .email-wrapper {
        width: 100%;
        margin: 0;
        padding: 0;
        -premailer-width: 100%;
        -premailer-cellpadding: 0;
        -premailer-cellspacing: 0;
        background-color: #F2F4F6;
    }

    .email-content {
        width: 100%;
        margin: 0;
        padding: 0;
        -premailer-width: 100%;
        -premailer-cellpadding: 0;
        -premailer-cellspacing: 0;
    }

    /* Masthead ----------------------- */

    .email-masthead {
        padding: 25px 0;
        text-align: center;
    }

    .email-masthead_logo {
        width: 94px;
    }

    .email-masthead_name {
        font-size: 16px;
        font-weight: bold;
        color: #A8AAAF;
        text-decoration: none;
        text-shadow: 0 1px 0 white;
    }

    /* Body ------------------------------ */

    .email-body {
        width: 100%;
        margin: 0;
        padding: 0;
        -premailer-width: 100%;
        -premailer-cellpadding: 0;
        -premailer-cellspacing: 0;
    }

    .email-body_inner {
        width: 570px;
        margin: 0 auto;
        padding: 0;
        -premailer-width: 570px;
        -premailer-cellpadding: 0;
        -premailer-cellspacing: 0;
        background-color: #FFFFFF;
    }

    .email-footer {
        width: 570px;
        margin: 0 auto;
        padding: 0;
        -premailer-width: 570px;
        -premailer-cellpadding: 0;
        -premailer-cellspacing: 0;
        text-align: center;
    }

    .email-footer p {
        color: #A8AAAF;
    }

    .body-action {
        width: 100%;
        margin: 30px auto;
        padding: 0;
        -premailer-width: 100%;
        -premailer-cellpadding: 0;
        -premailer-cellspacing: 0;
        text-align: center;
    }

    .body-sub {
        margin-top: 25px;
        padding-top: 25px;
        border-top: 1px solid #EAEAEC;
    }

    .content-cell {
        /* padding: 45px; */
        padding-left: 45px;
        padding-top: 45px;
        padding-right: 45px;
        padding-bottom: 0px;
    }

    /*Media Queries ------------------------------ */

    @media only screen and (max-width: 600px) {

        .email-body_inner,
        .email-footer {
            width: 100% !important;
        }
    }

    @media (prefers-color-scheme: dark) {

        body,
        .email-body,
        .email-body_inner,
        .email-content,
        .email-wrapper,
        .email-masthead,
        .email-footer {
            background-color: #333333 !important;
            color: #FFF !important;
        }

        p,
        ul,
        ol,
        blockquote,
        h1,
        h2,
        h3 {
            color: #FFF !important;
        }

        .attributes_content,
        .discount {
            background-color: #222 !important;
        }

        .email-masthead_name {
            text-shadow: none !important;
        }
    }
    </style>
</head>

<body>
    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="padding-top: 60px;">
        <tr>
            <td>
                <table class="email-content" width="650" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="email-body" width="650" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="650" cellpadding="0" cellspacing="0"
                                role="presentation">
                                <tr>
                                    <td class="content-cell">
                                        <div class="f-fallback">
                                            <div
                                                style="width: 100%; margin-bottom: 10px; text-align: center; margin-top: 30px;">
                                                <h2 style="font-size: 30px;font-style: normal;font-weight: 800;line-height: 36px;text-align: center;
                                                font-family: Muli,-apple-system,system-ui,helvetica,'helvetica neue'
                                                ,ubuntu,roboto,noto,'segoe ui',arial,sans-serif;">Welcome to
                                                    GoSkillBoost LMS</h2>
                                                <div
                                                    style="font-family:Muli,-apple-system,system-ui,helvetica,'helvetica neue',ubuntu,roboto,noto,'segoe ui',arial,sans-serif;font-size:16px;font-style:normal;line-height:22px;text-align:left;color:#000000">
                                                    Hi Test, congratulations on joining GoSkillBoost LMS. Begin your
                                                    learning journey by setting up your account.</div>
                                                <br /><br />
                                                <img src="<?= base_url('uploads/system/lms-logo.png'); ?>"
                                                    alt="Color logo - no background.png" width="213" height="136"
                                                    style="margin-right:0px" data-image-whitelisted=""
                                                    class="CToWUd a6T" tabindex="0">
                                            </div><br /><br />

                                            <h2 style="font-size: 18px;">Hi <?= $full_name; ?>,</h2>
                                            <br>
                                            <?php if(!empty($status)){ ?>
                                            <h2 style="font-size: 18px; text-align: center; color: red;">
                                                <?= $status; ?>
                                            </h2>
                                            <?php } else {?>
                                            <p>We are delighted to welcome you to GoSkillBoost Learning Portal.</p>

                                            <p>Get Started</p>
                                            <p>At GoSkillBoost, we want you to feel empowered in your career journey,
                                                with the right training tools and resources to support you.</p>
                                            <p>Spending just a little time on your own career development help you keep
                                                up to date with knowledge, improve your everyday work skills, and get
                                                you where you want to go-faster.</p>
                                            <p>It’s the steps you take today that will impact your tomorrow. So, whether
                                                it’s ten minutes a day, or an hour each week, start taking charge of
                                                your own career journey.</p>
                                            <p>To get started with your online training click on the link below and
                                                enter the username and password. </p>
                                            <br>
                                            <p style="margin-bottom: 9px; margin-top:2px;">LMS LINK:
                                                <a href="<?= site_url('home/login'); ?>" target="_blank">LMS Login
                                                    Here</a>
                                            </p>
                                            <p style="margin-bottom: 9px; margin-top:2px;">Username:
                                                <b style="cursor: pointer;"><?= $to; ?></b>
                                            </p>
                                            <?php if(isset($password)  && !empty($password)){?>
                                            <p style="margin-bottom: 9px; margin-top:2px;">Password:
                                                <b style="cursor: pointer;"><?= $password; ?></b>
                                            </p>
                                            <?php } ?>

                                            <br>
                                            <p>Continue to your account settings inside the page and change your
                                                password before logging out.</p>
                                            <?php } ?>
                                            <hr style="border: 1px solid #efefef; margin-top: 50px;">
                                            <br>
                                            <div
                                                style="font-family:Muli,-apple-system,system-ui,helvetica,'helvetica neue',ubuntu,roboto,noto,'segoe ui',arial,sans-serif;font-size:16px;font-style:normal;line-height:22px;text-align:left;color:#000000">
                                                Take the next step and achieve your learning goals with access
                                                to
                                                quality training content across a variety of topics that are
                                                relevant to
                                                you.</div>
                                            <br />
                                        </div>
                                    </td>
                                </tr>
                                <td>
                                    <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                                        style="background:#f1f6f9;background-color:#f1f6f9;width:100%">
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div style="margin:0px auto;max-width:600px">
                                                        <table align="center" border="0" cellpadding="0" cellspacing="0"
                                                            role="presentation" style="width:100%">
                                                            <tbody>
                                                                <tr>
                                                                    <td
                                                                        style="direction:ltr;font-size:0px;padding:0;padding-top:24px;text-align:center">
                                                                        <div
                                                                            style="padding-left:30px;padding-right:30px;background-color:#fff;overflow:hidden;margin:0px auto;max-width:600px">
                                                                            <table align="center" border="0"
                                                                                cellpadding="0" cellspacing="0"
                                                                                role="presentation" style="width:100%">
                                                                                <tbody>
                                                                                    <tr>
                                                                                        <td
                                                                                            style="direction:ltr;font-size:0px;padding:0;padding-top:26px;text-align:center; padding-bottom:26px">
                                                                                            <br>
                                                                                        </td>
                                                                                    </tr>
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                        <div
                                                                            style="padding-left:30px;padding-right:30px;background-color:#fff;overflow:hidden;margin:0px auto;max-width:600px">
                                                                            <table align="center" border="0"
                                                                                cellpadding="0" cellspacing="0"
                                                                                role="presentation" style="width:100%">
                                                                                <tbody></tbody>
                                                                            </table>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0"
                                role="presentation">
                                <tr>
                                    <td class="content-cell" align="center">
                                        <p class="f-fallback sub align-center">&copy;
                                            <?= get_settings('system_name'); ?>. All rights reserved.</p>
                                        <p class="f-fallback sub align-center">
                                            <?= get_settings("address"); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>