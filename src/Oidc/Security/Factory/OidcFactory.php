<?php

namespace App\Oidc\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OidcFactory extends AbstractFactory
{
  /**
   * Subclasses must return the id of a service which implements the
   * AuthenticationProviderInterface.
   *
   * @param ContainerBuilder $container
   * @param string           $id             The unique id of the firewall
   * @param array            $config         The options array for this listener
   * @param string           $userProviderId The id of the user provider
   *
   * @return string never null, the id of the authentication provider
   */
  protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
  {
    $providerId = sprintf("%s.%s", $this->getProviderKey(), $id);

    $container
        ->setDefinition($providerId, new ChildDefinition($this->getProviderKey()))
        ->replaceArgument(0, new Reference($userProviderId));

    return $providerId;
  }

  /**
   * Subclasses must return the id of the listener template.
   *
   * Listener definitions should inherit from the AbstractAuthenticationListener
   * like this:
   *
   *    <service id="my.listener.id"
   *             class="My\Concrete\Classname"
   *             parent="security.authentication.listener.abstract"
   *             abstract="true" />
   *
   * In the above case, this method would return "my.listener.id".
   *
   * @return string
   */
  protected function getListenerId()
  {
    return 'security.authentication.listener.oidc';
  }

  /**
   * @return string
   */
  protected function getProviderKey()
  {
    return 'security.authentication.provider.oidc';
  }

  /**
   * Defines the position at which the provider is called.
   * Possible values: pre_auth, form, http, and remember_me.
   *
   * @return string
   */
  public function getPosition()
  {
    return 'http';
  }

  /**
   * Defines the configuration key used to reference the provider
   * in the firewall configuration.
   *
   * @return string
   */
  public function getKey()
  {
    return 'oidc';
  }
}
