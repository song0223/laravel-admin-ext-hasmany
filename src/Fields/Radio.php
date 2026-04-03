<?php

namespace Encore\HasmanyExtra\Fields;

use Closure;
use Encore\Admin\Form\NestedForm;
use Encore\HasmanyExtra\NestedFormWhen;

class Radio extends \Encore\Admin\Form\Field\Radio
{
    /**
     * @var NestedForm|null
     */
    protected $nestedForm;

    /**
     * @var string
     */
    protected $nestedWhenScript = '';

    /**
     * @param NestedForm $nestedForm
     * @return $this
     */
    public function setNestedForm(NestedForm $nestedForm)
    {
        $this->nestedForm = $nestedForm;

        return $this;
    }

    /**
     * @param mixed $operator
     * @param mixed $value
     * @param Closure|null $closure
     * @return $this
     */
    public function when($operator, $value, $closure = null)
    {
        if (func_num_args() == 2) {
            $closure = $value;
            $value = $operator;
            $operator = '=';
        }

        if ($this->nestedForm instanceof NestedForm && $operator === '=' && $closure instanceof Closure) {
            NestedFormWhen::make($this->nestedForm, $this)->when($value, $closure);

            return $this;
        }

        return parent::when($operator, $value, $closure);
    }

    /**
     * @param string $script
     * @return $this
     */
    public function appendNestedWhenScript($script)
    {
        $this->nestedWhenScript .= "\n".$script;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $this->script = "$('{$this->getElementClassSelector()}').iCheck({radioClass:'iradio_minimal-blue'});";

        $this->addCascadeScript();

        if ($this->nestedWhenScript) {
            $this->script .= "\n".$this->nestedWhenScript;
        }

        $this->addVariables(['options' => $this->options, 'checked' => $this->checked, 'inline' => $this->inline]);

        return (new \ReflectionMethod(\Encore\Admin\Form\Field::class, 'render'))->invoke($this);
    }

    /**
     * Keep laravel-admin native cascade behavior working for normal forms,
     * while this subclass adds nested-form support.
     *
     * @return string
     */
    protected function getFormFrontValue()
    {
        return 'var checked = $(this).val();';
    }
}
