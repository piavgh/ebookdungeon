<?php
use Phalcon\Mvc\Model\Transaction\Manager as txManager;
use Phalcon\Mvc\Model\Transaction\Failed as txFailed;
use Phalcon\Tag as Tag;
use Phalcon\Paginator\Adapter\Model as Paginator;

class GroupsController extends ControllerBase {

    public function initialize() {
        $this->view->setTemplateAfter('backend');
        Tag::setTitle('Groups');
        parent::initialize();
    }

    public function indexAction() {

        $auth = $this->session->get('auth');
        $userId = $auth['id'];
        $groupId = $auth['group_id'];

        $numberPage = 1;

        if (!$this->request->isPost()) {
            $numberPage = $this->request->getQuery("page", "int");
            if ($numberPage <= 0) {
                $numberPage = 1;
            }
        }

        try {
            $sql = "SELECT Groups.*, Users.user_name,  GroupMember.member_status FROM Groups "
                    . "JOIN Users on Groups.owner_id = Users.user_id "
                    . "LEFT JOIN GroupMember on GroupMember.group_id = Groups.group_id AND GroupMember.member_id = $userId "
                    . "WHERE Groups.group_id != $groupId ";
            $query = $this->modelsManager->createQuery($sql);
            $contents = $query->execute();

            $paginator = new Paginator(array(
                "data" => $contents,
                "limit" => 10,
                "page" => $numberPage
            ));

            $this->view->page = $paginator->getPaginate();
        } catch (Exception $ex) {
            $this->logger->error("Group list exception: " . $ex->getMessage());
        }
    }

    public function joinAction($group_id) {

        $auth = $this->session->get('auth');
        $userId = $auth['id'];
        $userName = $auth['name'];

        $group = Groups::findFirstBygroup_id($group_id);
        $owner = Users::findFirst("user_id = $group->owner_id");
        $token = md5(uniqid(rand(), 1));
        
        if ($group && $owner) {
            $member = GroupMember::findFirst(array("group_id = $group_id AND member_id = $userId"));

            if (!$member) {
                $groupMeber = new GroupMember();
                $groupMeber->group_id = $group->group_id;
                $groupMeber->member_id = $userId;
                $groupMeber->member_status = 'pending';
                $groupMeber->action_token = $token;
                
                if ($groupMeber->save() == false) {
                    $messages = $groupMeber->getMessages();
                    $this->flash->error($messages[0]);

                    // Logging
                    foreach ($messages as $m) {
                        $this->logger->error("Join group error: " . $m);
                    }
                } else {
                    /*
                     * Send joining request mail to group owner
                     */
                    if ($this->mail->sendJoinRequestMail($owner->email, $owner->first_name, $group->group_name, $userName, $userId, $group_id, $token) == FALSE) {
                        $this->logger->error("Cannot send joining group request mail from user: <$userName> to group: <$group->group_name>");
                    }

                    $this->flash->success("Joining request has been sent. Wait the approval of the admin!");
                }
            } elseif ($member && $member->member_status == 'rejected') {
                $member->member_status = 'pending';
                if ($member->save() == false) {
                    $messages = $member->getMessages();
                    $this->flash->error($messages[0]);

                    // Logging
                    foreach ($messages as $m) {
                        $this->logger->error("Join group error: " . $m);
                    }
                } else {
                    /*
                     * Send joining request mail to group owner
                     */
                    if ($this->mail->sendJoinRequestMail($owner->email, $owner->first_name, $group->group_name, $userName, $userId, $group_id, $token) == FALSE) {
                        $this->logger->error("Cannot send joining group request mail from user: <$userName> to group: <$group->group_name>");
                    }
                    
                    $this->flash->success("Joining request has been sent. Wait the approval of the admin!");
                }
            }
        } else {
            $this->flash->error("This group doesn't exist!");
            // Logging
            $this->logger->error("Join group: $group_id doesn't exist");
        }
        return $this->forward("groups/index");
    }

