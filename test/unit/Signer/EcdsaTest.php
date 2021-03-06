<?php
declare(strict_types=1);

namespace Lcobucci\JWT\Signer;

use Lcobucci\JWT\Signer\Ecdsa\BaseTestCase;
use Mdanter\Ecc\Crypto\Key\PrivateKeyInterface;
use Mdanter\Ecc\Crypto\Key\PublicKeyInterface;
use function gmp_init;

final class EcdsaTest extends BaseTestCase
{
    private function getSigner(): Ecdsa
    {
        $signer = $this->getMockForAbstractClass(
            Ecdsa::class,
            [$this->adapter, $this->keyParser]
        );

        $signer->method('getAlgorithm')
               ->willReturn('sha256');

        $signer->method('getAlgorithmId')
               ->willReturn('ES256');

        return $signer;
    }

    /**
     * @test
     *
     * @covers \Lcobucci\JWT\Signer\Ecdsa::__construct
     */
    public function constructShouldConfigureDependencies(): void
    {
        $signer = $this->getSigner();

        self::assertAttributeSame($this->adapter, 'adapter', $signer);
    }

    /**
     * @test
     *
     * @uses \Lcobucci\JWT\Signer\Ecdsa::__construct
     * @uses \Lcobucci\JWT\Signer\Key
     *
     * @covers \Lcobucci\JWT\Signer\Ecdsa::sign
     */
    public function signShouldReturnAHashUsingPrivateKey(): void
    {
        $signer      = $this->getSigner();
        $key         = new Key('testing');
        $privateKey  = $this->createMock(PrivateKeyInterface::class);
        $signingHash = gmp_init(10, 10);

        $this->keyParser->expects($this->once())
                      ->method('getPrivateKey')
                      ->with($key)
                      ->willReturn($privateKey);

        $this->adapter->expects($this->once())
                      ->method('createSigningHash')
                      ->with('testing', 'sha256')
                      ->willReturn($signingHash);

        $this->adapter->expects($this->once())
                      ->method('createHash')
                      ->with($privateKey, $signingHash)
                      ->willReturn('string');

        self::assertInternalType('string', $signer->sign('testing', $key));
    }

    /**
     * @test
     *
     * @uses \Lcobucci\JWT\Signer\Ecdsa::__construct
     * @uses \Lcobucci\JWT\Signer\Key
     *
     * @covers \Lcobucci\JWT\Signer\Ecdsa::verify
     */
    public function verifyShouldDelegateToEcdsaSignerUsingPublicKey(): void
    {
        $signer      = $this->getSigner();
        $key         = new Key('testing');
        $publicKey   = $this->createMock(PublicKeyInterface::class);
        $signingHash = gmp_init(10, 10);

        $this->keyParser->expects($this->once())
                        ->method('getPublicKey')
                        ->with($key)
                        ->willReturn($publicKey);

        $this->adapter->expects($this->once())
                     ->method('createSigningHash')
                     ->with('testing2', 'sha256')
                     ->willReturn($signingHash);

        $this->adapter->expects($this->once())
                      ->method('verifyHash')
                      ->with('testing', $publicKey, $signingHash)
                      ->willReturn(true);

        self::assertTrue($signer->verify('testing', 'testing2', $key));
    }
}
