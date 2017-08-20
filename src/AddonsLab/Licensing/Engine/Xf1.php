<?php

namespace AddonsLab\Licensing\Engine;

use AddonsLab\Licensing\Checker;
use AddonsLab\Licensing\Exception\DataIntegrityException;
use AddonsLab\Licensing\Exception\LicenseFailedException;
use AddonsLab\Licensing\Exception\LicenseNotFoundException;
use AddonsLab\Licensing\LicenseData;
use AddonsLab\Licensing\StorageDriver\AbstractStorageDriver;
use AddonsLab\Licensing\StorageDriver\Database;
use AddonsLab\Licensing\StorageDriver\File;

/**
 * Class Xf1
 * @package AddonsLab\Licensing\Engine
 * XenForo 1.x-related methods for license validation and rending license option
 */
abstract class Xf1 implements AbstractEngine
{
    public static function getEndpoint() {
        return '';
    }

    /**
     * @return AbstractStorageDriver[]
     * Default drivers working on XenForo 1.x
     */
    public static function getDrivers()
    {
        $file = (new File())
            ->setCacheDirectory(\XenForo_Helper_File::getExternalDataPath() . '/license')
            ->setCacheDirectoryUrl(\XenForo_Application::getOptions()->get('boardUrl') . '/data/license');
        $db = (new Database())->setDb(\XenForo_Application::getDb());

        return [
            $file,
            $db,
        ];
    }

    public static function getLicenseChecker()
    {
        // check for existing license
        $checker = new Checker(
            static::getDrivers()
        );
        
        $checker->setEndpoint(static::getEndpoint());

        return $checker;
    }

    public static function installDrivers()
    {
        foreach (static::getDrivers() AS $driver) {
            $driver->install();
        }
    }

    public static function getInvalidLicenseMessage($addonName)
    {
        return '<a style="color: red" href="https://customers.addonslab.com/" target="_blank">' . $addonName . ': invalid license detected.</a>';
    }

    public static function getLicenseEmptyMessage($addonName)
    {
        return '<a style="color: red" href="https://customers.addonslab.com/submitticket.php" target="_blank">' . $addonName
            . ': please enter your license key in Admin Panel.</a>';
    }

    public static function getBrandingMessage($addonName)
    {
        return '<a class="concealed" href="https://customers.addonslab.com/marketplace.php" target="_blank">' . $addonName . '</a>';
    }

    /**
     * @param $licenseKey
     * @param bool $logFailure
     * @return \AddonsLab\Licensing\LicenseData
     * @throws DataIntegrityException
     * @throws LicenseFailedException Does full re-validation and throws exceptions in case of failure
     */
    public static function licenseLocalReValidation($licenseKey, $logFailure = true)
    {
        $checker = static::getLicenseChecker();

        $licenseData = $checker->getLocalLicenseData($licenseKey);

        if ($licenseData === false) {
            // should not happen
            throw new LicenseNotFoundException();
        }

        if (!$licenseData->checkDataIntegrity()) {
            // prevent usage, as license integrity could not be checked
            throw new DataIntegrityException();
        }

        if ($licenseData->isValid() === false && $logFailure === true) {
            $licenseData->increaseFailCount();
	        $checker->setLicenseData($licenseKey, $licenseData);
	        if ($licenseData->isFailed()) {
		        throw new LicenseFailedException();
	        }
        }
	    
        return $licenseData;
    }

    /**
     * @param $licenseKey
     * @param bool $logFailure
     * @return \AddonsLab\Licensing\LicenseData
     * @throws DataIntegrityException
     * @throws LicenseFailedException Does full re-validation and throws exceptions in case of failure
     */
    public static function licenseReValidation($licenseKey, $logFailure = true)
    {
        $checker = static::getLicenseChecker();

        $licenseData = $checker->forceLicenseUpdate($licenseKey);

        if (!$licenseData->checkDataIntegrity()) {
            // prevent usage, as license integrity could not be checked
            throw new DataIntegrityException();
        }

        if ($licenseData->isValid() === false && $logFailure === true) {
            $licenseData->increaseFailCount();
        }

        $checker->setLicenseData($licenseKey, $licenseData);

        if ($licenseData->isFailed()) {
            throw new LicenseFailedException();
        }

        return $licenseData;
    }

    public static function disableAddon($addonId, $licenseMessage)
    {
        /** @var \XenForo_DataWriter_AddOn $dw */
        $dw = \XenForo_DataWriter::create('XenForo_DataWriter_AddOn');
        $dw->setExistingData('alcef');
        $dw->set('active', 0);
        $dw->save();

        $addonData = $dw->getMergedData();

        $mail = \XenForo_Mail::create('contact', []); // just an example template, we will change the content
        $mailObj = $mail->getPreparedMailHandler(
            \XenForo_Application::getOptions()->get('contactEmailAddress')
        );

        $mailObj->setBodyText($bodyText=sprintf('We have detected, that your license information at %s for add-on ID "%s" is invalid. 

Here is the reason why the license was considered invalid: %s

The add-on will be now disabled. Please check your license key or contact the add-on provider at %s to resolve the issue.

Please re-enable the add-on and provide a correct license key to continue using the add-on.		

Thank you!',
            \XenForo_Application::getOptions()->get('boardTitle'),
            $addonData['title'],
            $licenseMessage,
            $addonData['url']
        ));

        $mailObj->clearSubject();
        $mailObj->setSubject(sprintf('Add-on License Expired at %s', \XenForo_Application::getOptions()->get('boardTitle')));
        $mailObj->setBodyHtml(nl2br($bodyText));

        $mail->sendMail($mailObj);
    }

    /**
     * Renders the user group chooser option as a <select>.
     *
     * @param \XenForo_View $view View object
     * @param string $fieldPrefix Prefix for the HTML form field name
     * @param array $preparedOption Prepared option info
     * @param boolean $canEdit True if an "edit" link should appear
     *
     * @return \XenForo_Template_Abstract Template object
     */
    public static function renderLicenseOption(\XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
        if ($preparedOption['option_value']) {
            // force check of license and put the information here
            try {
                $licenseData = static::licenseReValidation(
                    $preparedOption['option_value'],
                    false
                );
                $preparedOption['explain'] = $licenseData->getFullStatusMessage();
            } catch (\Exception $exception) {
                // set the description field to the error message we got
                $preparedOption['explain'] = $exception->getMessage();
            }
        }
        return static::_render('option_list_option_textbox', $view, $fieldPrefix, $preparedOption, $canEdit);
    }

    /**
     * Renders the user group chooser option.
     *
     * @param string $templateName Name of template to render
     * @param \XenForo_View $view View object
     * @param string $fieldPrefix Prefix for the HTML form field name
     * @param array $preparedOption Prepared option info
     * @param boolean $canEdit True if an "edit" link should appear
     *
     * @return \XenForo_Template_Abstract Template object
     */
    protected static function _render($templateName, \XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
        return \XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal(
            $templateName, $view, $fieldPrefix, $preparedOption, $canEdit
        );
    }
}