<?php


namespace Fridde\Annotations;

/**
 * @Annotation
 */
class PostArgs
{
    public $args;

    /**
     * PostArgs constructor.
     * @param
     */
    public function __construct(array $values)
    {
        $values = array_map('trim', explode(',', $values['value']));

        $this->args = $values;
    }

}
