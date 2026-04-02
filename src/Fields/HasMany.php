<?php

namespace Encore\HasmanyExtra\Fields;

use Encore\Admin\Form;
use Encore\Admin\Form\NestedForm as BaseNestedForm;
use Encore\HasmanyExtra\Form\NestedForm;

class HasMany extends \Encore\Admin\Form\Field\HasMany
{
    /**
     * @param string $column
     * @param \Closure $builder
     * @param null $model
     * @return BaseNestedForm
     */
    protected function buildNestedForm($column, \Closure $builder, $model = null)
    {
        $form = new NestedForm($column, $model);

        $form->setForm($this->form);

        call_user_func($builder, $form);

        $form->hidden($this->getKeyName());

        $form->hidden(BaseNestedForm::REMOVE_FLAG_NAME)->default(0)->addElementClass(BaseNestedForm::REMOVE_FLAG_CLASS);

        return $form;
    }

    /**
     * @param string $templateScript
     * @return string
     */
    protected function replaceTemplateKeyScript($templateScript)
    {
        $defaultKey = BaseNestedForm::DEFAULT_KEY_NAME;

        return <<<EOT
    var currentScript = {$templateScript};
    var scriptTag = document.createElement('script');
    scriptTag.type = 'text/javascript';
    scriptTag.text = currentScript.replace(/{$defaultKey}/g, index);
    document.body.appendChild(scriptTag);
    document.body.removeChild(scriptTag);
EOT;
    }

    /**
     * @param string $templateScript
     * @return void
     */
    protected function setupScriptForDefaultView($templateScript)
    {
        parent::setupScriptForDefaultView($this->replaceTemplateKeyScript(json_encode($templateScript)));
    }

    /**
     * @param string $templateScript
     * @return void
     */
    protected function setupScriptForTabView($templateScript)
    {
        parent::setupScriptForTabView($this->replaceTemplateKeyScript(json_encode($templateScript)));
    }

    /**
     * @param string $templateScript
     * @return void
     */
    protected function setupScriptForTableView($templateScript)
    {
        parent::setupScriptForTableView($this->replaceTemplateKeyScript(json_encode($templateScript)));
    }
}
