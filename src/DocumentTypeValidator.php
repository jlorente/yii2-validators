<?php

/**
 * @author      José Lorente <jose.lorente.martin@gmail.com>
 * @license     The MIT License (MIT)
 * @copyright   José Lorente
 * @version     1.0
 */

namespace jlorente\validators;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\validators\RegularExpressionValidator;
use yii\web\JsExpression;

/**
 * NifValidator to validate spanish NIF's and NIE's documents.
 *
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class DocumentTypeValidator extends RegularExpressionValidator
{

    const TYPE_NIF = 1;
    const TYPE_NIE = 2;
    const TYPE_CIF = 3;
    const TYPE_OTHER = 4;

    /**
     * Control digit table.
     * 
     * @var string
     */
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
        'T', 'R', 'W', 'A', 'G', 'M', 'Y', 'F', 'P', 'D', 'X', 'B', 'N', 'J', 'Z', 'S', 'Q', 'V', 'H', 'L', 'C', 'K', 'E'
    ];

    /**
     * Control digit table.
     * 
     * @var string
     */
    protected static $cifTable = [
        '0', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'
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
     * Validates the document type.
     * 
     * @var int
     */
    public $documentTypeAttribute = 'document_type';

    /**
     *
     * @var int 
     */
    protected $documentType = self::TYPE_OTHER;

    /**
     * Validates the docment with letter.
     * 
     * @var boolean
     */
    public $withDC = true;

    /**
     * If withDC is false and setDC is true, the document will be returned 
     * with the corresponding letter.
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
    public function init()
    {
        $this->pattern = $this->getOtherPattern();
        $this->ensureMessages();
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $this->documentType = $model->{$this->documentTypeAttribute};
        $result = $this->validateValue($model->$attribute);
        if (!empty($result)) {
            $this->addError($model, $attribute, $result[0], $result[1]);
        }
        $model->$attribute = $this->_value;
    }

    /**
     * @inheritdoc
     */
    public function validateValue($value)
    {
        switch ($this->documentType) {
            case self::TYPE_NIF:
                return $this->validateNif($value);
            case self::TYPE_NIE:
                return $this->validateNie($value);
            case self::TYPE_CIF:
                return $this->validateCif($value);
            case self::TYPE_OTHER:
            default:
                return $this->validateOther($value);
        }
    }

    /**
     * 
     * @param string $value
     */
    public function validateNif($value)
    {
        $this->pattern = $this->getNifPattern();
        $valid = parent::validateValue($value);
        if ($valid !== null) {
            return [$this->messages['patternErrorNif'], []];
        }

        $split = [];
        preg_match_all('/[0-9]+|[A-Z]+/' . ($this->caseInsensitive === true ? 'i' : ''), $value, $split);
        $fragments = $split[0];

        $number = (int) preg_replace('/^[0]+/', '', $fragments[0]);
        $letter = static::$table[$number % 23];
        if ($this->withDC === true && $letter !== $fragments[1]) {
            return [$this->messages['controlDigitError'], []];
        } elseif ($this->setDC === true) {
            $fragments[1] = $letter;
        }

        $this->_value = implode('', $fragments);
        return;
    }

    /**
     * 
     * @param string $value
     */
    public function validateNie($value)
    {
        $this->pattern = $this->getNiePattern();
        $valid = parent::validateValue($value);
        if ($valid !== null) {
            return [$this->messages['patternErrorNie'], []];
        }

        $split = [];
        preg_match_all('/[0-9]+|[A-Z]+/' . ($this->caseInsensitive === true ? 'i' : ''), $value, $split);
        $fragments = $split[0];

        $number = (int) preg_replace('/^[0]+/', '', array_search($fragments[0], static::$nieLeadingLetters) . $fragments[1]);
        $letter = static::$table[$number % 23];
        if ($this->withDC === true && $letter !== $fragments[2]) {
            return [$this->messages['controlDigitError'], []];
        } elseif ($this->setDC === true) {
            $fragments[2] = $letter;
        }

        $this->_value = implode('', $fragments);
        return;
    }

    /**
     * 
     * @param string $value
     */
    public function validateCif($value)
    {
        $this->pattern = $this->getCifPattern();
        $valid = parent::validateValue($value);
        if ($valid !== null) {
            return [$this->messages['patternErrorCif'], []];
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
            $dc = static::$cifTable[$dcNumber];
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
     * 
     * @param string $value
     */
    public function validateOther($value)
    {
        $this->pattern = $this->getOtherPattern();
        $valid = parent::validateValue($value);
        if ($valid !== null) {
            return [$this->messages['patternErrorDefault'], []];
        }

        return;
    }

    /**
     * Gets the value after validation.
     * 
     * @return string
     */
    public function getNewValue()
    {
        return $this->_value;
    }

    /**
     * @inheritdoc
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {
        $table = Json::encode(static::$table);
        $nieDigits = Json::encode(static::$nieLeadingLetters);
        $errorMessage = Json::encode($this->messages['controlDigitError']);
        $typeAttribute = $model->formName() . "[{$this->documentTypeAttribute}]";
        $organization = Json::encode(static::$organization);
        $cifTable = Json::encode(static::$cifTable);

        $regularExpression = Json::htmlEncode([
                    self::TYPE_NIF => new JsExpression(Html::escapeJsRegularExpression($this->getNifPattern()))
                    , self::TYPE_NIE => new JsExpression(Html::escapeJsRegularExpression($this->getNiePattern()))
                    , self::TYPE_CIF => new JsExpression(Html::escapeJsRegularExpression($this->getCifPattern()))
                    , self::TYPE_OTHER => new JsExpression(Html::escapeJsRegularExpression($this->getOtherPattern()))
        ]);

        $typeNif = self::TYPE_NIF;
        $typeNie = self::TYPE_NIE;
        $typeCif = self::TYPE_CIF;
        $typeOther = self::TYPE_OTHER;

        $patternErrorNif = Json::encode($this->messages['patternErrorNif']);
        $patternErrorNie = Json::encode($this->messages['patternErrorNie']);
        $patternErrorCif = Json::encode($this->messages['patternErrorCif']);
        $patternErrorDefault = Json::encode($this->messages['patternErrorDefault']);

        $js = null;
        if ($this->withDC === true) {
            $js = <<<JS
(function() {
    var regExps = $regularExpression;
    var split, nSplit, number, cLetter;

    if (!value.length) {
        return;
    }
                    
    var type = parseInt(\$form.find('[name="$typeAttribute"]').val());
    if (!type) {
        return;
    }
                                     
    switch (type) {
        case $typeNif:
            if (!value.match(regExps[$typeNif])) {
                return yii.validation.addMessage(messages, $patternErrorNif, value);
            }
                
            split = value.match(/(\d+|[^\d]+)/g);
            nSplit = split.length;
            number = split[nSplit - 2].replace(/^0+/, '');
            cLetter = {$table}[number % 23];
            if (cLetter !== split[split.length - 1]) {
                yii.validation.addMessage(messages, $errorMessage, value);
            }
                    
            break;
        case $typeNie:
            if (!value.match(regExps[$typeNie])) {
                return yii.validation.addMessage(messages, $patternErrorNie, value);
            }
                
            split = value.match(/(\d+|[^\d]+)/g);
            nSplit = split.length;
            number = ($.inArray(split[0], {$nieDigits}) + '' + split[nSplit - 2]).replace(/^0+/, '');
            cLetter = {$table}[number % 23];
            if (cLetter !== split[split.length - 1]) {
                yii.validation.addMessage(messages, $errorMessage, value);
            }
                    
            break;
        case $typeCif:
            if (!value.match(regExps[$typeCif])) {
                return yii.validation.addMessage(messages, $patternErrorCif, value);
            }
            
            var org, number, sum = 0, i, dcNumber, dc, tableOrg, tableDC, n, aux;
            tableOrg = $organization;
            tableDC = $cifTable;
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
                        
            break;
        default:
            if (!value.match(regExps[$typeOther])) {
                yii.validation.addMessage(messages, $patternErrorDefault, value);
            }
            break;
    }
                    
})();
JS;
        }
        return $js;
    }

    /**
     * Ensures the format of the validator.
     */
    protected function ensureValidators()
    {
        switch ($this->documentType) {
            case self::TYPE_NIF:
                $pattern = $this->getNifPattern();
                break;
            case self::TYPE_NIE:
                $pattern = $this->getNiePattern();
                break;
            case self::TYPE_CIF:
                $pattern = $this->getCifPattern();
                break;
            case self::TYPE_OTHER:
                $pattern = $this->getOtherPattern();
                break;
        }

        $this->pattern = $pattern . ($this->caseInsensitive === true ? 'i' : '');
    }

    /**
     * Ensures the error messages of the validator.
     */
    protected function ensureMessages()
    {
        $this->messages = array_merge([
            'controlDigitError' => Yii::t('yii', 'The letter don\'t correspond to the number.')
            , 'patternErrorNif' => Yii::t('yii', 'The valid format for NIF is 8 digits followed by a valid letter.')
            , 'patternErrorNie' => Yii::t('yii', 'The valid format for NIE is a leading letter followed by 7 digits and an ending letter.')
            , 'patternErrorCif' => Yii::t('yii', 'The valid format for CIF is a letter followed by 7 digits and an ending letter.')
            , 'patternErrorDefault' => Yii::t('yii', 'The valid format for document is a string formed by letters and numbers.')
                ], $this->messages);

        $this->message = $this->messages['patternErrorDefault'];
    }

    /**
     * 
     * @return type
     */
    protected function getNifPattern()
    {
        $std = '^[0-9]{8}';
        if ($this->withDC) {
            $letters = implode('', static::$table);
            $std .= '[' . $letters . ']{1}$';
        } else {
            $std .= '$';
        }

        return "/$std/";
    }

    /**
     * 
     * @return 
     */
    protected function getNiePattern()
    {
        $nie = '^[XYZ]{1}[0-9]{7}';
        if ($this->withDC) {
            $letters = implode('', static::$table);
            $nie .= '[' . $letters . ']{1}$';
        } else {
            $nie .= '$';
        }

        return "/$nie/";
    }

    /**
     * 
     * @return 
     */
    protected function getCifPattern()
    {
        $intTable = implode('', static::$organization['int']);
        $charTable = implode('', static::$organization['char']);
        $int = "[$intTable]{1}[0-9]{7}";
        $char = "[$charTable]{1}[0-9]{7}";
        if ($this->withDC) {
            $int .= '[0-9]{1}';
            $char .= '[0A-J]{1}';
        }

        return "/^$int$|^$char$/";
    }

    /**
     * 
     * @return 
     */
    protected function getOtherPattern()
    {
        return "/^[A-Z0-9]{2,30}$/";
    }

    /**
     * Guesses the value document type.
     * 
     * @param string $value
     * @return int|null
     */
    public function guessType($value)
    {
        if (!$value) {
            return null;
        }

        if ($this->validateNif($value) === null) {
            return self::TYPE_NIF;
        } elseif ($this->validateNie($value) === null) {
            return self::TYPE_NIE;
        } elseif ($this->validateCif($value) === null) {
            return self::TYPE_CIF;
        } else {
            return self::TYPE_OTHER;
        }
    }

}
