<?php

namespace Aimeos\MW\Convert\Number;


class DecimalCommaTest extends \PHPUnit\Framework\TestCase
{
	public function testTranslate()
	{
		$object = new \Aimeos\MW\Convert\Number\DecimalComma();

		$this->assertInstanceOf( \Aimeos\MW\Convert\Iface::class, $object );
		$this->assertEquals( '2.00', $object->translate( '2,00' ) );
	}


	public function testReverse()
	{
		$object = new \Aimeos\MW\Convert\Number\DecimalComma();

		$this->assertInstanceOf( \Aimeos\MW\Convert\Iface::class, $object );
		$this->assertEquals( '1,0', $object->reverse( '1.0' ) );
	}
}
