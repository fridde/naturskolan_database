<?php


namespace Fridde;


use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Fridde\Entities\Change;
use Fridde\Entities\ChangeRepository;

class EntitySubscriber implements EventSubscriber
{
    /* @var EntityManager $EM */
    private $EM;
    /* @var UnitOfWork $UoW */
    private $UoW;
    /* @var ChangeRepository $change_repo */
    private $change_repo;

    private $changes_to_log = [
        'Visit' => ['Group', 'Date', 'Time', 'Topic'],
        'Group' => ['User', 'Food', 'NumberStudents', 'Info'],
    ];

    public function getSubscribedEvents()
    {
        return ['onFlush'];
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $this->EM = $args->getEntityManager();
        $this->UoW = $this->EM->getUnitOfWork();
        $this->change_repo = $this->EM->getRepository(Change::class);


        foreach ($this->getLoggableChanges() as $change) {
            if (!$this->change_repo->changeIsLogged($change)) {
                $this->logChange($change);
            }
        }
    }

    private function getLoggableChanges()
    {
        $loggable_changes = [];
        $UoW_entities = [
            'update' => $this->UoW->getScheduledEntityUpdates(),
            'insertion' => $this->UoW->getScheduledEntityInsertions(),
            'deletion' => $this->UoW->getScheduledEntityDeletions(),
        ];

        foreach ($UoW_entities as $change_type => $entity_array) {
            $change_type_int = constant(Change::class . '::' . strtoupper($change_type));
            foreach ($entity_array as $entity) {
                if($change_type_int === Change::INSERTION){
                    continue;
                }
                $class_name = $this->getShortClassName($entity);
                $entity_id = $entity->getId();

                if($change_type_int === Change::DELETION){

                    $change = new Change();
                    $change->setType($change_type_int);
                    $change->setEntityClass($class_name);
                    $change->setEntityId($entity_id);

                    $loggable_changes[] = $change;
                } elseif ($change_type_int === Change::UPDATE){

                    $loggable_properties = $this->changes_to_log[$class_name] ?? [];
                    $change_set = $this->UoW->getEntityChangeSet($entity);
                    $common_properties = array_intersect($loggable_properties, array_keys($change_set));
                    foreach ($common_properties as $property_name) {
                        $change = new Change();
                        $change->setType($change_type_int);
                        $change->setEntityClass($class_name);
                        $change->setEntityId($entity_id);
                        $change->setProperty($property_name);
                        $old_value = $this->standardizeOldValue($change_set[$property_name][0]);
                        $change->setOldValue($old_value);

                        $loggable_changes[] = $change;
                    }
                }
            }
        }

        return $loggable_changes;
    }

    private function standardizeOldValue($old_value)
    {
        if (is_object($old_value) && method_exists($old_value, 'getId')) {
            return $old_value->getId();
        }

        return $old_value;
    }

    private function getShortClassName($entity)
    {
        return (new \ReflectionClass($entity))->getShortName();
    }

    public function logChange(Change $change)
    {
        $this->EM->persist($change);
        $classMetadata = $this->EM->getClassMetadata(Change::class);
        $this->UoW->computeChangeSet($classMetadata, $change);
    }
}