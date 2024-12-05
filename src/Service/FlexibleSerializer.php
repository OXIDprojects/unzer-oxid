<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidSolutionCatalysts\Unzer\Service;

use OxidEsales\Eshop\Application\Model\Order;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

class FlexibleSerializer
{
    /**
     * Safely serialize data, handling non-serializable properties.
     *
     * @param mixed $object The data to be serialized.
     * @return string The serialized data as a string.
     */
    public function safeSerialize($object): string
    {
        $serializable = $this->makeSerializable($object);
        return serialize($serializable);
    }

    /**
     * Safely unserialize data, restoring objects of allowed classes.
     *
     * @param string $serialized The serialized data string.
     * @param array $allowedClasses An array of fully qualified class names that are allowed to be unserialized.
     * @return mixed The unserialized data.
     */
    public function safeUnserialize(string $serialized, array $allowedClasses = [])
    {
        $unserializedData = unserialize(
            $serialized,
            [
                'allowed_classes' => array_merge(
                    $allowedClasses,
                    [stdClass::class]
                )
            ]
        );
        return $this->restoreUnserializable($unserializedData, $allowedClasses);
    }

    public function restoreOrderFromStrClass(string $serialized): ?Order
    {
        /** @var object $unserializedData */
        $unserializedData = unserialize(
            $serialized,
            [
                'allowed_classes' => [stdClass::class, Order ::class]
            ]
        );

        /** @var \OxidSolutionCatalysts\Unzer\Model\Order $order */
        $order = $this->getOrderModel();

        /**
         * @var string $value
         */
        foreach (get_object_vars($unserializedData) as $property => $value) {
            if (
                property_exists($order, $property)
                || method_exists($order, 'setFieldData')
            ) {
                $property = str_replace("\0", '', $property);
                if (method_exists($order, 'setFieldData')) {
                    $reflectionMethod = new ReflectionMethod($order, 'setFieldData');
                    if ($reflectionMethod->isPublic()) {
                        $order->setFieldData($property, $value);
                    }
                }
                $order->$property = $value;
            }
        }

        return $order;
    }

    protected function getOrderModel(): Order
    {
        return \oxNew(Order::class);
    }

    /**
     * Convert data into a serializable format, handling objects and resources.
     *
     * @param mixed $data The data to be made serializable.
     * @return mixed The data in a serializable format.
     */
    private function makeSerializable($data)
    {
        if (is_object($data)) {
            $serializable = new stdClass();
            $serializable->__class = get_class($data);
            foreach (get_object_vars($data) as $key => $value) {
                $serializable->$key = $this->makeSerializable($value);
            }
            return $serializable;
        }

        if (is_array($data)) {
            return array_map([$this, 'makeSerializable'], $data);
        }

        if (is_resource($data)) {
            return null;
        }

        return $data;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function restoreUnserializable(mixed $data, array $allowedClasses): mixed
    {
        if (is_array($data)) {
            return array_map(function ($item) use ($allowedClasses) {
                return $this->restoreUnserializable($item, $allowedClasses);
            }, $data);
        }

        if (is_object($data) && isset($data->__class)) {
            $className = $data->__class;
            if ($this->isAllowedClass($className, $allowedClasses)) {
                $restored = new $className();
                foreach (get_object_vars($data) as $key => $value) {
                    if ($key !== '__class') {
                        $key = str_replace("\0", '', $key);
                        $restored->$key = $this->restoreUnserializable($value, $allowedClasses);
                    }
                }
                return $restored;
            }
        }

        if (is_object($data)) {
            $restored = new stdClass();
            foreach (get_object_vars($data) as $key => $value) {
                if ($key !== '__class') {
                    $key = str_replace("\0", '', $key);
                    $restored->$key = $this->restoreUnserializable($value, $allowedClasses);
                }
            }
            return $restored;
        }

        return $data;
    }

    /**
     * Check if a given class is allowed based on the list of allowed classes.
     *
     * @param string $className The name of the class to check.
     * @param array $allowedClasses An array of allowed class names.
     * @return bool True if the class is allowed, false otherwise.
     */
    private function isAllowedClass(string $className, array $allowedClasses): bool
    {
        foreach ($allowedClasses as $allowedClass) {
            if (
                $className === $allowedClass ||
                is_subclass_of($className, $allowedClass) ||
                $this->isClassAlias($className, $allowedClass)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if two class names refer to the same class (are aliases).
     *
     * @param string $className The name of the class to check.
     * @param string $aliasName The name of the potential alias to check against.
     * @return bool True if the classes are aliases, false otherwise.
     */
    private function isClassAlias(string $className, string $aliasName): bool
    {
        if (class_exists($className) && class_exists($aliasName)) {
            return (new ReflectionClass($className))->getName() === (new ReflectionClass($aliasName))->getName();
        }
        return false;
    }
}
