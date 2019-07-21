## Event Rules and Actions for TastyIgniter

This extension allows admins to configure event rule actions to be triggered when certain events happen in TastyIgniter. 
An action is attached to the event rule, such as send mail to customer or send print jobs to printer.

### Admin Panel

Event Rules are managed in the admin panel by navigating to **Tools > Event Rules**.

### Event Rules workflow

When an event rule is triggered, it uses the following workflow:

- Extension registers associated actions, conditions and events using `registerEventRules`
- When a system event is fired `Event::fire`, the parameters of the event are captured, along with any global parameters
- A command is pushed on the queue to process the event rule `Queue::push`
- The command finds all event rules using the event class and triggers them
- The event rule conditions are checked and only proceed if met
- The event rule actions are triggered

### Usage

**Example of Registering Event Rules**

The `presets` definition specifies event rules defined by the system.

```
public function registerEventRules()
{
    return [
        'events' => [
            \Igniter\User\EventRules\Events\CustomerRegistered::class,
        ],
        'actions' => [
            \Igniter\User\EventRules\Actions\SendMailTemplate::class,
        ],
        'conditions' => [
            \Igniter\User\EventRules\Conditions\CustomerAttribute::class
        ],
        'presets' => [
            'registration_email' => [
                'name' => 'Send customer registration email',
                'event' => \Igniter\User\EventRules\Events\CustomerRegistered::class,
                'actions' => [
                    \Igniter\User\EventRules\Actions\SendMailTemplate::class => [
                        'template' => 'igniter.user::mail.registration_email'
                    ],
                ]
            ]
        ],
    ];
}
```

**Example of Registering Global Parameters**
These parameters are available globally to all event rules.

```
\Igniter\EventRules\Classes\EventManager::instance()->registerCallback(function($manager) {
    $manager->registerGlobalParams([
        'customer' => Auth::customer()
    ]);
});
```

**Example of an Event Class**

An event class is responsible for preparing the parameters passed to the conditions and actions.

```
class CustomerRegisteredEvent extends \Igniter\EventRules\Classes\BaseEvent
{
    /**
     * Returns information about this event, including name and description.
     */
    public function eventDetails()
    {
        return [
            'name'        => 'Registered',
            'description' => 'When a customer registers',
            'group'       => 'customer'
        ];
    }

    public static function makeParamsFromEvent(array $args, $eventName = null)
    {
        return [
            'user' => array_get($args, 0)
        ];
    }
}
```

**Example of an Action Class**

Action classes define the final step in a event rule and perform the event rule itself.

```
class SendMailTemplate extends \Igniter\EventRules\Classes\BaseAction
{
    /**
     * Returns information about this action, including name and description.
     */
    public function actionDetails()
    {
        return [
            'name'        => 'Compose a mail message',
            'description' => 'Send a message to a recipient',
        ];
    }

    /**
     * Field configuration for the action.
     */
    public function defineFormFields()
    {
        return [
            'fields' => [],
        ];
    }

    /**
     * Triggers this action.
     * @param array $params
     * @return void
     */
    public function triggerAction($params)
    {
        $email = 'test@email.tld';
        $template = $this->model->template;

        Mail::sendTo($email, $template, $params);
    }
}
```

**Example of a Condition Class**

A condition class must declare an `isTrue` method for evaluating whether the condition is true or not.

```
class MyCondition extends \Igniter\EventRules\Classes\BaseCondition
{
    /**
     * Returns information about this condition, including name and description.
     */
    public function conditionDetails()
    {
        return [
            'name'        => 'Condition',
            'description' => 'My Condition is checked',
        ];
    }

    /**
     * Checks whether the condition is TRUE for specified parameters
     * @param array $params
     * @return bool
     */
    public function isTrue(&$params)
    {
        return true;
    }
}
```

**Example of a Model Attribute Condition Class**

A condition class applies conditions to sets of model attributes.

```
class CustomerAttribute extends \Igniter\EventRules\Classes\BaseCondition
{
    /**
     * Returns information about this condition, including name and description.
     */
    public function conditionDetails()
    {
        return [
            'name'        => 'Customer attribute',
        ];
    }
    
    public function defineModelAttributes()
    {
        return [
            'first_name' => [
                'label' => 'First Name',
            ],
            'last_name' => [
                'label' => 'Last Name',
            ],
        ];
    }

    /**
     * Checks whether the condition is TRUE for specified parameters
     * @param array $params
     * @return bool
     */
    public function isTrue(&$params)
    {
        return true;
    }
}
```
