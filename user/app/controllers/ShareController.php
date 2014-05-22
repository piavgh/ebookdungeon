<?php
use Phalcon\Tag as Tag;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Paginator\Adapter\Model as Paginator;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;

class ShareController extends ControllerBase {

    public function initialize() {
        $this->view->setTemplateAfter('backend');
        Tag::setTitle('Share');
        parent::initialize();
    }

    public function indexAction() {

        $auth = $this->session->get('auth');
        $userId = $auth['id'];
        $groupId = $auth['group_id'];

        $group_ids = array();
        $shared_content_ids = array();
        $public_content_ids = array();
        $owner_content_ids = array();
        $need_content_ids = array();

        try {
            $sql = "SELECT GroupMember.* FROM GroupMember "
                 . "WHERE GroupMember.member_id = $userId AND GroupMember.member_status = 'active'";
            $query = $this->modelsManager->createQuery($sql);
            $group_members = $query->execute();
            foreach ($group_members as $group_member) {
                $group_ids[] = $group_member->group_id;
            }

            foreach ($group_ids as $group_id) {
                $shared_contents = SharedContents::find("group_id = $group_id");
                foreach ($shared_contents as $shared_content) {
                    $shared_content_ids[] = $shared_content->content_id;
                    $need_content_ids[] = $shared_content->content_id;
                }
            }

            $numberPage = 1;

            if (!$this->request->isPost()) {
                $numberPage = $this->request->getQuery("page", "int");
                if ($numberPage <= 0) {
                    $numberPage = 1;
                }
            }

            $public_contents = Contents::find("status = 'public'");
            foreach ($public_contents as $public_content) {
                $public_content_ids[] = $public_content->content_id;
                $need_content_ids[] = $public_content->content_id;
            }

            $owner_contents = Contents::find("owner_id = $userId");
            foreach ($owner_contents as $owner_content) {
                $owner_content_ids[] = $owner_content->content_id;
                $need_content_ids[] = $owner_content->content_id;
            }

            $contents = Contents::find();

            $paginator = new Paginator(array(
                "data" => $contents,
                "limit" => 20,
                "page" => $numberPage
            ));

            $this->view->need_content_ids = $need_content_ids;
            $this->view->page = $paginator->getPaginate();
        } catch (Exception $ex) {
            $this->logger->error('Index exception: ' . $ex->getMessage());
        }
    }

    public function searchAction() {
        $auth = $this->session->get('auth');
        $userId = $auth['id'];
        $groupId = $auth['group_id'];

        $group_ids = array();
        $shared_content_ids = array();
        $public_content_ids = array();
        $owner_content_ids = array();
        $need_content_ids = array();

        try {
            $sql = "SELECT GroupMember.* FROM GroupMember "
                . "WHERE GroupMember.member_id = $userId AND GroupMember.member_status = 'active'";
            $query = $this->modelsManager->createQuery($sql);
            $group_members = $query->execute();
            foreach ($group_members as $group_member) {
                $group_ids[] = $group_member->group_id;
            }

            foreach ($group_ids as $group_id) {
                $shared_contents = SharedContents::find("group_id = $group_id");
                foreach ($shared_contents as $shared_content) {
                    $shared_content_ids[] = $shared_content->content_id;
                    $need_content_ids[] = $shared_content->content_id;
                }
            }

            $numberPage = 1;

            if (!$this->request->isPost()) {
                $numberPage = $this->request->getQuery("page", "int");
                if ($numberPage <= 0) {
                    $numberPage = 1;
                }
            } else {
                $this->persistent->search_documents = $this->request->getPost('search_documents');
            }

            if ($this->persistent->search_documents) {
                $search_documents = $this->persistent->search_documents;
            }

            $public_contents = Contents::find("status = 'public'");
            foreach ($public_contents as $public_content) {
                $public_content_ids[] = $public_content->content_id;
                $need_content_ids[] = $public_content->content_id;
            }

            $owner_contents = Contents::find("owner_id = $userId");
            foreach ($owner_contents as $owner_content) {
                $owner_content_ids[] = $owner_content->content_id;
                $need_content_ids[] = $owner_content->content_id;
            }

            $sql = "SELECT Contents.* " . "FROM Contents ";
            $sql = (isset($search_documents)) ? ($sql . "WHERE content_name LIKE '%$search_documents%'") : $sql;
            $query = $this->modelsManager->createQuery($sql);
            $contents = $query->execute();

            $paginator = new Paginator(array(
                "data" => $contents,
                "limit" => 20,
                "page" => $numberPage
            ));

            $this->view->need_content_ids = $need_content_ids;
            $this->view->page = $paginator->getPaginate();
        } catch (Exception $ex) {
            $this->logger->error('Index exception: ' . $ex->getMessage());
        }
    }
}