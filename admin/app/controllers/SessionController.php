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
class SessionController extends ControllerBase {
	public function initialize() {
		$this->view->setTemplateAfter ( 'main' );
		Tag::setTitle ( 'Log In' );
		parent::initialize ();
	}
	public function indexAction() {
// 		$auth = $this->session->get ( 'auth' );
// 		if ($auth) {
// 		$this->session->remove ( 'auth' );
// 		var_dump ( $auth . "AAA" );
// 		//die;
// 		$auth = $this->session->get ( 'auth' );
// 		if ($auth) {
// 		var_dump ( $auth . "BBB" );
// 		die ();
// 		}
// 		}
// 		$admin_auth = $this->session->get ( 'admin_auth' );
// 		if ($admin_auth) {
// 			return $this->forward ( "contents/index" );
// 		}
	}
	
	/**
	 * Register authenticated user into session data
	 *
	 * @param Users $user        	
	 */
	private function _registerSession($admin) {
		$this->session->set ( 'admin_auth', array (
				'id' => $admin->id,
				'name' => $admin->name 
		) );
	}
	
	/**
	 * This actions receive the input from the login form and do the login
	 */
	public function startAction() {
		session_unset();
		try {
			if ($this->request->isPost ()) {
				$name = $this->request->getPost ( 'name', 'alphanum' );
				
				$password = $this->request->getPost ( 'password' );
				$password = sha1 ( $password );
				
				$admin = Admin::findFirst ( "name='$name' AND password='$password'" );
				if ($admin != false) {
					$this->_registerSession ( $admin );
					$this->flash->success ( 'Welcome ' . $admin->name );
					// Forward to the 'dashboard' controller if the user is valid
					return $this->redirect ( "dashboard/index" );
				} else {
					$this->flash->error ( 'Wrong username or password' );
					// Forward to the login form again
					return $this->forward ( 'session/index' );
				}
			}
			
		} catch ( Exception $e ) {
			$this->logger->error ( "Login Errors : " . $e->getMessage () );
		}
	}
	
	/**
	 * Finishes the active session redirecting to the index
	 *
	 * @return unknown
	 */
	public function endAction() {
		$this->session->remove ( 'admin_auth' );
		$this->flash->success ( 'Goodbye!' );
		// return $this->forward('index/index');
		return $this->forward ( "index" );
	}
}
