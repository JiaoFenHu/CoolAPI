<?php
declare(strict_types=1);

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\ConstraintViolation;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Token;

class jwtAuthorize extends base
{
    private $signer;
    private $key;

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
        $this->signer = new Sha256();
        $this->key = InMemory::base64Encoded(get_env("jwt.secret"));
        return Configuration::forSymmetricSigner(
            $this->signer,
            $this->key
        );
    }

    /**
     * 获取token
     * @param array $claims
     * @param int $user_id
     * @return string
     */
    public function createToken(array $claims, int $user_id): string
    {
        $configure = $this->getConfigure();
        $this->api->member_id = $user_id;

        $now = new DateTimeImmutable();
        $token = $configure->builder()
            ->issuedBy(API_DOMAIN_REAL)
            ->permittedFor(API_DOMAIN_REAL)
            ->identifiedBy(sha1((string)$user_id))
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify("+1 day"));

        foreach ($claims as $field => $value) {
            $token->withClaim($field, $value);
        }
        return $token->getToken($configure->signer(), $configure->signingKey())->toString();
    }


    /**
     * 解析token
     * @param string $token
     * @return array
     */
    public function parseToken(string $token): array
    {
        $configure = $this->getConfigure();
        $token = $configure->parser()->parse($token);
        return $token->claims()->all();
    }

    /**
     * 验证token是否有效
     * @param string $token
     * @return bool
     */
    public function verifyToken(string $token)
    {
        try {
            $configure = $this->getConfigure();
            $token = $configure->parser()->parse($token);

            $this->api->member_id = $token->claims()->get('member_id');

            // 以下验证token各项值是否符合签发的值代码
            $this->checkTokenSignedWith($token);
            $this->checkTokenTimeValid(
                $token->claims()->get('nbf'),
                $token->claims()->get('exp')
            );
            $this->checkTokenClaims($token->claims()->get('iss'), 'iss');
            $this->checkTokenClaims($token->claims()->get('aud'), 'aud');
            $this->checkTokenClaims($token->claims()->get('jti'), 'jti');

        } catch (ConstraintViolation $e) {
            $this->api->outputResponseError($e->getMessage());
        } catch (CannotDecodeContent $e) {
            $this->api->outputResponseError("身份验证失败，您正在非法操作，已记录您的IP！");
        }
        return true;
    }

    /**
     * 验证签发(iss)，受众(aud)，唯一编号(jti)
     * @param $value
     * @param string $mode
     * @return bool
     */
    private function checkTokenClaims($value, string $mode)
    {
        switch ($mode) {
            case 'iss':
                if ($value !== API_DOMAIN_REAL) {
                    throw new ConstraintViolation("身份验证失败，签发人异常！");
                }
                break;
            case 'aud':
                if (!in_array(API_DOMAIN_REAL, $value)) {
                    throw new ConstraintViolation("身份验证失败，访问服务身份异常！");
                }
                break;
            case 'jti':
                if ($value !== sha1((string)$this->api->member_id)) {
                    throw new ConstraintViolation("身份验证失败，身份编号异常！");
                }
                break;
        }
        return true;
    }

    /**
     * 验证token的有效范围内
     * @param DateTimeImmutable $s_dt
     * @param DateTimeImmutable $e_dt
     */
    private function checkTokenTimeValid(DateTimeImmutable $s_dt, DateTimeImmutable $e_dt)
    {
        $begin_time = $s_dt->getTimestamp();
        $end_time = $e_dt->getTimestamp();
        $now = time();
        if ($now < $begin_time) {
            throw new ConstraintViolation("身份验证异常，不在有效时间范围！");
        }
        if ($now >= $end_time) {
            throw new ConstraintViolation("身份验证超时，请重新登录！");
        }
    }

    /**
     * 验证token签名
     * @param Token $token
     */
    private function checkTokenSignedWith(Token $token)
    {
        if ($token->headers()->get('alg') !== $this->signer->getAlgorithmId()) {
            throw new ConstraintViolation('身份验证失败，签名算法异常！');
        }

        if (!$this->signer->verify((string)$token->signature(), $token->getPayload(), $this->key)) {
            throw new ConstraintViolation('身份验证失败，签名错误！');
        }
    }

}