<?php

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\RestBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Exception\Exception as SymfonySerializerException;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use JMS\Serializer\Exception\UnsupportedFormatException;
use JMS\Serializer\Exception\Exception as JMSSerializerException;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;
use FOS\Rest\Util\Codes;

/**
 * @author Tyler Stroud <tyler@tylerstroud.com>
 */
class RequestBodyParamConverter implements ParamConverterInterface
{
    /**
     * @var object
     */
    protected $serializer;

    /**
     * @param object $serializer
     */
    public function __construct($serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(Request $request, ConfigurationInterface $configuration)
    {
        $context = null;
        $options = $configuration->getOptions();
        if (isset($options['serializationContext']) && is_array($options['serializationContext'])) {
            if ($this->serializer instanceof SerializerInterface) {
                $context = $this->configureDeserializationContext(
                    $this->getDeserializationContext(),
                    $options['serializationContext']
                );
            } elseif ($this->serializer instanceof SymfonySerializerInterface) {
                $context = $options['serializationContext'];
            }
        }

        try {
            $object = $this->serializer->deserialize(
                $request->getContent(),
                $configuration->getClass(),
                $request->getContentType(),
                $context
            );
        } catch (UnsupportedFormatException $e) {
            throw new HttpException(Codes::HTTP_UNSUPPORTED_MEDIA_TYPE, $e->getMessage());
        } catch (JMSSerializerException $e) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $e->getMessage());
        } catch (SymfonySerializerException $e) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $e->getMessage());
        }

        $request->attributes->set($configuration->getName(), $object);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(ConfigurationInterface $configuration)
    {
        return null !== $configuration->getClass();
    }

    /**
     * @return DeserializationContext
     */
    protected function getDeserializationContext()
    {
        return DeserializationContext::create();
    }

    /**
     * @param DeserializationContext $context
     * @param array                  $options
     *
     * @return DeserializationContext
     */
    protected function configureDeserializationContext(DeserializationContext $context, array $options)
    {
        if (isset($options['groups'])) {
            $context->setGroups($options['groups']);
        }
        if (isset($options['version'])) {
            $context->setVersion($options['version']);
        }

        return $context;
    }
}
