<?php
/**
 * 2016-2017 EEWEE
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <prestashop@eewee.fr>
 *  @copyright 2016-2017 EEWEE
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

/**
 * Class EeweesellsyApiSupportModel
 */
class EeweesellsyApiSupportModel extends ObjectModel
{
    /**
     * Staff : infos
     * @return mixed
     */
    public static function create($d=array())
    {
        // INIT
        $subject        = $d['subject'];
        $message        = $d['message'];
        $requesterEmail = $d['requesterEmail'];
        $staffId        = $d['staffId'];

        $request = array(
            'method' => 'Support.create',
            'params' => array(
                'ticket'        => array(
                    'subject'       => $subject,
                    'message'       => $message,
                    'sender'        => Configuration::get('PS_SHOP_EMAIL'),
                    'step'          => "active",	// default
                    'source'        => "email",
                    'requesterEmail'=> $requesterEmail,
                    //'thirdid'     => {{thirdid}},
                    //'thirdcontactid'=> {{thirdcontactid}},
                    'staffid'       => $staffId,	// assignedTo
                    //'groupid'     => {{groupid}}
                )
            )
        );

        $response = sellsyConnect_curl::load()->requestApi($request);
        return $response;
    }
}
