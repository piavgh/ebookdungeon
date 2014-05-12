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
class GroupsController extends ControllerBase {
	public function initialize() {
		$this->view->setTemplateAfter ( 'main' );
		Tag::setTitle ( 'Manage Groups' );
		parent::initialize ();
	}
	public function indexAction() {
		// Get session info
		$admin_auth = $this->session->get ( 'admin_auth' );
		
		// Query the active user
		$admin = Admin::findFirst ( $admin_auth ['id'] );
		if ($admin == false) {
			$this->_forward ( 'index/index' );
		}
		
		$numberPage = 1;
		if ($this->request->isPost ()) {
			$query = Criteria::fromInput ( $this->di, "Groups", $_POST );
			$this->persistent->searchParams = $query->getParams ();
		} else {
			$numberPage = $this->request->getQuery ( "page", "int" );
			if ($numberPage <= 0) {
				$numberPage = 1;
			}
		}
		
		$sql = "SELECT Groups.group_id, Groups.owner_id, Groups.group_name, Users.user_name "
				. "FROM Groups, Users "
				. "WHERE Groups.owner_id = Users.user_id ";
			
		$query = $this->modelsManager->createQuery($sql);
		$groups = $query->execute();
		
		$paginator = new Phalcon\Paginator\Adapter\Model ( array (
				"data" => $groups,
				"limit" => 10,
				"page" => $numberPage 
		) );
		$page = $paginator->getPaginate ();
		
		$this->view->setVar ( "page", $page );
		$this->view->setVar ( "groups", $groups );
	}
	
	/**
	 * Manage group members, redirect to manage members page
	 * @param int group_id
	 */
	public function manageAction($group_id) {
		try {
			$group = Groups::findFirstBygroup_id($group_id);
			if (!$group) {
				$this->flash->error("Group was not found");
				return $this->forward("groups/index");
			}
			
			$numberPage = 1;
			if (!$this->request->isPost ()) {
				$numberPage = $this->request->getQuery ( "page", "int" );
				if ($numberPage <= 0) {
					$numberPage = 1;
				}
			}
			
			$sql = "SELECT GroupMember.group_id, GroupMember.member_id, GroupMember.member_status, Users.user_name "
					. "FROM GroupMember, Users "
					. "WHERE GroupMember.member_id = Users.user_id "
							. "AND GroupMember.group_id = :groupId: ";
			
			$query = $this->modelsManager->createQuery($sql);
			$group_members = $query->execute(array('groupId' => $group_id));
			
			$paginator = new Phalcon\Paginator\Adapter\Model ( array (
					"data" => $group_members,
					"limit" => 10,
					"page" => $numberPage
			) );
			$page = $paginator->getPaginate ();
			
			$this->view->setVar ( "page", $page );
			$this->view->setVar ( "group", $group );
		} catch (Exception $e) {
			$this->logger->error('Edit content exception: ' . $ex->getMessage());
			return $this->forward("groups/index");
		}
	}
	
	/**
	 * Select members to add to group
	 * @param int $group_id
	 */
	public function addmemberAction($group_id) {
		try {
			$group = Groups::findFirstBygroup_id($group_id);
			if (!$group) {
				$this->flash->error("Group was not found");
				return $this->forward("groups/index");
			}
			
			$numberPage = 1;
			
			if (!$this->request->isPost()) {
				$numberPage = $this->request->getQuery("page", "int");
				if ($numberPage <= 0) {
					$numberPage = 1;
				}
			}
			
			$members = Users::find("status = 'active'");

			$array1 = array();
			$sql = "SELECT GroupMember.member_id FROM GroupMember WHERE GroupMember.group_id = :group_id:";
			$query = $this->modelsManager->createQuery($sql);
			$members_in_group = $query->execute(array('group_id' => $group_id));
			
			foreach ($members_in_group as $member_in_group) {
				$array1[] = $member_in_group->member_id;
			}
			
			$sql = "SELECT Groups.owner_id FROM Groups WHERE Groups.group_id = :group_id:";
			$query = $this->modelsManager->createQuery($sql);
			$owner_of_group = $query->execute(array('group_id' => $group_id));
				
			foreach ($owner_of_group as $owner) {
				$array1[] = $owner->owner_id;
			}

			if (count($members) == 0) {
				$this->flash->notice("The search did not find any member");
			}
			
			$paginator = new Phalcon\Paginator\Adapter\Model(array(
					"data" => $members,
					"limit" => 10,
					"page" => $numberPage
			));
			$page = $paginator->getPaginate ();
			
			$this->view->setVar ( "group", $group );
			$this->view->setVar ( "page", $page );
			$this->view->setVar("members", $members);
			$this->view->setVar("array1", $array1);
			
		} catch (Exception $e) {
			$this->logger->error ( "Add Members Errors : " . $e->getMessage () );
		}
	}
	
