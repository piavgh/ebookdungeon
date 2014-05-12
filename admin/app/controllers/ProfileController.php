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

use Phalcon\Tag as Tag;
use Phalcon\Flash as Flash;
use Phalcon\Session as Session;
class ProfileController extends ControllerBase {
	public function initialize() {
		$this->view->setTemplateAfter ( 'main' );
		Tag::setTitle ( 'Manage your Profile' );
		parent::initialize ();
	}
	public function indexAction() {

		// Get session info
		$admin_auth = $this->session->get ( 'admin_auth' );
		
		// Query the active user
		$admin = Admin::findFirst ( $admin_auth ['id'] );
		if ($admin == false) {
			return $this->forward ( 'index/index' );
		}
		
		$this->view->adminname = $admin->name;
		$this->view->adminemail = $admin->email;
	}
	public function changeemailAction() {
		try {
			// Get session info
			$admin_auth = $this->session->get ( 'admin_auth' );
			
			// Query the active user
			$admin = Admin::findFirst ( $admin_auth ['id'] );
			if ($admin == false) {
				return $this->forward ( 'index/index' );
			}
			
			$request = $this->request;
			
			if (! $request->isPost ()) {
				Tag::setDefault ( 'email', $admin->email );
			} else {
				$email = $request->getPost ( 'email', 'email' );
				$password = $request->getPost ( 'password' );
				$password = sha1 ( $password );
				
				$admin = Admin::findFirst ( "password='$password'" );
				if ($admin != false) {
					$admin->email = $email;
					if ($admin->save () == false) {
						foreach ( $admin->getMessages () as $message ) {
							$this->flash->error ( ( string ) $message );
							$this->logger->error ( ( string ) $message );
						}
					} else {
						$this->flash->success ( 'Your email was updated successfully' );
					}
				} else {
					$this->flash->error ( 'Wrong password' );
				}
			}
		} catch ( Exception $e ) {
			$this->logger->error ( "Change Email Errors : " . $e->getMessage () );
		}
	}
	public function changepassAction() {
		try {
			// Get session info
			$admin_auth = $this->session->get ( 'admin_auth' );
			
			// Query the active user
			$admin = Admin::findFirst ( $admin_auth ['id'] );
			if ($admin == false) {
				return $this->forward ( 'index/index' );
			}
			
			$request = $this->request;
			
			if ($request->isPost ()) {
				$current_password = $request->getPost ( 'current_password' );
				$current_password = sha1 ( $current_password );
				$new_password = $request->getPost ( 'new_password' );
				$confirm_password = $request->getPost ( 'confirm_password' );
				
				$admin = Admin::findFirst ( "password='$current_password'" );
				if ($admin != false) {
					if ($new_password != $confirm_password) {
						$this->flash->error ( 'Passwords do not match !' );
					} elseif ($current_password == sha1 ( $new_password )) {
						$this->flash->error ( 'New password can not be the same as old password' );
					} else {
						$new_password = sha1 ( $new_password );
						$admin->password = $new_password;
						if ($admin->save () == false) {
							foreach ( $admin->getMessages () as $message ) {
								$this->flash->error ( ( string ) $message );
								$this->logger->error ( ( string ) $message );
							}
						} else {
							$this->flash->success ( 'Your password was updated successfully' );
						}
					}
				} else {
					$this->flash->error ( 'Wrong current password !' );
				}
			}
		} catch ( Exception $e ) {
			$this->logger->error ( "Change Pass Errors : " . $e->getMessage () );
		}
	}
}