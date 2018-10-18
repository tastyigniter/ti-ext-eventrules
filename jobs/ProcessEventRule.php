<?php namespace Igniter\EventRules\Jobs;

use Igniter\EventRules\Classes\EventManager;

class ProcessEventRule
{
    use \Illuminate\Queue\InteractsWithQueue;
    use \Illuminate\Queue\SerializesAndRestoresModelIdentifiers;

    protected $eventClass;
    protected $params;

    /**
     * Create a new job instance.
     *
     * @param $eventClass
     * @param  array $params
     */
    public function __construct($eventClass, array $params)
    {
        $this->eventClass = $eventClass;

        $this->params = $this->serializeParams($params);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->delete();

        EventManager::instance()->fireEvent(
            $this->eventClass,
            $this->unserializeParams()
        );
    }

    protected function serializeParams($params)
    {
        $result = [];

        foreach ($params as $param => $value) {
            $result[$param] = $this->getSerializedPropertyValue($value);
        }

        return $result;
    }

    protected function unserializeParams()
    {
        $result = [];

        foreach ($this->params as $param => $value) {
            $result[$param] = $this->getRestoredPropertyValue($value);
        }

        return $result;
    }
}