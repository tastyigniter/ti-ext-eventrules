<?php

namespace Igniter\EventRules\Controllers;

use AdminMenu;
use ApplicationException;
use Igniter\EventRules\Models\EventRule;

/**
 * EventRules Admin Controller
 */
class EventRules extends \Admin\Classes\AdminController
{
    public $implement = [
        'Admin\Actions\FormController',
        'Admin\Actions\ListController',
    ];

    public $listConfig = [
        'list' => [
            'model' => 'Igniter\EventRules\Models\EventRule',
            'title' => 'lang:igniter.eventrules::default.text_title',
            'emptyMessage' => 'lang:igniter.eventrules::default.text_empty',
            'defaultSort' => ['id', 'DESC'],
            'configFile' => 'eventrule',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter.eventrules::default.text_form_name',
        'model' => 'Igniter\EventRules\Models\EventRule',
        'create' => [
            'title' => 'lang:admin::lang.form.create_title',
            'redirect' => 'igniter/eventrules/eventrules/edit/{id}',
            'redirectClose' => 'igniter/eventrules/eventrules',
        ],
        'edit' => [
            'title' => 'lang:admin::lang.form.edit_title',
            'redirect' => 'igniter/eventrules/eventrules/edit/{id}',
            'redirectClose' => 'igniter/eventrules/eventrules',
        ],
        'preview' => [
            'title' => 'lang:admin::lang.form.preview_title',
            'redirect' => 'igniter/eventrules/eventrules',
        ],
        'delete' => [
            'redirect' => 'igniter/eventrules/eventrules',
        ],
        'configFile' => 'eventrule',
    ];

    protected $requiredPermissions = 'Igniter.EventRules';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('tools', 'eventrules');
    }

    public function index()
    {
        if ($this->getUser()->hasPermission('Igniter.EventRules.Manage'))
            EventRule::syncAll();

        $this->asExtension('ListController')->index();
    }

    public function edit_onLoadCreateActionForm($context, $recordId)
    {
        return $this->loadConnectorFormField('actions', $context, $recordId);
    }

    public function edit_onLoadCreateConditionForm($context, $recordId)
    {
        return $this->loadConnectorFormField('conditions', $context, $recordId);
    }

    public function formExtendFields($form)
    {
        if ($form->context != 'create')
            $form->getField('event_class')->disabled = TRUE;
    }

    public function formBeforeCreate($model)
    {
        $model->is_custom = TRUE;
        $model->status = TRUE;
    }

    public function formValidate($model, $form)
    {
        $rules = [
            ['event_class', 'lang:igniter.eventrules::default.label_event_class', 'sometimes|required'],
        ];

        return $this->validatePasses(post($form->arrayName), $rules);
    }

    protected function loadConnectorFormField($method, $context, $recordId): array
    {
        $actionClass = post('EventRule._'.str_singular($method));
        if (!strlen($actionClass))
            throw new ApplicationException(sprintf('Please select an %s to attach', str_singular($method)));

        $formController = $this->asExtension('FormController');
        $model = $formController->formFindModelObject($recordId);

        $model->$method()->create([
            'class_name' => $actionClass,
            'event_rule_id' => $recordId,
        ]);

        $formController->initForm($model, $context);
        $formField = $this->widgets['form']->getField($method);

        return [
            '#notification' => $this->makePartial('flash'),
            '#'.$formField->getId('group') => $this->widgets['form']->renderField($formField, [
                'useContainer' => FALSE,
            ]),
        ];
    }
}
