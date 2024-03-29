<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2021
 * @package Admin
 * @subpackage JsonAdm
 */


namespace Aimeos\Admin;


/**
 * Factory which can create all JSON API clients
 *
 * @package Admin
 * @subpackage JsonAdm
 */
class JsonAdm extends \Aimeos\Admin\JsonAdm\Common\Factory\Base
{
	/**
	 * Creates the required client specified by the given path of client names.
	 *
	 * Clients are created by providing only the domain name, e.g. "product"
	 *  for the \Aimeos\Admin\JsonAdm\Product\Standard or a path of names to
	 * retrieve a specific sub-client, e.g. "product/type" for the
	 * \Aimeos\Admin\JsonAdm\Product\Type\Standard client.
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object required by clients
	 * @param \Aimeos\Bootstrap $aimeos Aimeos Bootstrap object
	 * @param string $path Name of the client separated by slashes, e.g "product/property"
	 * @param string|null $name Name of the client implementation ("Standard" if null)
	 * @return \Aimeos\Admin\JsonAdm\Iface JSON admin instance
	 * @throws \Aimeos\Admin\JsonAdm\Exception If the given path is invalid
	 */
	public static function create( \Aimeos\MShop\Context\Item\Iface $context,
		\Aimeos\Bootstrap $aimeos, string $path, string $name = null ) : \Aimeos\Admin\JsonAdm\Iface
	{
		$path = trim( $path, '/' );

		if( empty( $path ) ) {
			return self::createRoot( $context, $aimeos, $path, $name );
		}

		return self::createNew( $context, $aimeos, $path, $name );
	}


	/**
	 * Creates a new client specified by the given path of client names.
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object required by clients
	 * @param \Aimeos\Bootstrap $aimeos Aimeos Bootstrap object
	 * @param string $path Name of the client separated by slashes, e.g "product/stock"
	 * @param string|null $name Name of the client implementation ("Standard" if null)
	 * @return \Aimeos\Admin\JsonAdm\Iface JSON admin instance
	 * @throws \Aimeos\Admin\JsonAdm\Exception If the given path is invalid
	 */
	protected static function createNew( \Aimeos\MShop\Context\Item\Iface $context,
		\Aimeos\Bootstrap $aimeos, string $path, string $name = null ) : \Aimeos\Admin\JsonAdm\Iface
	{
		$pname = $name;
		$parts = explode( '/', $path );

		foreach( $parts as $key => $part )
		{
			if( ctype_alnum( $part ) === false )
			{
				$msg = sprintf( 'Invalid client "%1$s"', $path );
				throw new \Aimeos\Admin\JsonAdm\Exception( $msg, 400 );
			}

			$parts[$key] = ucwords( $part );
		}

		if( $pname === null ) {
			$pname = $context->getConfig()->get( 'admin/jsonadm/' . $path . '/name', 'Standard' );
		}

		$view = $context->view();
		$config = $context->getConfig();

		if( $view->access( $config->get( 'admin/jsonadm/resource/' . $path . '/groups', [] ) ) !== true ) {
			throw new \Aimeos\Admin\JQAdm\Exception( sprintf( 'Not allowed to access JsonAdm "%1$s" client', $path ) );
		}


		$view = $context->view();
		$iface = \Aimeos\Admin\JsonAdm\Iface::class;
		$classname = '\\Aimeos\\Admin\\JsonAdm\\' . join( '\\', $parts ) . '\\' . $pname;

		if( ctype_alnum( $pname ) === false ) {
			throw new \Aimeos\Admin\JsonAdm\Exception( sprintf( 'Invalid class name "%1$s"', $classname ) );
		}

		if( class_exists( $classname ) === false ) {
			return self::createRoot( $context, $aimeos, $path, $name );
		}

		$client = self::createAdmin( $classname, $iface, $context, $path );
		$client = self::addClientDecorators( $client, $context, $path );

		return $client->setAimeos( $aimeos )->setView( $view );
	}


