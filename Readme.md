<h1 align="center">
  Hermod
</h1>

<h4 align="center">Simple pre-configured logging and exception handling for Neos</h4>

<p align="center">
  <a href="">📦 Packagist</a> •
  <a href="#how-to-use">How To Use</a> •
  <a href="#Functionality">Functionality</a>
</p>

> [!WARNING]  
> 🚧 This package is still WIP 🚧

## How To Use

Just add these environment variables and you are good to go:

- **`LOKI_URI`**: the URL to your Loki instance
- **`LOKI_USER`**: Username
- **`LOKI_TOKEN`**: Access Token

## Logging Backend

A buffered logging backend.

**Class:** `SJS\Hermod\Log\Backend\LokiBackend`

### Options

| key               | type   | description                                                  |
| ----------------- | ------ | ------------------------------------------------------------ |
| severityThreshold | string |                                                              |
| url               | string | url to your Loki instance. Port included                     |
| user              | string | Username                                                     |
| token             | string | Access token                                                 |
| maxBufferSize     | number | How many logs should be buffered until they get sent to Hermod |
| labels            | array  | _optional_ key value pair of static labels                   |

### Example

_`Configuration/Settings.Neos.Flow.yaml`_

```yaml
Neos:
  Flow:
    log:
      psr3:
        'Neos\Flow\Log\PsrLoggerFactory':
          systemLogger:
            default:
              class: SJS\Hermod\Log\Backend\LokiBackend
              options:
                severityThreshold: "%LOG_DEBUG%"
                url: "%env:LOKI_URI%"
                user: "%env:LOKI_USER%"
                token: "%env:LOKI_TOKEN%"
                maxBufferSize: 300
                labels:
                  target: systemLogger
```

## Exception Handling

**Production Class**: `SJS\Hermod\Handler\ProductionExceptionHandler`

**Debug Class**: `SJS\Hermod\Handler\DebugExceptionHandler`

### Options

The exception handling is split into two parts. The Exception Handler uses the Exception Service to send the data to Hermod.

#### Exception Service

| key    | type   | description                                |
| ------ | ------ | ------------------------------------------ |
| url    | string | url to your Hermod instance. Port included   |
| user   | string | Username                                   |
| token  | string | Access token                               |
| labels | array  | _optional_ key value pair of static labels |

#### Exception Handler

| key                 | type | description                                 |
| ------------------- | ---- | ------------------------------------------- |
| lokiIgnoreException | bool | _optional_ Should the exception be ignored. |

### Example

_`Configuration/Settings.SJS.Hermod.yaml`_

```yaml
SJS:
  Hermod:
    exceptionService:
      url: "%env:LOKI_URI%"
      user: "%env:LOKI_USER%"
      token: "%env:LOKI_TOKEN%"
      labels:
        target: exception
```

_`Configuration/Settings.Neos.Flow.yaml`_

```yaml
Neos:
  Flow:
    error:
      exceptionHandler:
        className: SJS\Hermod\Handler\ProductionExceptionHandler

        defaultRenderingOptions:
          lokiIgnoreException: false

        renderingGroups:
          authenticationRequiredExceptions:
            matchingStatusCodes: [401]
            options:
              lokiIgnoreException: true

          accessDeniedExceptions:
            matchingStatusCodes: [403]
            options:
              lokiIgnoreException: true

```

> [!NOTE]
> The exception handling is based on [Networkteam.SentryClient](https://github.com/networkteam/Networkteam.SentryClient)
