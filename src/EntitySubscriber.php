<?php


namespace Fridde;


use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Fridde\Annotations\Loggable;
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
    /* @var ORM $ORM */
    private $ORM;

    public function __construct(ORM $ORM)
    {
        $this->ORM = $ORM;
    }

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
            Change::TYPE_UPDATE => $this->UoW->getScheduledEntityUpdates(),
            Change::TYPE_INSERTION => $this->UoW->getScheduledEntityInsertions(),
            Change::TYPE_DELETION => $this->UoW->getScheduledEntityDeletions(),
        ];

        foreach ($UoW_entities as $change_type_int => $entity_array) {
            foreach ($entity_array as $entity) {
                if ($change_type_int === Change::TYPE_INSERTION) {
                    continue;
                }
                $class_name = $this->getShortClassName($entity);
                $fqcn = $this->getFQCN($entity);
                $entity_id = $entity->getId();

                if ($change_type_int === Change::TYPE_DELETION) {

                    $change = new Change();
                    $change->setType($change_type_int);
                    $change->setEntityClass($class_name);
                    $change->setEntityId($entity_id);

                    $loggable_changes[] = $change;
                } elseif ($change_type_int === Change::TYPE_UPDATE) {
                    $change_set = $this->UoW->getEntityChangeSet($entity);

                    $loggable_properties = array_filter(
                        array_keys($change_set),
                        function (string $property_name) use ($fqcn) {
                            return $this->isLoggable($fqcn, $property_name);
                        }
                    );

                    foreach ($loggable_properties as $property_name) {
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

    private function isLoggable(string $class, string $property): bool
    {
        return $this->ORM->getAnnotationReader()->hasPropertyAnnotation($class, $property, Loggable::class);
    }

    private function standardizeOldValue($old_value)
    {
        if (is_object($old_value) && method_exists($old_value, 'getId')) {
            return $old_value->getId();
        }

        return $old_value;
    }

    private function getFQCN($entity)
    {
        return (new \ReflectionClass($entity))->getName();
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
