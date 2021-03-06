<?php

/**
 * @author      José Lorente <jose.lorente.martin@gmail.com>
 * @license     The MIT License (MIT)
 * @copyright   José Lorente
 * @version     1.0
 */

namespace jlorente\validators;

use yii\validators\RegularExpressionValidator;

/**
 * ColorValidator validates that the attribute value matches a valid hexadecimal color.
 * You may invert the validation logic with help of the {@link not} property (available since 1.1.5).
 *
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class ColorValidator extends RegularExpressionValidator {

    /**
     * Pattern for color validation
     * 
     * @var string 
     */
    public $pattern = '/^#[a-f0-9]{6}$/';

    /**
     * Returns a random color.
     * 
     * @return string
     */
    public static function randColor() {
        $c = '#';
        $i = 0;
        while ($i++ < 3) {
            $c .= str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
        }
        return $c;
    }

}
