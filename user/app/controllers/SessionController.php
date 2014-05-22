<?php
use Phalcon\Tag as Tag;

class SessionController extends ControllerBase {

    public function initialize() {
        $this->view->setTemplateAfter('main');
        Tag::setTitle('Register/Log In');
        parent::initialize();
    }

    public function indexAction() {
        $auth = $this->session->get('auth');
        if ($auth) {
            $userId = $auth['id'];
            $userName = $auth['name'];
            $groupId = $auth['group_id'];

            if ($groupId > 0) {
                return $this->redirect("lobby/index");
            } else {
                return $this->redirect("contents/index");
            }
        }
    }

    /**
     * Register authenticated user into session data
     *
     * @param Users $user
     */
    private function _registerSession($user, $grpId = 0) {

        session_unset();

        $this->session->set('auth', array(
            'id' => $user->user_id,
            'name' => $user->user_name,
            'group_id' => $grpId
        ));
    }

    /*
     * Register new user
     */

    public function registerAction() {
        $validate = TRUE;
        $request = $this->request;
        if ($request->isPost()) {
            if (isset($_POST['register_submit'])) {
                $accountType = $request->getPost('accounttype');
                $username = $request->getPost('username', 'alphanum');
                $email = $request->getPost('email', 'email');
                $password = $request->getPost('password');
                $firstname = $request->getPost('firstname');
                $lastname = $request->getPost('lastname');
                $mobilephone = $request->getPost('mobilephone');

                $verification_hash = md5(uniqid(rand(), 1));

                $user = new Users();
                $user->user_name = $username;
                $user->password_hash = sha1($password);
                $user->verification_hash = $verification_hash;
                $user->email = $email;
                $user->first_name = $firstname;
                $user->last_name = $lastname;
                $user->phone = $mobilephone;
                $user->expiration = new Phalcon\Db\RawValue('now()');
                $user->extra_time = 0;
                $user->status = 'active';
                $user->maximum = new Phalcon\Db\RawValue('default');
                $user->used = new Phalcon\Db\RawValue('default');
                ;
                $user->available = new Phalcon\Db\RawValue('default');

                //$user->created_at = new Phalcon\Db\RawValue('now()');

                try {
                    if ($user->save() == false) {
                        $validate = false;
                        $message = $user->getMessages();
                        $this->flash->error((string) $message[0]);

                        // Logging
                        foreach ($user->getMessages() as $m) {
                            $this->logger->error($m);
                        }
                    } else {
                        if ($accountType == "group") {
                            // Create group
                            $group = new Groups();
                            $group->owner_id = $user->user_id;
                            $group->group_name = $request->getPost('groupname');

                            if ($group->save() == false) {
                                $validate = FALSE;

                                // Delete user member
                                if ($user->delete() == false) {
                                    foreach ($user->getMessages() as $m) {
                                        $this->logger->error('Register error: ' . $m);
                                    }
                                }

                                $message = $group->getMessages();
                                $this->flash->error((string) $message[0]);
                            }
                        }

                        // Send mail if validation is passed
                        if ($validate == true) {
                            $this->flash->success('Thanks for sign-up! You can now log in!');
                        }
                    }
                } catch (\Exception $ex) {
                    $this->logger->error("Register exception: " . $ex->getMessage());
                }
            }
        }
        return $this->forward('session/index');
    }

    /*
     * Verify the user account
     */

    public function verifyAction() {
        $request = $this->request;
        if ($request->isGet()) {
            $userName = $request->getQuery("username");
            $verification = $request->getQuery("id");

            $user = Users::findFirst("user_name='$userName' AND verification_hash='$verification'");
            if ($user == false) {
                $this->flash->error("Your account has not been created. Please sign-up again!");
            }

            try {
                if ($user != false) {
                    /*
                      $expire = new DateTime($user->verification);
                      $now = new Date();
                      $diff = $now->diff($expire);
                      if ($diff->s > 86400)
                      {
                      $this->flash->error ("This verification has been expired. Please re-send the verification.");
                      } */

                    $user->status = 'active';
                    if ($user->save())
                        $this->flash->success("Your account has been successfully activated!");
                }
            } catch (\Exception $exc) {
                $this->logger->error("Verify account exception: " . $exc->getMessage());
            }
        }
        return $this->forward("session/index");
    }

    /*
     * Reset password
     */

