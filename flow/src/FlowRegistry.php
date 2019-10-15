<?php 
namespace Ezadev\Flow;

use Ezadev\Flow\Events\FlowSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\MarkingStore\MultipleStateMarkingStore;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;

class FlowRegistry
{
    protected $registry;
    protected $config;
    protected $dispatcher;

    public function __construct(array $config){
        $this->registry = new Registry();
        $this->config = $config;
        $this->dispatcher = new EventDispatcher();

        $subscriber = new FlowSubscriber();
        $this->dispatcher->addSubscriber($subscriber);

        foreach ($this->config as $nm=>$flow_data){
            $this->addFromArray($nm, $flow_data);
        }
    }

    public function get($subject, $flow_name=null){
        return $this->registry->get($subject, $flow_name);
    }

    public function add(Workflow $flow, $supportStrategy){
        $this->registry->addWorkflow($flow, new InstanceOfSupportStrategy($supportStrategy));
    }

    public function addFromArray($nm, array $flow_data){
        $builder = new DefinitionBuilder($flow_data['places']);
        foreach ($flow_data['transitions'] as $transition_name=>$transition){
            if(!is_string($transition_name)){
                $transition_name = $transition['name'];
            }

            foreach ((array) $transition['from'] as $from){
                $builder->addTransition(new Transition($transition_name, $from, $transition['to']));
            }
        }

        $definition = $builder->build();
        $marking_store = $this->getMarkingStoreInstance($flow_data);
        $flow = $this->getWorkflowInstance($nm, $flow_data, $definition, $marking_store);

        foreach($flow_data['supports'] as $supportClass){
            $this->add($flow, $supportClass);
        }
    }

    protected function getWorkflowInstance(
        $name,
        array $flow_data,
        Definition $definition,
        MarkingStoreInterface $marking_store
    ){
        if(isset($flow_data['class'])){
            $className = $flow_data['class'];
        }elseif(isset($flow_data['type']) && $flow_data['type'] == 'state_machine'){
            $className = StateMachine::class;
        }else{
            $className = Workflow::class;
        }

        return new $className($definition, $marking_store, $this->dispatcher, $name);
    }

    protected function getMarkingStoreInstance(array $flow_data){
        $marking_store_data = isset($flow_data['marking_store']) ? $flow_data['marking_store'] : [];
        $args = isset($marking_store_data['arguments']) ? $marking_store_data['arguments'] : [];

        if(isset($marking_store_data['class'])){
            $className = $marking_store_data['class'];
        }elseif(isset($marking_store_data['type']) && $marking_store_data['type'] == 'multiple_state'){
            $className = MultipleStateMarkingStore::class;
        }else{
            $className = SingleStateMarkingStore::class;
        }

        $class = new \ReflectionClass($className);
        return $class->newInstanceArgs($args);
    }
}