	/**
	 * Search member to add to group
	 * @param int $group_id
	 */
	public function searchAction($group_id) {
		try {
			$group = Groups::findFirstBygroup_id($group_id);
			if (!$group) {
				$this->flash->error("Group was not found");
				return $this->forward("groups/index");
			}
			
			$numberPage = 1;
				
			if (!$this->request->isPost()) {
				$numberPage = $this->request->getQuery("page", "int");
				if ($numberPage <= 0) {
					$numberPage = 1;
				}
			} else {
				$this->persistent->memberName = $this->request->getPost('member_name');
			}
			
			if ($this->persistent->memberName) {
				$member_name = $this->persistent->memberName;
			}
				
			//$members = Users::find("status = 'active'");
			
			$sql2 = "SELECT Users.* FROM Users WHERE Users.status = 'active' ";
			$sql2 = (isset($member_name)) ? ($sql2 . "AND Users.user_name LIKE '%$member_name%'" ) : $sql2;
			$query = $this->modelsManager->createQuery($sql2);
			$members = $query->execute();
		
			$array1 = array();
			$sql = "SELECT GroupMember.member_id FROM GroupMember WHERE GroupMember.group_id = :group_id:";
			$query = $this->modelsManager->createQuery($sql);
			$members_in_group = $query->execute(array('group_id' => $group_id));
				
			foreach ($members_in_group as $member_in_group) {
				$array1[] = $member_in_group->member_id;
			}
				
			$sql = "SELECT Groups.owner_id FROM Groups WHERE Groups.group_id = :group_id:";
			$query = $this->modelsManager->createQuery($sql);
			$owner_of_group = $query->execute(array('group_id' => $group_id));
		
			foreach ($owner_of_group as $owner) {
				$array1[] = $owner->owner_id;
			}
		
			if (count($members) == 0) {
				$this->flash->notice("The search did not find any member");
			}
				
			$paginator = new Phalcon\Paginator\Adapter\Model(array(
					"data" => $members,
					"limit" => 10,
					"page" => $numberPage
			));
			$page = $paginator->getPaginate ();
				
			$this->view->setVar ( "group", $group );
			$this->view->setVar ( "page", $page );
			$this->view->setVar("members", $members);
			$this->view->setVar("array1", $array1);
				
		} catch (Exception $e) {
			$this->logger->error ( "Add Members Errors : " . $e->getMessage () );
		}
	}
	
	/**
	 * Active member in group
	 * @param int $user_id
	 * @param int $group_id
	 */
	public function activememberAction($user_id, $group_id) {
		try {
			$group_member = GroupMember::findFirst("group_id = $group_id AND member_id = $user_id");
		
			if ($group_member) {
				$group_member->member_status = 'active';
				if ($group_member->save()) {
					$this->flash->success($this->config->message->success->save);
				} else {
					$this->flash->error($this->config->message->error->change_member_status);
					$this->logger->error("Cannot change status member_id = $user_id, group_id = $group_id");
				}
			}
		} catch (Exception $ex) {
			$this->logger->error("Active group member exception: " . $ex->getMessage());
		}
		
		return $this->redirect("groups/manage/$group_id");
	}
	
