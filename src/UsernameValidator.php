<?php

/**
 * @author      José Lorente <jose.lorente.martin@gmail.com>
 * @license     The MIT License (MIT)
 * @copyright   José Lorente
 * @version     1.0
 */

namespace jlorente\validators;

use yii\validators\RegularExpressionValidator;
use Yii;

/**
 * UsernameValidator validates that the attribute value matches to the specified 
 * pattern regular expression.
 *
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class UsernameValidator extends RegularExpressionValidator {

    /**
     * @inheritdoc
     */
    public function init() {
        $this->pattern = '/^[a-zA-Z0-9_-]+$/u';

        if ($this->message === null) {
            $this->message = Yii::t('validator', 'A username can consist of alphabetic characters, numbers, dashes and underscores.');
        }
        parent::init();
    }

}
