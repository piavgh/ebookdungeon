<?php
use Phalcon\Tag as Tag;
use Phalcon\Paginator\Adapter\Model as Paginator;

class IndexController extends ControllerBase
{
    public function initialize() {
        Tag::setTitle("Home");
        parent::initialize();
    }

    public function indexAction()
    {
        try {
            $numberPage = 1;

            if (!$this->request->isPost()) {
                $numberPage = $this->request->getQuery("page", "int");
                if ($numberPage <= 0) {
                    $numberPage = 1;
                }
            }

            $contents = Contents::find("status = 'public'");

            $paginator = new Paginator(array(
                "data" => $contents,
                "limit" => 20,
                "page" => $numberPage
            ));

            $this->view->page = $paginator->getPaginate();
        } catch (Exception $ex) {
            $this->logger->error('Index exception: ' . $ex->getMessage());
        }
    }

    public function searchAction() {
        try {
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

            $sql = "SELECT Contents.* FROM Contents " .
                "WHERE Contents.status = 'public'";
            $sql = (isset($search_documents)) ? ($sql . "AND content_name LIKE '%$search_documents%'") : $sql;
            $query = $this->modelsManager->createQuery($sql);
            $contents = $query->execute();

            $paginator = new Paginator(array(
                "data" => $contents,
                "limit" => 20,
                "page" => $numberPage
            ));

            $this->view->page = $paginator->getPaginate();
        } catch (Exception $ex) {
            $this->logger->error('Index exception: ' . $ex->getMessage());
        }
    }
}

