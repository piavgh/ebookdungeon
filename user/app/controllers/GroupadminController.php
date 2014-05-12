<?php
use Phalcon\Tag as Tag;
use Phalcon\Paginator\Adapter\Model as Paginator;

class GroupadminController extends ControllerBase {

    public function initialize() {
        $this->view->setTemplateAfter('backend');
        Tag::setTitle('Group Admin');
        parent::initialize();
    }

    public function indexAction() {
        $auth = $this->session->get('auth');
        $groupId = $auth['group_id'];

        // Check valid group account
        if ($groupId <= 0) return $this->redirect ("index");

        // Set group name
        $groupName = Groups::findFirst(array(
            "conditions" => "group_id = ?0",
            "columns" => "group_name",
            "bind" => array(0 => $groupId)
        ));
        if ($groupName) $this->view->setVar ("groupName", $groupName);
        
        $numberPage = 1;

        if (!$this->request->isPost()) {
            $numberPage = $this->request->getQuery("page", "int");
            if ($numberPage <= 0) {
                $numberPage = 1;
            }
        }

        try {

            $sql = "SELECT GroupMember.*, Users.user_name "
                    . "FROM GroupMember, Users "
                    . "WHERE GroupMember.member_id = Users.user_id "
                    . "AND GroupMember.group_id = :groupId: ";
            
            $query = $this->modelsManager->createQuery($sql);
            $contents = $query->execute(array('groupId' => $groupId));

            $paginator = new Paginator(array(
                "data" => $contents,
                "limit" => 10,
                "page" => $numberPage
            ));
            $this->view->page = $paginator->getPaginate();
        } catch (Exception $exc) {
            $this->logger->error("Group admin exception: " . $exc->getMessage());
        }
    }

    /*
     * Active member's status
     * 
     * @param int $member_id
     */

    public function activeAction($member_id) {
        $auth = $this->session->get('auth');
        $groupId = $auth['group_id'];

        // Check valid group account
        if ($groupId <= 0) return $this->redirect ("index");
        
        try {
            $group_member = GroupMember::findFirst("group_id = $groupId AND member_id = $member_id");

            if ($group_member) {
                $group_member->member_status = 'active';
                if ($group_member->save()) {
                    $this->flash->success($this->config->message->success->save);
                } else {
                    $this->flash->error($this->config->message->error->change_member_status);
                    $this->logger->error("Cannot change status member_id = $member_id, group_id = $groupId");
                }
            }
        } catch (Exception $ex) {
            $this->logger->error("Active group member exception: " . $ex->getMessage());
        }

        return $this->forward("groupadmin/index");
    }

    /*
     * Reject member
     * 
     * @param int $member_id
     */

    public function rejectAction($member_id) {
        $auth = $this->session->get('auth');
        $groupId = $auth['group_id'];

        // Check valid group account
        if ($groupId <= 0) return $this->redirect ("index");
        
        try {
            $group_member = GroupMember::findFirst("group_id = $groupId AND member_id = $member_id");

            if ($group_member) {
                $group_member->member_status = 'rejected';
                if ($group_member->save()) {
                    $this->flash->success($this->config->message->success->save);
                } else {
                    $this->flash->error($this->config->message->error->change_member_status);
                    $this->logger->error("Cannot change status member_id = $member_id, group_id = $groupId");
                }
            }
        } catch (Exception $ex) {
            $this->logger->error("Reject group member exception: " . $ex->getMessage());
        }

        return $this->forward("groupadmin/index");
    }

    /**
     * Deletes a group member
     *
     * @param int $member_id
     */
    public function deleteAction($member_id) {
        $auth = $this->session->get('auth');
        $groupId = $auth['group_id'];

        // Check valid group account
        if ($groupId <= 0) return $this->redirect ("index");
        
        try {
            // Delete shared contents first
            $contentList = Contents::find("owner_id = $member_id");
            foreach ($contentList as $content) {
                $sharedContent = SharedContents::findFirst("content_id = $content->content_id AND group_id = $groupId");
                if ($sharedContent)
                    $sharedContent->delete();
            }

            // Delete member from the group
            $member = GroupMember::find(array("group_id = :group_id: AND member_id = :member_id:",
                        "bind" => array('group_id' => $groupId, 'member_id' => $member_id)
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

        return $this->dispatcher->forward(array(
                    "controller" => "groupadmin",
                    "action" => "index"
        ));
    }

}
