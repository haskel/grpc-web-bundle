## gRPC-Web Bundle

### Installation

```bash
composer require haskel/grpc-web-bundle
```

Add  bundle to `config/bundles.php`:

```php
return [
    // ...
    Haskel\GrpcWebBundle\GrpcWebBundle::class => ['all' => true],
];
```

### Configuration

```yaml
# config/packages/grpc_web.yaml

grpc_web:
  # optional namespace of proto files
  proto_namespace: 'my.somenamespace.api'

  # optional name of response type attribute in request object
  response_type_attribute_name: '_grpc_response_type'
  
  # map of exception classes to grpc standard response codes
  exception_code_map:
    App\Exception\ValidationException: 1
    App\Exception\InvalidArgumentException: 2
  
  # configuration of integration with lexik_jwt_authentication bundle
  security:
    # optional class of success response builder
    success_response_builder: 'App\Security\SuccessResponseBuilder'
    # optional class of failure response builder
    failure_response_builder: 'App\Security\FailureResponseBuilder'
    # required class of sign in request
    sign_in_request_class: 'App\Model\Api\SignInRequest'
```

### Usage

#### Create proto file

```protobuf
syntax = "proto3";

package grpc.api;

service PingService {
    rpc Ping (PingRequest) returns (PingResponse) {}
}

message PingRequest {
    string message = 1;
}

message PingResponse {
    string message = 1;
}
```

#### Generate php code

```bash
protoc --php_out=src --grpc-web_out=mode=grpcwebtext:src --proto_path=config/proto 
```

#### Create controller

```php
<?php

namespace App\Controller\Grpc;

use App\Grpc\Api\PingRequest;
use App\Grpc\Api\PingResponse;
use Haskel\GrpcWebBundle\Attribute as Grpc;

#[Grpc\Service('PingService', package: 'grpc.api')]
class PingService
{
    public function Ping(PingRequest $request): PingResponse
    {
        $message = $request->getMessage();
        
        $response = new PingResponse();
        $response->setMessage("pong:" . $message);
        
        return $response;
    }
}
```