	/**
	 * Reject member in group
	 * @param int $user_id
	 * @param int $group_id
	 */
	public function rejectmemberAction($user_id, $group_id) {
		try {
			$group_member = GroupMember::findFirst("group_id = $group_id AND member_id = $user_id");
		
			if ($group_member) {
				$group_member->member_status = 'rejected';
				if ($group_member->save()) {
					$this->flash->success($this->config->message->success->save);
				} else {
					$this->flash->error($this->config->message->error->change_member_status);
					$this->logger->error("Cannot change status member_id = $user_id, group_id = $group_id");
				}
			}
		} catch (Exception $ex) {
			$this->logger->error("Reject group member exception: " . $ex->getMessage());
		}
		
		return $this->redirect("groups/manage/$group_id");
	}
	
	/**
	 * Delete members from group
	 * @param int $user_id
	 * @param int $group_id
	 */
	public function deletememberAction($user_id, $group_id) {
		try {
			// Delete shared contents first
			$contentList = Contents::find("owner_id = $user_id");
			foreach ($contentList as $content) {
				$sharedContent = SharedContents::findFirst("content_id = $content->content_id AND group_id = $group_id");
				if ($sharedContent)
					$sharedContent->delete();
			}
		
			// Delete member from the group
			$member = GroupMember::find(array("group_id = :group_id: AND member_id = :member_id:",
					"bind" => array('group_id' => $group_id, 'member_id' => $user_id)
			));
		
			if (!$member) {
				$this->flash->error("Member was not found");
			} else {
				if ($member->delete())
					$this->flash->success("Member was deleted successfully");
			}
		} catch (Exception $ex) {
			$this->logger->error("Delete group member exception: " . $ex->getMessage());
		}
		
		return $this->redirect("groups/manage/$group_id");
	}
	
	/**
	 * Add member to group
	 * @param int $user_id
	 * @param int $group_id
	 */
	public function saveusertogroupAction($user_id, $group_id) {
		$foundMember = GroupMember::findFirst("group_id = $group_id & member_id = $user_id");
		if ($foundMember) {
			$this->flash->error("This user is already in group!");
			return $this->forward("groups/addmember");
		}
		
		$member = new GroupMember();
		$member->group_id = $group_id;
		$member->member_id = $user_id;
		$member->member_status = "active";
		
		if (!$member->save()) {
			$this->flash->error("Failed to add user to group");
			return $this->forward("groups/addmember");
		} else {
			$this->flash->success("Add user to group successfully");
			return $this->redirect("groups/manage/$group_id");
		}
	}
	
	/**
	 * Delete groups
	 */
	public function deleteAction() {
		try {
			$deleteItems = array ();
			// Check if request has made with POST
			if ($this->request->isPost () == true) {
				// Access POST data
				$deleteItems = $this->request->getPost ( "item" );
				// Delete all of them
				foreach ( $deleteItems as $group_id ) {
					$group_members = GroupMember::find ( "group_id = $group_id" );
					if ($group_members) {
						foreach ( $group_members as $group_member ) {
							if (! $group_member->delete ()) {
								foreach ( $group_member->getMessages () as $message ) {
									$this->flash->error ( ( string ) $message );
									$this->logger->error ( ( string ) $message );
								}
								return $this->forward ( "groups/index" );
							}
						}
					}
					$group = Groups::findFirst ( "group_id = $group_id" );
					if (! $group) {
						$this->flash->error ( "Group was not found" );
						return $this->forward ( "groups/index" );
					}
					
					if (! $group->delete ()) {
						foreach ( $group->getMessages () as $message ) {
							$this->flash->error ( ( string ) $message );
							$this->logger->error ( ( string ) $message );
						}
						return $this->forward ( "groups/index" );
					}
				}
			}
			
			$this->flash->success ( "Group was deleted successfully" );
			return $this->forward ( "groups/index" );
		} catch ( Exception $e ) {
			$this->logger->error ( "Delete Groups Errors : " . $e->getMessage () );
		}
	}
}
