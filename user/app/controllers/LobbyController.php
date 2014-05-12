<?php
use Phalcon\Tag as Tag;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Paginator\Adapter\Model as Paginator;

class LobbyController extends ControllerBase {

    public function initialize() {
        
        $this->view->setTemplateAfter('backend');
        Tag::setTitle('Lobby');
        parent::initialize();
        
    }

    public function indexAction() {
        
        $numberPage = 1;
        $auth = $this->session->get('auth');
        $userId = $auth['id'];
        $groupId = $auth['group_id'];
        
        // Check valid group account
        if (intval($groupId <= 0)) return $this->redirect ("index");

        if (!$this->request->isPost()) {
            $numberPage = $this->request->getQuery("page", "int");
            if ($numberPage <= 0) {
                $numberPage = 1;
            }
        }

        try {
            $contents = $this->modelsManager->createBuilder()
                    ->columns("Contents.*, Users.user_name")
                    ->from('Contents')
                    ->join('SharedContents', 'Contents.content_id = SharedContents.content_id')
                    ->join('Users', 'Contents.owner_id = Users.user_id')
                    ->where("content_status = 'shared'")
                    ->andWhere('group_id = :group_id:', array('group_id' => $groupId))
                    ->orderBy('Contents.content_name')
                    ->getQuery()
                    ->execute();

            $paginator = new Paginator(array(
                "data" => $contents,
                "limit" => 10,
                "page" => $numberPage
            ));

            $this->view->page = $paginator->getPaginate();
        } catch (Exception $ex) {
            $this->logger->error('Lobby contents exception: ' . $ex->getMessage());
        }
    }

    /**
     * Searches for contents
     */
    public function searchAction() {
        
        $numberPage = 1;
        if ($this->request->isPost()) {
            $query = Criteria::fromInput($this->di, "Contents", $_POST);
            $this->persistent->parameters = $query->getParams();
        } else {
            $numberPage = $this->request->getQuery("page", "int");
            if ($numberPage <= 0) {
                $numberPage = 1;
            }
        }

        $parameters = $this->persistent->parameters;
        if (!is_array($parameters)) {
            $parameters = array();
        }
        $parameters["order"] = "content_name";

        $contents = Contents::find($parameters);
        if (count($contents) == 0) {
            return $this->dispatcher->forward(array(
                        "controller" => "contents",
                        "action" => "index"
            ));
        }

        $paginator = new Paginator(array(
            "data" => $contents,
            "limit" => 10,
            "page" => $numberPage
        ));

        $this->view->page = $paginator->getPaginate();
    }

    /**
     * Edits a content
     *
     * @param string $content_id
     */
    public function editAction($content_id) {
        
        if (!$this->request->isPost()) {

            $content = Contents::findFirstBycontent_id($content_id);
            if (!$content) {
                $this->flash->error("content was not found");
                // Logging
                $this->logger->error("Edit content: $content_id doesn't exit");

                return $this->dispatcher->forward(array(
                            "controller" => "contents",
                            "action" => "index"
                ));
            }

            $this->view->content_id = $content->content_id;

            $this->tag->setDefault("content_id", $content->content_id);
            $this->tag->setDefault("owner_id", $content->owner_id);
            $this->tag->setDefault("file_type", $content->file_type);
            $this->tag->setDefault("path", $content->path);
            $this->tag->setDefault("information", $content->information);
            $this->tag->setDefault("content_name", $content->content_name);
            $this->tag->setDefault("content_size", $content->content_size);
            $this->tag->setDefault("content_extension", $content->content_extension);
            $this->tag->setDefault("status", $content->status);
            $this->tag->setDefault("created", $content->created);
            $this->tag->setDefault("uploaded", $content->uploaded);
        }
    }

