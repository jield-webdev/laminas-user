<?php

namespace ZfcUser\Authentication\Adapter;

abstract class AbstractAdapter implements ChainableAdapter
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Check if this adapter is satisfied or not
     *
     * @return bool
     */
    public function isSatisfied()
    {
        $storage = $this->getStorage()->read();
        return (isset($storage['is_satisfied']) && true === $storage['is_satisfied']);
    }

    /**
     * Returns the persistent storage handler
     *
     * Session storage is used by default unless a different storage adapter has been set.
     *
     * @return StorageInterface
     */
    public function getStorage()
    {
        if (null === $this->storage) {
            $this->setStorage(new SessionStorage(get_class($this)));
        }

        return $this->storage;
    }

    /**
     * Sets the persistent storage handler
     *
     * @param StorageInterface $storage
     * @return AbstractAdapter Provides a fluent interface
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * Set if this adapter is satisfied or not
     *
     * @param bool $bool
     * @return AbstractAdapter
     */
    public function setSatisfied($bool = true)
    {
        $storage = $this->getStorage();
        $data    = $storage->read() ?: [];

        $data['is_satisfied'] = (bool)$bool;
        $storage->write($data);

        return $this;
    }
}
