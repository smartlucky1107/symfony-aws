<?php

namespace App\DataTransformer;

use App\Exception\AppException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AppTransformer
{
    /** @var ValidatorInterface */
    private $validator;

    /**
     * AppTransformer constructor.
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param $entity
     * @return bool
     * @throws AppException
     */
    public function validateForm($entity){
        $errors = $this->validator->validate($entity);
        if (count($errors) > 0) {
            $message = [];
            /** @var ConstraintViolation $e */
            foreach ($errors as $e) {
                $message[$e->getPropertyPath()] = $e->getMessage();
            }
            throw new AppException(json_encode($message));
        }

        return true;
    }

    /**
     * @param $entity
     * @return bool
     * @throws AppException
     */
    public function validate($entity)
    {
        $errors = $this->validator->validate($entity);
        if (count($errors) > 0) {
            /** @var ConstraintViolation $e */
            foreach ($errors as $e) {
                throw new AppException($e->getMessage().'['.$e->getPropertyPath().']');
            }
        }

        return true;
    }
}
