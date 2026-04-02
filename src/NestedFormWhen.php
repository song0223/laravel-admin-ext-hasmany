<?php

namespace Encore\HasmanyExtra;

use Closure;
use Encore\Admin\Form\Field;
use Encore\Admin\Form\NestedForm;
use ReflectionClass;

class NestedFormWhen
{
    /**
     * @var NestedForm
     */
    protected $form;

    /**
     * @var Field
     */
    protected $field;

    /**
     * @var array
     */
    protected $conditions = [];

    /**
     * @param NestedForm $form
     * @param Field $field
     */
    public function __construct(NestedForm $form, Field $field)
    {
        $this->form = $form;
        $this->field = $field;
        $this->field->addElementClass($this->triggerClass());
    }

    /**
     * @param NestedForm $form
     * @param Field $field
     * @return static
     */
    public static function make(NestedForm $form, Field $field)
    {
        return new static($form, $field);
    }

    /**
     * @param mixed $value
     * @param Closure $closure
     * @return $this
     */
    public function when($value, Closure $closure)
    {
        $this->conditions[] = (string) $value;
        $hiddenClass = ((string) $this->currentValue() === (string) $value) ? '' : 'hide';

        $this->form->html(sprintf(
            '<div class="nested-form-when-group %s %s %s" data-trigger-class="%s" data-when-value="%s">',
            $this->groupBaseClass(),
            $this->groupValueClass($value),
            $hiddenClass,
            $this->triggerClass(),
            e((string) $value)
        ))->plain();

        $closure($this->form);

        $this->form->html('</div>')->plain();

        $this->bootScript();

        return $this;
    }

    /**
     * @return void
     */
    protected function bootScript()
    {
        $script = <<<JS
(function () {
    var selector = '.{$this->triggerClass()}';
    var groupSelector = '.{$this->groupBaseClass()}';
    var namespace = '.nestedFormWhen_{$this->domKey()}';

    var resolveValue = function (\$form, currentInput) {
        if (currentInput && $(currentInput).is(selector)) {
            return $(currentInput).val();
        }

        var checked = \$form.find(selector + ':checked').val();

        if (typeof checked !== 'undefined' && checked !== null) {
            return checked;
        }

        var \$checked = \$form.find(selector).filter(function () {
            var \$radio = $(this);

            return \$radio.prop('checked') || \$radio.closest('.iradio_minimal-blue').hasClass('checked');
        }).first();

        return \$checked.length ? \$checked.val() : null;
    };

    var sync = function (context, currentInput) {
        var \$context = $(context || document);

        \$context.find(groupSelector).each(function () {
            var \$group = $(this);
            var \$form = \$group.closest('.has-many-items-form, .fields-group');
            var whenValue = String(\$group.data('when-value'));

            if (!\$form.length) {
                return;
            }

            var currentValue = resolveValue(\$form, currentInput);

            if (String(currentValue) === whenValue) {
                \$group.removeClass('hide');
            } else {
                \$group.addClass('hide');
            }
        });
    };

    var bind = function (context) {
        var \$context = $(context || document);

        \$context.find(selector).each(function () {
            var \$input = $(this);

            \$input.off(namespace);
            \$input.on('change' + namespace + ' ifChanged' + namespace + ' ifChecked' + namespace + ' ifUnchecked' + namespace + ' click' + namespace, function () {
                var \$current = $(this);

                setTimeout(function () {
                    sync(\$current.closest('.has-many-items-form, .fields-group'), \$current);
                }, 0);
            });
        });
    };

    bind(document);

    setTimeout(function () {
        bind(document);
        sync(document);
    }, 0);
})();
JS
        ;

        if (method_exists($this->field, 'appendNestedWhenScript')) {
            $this->field->appendNestedWhenScript($script);

            return;
        }

        $this->field->setScript($this->field->getScript()."\n".$script);
    }

    /**
     * @return string
     */
    protected function domKey()
    {
        $reflection = new ReflectionClass($this->field);
        $property = $reflection->getProperty('elementName');
        $property->setAccessible(true);
        $name = $property->getValue($this->field) ?: $this->field->column();

        return preg_replace('/[^A-Za-z0-9_]+/', '_', (string) $name);
    }

    /**
     * @return string
     */
    protected function triggerClass()
    {
        return 'nested-form-when-trigger-'.$this->domKey();
    }

    /**
     * @return string
     */
    protected function groupBaseClass()
    {
        return 'nested-form-when-'.$this->domKey();
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function groupValueClass($value)
    {
        return $this->groupBaseClass().'-'.preg_replace('/[^A-Za-z0-9_]+/', '_', (string) $value);
    }

    /**
     * @return mixed|null
     */
    protected function currentValue()
    {
        $model = $this->form->model();

        if ($model && method_exists($model, 'getAttribute')) {
            return $model->getAttribute($this->field->column());
        }

        return $this->field->value();
    }
}
