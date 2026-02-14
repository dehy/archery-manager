<?php

declare(strict_types=1);

namespace App\Service;

use FriendlyCaptcha\SDK\Client;
use FriendlyCaptcha\SDK\ClientConfig;
use Psr\Log\LoggerInterface;

class FriendlyCaptchaService
{
    private readonly Client $client;

    public function __construct(
        private readonly string $siteKey,
        private readonly string $secret,
        private readonly LoggerInterface $logger,
    ) {
        $config = new ClientConfig();
        $config->setAPIKey($this->secret);
        $config->setSitekey($this->siteKey);

        $this->client = new Client($config);
    }

    /**
     * Verify a Friendly Captcha solution.
     *
     * @param string $solution The solution token from the captcha widget
     *
     * @return bool True if verification successful, false otherwise
     */
    public function verify(string $solution): bool
    {
        if ('' === $solution || '0' === $solution) {
            $this->logger->warning('Empty CAPTCHA solution provided');

            return false;
        }

        try {
            $result = $this->client->verifyCaptchaResponse($solution);

            if ($result->wasAbleToVerify() && $result->shouldAccept()) {
                $this->logger->info('CAPTCHA verification successful');

                return true;
            }

            $this->logger->warning('CAPTCHA verification failed', [
                'able_to_verify' => $result->wasAbleToVerify(),
                'should_accept' => $result->shouldAccept(),
            ]);
        } catch (\Exception $exception) {
            $this->logger->error('CAPTCHA verification exception', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return false;
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }
}
