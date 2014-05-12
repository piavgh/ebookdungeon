<?php
use Phalcon\Tag as Tag;

class ProductsController extends ControllerBase
{
    public function initialize() {
        Tag::setTitle("Products");
        parent::initialize();
    }

    public function indexAction()
    {

    }
}