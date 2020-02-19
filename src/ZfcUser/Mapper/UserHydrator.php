<?php

namespace ZfcUser\Mapper;

use Laminas\Hydrator\ClassMethods;
use ZfcUser\Entity\UserInterface as UserEntityInterface;

class UserHydrator extends ClassMethods implements HydratorInterface
{
    /**
     * Extract values from an object
     *
     * @param UserEntityInterface $object
     * @return array
     * @throws Exception\InvalidArgumentException
     */
    public function extract($object)
    {
        if (!$object instanceof UserEntityInterface) {
            throw new Exception\InvalidArgumentException('$object must be an instance of ZfcUser\Entity\UserInterface');
        }

        /* @var $object UserEntityInterface */
        $data = parent::extract($object);
        if ($data['id'] === null) {
            unset($data['id']);
        }

        return $data;
    }

    /**
     * Hydrate $object with the provided $data.
     *
     * @param  array $data
     * @param  UserEntityInterface $object
     * @return UserInterface
     * @throws Exception\InvalidArgumentException
     */
    public function hydrate(array $data, $object)
    {
        if (!$object instanceof UserEntityInterface) {
            throw new Exception\InvalidArgumentException('$object must be an instance of ZfcUser\Entity\UserInterface');
        }

        $data = $this->mapField('user_id', 'id', $data);

        return parent::hydrate($data, $object);
    }

    /**
     * @param string $keyFrom
     * @param string $keyTo
     * @param array $array
     * @return array
     */
    protected function mapField($keyFrom, $keyTo, array $array)
    {
        if (isset($array[$keyFrom])) {
            $array[$keyTo] = $array[$keyFrom];
        }
        unset($array[$keyFrom]);

        return $array;
    }

    /**
     * Ensure $object is an UserEntity
     *
     * @param  mixed $object
     * @throws Exception\InvalidArgumentException
     */
    protected function guardUserObject($object)
    {
        if (!$object instanceof UserEntity) {
            throw new Exception\InvalidArgumentException(
                '$object must be an instance of ZfcUser\Entity\UserInterface'
            );
        }
    }
}
