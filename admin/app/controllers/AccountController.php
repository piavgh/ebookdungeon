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
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Date;
class AccountController extends ControllerBase {
	
	private function _deleteUser($user_id = 0) {
		$result = true;
		
		try {
			$user = Users::findFirstByuser_id($user_id);
			if (!$user) {
				$this->logger->error("Delete user: $user_id does not exits");
					
				return $result;
			}
			
			// Obtain transaction manager
			$manager = $this->transactions;
				
			// Request a transaction
			$transaction = $manager->get();
			
			// Delete group that user own
			$group = Groups::findFirst ( "owner_id = $user_id" );
			if ($group) {
				$tmp_group_id = $group->group_id;
				$group_members = GroupMember::find ( "group_id = $tmp_group_id" );
				if ($group_members) {
					foreach ( $group_members as $group_member ) {
						$group_member->setTransaction($transaction);
						if (! $group_member->delete ()) {
							$result = false;
							foreach ( $group_member->getMessages () as $message ) {
								$transaction->rollback($message->getMessage());
							}
						}
					}
				}
				$group->setTransaction($transaction);
				if (! $group->delete ()) {
					foreach ( $group->getMessages () as $message ) {
						$transaction->rollback($message->getMessage());
					}
				}
			}
			
			// Delete contents belong to user
			$contents = Contents::find ( "owner_id = $user_id" );
			if ($contents) {
				foreach ( $contents as $content ) {
					$shared_contents = SharedContents::find ( "content_id = $content->content_id" );
					if ($shared_contents) {
						foreach ( $shared_contents as $shared_content ) {
							$shared_content->setTransaction($transaction);
							if (! $shared_content->delete ()) {
								$result = false;
								// Rollback the transaction
								foreach ( $shared_content->getMessages () as $message ) {
									$transaction->rollback($message->getMessage());
								}
							}
						}
					}
			
					// Delete conversion contents
					$conversions = Conversions::find("content_id=$content->content_id");
					if ($conversions) {
						foreach ($conversions as $conversion) {
							$conversion->setTransaction($transaction);
							if (! $conversion->delete ()) {
								$result = false;
								// Rollback the transaction
								foreach ( $conversion->getMessages () as $message ) {
									$transaction->rollback($message->getMessage());
								}
							}
						}
					}
					
					// Delete physical
					$filepath = $content->path;
					$success = true;
					if (is_file($filepath))
						$success = unlink($filepath);
					else
						$success = FALSE;
					
					if ($success == FALSE) {
						$result = FALSE;
						$err = error_get_last();
						$transaction->rollback($err['message']);
					}
					
					
					$content->setTransaction($transaction);
					if (! $content->delete ()) {
						$result = false;
						// Rollback the transaction
						foreach ( $content->getMessages () as $message ) {
							$transaction->rollback($message->getMessage());
						}
					}
				}
			}
			
			// Delete the contents the removed_contents table
			
			$removedContents = RemovedContents::find("owner_id = $user->user_id");
			if ($removedContents) {
				foreach ($removedContents as $removedContent) {
					// Delete physical
					$removedContent_filepath = $removedContent->path;
					$success = true;
					if (is_file($removedContent_filepath))
						$success = unlink($removedContent_filepath);
					else
						$success = FALSE;
					
					if ($success == FALSE) {
						$result = FALSE;
						$err = error_get_last();
						$transaction->rollback($err['message']);
					}
					
					$removedContent->setTransaction($transaction);
					if (! $removedContent->delete ()) {
						$result = false;
						// Rollback the transaction
						foreach ( $conversion->getMessages () as $message ) {
							$transaction->rollback($message->getMessage());
						}
					}
				}
			}
			
			$user->setTransaction($transaction);
			if (! $user->delete ()) {
				$result = false;
				// Rollback the transaction
				foreach ( $user->getMessages () as $message ) {
					$transaction->rollback($message->getMessage());
				}
			}
			
			// Commit the transaction
			$transaction->commit();
		} catch (TxFailed $ex) {
            $this->logger->error("Delete user exception: " . $ex->getMessage());
        }
        return $result;
	}
	
	public function initialize() {
		$this->view->setTemplateAfter ( 'main' );
		Tag::setTitle ( 'Manage Accounts' );
		parent::initialize ();
	}
	
