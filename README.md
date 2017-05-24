# see-me

This package is a helper for send API calls to [SeeMe](https://seeme.hu/).

## Install

```

composer require andrewboy/see-me

```

## SeeMeGateway class

### Format types

* 'json'
* 'string'
* 'xml'

### Method types

* 'curl'
* 'file_get_contents'

### Methods

#### __consrtuct

##### params

* *$apiKey:* application key
    * type: string
    * required: true
    
* *$logFileDestination:* set log file destination. If false, log is dismissed
    * type: string|boolean
    * required: false
    * default: false
    
* *$format:* result format
    * type: string
    * required: false
    * default: 'json'
    
* *$method:* set method type
    * type: string
    * required: false
    * default: 'curl'
    
##### return

* void
    
#### setApiKey

##### params

* *$apiKey:* application key
    * type: string
    * required: true
    
##### return

* void
    
#### setFormat

##### params
    
* *$format:* set result format
    * type: string
    * required: true
    
##### return

* void
    
#### setMethod

##### params

* *$method:* set method type
    * type: string
    * required: true
    
##### return

* void
    
#### setLogFileDestination

##### params

* *$logFileDestination*: set log destination. Must be string "destination" or boolean false if we not want to log
    * type: string|boolean
    * required: true
    
##### return

* void
    
#### sendSMS

##### params

* *$number:* mobile number, format: /^36(20|30|31|70)\d{7}$/
    * type: string
    * required: true
    
* *$message:* SMS message
    * type: string
    * required: true
    
* *$sender:* sender id (mobile number)
    * type: string|null
    * required: false
    * default: null (number that you se in the SeeMe admin panel)
    
* *$reference:* 
    * type: string|null
    * required: false
    * default: null
    
* *$callbackParams:* 
    * type: string|null
    * required: false
    * default: null
    
* *$callbackURL:* 
    * type: string|null
    * required: false
    * default: null
    
##### return

* array
    
#### getBalance

##### return

* array

#### setIP

##### params

* *$ip:*
    * type: string
    * required: true
    
##### return

* array

#### getResult

##### return

* array

#### getLog

##### return

* string