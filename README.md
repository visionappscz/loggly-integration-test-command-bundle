# Loggly Integration Test Command Bundle

The Loggly Integration Test Bundle provides commands for listing and testing of loggly monolog handlers integration in symfony projects.

## Getting Started

### Installing

Install with composer
```
require visionappscz/loggly-integration-test-command-bundle
```

Add bundle to AppKernel
```
// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new LogglyIntegrationTestCommandBundle\LogglyIntegrationTestCommandBundle(),
        ];

        // ...

        return $bundles;
    }
```

### Usage
```
loggly:handlers
loggly:test [handler|all [severity|all]]
```
