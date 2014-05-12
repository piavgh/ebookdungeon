<?php
class SharedContents extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $content_id;
     
    /**
     *
     * @var integer
     */
    public $group_id;
     
    /**
     *
     * @var string
     */
    public $content_status;
     
    /**
     * Independent Column Mapping.
     */
    public function columnMap()
    {
        return array(
            'content_id' => 'content_id', 
            'group_id' => 'group_id', 
            'content_status' => 'content_status'
        );
    }

}
