<?php
/**
 * Unique User constraint.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueUser.
 *
 * @package Validator\Constraints
 */
class UniqueUser extends Constraint
{
    /**
     * Message.
     *
     * @var string $message
     */
    public $message = 'login.in.usage';
    /**
     * Element id.
     *
     * @var int|string|null $elementId
     */
    public $userId = null;

    /**
     * User repository.
     *
     * @var null|\Repository\UserRepository $repository
     */
    public $repository = null;
}
