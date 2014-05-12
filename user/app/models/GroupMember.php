<?php
class GroupMember extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $group_id;
     
    /**
     *
     * @var integer
     */
    public $member_id;
     
    /**
     *
     * @var string
     */
    public $member_status;
    
    /**
     *
     * @var string
     */
    public $action_token;
    
    /**
     * Independent Column Mapping.
     */
    public function columnMap()
    {
        return array(
            'group_id' => 'group_id', 
            'member_id' => 'member_id', 
            'member_status' => 'member_status',
            'action_token' => 'action_token'
        );
    }

}
