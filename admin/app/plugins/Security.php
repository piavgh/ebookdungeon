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

use Phalcon\Events\Event,
	Phalcon\Mvc\User\Plugin,
	Phalcon\Mvc\Dispatcher,
	Phalcon\Acl;

/**
 * Security
 *
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class Security extends Plugin
{

	public function __construct($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;
	}

	public function getAcl()
	{
		if (!isset($this->persistent->acl)) {

			$acl = new Phalcon\Acl\Adapter\Memory();

			$acl->setDefaultAction(Phalcon\Acl::DENY);

			//Register roles
			$roles = array(
				'users' => new Phalcon\Acl\Role('Users'),
				'guests' => new Phalcon\Acl\Role('Guests')
			);
			foreach ($roles as $role) {
				$acl->addRole($role);
			}

			//Private area resources
			$privateResources = array(
				'admin' => array('index', 'profile'),
				'dashboard' => array('index', 'profile'),
                'groups' => array('index', 'manage', 'delete', 'search', 'addmember', 'activemember', 'rejectmember', 'deletemember', 'saveusertogroup'),
                'account' => array('index', 'search', 'new', 'edit', 'save', 'create', 'delete', 'enable', 'disable', 'changemaximum', 'updatemaximum'),
                'contents' => array('index', 'search', 'new', 'edit', 'update', 'delete', 'modify', 'deleteselect', 'show'),
				'profile' => array('index', 'changeemail', 'changepass')
			);
			foreach ($privateResources as $resource => $actions) {
				$acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
			}

			//Public area resources
			$publicResources = array(
				'index' => array('index'),
                'session' => array('index', 'start', 'end')
			);
			foreach ($publicResources as $resource => $actions) {
				$acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
			}

			//Grant access to public areas to both users and guests
			foreach ($roles as $role) {
				foreach ($publicResources as $resource => $actions) {
					$acl->allow($role->getName(), $resource, '*');
				}
			}

			//Grant acess to private area to role Users
			foreach ($privateResources as $resource => $actions) {
				foreach ($actions as $action){
					$acl->allow('Users', $resource, $action);
				}
			}

			//The acl is stored in session, APC would be useful here too
			$this->persistent->acl = $acl;
		}

		return $this->persistent->acl;
	}

	/**
	 * This action is executed before execute any action in the application
	 */
	public function beforeDispatch(Event $event, Dispatcher $dispatcher)
	{

		$admin_auth = $this->session->get('admin_auth');
		if (!$admin_auth){
			$role = 'Guests';
		} else {
			$role = 'Users';
		}

		$controller = $dispatcher->getControllerName();
		$action = $dispatcher->getActionName();

		$acl = $this->getAcl();

		$allowed = $acl->isAllowed($role, $controller, $action);
		if ($allowed != Acl::ALLOW) {
			$this->flash->error("You don't have access!");
			$dispatcher->forward(
				array(
					'controller' => 'session',
					'action' => 'index'
				)
			);
			return false;
		}

	}

}
