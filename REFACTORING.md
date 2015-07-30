# ZF3 Refactoring Proposal

With the version switch to 3 it's a good time to refactor
the Di component with possible BC breaks (much like the proposed ServiceManager refactoring).

## Goals

### Code cleanup

  * The ZF2 code has duplications (namingly the compiler and runtime definition)
  * The resolver code is uneccessary complex and should be put into a separate implementation
  * The InstanceManager should be interfaced and be exchangeible

### Add support for property injections

  * Use PHP 7 Typehints generating the definition.
  * Should fallback to @var annotations
  * Properties will be optional unless they're annotated with @Inject
  * Only (single)typed properties are injectable

### Add support for generating an optimized di container

This should generate a container and injectors by utilizing the
known class definitions and instance configuration.
The DependencyInjector would then at first attempt to use the generated
injector for the particular type and fall back to the default routines.

### Remove ServiceLocator and ServiceLocatorInterface

These are not only leading to problems/ambugiouties with IDEs, they're also
more part of the ServiceManager domain.

The current ones are most likely never used in the real world.

### Better integration with ServiceManager

The ZF2 integration of Di into the ServiceManager/Mvc is using an odd factory
that lacks the capability to inject already configured services into deeper
nested dependencies which makes this integration quite useless.

Example:

  * Class Foo
    - Depends on Zend\Db\Adapter\AdapterInterface
    - Depends on Bar
      + Depends on Zend\Authentication\AuthenticationServiceInterface

Assuming the service manager has configured the Zend classes as services:

While the DB Adapter for Foo is taken from the ServiceManager, the auth service
dependency for Bar is attempted to be created purely via Di. All reference to the
ServiceManager is lost.

By making the InstanceManager exchangable and change its importance it could be
replaced with a Zend\ServiceManager\ServiceLocator aware implementation where the
instatiation handling is delegated to the service manager (Which might fire back
to the DependencyInjector for undefined services).

### Remove parameters array

While the concept of providing parameters to a service locator (or its factories) is
fine for the ServiceManager, it's questionable for dependency injection.

Mapping a parameter hash is not only a pain to resolve (especially for nested dependencies),
they also make things uneccessarily complicated.

The better approach are instance configs and aliases. If the params flexibility
is required, a service factory and the ServiceManager would be the better fit
then trying to guess what the consumer actually intended to inject at runtime.
