<?php

namespace App\Scrapper;

use Goutte\Client;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FftaScrapper
{
    private string $baseUrl = "https://ffta-goal.multimediabs.com";

    protected Client $client;

    public function __construct(
        private readonly string $username,
        private readonly string $password
    ) {
        if (!$this->username || !$this->password) {
            throw new \Exception("FFTA Credentials not set");
        }
        $this->client = new Client();
        $this->login();
    }

    public function fetchLicenceeIdentity(string $fftaId): FftaIdentity
    {
        $url = sprintf(
            "%s/personnes/gettabpanel?personne.id=%s&tabId=Coordonnees_Personne",
            $this->baseUrl,
            $fftaId
        );
        $crawler = $this->client->request("GET", $url);

        $identity = new FftaIdentity();
        $identity->codeAdherent = $this->clean(
            $crawler
                ->filterXPath(
                    "descendant-or-self::*[@id = 'identite.codeAdherent']"
                )
                ->text()
        );
        $identity->email = $this->clean(
            $crawler
                ->filterXPath("descendant-or-self::*[@id = 'email']")
                ->text()
        );
        $identity->nom = $this->clean(
            $crawler
                ->filterXPath("descendant-or-self::*[@id = 'identite.nom']")
                ->text(),
            true
        );
        $identity->prenom = $this->clean(
            $crawler
                ->filterXPath("descendant-or-self::*[@id = 'identite.prenom']")
                ->text(),
            true
        );

        $mobileNode = $crawler->filterXPath(
            "descendant-or-self::*[@id = 'mobile']"
        );
        $telephoneNode = $crawler->filterXPath(
            "descendant-or-self::*[@id = 'telephone']"
        );
        $phone = null;
        if ($mobileNode->count() > 0) {
            $phone = $this->cleanPhoneNumber(
                $this->clean($mobileNode->text(), true)
            );
        } elseif ($telephoneNode->count() > 0) {
            $phone = $this->cleanPhoneNumber(
                $this->clean($telephoneNode->text(), true)
            );
        }
        $identity->mobile = $phone;

        return $identity;
    }

    protected function clean(string $text, bool $ucwords = false): string
    {
        $text = trim($text, " \t\n\r\0\x0B\xC2\xA0");
        if ($ucwords) {
            $text = ucwords(mb_strtolower($text));
        }

        return $text;
    }

    protected function cleanPhoneNumber(string $number): string
    {
        $number = str_replace(" ", "", $number);
        return preg_replace("/^0/", "+33", $number);
    }

    protected function login(): void
    {
        $crawler = $this->client->request(
            "GET",
            sprintf("%s/login", $this->baseUrl)
        );
        $form = $crawler->selectButton("CONNEXION")->form();
        $this->client->submit($form, [
            "username" => $this->username,
            "password" => $this->password,
        ]);
        /** @var Response $response */
        $response = $this->client->getResponse();
        if ($response->getStatusCode() !== 200) {
            throw new BadRequestHttpException(
                "Bad response from FFTA login procedure"
            );
        }
    }
}