    public function resetAction($hash = NULL) {
        Tag::setTitle('Reset Password');

        // Confirm email to reset
        if ($hash == NULL) {
            $this->view->content = 1;   // Display email form

            $requeset = $this->request;
            if ($requeset->isPost() && $requeset->hasPost('reset_email')) {
                try {
                    $email = $requeset->getPost('reset_email');

                    $user = Users::findFirst("email = '$email'");

                    // Check user existence
                    if (!$user) {
                        $this->flash->error($this->config->message->error->user_mail_existence);
                    } else if ($user->status != 'active') {
                        $this->flash->error($this->config->message->error->account_verification);
                    } else {
                        $reset_hash = md5(uniqid(rand(), 1));
                        $user->reset_password_hash = $reset_hash;
                        $user->expiration = new Phalcon\Db\RawValue('now()');
                        $user->extra_time = $this->config->password_expire_time;

                        if ($user->save() == FALSE) {
                            $this->flash->error($this->config->message->error->update_content);
                        } else {
                            // Send reset password mail
                            if ($this->mail->sendResetPasswordMail($email, $reset_hash))
                                $this->flash->success($this->config->message->info->reset_password);
                            else
                                $this->flash->error($this->config->message->error->send_mail);
                        }
                    }
                } catch (Exception $ex) {
                    $this->logger->error("Reset password exception: " . $ex->getMessage());
                    $this->flash->error("Error has occured. Please try again later!");
                }
            }
        }

        // Reset password
        else {
            $this->view->content = 2;   // Display reset password form

            $user = Users::findFirst("reset_password_hash = '$hash'");
            if (!$user)
                $this->flash->error($this->config->message->error->password_reset);
            else {
                $now = time();

                $format = 'Y-m-d H:i:s';
                $expDate = DateTime::createFromFormat($format, $user->expiration);
                $expTimeStamp = $expDate->getTimestamp();
                $extra = intval($user->extra_time);

                if ($expTimeStamp + $extra <= $now) {
                    $this->flash->error($this->config->message->error->password_reset);
                } else {
                    $this->view->hash = $hash;
                    $this->view->email = $user->email;
                }
            }
        }
    }

    /*
     * Reset user password
     */

    public function resetpasswordAction() {
        $request = $this->request;
        if ($request->isPost()) {
            $hash = $request->getPost('reset_password_hash');
            $password = $request->getPost('reset_password');
            $confirm_password = $request->getPost('confirm_reset_password');

            $user = Users::findFirst("reset_password_hash = '$hash'");

            if (!$user)
                $this->flash->error($this->config->message->error->password_reset);
            else {
                // Check expired time
                $now = time();
                $format = 'Y-m-d H:i:s';
                $expDate = DateTime::createFromFormat($format, $user->expiration);
                $expTimeStamp = $expDate->getTimestamp();
                $extra = intval($user->extra_time);

                if ($expTimeStamp + $extra <= $now) {
                    $this->flash->error($this->config->message->error->password_reset);
                }

                // Check matching password
                else if ($password != $confirm_password) {
                    $this->flash->error($this->config->message->error->confirm_password);
                } else {
                    $user->password_hash = sha1($password);
                    $user->reset_password_hash = null;
                    if ($user->save() == false) {
                        $this->flash->error($this->config->message->error->update_content);

                        // Logging
                        foreach ($user->getMessages() as $m) {
                            $this->logger->error("Reset password error: " . (string) $m);
                        }
                    } else {
                        $this->flash->success($this->config->message->info->password_change);
                    }
                }
            }
        }
        return $this->forward("session/index");
    }

    /**
     * This actions receive the input from the login form
     *
     */
    public function startAction() {
        if ($this->request->isPost()) {
            $username = $this->request->getPost('login_username', 'alphanum');
            $password = $this->request->getPost('login_password');
            $password = sha1($password);

            $user = Users::findFirst("user_name='$username' AND password_hash='$password'");
            if ($user != false && $user->status == 'active') {
                $group = Groups::findFirst("owner_id='$user->user_id'");

                if ($group == false) {
                    $this->_registerSession($user);
                    return $this->redirect("contents/index");
                } else {
                    $this->_registerSession($user, $group->group_id);
                    return $this->redirect("contents/index");
                }

                //return $this->forward('contents/index');
            } else if ($user != false && $user->status != 'active')
                $this->flash->error('Your account has not been activated!');

            else if ($user == false)
                $this->flash->error('Wrong username/password');
        }

        return $this->forward('session/index');
    }

    /**
     * Finishes the active session redirecting to the index
     *
     * @return unknown
     */
    public function endAction() {
        $this->session->destroy();

        //$this->flash->success('Goodbye!');
        //return $this->forward('/index');
        return $this->redirect("index");
    }

    /*
     * Verify group admin activity to 
     * active or reject the member joining request
     */

    public function verifymemberAction($groupId = 0, $memberId = 0) {
        $request = $this->request;

        if ($request->isGet()) {
            $action = $request->getQuery("action");
            $token = $request->getQuery("id");

            try {

                $group_member = GroupMember::findFirst("group_id = $groupId AND member_id = $memberId AND action_token = '$token'");

                if ($group_member) {
                    $group_member->member_status = ($action == "1") ? 'active' : 'rejected';
                    if ($group_member->save()) {
                        $this->flash->success($this->config->message->success->save);
                    } else {
                        $this->flash->error($this->config->message->error->change_member_status);
                        $this->logger->error("Cannot change status member_id = $member_id, group_id = $groupId");
                    }
                } else {
                    $this->flash->error($this->config->message->error->change_member_status);
                    $this->flash->error($this->config->message->error->member_exist);
                }
            } catch (Exception $ex) {
                $this->logger->error("Change group member status by link exception: " . $ex->getMessage());
            }

            /*
             * Fowarding to group admin page incase
             * current group admin is logging in
             */
            $auth = $this->session->get('auth');
            if ($auth && $groupId == $auth['group_id']) {
                return $this->forward("groupadmin/index");
            }
        }
    }

}
