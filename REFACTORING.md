# ZF3 Refactoring Proposal

With the version switch to 3 it's a good time to refactor
the Di component with possible BC breaks (much like the proposed ServiceManager refactoring).

## Goals

### Code cleanup

  * The ZF2 code has duplications (namingly the compiler and runtime definition)
  * The resolver code is uneccessary complex and should be put into a separate implementation
  * The InstanceManager should bereplaced by the service locator interface
  * The service locator should be exchangible (by any Interop\ContainerInterface)

### Add support for generating an optimized di container

This should generate a container and injectors by utilizing the
known class definitions and instance configuration.
The DependencyInjector would then at first attempt to use the generated
injector for the particular type and fall back to the default routines.

### Refactor ServiceLocator and ServiceLocatorInterface

The current ones are most likely never used in the real world or by Di.
This should be integrated better and be used for looking up instances.

### Make it a ZF3 Module

This component should easily integrate by adding it as a ZF-Module to the application config. This approach
will make it possible to remove dependencies from Mvc or other components.

So people can decide whether they stay with ServiceManager only or combine it with Zend\Di.

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

Since the ServiceManager in ZF3 now uses Interop\ContainerInterface, we can pass the ServiceManager
for retrieving the instances the dependency injector needs.

When Using the Module approach above, Di can register an abstract service factory to handle class instanciation.

### Change the parameters array role

While the concept of providing complex parameters to a service locator (or its factories) is
fine for the ServiceManager, but it's questionable for dependency injection.

Mapping a parameter hash is not only a pain to resolve (especially for nested dependencies),
they also make things uneccessarily complicated.

To remove this complexity and allow faster instanciation the provided parameters array will only be used for
the instanciation method of the requested class. It will not be passed to any other methods and/or newly instanciated dependencies.
