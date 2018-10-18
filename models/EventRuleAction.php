<?php

namespace Igniter\EventRules\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;
use SystemException;

class EventRuleAction extends Model
{
    use Validation;

    /**
     * @var string The database table name
     */
    protected $table = 'igniter_eventrules_actions';

    public $timestamps = TRUE;

    protected $guarded = [];

    public $relation = [
        'belongsTo' => [
            'event_rule' => [EventRule::class, 'key' => 'event_rule_id'],
        ]
    ];

    public $casts = [
        'options' => 'array',
    ];

    public $rules = [
        'class_name' => 'required',
    ];

    //
    // Attributes
    //

    public function getNameAttribute()
    {
        return $this->getActionObject()->getActionName();
    }

    public function getDescriptionAttribute()
    {
        return $this->getActionObject()->getActionDescription();
    }

    //
    // Events
    //

    public function afterFetch()
    {
        $this->applyActionClass();
        $this->loadCustomData();
    }

    public function beforeSave()
    {
        $this->setCustomData();
    }

    public function applyCustomData()
    {
        $this->setCustomData();
        $this->loadCustomData();
    }

    /**
     * Extends this model with the action class
     * @param  string $class Class name
     * @return boolean
     */
    public function applyActionClass($class = null)
    {
        if (!$class)
            $class = $this->class_name;

        if (!$class)
            return FALSE;

        if (!$this->isClassExtendedWith($class)) {
            $this->extendClassWith($class);
        }

        $this->class_name = $class;
        return TRUE;
    }

    /**
     * @return \Igniter\EventRules\Classes\BaseAction
     */
    public function getActionObject()
    {
        $this->applyActionClass();

        return $this->asExtension($this->getActionClass());
    }

    public function getActionClass()
    {
        return $this->class_name;
    }

    protected function loadCustomData()
    {
        $this->setRawAttributes((array)$this->getAttributes() + (array)$this->options, TRUE);
    }

    protected function setCustomData()
    {
        if (!$actionObj = $this->getActionObject()) {
            throw new SystemException(sprintf('Unable to find action object [%s]', $this->getActionClass()));
        }

        $config = $actionObj->getFieldConfig();
        if (!$fields = array_get($config, 'fields'))
            return;

        $fieldAttributes = array_keys($fields);
        $this->options = array_only($this->getAttributes(), $fieldAttributes);
        $this->setRawAttributes(array_except($this->getAttributes(), $fieldAttributes));
    }
}