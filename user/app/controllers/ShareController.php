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
}