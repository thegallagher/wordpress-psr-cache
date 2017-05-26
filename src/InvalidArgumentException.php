<?php

namespace TheGallagher\WordPressPsrCache;

/**
 * Exception for invalid cache arguments.
 */
class InvalidArgumentException
    extends \InvalidArgumentException
    implements \Psr\Cache\InvalidArgumentException, \Psr\SimpleCache\InvalidArgumentException
{
}