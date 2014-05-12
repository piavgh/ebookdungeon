<?php
use Phalcon\Tag as Tag;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Paginator\Adapter\Model as Paginator;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;

class ContentsController extends ControllerBase {

    private function _instantConvert($userName) {
        $command = "/var/www/mediacloud/conversion/instant_converter.sh \"{$userName}\"";

        // Execute the shell command
        shell_exec($command);
    }

    private function _deleteContent($content_id = 0) {
        $auth = $this->session->get('auth');
        $userId = $auth['id'];

        $result = TRUE;
        try {
            $user = Users::findFirstByuser_id($userId);
            if (!$user) {
                $this->logger->error("Delete content of user_id: $userId does not exits");
                return $result;
            }

            $content = Contents::findFirstBycontent_id($content_id);
            if (!$content) {
                $this->logger->error("Delete content: $content_id does not exits");

                return $result;
            }

            $contentSize = $content->content_size;
            // Increase user account available space
            $user->used -= $contentSize;
            $user->available = (intval($user->maximum) - intval($user->used));

            // Obtain transaction manager
            $manager = $this->transactions;

            // Request a transaction
            $transaction = $manager->get();

            // Delete shared contents
            $sharedContents = SharedContents::find("content_id=$content->content_id");
            if (count($sharedContents) > 0) {
                foreach ($sharedContents as $sharedContent) {
                    $sharedContent->setTransaction($transaction);
                    if ($sharedContent->delete() == false) {
                        $result = FALSE;
                        // Rollback the transaction
                        foreach ($sharedContent->getMesasges() as $m) {
                            $transaction->rollback($m->getMessage());
                        }
                    }
                }
            }

            // Delete conversion contents
            $conversions = Conversions::find("content_id=$content_id");
            if ($conversions) {
                foreach ($conversions as $conversion) {
                    $conversion->setTransaction($transaction);
                    if ($conversion->delete() == false) {

                        $result = FALSE;
                        // Rollback
                        foreach ($conversion->getMessages() as $message) {
                            $transaction->rollback($message->getMessage());
                        }
                    } else {
                        // Delete physical converted file
                        $convertpath = $conversion->convert_path;
                        $deleteConverted = TRUE;
                        if ($convertpath != null && is_file($convertpath)) {
                            $deleteConverted = unlink($convertpath);
                        }

                        // Rollback
                        if ($deleteConverted == FALSE) {
                            $result = FALSE;
                            $err = error_get_last();
                            $transaction->rollback($err['message']);
                        }
                    }
                }
            }

            // Delete content

            $content->setTransaction($transaction);
            if (!$content->delete()) {
                $result = FALSE;
                foreach ($content->getMessages() as $message) {
                    $transaction->rollback($message->getMessage());
                }
            }

            // Delete physical
            $filepath = $content->path;
            $success = true;
            if (is_file($filepath))
                $success = unlink($filepath);
            else
                $success = FALSE;

            if ($success == FALSE) {
                $result = FALSE;
                $err = error_get_last();
                $transaction->rollback($err['message']);
            }

            // Save account space info
            $user->setTransaction($transaction);
            if ($user->save() == FALSE) {
                $result = FALSE;
                // Rollback
                foreach ($user->getMessages() as $message) {
                    $transaction->rollback($message->getMessage());
                }
            }

            // Commit the transaction
            $transaction->commit();
        } catch (TxFailed $ex) {
            $this->logger->error("Delete content exception: " . $ex->getMessage());
        }
        return $result;
    }

    public function actionAction() {
        $request = $this->request;
        if ($request->isPost()) {
            $contents = $request->getPost("content");
            
            if ($request->hasPost("edit_content")) {
                // Redirect to edit content page
                if ($contents && count($contents) == 1) {
                    $content_id = $contents[0];
                    return $this->redirect("contents/edit/$content_id");
                }
                
            } elseif ($request->hasPost("delete_content")) {
                // Redirect to delete content page
                return $this->_deleteAction($contents);
                
            } else {
                return $this->dispatcher->forward(array(
                            "controller" => "contents",
                            "action" => "index"
                ));
            }
        } else {
            return $this->dispatcher->forward(array(
                        "controller" => "contents",
                        "action" => "index"
            ));
        }
    }

