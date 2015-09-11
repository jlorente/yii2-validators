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
 * NameValidator validates that the attribute value matches to the specified 
 * pattern regular expression.
 *
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class NameValidator extends RegularExpressionValidator {

    /**
     * @inheritdoc
     */
    public function init() {
        $this->pattern = '/^[a-zA-ZÀ-ÖØ-öø-ÿ]+(([.\'ªº]{1}[\s]?|[\s\-]{1})[a-zA-ZÀ-ÖØ-öø-ÿ]+)*[.ªº]?$/u';

        if ($this->message === null) {
            $this->message = Yii::t('validator', "A name can consist of Latin alphabetic characters. It can contain points, apostrophes ['] and ordinals [ºª] as terminators of words, and blank spaces [ ] or dashes [-] as separator characters. A name can not contain more than one successive separator character.");
        }
        parent::init();
    }

}
