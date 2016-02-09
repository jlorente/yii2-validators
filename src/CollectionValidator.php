<?php

/**
 * @author      José Lorente <jose.lorente.martin@gmail.com>
 * @license     The MIT License (MIT)
 * @copyright   José Lorente
 * @version     1.0
 */

namespace custom\validators;

use Yii;
use yii\validators\Validator;
use Traversable;

/**
 * CollectionValidator validates arrays and Traversable objects of the same type.
 * 
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class CollectionValidator extends Validator {

    public $message;
    public $validator;
    protected $_validator;

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        if ($this->message === null) {
            $this->message = Yii::t(
                            'validator', '{attribute} must be an array', [
                        'attribute' => $attribute
            ]);
        }

        if ($this->validator !== null) {
            $this->_validator = Validator::createValidator($this->validator[1], null, null, array_slice($this->validator, 2));
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute) {
        $result = $this->validateValue($model->$attribute);
        if ($result !== null) {
            $model->addErrors($result);
        }
    }

    /**
     * @inheritdoc
     */
    public function validateValue(&$value) {
        $error = [];
        if (!is_array($value) && !$value instanceof Traversable) {
            if (empty($value)) {
                $value = [];
            } else {
                $error[] = $this->message;
            }
        } elseif ($this->_validator !== null) {
            foreach ($value as $el) {
                $er = $this->_validator->validateValue($el);
                if ($er !== null) {
                    $error[] = $er;
                }
            }
        }
        return empty($error) ? null : $error;
    }

    public function _array($attribute, $params) {
        $r = true;
        if (!is_array($this->$attribute)) {
            if (empty($this->$attribute)) {
                $this->$attribute = [];
            } else {
                $this->addError(
                        $this, $attribute, isset($params['message']) ? $params['message'] : Yii::t(
                                        'validator', '{attribute} must be an array', [
                                    'attribute' => $attribute
                                ])
                );
                $r = false;
            }
        } elseif (isset($params['class'])) {
            foreach ($this->$attribute as $el) {
                if (is_a($el, $params['class'])) {
                    $this->addError(
                            $this, $attribute, isset($params['message']) ? $params['message'] : Yii::t(
                                            'validator', 'Elements in {attribute} must be of class {class}.', [
                                        'attribute' => $attribute,
                                        'class' => $params['class']
                                    ])
                    );
                    $r = false;
                }
            }
        }
        return $r;
    }

}
