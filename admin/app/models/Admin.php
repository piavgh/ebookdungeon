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

use Phalcon\Mvc\Model\Validator\Email as EmailValidator;

class Admin extends Phalcon\Mvc\Model
{
    public function validation()
    {
        $this->validate(new EmailValidator(array(
            'field' => 'email'
        )));
        if ($this->validationHasFailed() == true) {
            return false;
        }
    }
}