    /**
     * Creates a new content
     */
    public function createAction() {
        
        if (!$this->request->isPost()) {
            return $this->dispatcher->forward(array(
                        "controller" => "contents",
                        "action" => "index"
            ));
        }

        $content = new Contents();

        $content->owner_id = $this->request->getPost("owner_id");
        $content->file_type = $this->request->getPost("file_type");
        $content->path = $this->request->getPost("path");
        $content->information = $this->request->getPost("information");
        $content->content_name = $this->request->getPost("content_name");
        $content->content_size = $this->request->getPost("content_size");
        $content->content_extension = $this->request->getPost("content_extension");
        $content->status = $this->request->getPost("status");
        $content->created = $this->request->getPost("created");
        $content->uploaded = $this->request->getPost("uploaded");

        try {
            if (!$content->save()) {
                foreach ($content->getMessages() as $message) {
                    $this->flash->error($message);
                    // Logging
                    $this->logger->error("Lobby save content: " . (string)$message);
                }

                return $this->dispatcher->forward(array(
                            "controller" => "contents",
                            "action" => "new"
                ));
            }
        } catch (Exception $ex) {
            $this->logger->error('Create lobby content exception: ' . $ex->getMessage());
            return $this->dispatcher->forward(array(
                        "controller" => "contents",
                        "action" => "new"
            ));
        }



        $this->flash->success("content was created successfully");

        return $this->dispatcher->forward(array(
                    "controller" => "contents",
                    "action" => "index"
        ));
    }

    /**
     * Saves a content edited
     *
     */
    public function saveAction() {
        
        if (!$this->request->isPost()) {
            return $this->dispatcher->forward(array(
                        "controller" => "contents",
                        "action" => "index"
            ));
        }

        $content_id = $this->request->getPost("content_id");

        $content = Contents::findFirstBycontent_id($content_id);
        if (!$content) {
            $this->flash->error("content does not exist " . $content_id);
            // Logging
            $this->logger->error("Lobby Save content: $content_id does not exit");

            return $this->dispatcher->forward(array(
                        "controller" => "contents",
                        "action" => "index"
            ));
        }

        $content->owner_id = $this->request->getPost("owner_id");
        $content->file_type = $this->request->getPost("file_type");
        $content->path = $this->request->getPost("path");
        $content->information = $this->request->getPost("information");
        $content->content_name = $this->request->getPost("content_name");
        $content->content_size = $this->request->getPost("content_size");
        $content->content_extension = $this->request->getPost("content_extension");
        $content->status = $this->request->getPost("status");
        $content->created = $this->request->getPost("created");
        $content->uploaded = $this->request->getPost("uploaded");


        if (!$content->save()) {

            foreach ($content->getMessages() as $message) {
                $this->flash->error($message);
                $this->logger->error("Lobby save content: " . $message);
            }

            return $this->dispatcher->forward(array(
                        "controller" => "contents",
                        "action" => "edit",
                        "params" => array($content->content_id)
            ));
        }

        $this->flash->success("content was updated successfully");

        return $this->dispatcher->forward(array(
                    "controller" => "contents",
                    "action" => "index"
        ));
    }

    /**
     * Deletes a content
     *
     * @param string $content_id
     */
    public function deleteAction($content_id) {
        
        $content = Contents::findFirstBycontent_id($content_id);
        if (!$content) {
            $this->flash->error("content was not found");
            // Logging
            $this->logger->error("Delete content: $content_id does not exist");


            return $this->dispatcher->forward(array(
                        "controller" => "contents",
                        "action" => "index"
            ));
        }

        if (!$content->delete()) {

            foreach ($content->getMessages() as $message) {
                $this->flash->error($message);
                // Logging
                $this->logger->error("Lobby delete content error: " . (string)$m);
            }

            return $this->dispatcher->forward(array(
                        "controller" => "contents",
                        "action" => "search"
            ));
        }

        $this->flash->success("content was deleted successfully");

        return $this->dispatcher->forward(array(
                    "controller" => "contents",
                    "action" => "index"
        ));
    }

}
