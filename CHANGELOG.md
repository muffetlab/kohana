## [3.4.4](https://github.com/muffetlab/kohana/compare/v3.4.3...v3.4.4) (2025-12-31)


### Bug Fixes

* **Core:** correct PHP version comparison for openssl_encrypt and openssl_decrypt ([5f8318d](https://github.com/muffetlab/kohana/commit/5f8318de54abef0ef7e8897897cd5a5562162303))
* **Core:** fix OpenSSL encryption and decryption to conditionally use AEAD parameters only when a tag is provided ([619b60c](https://github.com/muffetlab/kohana/commit/619b60c991301fdc6ab67e226c8fe6673c5a2996))
* **Minion:** fix include path for index.php ([8c57e92](https://github.com/muffetlab/kohana/commit/8c57e92170e1156ea44b5014116b7ff1a7fa0b1c))
* **ORM:** correct count for has_many through relationship ([7b20a66](https://github.com/muffetlab/kohana/commit/7b20a667b7a1d7cc29ea86bff9da3279a404c7e7))
* **ORM:** correct the update query builder to use only table name ([f1f7eb8](https://github.com/muffetlab/kohana/commit/f1f7eb8e588367ac0912c3244ea7a91d5ca20a6f))
* **ORM:** update user login count by simply incrementing it ([a1cd35b](https://github.com/muffetlab/kohana/commit/a1cd35b8311db4c747c40fe01dc1a74b631661a6))
* **system:** enhance logic to skip introspection for non-existent function in debug backtraces ([85a5b26](https://github.com/muffetlab/kohana/commit/85a5b264d0219cb4ab2f2f5a8110f4434340d3bf))
* **system:** ensure loaded files are treated as arrays in merge operations ([b41e5de](https://github.com/muffetlab/kohana/commit/b41e5de37b1e3f04c87ce5239660113bad8dba1f))
* **system:** return empty array for undefined types instead of false in File::exts_by_mime() ([6a62704](https://github.com/muffetlab/kohana/commit/6a627042bd863b812acf3bb400346b8deafec349))
* **system:** skip rule processing for empty fields earlier ([310f65a](https://github.com/muffetlab/kohana/commit/310f65a160db1e2b08f6d71c23c9c35fa4ca92e3))
* **UnitTest:** correct path constant definitions in bootstrap file ([90d96d3](https://github.com/muffetlab/kohana/commit/90d96d352cb8cc82ae777702f03618087aa33637))


### Features

* add VENDOR_PATH constant for vendor directory ([b9c9048](https://github.com/muffetlab/kohana/commit/b9c90487ba5c30019bcc78b5527c6c607c534d6b))
* **Image:** enforce minimum imagick version 3.6.0 via version check ([08436f3](https://github.com/muffetlab/kohana/commit/08436f38e99da99b51ebec25efacb4adc9994c20))
* replace TestCase::assertTag() and TestCase::assertNotTag() with PHPUnit DOM Assertions ([403f6f5](https://github.com/muffetlab/kohana/commit/403f6f54ac53d99162f69aadad90e53368e89ebb))
* **system:** add VENDOR_PATH constant support to Debug::path() method ([eab0a68](https://github.com/muffetlab/kohana/commit/eab0a68eae49acdd207947e89d33ab8f36f3d916))
* **system:** deprecate legacy Kohana constants and properties ([33afbae](https://github.com/muffetlab/kohana/commit/33afbae4e8111030d5658b65cb64a96e7e68d25d))



## [3.4.3](https://github.com/kilofox/kohana/compare/v3.4.2...v3.4.3) (2025-06-16)


### Bug Fixes

* **public:** add lang attribute to specify English document language ([d2fea1c](https://github.com/kilofox/kohana/commit/d2fea1c4fb6dbcd51610f65450fe7927b037fcb7))
* **Core:** correct parentheses for proper assignment ([e9e56a2](https://github.com/kilofox/kohana/commit/e9e56a29bc58f5dd741bb603cb9531b79a0b2f8f))
* **Core:** optimize Debug output by removing nested pre and code tags ([3b3f13a](https://github.com/kilofox/kohana/commit/3b3f13afc71d2874eba153c640c38e1a3399d59f))
* **Unittest:** fix wrong return types ([d4515f9](https://github.com/kilofox/kohana/commit/d4515f91199ce6a92436f50d061e724fd11dbc62))
* **Userguide:** add alt text to Kohana logo for accessibility ([9e7d3d8](https://github.com/kilofox/kohana/commit/9e7d3d841d0939000f5e8eabaae11ff5b0c50ab9))
* **Userguide:** improve accessibility by linking label to input field ([00bfc9f](https://github.com/kilofox/kohana/commit/00bfc9f7a996f10a27f58dbf9ed2f43ca7a5c3c2))
* **Userguide:** remove obsolete IE9 polyfill script ([e3a539a](https://github.com/kilofox/kohana/commit/e3a539af31700a36cfcdc46094c5cc0857841d9e))


### Performance Improvements

* **Core:** use foreach instead of the "each()" function for better performance ([af20ac8](https://github.com/kilofox/kohana/commit/af20ac86f4dd7e675e698f462fb51a6dd9c14670))



## [3.4.2](https://github.com/kilofox/kohana/compare/v3.4.1...v3.4.2) (2023-08-13)


### Bug Fixes

* **Database:** add order direction check ([7b3e91f](https://github.com/kilofox/kohana/commit/7b3e91f4f82a55fbe80628a62bc9d25d55df404e))



## [3.4.1](https://github.com/kilofox/kohana/compare/v3.4.0...v3.4.1) (2022-11-26)


### Bug Fixes

* **public:** fix "No input file specified" error ([369bb8c](https://github.com/kilofox/kohana/commit/369bb8c353334594e30498b4ae26b8f4c20ae6cd))


### Features

* **public:** simplify the definition of directories ([1b0465e](https://github.com/kilofox/kohana/commit/1b0465e573bca4cebf1408b576b3884d8bec0ea4))



# [3.4.0](https://github.com/kilofox/kohana/compare/v3.3.6...v3.4.0) (2018-12-25)


### Bug Fixes

* fix incompatible exception handling with PHP 7 ([24cbefa](https://github.com/kilofox/kohana/commit/24cbefac1e1c1526cc25f28105cdecf998f7b909))
* **Userguide:** change the data type of Kohana_Kodoc_Markdown::$_toc to array ([58f7aef](https://github.com/kilofox/kohana/commit/58f7aefed9e624b409540d07f7a23e9ee8487f47))


### Features

* **Auth:** remove hash_password() method ([2c974aa](https://github.com/kilofox/kohana/commit/2c974aa00b4b1d00e2dc6d12611bbd7162f113c2))
* **Cache:** add Memcached driver ([b7287ed](https://github.com/kilofox/kohana/commit/b7287ed5946a46efe6c357100b599a47e3cf5d33))
* **Cache:** deprecate the APC and Memcache drivers ([55c0690](https://github.com/kilofox/kohana/commit/55c06901e97780aead55659a3df26496b94e8220))
* **Cache:** deprecate the MemcacheTag driver ([4cfb5f5](https://github.com/kilofox/kohana/commit/4cfb5f5eda41c438e4b9c56d4e5327e6f1867035))
* **Core:** deprecate Kohana::CODENAME constant ([8e07900](https://github.com/kilofox/kohana/commit/8e07900588c58f619d669f153c3a47f5df826a01))
* **Database:** remove MySQL driver ([428ec22](https://github.com/kilofox/kohana/commit/428ec223bd913a32713d748312a5551213e3b560))
* **Encrypt:** deprecate the Mcrypt driver ([a5f5456](https://github.com/kilofox/kohana/commit/a5f54566730823585f9604f3c6bc48acef0052dc))
* **Encrypt:** separate Mcrypt from Encrypt as a driver and implement OpenSSL driver ([bee97eb](https://github.com/kilofox/kohana/commit/bee97ebb162f58c048c01895f1b17d2623ff057e))
* isolate the visible part of an application inside a public directory ([9b794b9](https://github.com/kilofox/kohana/commit/9b794b9ecbaff4683cdb35264178ea6f6d9bbbb5))
* **Security:** remove strip_image_tags() method ([9846cde](https://github.com/kilofox/kohana/commit/9846cde1322b94fb7d6addf7eb0afd7c45b682c0))
* **Validation:** remove as_array() method ([92964d3](https://github.com/kilofox/kohana/commit/92964d38dc5a62f27dcb203b564cdba99fe5baa9))



## [3.3.6](https://github.com/kilofox/kohana/compare/b045d16354375d7b7472734439aefc9ae05e4eb7...v3.3.6) (2018-03-31)


### Reverts

* Revert "Remove auth module" ([0d7cb4c](https://github.com/kilofox/kohana/commit/0d7cb4cdab10552d0c191e2374105e73c28f7ab9))
* Revert "try php 5.4 again for travis" ([6659f64](https://github.com/kilofox/kohana/commit/6659f643dbee8a2eef9e3424803efec6d65e7d62))
* Revert "Updated system tracking to latest kohana/core3.2/develop" ([b045d16](https://github.com/kilofox/kohana/commit/b045d16354375d7b7472734439aefc9ae05e4eb7))



