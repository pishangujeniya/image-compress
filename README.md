# [image-compress](http://image-compress.herokuapp.com)
Image Compressor Web + API

### API Usage Guidelines

API URL : https://image-compress.herokuapp.com/image-compress.php

Request : POST

| KEYNAME | TYPE | VALUE |
| ------ | ------ | ------ |
| delete_dirs | string | false |
| secret_key | string | coffee |
| image_link | string | https://www.google.com/logos/doodles/2019/holi-2019-5084538337230848.2-l.png |
| quality | int | 60 |
| debug_mode | string | false |
| want_binary | string | true |


* ```delete_dirs``` : false = Won't delete dls & compressed directories on server.
* ```secret_key``` : secret key required to execute.
* ```image_link``` : image link to compress.
* ```quality``` : 60 = quality in integer between 1 to 100 (more the number , better the quality and less compression).
* ```debug_mode``` : false = echo errors & warnings || true = won't echo errors & warnings.
* ```want_binary``` : false = response json with base64 encoded compressed image || true = response image type with binary image data.

### Tools

| Built with | Download Link |
| ------ | ------ |
| Visual Studio Code | [Download](https://code.visualstudio.com/) |
| Php | [Download](http://php.net/) |

### Deploy on Heroku Guidelines

* [Download heroku cli](https://devcenter.heroku.com/articles/heroku-cli)
* ```heroku login```
* ```heroku git:clone -a image-compress```
* ```cd image-compress```
* ```git config --global user.email "email@example.com"```
* ```git config --global user.name "your_name"```
* ```git add .```
* ```git commit -am "make it better"```
* ```git push heroku master```


### Star it

Loved? Star it !
