<?php
use Phalcon\Tag as Tag;

class AboutusController extends ControllerBase
{
    public function initialize() {
        Tag::setTitle("About Us");
        parent::initialize();
    }

    public function indexAction()
    {

    }
}