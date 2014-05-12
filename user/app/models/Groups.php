<?php
use Phalcon\Mvc\Model\Validator\Email as Email;
use Phalcon\Mvc\Model\Validator\Uniqueness as UniquenessValidator;


class Groups extends \Phalcon\Mvc\Model
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
    public $owner_id;
     
    /**
     *
     * @var string
     */
    public $group_name;
    
    /**
     * Validations and business logic
     */
    public function validation() {

        $this->validate(new UniquenessValidator(array(
            'field' => 'group_name',
            'message' => 'Sorry, The group name was registered by another user'
        )));

        if ($this->validationHasFailed() == true) {
            return false;
        }
    }
}
