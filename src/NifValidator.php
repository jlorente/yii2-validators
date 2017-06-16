<?php

/**
 * @author      José Lorente <jose.lorente.martin@gmail.com>
 * @license     The MIT License (MIT)
 * @copyright   José Lorente
 * @version     1.0
 */

namespace jlorente\validators;

use Yii;
use yii\helpers\Json;
use yii\validators\RegularExpressionValidator;

/**
 * NifValidator to validate spanish NIF's and NIE's documents.
 *
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class NifValidator extends RegularExpressionValidator {

    /**
     * Control digit table.
     * 
     * @var string
     */
    protected static $table = [
        'T', 'R', 'W', 'A', 'G', 'M', 'Y', 'F', 'P', 'D', 'X', 'B', 'N', 'J', 'Z', 'S', 'Q', 'V', 'H', 'L', 'C', 'K', 'E'
    ];

    /**
     * Allowed leading letters in NIE document.
     * 
     * @var string
     */
    protected static $nieLeadingLetters = [
        'X', 'Y', 'Z'
    ];

    /**
     * Validates the NIF with letter.
     * 
     * @var boolean
     */
    public $withDC = true;

    /**
     * If withDC is false and setDC is true, the NIF will be returned 
     * with the corresponding letter.
     * 
     * @var boolean
     */
    public $setDC = false;

    /**
     * Validates also NIE.
     * 
     * @var boolean 
     */
    public $allowNie = false;

    /**
     *
     * @var boolean
     */
    public $caseInsensitive = true;

    /**
     *
     * @var type 
     */
    public $messages = [];

    /**
     *
     * @var string
     */
    protected $_value;

    /**
     * @inheritdoc
     */
    public function init() {
        $this->ensureValidators();
        $this->ensureMessages();
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute) {
        $result = $this->validateValue($model->$attribute);
        if (!empty($result)) {
            $this->addError($model, $attribute, $result[0], $result[1]);
        }
        $model->$attribute = $this->_value;
    }

    /**
     * @inheritdoc
     */
    public function validateValue($value) {
        $valid = parent::validateValue($value);
        if ($valid !== null) {
            return [$this->messages['patternError'], []];
        }
        $split = [];
        preg_match_all('/[0-9]+|[A-Z]+/' . ($this->caseInsensitive === true ? 'i' : ''), $value, $split);
        $split = $split[0];
        $nSplit = count($split);
        $numberPosition = $nSplit - ($this->withDC === true ? 2 : 1);
        $number = preg_replace('/^[0]+/', '', ($nSplit > 2 ? array_search($split[0], static::$nieLeadingLetters) : '') . $split[$numberPosition]);
        $letter = static::$table[$number % 23];
        if ($this->withDC === true && $letter !== $split[$numberPosition + 1]) {
            return [$this->messages['controlDigitError'], []];
        } elseif ($this->setDC === true) {
            $split[$numberPosition + 1] = $letter;
        }
        $this->_value = implode('', $split);
        return;
    }

    /**
     * @inheritdoc
     */
    public function clientValidateAttribute($model, $attribute, $view) {
        $table = Json::encode(static::$table);
        $nieDigits = Json::encode(static::$nieLeadingLetters);
        $errorMessage = Json::encode($this->messages['controlDigitError']);
        $js = parent::clientValidateAttribute($model, $attribute, $view);
        if ($this->withDC === true) {
            $js .= <<<JS
(function() {
    if (value.length) {
        var split, nSplit, number, cLetter;
        split = value.match(/(\d+|[^\d]+)/g);
        nSplit = split.length;
        number = ((nSplit > 2 ? $.inArray(split[0], {$nieDigits}) : '') + '' + split[nSplit - 2]).replace(/^0+/, '');
        cLetter = {$table}[number % 23];
        if (cLetter !== split[split.length - 1]) {
            yii.validation.addMessage(messages, $errorMessage, value);
        }
    }
})();
JS;
        }
        return $js;
    }

    /**
     * Ensures the error messages of the validator.
     */
    protected function ensureMessages() {
        $this->messages = array_merge([
            'controlDigitError' => Yii::t('yii', 'The letter don\'t correspond to the number.')
            , 'patternError' => Yii::t('yii', 'The valid format for NIF is 8 digits followed by a valid letter and for NIE a letter followed by 7 digits and an ending letter.')
                ], $this->messages);
        $this->message = $this->messages['patternError'];
    }

    /**
     * Ensures the format of the validator.
     */
    protected function ensureValidators() {
        $std = '^[0-9]{8}';
        $nie = '^[XYZ]{1}[0-9]{7}';
        if ($this->withDC) {
            $letters = implode('', static::$table);
            $std .= '[' . $letters . ']{1}$';
            $nie .= '[' . $letters . ']{1}$';
        } else {
            $std .= '$';
            $nie .= '$';
        }
        if ($this->allowNie === true) {
            $std .= '|' . $nie;
        }
        $this->pattern = '/' . $std . '/' . ($this->caseInsensitive === true ? 'i' : '');
    }

}
