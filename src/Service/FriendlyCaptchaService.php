<?php

declare(strict_types=1);

namespace App\Service;

use FriendlyCaptcha\SDK\Client;
use FriendlyCaptcha\SDK\ClientConfig;
use Psr\Log\LoggerInterface;

class FriendlyCaptchaService
{
    private readonly ?Client $client;

    public function __construct(
        private readonly string $siteKey,
        private readonly string $secret,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled = true,
    ) {
        if ($this->enabled) {
            $config = new ClientConfig();
            $config->setAPIKey($this->secret);
            $config->setSitekey($this->siteKey);

            $this->client = new Client($config);
        } else {
            $this->client = null;
        }
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
        if (!$this->enabled) {
            return true;
        }

        $accepted = false;

        if ('' === $solution || '0' === $solution) {
            $this->logger->warning('Empty CAPTCHA solution provided');
        } else {
            try {
                $result = $this->client->verifyCaptchaResponse($solution);
                $accepted = $result->wasAbleToVerify() && $result->shouldAccept();

                if ($accepted) {
                    $this->logger->info('CAPTCHA verification successful');
                } else {
                    $this->logger->warning('CAPTCHA verification failed', [
                        'able_to_verify' => $result->wasAbleToVerify(),
                        'should_accept' => $result->shouldAccept(),
                    ]);
                }
            } catch (\Exception $exception) {
                $this->logger->error('CAPTCHA verification exception', [
                    'message' => $exception->getMessage(),
                    'exception_class' => $exception::class,
                    'trace' => $exception->getTraceAsString(),
                ]);
            }
        }

        return $accepted;
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }
}
