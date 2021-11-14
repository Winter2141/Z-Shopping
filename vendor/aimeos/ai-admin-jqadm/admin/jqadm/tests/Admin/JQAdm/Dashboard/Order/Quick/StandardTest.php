<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */


namespace Aimeos\Admin\JQAdm\Dashboard\Order\Quick;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		$this->view = \TestHelperJqadm::view();
		$this->context = \TestHelperJqadm::getContext();

		$this->object = new \Aimeos\Admin\JQAdm\Dashboard\Order\Quick\Standard( $this->context );
		$this->object = new \Aimeos\Admin\JQAdm\Common\Decorator\Page( $this->object, $this->context );
		$this->object->setAimeos( \TestHelperJqadm::getAimeos() );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->view, $this->context );
	}


	public function testSearch()
	{
		$result = $this->object->search();

		$this->assertStringContainsString( 'dashboard-order-quick', $result );
	}


	public function testGetSubClient()
	{
		$result = $this->object->getSubClient( 'counttotal' );
		$this->assertInstanceOf( \Aimeos\Admin\JQAdm\Iface::class, $result );
	}
}
