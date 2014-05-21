<?php
$settings = array(
    'database' => array(
        'adapter' => 'Mysql',
        'host' => 'localhost',
        'username' => 'root',
        'password' => '123456',
        'dbname' => 'ebookdungeon',
    ),
    'application' => array(
        'controllersDir' => __DIR__ . '/../../app/controllers/',
        'modelsDir' => __DIR__ . '/../../app/models/',
        'viewsDir' => __DIR__ . '/../../app/views/',
        'pluginsDir' => __DIR__ . '/../../app/plugins/',
        'libraryDir' => __DIR__ . '/../../app/library/',
        'cacheDir' => __DIR__ . '/../../app/cache/',
        'baseUri' => '/',
    ),
    'url' => "http://" . $_SERVER['SERVER_NAME'] . '/',
    'mail' => array(
        'to' => 'hoangth@agile.vn',
        'from' => 'noreply@example.com',
        'mime' => 'MIME-Version: 1.0',
        'contentType' => 'Content-Type: text/html; charset=utf-8',
        'priority' => 'X-Priority: 1'
    ),
    'login_attemp' => 5,
    'password_expire_time' => 3600 * 24, // one day
    'upload_dir' => 'C:\Ampps\www\ebookdungeon\upload\\',
    'log' => array(
        'error' => 'C:\Ampps\www\ebookdungeon\log\user\error.log',
        'access' => 'C:\Ampps\www\ebookdungeon\log\user\access.log'
    ),
    'message' => array(
        'info' => array(
            'reset_password' => "Check your inbox for the next steps. If you don't receive an email, and it's not in your spam folder, this could mean you signed up with a different address.",
            'password_change' => 'Your password has been changed.'
        ),
        'success' => array(
            'upload' => 'Content was uploaded successfully!',
            'save' => 'Info was updated successfully!',
            'update_content' => 'Content was updated successfully!',
            'upgrade_group' => 'Account has changed successfully!',
            'change_group_name' => 'Group name changed successfully'
        ),
        'error' => array(
            'make_dir' => 'Error has occured. Please try again later or contact us for the help!',
            'change_member_status' => "Cannot change member's status. Please try again later!",
            'change_group_name' => 'Can not change group name. Please try again later!',
            'delete_content' => 'Error has occured. Content cannot be deleted',
            'update_content' => 'Error has occured!',
            'user_mail_existence' => 'This email does not exist in our system.',
            'account_verification' => 'Your account has not been activated.',
            'send_mail' => 'Email cannot be sent. Please try again later',
            'password_reset' => 'Password Reset Not Found',
            'confirm_password' => 'Password does not match',
            'group_name' => 'This group is not available. Please try another!',
            'member_exist' => 'Member does not exist in the group'
        )
    )
);

return new \Phalcon\Config($settings);