    public function initialize() {
        $this->view->setTemplateAfter('backend');
        Tag::setTitle('Contents');
        parent::initialize();
    }

    public function indexAction() {
        /*
         * Assets resource 
         */
        // Adding style sheet
        $this->assets
                ->addCss('upload_plugin/css/jquery.fileupload.css')
                ->addCss('upload_plugin/css/jquery.fileupload-ui.css');

        // Adding javascript
        $this->assets
                ->addJs('upload_plugin/js/vendor/jquery.ui.widget.js')
                ->addJs('upload_plugin/js/tmpl.min.js')
                ->addJs('upload_plugin/js/load-image.min.js')
                ->addJs('upload_plugin/js/canvas-to-blob.min.js')
                ->addJs('upload_plugin/js/jquery.blueimp-gallery.min.js')
                ->addJs('upload_plugin/js/jquery.iframe-transport.js')
                ->addJs('upload_plugin/js/jquery.fileupload.js')
                ->addJs('upload_plugin/js/jquery.fileupload-process.js')
                ->addJs('upload_plugin/js/jquery.fileupload-image.js')
                ->addJs('upload_plugin/js/jquery.fileupload-audio.js')
                ->addJs('upload_plugin/js/jquery.fileupload-video.js')
                ->addJs('upload_plugin/js/jquery.fileupload-validate.js')
                ->addJs('upload_plugin/js/jquery.fileupload-ui.js')
                ->addJs('upload_plugin/js/main.js');

        $auth = $this->session->get('auth');
        $userId = $auth['id'];
        $userName = $auth['name'];

        // Account space
        $maxSpace = $usedSpace = $availableSpace = $percent = 0;
        $user = Users::findFirst("user_id = $userId");
        if ($user) {
            $maxSpace = intval($user->maximum);
            $usedSpace = intval($user->used);
            $availableSpace = intval($user->available);
            $percent = ($maxSpace != 0) ? round((($usedSpace / $maxSpace) * 100), 2) : 0;
        }
        
        $this->view->setVar("maxSpace", $maxSpace);
        $this->view->setVar("usedSpace", $usedSpace);
        $this->view->setVar("availableSpace", $availableSpace);
        $this->view->setVar("percent", $percent);
        
        
        $dirPath = $this->config->upload_dir . $userName;
        
        try {
            $numberPage = 1;

            $query = Criteria::fromInput($this->di, "Contents", array("owner_id" => $userId));
            $this->persistent->parameters = $query->getParams();

            if (!$this->request->isPost()) {
                $numberPage = $this->request->getQuery("page", "int");
                if ($numberPage <= 0) {
                    $numberPage = 1;
                }
            }

            $parameters = $this->persistent->parameters;
            if (!$parameters) {
                $parameters = array();
            }

            $parameters["order"] = "content_name";

            $contents = Contents::find($parameters);

            $paginator = new Paginator(array(
                "data" => $contents,
                "limit" => 10,
                "page" => $numberPage
            ));

            $this->view->page = $paginator->getPaginate();
        } catch (Exception $ex) {
            $this->logger->error('Session exception: ' . $ex->getMessage());
        }
    }

