<?php

namespace Igniter\EventRules\Models;

use Igniter\Flame\Database\Model;

class EventRuleCondition extends Model
{
    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    /**
     * @var string The database table name
     */
    protected $table = 'igniter_eventrules_conditions';

    public $timestamps = TRUE;

    protected $guarded = [];

    public $relation = [
        'belongsTo' => [
            'event_rule' => [EventRule::class, 'key' => 'event_rule_id'],
        ],
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
        return $this->getConditionObject()->getConditionName();
    }

    public function getDescriptionAttribute()
    {
        return $this->getConditionObject()->getConditionDescription();
    }

    //
    // Events
    //

    protected function afterFetch()
    {
        $this->applyConditionClass();
    }

    /**
     * Extends this model with the condition class
     * @param  string $class Class name
     * @return boolean
     */
    public function applyConditionClass($class = null)
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
     * @return \Igniter\EventRules\Classes\BaseCondition
     */
    public function getConditionObject()
    {
        $this->applyConditionClass();

        return $this->asExtension($this->getConditionClass());
    }

    public function getConditionClass()
    {
        return $this->class_name;
    }
}