<?php

namespace Igniter\EventRules\Models;

use ApplicationException;
use Igniter\EventRules\Classes\BaseAction;
use Igniter\EventRules\Classes\BaseCondition;
use Igniter\EventRules\Classes\BaseEvent;
use Igniter\Flame\Database\Traits\Purgeable;
use Igniter\Flame\Database\Traits\Validation;
use Model;

class EventRule extends Model
{
    use Validation;
    use Purgeable;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    /**
     * @var string The database table name
     */
    protected $table = 'igniter_eventrules_rules';

    public $timestamps = TRUE;

    public $relation = [
        'hasMany' => [
            'conditions' => [EventRuleCondition::class, 'delete' => TRUE],
            'actions' => [EventRuleAction::class, 'delete' => TRUE],
        ],
    ];

    public $casts = [
        'config_data' => 'array',
    ];

    public $purgeable = ['actions', 'conditions'];

    public $rules = [
        'name' => 'sometimes|required|string',
        'code' => 'sometimes|required|alpha_dash|unique:igniter_eventrules_rules,code',
        'event_class' => 'required',
    ];

    /**
     * Kicks off this notification rule, fires the event to obtain its parameters,
     * checks the rule conditions evaluate as true, then spins over each action.
     */
    public function triggerRule()
    {
        if (!$conditions = $this->conditions)
            throw new ApplicationException('Event rule is missing a condition');

        $params = $this->getEventObject()->getEventParams();
        $validConditions = $conditions->sortBy('priority')->filter(function (EventRuleCondition $condition) use ($params) {
            return $condition->getConditionObject()->isTrue($params);
        });

        if (!$conditions->isEmpty() AND !$validConditions->count())
            return FALSE;

        $this->actions->each(function (EventRuleAction $action) use ($params) {
            $action->setRelation('event_rule', $this);
            $action->triggerAction($params);
        });
    }

    public function getEventClassOptions()
    {
        return array_map(function (BaseEvent $eventObj) {
            return $eventObj->getEventName().' - '.$eventObj->getEventDescription();
        }, BaseEvent::findEventObjects());
    }

    public function getActionOptions()
    {
        return array_map(function (BaseAction $actionObj) {
            return $actionObj->getActionName();
        }, BaseAction::findActions());
    }

    public function getConditionOptions()
    {
        return array_map(function (BaseCondition $conditionObj) {
            return $conditionObj->getConditionName();
        }, BaseCondition::findConditions());
    }

    //
    // Attributes
    //

    public function getEventNameAttribute()
    {
        return $this->getEventObject()->getEventName();
    }

    public function getEventDescriptionAttribute()
    {
        return $this->getEventObject()->getEventDescription();
    }

    //
    // Events
    //

    protected function afterFetch()
    {
        $this->applyEventClass();
    }

    //
    // Scope
    //

    public function scopeApplyStatus($query, $status = TRUE)
    {
        return $query->where('status', $status);
    }

    public function scopeApplyClass($query, $class)
    {
        if (!is_string($class)) {
            $class = get_class($class);
        }

        return $query->where('event_class', $class);
    }

    //
    // Manager
    //

    /**
     * Extends this class with the event class
     * @param  string $class Class name
     * @return boolean
     */
    public function applyEventClass($class = null)
    {
        if (!$class)
            $class = $this->event_class;

        if (!$class)
            return FALSE;

        if (!$this->isClassExtendedWith($class)) {
            $this->extendClassWith($class);
        }

        $this->event_class = $class;

        return TRUE;
    }

    /**
     * Returns the event class extension object.
     * @return \Igniter\EventRules\Classes\BaseEvent
     */
    public function getEventObject()
    {
        $this->applyEventClass();

        return $this->asExtension($this->getEventClass());
    }

    public function getEventClass()
    {
        return $this->event_class;
    }

    /**
     * Returns an array of rule codes and descriptions.
     * @param $eventClass
     * @return self[]
     */
    public static function listRulesForEvent($eventClass)
    {
        return self::applyStatus()->applyClass($eventClass)->get();
    }

    /**
     * Synchronise all file-based rules to the database.
     * @return void
     */
    public static function syncAll()
    {
        $presets = BaseEvent::findEventPresets();
        $dbRules = self::lists('is_custom', 'code')->toArray();
        $newRules = array_diff_key($presets, $dbRules);

        // Clean up non-customized rules
        foreach ($dbRules as $code => $isCustom) {
            if ($isCustom OR !$code)
                continue;

            if (!array_key_exists($code, $presets))
                self::whereName($code)->delete();
        }

        // Create new rules
        foreach ($newRules as $code => $preset) {
            self::createFromPreset($code, $preset);
        }
    }

    public static function createFromPreset($code, $preset)
    {
        $actions = array_get($preset, 'actions');
        if (!$actions OR !is_array($actions))
            return;

        $eventRule = new self;
        $eventRule->status = 1;
        $eventRule->is_custom = 0;
        $eventRule->code = $code;
        $eventRule->name = array_get($preset, 'name');
        $eventRule->event_class = array_get($preset, 'event');
        $eventRule->save();

        foreach ($actions as $actionClass => $config) {
            $eventRuleAction = new EventRuleAction;
            $eventRuleAction->fill($config);
            $eventRuleAction->class_name = $actionClass;
            $eventRuleAction->event_rule_id = $eventRule->getKey();
            $eventRuleAction->save();
        }

        return $eventRule;
    }
}