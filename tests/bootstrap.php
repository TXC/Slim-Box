<?php

require __DIR__ . '/../vendor/autoload.php';

putenv('ENVIRONMENT=' . getenv('ENVIRONMENT') ?: 'test');
