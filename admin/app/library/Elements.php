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

/**
 * Elements
 *
 * Helps to build UI elements for the application
 */
class Elements extends Phalcon\Mvc\User\Component
{

    private $_headerMenu = array(
//         'pull-left' => array(
//             'dashboard' => array(
//                 'caption' => 'Dashboard',
//                 'action' => 'index'
//             ),
//         )
    );

    private $_tabs = array(
        'Dashboard' => array(
            'controller' => 'dashboard',
            'action' => 'index',
            'any' => false
        ),
        'Manage Groups' => array(
            'controller' => 'groups',
            'action' => 'index',
            'any' => true
        ),
        'Manage Accounts' => array(
            'controller' => 'account',
            'action' => 'index',
            'any' => true
        ),
        'Manage Contents' => array(
            'controller' => 'contents',
            'action' => 'index',
            'any' => true
        ),
        'My Profile' => array(
            'controller' => 'profile',
            'action' => 'index',
            'any' => true
        )
    );

    /**
     * Builds header menu with left and right items
     *
     * @return string
     */
    public function getMenu()
    {

        $admin_auth = $this->session->get('admin_auth');
        if ($admin_auth) {
            $this->_headerMenu['pull-right']['dashboard'] = array(
                'caption' => 'Hello, admin',
                'action' => 'index'
            );
            $this->_headerMenu['pull-right']['session'] = array(
                'caption' => 'Log Out',
                'action' => 'end'
            );
        } else {
        	unset($this->_headerMenu['pull-left']['dashboard']);
        }

        echo '<div class="nav-collapse">';
        $controllerName = $this->view->getControllerName();
        foreach ($this->_headerMenu as $position => $menu) {
            echo '<ul class="nav ', $position, '">';
            foreach ($menu as $controller => $option) {
                if ($controllerName == $controller) {
                    echo '<li class="active">';
                } else {
                    echo '<li>';
                }
                echo Phalcon\Tag::linkTo($controller.'/'.$option['action'], $option['caption']);
                echo '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
    }

    public function getTabs()
    {
        $controllerName = $this->view->getControllerName();
        $actionName = $this->view->getActionName();
        echo '<ul class="nav nav-tabs">';
        foreach ($this->_tabs as $caption => $option) {
            if ($option['controller'] == $controllerName && ($option['action'] == $actionName || $option['any'])) {
                echo '<li class="active">';
            } else {
                echo '<li>';
            }
            echo Phalcon\Tag::linkTo($option['controller'].'/'.$option['action'], $caption), '<li>';
        }
        echo '</ul>';
    }
}