    /**
     * Searches for contents
     */
    public function searchAction() {

        /*
         * Assets resource 
         */
        // Adding style sheet
        $this->assets
                ->addCss('upload_plugin/css/jquery.fileupload.css')
                ->addCss('upload_plugin/css/jquery.fileupload-ui.css');

        // Adding javascript
        $this->assets
                ->addJs('upload_plugin/js/vendor/jquery.ui.widget.js')
                ->addJs('upload_plugin/js/tmpl.min.js')
                ->addJs('upload_plugin/js/load-image.min.js')
                ->addJs('upload_plugin/js/canvas-to-blob.min.js')
                ->addJs('upload_plugin/js/jquery.blueimp-gallery.min.js')
                ->addJs('upload_plugin/js/jquery.iframe-transport.js')
                ->addJs('upload_plugin/js/jquery.fileupload.js')
                ->addJs('upload_plugin/js/jquery.fileupload-process.js')
                ->addJs('upload_plugin/js/jquery.fileupload-image.js')
                ->addJs('upload_plugin/js/jquery.fileupload-audio.js')
                ->addJs('upload_plugin/js/jquery.fileupload-video.js')
                ->addJs('upload_plugin/js/jquery.fileupload-validate.js')
                ->addJs('upload_plugin/js/jquery.fileupload-ui.js')
                ->addJs('upload_plugin/js/main.js');

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
    public function editAction($content_id = 0) {
        // Get session
        $auth = $this->session->get('auth');
        $userId = $auth['id'];
        $groupId = $auth['group_id'];

        try {
            $content = Contents::findFirstBycontent_id($content_id);
            if (!$content) {
                $this->flash->error("content was not found");

                return $this->dispatcher->forward(array(
                            "controller" => "contents",
                            "action" => "index"
                ));
            }

            $sql = 'SELECT Groups.*, Users.user_name FROM Groups '
                    . 'JOIN Users on Groups.owner_id = Users.user_id ';

            $query = $this->modelsManager->createQuery($sql);
            $groups = $query->execute(array('groupId' => $groupId));

            // Getting member list
            $memGroup = array();

            // Check if current account is a group
            if ($groupId > 0) {
                $memGroup[] = $groupId;
            }

            $groupMember = GroupMember::find("member_id = $userId AND member_status = 'active'");
            foreach ($groupMember as $grp) {
                $memGroup[] = $grp->group_id;
            }
            $this->view->memList = $memGroup;

            $this->view->content = $content;
            $this->view->content_id = $content->content_id;
            $this->view->groups = $groups;

            // Getting shared group list
            $shared_list = array();
            $sharedGroup = SharedContents::find("content_id = $content_id AND content_status='shared'");
            foreach ($sharedGroup as $group) {
                $shared_list[] = $group->group_id;
            }

            $this->view->shared_list = $shared_list;
        } catch (Exception $ex) {
            $this->logger->error('Edit content exception: ' . $ex->getMessage());
            return $this->dispatcher->forward(array(
                        "controller" => "contents",
                        "action" => "index"
            ));
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
                    $this->logger->error($message);
                }

                return $this->dispatcher->forward(array(
                            "controller" => "contents",
                            "action" => "new"
                ));
            }
        } catch (Exception $ex) {
            $this->logger->error('Create content exception: ' . $ex->getMessage());
            return $this->dispatcher->forward(array(
                        "controller" => "contents",
                        "action" => "new"
            ));
        }

        $this->flash->success("Content was created successfully");

