<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace ZfcUser;

use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\ModuleManager\Feature\ControllerPluginProviderInterface;
use Laminas\ModuleManager\Feature\ControllerProviderInterface;
use Laminas\ModuleManager\Feature\ServiceProviderInterface;

class Module implements
    ControllerProviderInterface,
    ControllerPluginProviderInterface,
    ConfigProviderInterface,
    ServiceProviderInterface
{
    public function getConfig($env = null)
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getControllerPluginConfig()
    {
        return array(
            'factories' => array(
                'zfcUserAuthentication' => \ZfcUser\Factory\Controller\Plugin\ZfcUserAuthentication::class,
            ),
        );
    }

    public function getControllerConfig()
    {
        return array(
            'factories' => array(
                'zfcuser' => \ZfcUser\Factory\Controller\UserControllerFactory::class,
            ),
        );
    }

    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'zfcUserDisplayName' => \ZfcUser\Factory\View\Helper\ZfcUserDisplayName::class,
                'zfcUserIdentity' => \ZfcUser\Factory\View\Helper\ZfcUserIdentity::class,
                'zfcUserLoginWidget' => \ZfcUser\Factory\View\Helper\ZfcUserLoginWidget::class,
            ),
        );
    }

    public function getServiceConfig()
    {
        return array(
            'aliases' => array(
                'zfcuser_zend_db_adapter' => \Laminas\Db\Adapter\Adapter::class,
            ),
            'invokables' => array(
                'zfcuser_register_form_hydrator' => \Laminas\Hydrator\ClassMethods::class,
            ),
            'factories' => array(
                'zfcuser_redirect_callback' => \ZfcUser\Factory\Controller\RedirectCallbackFactory::class,
                'zfcuser_module_options' => \ZfcUser\Factory\Options\ModuleOptions::class,
                'ZfcUser\Authentication\Adapter\AdapterChain' => \ZfcUser\Authentication\Adapter\AdapterChainServiceFactory::class,

                // We alias this one because it's ZfcUser's instance of
                // Laminas\Authentication\AuthenticationService. We don't want to
                // hog the FQCN service alias for a Zend\* class.
                'zfcuser_auth_service' => \ZfcUser\Factory\AuthenticationService::class,

                'zfcuser_user_hydrator' => \ZfcUser\Factory\UserHydrator::class,
                'zfcuser_user_mapper' => \ZfcUser\Factory\Mapper\User::class,

                'zfcuser_login_form' => \ZfcUser\Factory\Form\Login::class,
                'zfcuser_register_form' => \ZfcUser\Factory\Form\Register::class,
                'zfcuser_change_password_form' => \ZfcUser\Factory\Form\ChangePassword::class,
                'zfcuser_change_email_form' => \ZfcUser\Factory\Form\ChangeEmail::class,

                'ZfcUser\Authentication\Adapter\Db' => \ZfcUser\Factory\Authentication\Adapter\DbFactory::class,
                'ZfcUser\Authentication\Storage\Db' => \ZfcUser\Factory\Authentication\Storage\DbFactory::class,

                'zfcuser_user_service' => \ZfcUser\Factory\Service\UserFactory::class,
            ),
        );
    }
}
