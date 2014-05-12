<?php
use Phalcon\Mvc\Model\Validator\Email as Email;
use Phalcon\Mvc\Model\Validator\Uniqueness as UniquenessValidator;

class Users extends \Phalcon\Mvc\Model {

    /**
     *
     * @var integer
     */
    public $user_id;

    /**
     *
     * @var string
     */
    public $user_name;

    /**
     *
     * @var string
     */
    public $password_hash;

    /**
     *
     * @var string
     */
    public $verification_hash;

    /**
     *
     * @var string
     */
    public $email;

    /**
     *
     * @var string
     */
    public $first_name;

    /**
     *
     * @var string
     */
    public $last_name;

    /**
     *
     * @var string
     */
    public $phone;

    /**
     *
     * @var string
     */
    public $expiration;

    /**
     *
     * @var integer
     */
    public $extra_time;

    /**
     *
     * @var string
     */
    public $status;

    /**
     *
     * @var string
     */
    public $reset_password_hash;

    /**
     *
     * @var integer
     */
    public $maximum;
     
    /**
     *
     * @var integer
     */
    public $used;
     
    /**
     *
     * @var integer
     */
    public $available;
    
    /**
     * Validations and business logic
     */
    public function validation() {

        $this->validate(
                new Email(
                array(
            "field" => "email",
            "required" => true,
                )
                )
        );

        $this->validate(new UniquenessValidator(array(
            'field' => 'email',
            'message' => 'Sorry, The email was registered by another user'
        )));

        $this->validate(new UniquenessValidator(array(
            'field' => 'user_name',
            'message' => 'Sorry, The user name was registered by another user'
        )));

        $this->validate(new UniquenessValidator(array(
            'field' => 'phone',
            'message' => 'Sorry, The mobile phone number was registered by another user'
        )));

        if ($this->validationHasFailed() == true) {
            return false;
        }
    }

    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        return array(
            'user_id' => 'user_id',
            'user_name' => 'user_name',
            'password_hash' => 'password_hash',
            'verification_hash' => 'verification_hash',
            'email' => 'email',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone' => 'phone',
            'expiration' => 'expiration',
            'extra_time' => 'extra_time',
            'status' => 'status',
            'reset_password_hash' => 'reset_password_hash',
            'maximum' => 'maximum', 
            'used' => 'used', 
            'available' => 'available'
        );
    }

}
