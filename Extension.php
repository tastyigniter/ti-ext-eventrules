<?php namespace Igniter\EventRules;

use Admin\Widgets\Form;
use Event;
use Igniter\EventRules\Classes\EventManager;
use System\Classes\BaseExtension;

/**
 * EventRules Extension Information File
 */
class Extension extends BaseExtension
{
    public function boot()
    {
        EventManager::instance()->bindEventRules();

        $this->extendActionFormFields();
    }

    public function registerPermissions()
    {
        return [
            'Igniter.EventRules' => [
                'description' => 'Some permission',
                'action' => ['access', 'add', 'manage', 'delete'],
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'tools' => [
                'child' => [
                    'eventrules' => [
                        'priority' => 5,
                        'class' => 'eventrules',
                        'href' => admin_url('igniter/eventrules/eventrules'),
                        'title' => lang('igniter.eventrules::default.text_title'),
                        'permission' => 'Igniter.EventRules',
                    ],
                ]
            ],
        ];
    }

    protected function extendActionFormFields()
    {
        Event::listen('admin.form.extendFieldsBefore', function (Form $form) {
            if (!$form->getController() instanceof \Igniter\EventRules\Controllers\EventRules) return;
            if ($form->model instanceof \Igniter\EventRules\Models\EventRuleAction) {
                $form->arrayName .= '[options]';
                $form->fields = array_get($form->model->getFieldConfig(), 'fields', []);
            }
        });
    }
}
