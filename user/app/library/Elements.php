<?php
/**
 * Elements
 *
 * Helps to build UI elements for the application
 */
use Phalcon\Tag as Tag;

class Elements extends \Phalcon\Mvc\User\Component {

    private $_tabs = array(
        'Products' => array(
            'controller' => 'products',
            'action' => 'index',
            'any' => true
        ),
        'Services' => array(
            'controller' => 'services',
            'action' => 'index',
            'any' => true
        ),
        'My Account' => array(
            'controller' => 'session',
            'action' => 'index',
            'any' => true
        ),
        'About us' => array(
            'controller' => 'aboutus',
            'action' => 'index',
            'any' => true
        ),
        'Contact us' => array(
            'controller' => 'contactus',
            'action' => 'index',
            'any' => false
        )
    );
    private $_menu = array(
        'Contents' => array(
            'controller' => 'contents',
            'action' => 'index',
            'any' => false
        ),
        'Groups' => array(
            'controller' => 'groups',
            'action' => 'index',
            'any' => true
        ),
        'Account' => array(
            'controller' => 'account',
            'action' => 'index',
            'any' => true
        )
    );
    private $_group_menu = array(
        'Lobby' => array(
            'controller' => 'lobby',
            'action' => 'index',
            'any' => false
        ),
        'Group Admin' => array(
            'controller' => 'groupadmin',
            'action' => 'index',
            'any' => false
        ),
        'Contents' => array(
            'controller' => 'contents',
            'action' => 'index',
            'any' => false
        ),
        'Groups' => array(
            'controller' => 'groups',
            'action' => 'index',
            'any' => false
        ),
        'Account' => array(
            'controller' => 'account',
            'action' => 'index',
            'any' => true
        )
    );

    /*
     * Build top login header
     * 
     */

    public function getLoginHeader() {
        echo '<div class="row" id="sign-in-row">';
        echo '<div class="col-lg-4"></div><div class="col-lg-4"></div>';
        echo '<div class="col-lg-4" id="sign-in">' .
        '<span id="signin-container" class="btn-sm">' .
        'Already a customer,' .
        Phalcon\Tag::linkTo(array("session",
            "log in",
            "class" => "btn-sm btn-login")) .
        '</span></div>';
        echo '</div>';
    }

    /*
     * Build header navigation menu
     * 
     */

    public function getNavMenu() {
        $controllerName = $this->view->getControllerName();
        $actionName = $this->view->getActionName();
        echo '<div class="masthead">';

        echo '<ul class="nav nav-justified">';
        foreach ($this->_tabs as $caption => $option) {

            if ($option['controller'] == $controllerName && ($option['action'] == $actionName || $option['any'])) {
                echo '<li class="active">';
            } else {
                echo '<li>';
            }
            echo Phalcon\Tag::linkTo($option['controller'] . '/' . $option['action'], $caption), '</li>';
        }
        echo '</ul>';

        echo '</div>';
    }

    /*
     * Build back-end menu
     */

    private function _getBackendMenu($menu) {
        $controllerName = $this->view->getControllerName();
        $actionName = $this->view->getActionName();

        $auth = $this->session->get('auth');
        $userName = $auth['name'];
        $userId = $auth['id'];

        $maxSpace = $usedSpace = $percent = 0;
        $user = Users::findFirst("user_id = $userId");
        if ($user) {
            $maxSpace = intval($user->maximum);
            $usedSpace = intval($user->used);
            $percent = ($maxSpace != 0) ? round((($usedSpace / $maxSpace) * 100), 2) : 0;
        }

        echo '<div class="masthead">';

        echo '<div class="navbar navbar-default" role="navigation">
                <div class="container-fluid">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                        <a class="navbar-brand" href="#">Media Cloud</a>
                    </div>
                    <div class="navbar-collapse collapse">
                        <ul class="nav navbar-nav">';

        foreach ($menu as $caption => $option) {
            if ($option['controller'] == $controllerName && ($option['action'] == $actionName || $option['any'])) {
                echo '<li class="active">';
            } else {
                echo '<li>';
            }
            echo Phalcon\Tag::linkTo($option['controller'] . '/' . $option['action'], $caption), '</li>';
        }
        echo '</ul>
                <ul class="nav navbar-nav navbar-right">
                
            <li class="dropdown">';
        echo \Phalcon\Tag::linkTo(array(
            "#",
            '<span class="glyphicon glyphicon-user"></span>' . ' ' . $userName,
            "class" => "dropdown-toggle",
            "data-toggle" => "dropdown",
            "role" => "menu"
        ));

        echo "<ul class='dropdown-menu'>";

        echo "<li role='presentation'>";
        echo "<div class='account-space'>" . "<span class='info-space used-space'>" . Utils::formatFileSize($usedSpace) . "</span>" . " ({$percent}%)" . " of " . "<span class='info-space '>" . Utils::formatFileSize($maxSpace) . "</span>" . " used" . "</div>";
        echo "</li>";

        echo "<li role='presentation' class='account-space'>";
        echo "<div class='progress' style='margin-bottom:0; height:7px;'>";
        echo "<div class='progress-bar progress-bar-success' role='progressbar' aria-valuenow='{$percent}' aria-valuemin='0' aria-valuemax='100' style='width: {$percent}%;'>";
        echo "</div>";
        echo "</div>";
        
        echo "</li>";

        echo "<li role='presentation' class='divider'></li>";
        echo "<li>" . \Phalcon\Tag::linkTo("account/index", "Account") . "</li>";
        echo "</ul>";

        echo '</li><li>';
        echo \Phalcon\Tag::linkTo("session/end", "Log Out");
        echo '</li>
            
                </ul>
            </div><!--/.nav-collapse -->
        </div><!--/.container-fluid -->
    </div>';
    }

    /*
     * Build groups side bar
     */

    public function getGroupSidebar($userId, $active = 0) {

        $activeTag = '<li class="active">';

        echo '<ul class="nav nav-sidebar">';

        if ($active == 0)
            echo $activeTag;
        else
            echo '<li>';

        echo Tag::linkTo("groups/index", "Group");

        echo '</li>';

        $groupMembers = GroupMember::find("member_id=$userId AND member_status='active'");
        foreach ($groupMembers as $member) {
            $groupId = $member->group_id;
            $group = Groups::findFirst("group_id=$groupId");
            if ($active == $groupId)
                echo $activeTag;
            else
                echo '<li>';
            echo Phalcon\Tag::linkTo("groups/access/" . $groupId, $group->group_name);
            echo '</li>';
        }

        echo '</ul>';
    }

    public function getIndividualMenu() {
        $this->_getBackendMenu($this->_menu);
    }

    public function getGroupMenu() {
        $this->_getBackendMenu($this->_group_menu);
    }

}
