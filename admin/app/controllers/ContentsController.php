<?php

//====================================================================
// Copyright 2012 - 2014 Pacific NW Investments, Ltd. All Rights Reserved.
//
// This software, in source or compiled form, is confidential and proprietary
// information and is protected by Canadian copyright laws and
// international treaty provisions.
//
// The intellectual and technical concepts contained herein are proprietary
// to Pacific NW Investments, Ltd. and may be covered by Canadian and
// ForeignPatents, patents in process, and are protected by trade secret
// or copyright law.  Use and/or duplication, is forbidden without written permission
// from Pacific NW Investments, Ltd.
//====================================================================

use Phalcon\Tag as Tag;
use Phalcon\Flash as Flash;
use Phalcon\Session as Session;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Date;
use Phalcon\Forms\Element\Textarea;
use Phalcon\Forms\Element\Select;
class ContentsController extends ControllerBase {
	
	private function _deleteContent($content_id = 0) {
		$result = true;
		try {
			$content = Contents::findFirstBycontent_id($content_id);
			if (!$content) {
				$this->logger->error("Delete content: $content_id does not exits");
			
				return $result;
			}
			
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
					}
				}
			}
			
			// Add to removed_contents table
			$removed_content = new RemovedContents ();
			$removed_content->content_id = $content->content_id;
			$removed_content->owner_id = $content->owner_id;
			$removed_content->file_type = $content->file_type;
			$removed_content->path = $content->path;
			$removed_content->information = $content->information;
			$removed_content->content_name = $content->content_name;
			$removed_content->content_size = $content->content_size;
			$removed_content->content_extension = $content->content_extension;
			$removed_content->created = $content->created;
			$removed_content->deleted = new Phalcon\Db\RawValue ( 'now()' );
				
			if (! $removed_content->save ()) {
				$result = FALSE;
				foreach ( $removed_content->getMessages () as $message ) {
					$this->flash->error ( ( string ) $message );
					$this->logger->error ( ( string ) $message );
					$transaction->rollback($message->getMessage());
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
			
// 			// Delete physical
// 			$filepath = $content->path;
// 			$success = true;
// 			if (is_file($filepath))
// 				$success = unlink($filepath);
// 			else
// 				$success = FALSE;
			
// 			if ($success == FALSE) {
// 				$result = FALSE;
// 				$err = error_get_last();
// 				$transaction->rollback($err['message']);
// 			}
			
			// Commit the transaction
			$transaction->commit();
		} catch (TxFailed $ex) {
            $this->logger->error("Delete content exception: " . $ex->getMessage());
        }
        return $result;
	}

	public function initialize() {
		$this->view->setTemplateAfter ( 'main' );
		Tag::setTitle ( 'Manage Contents' );
		parent::initialize ();
	}

	protected function getForm($entity = null, $edit = false) {
		$form = new Form ( $entity );
		
		if (! $edit) {
			$form->add ( new Text ( "content_id", array (
					"size" => 10,
					"maxlength" => 10 
			) ) );
		} else {
			$form->add ( new Hidden ( "content_id" ) );
		}
		
		$form->add ( new Text ( "owner_id", array (
				"size" => 10,
				"maxlength" => 10 
		) ) );
		
		$form->add ( new Text ( "path", array (
				"size" => 30,
				"maxlength" => 100 
		) ) );
		
		$form->add ( new Textarea ( "information", array (
				"size" => 30,
				"maxlength" => 200 
		) ) );
		
		$form->add ( new Text ( "content_name", array (
				"size" => 20,
				"maxlength" => 40 
		) ) );

		$form->add(new Select("status", array(
    			'P' => 'Private',
    			'S' => 'Share'
		) ) );
		
		return $form;
	}

	public function indexAction() {
		
		// Get session info
		$admin_auth = $this->session->get ( 'admin_auth' );
		
		// Query the active user
		$admin = Admin::findFirst ( $admin_auth ['id'] );
		if ($admin == false) {
			$this->_forward ( 'index/index' );
		}
		
		$numberPage = 1;
		if ($this->request->isPost ()) {
			$query = Criteria::fromInput ( $this->di, "Contents", $_POST );
			$this->persistent->searchParams = $query->getParams ();
		} else {
			$numberPage = $this->request->getQuery ( "page", "int" );
			if ($numberPage <= 0) {
				$numberPage = 1;
			}
		}
		
// 		$contents = Contents::find ();
		$sql = 'SELECT Contents.*, Users.user_name FROM Contents '
				. 'JOIN  Users on Contents.owner_id = Users.user_id';
		$query = $this->modelsManager->createQuery($sql);
		$contents = $query->execute();
		
		$paginator = new Phalcon\Paginator\Adapter\Model ( array (
				"data" => $contents,
				"limit" => 10,
				"page" => $numberPage 
		) );
		$page = $paginator->getPaginate ();
		
		$this->view->setVar ( "page", $page );
		$this->view->setVar ( "contents", $contents );
	}

	public function searchAction() {
		$numberPage = 1;
		if ($this->request->isPost ()) {
			$query = Criteria::fromInput ( $this->di, "Contents", $_POST );
			$this->persistent->searchParams = $query->getParams ();
		} else {
			$numberPage = $this->request->getQuery ( "page", "int" );
			if ($numberPage <= 0) {
				$numberPage = 1;
			}
		}
		
		$parameters = array ();
		if ($this->persistent->searchParams) {
			$parameters = $this->persistent->searchParams;
		}
		
		$contents = Contents::find ( $parameters );
		if (count ( $contents ) == 0) {
			$this->flash->notice ( "The search did not find any contents" );
			return $this->forward ( "contents/index" );
		}
		
		$paginator = new Phalcon\Paginator\Adapter\Model ( array (
				"data" => $contents,
				"limit" => 10,
				"page" => $numberPage 
		) );
		$page = $paginator->getPaginate ();
		
		$this->view->setVar ( "page", $page );
		$this->view->setVar ( "contens", $contents );
	}

	/**
	 * Edit content status and filename
	 */
	public function editAction($content_id = 0) {
		
		try {
			$content = Contents::findFirstBycontent_id($content_id);
			if (!$content) {
				$this->flash->error("Content was not found");
				return $this->forward("contents/index");
			}
			
			$userId = $content->owner_id;
			$groupId = Groups::findFirstByowner_id($userId);
			
			if (!$groupId) {
				$groupId = 0;
			}
			
			$sql = 'SELECT Groups.*, Users.user_name FROM Groups '
					. 'JOIN Users on Groups.owner_id = Users.user_id ';
// 					. 'WHERE Groups.group_id != :groupId: ';
			
			$query = $this->modelsManager->createQuery($sql);
// 			$groups = $query->execute(array('groupId' => $groupId));
			$groups = $query->execute();
			
			// Getting member list
			$memGroup = array();
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
            return $this->forward("contents/index");
        }
	}

/**
     * Update a content edited
     *
     */
    public function updateAction() {
        if (!$this->request->isPost()) {
            return $this->forward("contents/index");
        }

        $content_id = $this->request->getPost("content_id");
        $groupList = $this->request->getPost("group");
        
        $content = Contents::findFirst("content_id = $content_id");
        
        $userId = $content->owner_id;

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
            // Checking unshared group
            foreach ($groupIds as $group) {
                $ungroup_id = $group->group_id;
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
	
/**
     * Delete a content
     *
     * @param string $content_id
     */
    public function deleteAction($content_id) {

        $result = $this->_deleteContent($content_id);
        if ($result == FALSE)
            $this->flash->error("Error has occured. Content cannot be deleted");
        else
            $this->flash->success("Content was deleted successfully");

        return $this->forward("contents/index");
    }

	public function deleteselectAction() {
		if (!$this->request->isPost()) {
			return $this->forward("contents/index");
		}
		
		$contentList = $this->request->getPost("item");
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
			$this->flash->error("Error has occured. Content cannot be deleted");
		else
			$this->flash->success("Content was deleted successfully");
		
		return $this->forward("contents/index");
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
