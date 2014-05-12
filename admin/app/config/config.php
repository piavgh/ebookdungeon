<?php

//====================================================================
// Copyright 2012 - 2014 Pacific NW Investments, Ltd. All Rights Reserved.
//
// This software, in source or compiled form, is confidential and proprietary
// information and is protected by Canadian copyright laws and
// international treaty provisions.
//
// The intellectual and technical concepts contained herein are proprietary
// to Pacific NW Investments, Ltd. and may be covered by Canadian and
// ForeignPatents, patents in process, and are protected by trade secret
// or copyright law.  Use and/or duplication, is forbidden without written permission
// from Pacific NW Investments, Ltd.
//====================================================================

$settings = array(
    'database' => array(
        'host'        => 'localhost',
        'username'    => 'root',
        'password'    => '123456',
        'dbname'      => 'ebookdungeon',
    ),
    'application' => array(
        'controllersDir' => '/../app/controllers/',
        'modelsDir'      => '/../app/models/',
        'viewsDir'       => '/../app/views/',
        'pluginsDir'     => '/../app/plugins/',
        'libraryDir'     => '/../app/library/',
        'cacheDir'       => '/../app/cache/',
    	'logDir'		 => '/../../log/admin/',
        'baseUri'        => '/admin/',
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
    				'upgrade_group' => 'Account has changed successfully!'
    		),
    		'error' => array(
    				'make_dir' => 'Error has occured. Please try again later or contact us for the help!',
    				'change_member_status' => "Cannot change member's status. Please try again later!",
    				'delete_content' => 'Error has occured. Content cannot be deleted',
    				'update_content' => 'Error has occured!',
    				'user_mail_existence' => 'This email does not exist in our system.',
    				'account_verification' => 'Your account has not been activated.',
    				'send_mail' => 'Email cannot be sent. Please try again later',
    				'password_reset' => 'Password Reset Not Found',
    				'confirm_password' => 'Password does not match',
    				'group_name' => 'This group is not available. Please try another!'
    		)
    )
);

return new \Phalcon\Config($settings);
