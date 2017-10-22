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
        'Visit' => ['Group', 'Date', 'Time'],
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


        foreach ($this->getLoggableChanges() as $c) {
            if (!$this->change_repo->changeIsLogged($c[0], $c[1], $c[2])) {
                $this->logChange(...$c);
            }
        }
    }

    private function getLoggableChanges()
    {
        $loggable_changes = [];
        foreach ($this->UoW->getScheduledEntityUpdates() as $entity) {
            $class_name = $this->getShortClassName($entity);
            $loggable_properties = $this->changes_to_log[$class_name] ?? [];
            $change_set = $this->UoW->getEntityChangeSet($entity);
            $common_properties = array_intersect($loggable_properties, array_keys($change_set));
            foreach ($common_properties as $property_name) {
                $change = [$class_name, $entity->getId(), $property_name];
                $change[] = $change_set[$property_name][0];
                $loggable_changes[] = $change;
            }
        }

        return $loggable_changes;
    }

    private function getShortClassName($entity)
    {
        return (new \ReflectionClass($entity))->getShortName();
    }

    public function logChange(string $class_name, $entity_id, string $property_name, $old_value)
    {
        $c = new Change();
        $c->setEntityClass($class_name);
        $c->setEntityId($entity_id);
        $c->setProperty($property_name);
        $c->setOldValue($old_value);
        $this->EM->persist($c);
        $classMetadata = $this->EM->getClassMetadata(Change::class);
        $this->UoW->computeChangeSet($classMetadata, $c);
    }
}