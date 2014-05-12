<?php
use Phalcon\Mvc\Controller;

class ControllerBase extends Controller
{
    protected function initialize()
    {
        Phalcon\Tag::prependTitle('Ebook Dungeon | ');
    }

    protected function forward($uri){
    	$uriParts = explode('/', $uri);
    	return $this->dispatcher->forward(
    		array(
    			'controller' => $uriParts[0], 
    			'action' => $uriParts[1]
    		)
    	);
    }
    
    protected  function redirect($uri){
        $response = new \Phalcon\Http\Response();
        return $response->redirect($uri);
    }
}