	protected function getForm($entity = null, $edit = false) {
		$form = new Form ( $entity );
		
		if (! $edit) {
			$form->add ( new Text ( "user_id", array (
					"size" => 10,
					"maxlength" => 10 
			) ) );
		} else {
			$form->add ( new Hidden ( "user_id" ) );
		}
		
		$form->add ( new Text ( "user_name", array (
				"size" => 24,
				"maxlength" => 70 
		) ) );
		
		$form->add ( new Password ( "password", array (
				"size" => 24,
				"maxlength" => 100 
		) ) );
		
		$form->add ( new Password ( "password_confirm", array (
				"size" => 24,
				"maxlength" => 100 
		) ) );
		
		$form->add ( new Text ( "email", array (
				"size" => 24,
				"maxlength" => 70 
		) ) );
		
		$form->add ( new Text ( "first_name", array (
				"size" => 10,
				"maxlength" => 30 
		) ) );
		
		$form->add ( new Text ( "last_name", array (
				"size" => 10,
				"maxlength" => 30 
		) ) );
		
		$form->add ( new Text ( "phone", array (
				"size" => 20,
				"maxlength" => 40 
		) ) );
		
		$form->add ( new Date ( "expiration" ) );
		
		return $form;
	}
	
	public function indexAction() {
		// Get session info
		$admin_auth = $this->session->get ( 'admin_auth' );
		
		// Query the active user
		$admin = Admin::findFirst ( $admin_auth ['id'] );
		if ($admin == false) {
			return $this->forward ( 'index/index' );
		}
		
		$numberPage = 1;
		if ($this->request->isPost ()) {
			$query = Criteria::fromInput ( $this->di, "Users", $_POST );
			$this->persistent->searchParams = $query->getParams ();
		} else {
			$numberPage = $this->request->getQuery ( "page", "int" );
			if ($numberPage <= 0) {
				$numberPage = 1;
			}
		}
		
		$users = Users::find ();
		
		$paginator = new Phalcon\Paginator\Adapter\Model ( array (
				"data" => $users,
				"limit" => 10,
				"page" => $numberPage 
		) );
		$page = $paginator->getPaginate ();
		
		$this->view->setVar ( "page", $page );
		$this->view->setVar ( "users", $users );
	}
	
	public function searchAction() {
		$numberPage = 1;
		if ($this->request->isPost ()) {
			$query = Criteria::fromInput ( $this->di, "Users", $_POST );
			$this->persistent->searchParams = $query->getParams ();
		} else {
			$numberPage = $this->request->getQuery ( "page", "int" );
			if ($numberPage <= 0) {
				$numberPage = 1;
			}
		}
		
		$parameters = array ();
		if ($this->persistent->searchParams) {
			$parameters = $this->persistent->searchParams;
		}
		
		$users = Users::find ( $parameters );
		if (count ( $users ) == 0) {
			$this->flash->notice ( "The search did not find any $users" );
			return $this->forward ( "account/index" );
		}
		
		$paginator = new Phalcon\Paginator\Adapter\Model ( array (
				"data" => $users,
				"limit" => 10,
				"page" => $numberPage 
		) );
		$page = $paginator->getPaginate ();
		
		$this->view->setVar ( "page", $page );
		$this->view->setVar ( "users", $users );
	}
	
	public function newAction() {
		$this->view->form = $this->getForm ();
	}
	
	public function createAction() {
		try {
			if (! $this->request->isPost ()) {
				return $this->forward ( "account/index" );
			}
			
			$password = $this->request->getPost ( "password" );
			$password_confirm = $this->request->getPost ( "password_confirm" );
			
			if ($password != $password_confirm) {
				$this->flash->error ( "Passwords do not match" );
				return $this->forward ( "account/new" );
			} else {
				
				$user = new Users ();
				$user->user_name = $this->request->getPost ( "user_name", "striptags" );
				$user->password_hash = sha1 ( $this->request->getPost ( "password" ) );
				$user->verification_hash = $user->password_hash;
				$user->email = $this->request->getPost ( "email", "email" );
				$user->first_name = $this->request->getPost ( "first_name", "striptags" );
				$user->last_name = $this->request->getPost ( "last_name", "striptags" );
				$user->phone = $this->request->getPost ( "phone", "striptags" );
				$user->expiration = "0";
				$user->extra_time = 0;
				$user->status = "active";
				
				if (! $user->save ()) {
					foreach ( $user->getMessages () as $message ) {
						$this->flash->error ( ( string ) $message );
						$this->logger->error ( "Create User Error : " . ( string ) $message );
					}
					return $this->forward ( "account/new" );
				}
				
				$this->flash->success ( "User was created successfully" );
				return $this->forward ( "account/index" );
			}
		} catch ( Exception $e ) {
			$this->logger->error ( "Create User Errors : " . $e->getMessage () );
		}
	}
	
