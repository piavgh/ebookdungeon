<?php
use Phalcon\Tag as Tag;

class IndexController extends ControllerBase
{
    public function initialize() {
        Tag::setTitle("Home");
        parent::initialize();
    }

    public function indexAction()
    {
        
    }
}

