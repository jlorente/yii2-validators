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
 * CifValidator to validate spanish CIF's.
 *
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class CifValidator extends RegularExpressionValidator {

    protected static $organization = [
        'int' => [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'U', 'V',
        ]
        , 'char' => [
            'N', 'P', 'Q', 'R', 'S', 'W'
        ]
    ];

    /**
     * Control digit table.
     * 
     * @var string
     */
    protected static $table = [
        '0', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'
    ];

    /**
     * If true the value must be written with the control digit.
     * 
     * @var boolean
     */
    public $withDC = true;

    /**
     * If withLetter is false and setLetter is true, the CIF will be returned 
     * with the corresponding control digit.
     * 
     * @var boolean
     */
    public $setDC = false;

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
        $organization = substr($value, 0, 1);
        $number = substr($value, 1, 7);
        $sum = 0;
        //The specification talks about odds and evens but one based. So here the odds and evens vars are exchanged.
        for ($i = 0, $l = strlen($number); $i < $l; ++$i) {
            $n = $number[$i];
            if ($i % 2 === 0) {
                $n = array_sum(str_split($n * 2));
            }
            $sum += $n;
        }
        $dcNumber = 10 - ($sum % 10);
        $dc = (string) $dcNumber;
        if (array_search($organization, static::$organization['char']) !== false) {
            $dc = static::$table[$dcNumber];
        } elseif ($dcNumber === 10) {
            $dc = "0";
        }
        if ($this->withDC === true && $dc !== substr($value, -1)) {
            return [$this->messages['controlDigitError'], []];
        } elseif ($this->setDC === true) {
            $value .= $dc;
        }
        $this->_value = $value;
        return;
    }

    /**
     * @inheritdoc
     */
    public function clientValidateAttribute($model, $attribute, $view) {
        $organization = Json::encode(static::$organization);
        $table = Json::encode(static::$table);
        $errorMessage = Json::encode($this->messages['controlDigitError']);
        $js = parent::clientValidateAttribute($model, $attribute, $view);
        if ($this->withDC === true) {
            $js .= <<<JS
(function() {
    if (value.length) {
        var org, number, sum = 0, i, dcNumber, dc, tableOrg, tableDC, n, aux;
        tableOrg = $organization;
        tableDC = $table;
        org = value.substr(0, 1);
        number = value.substr(1, 7);
        for (i = 0; i < number.length; ++i) {
            n = parseInt(number[i]);
            if (i % 2 === 0) {
                n = 0;
                aux = (number[i] * 2).toString();
                for (var j in aux) {
                    n += parseInt(aux[j]);
                }
            }
            sum += n;
        }
        dcNumber = 10 - (sum % 10);
        dc = dcNumber.toString();
        if (tableOrg.char.indexOf(org) !== -1) {
            dc = tableDC[dcNumber];
        } else if (dcNumber === 10) {
            dc = "0";
        }
        if (dc !== value.substr(-1)) {
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
        $patternError = 'The valid format for CIF is a letter followed by 7 digits';
        if ($this->withDC === true) {
            $patternError .= ' and an ending control digit';
        }
        $this->messages = array_merge([
            'controlDigitError' => Yii::t('yii', 'The control digit don\'t correspond to the number')
            , 'patternError' => Yii::t('yii', $patternError)
                ], $this->messages);
        $this->message = $this->messages['patternError'];
    }

    /**
     * Ensures the format of the validator.
     */
    protected function ensureValidators() {
        $intTable = implode('', static::$organization['int']);
        $charTable = implode('', static::$organization['char']);
        $int = "[$intTable]{1}[0-9]{7}";
        $char = "[$charTable]{1}[0-9]{7}";
        if ($this->withDC) {
            $int .= '[0-9]{1}';
            $char .= '[0A-J]{1}';
        }
        $this->pattern = "/^$int$|^$char$/" . ($this->caseInsensitive === true ? 'i' : '');
    }

    /**
     * Gets the value splited in text and number parts.
     * 
     * @param string $value
     * @return array
     */
    protected function extractParts($value) {
        $split = [
            substr($value, 0, 1)
            , substr($value, 1, 7)
        ];
        if ($this->withDC) {
            $split[] = substr(7, 1);
        }
        return $split;
    }

}
