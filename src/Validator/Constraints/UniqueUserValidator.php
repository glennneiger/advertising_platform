<?php
/**
 * Unique User validator.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class UniqueUserValidator.
 *
 * @package Validator\Constraints
 */
class UniqueUserValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint->repository) {
            return;
        }

        $result = $constraint->repository->uniqueLogin(
            $value,
            $constraint->userId
        );

        if ($result && count($result)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ user }}', $value)
                ->addViolation();
        }
    }
}
