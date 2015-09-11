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
 * AddressValidator validates that the attribute value matches to the specified.
 *
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class AddressValidator extends RegularExpressionValidator {

    /**
     * @inheritdoc
     */
    public function init() {
        $this->pattern = '/^[a-zA-ZÀ-ÖØ-öø-ÿ0-9]+(([,.\'\/ºª]{1}[\s]?|[ºª]{1}[\-]?|[\s\-]{1})[a-zA-ZÀ-ÖØ-öø-ÿ0-9]+)*\.?$/u';

        if ($this->message === null) {
            $this->message = Yii::t('validator', "An address can consist of Latin alphabetic characters. It can contain punctuation marks like points [.], commas [,], slashes [/] and apostrophes ['] followed by a blank space, ordinal [ºª] as terminator of word and blank spaces [ ] or dashes [-] as word separator characters. An address can't contain more than one successive separator character.");
        }
        parent::init();
    }

}
