<?php
/**
 * Admin Role validator.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class AdminRoleValidator.
 *
 * @package Validator\Constraints
 */
class AdminRoleValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint->userId) {
            return;
        }

        if ($constraint->userId == 1 && $value == 2) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ role_id }}', $value)
                ->addViolation();
        }
    }
}
