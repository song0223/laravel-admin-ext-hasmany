<?php

namespace Encore\HasmanyExtra\Form;

use Encore\Admin\Form;
use Encore\Admin\Form\Field;
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