        return $this->dispatcher->forward(array(
                    "controller" => "contents",
                    "action" => "index"
        ));
    }

    /**
     * Update a content edited
     *
     */
    public function updateAction() {
        if (!$this->request->isPost()) {
            return $this->dispatcher->forward(array(
                        "controller" => "contents",
                        "action" => "index"
            ));
        }

        $auth = $this->session->get('auth');
        $userId = $auth['id'];
        $groupId = $auth['group_id'];

        $content_id = $this->request->getPost("content_id");
        $groupList = $this->request->getPost("group");

        try {
            $isShared = FALSE;
            $isError = FALSE;
            $content = Contents::findFirst("content_id = $content_id");

            if (is_array($groupList)) {
                foreach ($groupList as $group_id) {
                    $shared = SharedContents::findFirst("content_id = $content_id AND group_id = $group_id");
                    if (!$shared) {
                        $shared = new SharedContents();
                        $shared->content_id = $content_id;
                        $shared->group_id = $group_id;
                    }
                    $shared->content_status = 'shared';

                    if ($shared->save() == FALSE) {
                        $isError = TRUE;
                        $messages = $shared->getMessages();
                        $this->flash->error($messages[0]);

                        // Logging
                        foreach ($messages as $m) {
                            $this->logger->error("Share content error: " . (string) $m);
                        }
                    } else {
                        $isShared = true;
                    }
                }
            }

            // Change content status
            $content->status = ($isShared == TRUE) ? 'shared' : 'private';

            if ($content->save() == false) {
                $isError = TRUE;
                // Logging
                foreach ($content->getMessages() as $m) {
                    $this->logger->error("Share content error: " . (string) $m);
                }
            }

            $groupIds = GroupMember::find("member_id = $userId");
            /*
             *  Getting array of not sharing group
             */
            $ungroupIds = array();
            foreach ($groupIds as $group) {
                $ungroupIds[] = $group->group_id;
            }
            // Check if current account is a group
            if ($groupId > 0) {
                $ungroupIds[] = $groupId;
            }

            // Checking unshared group
            foreach ($ungroupIds as $ungroup_id) {
                if (!is_array($groupList) || in_array($ungroup_id, $groupList) == FALSE) {
                    $shared = SharedContents::findFirst("content_id = $content_id AND group_id = $ungroup_id");
                    // Change status to private
                    if ($shared) {
                        $shared->content_status = 'private';
                        if ($shared->save() == false) {
                            $isError = TRUE;
                            // Logging
                            foreach ($shared->getMessages() as $m) {
                                $this->logger->error("Private content error: " . (string) $m);
                            }
                        }
                    }
                }
            }

            if (!$isError)
                $this->flash->success($this->config->message->success->update_content);
            else
                $this->flash->error($this->config->message->error->update_content);
        } catch (Exception $ex) {
            $this->logger->error("Share content exception: " . $ex->getMessage());
        }

        return $this->dispatcher->forward(array(
                    "controller" => "contents",
                    "action" => "edit",
                    "params" => array($content_id)
        ));
    }

    /*
     * Get convert call
     */

    public function convertAction() {
        $request = $this->request;
        if ($request->isPost()) {
            if ($request->hasPost("trigger_converter") && ($request->getPost("trigger_converter") == 1)) {
                $auth = $this->session->get('auth');
                $userName = $auth['name'];

                // Trigger the converter
                $this->_instantConvert($userName);
            }
        }
    }

    /*
     * Delete multiple contents
     */

    private function _deleteAction($contentList) {
        $result = TRUE;

        try {
            if (count($contentList) > 0) {
                foreach ($contentList as $content_id) {
                    $result = $this->_deleteContent($content_id) ? $result : FALSE;
                }
            }
        } catch (Exception $ex) {
            $this->logger->error("Delete multiple contents exception: " . $ex->getMessage());
        }

        if ($result == FALSE)
            $this->flash->error($this->config->message->error->delete_content);
        else
            $this->flash->success("Content was deleted successfully");

        return $this->dispatcher->forward(array(
                    "controller" => "contents",
                    "action" => "index",
        ));
    }

    public function uploadAction() {
        $auth = $this->session->get('auth');
        $userId = $auth['id'];
        $userName = $auth['name'];

        $dirPath = $this->config->upload_dir . $userName;
        
        try {
            // Check if the user has uploaded files
            if ($this->request->hasFiles() == true) {

                $errors = array();

                // Obtain transaction manager
                $manager = $this->transactions;

                // Request a transaction
                $transaction = $manager->get();

                // Print the real file names and sizes
                foreach ($this->request->getUploadedFiles() as $file) {

                    $fileInfo = $file->getName();
                    $fileSize = intval($file->getSize());

                    // Check user space available
                    $availSpace = 0;
                    $user = Users::findFirst("user_id = $userId");
                    if ($user) {
                        $availSpace = intval($user->available);
                    }

                    if ($fileSize <= $availSpace) {

                        // Check if content has already existed
                        $content = Contents::findFirst("owner_id = $userId AND information = '$fileInfo'");

                        if (!$content) {
                            // Create new one
                            $content = new Contents();
                            $content->status = "private";
                        }

                        // Assign transaction
                        $content->setTransaction($transaction);

                        // Update content info
                        $content->owner_id = $userId;
                        $content->file_type = $file->getType();
                        $content->path = $dirPath . '/' . $file->getName();
                        $content->information = $file->getName();
                        $content->content_size = $fileSize;

                        $info = new SplFileInfo($file->getName());
                        $extension = $info->getExtension();
                        $contentName = $info->getBasename("." . $extension);

                        $content->content_name = $contentName;
                        $content->content_extension = $extension;
                        $content->uploaded = new Phalcon\Db\RawValue('now()');
                        $content->created = time();

                        // Create new directory if not exists
                        if (!is_dir($dirPath)) {
                            if (!mkdir($dirPath)) {
                                $err = error_get_last();
                                $errors[] = $err['message'];
                                $this->logger->error($err['message']);
                                $this->flash->error($this->config->message->error->make_dir);
                            }
                        }
                        //Move the file into the application
                        if (!$file->moveTo($dirPath . '/' . $file->getName())) {
                            // Logging
                            $err = error_get_last();
                            $errors[] = $err['message'];
                            $this->logger->error($err['message']);
                        } else {

                            // Save to content table
                            if (!$content->save()) {
                                // Rollback
                                $messages = $content->getMessages();
                                $transaction->rollback((string) $messages[0]);

                                // Logging
                                foreach ($messages as $m) {
                                    $errors[] = (string) $m;
                                    $this->logger->error("Save file upload error: " . (string) $m);
                                }
                            } else {
                                // Save to conversion table
                                $conversion = new Conversions();
                                $conversion->setTransaction($transaction);
                                $conversion->content_id = $content->content_id;

                                $conversion->convert_mode = 'instant';
                                $conversion->convert_status = 0;
                                $conversion->convert_datetime = '0000-00-00 00:00:00';

                                if (!$conversion->save()) {
                                    // Logging
                                    $messages = $conversion->getMessages();
                                    // Rollback
                                    $transaction->rollback((string) $messages[0]);
                                    foreach ($messages as $m) {
                                        $errors[] = (string) $m;
                                        $this->logger->error("Save conversion error: " . (string) $m);
                                    }
                                } else {
                                    // Update user account space info
                                    $user->used += $fileSize;
                                    $user->available -= $fileSize;
                                    $user->setTransaction($transaction);

                                    if ($user->save() == FALSE) {
                                        $messages = $user->getMessages();
                                        // Rollback
                                        $transaction->rollback((string) $messages[0]);

                                        foreach ($messages as $m) {
                                            $errors[] = (string) $m;
                                            $this->logger->error("Save account space info error: " . (string) $m);
                                        }
                                    }
                                }

                                // Success transaction
                                $transaction->commit();

                                $this->flash->success($this->config->message->success->upload);
                            }
                        }
                    } else {
                        // Not enough space for uploading
                        $errors[] = "Available space is not enough";
                    }

                    // Prepare response to client
                    $content = "<response>";
                    if (!empty($errors)) {
                        $content .= "<errors>";
                        foreach ($errors as $error) {
                            $content .= "<error>";
                            $content .= $error;
                            $content .= "</error>";
                        }
                        $content .= "</errors>";
                    } else {
                        $content .= "<success>";
                        $content .= 1;
                        $content .= "</success>";
                    }
                    $content .= "</response>";

                    $response = new Phalcon\Http\Response();
                    $response->setHeader('Content-Type', 'text/xml');
                    $response->setContent($content);

                    return $response;
                }
            }
        } catch (Exception $ex) {
            $this->logger->error('Upload exception: ' . $ex->getMessage());
        }
    }

    /*
     * Display content
     * 
     * @param content_id
     */

    public function showAction($content_id = 0) {
        try {
            $content = Contents::findFirst($content_id);
            if ($content) {
                $user = Users::findFirst("user_id = " . $content->owner_id);
                $filePath = $content->path;
                if (file_exists($filePath)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: ' . $content->file_type);
                    header('Content-Disposition: inline; filename="' . basename($content->information) . '"');
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($filePath));
                    ob_clean();
                    flush();
                    readfile($filePath);
                    exit();
                }
            } else {
                $this->flash->error("Content was not found");
                return $this->forward('contents/index');
            }
        } catch (Exception $ex) {
            $this->logger->error('Show content exception: ' . $ex->getMessage());
            return $this->forward('contents/index');
        }
    }

}
