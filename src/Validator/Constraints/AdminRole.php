<?php
/**
 * Admin Role constraint.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class AdminRole.
 *
 * @package Validator\Constraints
 */
class AdminRole extends Constraint
{
    /**
     * Message.
     *
     * @var string $message
     */
    public $message = 'cant.resign';
    /**
     * Element id.
     *
     * @var int|string|null $elementId
     */
    public $userId = null;
}
