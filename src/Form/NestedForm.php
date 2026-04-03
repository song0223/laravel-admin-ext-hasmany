<?php

namespace Encore\HasmanyExtra\Form;

use Encore\Admin\Form;
use Encore\Admin\Form\Field;
use Encore\HasmanyExtra\Fields\HasManyMultipleImage;
use Encore\HasmanyExtra\Fields\JsonTable;
use Encore\HasmanyExtra\Fields\Radio;
use Illuminate\Support\Arr;

class NestedForm extends \Encore\Admin\Form\NestedForm
{
    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (in_array($method, ['hasmanyExtraMultipleImage', 'hasmanyMultipleImage'], true)) {
            $column = Arr::get($arguments, 0, '');

            /** @var Field $field */
            $field = new HasManyMultipleImage($column, array_slice($arguments, 1));

            $field->setForm($this->form);
            $field = $this->formatField($field);
            $this->pushField($field);

            return $field;
        }

        if ($method === 'jsonTable' || $method === 'table') {
            $column = Arr::get($arguments, 0, '');

            /** @var Field $field */
            $field = new JsonTable($column, array_slice($arguments, 1));

            $field->setForm($this->form);

            if (method_exists($field, 'setNestedForm')) {
                $field->setNestedForm($this);
            }

            $field = $this->formatField($field);

            $this->pushField($field);

            return $field;
        }

        if ($method === 'radio') {
            $column = Arr::get($arguments, 0, '');

            /** @var Field $field */
            $field = new Radio($column, array_slice($arguments, 1));

            $field->setForm($this->form);
            $field->setNestedForm($this);
            $field = $this->formatField($field);
            $this->pushField($field);

            return $field;
        }

        if ($className = Form::findFieldClass($method)) {
            $column = Arr::get($arguments, 0, '');

            /** @var Field $field */
            $field = new $className($column, array_slice($arguments, 1));

            $field->setForm($this->form);

            if (method_exists($field, 'setNestedForm')) {
                $field->setNestedForm($this);
            }

            $field = $this->formatField($field);

            $this->pushField($field);

            return $field;
        }

        return $this;
    }
}
