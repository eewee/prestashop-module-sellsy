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
 * Class EeweesellsyApiSellsyPeopleModel
 */
class EeweesellsyApiSellsyPeopleModel extends ObjectModel
{
    /**
     * People : infos
     * @return mixed
     */
    public static function getPeopleInfos()
    {
        $request = array(
            'method' => 'Peoples.getList',
            'params' => array ()
        );
        $resGeneral = sellsyConnect_curl::load()->requestApi($request);
        return $resGeneral->response->infos;
    }

    /**
     * People : result
     * @param array $d
     * @return array
     */
    public static function getPeopleResults($d)
    {
        $request = array(
            'method' => 'Peoples.getList',
            'params' => array (
                'pagination'    => array (
                    'nbperpage' => 10,
                    'pagenum'   => $d['pagenum']
                ),
            )
        );
        $response = sellsyConnect_curl::load()->requestApi($request);
        return $response->response->result;
    }

    /**
     * Get data db Sellsy for match with PrestaShop
     * @param array $d
     * @return array $res
     */
    public static function dataSellsyForMatchWithPrestashop($d)
    {
        $res = array();
        foreach ($d['response'] as $data) {
            $res[] = array(
                'id'                        => $data->id,
                'status'                    => $data->status,
                'civil'                     => strtolower($data->civil),
                'forename'                  => strtolower($data->forename),
                'name'                      => strtolower($data->name),
                'email'                     => strtolower($data->email),
                'birthdate'                 => $data->birthdate,
                'tel'                       => $data->tel,
                'mobile'                    => $data->mobile,
                'fax'                       => $data->fax,
                'stickyNote'                => $data->stickyNote,
                'massmailingUnsubscribed'   => $data->massmailingUnsubscribed,
            );
        }
        return $res;
    }

    /**
     * People : update
     * @param array $d
     * @return array (id, error, status)
     */
    public static function addSellsy($d)
    {
        // INIT
        $people = (array)$d['people'];
        $people['name']     = strtoupper($people['name']);
        $people['forename'] = ucfirst(strtolower($people['forename']));
        $people['email']    = strtolower($people['email']);

        // INSERT
        $request = array(
            'method' => 'Peoples.create',
            'params' => array (
                'people' => $people
            )
        );
        $response = sellsyConnect_curl::load()->requestApi($request);

        return array(
            'id'        => $response->response->id,
            'error'     => $response->error,
            'status'    => $response->status,
        );
    }

    /**
     * People : update
     * @param array $d
     * @return array (id, error, status)
     */
    public static function updateSellsy($d)
    {
        $id     = (int)$d['idPeople'];
        $people = (array)$d['people'];

        $request = array(
            'method' => 'Peoples.update',
            'params' => array (
                'id'        => $id,
                'people'    => $people,
            )
        );
        $response = sellsyConnect_curl::load()->requestApi($request);

        return array(
            'id'        => $response->response->id,
            'error'     => $response->error,
            'status'    => $response->status,
        );
    }

}
