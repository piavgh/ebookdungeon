<?php
use Phalcon\Tag as Tag;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Paginator\Adapter\Model as Paginator;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;

class ContentsController extends ControllerBase
{
    private function read_file_docx($filename)
    {
        $striped_content = '';
        $content = '';
        if (!$filename || !file_exists($filename))
            return false;
        $zip = zip_open($filename);
        if (!$zip || is_numeric($zip))
            return false;
        while ($zip_entry = zip_read($zip)) {
            if (zip_entry_open($zip, $zip_entry) == FALSE)
                continue;
            if (zip_entry_name($zip_entry) != "word/document.xml")
                continue;
            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
            zip_entry_close($zip_entry);
        }
        // end while
        zip_close($zip);
        //echo $content;
        //echo "<hr>";
        //file_put_contents('1.xml', $content);
        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        $striped_content = strip_tags($content);
        return $striped_content;
    }


    private function _deleteContent($content_id = 0)
    {
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

    public function actionAction()
    {
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

    public function initialize()
    {
        $this->view->setTemplateAfter('backend');
        Tag::setTitle('Contents');
        parent::initialize();
    }

    public function indexAction()
    {
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

            $parameters["order"] = "content_id";

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
    public function searchAction()
    {
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

            $sql = "SELECT Contents.* FROM Contents "
                . "WHERE Contents.owner_id = $userId ";
            $sql = (isset($search_documents)) ? ($sql . "AND content_name LIKE '%$search_documents%'") : $sql;

            $query = $this->modelsManager->createQuery($sql);
            $contents = $query->execute();

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
     * Edits a content
     *
     * @param int $content_id
     */
    public function editAction($content_id = 0)
    {
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

    public function makecontentpublicAction($content_id = 0)
    {
        try {
            $content = Contents::findFirstBycontent_id($content_id);
            if (!$content) {
                $this->flash->error("content was not found");

                return $this->dispatcher->forward(array(
                    "controller" => "contents",
                    "action" => "index"
                ));
            }

            $content->status = 'public';
            if (!$content->save()) {
                $messages = $content->getMessages();
                $this->flash->error((string)$messages[0]);
                // Logging
                foreach ($messages as $m) {
                    $this->logger->error("Make content public error: " . (string)$m);
                }
                return $this->forward("contents/index");
            }
            $this->flash->success("This content is now public");
            return $this->forward("contents/index");
        } catch (Exception $e) {
            $this->logger->error('Make content public exception: ' . $e->getMessage());
            return $this->dispatcher->forward(array(
                "controller" => "contents",
                "action" => "index"
            ));
        }
    }

    public function makecontentprivateAction($content_id = 0)
    {
        try {
            $content = Contents::findFirstBycontent_id($content_id);
            if (!$content) {
                $this->flash->error("content was not found");

                return $this->dispatcher->forward(array(
                    "controller" => "contents",
                    "action" => "index"
                ));
            }

            $content->status = 'private';
            if (!$content->save()) {
                $messages = $content->getMessages();
                $this->flash->error((string)$messages[0]);
                // Logging
                foreach ($messages as $m) {
                    $this->logger->error("Make content private error: " . (string)$m);
                }
                return $this->forward("contents/index");
            }
            $this->flash->success("This content is now private");
            return $this->forward("contents/index");
        } catch (Exception $e) {
            $this->logger->error('Make content public exception: ' . $e->getMessage());
            return $this->dispatcher->forward(array(
                "controller" => "contents",
                "action" => "index"
            ));
        }
    }

    /**
     * Creates a new document
     */
    public function createAction()
    {

    }

    /**
     * Save new document
     */
    public function saveAction()
    {
        if (!$this->request->isPost()) {
            return $this->dispatcher->forward(array(
                "controller" => "contents",
                "action" => "create"
            ));
        }

        $auth = $this->session->get('auth');
        $userId = $auth['id'];
        $userName = $auth['name'];

        $dirPath = $this->config->upload_dir . $userName . '/';

        // Load the files we need:
        require_once 'phpword/PHPWord.php';
        require_once 'simplehtmldom/simple_html_dom.php';
        require_once 'htmltodocx_converter/h2d_htmlconverter.php';
        require_once 'example_files/styles.inc';

        // Functions to support this example.
        require_once 'documentation/support_functions.inc';

        // HTML fragment we want to parse:
        //$html = file_get_contents('example_files/example_html.html');
        $html = $this->request->getPost("content");
        // $html = file_get_contents('test/table.html');

        // New Word Document:
        $phpword_object = new PHPWord();
        $section = $phpword_object->createSection();

        // HTML Dom object:
        $html_dom = new simple_html_dom();
        $html_dom->load('<html><body>' . $html . '</body></html>');
        // Note, we needed to nest the html in a couple of dummy elements.

        // Create the dom array of elements which we are going to work on:
        $html_dom_array = $html_dom->find('html', 0)->children();

        // We need this for setting base_root and base_path in the initial_state array
        // (below). We are using a function here (derived from Drupal) to create these
        // paths automatically - you may want to do something different in your
        // implementation. This function is in the included file
        // documentation/support_functions.inc.
        $paths = htmltodocx_paths();

        // Provide some initial settings:
        $initial_state = array(
            // Required parameters:
            'phpword_object' => &$phpword_object, // Must be passed by reference.
            // 'base_root' => 'http://test.local', // Required for link elements - change it to your domain.
            // 'base_path' => '/htmltodocx/documentation/', // Path from base_root to whatever url your links are relative to.
            'base_root' => $paths['base_root'],
            'base_path' => $paths['base_path'],
            // Optional parameters - showing the defaults if you don't set anything:
            'current_style' => array('size' => '11'), // The PHPWord style on the top element - may be inherited by descendent elements.
            'parents' => array(0 => 'body'), // Our parent is body.
            'list_depth' => 0, // This is the current depth of any current list.
            'context' => 'section', // Possible values - section, footer or header.
            'pseudo_list' => TRUE, // NOTE: Word lists not yet supported (TRUE is the only option at present).
            'pseudo_list_indicator_font_name' => 'Wingdings', // Bullet indicator font.
            'pseudo_list_indicator_font_size' => '7', // Bullet indicator size.
            'pseudo_list_indicator_character' => 'l ', // Gives a circle bullet point with wingdings.
            'table_allowed' => TRUE, // Note, if you are adding this html into a PHPWord table you should set this to FALSE: tables cannot be nested in PHPWord.
            'treat_div_as_paragraph' => TRUE, // If set to TRUE, each new div will trigger a new line in the Word document.

            // Optional - no default:
            'style_sheet' => htmltodocx_styles_example(), // This is an array (the "style sheet") - returned by htmltodocx_styles_example() here (in styles.inc) - see this function for an example of how to construct this array.
        );

        // Convert the HTML and put it into the PHPWord object
        htmltodocx_insert_html($section, $html_dom_array[0]->nodes, $initial_state);

        // Clear the HTML dom object:
        $html_dom->clear();
        unset($html_dom);

        // Save File
        $h2d_file_uri = tempnam('', 'htd');
        $objWriter = PHPWord_IOFactory::createWriter($phpword_object, 'Word2007');
        $objWriter->save($h2d_file_uri);

        // Create new content in contents table
        $content = new Contents();
        $content->owner_id = $userId;
        $content->file_type = 'application/vnd.openxmlformats-officedoc';
        $content->uploaded = new Phalcon\Db\RawValue('now()');
        $content->created = new Phalcon\Db\RawValue('now()');
        $content->content_name = 'Untitled' . rand(1, 1000);
        $content->information = $content->content_name . '.docx';
        $content->path = $dirPath . $content->information;
        $content->content_size = filesize($h2d_file_uri);
        $content->content_extension = 'docx';
        $content->status = 'private';

        // Create new directory if not exists
        if (!is_dir($dirPath)) {
            if (!mkdir($dirPath)) {
                $err = error_get_last();
                $errors[] = $err['message'];
                $this->logger->error($err['message']);
                $this->flash->error($this->config->message->error->make_dir);
            }
        }

        if (!$content->save()) {
            $messages = $content->getMessages();
            // Logging
            foreach ($messages as $m) {
                $errors[] = (string)$m;
                $this->logger->error("Save file upload error: " . (string)$m);
            }
        }
        //var_dump($dirPath . $contentName); die;
        rename($h2d_file_uri, ($dirPath . $content->information));

//        // Download the file:
//        header('Content-Description: File Transfer');
//        header('Content-Type: application/octet-stream');
//        header('Content-Disposition: attachment; filename=' . $h2d_file_uri . '.docx');
//        header('Content-Transfer-Encoding: binary');
//        header('Expires: 0');
//        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
//        header('Pragma: public');
//        header('Content-Length: ' . filesize($h2d_file_uri));
//        ob_clean();
//        flush();
//        $status = readfile($h2d_file_uri);

//        unlink($h2d_file_uri);
//        exit;
        $this->flash->success("Your document is created successfully");
        return $this->forward("contents/index");
    }

    /**
     * Update a content edited
     *
     */
    public function updateAction()
    {
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
                            $this->logger->error("Share content error: " . (string)$m);
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
                    $this->logger->error("Share content error: " . (string)$m);
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
                                $this->logger->error("Private content error: " . (string)$m);
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

    public function convertAction()
    {
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

    private function _deleteAction($contentList)
    {
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

    public function uploadAction()
    {
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
                        $content->created = new Phalcon\Db\RawValue('now()');

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
                                $transaction->rollback((string)$messages[0]);

                                // Logging
                                foreach ($messages as $m) {
                                    $errors[] = (string)$m;
                                    $this->logger->error("Save file upload error: " . (string)$m);
                                }
                            } else {
                                // Update user account space info
                                $user->used += $fileSize;
                                $user->available -= $fileSize;
                                $user->setTransaction($transaction);

                                if ($user->save() == FALSE) {
                                    $messages = $user->getMessages();
                                    // Rollback
                                    $transaction->rollback((string)$messages[0]);

                                    foreach ($messages as $m) {
                                        $errors[] = (string)$m;
                                        $this->logger->error("Save account space info error: " . (string)$m);
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

    public function showAction($content_id = 0)
    {
        $auth = $this->session->get('auth');
        $userId = $auth['id'];
        $userName = $auth['name'];
        try {
            $content = Contents::findFirst($content_id);

            if ($content) {
                if ($content->status == 'private' && $content->owner_id != $userId) {
                    $this->flash->error("You don't have the right to view this document!");
                    return $this->forward("contents/index");
                }
                if ($content->content_extension == 'docx') {
                    $user = Users::findFirst("user_id = " . $content->owner_id);
                    $filePath = $content->path;
                    if (file_exists($filePath)) {
                        $content = $this->read_file_docx($filePath);
                        if ($content !== false) {
                            echo '<div style="width: 90%; background-color: #ffffff">';
                            echo nl2br($content);
                            echo '</div>';
                        } else {
                            echo 'Couldn\'t the file. Please check that file.';
                        }
                    }
                } elseif ($content->content_extension == 'xlsx' || $content->content_extension == 'xls') {

                    $user = Users::findFirst("user_id = " . $content->owner_id);
                    $filePath = $content->path;
                    include 'Classes/PHPExcel/IOFactory.php';

                    $inputFileName = $filePath;

                    //  Read your Excel workbook
                    try {
                        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
                        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                        $objPHPExcel = $objReader->load($inputFileName);
                    } catch (Exception $e) {
                        die('Error loading file "' . pathinfo($inputFileName, PATHINFO_BASENAME)
                            . '": ' . $e->getMessage());
                    }

                    //  Get worksheet dimensions
                    $sheet = $objPHPExcel->getSheet(0);
                    $highestRow = $sheet->getHighestRow();
                    $highestColumn = $sheet->getHighestColumn();

                    //  Loop through each row of the worksheet in turn
                    for ($row = 1; $row <= $highestRow; $row++) {
                        //  Read a row of data into an array
                        $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                            NULL, TRUE, FALSE);
                        foreach($rowData[0] as $k=>$v)
                            echo "Row: ".$row."- Col: ".($k+1)." = ".$v."<br />";
                    }
                } else {
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

    public function downloadAction($content_id = 0)
    {
        try {
            $content = Contents::findFirst($content_id);
            if ($content) {
                $user = Users::findFirst("user_id = " . $content->owner_id);
                $filePath = $content->path;
                if (file_exists($filePath)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
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
            $this->logger->error('Download content exception: ' . $ex->getMessage());
            return $this->forward('contents/index');
        }
    }
}
