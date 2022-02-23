<?php
declare(strict_types = 1);

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class jwtAuthorize extends base
{
    public $api;

    function __construct(api $api)
    {
        $this->api = $api;
    }

    /**
     * jwt配置
     * @return Configuration
     */
    private function getConfigure() : Configuration
    {
        return Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::base64Encoded(get_env("jwt.secret"))
        );
    }

    /**
     * 获取token
     * @param int $user_id
     * @param string $field
     * @return string
     */
    public function createToken(int $user_id, string $field = 'member_id') : string
    {
        $configure = $this->getConfigure();
        assert($configure instanceof Configuration);

        $now = new DateTimeImmutable();
        return $configure->builder()
            ->issuedBy(API_DOMAIN_REAL)
            ->permittedFor(API_DOMAIN_REAL)
            ->identifiedBy(sha1($user_id))
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify("+1 day"))
            ->withClaim($field, $user_id)
            ->withClaim('dd', '12222')
            ->withHeader('name', 'test')
            ->getToken($configure->signer(), $configure->signingKey())
            ->toString();
    }


    /**
     * 解析token
     * @param string $token
     * @return array
     */
    public function parseToken(string $token) : array
    {
        $configure = $this->getConfigure();
        $token = $configure->parser()->parse($token);


        return [
            'claims' => $token->claims()->all(),
            'header' => $token->headers()->all()
        ];
    }

}