	/**
	 * Creates the top level client
	 *
	 * @param \Aimeos\MShop\Context\Item\Iface $context Context object required by clients
	 * @param \Aimeos\Bootstrap $aimeos Aimeos Bootstrap object
	 * @param string $path Name of the client separated by slashes, e.g "product/property"
	 * @param string|null $name Name of the JsonAdm client (default: "Standard")
	 * @return \Aimeos\Admin\JsonAdm\Iface JSON admin instance
	 * @throws \Aimeos\Admin\JsonAdm\Exception If the client couldn't be created
	 */
	protected static function createRoot( \Aimeos\MShop\Context\Item\Iface $context,
		\Aimeos\Bootstrap $aimeos, string $path, string $name = null ) : \Aimeos\Admin\JsonAdm\Iface
	{
		/** admin/jsonadm/name
		 * Class name of the used JSON API client implementation
		 *
		 * Each default JSON API client can be replace by an alternative imlementation.
		 * To use this implementation, you have to set the last part of the class
		 * name as configuration value so the client factory knows which class it
		 * has to instantiate.
		 *
		 * For example, if the name of the default class is
		 *
		 *  \Aimeos\Admin\JsonAdm\Standard
		 *
		 * and you want to replace it with your own version named
		 *
		 *  \Aimeos\Admin\JsonAdm\Mycntl
		 *
		 * then you have to set the this configuration option:
		 *
		 *  admin/jsonadm/name = Mycntl
		 *
		 * The value is the last part of your own class name and it's case sensitive,
		 * so take care that the configuration value is exactly named like the last
		 * part of the class name.
		 *
		 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
		 * characters are possible! You should always start the last part of the class
		 * name with an upper case character and continue only with lower case characters
		 * or numbers. Avoid chamel case names like "MyCntl"!
		 *
		 * @param string Last part of the class name
		 * @since 2015.12
		 * @category Developer
		 */
		if( $name === null ) {
			$name = $context->getConfig()->get( 'admin/jsonadm/name', 'Standard' );
		}

		$view = $context->view();
		$iface = '\\Aimeos\\Admin\\JsonAdm\\Iface';
		$classname = '\\Aimeos\\Admin\\JsonAdm\\' . $name;

		if( ctype_alnum( $name ) === false ) {
			throw new \Aimeos\Admin\JsonAdm\Exception( sprintf( 'Invalid class name "%1$s"', $classname ) );
		}

		$client = self::createAdmin( $classname, $iface, $context, $path );

		/** admin/jsonadm/decorators/excludes
		 * Excludes decorators added by the "common" option from the JSON API clients
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to remove a decorator added via
		 * "admin/jsonadm/common/decorators/default" before they are wrapped
		 * around the Jsonadm client.
		 *
		 *  admin/jsonadm/decorators/excludes = array( 'decorator1' )
		 *
		 * This would remove the decorator named "decorator1" from the list of
		 * common decorators ("\Aimeos\Admin\JsonAdm\Common\Decorator\*") added via
		 * "admin/jsonadm/common/decorators/default" for the JSON API client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jsonadm/common/decorators/default
		 * @see admin/jsonadm/decorators/global
		 * @see admin/jsonadm/decorators/local
		 */

		/** admin/jsonadm/decorators/global
		 * Adds a list of globally available decorators only to the Jsonadm client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap global decorators
		 * ("\Aimeos\Admin\Jsonadm\Common\Decorator\*") around the Jsonadm
		 * client.
		 *
		 *  admin/jsonadm/product/decorators/global = array( 'decorator1' )
		 *
		 * This would add the decorator named "decorator1" defined by
		 * "\Aimeos\Admin\Jsonadm\Common\Decorator\Decorator1" only to the
		 * "product" Jsonadm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jsonadm/common/decorators/default
		 * @see admin/jsonadm/decorators/excludes
		 * @see admin/jsonadm/decorators/local
		 */

		/** admin/jsonadm/decorators/local
		 * Adds a list of local decorators only to the Jsonadm client
		 *
		 * Decorators extend the functionality of a class by adding new aspects
		 * (e.g. log what is currently done), executing the methods of the underlying
		 * class only in certain conditions (e.g. only for logged in users) or
		 * modify what is returned to the caller.
		 *
		 * This option allows you to wrap local decorators
		 * ("\Aimeos\Admin\Jsonadm\Product\Decorator\*") around the Jsonadm
		 * client.
		 *
		 *  admin/jsonadm/product/decorators/local = array( 'decorator2' )
		 *
		 * This would add the decorator named "decorator2" defined by
		 * "\Aimeos\Admin\Jsonadm\Product\Decorator\Decorator2" only to the
		 * "product" Jsonadm client.
		 *
		 * @param array List of decorator names
		 * @since 2016.01
		 * @category Developer
		 * @see admin/jsonadm/common/decorators/default
		 * @see admin/jsonadm/decorators/excludes
		 * @see admin/jsonadm/decorators/global
		 */

		$client = self::addClientDecorators( $client, $context, $path );

		return $client->setAimeos( $aimeos )->setView( $view );
	}
}
