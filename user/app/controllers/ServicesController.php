<?php
use Phalcon\Tag as Tag;

class ServicesController extends ControllerBase
{
    public function initialize() {
        Tag::setTitle("Services");
        parent::initialize();
    }

    public function indexAction()
    {

    }
}