	public function deleteAction() {
		$deleteItems = array ();
		if (!$this->request->isPost()) {
			return $this->forward("account/index");
		}
		try {
			// Check if request has made with POST
			if ($this->request->isPost () == true) {
				// Access POST data
				$deleteItems = $this->request->getPost ( "item" );
				$result = TRUE;
				if (count($deleteItems) > 0) {
					// Delete all of them
					foreach ( $deleteItems as $user_id ) {
						$result = $this->_deleteUser($user_id) ? $result : FALSE;
					}
				}
			}
		} catch ( Exception $e ) {
			$this->logger->error ( "Delete User Errors : " . $e->getMessage () );
		}
		
		if ($result == FALSE)
			$this->flash->error("Delete contents failed");
		else
			$this->flash->success("User was deleted successfully");
		
		return $this->forward("account/index");
	}
	
	public function enableAction($user_id) {
		try {
			$user = Users::findFirst ( "user_id = $user_id" );
			if (! $user) {
				$this->flash->error ( "User was not found" );
				return $this->forward ( "account/index" );
			}
			
			if ($user->status == "inactive") {
				$user->status = "active";
				if (! $user->save ()) {
					foreach ( $user->getMessages () as $message ) {
						$this->flash->error ( ( string ) $message );
						$this->logger->error ( ( string ) $message );
					}
					return $this->forward ( "account/index" );
				}
				$this->flash->success ( "User status was changed to 'active' successfully" );
				return $this->forward ( "account/index" );
			} else if ($user->status == "active") {
				$this->flash->error ( "User is already 'active'" );
				return $this->forward ( "account/index" );
			} else {
				$this->flash->error ( "User is pending" );
				return $this->forward ( "account/index" );
			}
		} catch ( Exception $e ) {
			$this->logger->error ( "Enable Errors : " . $e->getMessage () );
		}
	}
	
	public function disableAction($user_id) {
		try {
			$user = Users::findFirst ( "user_id = $user_id" );
			if (! $user) {
				$this->flash->error ( "User was not found" );
				return $this->forward ( "account/index" );
			}
			
			if ($user->status == "active") {
				$user->status = "inactive";
				if (! $user->save ()) {
					foreach ( $user->getMessages () as $message ) {
						$this->flash->error ( ( string ) $message );
						$this->logger->error ( ( string ) $message );
					}
					return $this->forward ( "account/index" );
				}
				$this->flash->success ( "User status was changed to 'inactive' successfully" );
				return $this->forward ( "account/index" );
			} else if ($user->status == "inactive") {
				$this->flash->error ( "User is already 'inactive'" );
				return $this->forward ( "account/index" );
			} else {
				$this->flash->error ( "User is pending" );
				return $this->forward ( "account/index" );
			}
		} catch ( Exception $e ) {
			$this->logger->error ( "Disable Errors : " . $e->getMessage () );
		}
	}
	
	public function changemaximumAction($user_id) {
		try {
		$user = Users::findFirstByuser_id($user_id);
		if (!$user) {
			$this->flash->error ( "User was not found" );
			return $this->forward ( "account/index" );
		}
		
		$this->view->setVar("user", $user);
		} catch (Exception $e) {
			$this->logger->error("Change Maximum Space error : " . $e->getMessage());
		}
	}
	
	public function updatemaximumAction() {
		if (!$this->request->isPost()) {
			return $this->forward("account/index");
		}
		try {
			$user_id = $this->request->getPost ("user_id", "int");
			$user = Users::findFirstByuser_id($user_id);
			if (!$user) {
				$this->flash->error("User was not found");
				return $this->forward("account/index");
			}
			
			$maximum = $this->request->getPost("new_maximum", "int");
			$maximum = $maximum * 1024 * 1024;
			if ($user->used <= $maximum) {
				$user->maximum = $maximum;
				$user->available = $maximum - $user->used;
			} else {
				$this->flash->error("User can not used more than maximum space");
				return $this->redirect("account/changemaximum/$user_id");
			}
			
			if (!$user->save()) {
				foreach ( $user->getMessages () as $message ) {
					$this->flash->error ( ( string ) $message );
					$this->logger->error ( "Update Maximum Space Errors : " . ( string ) $message );
				}
				return $this->redirect ( "account/changemaximum/$user_id" );
			}
			$this->flash->success ( "User maximum space was updated successfully" );
			return $this->redirect ( "account/changemaximum/$user_id" );
		} catch (Exception $e) {
			$this->logger->error ( "Update Maximum Space Errors : " . $e->getMessage () );
		}
	}
}
