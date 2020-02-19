<?php

namespace ZfcUserTest\Authentication\Adapter;

use Laminas\EventManager\Event;
use ZfcUser\Authentication\Adapter\Db;

class DbTest extends TestCase
{
    const PASSWORD_COST_04 = '04';
    const PASSWORD_COST_10 = '10';

    /**
     * The object to be tested.
     *
     * @var Db
     */
    protected $db;

    /**
     * Mock of AuthEvent.
     *
     * @var \ZfcUser\Authentication\Adapter\AdapterChainEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $authEvent;

    /**
     * Mock of Storage.
     *
     * @var \Laminas\Authentication\Storage\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * Mock of Options.
     *
     * @var \ZfcUser\Options\ModuleOptions|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $options;

    /**
     * Mock of Mapper.
     *
     * @var \ZfcUser\Mapper\UserInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mapper;

    /**
     * @var MockObject
     */
    protected $hydrator;

    /**
     * @var MockObject
     */
    protected $bcrypt;

    /**
     * Mock of User.
     *
     * @var \ZfcUser\Entity\UserInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $user;

    /**
     * Mock of ServiceManager.
     *
     * @var MockObject
     */
    protected $services;

    protected function setUp()
    {
        $storage = $this->getMock('Laminas\Authentication\Storage\Session');
        $this->storage = $storage;

        $authEvent = $this->getMock('ZfcUser\Authentication\Adapter\AdapterChainEvent');
        $this->authEvent = $authEvent;

        $options = $this->getMock('ZfcUser\Options\ModuleOptions');
        $this->options = $options;

        $mapper = $this->getMock('ZfcUser\Mapper\UserInterface');
        $this->mapper = $mapper;

        $user = $this->getMock('ZfcUser\Entity\UserInterface');
        $this->user = $user;

        $this->db = new Db;
        $this->db->setServiceManager($this->services);
        $this->db->setStorage($this->storage);

        $sessionManager = $this->getMock('Laminas\Session\SessionManager');
        \Laminas\Session\AbstractContainer::setDefaultManager($sessionManager);
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::logout
     */
    public function testLogout()
    {
        $this->storage->expects($this->once())
                      ->method('clear');

         $this->db->logout($this->authEvent);
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::Authenticate
     */
    public function testAuthenticateWhenSatisfies()
    {
        $this->authEvent->expects($this->once())
                        ->method('setIdentity')
                        ->with('ZfcUser')
                        ->will($this->returnValue($this->authEvent));
        $this->authEvent->expects($this->once())
                        ->method('setCode')
                        ->with(\Laminas\Authentication\Result::SUCCESS)
                        ->will($this->returnValue($this->authEvent));
        $this->authEvent->expects($this->once())
                        ->method('setMessages')
                        ->with(array('Authentication successful.'))
                        ->will($this->returnValue($this->authEvent));

        $this->storage->expects($this->at(0))
            ->method('read')
            ->will($this->returnValue(array('is_satisfied' => true)));
        $this->storage->expects($this->at(1))
            ->method('read')
            ->will($this->returnValue(array('identity' => 'ZfcUser')));

        $event = new Event(null, $this->authEvent);

        $result = $this->db->authenticate($event);
        $this->assertNull($result);
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::Authenticate
     */
    public function testAuthenticateNoUserObject()
    {
        $this->setAuthenticationCredentials();

        $this->options->expects($this->once())
            ->method('getAuthIdentityFields')
            ->will($this->returnValue(array()));

        $this->authEvent->expects($this->once())
            ->method('setCode')
            ->with(\Laminas\Authentication\Result::FAILURE_IDENTITY_NOT_FOUND)
            ->will($this->returnValue($this->authEvent));
        $this->authEvent->expects($this->once())
            ->method('setMessages')
            ->with(array('A record with the supplied identity could not be found.'))
            ->will($this->returnValue($this->authEvent));

        $this->db->setOptions($this->options);

        $event = new Event(null, $this->authEvent);
        $result = $this->db->authenticate($event);

        $this->assertFalse($result);
        $this->assertFalse($this->db->isSatisfied());
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::Authenticate
     */
    public function testAuthenticationUserStateEnabledUserButUserStateNotInArray()
    {
        $this->setAuthenticationCredentials();
        $this->setAuthenticationUser();

        $this->options->expects($this->once())
            ->method('getEnableUserState')
            ->will($this->returnValue(true));
        $this->options->expects($this->once())
            ->method('getAllowedLoginStates')
            ->will($this->returnValue(array(2, 3)));

        $this->authEvent->expects($this->once())
            ->method('setCode')
            ->with(\Laminas\Authentication\Result::FAILURE_UNCATEGORIZED)
            ->will($this->returnValue($this->authEvent));
        $this->authEvent->expects($this->once())
            ->method('setMessages')
            ->with(array('A record with the supplied identity is not active.'))
            ->will($this->returnValue($this->authEvent));

        $this->user->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(1));

        $this->db->setMapper($this->mapper);
        $this->db->setOptions($this->options);

        $event = new Event(null, $this->authEvent);
        $result = $this->db->authenticate($event);

        $this->assertFalse($result);
        $this->assertFalse($this->db->isSatisfied());
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::Authenticate
     */
    public function testAuthenticateWithWrongPassword()
    {
        $this->setAuthenticationCredentials();
        $this->setAuthenticationUser();

        $this->options->expects($this->once())
            ->method('getEnableUserState')
            ->will($this->returnValue(false));

        $this->bcrypt->expects($this->once())
            ->method('verify')
            ->will($this->returnValue(false));

        $this->authEvent->expects($this->once())
            ->method('setCode')
            ->with(\Laminas\Authentication\Result::FAILURE_CREDENTIAL_INVALID)
            ->will($this->returnValue($this->authEvent));
        $this->authEvent->expects($this->once(1))
            ->method('setMessages')
            ->with(array('Supplied credential is invalid.'));

        $this->db->setMapper($this->mapper);
        $this->db->setOptions($this->options);

        $event = new Event(null, $this->authEvent);
        $result = $this->db->authenticate($event);

        $this->assertFalse($result);
        $this->assertFalse($this->db->isSatisfied());
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::Authenticate
     */
    public function testAuthenticationAuthenticatesWithEmail()
    {
        $this->setAuthenticationCredentials('zfc-user@zf-commons.io');
        $this->setAuthenticationEmail();

        $this->options->expects($this->once())
            ->method('getEnableUserState')
            ->will($this->returnValue(false));

        $this->bcrypt->expects($this->once())
            ->method('verify')
            ->will($this->returnValue(true));
        $this->bcrypt->expects($this->any())
            ->method('getCost')
            ->will($this->returnValue(static::PASSWORD_COST_04));

        $this->user->expects($this->exactly(2))
            ->method('getPassword')
            ->will($this->returnValue('$2a$04$5kq1mnYWbww8X.rIj7eOVOHXtvGw/peefjIcm0lDGxRTEjm9LnOae'));
        $this->user->expects($this->once())
                   ->method('getId')
                   ->will($this->returnValue(1));

        $this->storage->expects($this->any())
                      ->method('getNameSpace')
                      ->will($this->returnValue('test'));

        $this->authEvent->expects($this->once())
                        ->method('setIdentity')
                        ->with(1)
                        ->will($this->returnValue($this->authEvent));
        $this->authEvent->expects($this->once())
                        ->method('setCode')
                        ->with(\Laminas\Authentication\Result::SUCCESS)
                        ->will($this->returnValue($this->authEvent));
        $this->authEvent->expects($this->once())
                        ->method('setMessages')
                        ->with(array('Authentication successful.'))
                        ->will($this->returnValue($this->authEvent));

        $this->db->setMapper($this->mapper);
        $this->db->setOptions($this->options);

        $event = new Event(null, $this->authEvent);
        $result = $this->db->authenticate($event);
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::Authenticate
     */
    public function testAuthenticationAuthenticates()
    {
        $this->setAuthenticationCredentials();
        $this->setAuthenticationUser();

        $this->options->expects($this->once())
             ->method('getEnableUserState')
             ->will($this->returnValue(true));

        $this->options->expects($this->once())
             ->method('getAllowedLoginStates')
             ->will($this->returnValue(array(1, 2, 3)));

        $this->bcrypt->expects($this->once())
            ->method('verify')
            ->will($this->returnValue(true));
        $this->bcrypt->expects($this->any())
            ->method('getCost')
            ->will($this->returnValue(static::PASSWORD_COST_04));

        $this->user->expects($this->exactly(2))
                   ->method('getPassword')
                   ->will($this->returnValue('$2a$04$5kq1mnYWbww8X.rIj7eOVOHXtvGw/peefjIcm0lDGxRTEjm9LnOae'));
        $this->user->expects($this->once())
                   ->method('getId')
                   ->will($this->returnValue(1));
        $this->user->expects($this->once())
                   ->method('getState')
                   ->will($this->returnValue(1));

        $this->storage->expects($this->any())
                      ->method('getNameSpace')
                      ->will($this->returnValue('test'));

        $this->authEvent->expects($this->once())
                        ->method('setIdentity')
                        ->with(1)
                        ->will($this->returnValue($this->authEvent));
        $this->authEvent->expects($this->once())
                        ->method('setCode')
                        ->with(\Laminas\Authentication\Result::SUCCESS)
                        ->will($this->returnValue($this->authEvent));
        $this->authEvent->expects($this->once())
                        ->method('setMessages')
                        ->with(array('Authentication successful.'))
                        ->will($this->returnValue($this->authEvent));

        $this->db->setMapper($this->mapper);
        $this->db->setOptions($this->options);

        $event = new Event(null, $this->authEvent);
        $result = $this->db->authenticate($event);
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::updateUserPasswordHash
     */
    public function testUpdateUserPasswordHashWithSameCost()
    {
        $this->user->expects($this->once())
            ->method('getPassword')
            ->will($this->returnValue('$2a$10$x05G2P803MrB3jaORBXBn.QHtiYzGQOBjQ7unpEIge.Mrz6c3KiVm'));

        $bcrypt = $this->getMock('Laminas\Crypt\Password\Bcrypt');
        $bcrypt->expects($this->once())
            ->method('getCost')
            ->will($this->returnValue(static::PASSWORD_COST_10));

        $this->hydrator->expects($this->never())->method('hydrate');
        $this->mapper->expects($this->never())->method('update');

        $method = new \ReflectionMethod(
            'ZfcUser\Authentication\Adapter\Db',
            'updateUserPasswordHash'
        );
        $method->setAccessible(true);
        $method->invoke($this->db, $this->user, 'ZfcUser', $this->bcrypt);
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::updateUserPasswordHash
     */
    public function testUpdateUserPasswordHashWithoutSameCost()
    {
        $this->user->expects($this->once())
            ->method('getPassword')
            ->will($this->returnValue('$2a$10$x05G2P803MrB3jaORBXBn.QHtiYzGQOBjQ7unpEIge.Mrz6c3KiVm'));

        $bcrypt = $this->getMock('Laminas\Crypt\Password\Bcrypt');
        $bcrypt->expects($this->once())
            ->method('getCost')
            ->will($this->returnValue(static::PASSWORD_COST_04));

        $this->hydrator->expects($this->once())
            ->method('hydrate')
            ->with(array('password' => 'ZfcUserNew'), $this->user)
            ->will($this->returnValue($this->user));

        $this->mapper->expects($this->once())
            ->method('update')
            ->with($this->user);

        $method = new \ReflectionMethod(
            'ZfcUser\Authentication\Adapter\Db',
            'updateUserPasswordHash'
        );
        $method->setAccessible(true);
        $method->invoke($this->db, $this->user, 'ZfcUserNew', $this->bcrypt);
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::preprocessCredential
     * @covers \ZfcUser\Authentication\Adapter\Db::setCredentialPreprocessor
     * @covers \ZfcUser\Authentication\Adapter\Db::getCredentialPreprocessor
     */
    public function testSetValidPreprocessCredential()
    {
        $callable = function () {
            // no-op
        };
        $this->db->setCredentialPreprocessor($callable);
        $this->assertSame($callable, $this->db->getCredentialPreprocessor());
    }

        $this->db->preProcessCredential('ZfcUser');
        $this->assertTrue($testVar);
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::preprocessCredential
     * @covers \ZfcUser\Authentication\Adapter\Db::setCredentialPreprocessor
     * @covers \ZfcUser\Authentication\Adapter\Db::getCredentialPreprocessor
     */
    public function testPreprocessCredentialWithoutCallable()
    {
        $this->db->setCredentialPreprocessor(false);
        $this->assertSame('zfcUser', $this->db->preProcessCredential('zfcUser'));
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::setServiceManager
     * @covers \ZfcUser\Authentication\Adapter\Db::getServiceManager
     */
    public function testGetServiceManager()
    {
        $sm = $this->getMock('Laminas\ServiceManager\ServiceManager');

        $this->db->setServiceManager($sm);

        $serviceManager = $this->db->getServiceManager();

        $this->assertInstanceOf('Laminas\ServiceManager\ServiceLocatorInterface', $serviceManager);
        $this->assertSame($sm, $serviceManager);
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::getOptions
     */
    public function testLazyLoadOptions()
    {
        $serviceMapper = $this->getMock('Laminas\ServiceManager\ServiceManager');
        $serviceMapper->expects($this->once())
            ->method('get')
            ->with('zfcuser_module_options')
            ->will($this->returnValue($this->options));

        $this->db->setServiceManager($serviceMapper);

        $options = $this->db->getOptions();

        $this->assertInstanceOf('ZfcUser\Options\ModuleOptions', $options);
        $this->assertSame($this->options, $options);
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::setOptions
     * @covers \ZfcUser\Authentication\Adapter\Db::getOptions
     */
    public function testSetOptions()
    {
        $options = new \ZfcUser\Options\ModuleOptions;
        $options->setLoginRedirectRoute('zfcUser');

        $this->db->setOptions($options);

        $this->assertInstanceOf('ZfcUser\Options\ModuleOptions', $this->db->getOptions());
        $this->assertSame('zfcUser', $this->db->getOptions()->getLoginRedirectRoute());
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::getMapper
     */
    public function testLazyLoadMapper()
    {
        $serviceMapper = $this->getMock('Laminas\ServiceManager\ServiceManager');
        $serviceMapper->expects($this->once())
            ->method('get')
            ->with('zfcuser_user_mapper')
            ->will($this->returnValue($this->mapper));

        $this->db->setServiceManager($serviceMapper);

        $mapper = $this->db->getMapper();
        $this->assertInstanceOf('ZfcUser\Mapper\UserInterface', $mapper);
        $this->assertSame($this->mapper, $mapper);
    }

    /**
     * @covers \ZfcUser\Authentication\Adapter\Db::setMapper
     * @covers \ZfcUser\Authentication\Adapter\Db::getMapper
     */
    public function testSetMapper()
    {
        $mapper = new \ZfcUser\Mapper\User;
        $mapper->setTableName('zfcUser');

        $this->db->setMapper($mapper);

        $this->assertInstanceOf('ZfcUser\Mapper\User', $this->db->getMapper());
        $this->assertSame('zfcUser', $this->db->getMapper()->getTableName());
    }

    /**
     * @depends testGetServiceManager
     * @covers ZfcUser\Authentication\Adapter\Db::getHydrator
     */
    public function testLazyLoadHydrator()
    {
        $this->assertEquals($this->hydrator, $this->db->getHydrator());
    }

    /**
     * @covers ZfcUser\Authentication\Adapter\Db::setHydrator
     * @covers ZfcUser\Authentication\Adapter\Db::getHydrator
     */
    public function testSetHydrator()
    {
        $this->db->setHydrator($this->hydrator);
        $this->assertSame($this->hydrator, $this->db->getHydrator());
    }

    protected function setAuthenticationEmail()
    {
        $this->mapper->expects($this->once())
            ->method('findByEmail')
            ->with('zfc-user@zf-commons.io')
            ->will($this->returnValue($this->user));

        $this->options->expects($this->once())
            ->method('getAuthIdentityFields')
            ->will($this->returnValue(array('email')));
    }

    protected function setAuthenticationUser()
    {
        $this->mapper->expects($this->once())
            ->method('findByUsername')
            ->with('ZfcUser')
            ->will($this->returnValue($this->user));

        $this->options->expects($this->once())
            ->method('getAuthIdentityFields')
            ->will($this->returnValue(array('username')));
    }

    protected function setAuthenticationCredentials($identity = 'ZfcUser', $credential = 'ZfcUserPassword')
    {
        $this->storage->expects($this->at(0))
            ->method('read')
            ->will($this->returnValue(array('is_satisfied' => false)));

        $post = $this->getMock('Laminas\Stdlib\Parameters');
        $post->expects($this->at(0))
            ->method('get')
            ->with('identity')
            ->will($this->returnValue($identity));
        $post->expects($this->at(1))
            ->method('get')
            ->with('credential')
            ->will($this->returnValue($credential));

        $request = $this->getMock('Laminas\Http\Request');
        $request->expects($this->exactly(2))
            ->method('getPost')
            ->will($this->returnValue($post));

        $this->authEvent->expects($this->exactly(2))
            ->method('getRequest')
            ->will($this->returnValue($request));
    }
}
