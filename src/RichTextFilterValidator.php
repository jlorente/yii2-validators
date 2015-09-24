<?php

/**
 * @author      José Lorente <jose.lorente.martin@gmail.com>
 * @license     The MIT License (MIT)
 * @copyright   José Lorente
 * @version     1.0
 */

namespace jlorente\validators;

use yii\validators\FilterValidator;
use yii\helpers\HtmlPurifier;

/**
 * Filters the content coming from the redactor widget.
 *
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class RichTextFilterValidator extends FilterValidator {

    public $allowedTags = '<code><span><div><label><a><br><p><b><i><del><strike><u><img><video><audio><iframe><object><embed><param><blockquote><mark><cite><small><ul><ol><li><hr><dl><dt><dd><sup><sub><big><pre><code><figure><figcaption><strong><em><table><tr><td><th><tbody><thead><tfoot><h1><h2><h3><h4><h5><h6>';

    /**
     * @inheritdoc
     */
    public function init() {
        $this->filter = function($value) {
            $value = strip_tags($value, $this->allowedTags);
            return RichTextPurifier::process($value);
        };
        parent::init();
    }

}

/**
 * Configuration for the HtmlPurifier of the RedactorFilterValidator.
 *
 * @author José Lorente <jose.lorente.martin@gmail.com>
 */
class RichTextPurifier extends HtmlPurifier {

    /**
     * @inheritdoc
     */
    public static function configure($config) {
        $config->set('HTML.AllowedAttributes', ['img.src', '*.style', 'a.href']);
    }

}
