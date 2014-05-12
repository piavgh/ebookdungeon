<?php
class ContactusController extends ControllerBase {

    public function initialize() {
        $this->view->setTemplateAfter('main');
        Phalcon\Tag::setTitle('Contact us');
        parent::initialize();
    }

    public function indexAction() {
        
    }

    public function sendAction() {
        if ($this->request->isPost() == true) {

            $name = $this->request->getPost('name', array('striptags', 'string'));
            $email = $this->request->getPost('email', 'email');
            $comments = $this->request->getPost('comments', array('striptags', 'string'));

            try {
                if ($this->mail->sendMail($this->config->mail->to, "Feedback", $comments))
                    $this->flash->success('Thanks for you feedback!');
                else
                    $this->flash->error('Error occur! Please retry later!');
            } catch (Exception $ex) {
                $this->logger->error("Contact us exception: " . $ex->getMessage());
            }
        }
        return $this->forward('contactus/index');
    }

}
