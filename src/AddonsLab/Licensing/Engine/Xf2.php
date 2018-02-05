<?php

namespace AddonsLab\Licensing\Engine;

use AddonsLab\Licensing\Checker;
use AddonsLab\Licensing\Exception\DataIntegrityException;
use AddonsLab\Licensing\Exception\LicenseFailedException;
use AddonsLab\Licensing\Exception\LicenseNotFoundException;
use AddonsLab\Licensing\Exception\TrialExpiredException;
use AddonsLab\Licensing\LicenseData;
use AddonsLab\Licensing\StorageDriver\AbstractStorageDriver;
use AddonsLab\Licensing\StorageDriver\Database;
use AddonsLab\Licensing\StorageDriver\File;
use XF\Entity\AddOn;
use XF\Mail\Mailer;

/**
 * Class Xf1
 * @package AddonsLab\Licensing\Engine
 * XenForo 1.x-related methods for license validation and rending license option
 */
abstract class Xf2 extends AbstractEngine
{
    public static function getEndpoint()
    {
        return '';
    }

    public static function getLicenseChecker()
    {
        $checker = parent::getLicenseChecker();

        $checker->setRemoteChecker(function ($endpoint, $queryData, LicenseData $licenseData) {
            $client = \XF::app()->http()->client();
            $request = $client->createRequest(
                'GET',
                $endpoint . '?' . http_build_query($queryData)
            );
            $response = $client->send($request);
            $result = $response->getBody()->getContents();
            $status_code = $response->getStatusCode();

            if ($status_code !== 200) {
                // do not increase failed count so we don't disable the product because of our server moving or unavailable
                $licenseData->setServerError($status_code . ' ' . $result);
                return false;
            }

            if (is_object($result)) {
                $jsonResponse = $result;
            } else {
                $jsonResponse = json_decode($result);
            }

            if (!$jsonResponse) {
                $licenseData->increaseFailCount();
                $licenseData->setLastError('Failed to decode license data - ' . substr($result, 0, 100) . '...');
                return false;
            }

            return $jsonResponse;
        });

        return $checker;
    }


    public static function getBoardDomain()
    {
        return parse_url(\XF::app()->options()['boardUrl'], PHP_URL_HOST);
    }

    /**
     * @return AbstractStorageDriver[]
     * Default drivers working on XenForo 1.x
     */
    public static function getDrivers()
    {
        $dataDir = \XF::app()->config('externalDataPath');

        $file = (new File())
            ->setCacheDirectory($dataDir . '/license')
            ->setCacheDirectoryUrl(\XF::app()->applyExternalDataUrl('license', true));
        $db = (new Database())->setDb(\XF::app()->db());
        $db->setTable('xf_al_license');

        return [
            $file,
            $db,
        ];
    }

    public static function getBrandingMessage($addonName)
    {
        return '<a class="u-concealed" href="https://customers.addonslab.com/marketplace.php" target="_blank">' . $addonName . '</a>';
    }

    public static function disableAddon($addonId, $licenseMessage)
    {
        /** @var AddOn $addon */
        $addon = \XF::finder('XF:AddOn')->whereId($addonId)->fetchOne();

        /** Add */
        if ($addon && $addon->active) {
            $addon->active = 0;
            $addon->save();

            $addonData = $addon->toArray();

            /** @var Mailer $mailer */
            $mailer = \XF::app()->mailer();
            $mail = $mailer->newMail();

            $bodyText = sprintf('We have detected, that your license information at %s for add-on ID "%s" is invalid. 

Here is the reason why the license was considered invalid: %s

The add-on will be now disabled. Please check your license key or contact the add-on provider at %s to resolve the issue.

Please re-enable the add-on and provide a correct license key to continue using the add-on.		

Thank you!', \XF::app()->options()['boardTitle'],
                $addon->title,
                $licenseMessage,
                'https://customers.addonslab.com/submitticket.php'
            );

            $output = [
                'subject' => sprintf('Add-on License Expired at %s', \XF::app()->options()['boardTitle']),
                'html' => nl2br($bodyText),
                'text' => $bodyText,
                'headers' => []
            ];

            $mail->setTo(\XF::app()->options()['contactEmailAddress'], '');
            $mail->setContent($output['subject'], $output['html'], $output['text']);
            $mail->send();
        }
    }

    public static function renderLicenseOption(\XF\Entity\Option $option, array $htmlParams)
    {
        if ($option->option_value) {
            try {
                $licenseData = static::licenseReValidation(
                    $option->option_value,
                    false
                );
                $message = $licenseData->getFullStatusMessage();
            } catch (\Exception $exception) {
                $message = $exception->getMessage();
            }
        }

        $templater = \XF::app()->templater();
        
        $formatParams=$option->getFormatParams();

        return $templater->formTextBoxRow(array(
            'name' => $htmlParams['inputName'],
            'value' => $option['option_value'],
            'type' => isset($formatParams['type'])? $formatParams['type']:'',
            'class' => isset($formatParams['class']) ? $formatParams['class'] : '',
        ), array(
            'label' => $templater->escape($option['title']),
            'hint' => $templater->escape($htmlParams['hintHtml']),
            'explain' => $templater->escape($message),
            'finalhtml' => $templater->escape($htmlParams['listedHtml']),
        ));
    }
}