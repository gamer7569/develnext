<?php
namespace php\gui\framework\behaviour\custom;
use php\lang\IllegalStateException;
use php\gui\UXDialog;
use ReflectionClass;
use ReflectionProperty;

/**
 * Class AbstractBehaviour
 * @package behaviour\custom
 */
abstract class AbstractBehaviour
{
    /**
     * @var bool
     */
    public $enabled = true;

    /**
     * @var BehaviourManager
     */
    protected $_manager;

    /**
     * @var mixed
     */
    protected $_target;

    /**
     * AbstractBehaviour constructor.
     * @param mixed $target
     */
    public function __construct($target = null)
    {
        if ($target) {
            $this->apply($target);
        }
    }

    /**
     * @param mixed $target
     */
    abstract protected function applyImpl($target);

    /**
     * @param array $properties
     */
    public function setProperties(array $properties)
    {
        foreach ($properties as $name => $value) {
            if ($name[0] == '_') continue;

            $this->{$name} = $value;
        }
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        $class = new ReflectionClass($this);

        $result = [];

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if ($name[0] == '_') continue;

            $result[$name] = $property->getValue($this);
        }

        return $result;
    }

    /**
     * @param $target
     * @throws \php\lang\IllegalStateException
     */
    public function apply($target)
    {
        if ($this->_target) {
            throw new IllegalStateException("This behaviour already used");
        }

        $this->_target = $target;

        $this->applyImpl($target);
    }

    public function disable()
    {
        $this->enabled = false;
    }

    public function enable()
    {
        $this->enabled = true;
    }
}