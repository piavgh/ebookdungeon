<?php
use Phalcon\Tag as Tag;

class AccountController extends ControllerBase {

    public function initialize() {
        $this->view->setTemplateAfter('backend');
        Tag::setTitle('Account');
        parent::initialize();
    }

    public function indexAction() {
        //Get session info
        $auth = $this->session->get('auth');
        $userId = $auth['id'];
        $groupId = $auth['group_id'];

        /*
         * Get group account info
         */
        if ($groupId > 0)
        {
            // Get group name
            $groupName = Groups::findFirst(array(
                "group_id = $groupId",
                "columns" => "group_name"
            ));
            
            if ($groupName) $this->view->setVar ("groupName", $groupName->group_name);
            
            // Get total members
            $members = GroupMember::find(array(
                "conditions" => "member_status = 'active' AND group_id = ?0",
                "bind" => array($groupId)
            ));
            $this->view->setVar("totalMembers", count($members));
            
            // Get total contents
            $contents = SharedContents::find(array(
                "content_status = 'shared' AND group_id = ?0",
                "bind" => array($groupId)
            ));
            $this->view->setVar("totalContents", count($contents));
            
        }
        
        $this->view->setVar('groupId', $groupId);

        try {
            //Query the active user
            $user = Users::findFirst($userId);
            if ($user == false) {
                $this->forward('index/index');
            }

            $request = $this->request;
            if (!$request->isPost() || !$request->has('update_info')) {
                Tag::setDefault('email', $user->email);
                Tag::setDefault('firstname', $user->first_name);
                Tag::setDefault('lastname', $user->last_name);
                Tag::setDefault('mobilephone', $user->phone);
            } 

            /*
             *  Handle user info update request
             */
            if ($request->hasPost('update_info')) {

                $email = $request->getPost('email', 'email');
                $first_name = $request->getPost('firstname', 'string');
                $last_name = $request->getPost('lastname', 'string');
                $phone = $request->getPost('mobilephone', 'string');
                $new_password = $request->getPost('newpassword', 'string');
                $confirm_password = $request->getPost('confirmpassword', 'string');

                if ($new_password != $confirm_password) {
                    $this->flash->error("Your password does not match!");
                } else {

                    $first_name = strip_tags($first_name);
                    $last_name = strip_tags($last_name);
                    $phone = strip_tags($phone);
                    $new_password = strip_tags($new_password);

                    $user->email = $email;
                    $user->first_name = $first_name;
                    $user->last_name = $last_name;
                    $user->phone = $phone;
                    if (empty($new_password) == FALSE)
                        $user->password_hash = sha1($new_password);

                    if ($user->save() == false) {
                        $messages = $user->getMessages();
                        $this->flash->error((string) $messages[0]);

                        // Logging
                        foreach ($messages as $m) {
                            $this->logger->error("Save account info: " . (string) $m);
                        }
                    } else {
                        $this->flash->success('Your profile information was updated successfully');
                    }
                }
            }

            /*
             * Handle group creating request
             */ else if ($request->hasPost('create_group')) {
                $groupName = $request->getPost('group_name');
                $group = Groups::findFirst("group_name = '$groupName'");
                // Check if group has already existed
                if ($group) {
                    $this->flash->error($this->config->message->error->group_name);
                }
                // Create new group
                else {
                    $group = new Groups();
                    $group->owner_id = $userId;
                    $group->group_name = $groupName;

                    if ($group->save() == false) {
                        $messages = $group->getMessages();
                        $this->flash->error((string) $messages[0]);

                        // Logging
                        foreach ($messages as $m) {
                            $this->logger->error("Update to group admin error: " . (string) $m);
                        }
                    } else {
                        // Update session
                        $this->session->set('auth', array(
                            'id' => $auth['id'],
                            'name' => $auth['name'],
                            'group_id' => $group->group_id
                        ));

                        $this->flash->success($this->config->message->success->upgrade_group);
                    }
                }
            }
        } catch (Exception $ex) {
            $this->logger->error('Update account info exception: ' . $ex->getMessage());
        }
    }

}
