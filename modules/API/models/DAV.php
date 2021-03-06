<?php
/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class API_DAV_Model
{

	public $davUsers = [];

	public function runCronCardDav()
	{
		$dav = new self();
		\App\Log::trace(__CLASS__ . '::' . __METHOD__ . ' | Start CardDAV Sync ');
		$crmUsers = Users_Record_Model::getAll();
		$davUsers = $dav->getAllUser(1);
		foreach ($crmUsers as $key => $user) {
			if (array_key_exists($key, $davUsers)) {
				$user->set('david', $davUsers[$key]['david']);
				$user->set('addressbooksid', $davUsers[$key]['addressbooksid']);
				$dav->davUsers[$key] = $user;
				\App\Log::trace(__CLASS__ . '::' . __METHOD__ . ' | User is active ' . $user->getName());
			} else { // User is inactive
				\App\Log::warning(__CLASS__ . '::' . __METHOD__ . ' | User is inactive ' . $user->getName());
			}
		}
		$cardDav = new API_CardDAV_Model();
		$cardDav->log = $dav->log;
		$cardDav->davUsers = $dav->davUsers;
		$cardDav->cardDavCrm2Dav();
		$cardDav->cardDav2Crm();
		\App\Log::trace(__CLASS__ . '::' . __METHOD__ . ' | End CardDAV Sync ');
	}

	public function runCronCalDav()
	{
		$dav = new self();
		\App\Log::trace(__CLASS__ . '::' . __METHOD__ . ' | Start CalDAV Sync ');
		$crmUsers = Users_Record_Model::getAll();
		$davUsers = $dav->getAllUser(2);
		foreach ($crmUsers as $key => $user) {
			if (array_key_exists($key, $davUsers)) {
				$user->set('david', $davUsers[$key]['david']);
				$user->set('calendarsid', $davUsers[$key]['calendarsid']);
				$dav->davUsers[$key] = $user;
				\App\Log::trace(__CLASS__ . '::' . __METHOD__ . ' | User is active ' . $user->getName());
			} else { // User is inactive
				\App\Log::warning(__CLASS__ . '::' . __METHOD__ . ' | User is inactive ' . $user->getName());
			}
		}
		$cardDav = new API_CalDAV_Model();
		$cardDav->log = $dav->log;
		$cardDav->davUsers = $dav->davUsers;
		$cardDav->calDavCrm2Dav();
		$cardDav->calDav2Crm();
		\App\Log::trace(__CLASS__ . '::' . __METHOD__ . ' | End CalDAV Sync ');
	}

	public function getAllUser($type = 0)
	{
		$db = new App\db\Query();
		if ($type == 0) {
			$db->select([
					'dav_users.*',
					'addressbooksid' => 'dav_addressbooks.id',
					'calendarsid' => 'dav_calendars.id',
					'dav_principals.email',
					'dav_principals.displayname',
					'vtiger_users.status',
					'userid' => 'vtiger_users.id',
					'vtiger_users.user_name'
				])->from('dav_users')
				->innerJoin('vtiger_users', 'vtiger_users.id = dav_users.userid')
				->innerJoin('dav_principals', 'dav_principals.userid = dav_users.userid')
				->leftJoin('dav_addressbooks', 'dav_addressbooks.principaluri = dav_principals.uri')
				->leftJoin('dav_calendars', 'dav_calendars.principaluri = dav_principals.uri');
		} elseif ($type == 1) {
			$db->select([
				'david' => 'dav_users.id',
				'userid' => 'dav_users.userid',
				'addressbooksid' => 'dav_addressbooks.id'
			])->from('dav_users')
				->innerJoin('vtiger_users', 'vtiger_users.id = dav_users.userid')
				->innerJoin('dav_principals', 'dav_principals.userid = dav_users.userid')
				->innerJoin('dav_addressbooks', 'dav_addressbooks.principaluri = dav_principals.uri')
				->where(['vtiger_users.status' => 'Active']);
		} elseif ($type == 2) {
			$db->select([
				'david' => 'dav_users.id',
				'userid' => 'dav_users.userid',
				'calendarsid' => 'dav_calendars.id'
			])->from('dav_users')
				->innerJoin('vtiger_users', 'vtiger_users.id = dav_users.userid')
				->innerJoin('dav_principals', 'dav_principals.userid = dav_users.userid')
				->innerJoin('dav_calendars', 'dav_calendars.principaluri = dav_principals.uri')
				->where(['vtiger_users.status' => 'Active']);
		}
		$dataReader = $db->createCommand()->query();
		$users = [];
		while ($row = $dataReader->read()) {
			$users[$row['userid']] = $row;
		}
		return $users;
	}
}
