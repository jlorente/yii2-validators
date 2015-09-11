<?php

/**
 * @author      José Lorente <jose.lorente.martin@gmail.com>
 * @license     The MIT License (MIT)
 * @copyright   José Lorente
 * @version     1.0
 */

namespace jlorente\validators;

use yii\validators\Validator;
use Yii;
use yii\db\IntegrityException;
use yii\base\InvalidConfigException;

/**
 * Validates a value againts the identifier of another model.
 *
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class IntegrityValidator extends Validator {

    public $className;
    public $isArray = false;
    public $field = null;

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();

        if (empty($this->className)) {
            throw InvalidConfigException('Property "className" must be provided');
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value) {
        if ($this->isArray === true) {
            foreach ($value as $v) {
                $r = $this->_validateValue($v);
                if ($r !== null) {
                    return $r;
                }
            }
            return null;
        } else {
            return $this->_validateValue($value);
        }
    }

    private function _validateValue($value) {
        $class = $this->className;
        if ($this->field === null) {
            $obj = $class::findOne($value);
        } else {
            $obj = $class::find([$this->field => $value])->one();
        }

        if ($obj === null) {
            throw new IntegrityException("Integrity constant violation. {$class} with primary key {$value} doesn't exist");
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function clientValidateAttribute($model, $attribute, $view) {
        $class = $this->className;
        $options = [
            'message' => Yii::$app->getI18n()->format($this->message, [
                'class' => $class::className(),
                'value' => is_array($model->$attribute) ? json_encode($model->$attribute) : $model->$attribute
                    ], Yii::$app->language),
        ];
        return '';
    }

}
