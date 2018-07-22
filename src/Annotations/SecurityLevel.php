<?php


namespace Fridde\Annotations;

use Fridde\Security\Authorizer;

/**
 * @Annotation
 */
class SecurityLevel
{
    public $value;

    public const ACCESS_ALL = Authorizer::ACCESS_ALL;
    public const ACCESS_ALL_EXCEPT_GUEST = Authorizer::ACCESS_ALL_EXCEPT_GUEST;
    public const ACCESS_ADMIN_ONLY = Authorizer::ACCESS_ADMIN_ONLY;

    /**
     * SecurityLevel constructor.
     * @param $value
     */
    public function __construct($value)
    {
        $this->value = $value['value'];
    }


}
