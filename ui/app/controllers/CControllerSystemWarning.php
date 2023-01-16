<?php
/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


class CControllerSystemWarning extends CController {

	protected function init() {
		$this->disableCsrfValidation();
	}

	protected function checkInput() {
		return $this->validateInput([]);
	}

	protected function checkPermissions() {
		return true;
	}

	protected function doAction() {
		CMessageHelper::restoreScheduleMessages();

		$data = [
			'theme' => getUserTheme(CWebUser::$data),
			'messages' => []
		];

		$data['messages'] = CMessageHelper::getMessages();

		$data['messages'] = array_map(function (array $message): string {
			return $message['message'];
		}, $data['messages']);

		$this->setResponse(new CControllerResponseData($data));
	}
}