    public function leaveAction($group_id) {
        $auth = $this->session->get('auth');
        $userId = $auth['id'];

        // Delete shared contents
        $phql = "SELECT SharedContents.* FROM SharedContents "
                . "JOIN Contents on Contents.content_id = SharedContents.content_id "
                . "WHERE SharedContents.group_id = ?0 AND Contents.owner_id = ?1";

        $manager = $this->modelsManager;
        $sharedContents = $manager->executeQuery($phql, array(0 => $group_id, 1 => $userId));

        $member = GroupMember::findFirst("group_id = $group_id AND member_id = $userId");

        try {
            // Create a transaction manager
            $manager = new txManager();

            // Request a transaction
            $transaction = $manager->get();

            // Deleting content
            foreach ($sharedContents as $sharedContent) {
                $content = Contents::findFirst("content_id = $sharedContent->content_id");

                $sharedContent->setTransaction($transaction);
                if ($sharedContent->delete() == false) {
                    // Something went wrong, rollback transaction
                    foreach ($sharedContent->getMessages() as $message) {
                        $transaction->rollback($message->getMessage());
                    }
                }

                // Check to change content status
                $content->setTransaction($transaction);
                $tmpContent = SharedContents::find("content_id = $sharedContent->content_id AND content_status='shared'");
                if (count($tmpContent) == 1) {
                    $content->status = 'private';
                    if ($content->save() == false) {
                        // Rollback
                        foreach ($content->getMessages() as $message) {
                            $transaction->rollback($message->getMessage());
                        }
                    }
                }
            }

            // Delete group member
            $member->setTransaction($transaction);
            if ($member->delete() == FALSE) {
                foreach ($member->getMessages() as $message) {
                    $transaction->rollback($message->getMessage());
                }
            }

            // Everything goes fine, commit transaction
            $transaction->commit();
        } catch (Failed $ex) {
            $this->logger->error("Leave group exception: " . $ex->getMessage());
        }

        return $this->forward("groups/index");
    }

    public function searchAction() {
        $auth = $this->session->get('auth');
        $userId = $auth['id'];
        $groupId = $auth['group_id'];

        $numberPage = 1;
        if ($this->request->isPost()) {
            $this->persistent->groupName = $this->request->getPost('group_name');
        } else {
            $numberPage = $this->request->getQuery("page", "int");
            if ($numberPage <= 0) {
                $numberPage = 1;
            }
        }

        if ($this->persistent->groupName) {
            $groupName = $this->persistent->groupName;
        }

        try {
            $sql = "SELECT Groups.*, Users.user_name,  GroupMember.member_status FROM Groups "
                    . "JOIN Users on Groups.owner_id = Users.user_id "
                    . "LEFT JOIN GroupMember on GroupMember.group_id = Groups.group_id AND GroupMember.member_id = $userId "
                    . "WHERE Groups.group_id != $groupId ";
            $sql = (isset($groupName)) ? ($sql . "AND group_name LIKE '%$groupName%'" ) : $sql;

            $query = $this->modelsManager->createQuery($sql);
            $contents = $query->execute();

            $paginator = new Paginator(array(
                "data" => $contents,
                "limit" => 10,
                "page" => $numberPage
            ));

            $this->view->page = $paginator->getPaginate();
        } catch (Exception $ex) {
            $this->logger->error("Search group exception: " . $ex->getMessage());
        }
    }

    public function accessAction($groupId = 0) {

        $numberPage = 1;

        if (!$this->request->isPost()) {
            $numberPage = $this->request->getQuery("page", "int");
            if ($numberPage <= 0) {
                $numberPage = 1;
            }
        }

        try {

            $contents = $this->modelsManager->createBuilder()
                    ->columns(array('Contents.*', 'Users.user_name'))
                    ->from('Contents')
                    ->join('SharedContents', 'Contents.content_id = SharedContents.content_id')
                    ->join('Users', 'Users.user_id = Contents.owner_id')
                    ->where("content_status = 'shared'")
                    ->andWhere('group_id = :group_id:', array('group_id' => $groupId))
                    ->orderBy('Contents.content_name')
                    ->getQuery()
                    ->execute();

            if (count($contents) == 0) {
                $this->flash->notice("The search did not find any contents");
            }
            $paginator = new Paginator(array(
                "data" => $contents,
                "limit" => 10,
                "page" => $numberPage
            ));

            $this->view->page = $paginator->getPaginate();
            // Set group id to highlight group in sidebar
            $this->view->setVar("activeId", $groupId);
        } catch (Exception $ex) {
            $this->logger->error('Access group exception: ' . $ex->getMessage());
        }
    }

}
